<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}


function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}


function redirect_if_logged_in(): void
{
    if (is_logged_in()) {
        header('Location: task.php');
        exit;
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
