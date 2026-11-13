<?php

const SHOPEASE_IDLE_TIMEOUT = 900;
const SHOPEASE_PATH = '/E-Commerce-Management-System/';

function start_shopease_session(bool $admin = false): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SHOPEASE_IDLE_TIMEOUT);
    session_name($admin ? 'ShopEaseAdmin' : 'ShopEaseCustomer');
    session_set_cookie_params([
        'lifetime' => SHOPEASE_IDLE_TIMEOUT,
        'path' => SHOPEASE_PATH . ($admin ? 'admin/' : ''),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $now = time();
    $lastActivity = $_SESSION['LAST_ACTIVITY'] ?? null;
    if (is_int($lastActivity) && $now - $lastActivity >= SHOPEASE_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    } elseif (($_COOKIE[session_name()] ?? null) === session_id()) {
        // Extend the browser cookie when a request uses the existing session.
        set_shopease_session_cookie(session_id(), $now + SHOPEASE_IDLE_TIMEOUT);
    }
    $_SESSION['LAST_ACTIVITY'] = $now;
}

function end_shopease_session(): void
{
    $_SESSION = [];
    set_shopease_session_cookie('', time() - 3600);
    session_destroy();
}

function set_shopease_session_cookie(string $value, int $expires): void
{
    $params = session_get_cookie_params();
    unset($params['lifetime']);
    $params['expires'] = $expires;
    setcookie(session_name(), $value, $params);
}
