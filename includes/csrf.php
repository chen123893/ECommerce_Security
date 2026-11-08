<?php

function csrf_token(string $action): string
{
    return $_SESSION['csrf_tokens'][$action] ??= bin2hex(random_bytes(32));
}

function csrf_token_is_valid(string $action, mixed $token): bool
{
    $expected = $_SESSION['csrf_tokens'][$action] ?? null;
    return is_string($expected) && is_string($token) && hash_equals($expected, $token);
}
