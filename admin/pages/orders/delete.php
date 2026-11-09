<?php
include '../../includes/auth.php';
require_admin_role();
include '../../../config/database.php';

$id = $_GET['id'];

$sql = "DELETE FROM orders WHERE id = $id";

if (mysqli_query($conn, $sql)) {

    if (mysqli_affected_rows($conn) > 0) {
        security_log_event('admin_order_deleted', 'success', ['target_type' => 'order', 'target_id' => (int)$id]);
    }

    header("Location: index.php");
    exit;

} else {

    echo "Error: " . mysqli_error($conn);

}

?>