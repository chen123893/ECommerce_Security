<?php
include '../../includes/auth.php';
require_admin_role();
include '../../../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

$sql = "DELETE FROM brands
        WHERE id = $id";


if (mysqli_query($conn, $sql)) {

    header("Location: index.php");
    exit();

} else {

    echo "Error: " . mysqli_error($conn);

}

?>