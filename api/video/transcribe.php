<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../lib/GeminiTranscriptionService.php';

requireCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'POST requests are required.'], 405);
}

$geminiConfig = require __DIR__ . '/../../config/gemini.php';
$maxBytes = (int)($geminiConfig['max_upload_bytes'] ?? 100 * 1024 * 1024);

if (empty($_FILES['video']) || !is_array($_FILES['video'])) {
    jsonResponse(['success' => false, 'message' => 'Choose a video file to transcribe.'], 422);
}

$file = $_FILES['video'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The video is larger than the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The video is larger than the allowed upload size.',
        UPLOAD_ERR_PARTIAL => 'The video upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Choose a video file to transcribe.',
    ];
    jsonResponse(['success' => false, 'message' => $messages[$file['error']] ?? 'The video upload failed.'], 422);
}

$size = (int)$file['size'];
if ($size <= 0 || $size > $maxBytes) {
    jsonResponse(['success' => false, 'message' => 'Video files must be between 1 byte and 100 MB.'], 422);
}

$tmpPath = (string)$file['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string)$finfo->file($tmpPath);
$allowed = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov',
    'video/x-msvideo' => 'avi',
    'video/mpeg' => 'mpeg',
];
if (!isset($allowed[$mime])) {
    jsonResponse(['success' => false, 'message' => 'Unsupported video format. Use MP4, WebM, MOV, AVI or MPEG.'], 422);
}

$extension = $allowed[$mime];
$storedName = bin2hex(random_bytes(16)) . '.' . $extension;
$uploadDir = dirname(__DIR__, 2) . '/uploads/videos';
$captionDir = dirname(__DIR__, 2) . '/uploads/captions';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    jsonResponse(['success' => false, 'message' => 'The server could not create the video upload directory.'], 500);
}
if (!is_dir($captionDir) && !mkdir($captionDir, 0755, true) && !is_dir($captionDir)) {
    jsonResponse(['success' => false, 'message' => 'The server could not create the captions directory.'], 500);
}

$videoPath = $uploadDir . '/' . $storedName;
if (!move_uploaded_file($tmpPath, $videoPath)) {
    jsonResponse(['success' => false, 'message' => 'The server could not save the uploaded video.'], 500);
}

$originalName = cleanText((string)($file['name'] ?? 'uploaded-video'), 180);
$transcript = null;
try {
    $service = new GeminiTranscriptionService($geminiConfig);
    $transcript = $service->transcribeVideo($videoPath, $mime, $originalName);

    $vtt = "WEBVTT\n\n";
    foreach ($transcript['segments'] as $segment) {
        $vtt .= vttTime((float)$segment['start']) . ' --> ' . vttTime((float)$segment['end']) . "\n";
        $vtt .= str_replace(["\r", "\n"], ' ', $segment['text']) . "\n\n";
    }

    $captionName = bin2hex(random_bytes(16)) . '.vtt';
    $captionPath = $captionDir . '/' . $captionName;
    if (file_put_contents($captionPath, $vtt, LOCK_EX) === false) {
        throw new RuntimeException('The transcript was generated, but the caption file could not be saved.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO videos (original_filename, stored_filename, mime_type, file_size, video_path, vtt_path, transcript_json, transcription_status)
         VALUES (:original_filename, :stored_filename, :mime_type, :file_size, :video_path, :vtt_path, :transcript_json, :status)'
    );
    $stmt->execute([
        'original_filename' => $originalName,
        'stored_filename' => $storedName,
        'mime_type' => $mime,
        'file_size' => $size,
        'video_path' => 'uploads/videos/' . $storedName,
        'vtt_path' => 'uploads/captions/' . $captionName,
        'transcript_json' => json_encode($transcript, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'status' => 'completed',
    ]);

    jsonResponse([
        'success' => true,
        'message' => count($transcript['segments']) ? 'AI transcription completed successfully.' : 'The video contains no detectable spoken dialogue.',
        'video_url' => 'uploads/videos/' . rawurlencode($storedName),
        'vtt_url' => 'uploads/captions/' . rawurlencode($captionName),
        'language' => $transcript['language'],
        'transcript' => $transcript['segments'],
    ]);
} catch (Throwable $e) {
    @unlink($videoPath);
    error_log('DeafConnect transcription error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'The video could not be processed right now. Please try again with another video or a shorter file.'], 502);
}

function vttTime(float $seconds): string
{
    $seconds = max(0, $seconds);
    $hours = (int)floor($seconds / 3600);
    $minutes = (int)floor(($seconds % 3600) / 60);
    $whole = floor($seconds);
    $millis = (int)round(($seconds - $whole) * 1000);
    if ($millis >= 1000) {
        $whole++;
        $millis = 0;
    }
    $secs = (int)($whole % 60);
    return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $secs, $millis);
}
