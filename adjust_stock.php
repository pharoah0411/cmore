<?php 
include('config.php'); 

if(!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("Location: inventory.php");
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
$success_msg = '';
$error_msg = '';

// Handle Form Submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_msg = "Your session expired. Please refresh and try again.";
    }
    $action = $_POST['action'] ?? '';
    $amount_raw = trim($_POST['amount'] ?? '');
    $amount = ctype_digit($amount_raw) ? (int)$amount_raw : 0;

    if(empty($error_msg) && !in_array($action, ['add', 'deduct'], true)) {
        $error_msg = "Choose whether to increase or decrease stock.";
    } elseif(empty($error_msg) && $amount <= 0) {
        $error_msg = "Please enter a valid amount greater than 0.";
    } else {
        // Always fetch the absolute latest stock from DB before calculating
        $check_sql = "SELECT STOCK_QUANTITY FROM PRODUCT WHERE PRODUCT_ID = '$product_id'";
        $check_res = mysqli_query($conn, $check_sql);
        $check_row = mysqli_fetch_assoc($check_res);
        $current_db_stock = intval($check_row['STOCK_QUANTITY']);

        $new_stock = ($action == 'add') ? ($current_db_stock + $amount) : ($current_db_stock - $amount);

        if($new_stock < 0) {
            $error_msg = "Cannot deduct stock below 0. Current stock is only $current_db_stock.";
        } else {
            $update_sql = "UPDATE PRODUCT SET STOCK_QUANTITY = '$new_stock' WHERE PRODUCT_ID = '$product_id'";
            if(mysqli_query($conn, $update_sql)) {
                $success_msg = "Stock quantity adjusted successfully!";
            } else {
                $error_msg = "Error updating stock: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch Current Product Data for Display
$sql = "SELECT BRAND_NAME, CATEGORY, STOCK_QUANTITY FROM PRODUCT WHERE PRODUCT_ID = '$product_id'";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) {
    header("Location: inventory.php");
    exit;
}
$row = mysqli_fetch_assoc($res);
$current_stock = intval($row['STOCK_QUANTITY']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjust Stock | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
    <script>
        // Calculate projected stock dynamically
        function updateProjected() {
            const currentStock = <?php echo $current_stock; ?>;
            const action = document.getElementById('action').value;
            const amountInput = document.getElementById('amount').value;
            const amount = parseInt(amountInput) || 0;
            const projectedEl = document.getElementById('projected_stock');
            
            let projected = currentStock;
            if(amount > 0) {
                projected = (action === 'add') ? (currentStock + amount) : (currentStock - amount);
            }
            
            projectedEl.innerText = projected;
            
            if(projected < 0) {
                projectedEl.classList.remove('text-[#0097B2]');
                projectedEl.classList.add('text-red-500');
            } else {
                projectedEl.classList.remove('text-red-500');
                projectedEl.classList.add('text-[#0097B2]');
            }
        }
    </script>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-8">
            <a href="inventory.php" class="text-sm font-bold text-[#0097B2] hover:text-teal-700 transition flex items-center mb-2">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Inventory
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Adjust Stock</h1>
            <p class="text-slate-500 font-medium mt-1">Update inventory levels for #PRD-<?php echo str_pad($product_id, 4, '0', STR_PAD_LEFT); ?></p>
        </header>

        <?php if($success_msg): ?>
            <div class="mb-8 bg-teal-50 border border-teal-200 text-teal-700 px-6 py-4 rounded-2xl flex items-center shadow-sm max-w-4xl">
                <i class="fa-solid fa-circle-check text-xl mr-3"></i>
                <span class="font-bold"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center shadow-sm max-w-4xl">
                <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl">
            
            <div class="bg-slate-900 rounded-[2.5rem] shadow-xl p-10 text-white flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-[#0097B2] mb-6">
                        <i class="fa-solid fa-box-open text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-black mb-1"><?php echo htmlspecialchars($row['BRAND_NAME']); ?></h2>
                    <span class="inline-block text-[10px] font-black bg-white/10 text-slate-300 px-3 py-1 rounded uppercase tracking-widest"><?php echo htmlspecialchars($row['CATEGORY']); ?></span>
                </div>
                
                <div class="mt-10 border-t border-white/10 pt-6">
                    <p class="text-slate-400 text-xs font-black uppercase tracking-widest mb-1">Current Stock Limit</p>
                    <p class="text-6xl font-black text-white"><?php echo $current_stock; ?></p>
                </div>
            </div>

            <div class="md:col-span-2 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl p-10">
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                    
                    <div class="grid grid-cols-2 gap-6 mb-8 border-b border-slate-100 pb-8">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Adjustment Type</label>
                            <div class="relative">
                                <select name="action" id="action" onchange="updateProjected()" class="w-full pl-5 pr-10 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none appearance-none font-bold text-slate-700 transition-all cursor-pointer">
                                    <option value="add">Add Stock (+)</option>
                                    <option value="deduct">Deduct Stock (-)</option>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Quantity</label>
                            <input type="number" name="amount" id="amount" min="1" required oninput="updateProjected()" placeholder="e.g. 10" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-bold text-slate-700 text-lg">
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-teal-50 rounded-2xl p-6 mb-8 border border-teal-100">
                        <div>
                            <p class="text-teal-800 font-bold text-lg">Projected New Stock</p>
                            <p class="text-teal-600 text-sm">After adjustment is applied</p>
                        </div>
                        <div class="text-4xl font-black text-[#0097B2]" id="projected_stock">
                            <?php echo $current_stock; ?>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="inventory.php" class="px-8 py-4 rounded-2xl font-bold text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">Cancel</a>
                        <button type="submit" class="bg-[#0097B2] text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center">
                            <i class="fa-solid fa-check mr-2"></i> Confirm Adjustment
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</body>
</html>