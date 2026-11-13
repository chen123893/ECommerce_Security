<?php

require_once __DIR__ . '/../includes/session.php';
start_shopease_session(true);


require_once __DIR__ . '/../includes/security_audit.php';
if (isset($_SESSION['admin_id'])) {
    security_log_event('logout', 'success', ['context' => ['portal' => 'admin']]);
}

end_shopease_session();

// Go back to login
header("Location: login.php");
exit;

?>