<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Location: ' . (is_logged_in() ? 'pages/tasks.php' : 'pages/login.php'));
exit;
