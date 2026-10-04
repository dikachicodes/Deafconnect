<?php

declare(strict_types=1);

session_name('deafconnect_session');
session_start();

require_once __DIR__ . '/database.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireJsonRequest(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function requireCsrf(array $data = []): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $token)) {
        jsonResponse(['success' => false, 'message' => 'Security token expired. Refresh the page and try again.'], 419);
    }
}

function guestKey(): string
{
    if (empty($_SESSION['guest_key'])) {
        $_SESSION['guest_key'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['guest_key'];
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['success' => false, 'message' => 'Administrator authentication required.'], 401);
    }
}

function cleanText(?string $value, int $max = 5000): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/', ' ', $value) ?? '';
    return mb_substr($value, 0, $max);
}
