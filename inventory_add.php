<?php 
include('config.php'); 

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
    
    $supplier_id    = "NULL";

    // Handle New Supplier Insertion if toggle is active
    if(isset($_POST['is_new_supplier']) && $_POST['is_new_supplier'] == '1') {
        $supp_name    = mysqli_real_escape_string($conn, $_POST['new_supplier_name']);
        $supp_contact = mysqli_real_escape_string($conn, $_POST['new_supplier_contact']);
        $supp_phone   = mysqli_real_escape_string($conn, $_POST['new_supplier_phone']);
        $supp_email   = mysqli_real_escape_string($conn, $_POST['new_supplier_email']);

        $supp_insert_sql = "INSERT INTO supplier (COMPANY_NAME, CONTACT_PERSON, PHONE_NUMBER, EMAIL) 
                            VALUES ('$supp_name', '$supp_contact', '$supp_phone', '$supp_email')";
        
        if(mysqli_query($conn, $supp_insert_sql)) {
            $supplier_id = mysqli_insert_id($conn);
        } else {
            $error_msg = "Error adding new supplier: " . mysqli_error($conn);
        }
    } else {
        $supplier_id = !empty($_POST['supplier_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['supplier_id']) . "'" : "NULL";
    }

    // Proceed to insert product if no supplier errors occurred
    if(empty($error_msg)) {
        $insert_sql = "INSERT INTO product (CATEGORY, BRAND_NAME, STOCK_QUANTITY, UNIT_PRICE, MINIMUM_PRICE, SUPPLIER_ID, EXPIRY_DATE) 
                       VALUES ('$category', '$brand_name', '$stock_quantity', '$unit_price', '$minimum_price', $supplier_id, $expiry_date)";

        if(mysqli_query($conn, $insert_sql)) {
            $success_msg = "Product and supplier information added successfully!";
            $_POST = array(); // Clear form
        } else {
            $error_msg = "Error adding product: " . mysqli_error($conn);
        }
    }
}

// Fetch Suppliers for Dropdown
$suppliers = mysqli_query($conn, "SELECT SUPPLIER_ID, COMPANY_NAME FROM supplier ORDER BY COMPANY_NAME ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product | C-More</title>
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
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Add New Product</h1>
            <p class="text-slate-500 font-medium mt-1">Enter details to register a new item in the inventory.</p>
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
                            <input type="text" name="brand_name" required value="<?php echo isset($_POST['brand_name']) ? htmlspecialchars($_POST['brand_name']) : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="e.g. Ray-Ban Aviator Classic">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Category <span class="text-red-500">*</span></label>
                            <input type="text" name="category" required value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="e.g. Frames, Solutions, Contact Lenses">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Retail Price (RM) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="unit_price" required value="<?php echo isset($_POST['unit_price']) ? $_POST['unit_price'] : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700 text-[#0097B2]" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Min Price (RM) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="minimum_price" required value="<?php echo isset($_POST['minimum_price']) ? $_POST['minimum_price'] : '0.00'; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-red-400 focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Initial Stock <span class="text-red-500">*</span></label>
                            <input type="number" name="stock_quantity" required value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : '0'; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="0">
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Expiry Date</label>
                            <input type="date" name="expiry_date" value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700 text-slate-400">
                        </div>

                        <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl">
                            <div class="flex justify-between items-center mb-4">
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-500">Supplier Allocation</label>
                                <button type="button" id="btn_toggle_supplier" class="text-xs font-bold text-[#0097B2] hover:text-teal-700 transition" onclick="toggleNewSupplierForm()">
                                    <i class="fa-solid fa-plus mr-1"></i> Add New
                                </button>
                            </div>

                            <input type="hidden" name="is_new_supplier" id="is_new_supplier" value="0">

                            <div id="existing_supplier_wrapper" class="relative">
                                <input type="hidden" name="supplier_id" id="supplier_id_input" value="">
                                
                                <div class="w-full flex items-center justify-between px-5 py-4 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-[#0097B2] transition" onclick="toggleDropdown()">
                                    <span id="selected_supplier_text" class="text-slate-400 font-semibold truncate">-- Search & Select Supplier --</span>
                                    <i class="fa-solid fa-chevron-down text-slate-400 ml-2 shrink-0"></i>
                                </div>
                                
                                <div id="supplier_menu" class="hidden absolute z-10 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                    <div class="p-3 border-b border-slate-100 bg-slate-50">
                                        <div class="relative">
                                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                            <input type="text" id="search_supplier" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] text-sm font-semibold text-slate-700 shadow-sm" placeholder="Search by name..." onkeyup="filterSuppliers()">
                                        </div>
                                    </div>
                                    <ul class="max-h-52 overflow-y-auto" id="supplier_list">
                                        <li class="px-5 py-3 hover:bg-[#0097B2] hover:text-white cursor-pointer text-slate-600 font-semibold transition text-sm" onclick="selectSupplier('', '-- No Supplier Assigned --')">-- No Supplier Assigned --</li>
                                        <?php if($suppliers): while($supp = mysqli_fetch_assoc($suppliers)): ?>
                                            <li class="supplier-item px-5 py-3 border-t border-slate-50 hover:bg-[#0097B2] hover:text-white cursor-pointer text-slate-700 font-bold transition text-sm flex items-center justify-between" 
                                                data-name="<?php echo htmlspecialchars($supp['COMPANY_NAME']); ?>" 
                                                onclick="selectSupplier('<?php echo $supp['SUPPLIER_ID']; ?>', '<?php echo addslashes(htmlspecialchars($supp['COMPANY_NAME'])); ?>')">
                                                <?php echo htmlspecialchars($supp['COMPANY_NAME']); ?>
                                            </li>
                                        <?php endwhile; endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <div id="new_supplier_form" class="hidden space-y-4">
                                <div>
                                    <input type="text" name="new_supplier_name" id="new_supplier_name" placeholder="Company Name *" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none transition-all font-semibold text-slate-700 text-sm">
                                </div>
                                <div>
                                    <input type="text" name="new_supplier_contact" placeholder="Contact Person" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none transition-all font-semibold text-slate-700 text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" name="new_supplier_phone" placeholder="Phone" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none transition-all font-semibold text-slate-700 text-sm">
                                    <input type="email" name="new_supplier_email" placeholder="Email" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none transition-all font-semibold text-slate-700 text-sm">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 flex justify-end space-x-4">
                    <a href="inventory.php" class="px-8 py-4 rounded-2xl font-bold text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">Cancel</a>
                    <button type="submit" class="bg-[#0097B2] text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center">
                        <i class="fa-solid fa-plus mr-2"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleNewSupplierForm() {
            const form = document.getElementById('new_supplier_form');
            const wrapper = document.getElementById('existing_supplier_wrapper');
            const isNewFlag = document.getElementById('is_new_supplier');
            const toggleBtn = document.getElementById('btn_toggle_supplier');
            const nameInput = document.getElementById('new_supplier_name');

            if (form.classList.contains('hidden')) {
                // Show Add Form
                form.classList.remove('hidden');
                wrapper.classList.add('hidden');
                isNewFlag.value = "1";
                nameInput.setAttribute('required', 'required');
                toggleBtn.innerHTML = '<i class="fa-solid fa-rotate-left mr-1"></i> Use Existing';
                toggleBtn.classList.replace('text-[#0097B2]', 'text-orange-500');
            } else {
                // Show Dropdown
                form.classList.add('hidden');
                wrapper.classList.remove('hidden');
                isNewFlag.value = "0";
                nameInput.removeAttribute('required');
                toggleBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> Add New';
                toggleBtn.classList.replace('text-orange-500', 'text-[#0097B2]');
            }
        }

        function toggleDropdown() {
            const menu = document.getElementById('supplier_menu');
            menu.classList.toggle('hidden');
            if(!menu.classList.contains('hidden')) {
                document.getElementById('search_supplier').focus();
            }
        }

        function selectSupplier(id, name) {
            document.getElementById('supplier_id_input').value = id;
            const textDisplay = document.getElementById('selected_supplier_text');
            textDisplay.innerText = name;
            
            if(id === '') {
                textDisplay.classList.add('text-slate-400');
                textDisplay.classList.remove('text-slate-800');
            } else {
                textDisplay.classList.remove('text-slate-400');
                textDisplay.classList.add('text-slate-800');
            }
            
            document.getElementById('supplier_menu').classList.add('hidden');
        }

        function filterSuppliers() {
            let input = document.getElementById('search_supplier').value.toUpperCase();
            let items = document.querySelectorAll('.supplier-item');
            
            items.forEach(item => {
                let txtValue = item.getAttribute('data-name').toUpperCase();
                if (txtValue.indexOf(input) > -1) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        }

        // Close custom dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const wrapper = document.getElementById('existing_supplier_wrapper');
            const menu = document.getElementById('supplier_menu');
            if (wrapper && !wrapper.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>