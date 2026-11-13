<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
require_once __DIR__ . '/../../includes/security_audit.php';
if (isset($_SESSION['user_id'])) {
    security_log_event('logout', 'success', ['context' => ['portal' => 'customer']]);
}

end_shopease_session();

header("Location: ../../pages/home.php");
exit;
?>
