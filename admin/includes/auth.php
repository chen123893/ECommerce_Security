<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


function require_admin(): void
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: /E-Commerce-Management-System/admin/login.php");
        exit;
    }

    if (!in_array($_SESSION['admin_role'] ?? '', ['Admin', 'Staff'], true)) {
        $_SESSION = [];
        session_destroy();
        header("Location: /E-Commerce-Management-System/admin/login.php");
        exit;
    }
}

function require_admin_role(): void
{
    require_admin();

    if (($_SESSION['admin_role'] ?? '') !== 'Admin') {
        deny_admin_access();
    }
}

/**
 * Pages Staff may open, relative to /admin and in lowercase. Admin has full access.
 * Any page not listed is refused for non-Admin roles (deny by default).
 */
function staff_allowed_pages(): array
{
    return [
        'index.php',
        'pages/products/index.php',
        'pages/products/show.php',
        'pages/products/create.php',
        'pages/products/edit.php',
        'pages/orders/index.php',
        'pages/orders/order_items.php',
        'pages/orders/edit.php',
        'pages/clients/index.php',
        'pages/clients/show.php',
        'pages/security/mfa.php',
    ];
}

function is_admin_role(): bool
{
    return ($_SESSION['admin_role'] ?? '') === 'Admin';
}

function admin_can_access(string $page): bool
{
    return is_admin_role() || in_array(strtolower($page), staff_allowed_pages(), true);
}

/** Path of the running script relative to /admin, or '' if it cannot be resolved. */
function current_admin_page(): string
{
    $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $base = str_replace('\\', '/', (string) realpath(dirname(__DIR__))) . '/';

    if ($script === '' || stripos($script, $base) !== 0) {
        return '';
    }

    return strtolower(substr($script, strlen($base)));
}

function enforce_admin_page_access(): void
{
    if (!admin_can_access(current_admin_page())) {
        deny_admin_access();
    }
}

/** Sends HTTP 403 with the access-restricted page and stops the request. */
function deny_admin_access(): void
{
    http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 Forbidden | Access Restricted</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <link rel="stylesheet" href="/E-Commerce-Management-System/admin/assets/css/admin.css">
            <style>
                .unauthorized-page-body {
                    min-height: 100vh;
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: radial-gradient(circle at 15% 15%, rgba(239, 68, 68, 0.12) 0%, transparent 45%),
                                radial-gradient(circle at 85% 85%, rgba(25, 169, 116, 0.1) 0%, transparent 45%),
                                linear-gradient(135deg, #0B132B 0%, #172B4D 50%, #064E3B 100%);
                    padding: 2rem 1.5rem;
                    box-sizing: border-box;
                    margin: 0;
                    font-family: 'Inter', system-ui, -apple-system, sans-serif;
                }
                .unauthorized-card {
                    background: rgba(255, 255, 255, 0.96);
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    border-radius: 24px;
                    box-shadow: 0 25px 60px rgba(11, 19, 43, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.4);
                    max-width: 480px;
                    width: 100%;
                    padding: 3.5rem 2.5rem;
                    text-align: center;
                    position: relative;
                    animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                }
                @keyframes cardAppear {
                    from {
                        opacity: 0;
                        transform: translateY(24px) scale(0.96);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                .unauthorized-icon-wrap {
                    width: 80px;
                    height: 80px;
                    border-radius: 20px;
                    background: #FEF2F2;
                    border: 1px solid #FCA5A5;
                    color: #DC2626;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2.5rem;
                    margin: 0 auto 1.5rem;
                    box-shadow: 0 12px 25px rgba(220, 38, 38, 0.25);
                }
                .unauthorized-eyebrow {
                    font-size: 0.75rem;
                    font-weight: 800;
                    letter-spacing: 0.08em;
                    color: #DC2626;
                    text-transform: uppercase;
                    margin-bottom: 0.5rem;
                    display: inline-block;
                    background: rgba(220, 38, 38, 0.08);
                    padding: 0.35rem 0.85rem;
                    border-radius: 50px;
                }
                .unauthorized-title {
                    font-size: 1.85rem;
                    font-weight: 800;
                    color: #0F172A;
                    margin-bottom: 0.75rem;
                    letter-spacing: -0.025em;
                }
                .unauthorized-text {
                    color: #64748B;
                    font-size: 0.95rem;
                    line-height: 1.6;
                    margin-bottom: 2.25rem;
                    margin-top: 0;
                }
                .unauthorized-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                }
                @media (min-width: 480px) {
                    .unauthorized-actions {
                        flex-direction: row;
                        justify-content: center;
                    }
                }
                .btn-unauthorized-back {
                    min-height: 50px;
                    padding: 0 1.5rem;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    border-radius: 12px;
                    font-weight: 600;
                    font-size: 0.9375rem;
                    color: #475569;
                    background: #F1F5F9;
                    border: 1.5px solid #E2E8F0;
                    text-decoration: none;
                    transition: all 0.25s ease;
                    flex: 1;
                }
                .btn-unauthorized-back:hover {
                    background: #E2E8F0;
                    color: #0F172A;
                }
                .btn-unauthorized-home {
                    min-height: 50px;
                    padding: 0 1.5rem;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 0.9375rem;
                    color: #FFFFFF;
                    background: linear-gradient(135deg, #19A974 0%, #059669 100%);
                    border: none;
                    text-decoration: none;
                    box-shadow: 0 8px 20px rgba(25, 169, 116, 0.35);
                    transition: all 0.25s ease;
                    flex: 1;
                }
                .btn-unauthorized-home:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 12px 25px rgba(25, 169, 116, 0.45);
                    color: #FFFFFF;
                }
            </style>
        </head>
        <body class="unauthorized-page-body">
            <div class="unauthorized-card">
                <div class="unauthorized-icon-wrap">
                    <i class="bi bi-shield-x"></i>
                </div>
                <div>
                    <span class="unauthorized-eyebrow">403 FORBIDDEN ACCESS</span>
                </div>
                <h1 class="unauthorized-title">Access Restricted</h1>
                <p class="unauthorized-text">
                    You do not have permission to view or manage this section. Administrative privileges are required.
                </p>
                <div class="unauthorized-actions">
                    <a href="javascript:history.back()" class="btn-unauthorized-back">
                        <i class="bi bi-arrow-left"></i>
                        <span>Go Back</span>
                    </a>
                    <a href="/E-Commerce-Management-System/admin/index.php" class="btn-unauthorized-home">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
}

require_admin();
enforce_admin_page_access();

?>