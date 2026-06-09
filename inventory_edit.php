<?php 
include('config.php'); 

if(!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("Location: inventory.php");
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
$success_msg = '';
$error_msg = '';

// Handle Form Submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand_name     = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $category       = mysqli_real_escape_string($conn, $_POST['category']);
    $unit_price     = mysqli_real_escape_string($conn, $_POST['unit_price']);
    $minimum_price  = mysqli_real_escape_string($conn, $_POST['minimum_price']);
    $stock_quantity = mysqli_real_escape_string($conn, $_POST['stock_quantity']);
    
    $expiry_date    = !empty($_POST['expiry_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['expiry_date']) . "'" : "NULL";
    $supplier_id    = !empty($_POST['supplier_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['supplier_id']) . "'" : "NULL";

    $update_sql = "UPDATE PRODUCT SET 
                    BRAND_NAME = '$brand_name',
                    CATEGORY = '$category',
                    UNIT_PRICE = '$unit_price',
                    MINIMUM_PRICE = '$minimum_price',
                    STOCK_QUANTITY = '$stock_quantity',
                    EXPIRY_DATE = $expiry_date,
                    SUPPLIER_ID = $supplier_id
                  WHERE PRODUCT_ID = '$product_id'";

    if(mysqli_query($conn, $update_sql)) {
        $success_msg = "Product updated successfully!";
    } else {
        $error_msg = "Error updating product: " . mysqli_error($conn);
    }
}

// Fetch Current Product Data
$sql = "SELECT * FROM PRODUCT WHERE PRODUCT_ID = '$product_id'";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) {
    header("Location: inventory.php");
    exit;
}
$row = mysqli_fetch_assoc($res);

// Fetch Suppliers for Dropdown
$suppliers = mysqli_query($conn, "SELECT SUPPLIER_ID, COMPANY_NAME FROM SUPPLIER ORDER BY COMPANY_NAME ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-8">
            <a href="inventory.php" class="text-sm font-bold text-[#0097B2] hover:text-teal-700 transition flex items-center mb-2">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Inventory
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Edit Product</h1>
            <p class="text-slate-500 font-medium mt-1">Update details for #PRD-<?php echo str_pad($row['PRODUCT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
        </header>

        <?php if($success_msg): ?>
            <div class="mb-8 bg-teal-50 border border-teal-200 text-teal-700 px-6 py-4 rounded-2xl flex items-center shadow-sm">
                <i class="fa-solid fa-circle-check text-xl mr-3"></i>
                <span class="font-bold"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl p-10 max-w-4xl">
            <form action="" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Brand / Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="brand_name" required value="<?php echo htmlspecialchars($row['BRAND_NAME']); ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Category <span class="text-red-500">*</span></label>
                            <input type="text" name="category" required value="<?php echo htmlspecialchars($row['CATEGORY']); ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Retail Price (RM) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="unit_price" required value="<?php echo $row['UNIT_PRICE']; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700 text-[#0097B2]">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Min Price (RM) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="minimum_price" required value="<?php echo isset($row['MINIMUM_PRICE']) ? $row['MINIMUM_PRICE'] : '0.00'; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-red-400 focus:bg-white outline-none transition-all font-semibold text-slate-700">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Current Stock <span class="text-red-500">*</span></label>
                            <input type="number" name="stock_quantity" required value="<?php echo $row['STOCK_QUANTITY']; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Expiry Date</label>
                            <input type="date" name="expiry_date" value="<?php echo !empty($row['EXPIRY_DATE']) ? date('Y-m-d', strtotime($row['EXPIRY_DATE'])) : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700 text-slate-400">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Supplier</label>
                            <div class="relative">
                                <select name="supplier_id" class="w-full pl-5 pr-10 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none appearance-none font-semibold text-slate-700 transition-all">
                                    <option value="">-- No Supplier Assigned --</option>
                                    <?php if($suppliers): while($supp = mysqli_fetch_assoc($suppliers)): ?>
                                        <option value="<?php echo $supp['SUPPLIER_ID']; ?>" <?php echo ($row['SUPPLIER_ID'] == $supp['SUPPLIER_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supp['COMPANY_NAME']); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 flex justify-end space-x-4">
                    <a href="inventory.php" class="px-8 py-4 rounded-2xl font-bold text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">Cancel</a>
                    <button type="submit" class="bg-[#0097B2] text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center">
                        <i class="fa-solid fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>