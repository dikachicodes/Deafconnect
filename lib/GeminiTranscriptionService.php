<?php

declare(strict_types=1);

final class GeminiTranscriptionService
{
    private string $apiKey;
    private string $model;
    private int $timeout;
    private const BASE_URL = 'https://generativelanguage.googleapis.com';

    public function __construct(array $config)
    {
        $this->apiKey = trim((string)($config['api_key'] ?? ''));
        $this->model = trim((string)($config['model'] ?? 'gemini-3.8-flash'));
        $this->timeout = max(30, (int)($config['request_timeout'] ?? 180));

        if ($this->apiKey === '' || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
            throw new RuntimeException('Gemini API key is not configured. Copy config/gemini.example.php to config/gemini.php and add your key.');
        }
    }

    public function transcribeVideo(string $path, string $mimeType, string $displayName): array
    {
        $file = $this->uploadFile($path, $mimeType, $displayName);
        try {
            $file = $this->waitUntilActive($file);
            $result = $this->generateTranscript($file['uri'], $file['mimeType'] ?? $mimeType);
            return $this->normalizeTranscript($result);
        } finally {
            if (!empty($file['name'])) {
                $this->deleteFile($file['name']);
            }
        }
    }

    private function uploadFile(string $path, string $mimeType, string $displayName): array
    {
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('The uploaded video could not be read.');
        }

        $headers = [];
        $ch = curl_init(self::BASE_URL . '/upload/v1beta/files');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'x-goog-api-key: ' . $this->apiKey,
                'X-Goog-Upload-Protocol: resumable',
                'X-Goog-Upload-Command: start',
                'X-Goog-Upload-Header-Content-Length: ' . $size,
                'X-Goog-Upload-Header-Content-Type: ' . $mimeType,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['file' => ['display_name' => $displayName]], JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HEADERFUNCTION => function ($curl, string $header) use (&$headers): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            },
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Gemini upload session could not be started: ' . $error);
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Gemini rejected the upload session (HTTP ' . $status . ').');
        }

        $uploadUrl = $headers['x-goog-upload-url'] ?? $headers['location'] ?? '';
        if ($uploadUrl === '') {
            throw new RuntimeException('Gemini did not return an upload URL.');
        }

        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('The uploaded video could not be opened.');
        }

        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Length: ' . $size,
                'X-Goog-Upload-Offset: 0',
                'X-Goog-Upload-Command: upload, finalize',
            ],
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);

        if ($response === false || $error !== '') {
            throw new RuntimeException('Gemini video upload failed: ' . $error);
        }

        $data = json_decode($response, true);
        if ($status < 200 || $status >= 300 || !is_array($data) || empty($data['file'])) {
            throw new RuntimeException('Gemini video upload failed (HTTP ' . $status . ').');
        }

        return $data['file'];
    }

    private function waitUntilActive(array $file): array
    {
        $name = (string)($file['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Gemini returned an invalid file reference.');
        }

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $current = $this->getFile($name);
            $state = strtoupper((string)($current['state'] ?? 'ACTIVE'));
            if ($state === 'ACTIVE') {
                return $current;
            }
            if ($state === 'FAILED') {
                throw new RuntimeException('Gemini could not process the uploaded video.');
            }
            sleep(2);
        }

        throw new RuntimeException('Gemini video processing timed out. Try a shorter video.');
    }

    private function getFile(string $name): array
    {
        $encodedName = implode('/', array_map('rawurlencode', explode('/', $name)));
        $data = $this->request('GET', self::BASE_URL . '/v1beta/' . $encodedName);
        return $data;
    }

    private function generateTranscript(string $fileUri, string $mimeType): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'language' => ['type' => 'string'],
                'segments' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'start' => ['type' => 'number'],
                            'end' => ['type' => 'number'],
                            'text' => ['type' => 'string'],
                        ],
                        'required' => ['start', 'end', 'text'],
                    ],
                ],
            ],
            'required' => ['segments'],
        ];

        $prompt = <<<PROMPT
Transcribe all spoken dialogue in this video for Deaf and hard-of-hearing viewers.

Return only JSON matching the supplied schema.

Requirements:
- Create sequential caption segments covering the spoken dialogue.
- "start" and "end" are seconds from the beginning of the video, as numbers.
- Keep each segment concise enough to read comfortably as a caption (normally one or two sentences).
- Preserve the meaning and wording of the speech. Do not invent dialogue.
- Include punctuation and normal sentence casing.
- If there is no spoken dialogue, return an empty segments array.
- Set "language" to the primary spoken language when you can identify it.
PROMPT;

        $payload = [
            'model' => $this->model,
            'store' => false,
            'input' => [
                [
                    'type' => 'video',
                    'uri' => $fileUri,
                    'mime_type' => $mimeType,
                ],
                [
                    'type' => 'text',
                    'text' => $prompt,
                ],
            ],
            'response_format' => [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => $schema,
            ],
        ];

        return $this->request(
            'POST',
            self::BASE_URL . '/v1beta/interactions',
            $payload
        );
    }

    private function normalizeTranscript(array $response): array
    {
        $text = (string)($response['output_text'] ?? '');
        if ($text === '') {
            foreach (($response['steps'] ?? []) as $step) {
                foreach (($step['content'] ?? []) as $part) {
                    if (($part['type'] ?? '') === 'text') {
                        $text .= (string)($part['text'] ?? '');
                    }
                }
            }
        }
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty transcription.');
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded) || !isset($decoded['segments']) || !is_array($decoded['segments'])) {
            throw new RuntimeException('Gemini returned a transcription in an unexpected format.');
        }

        $segments = [];
        foreach ($decoded['segments'] as $segment) {
            $start = isset($segment['start']) ? (float)$segment['start'] : -1;
            $end = isset($segment['end']) ? (float)$segment['end'] : -1;
            $caption = trim((string)($segment['text'] ?? ''));

            if ($start < 0 || $end <= $start || $caption === '') {
                continue;
            }

            $segments[] = [
                'start' => round($start, 3),
                'end' => round($end, 3),
                'text' => mb_substr($caption, 0, 500),
            ];
        }

        usort($segments, fn(array $a, array $b): int => $a['start'] <=> $b['start']);

        return [
            'language' => cleanText((string)($decoded['language'] ?? ''), 40),
            'segments' => $segments,
        ];
    }

    private function deleteFile(string $name): void
    {
        try {
            $encodedName = implode('/', array_map('rawurlencode', explode('/', $name)));
            $this->request('DELETE', self::BASE_URL . '/v1beta/' . $encodedName);
        } catch (Throwable $ignored) {
            // Gemini Files API files expire automatically; deletion is best-effort.
        }
    }

    private function request(string $method, string $url, ?array $payload = null): array
    {
        $ch = curl_init($url);
        $headers = [
            'x-goog-api-key: ' . $this->apiKey,
            'Accept: application/json',
        ];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Gemini request failed: ' . $error);
        }

        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($data) ? (string)($data['error']['message'] ?? '') : '';
            throw new RuntimeException('Gemini API error (HTTP ' . $status . ')' . ($message ? ': ' . $message : '.'));
        }

        if (!is_array($data)) {
            throw new RuntimeException('Gemini returned an invalid response.');
        }

        return $data;
    }
}
