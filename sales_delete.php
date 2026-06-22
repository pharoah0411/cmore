<?php
include('config.php');

// Only Optometrist and Admin can delete sales
if (!isset($_SESSION['ROLE']) || ($_SESSION['ROLE'] !== 'Optometrist' && $_SESSION['ROLE'] !== 'Admin')) {
    header("Location: sales.php?error=unauthorized");
    exit();
}

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sale_id <= 0) {
    header("Location: sales.php?error=invalid_id");
    exit();
}

// Get sale details for audit logging
$sale_sql = "SELECT PATIENT_ID, STAFF_ID FROM SALES WHERE SALE_ID = $sale_id";
$sale_result = mysqli_fetch_assoc(mysqli_query($conn, $sale_sql));

if (!$sale_result) {
    header("Location: sales.php?error=not_found");
    exit();
}

// Delete related SALES_ITEM records first (referential integrity)
$delete_items = "DELETE FROM SALES_ITEM WHERE SALE_ID = $sale_id";
mysqli_query($conn, $delete_items);

// Delete the SALES record
$delete_sale = "DELETE FROM SALES WHERE SALE_ID = $sale_id";
if (mysqli_query($conn, $delete_sale)) {
    // Log the deletion in audit trail
    systemLog($conn, "Deleted sale transaction #TXN-$sale_id", "SALES", $sale_id);
    
    header("Location: sales.php?msg=deleted");
} else {
    header("Location: sales.php?error=delete_failed");
}
exit();
?>
