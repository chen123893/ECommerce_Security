<?php

include "../../includes/auth.php";
require_admin_role();
include "../../../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

$delete = mysqli_prepare(
    $conn,
    "DELETE FROM clients WHERE id = ?"
);

mysqli_stmt_bind_param(
    $delete,
    "i",
    $id
);

mysqli_stmt_execute($delete);

if (mysqli_stmt_affected_rows($delete) > 0) {
    security_log_event('admin_client_deleted', 'success', ['target_type' => 'client', 'target_id' => $id]);
}

header("Location: index.php");
exit;

?>