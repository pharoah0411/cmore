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

// ==========================================
// HANDLE INLINE SUPPLIER ASSIGNMENT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_supplier'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: inventory_view.php?product_id=" . urlencode($product_id) . "&msg=invalid");
        exit;
    }
    $new_supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $supplier_check = mysqli_prepare($conn, "SELECT SUPPLIER_ID FROM SUPPLIER WHERE SUPPLIER_ID = ?");
    mysqli_stmt_bind_param($supplier_check, 'i', $new_supplier_id);
    mysqli_stmt_execute($supplier_check);
    mysqli_stmt_store_result($supplier_check);
    $supplier_exists = mysqli_stmt_num_rows($supplier_check) > 0;
    mysqli_stmt_close($supplier_check);
    if (!$supplier_exists) {
        header("Location: inventory_view.php?product_id=" . urlencode($product_id) . "&msg=invalid");
        exit;
    }
    
    $update_sql = "UPDATE PRODUCT SET SUPPLIER_ID = $new_supplier_id WHERE PRODUCT_ID = '$product_id'";
    if (mysqli_query($conn, $update_sql)) {
        // Refresh the page to show the newly assigned supplier
        header("Location: inventory_view.php?product_id=$product_id&msg=assigned");
        exit;
    }
}
// ==========================================

$sql = "SELECT p.*, s.COMPANY_NAME, s.CONTACT_PERSON, s.PHONE_NUMBER, s.EMAIL 
        FROM PRODUCT p 
        LEFT JOIN SUPPLIER s ON p.SUPPLIER_ID = s.SUPPLIER_ID 
        WHERE p.PRODUCT_ID = '$product_id'";
$res = mysqli_query($conn, $sql);

if(mysqli_num_rows($res) == 0) {
    header("Location: inventory.php");
    exit;
}

$row = mysqli_fetch_assoc($res);
$is_low = ($row['STOCK_QUANTITY'] < 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Product | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-8">
            <div>
                <a href="inventory.php" class="text-sm font-bold text-[#0097B2] hover:text-teal-700 transition flex items-center mb-2">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Inventory
                </a>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Product Details</h1>
                <p class="text-slate-500 font-medium mt-1">Viewing information for #PRD-<?php echo str_pad($row['PRODUCT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="adjust_stock.php?product_id=<?php echo $row['PRODUCT_ID']; ?>" class="bg-white border border-slate-200 text-slate-700 px-6 py-3 rounded-2xl font-bold shadow-sm hover:bg-slate-50 transition-all inline-flex items-center">
                    <i class="fa-solid fa-scale-unbalanced-flip mr-2 text-slate-400"></i> Adjust Stock
                </a>
                <a href="inventory_edit.php?id=<?php echo $row['PRODUCT_ID']; ?>" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Product
                </a>
            </div>
        </header>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'assigned'): ?>
            <div class="bg-teal-50 text-teal-700 p-4 rounded-xl mb-6 font-bold flex items-center border border-teal-100 max-w-6xl">
                <i class="fa-solid fa-circle-check mr-3 text-teal-500"></i>
                Supplier assigned successfully!
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'invalid'): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 font-bold border border-red-100 max-w-6xl">Invalid supplier assignment.</div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl">
            
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl p-10 flex flex-col justify-center">
                <div class="flex items-start justify-between mb-10">
                    <div class="flex items-center space-x-5">
                        <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center text-[#0097B2]">
                            <i class="fa-solid fa-box-open text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-800"><?php echo htmlspecialchars($row['BRAND_NAME']); ?></h2>
                            <span class="inline-block text-[10px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded uppercase tracking-widest mt-2"><?php echo htmlspecialchars($row['CATEGORY']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 bg-slate-50 p-8 rounded-3xl border border-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-5">
                        <span class="text-slate-500 text-xs font-black uppercase tracking-widest">Pricing Info</span>
                        <div class="text-right">
                            <p class="text-2xl font-black text-[#0097B2]"><span class="text-teal-400 text-base mr-1 font-bold">RM</span><?php echo number_format($row['UNIT_PRICE'], 2); ?></p>
                            <?php if(isset($row['MINIMUM_PRICE']) && $row['MINIMUM_PRICE'] > 0): ?>
                                <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest bg-white px-2 py-0.5 rounded-md inline-block border border-slate-100"><i class="fa-solid fa-lock text-slate-300 mr-1"></i> Min: RM <?php echo number_format($row['MINIMUM_PRICE'], 2); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-b border-slate-200 pb-5">
                        <span class="text-slate-500 text-xs font-black uppercase tracking-widest">Current Stock</span>
                        <div class="flex flex-col items-end">
                            <span class="text-2xl font-black <?php echo $is_low ? 'text-red-500' : 'text-slate-800'; ?>"><?php echo $row['STOCK_QUANTITY']; ?></span>
                            <?php if($is_low): ?>
                                <span class="text-[9px] font-black bg-red-100 text-red-600 px-2 py-0.5 rounded uppercase tracking-widest mt-1 animate-pulse">Low Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-xs font-black uppercase tracking-widest">Expiry Date</span>
                        <?php if(!empty($row['EXPIRY_DATE'])): ?>
                            <span class="text-base font-bold text-slate-800 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                                <?php echo date('d M Y', strtotime($row['EXPIRY_DATE'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-base font-bold text-slate-400">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-[2.5rem] shadow-xl p-10 text-white relative overflow-hidden flex flex-col justify-center">
                <div class="absolute -right-8 -top-8 text-white/5 text-9xl pointer-events-none">
                    <i class="fa-solid fa-building"></i>
                </div>
                
                <p class="text-[11px] font-black uppercase text-slate-400 tracking-widest mb-8 relative z-10">Assigned Supplier</p>
                
                <?php if(!empty($row['COMPANY_NAME'])): ?>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-6 text-white"><?php echo htmlspecialchars($row['COMPANY_NAME']); ?></h3>
                        
                        <div class="space-y-4 bg-white/5 p-6 rounded-3xl border border-white/10">
                            <div class="flex items-center text-slate-300">
                                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center mr-4 shrink-0">
                                    <i class="fa-solid fa-user text-[#0097B2]"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-0.5">Contact Person</p>
                                    <span class="font-semibold text-white"><?php echo htmlspecialchars($row['CONTACT_PERSON']); ?></span>
                                </div>
                            </div>
                            
                            <?php if(!empty($row['PHONE_NUMBER'])): ?>
                            <div class="flex items-center text-slate-300">
                                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center mr-4 shrink-0">
                                    <i class="fa-solid fa-phone text-[#0097B2]"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-0.5">Phone Number</p>
                                    <span class="font-semibold text-white"><?php echo htmlspecialchars($row['PHONE_NUMBER']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($row['EMAIL'])): ?>
                            <div class="flex items-center text-slate-300">
                                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center mr-4 shrink-0">
                                    <i class="fa-solid fa-envelope text-[#0097B2]"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-0.5">Email Address</p>
                                    <span class="font-semibold text-white truncate max-w-[200px] block" title="<?php echo htmlspecialchars($row['EMAIL']); ?>"><?php echo htmlspecialchars($row['EMAIL']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="relative z-10 flex flex-col items-center justify-center py-6 text-center bg-white/5 rounded-3xl border border-white/10 w-full">
                        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center text-slate-500 mb-4 border border-slate-700">
                            <i class="fa-solid fa-building-circle-xmark text-xl"></i>
                        </div>
                        <p class="text-slate-400 font-medium mb-6">No supplier is currently<br>assigned to this product.</p>
                        
                        <form action="" method="POST" class="w-full px-8 flex flex-col items-center">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                            <select name="supplier_id" required class="w-full p-3 mb-4 bg-slate-800 border border-slate-600 rounded-xl text-sm font-bold text-slate-300 focus:outline-none focus:border-[#0097B2]">
                                <option value="">-- Choose Supplier --</option>
                                <?php
                                $sup_res = mysqli_query($conn, "SELECT SUPPLIER_ID, COMPANY_NAME FROM SUPPLIER ORDER BY COMPANY_NAME ASC");
                                while($sup = mysqli_fetch_assoc($sup_res)) {
                                    echo "<option value='".$sup['SUPPLIER_ID']."'>".htmlspecialchars($sup['COMPANY_NAME'])."</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" name="assign_supplier" class="text-sm w-full font-bold text-white bg-[#0097B2] hover:bg-teal-500 px-6 py-3 rounded-xl transition shadow-lg shadow-[#0097B2]/20 flex justify-center items-center">
                                Save Assignment <i class="fa-solid fa-check ml-2"></i>
                            </button>
                        </form>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>