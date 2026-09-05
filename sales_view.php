<?php 
include('config.php'); 
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
$payment_methods = ['Cash', 'Card', 'E-wallet', 'Online Banking'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_edit']) || isset($_POST['add_payment']))) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=csrf");
        exit();
    }
}

// Handle Edit Save
if(isset($_POST['save_edit'])) {
    $paid_amount_raw = trim($_POST['paid_amount'] ?? '');
    $new_paid_amount = is_numeric($paid_amount_raw) ? (float)$paid_amount_raw : -1;
    $new_payment_method = $_POST['payment_method'] ?? '';
    
    // Fetch sale details
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT TOTAL_AMOUNT, PAID_AMOUNT, PAYMENT_METHOD FROM SALES WHERE SALE_ID = '$id'"));
    $total = floatval($check['TOTAL_AMOUNT']);
    
    if ($new_paid_amount < 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $paid_amount_raw)) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=amount");
        exit();
    }
    if (!in_array($new_payment_method, $payment_methods, true) || $new_paid_amount > $total) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=payment");
        exit();
    }
    $new_payment_method = mysqli_real_escape_string($conn, $new_payment_method);
    
    $status = ($new_paid_amount >= $total) ? 'Completed' : ($new_paid_amount > 0 ? 'Partial' : 'Pending');
    
    // Update the sale
    $update_sql = "UPDATE SALES SET PAID_AMOUNT = '$new_paid_amount', PAYMENT_STATUS = '$status', PAYMENT_METHOD = '$new_payment_method' WHERE SALE_ID = '$id'";
    
    if(mysqli_query($conn, $update_sql)) {
        // Log the edit in audit trail (all users logging edits)
        systemLog($conn, "Edited sale transaction #TXN-$id (Changed paid amount to RM" . number_format($new_paid_amount, 2) . ")", "SALES", $id);
        header("Location: sales_view.php?id=$id&msg=updated");
        exit();
    }
}

// Handle Payment Update (Quick Add)
if(isset($_POST['add_payment'])) {
    $payment_amount_raw = trim($_POST['payment_amount'] ?? '');
    $new_payment = is_numeric($payment_amount_raw) ? (float)$payment_amount_raw : -1;
    $new_payment_method = $_POST['payment_method'] ?? '';
    
    // Fetch current amounts
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT TOTAL_AMOUNT, PAID_AMOUNT FROM SALES WHERE SALE_ID = '$id'"));
    $total = floatval($check['TOTAL_AMOUNT']);
    $current_paid = floatval($check['PAID_AMOUNT']);
    $remaining = max(0, $total - $current_paid);
    
    if ($new_payment <= 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $payment_amount_raw)) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=amount");
        exit();
    }
    if ($new_payment > $remaining || !in_array($new_payment_method, $payment_methods, true)) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=payment");
        exit();
    }

    $updated_paid = min($total, $current_paid + $new_payment);
    $new_payment_method = mysqli_real_escape_string($conn, $new_payment_method);
    
    $status = ($updated_paid >= $total) ? 'Completed' : 'Partial';
    
    if (!mysqli_query($conn, "UPDATE SALES SET PAID_AMOUNT = '$updated_paid', PAYMENT_STATUS = '$status', PAYMENT_METHOD = '$new_payment_method' WHERE SALE_ID = '$id'")) {
        header("Location: sales_view.php?id=" . urlencode($id) . "&error=save");
        exit();
    }
    // Log quick payment addition
    systemLog($conn, "Added payment of RM" . number_format($new_payment, 2) . " to sale #TXN-$id", "SALES", $id);
    header("Location: sales_view.php?id=$id&msg=updated");
    exit();
}

$sql = "SELECT s.*, p.NAME as P_NAME, p.PHONE_NUMBER, u.NAME as S_NAME 
        FROM SALES s JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID JOIN USER u ON s.STAFF_ID = u.USER_ID 
        WHERE s.SALE_ID = '$id'";
$sale = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fix: Use max(0, ...) to guarantee the balance never displays as a negative number
$balance = max(0, $sale['TOTAL_AMOUNT'] - $sale['PAID_AMOUNT']);

$status_color = $sale['PAYMENT_STATUS'] == 'Completed' ? 'bg-green-500' : ($sale['PAYMENT_STATUS'] == 'Partial' ? 'bg-orange-500' : 'bg-red-500');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | View Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-start mb-12">
            <div>
                <a href="sales.php" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Sales
                </a>
                <div class="flex items-center space-x-4 mt-4">
                    <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Transaction #TXN-<?php echo $sale['SALE_ID']; ?></h1>
                    <span class="px-4 py-1.5 text-white text-xs font-black uppercase tracking-widest rounded-lg <?php echo $status_color; ?> shadow-sm">
                        <?php echo $sale['PAYMENT_STATUS']; ?>
                    </span>
                </div>
                <p class="text-slate-500 font-medium mt-2">Processed on <?php echo date('d F Y, h:i A', strtotime($sale['SALE_DATE'])); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="receipt.php?id=<?php echo $id; ?>&rx=1" target="_blank" class="bg-white border border-slate-200 text-[#0097B2] px-6 py-3 rounded-xl font-bold hover:bg-teal-50 transition shadow-sm">
                    <i class="fa-solid fa-file-prescription mr-2"></i> Print (With Rx)
                </a>
                <a href="receipt.php?id=<?php echo $id; ?>&rx=0" target="_blank" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold hover:bg-slate-800 transition shadow-sm">
                    <i class="fa-solid fa-print mr-2"></i> Print Receipt
                </a>
                <?php if(!$is_edit_mode): ?>
                    <a href="sales_view.php?id=<?php echo $id; ?>&edit=1" class="bg-blue-50 border border-blue-200 text-blue-600 px-6 py-3 rounded-xl font-bold hover:bg-blue-100 transition shadow-sm">
                        <i class="fa-solid fa-edit mr-2"></i> Edit
                    </a>
                <?php else: ?>
                    <a href="sales_view.php?id=<?php echo $id; ?>" class="bg-slate-50 border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-100 transition shadow-sm">
                        <i class="fa-solid fa-times mr-2"></i> Cancel Edit
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Success Message -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-5 rounded-2xl mb-8 flex items-center shadow-sm">
            <i class="fa-solid fa-check-circle mr-3 text-lg text-green-600"></i>
            <p class="font-bold text-sm">Sale transaction updated successfully and logged in audit trail.</p>
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-5 rounded-2xl mb-8 flex items-center shadow-sm">
            <i class="fa-solid fa-circle-exclamation mr-3 text-lg text-red-600"></i>
            <p class="font-bold text-sm"><?php echo $_GET['error'] === 'amount' ? 'Enter a valid payment amount within the remaining balance.' : ($_GET['error'] === 'csrf' ? 'Your session expired. Refresh the page and try again.' : 'Payment method or payment details are invalid.'); ?></p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-6 border-b border-slate-50 pb-4">Purchased Items</h3>
                    <div class="space-y-4">
                        <?php
                        $items = mysqli_query($conn, "SELECT si.QUANTITY, p.BRAND_NAME, p.UNIT_PRICE FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID WHERE si.SALE_ID = '$id'");
                        while($item = mysqli_fetch_assoc($items)):
                        ?>
                        <div class="flex justify-between items-center bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <div>
                                <p class="font-bold text-slate-800 text-lg"><?php echo $item['BRAND_NAME']; ?></p>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wide mt-1">Qty: <?php echo $item['QUANTITY']; ?> &times; RM <?php echo $item['UNIT_PRICE']; ?></p>
                            </div>
                            <p class="font-black text-slate-700 text-xl font-mono">RM <?php echo number_format($item['QUANTITY'] * $item['UNIT_PRICE'], 2); ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                
                <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-6 border-b border-white/10 pb-4">
                        <?php echo $is_edit_mode ? 'Edit Payment Details' : 'Payment Ledger'; ?>
                    </h3>
                    
                    <?php if($is_edit_mode): ?>
                    <!-- EDIT MODE FORM -->
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase block mb-3 tracking-widest">Total Amount</label>
                            <p class="text-3xl font-mono font-bold text-white">RM <?php echo number_format($sale['TOTAL_AMOUNT'], 2); ?></p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase block mb-3 tracking-widest">Paid Amount</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">RM</span>
                                <input type="number" step="0.01" min="0" max="<?php echo $sale['TOTAL_AMOUNT']; ?>" name="paid_amount" required
                                       value="<?php echo $sale['PAID_AMOUNT']; ?>"
                                       class="w-full pl-12 pr-4 py-4 bg-white/10 border border-white/20 rounded-xl outline-none font-mono font-bold text-white focus:border-[#B9D977] transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase block mb-3 tracking-widest">Payment Method</label>
                            <select name="payment_method" class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-xl outline-none font-bold text-white focus:border-[#B9D977] transition-colors appearance-none cursor-pointer">
                                <option class="bg-white text-slate-900" value="Cash" <?php echo $sale['PAYMENT_METHOD'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                <option class="bg-white text-slate-900" value="Card" <?php echo $sale['PAYMENT_METHOD'] == 'Card' ? 'selected' : ''; ?>>Card</option>
                                <option class="bg-white text-slate-900" value="E-wallet" <?php echo $sale['PAYMENT_METHOD'] == 'E-wallet' ? 'selected' : ''; ?>>E-Wallet</option>
                                <option class="bg-white text-slate-900" value="Online Banking" <?php echo $sale['PAYMENT_METHOD'] == 'Online Banking' ? 'selected' : ''; ?>>Online Banking</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase block mb-3 tracking-widest">Payment Status</label>
                            <p class="text-lg font-bold text-[#B9D977] py-3">
                                <i class="fa-solid fa-circle text-xs mr-2"></i>
                                <?php 
                                    $calc_status = ($sale['PAID_AMOUNT'] >= $sale['TOTAL_AMOUNT']) ? 'Completed' : ($sale['PAID_AMOUNT'] > 0 ? 'Partial' : 'Pending');
                                    echo $calc_status;
                                ?>
                            </p>
                        </div>

                        <button type="submit" name="save_edit" class="w-full bg-[#B9D977] text-slate-900 py-4 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                            <i class="fa-solid fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                    <!-- END EDIT MODE -->
                    <?php else: ?>
                    <!-- VIEW MODE -->
                    <div class="space-y-5 mb-8">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-medium text-slate-300">Grand Total:</p>
                            <p class="text-lg font-mono font-bold text-white">RM <?php echo number_format($sale['TOTAL_AMOUNT'], 2); ?></p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-medium text-slate-300">Previously Paid:</p>
                            <p class="text-lg font-mono font-bold text-green-400">- RM <?php echo number_format($sale['PAID_AMOUNT'], 2); ?></p>
                        </div>
                        
                        <div class="pt-5 border-t border-white/10 flex justify-between items-end">
                            <p class="text-xs font-black text-[#B9D977] uppercase tracking-[0.15em]">Left to Pay<br>(Balance)</p>
                            <p class="text-4xl font-mono font-black <?php echo ($balance > 0) ? 'text-red-400' : 'text-white'; ?>">
                                RM <?php echo number_format($balance, 2); ?>
                            </p>
                        </div>
                    </div>

                    <?php if($balance > 0): ?>
                    <div class="bg-white/5 p-6 rounded-3xl border border-white/10 relative z-10">
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                            <label class="text-[10px] font-black text-white uppercase block mb-3 tracking-widest">Receive New Payment</label>
                            <div class="space-y-3">
                                <div class="relative flex-1">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">RM</span>
                                    <input type="number" step="0.01" max="<?php echo $balance; ?>" name="payment_amount" required placeholder="0.00" 
                                           class="w-full pl-12 pr-4 py-4 bg-white/10 border border-transparent rounded-xl outline-none font-mono font-bold text-white focus:border-[#B9D977] transition-colors">
                                </div>
                                <select name="payment_method" required class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-xl outline-none font-bold text-white focus:border-[#B9D977] transition-colors">
                                    <option class="bg-white text-slate-900" value="Cash">Cash</option>
                                    <option class="bg-white text-slate-900" value="Card">Card</option>
                                    <option class="bg-white text-slate-900" value="E-wallet">E-Wallet</option>
                                    <option class="bg-white text-slate-900" value="Online Banking">Online Banking</option>
                                </select>
                                <button type="submit" name="add_payment" class="w-full bg-[#B9D977] text-slate-900 px-6 py-4 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                                    <i class="fa-solid fa-check mr-2"></i> Record Payment
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-3 font-medium"><i class="fa-solid fa-circle-info mr-1"></i> Maximum acceptable payment is RM <?php echo number_format($balance, 2); ?></p>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="bg-green-500/10 p-6 rounded-3xl border border-green-500/20 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white text-xl mb-3 shadow-lg shadow-green-500/30">
                            <i class="fa-solid fa-check-double"></i>
                        </div>
                        <h4 class="text-green-400 font-black tracking-tight text-lg">Transaction Cleared</h4>
                        <p class="text-green-500/70 text-xs font-medium mt-1">There is no remaining balance for this sale.</p>
                    </div>
                    <?php endif; ?>
                    <!-- END VIEW MODE -->
                    <?php endif; ?>

                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl space-y-6">
                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Patient Details</h3>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-[#0097B2] shadow-inner"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <p class="font-bold text-slate-800"><?php echo $sale['P_NAME']; ?></p>
                                <p class="text-xs font-bold text-slate-500 mt-1"><i class="fa-solid fa-phone mr-1 opacity-70"></i> <?php echo $sale['PHONE_NUMBER']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-slate-50">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Attending Staff</h3>
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400"><i class="fa-solid fa-user-tag"></i></div>
                            <p class="font-bold text-slate-700 text-sm"><?php echo $sale['S_NAME']; ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>