<?php 
include('config.php'); 
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// Handle Payment Update
if(isset($_POST['add_payment'])) {
    $new_payment = floatval($_POST['payment_amount']);
    
    // Fetch current amounts
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT TOTAL_AMOUNT, PAID_AMOUNT FROM SALES WHERE SALE_ID = '$id'"));
    $total = floatval($check['TOTAL_AMOUNT']);
    $current_paid = floatval($check['PAID_AMOUNT']);
    
    $updated_paid = $current_paid + $new_payment;
    
    // Ensure we don't accidentally overpay in the system
    if($updated_paid > $total) {
        $updated_paid = $total;
    }
    
    $status = ($updated_paid >= $total) ? 'Completed' : 'Partial';
    
    mysqli_query($conn, "UPDATE SALES SET PAID_AMOUNT = '$updated_paid', PAYMENT_STATUS = '$status' WHERE SALE_ID = '$id'");
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
            </div>
        </header>

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
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-6 border-b border-white/10 pb-4">Payment Ledger</h3>
                    
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
                            <label class="text-[10px] font-black text-white uppercase block mb-3 tracking-widest">Receive New Payment</label>
                            <div class="flex space-x-3">
                                <div class="relative flex-1">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">RM</span>
                                    <input type="number" step="0.01" max="<?php echo $balance; ?>" name="payment_amount" required placeholder="0.00" 
                                           class="w-full pl-12 pr-4 py-4 bg-white/10 border border-transparent rounded-xl outline-none font-mono font-bold text-white focus:border-[#B9D977] transition-colors">
                                </div>
                                <button type="submit" name="add_payment" class="bg-[#B9D977] text-slate-900 px-6 py-4 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                                    Add
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
                </div>

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