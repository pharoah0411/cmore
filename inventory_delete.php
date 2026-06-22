<?php
include('config.php');

// config.php normally starts the session; this is just a safety net.
if (session_status() === PHP_SESSION_NONE) session_start();

/*
 * OPTIONAL — restrict deletion to admins only.
 * Uncomment to require an Admin role (matches the Staff Management gate on the dashboard):
 *
 * if (($_SESSION['ROLE'] ?? '') !== 'Admin') {
 *     $_SESSION['flash'] = ['type' => 'error', 'msg' => 'You do not have permission to delete products.'];
 *     header('Location: inventory.php');
 *     exit;
 * }
 */

// Work out where to send the user back to (preserves their filters), guarding against open redirects.
$redirect = 'inventory.php';
$return_to = $_POST['return_to'] ?? '';
if ($return_to !== ''
    && strpos($return_to, '://') === false
    && substr($return_to, 0, 2) !== '//'
    && strpos($return_to, 'inventory.php') !== false) {
    $redirect = $return_to;
}

// Deleting is a state change — only accept POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

// CSRF protection.
if (empty($_POST['csrf_token'])
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Your session expired. Please try again.'];
    header('Location: ' . $redirect);
    exit;
}

// Validate the id (PRODUCT_ID is an integer).
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
if ($product_id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid product selected.'];
    header('Location: ' . $redirect);
    exit;
}

// Look up the name first so we can show a friendly message.
$name = null;
$stmt = mysqli_prepare($conn, "SELECT BRAND_NAME FROM PRODUCT WHERE PRODUCT_ID = ?");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $name);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($name === null) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'That product no longer exists.'];
    header('Location: ' . $redirect);
    exit;
}

// Attempt the delete with a prepared statement.
$del = mysqli_prepare($conn, "DELETE FROM PRODUCT WHERE PRODUCT_ID = ?");
mysqli_stmt_bind_param($del, 'i', $product_id);

if (mysqli_stmt_execute($del)) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '"' . $name . '" was deleted.'];
} else {
    // 1451 = cannot delete a parent row referenced by a foreign key (e.g. sales history).
    if (mysqli_errno($conn) === 1451) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'msg'  => 'Could not delete "' . $name . '" because it is linked to existing sales or other records. Remove those first, or archive the product instead.'
        ];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Something went wrong while deleting the product. Please try again.'];
    }
}
mysqli_stmt_close($del);

header('Location: ' . $redirect);
exit;