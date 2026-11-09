<?php
include '../../includes/auth.php';
require_admin_role();
include '../../../config/database.php';

$id = $_GET['id'];

try {

    $sql = "DELETE FROM users WHERE id = $id";

    mysqli_query($conn, $sql);

    if (mysqli_affected_rows($conn) > 0) {
        security_log_event('admin_user_deleted', 'success', ['target_type' => 'user', 'target_id' => (int)$id]);
    }

    header("Location: index.php");
    exit;

} catch (mysqli_sql_exception $e) {

    header("Location: index.php?error=orders");
    exit;

}

?>