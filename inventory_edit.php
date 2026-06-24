<?php
include('config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

$error_msg = '';
$success_msg = '';

// Check if Product ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing product data
$fetch_sql = "SELECT * FROM product WHERE PRODUCT_ID = '$product_id'";
$result = mysqli_query($conn, $fetch_sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: inventory.php");
    exit;
}

$row = mysqli_fetch_assoc($result);

// Handle Form Submission for Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category       = mysqli_real_escape_string($conn, $_POST['category']);
    $brand_name     = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $unit_price     = mysqli_real_escape_string($conn, $_POST['unit_price']);
    $minimum_price  = mysqli_real_escape_string($conn, $_POST['minimum_price']);
    $stock_quantity = mysqli_real_escape_string($conn, $_POST['stock_quantity']);
    $expiry_date    = !empty($_POST['expiry_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['expiry_date']) . "'" : "NULL";
    $supplier_id    = !empty($_POST['supplier_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['supplier_id']) . "'" : "NULL";

    // Image Upload Logic for Edit
    $image_update_query = "";
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('prod_') . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
                $safe_dest = mysqli_real_escape_string($conn, $destination);
                $image_update_query = ", PRODUCT_IMAGE = '$safe_dest'";
            } else {
                $error_msg = "Failed to upload new image.";
            }
        } else {
            $error_msg = "Invalid image format. Only JPG, PNG, WEBP, and GIF are allowed.";
        }
    }

    if(empty($error_msg)) {
        $update_sql = "UPDATE product SET 
                        BRAND_NAME = '$brand_name',
                        CATEGORY = '$category',
                        UNIT_PRICE = '$unit_price',
                        MINIMUM_PRICE = '$minimum_price',
                        STOCK_QUANTITY = '$stock_quantity',
                        EXPIRY_DATE = $expiry_date,
                        SUPPLIER_ID = $supplier_id
                        $image_update_query
                      WHERE PRODUCT_ID = '$product_id'";

        if(mysqli_query($conn, $update_sql)) {
            $success_msg = "Product updated successfully!";
            // Refresh row data to show newly updated info
            $row['BRAND_NAME'] = $brand_name;
            $row['CATEGORY'] = $category;
            $row['UNIT_PRICE'] = $unit_price;
            $row['MINIMUM_PRICE'] = $minimum_price;
            $row['STOCK_QUANTITY'] = $stock_quantity;
            $row['EXPIRY_DATE'] = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $row['SUPPLIER_ID'] = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            $row['PRODUCT_IMAGE'] = isset($destination) ? $destination : $row['PRODUCT_IMAGE']; 
        } else {
            $error_msg = "Error updating product: " . mysqli_error($conn);
        }
    }
}

// Fetch suppliers for the dropdown
$suppliers_sql = "SELECT SUPPLIER_ID, COMPANY_NAME FROM supplier ORDER BY COMPANY_NAME ASC";
$suppliers_result = mysqli_query($conn, $suppliers_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-8">
            <div>
                <a href="inventory.php" class="text-[#0097B2] font-bold text-sm mb-4 inline-flex items-center hover:text-teal-700 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Inventory
                </a>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Edit Product</h1>
                <p class="text-slate-500 font-medium mt-1">Update details for #PRD-<?php echo str_pad($row['PRODUCT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
            </div>
        </header>

        <!-- Notification Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 flex items-center justify-between gap-4 p-5 rounded-2xl bg-teal-50 border border-teal-200 text-teal-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-[#0097B2] text-xl"></i>
                    <span class="font-bold"><?php echo htmlspecialchars($success_msg); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-teal-600 hover:text-teal-800 transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 flex items-center justify-between gap-4 p-5 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-xl"></i>
                    <span class="font-bold"><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800 transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden max-w-4xl">
            <form action="" method="POST" enctype="multipart/form-data" class="p-8 md:p-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Basic Information -->
                    <div class="col-span-1 md:col-span-2">
                        <h3 class="text-xl font-extrabold text-slate-800 mb-6 border-b border-slate-100 pb-3">Product Identity</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Category <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="category" required class="w-full pl-5 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 appearance-none">
                                <option value="Frames" <?php echo $row['CATEGORY'] == 'Frames' ? 'selected' : ''; ?>>Frames</option>
                                <option value="Lenses" <?php echo $row['CATEGORY'] == 'Lenses' ? 'selected' : ''; ?>>Lenses</option>
                                <option value="Contact Lenses" <?php echo $row['CATEGORY'] == 'Contact Lenses' ? 'selected' : ''; ?>>Contact Lenses</option>
                                <option value="Accessories" <?php echo $row['CATEGORY'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Brand / Model Name <span class="text-red-500">*</span></label>
                        <input type="text" name="brand_name" required value="<?php echo htmlspecialchars($row['BRAND_NAME']); ?>" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>

                    <!-- Pricing & Stock -->
                    <div class="col-span-1 md:col-span-2 mt-4">
                        <h3 class="text-xl font-extrabold text-slate-800 mb-6 border-b border-slate-100 pb-3">Pricing & Inventory</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Unit Price (RM) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" required value="<?php echo htmlspecialchars($row['UNIT_PRICE']); ?>" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Minimum Price (RM) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="minimum_price" required value="<?php echo htmlspecialchars($row['MINIMUM_PRICE']); ?>" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Stock Quantity <span class="text-red-500">*</span></label>
                        <input type="number" min="0" name="stock_quantity" required value="<?php echo htmlspecialchars($row['STOCK_QUANTITY']); ?>" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Expiry Date <span class="text-slate-400 normal-case font-medium ml-1">(If applicable)</span></label>
                        <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($row['EXPIRY_DATE']); ?>" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-sm">
                    </div>

                    <!-- Supplier & Image -->
                    <div class="col-span-1 md:col-span-2 mt-4">
                        <h3 class="text-xl font-extrabold text-slate-800 mb-6 border-b border-slate-100 pb-3">Additional Details</h3>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Supplier</label>
                        <div class="relative">
                            <select name="supplier_id" class="w-full pl-5 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 appearance-none">
                                <option value="">Select a Supplier (Optional)</option>
                                <?php if(mysqli_num_rows($suppliers_result) > 0): while($supp = mysqli_fetch_assoc($suppliers_result)): ?>
                                    <option value="<?php echo $supp['SUPPLIER_ID']; ?>" <?php echo ($row['SUPPLIER_ID'] == $supp['SUPPLIER_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supp['COMPANY_NAME']); ?>
                                    </option>
                                <?php endwhile; endif; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Image Upload Section -->
                    <div class="col-span-1 md:col-span-2 mt-4">
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Product Image <span class="text-slate-400 normal-case font-medium ml-1">(Optional)</span></label>
                        <div class="flex items-center space-x-6">
                            
                            <!-- Current Image Preview -->
                            <div class="w-32 h-32 rounded-3xl shrink-0 overflow-hidden border-2 border-slate-100 shadow-sm bg-slate-50 flex items-center justify-center relative">
                                <?php if(!empty($row['PRODUCT_IMAGE'])): ?>
                                    <img id="edit_img_preview" src="<?php echo htmlspecialchars($row['PRODUCT_IMAGE']); ?>" alt="Product" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i id="edit_img_placeholder" class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                                    <img id="edit_img_preview" src="" class="hidden w-full h-full object-cover absolute inset-0">
                                <?php endif; ?>
                            </div>

                            <!-- Upload Button -->
                            <div class="flex-1">
                                <div class="w-full p-2 bg-white border-2 border-dashed border-slate-200 rounded-2xl hover:border-[#0097B2] hover:bg-teal-50 transition-all relative overflow-hidden text-center cursor-pointer">
                                    <input type="file" name="product_image" accept="image/*" onchange="previewImage(event, 'edit_img_preview', 'edit_img_placeholder')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="py-4 pointer-events-none text-slate-500">
                                        <i class="fa-solid fa-upload text-xl mb-2 text-[#0097B2]"></i>
                                        <p class="font-bold text-sm">Upload New Image</p>
                                        <p class="text-xs mt-1 text-slate-400">Replaces current image</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-100 flex justify-end gap-4">
                    <a href="inventory.php" class="px-8 py-3.5 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition text-center">Cancel</a>
                    <button type="submit" class="px-8 py-3.5 rounded-2xl bg-[#0097B2] text-white font-bold hover:bg-teal-600 transition shadow-lg shadow-teal-100 flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(event, previewId, placeholderId = null) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if(placeholderId) {
                        const img = document.getElementById(previewId);
                        img.src = e.target.result;
                        img.classList.remove('hidden');
                        const placeholder = document.getElementById(placeholderId);
                        if(placeholder) placeholder.classList.add('hidden');
                    }
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>