<?php 
include('config.php'); 

// config.php normally starts the session; safety net + CSRF token for the AJAX category actions.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

/* -------------------------------------------------------------------------
   Category picklist table.
   Categories were previously only stored as a text column on PRODUCT, so there
   was no list to select/add/delete from. We keep a lightweight table to drive
   the dropdown. On first creation we seed it from categories already in use so
   nothing is lost. PRODUCT.CATEGORY stays a text column (unchanged), so the
   rest of the app keeps working exactly as before.
   ------------------------------------------------------------------------- */
$cat_table_exists = mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'PRODUCT_CATEGORY'")) > 0;
if (!$cat_table_exists) {
    mysqli_query($conn, "CREATE TABLE PRODUCT_CATEGORY (
        CATEGORY_ID INT AUTO_INCREMENT PRIMARY KEY,
        CATEGORY_NAME VARCHAR(100) NOT NULL UNIQUE
    )");
    // Seed once from existing product categories.
    mysqli_query($conn, "INSERT IGNORE INTO PRODUCT_CATEGORY (CATEGORY_NAME)
        SELECT DISTINCT CATEGORY FROM PRODUCT WHERE CATEGORY IS NOT NULL AND CATEGORY <> ''");
}

/* -------------------------------------------------------------------------
   AJAX endpoint (same page): add / delete a category.
   Runs before any HTML is sent and exits with JSON.
   ------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['ok' => false, 'error' => 'Your session expired. Please refresh the page.']);
        exit;
    }

    $action = $_POST['ajax_action'];

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '')            { echo json_encode(['ok' => false, 'error' => 'Enter a category name.']); exit; }
        if (mb_strlen($name) > 100)  { echo json_encode(['ok' => false, 'error' => 'Category name is too long (max 100 characters).']); exit; }

        $stmt = mysqli_prepare($conn, "INSERT INTO PRODUCT_CATEGORY (CATEGORY_NAME) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $name);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            echo json_encode(['ok' => true, 'id' => $new_id, 'name' => $name, 'existed' => false]);
            exit;
        }
        mysqli_stmt_close($stmt);

        // 1062 = duplicate (UNIQUE). Treat as "already there" and return its id so the UI can just select it.
        if (mysqli_errno($conn) === 1062) {
            $exist_id = 0;
            $q = mysqli_prepare($conn, "SELECT CATEGORY_ID FROM PRODUCT_CATEGORY WHERE CATEGORY_NAME = ?");
            mysqli_stmt_bind_param($q, 's', $name);
            mysqli_stmt_execute($q);
            mysqli_stmt_bind_result($q, $exist_id);
            mysqli_stmt_fetch($q);
            mysqli_stmt_close($q);
            echo json_encode(['ok' => true, 'id' => $exist_id, 'name' => $name, 'existed' => true]);
            exit;
        }
        echo json_encode(['ok' => false, 'error' => 'Could not add the category.']);
        exit;
    }

    if ($action === 'delete_category') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok' => false, 'error' => 'Invalid category.']); exit; }

        // Look up the name so we can report how many products still use that label.
        $name = null;
        $q = mysqli_prepare($conn, "SELECT CATEGORY_NAME FROM PRODUCT_CATEGORY WHERE CATEGORY_ID = ?");
        mysqli_stmt_bind_param($q, 'i', $id);
        mysqli_stmt_execute($q);
        mysqli_stmt_bind_result($q, $name);
        mysqli_stmt_fetch($q);
        mysqli_stmt_close($q);
        if ($name === null) { echo json_encode(['ok' => false, 'error' => 'Category not found.']); exit; }

        $in_use = 0;
        $c = mysqli_prepare($conn, "SELECT COUNT(*) FROM PRODUCT WHERE CATEGORY = ?");
        mysqli_stmt_bind_param($c, 's', $name);
        mysqli_stmt_execute($c);
        mysqli_stmt_bind_result($c, $in_use);
        mysqli_stmt_fetch($c);
        mysqli_stmt_close($c);

        $d = mysqli_prepare($conn, "DELETE FROM PRODUCT_CATEGORY WHERE CATEGORY_ID = ?");
        mysqli_stmt_bind_param($d, 'i', $id);
        $ok = mysqli_stmt_execute($d);
        mysqli_stmt_close($d);

        if ($ok) { echo json_encode(['ok' => true, 'in_use' => (int)$in_use, 'name' => $name]); exit; }
        echo json_encode(['ok' => false, 'error' => 'Could not delete the category.']);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
    exit;
}

$success_msg = '';
$error_msg = '';

// Handle Product Form Submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand_name     = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $category       = mysqli_real_escape_string($conn, $_POST['category']);
    $unit_price     = mysqli_real_escape_string($conn, $_POST['unit_price']);
    $minimum_price  = mysqli_real_escape_string($conn, $_POST['minimum_price']);
    $stock_quantity = mysqli_real_escape_string($conn, $_POST['stock_quantity']);
    $expiry_date    = !empty($_POST['expiry_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['expiry_date']) . "'" : "NULL";
    
    $supplier_id    = "NULL";

    // Category now comes from the managed dropdown and is required.
    if (trim($_POST['category'] ?? '') === '') {
        $error_msg = "Please select or add a category before saving.";
    }

    // Handle New Supplier Insertion if toggle is active
    if(empty($error_msg) && isset($_POST['is_new_supplier']) && $_POST['is_new_supplier'] == '1') {
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
    } elseif(empty($error_msg)) {
        $supplier_id = !empty($_POST['supplier_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['supplier_id']) . "'" : "NULL";
    }

    // Proceed to insert product if no errors occurred
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

// Fetch Categories for Dropdown
$categories = mysqli_query($conn, "SELECT CATEGORY_ID, CATEGORY_NAME FROM PRODUCT_CATEGORY ORDER BY CATEGORY_NAME ASC");
$selected_cat = isset($_POST['category']) ? $_POST['category'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <form action="" method="POST" onsubmit="return validateProductForm()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Brand / Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="brand_name" required value="<?php echo isset($_POST['brand_name']) ? htmlspecialchars($_POST['brand_name']) : ''; ?>" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:border-[#0097B2] focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="e.g. Ray-Ban Aviator Classic">
                        </div>

                        <!-- ===================== CATEGORY: managed dropdown ===================== -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Category <span class="text-red-500">*</span></label>
                            <div id="category_wrapper" class="relative">
                                <input type="hidden" name="category" id="category_input" value="<?php echo htmlspecialchars($selected_cat); ?>">

                                <div class="w-full flex items-center justify-between px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl cursor-pointer hover:border-[#0097B2] transition" onclick="toggleCatDropdown()">
                                    <span id="selected_category_text" class="<?php echo $selected_cat ? 'text-slate-800' : 'text-slate-400'; ?> font-semibold truncate">
                                        <?php echo $selected_cat ? htmlspecialchars($selected_cat) : '-- Select a category --'; ?>
                                    </span>
                                    <i class="fa-solid fa-chevron-down text-slate-400 ml-2 shrink-0"></i>
                                </div>

                                <div id="category_menu" class="hidden absolute z-20 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                    <div class="p-3 border-b border-slate-100 bg-slate-50">
                                        <div class="flex gap-2">
                                            <div class="relative flex-1">
                                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                                <input type="text" id="category_search" autocomplete="off" onkeyup="onCatSearchKey(event)" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] text-sm font-semibold text-slate-700 shadow-sm" placeholder="Search, or type a new name…">
                                            </div>
                                            <button type="button" id="add_category_btn" onclick="addCategory()" class="hidden bg-[#0097B2] text-white px-4 rounded-xl text-sm font-bold hover:bg-teal-600 transition shrink-0">
                                                <i class="fa-solid fa-plus mr-1"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                    <ul class="max-h-52 overflow-y-auto" id="category_list">
                                        <?php if($categories): while($c = mysqli_fetch_assoc($categories)):
                                            $cname_js = htmlspecialchars(json_encode($c['CATEGORY_NAME']), ENT_QUOTES);
                                        ?>
                                        <li class="category-item flex items-center justify-between border-t border-slate-50 hover:bg-slate-50 transition" data-id="<?php echo $c['CATEGORY_ID']; ?>" data-name="<?php echo htmlspecialchars($c['CATEGORY_NAME'], ENT_QUOTES); ?>">
                                            <span class="flex-1 px-5 py-3 cursor-pointer text-slate-700 font-bold text-sm" onclick="selectCategory(<?php echo $cname_js; ?>)"><?php echo htmlspecialchars($c['CATEGORY_NAME']); ?></span>
                                            <button type="button" title="Delete category" onclick="deleteCategory(<?php echo $c['CATEGORY_ID']; ?>, <?php echo $cname_js; ?>)" class="px-4 py-3 text-slate-300 hover:text-red-500 transition shrink-0">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </li>
                                        <?php endwhile; endif; ?>
                                    </ul>
                                    <div id="category_empty" class="px-5 py-4 text-sm text-slate-400 font-semibold <?php echo (mysqli_num_rows($categories ?: false) ? 'hidden' : ''); ?>">
                                        No categories yet — type a name above and click Add.
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium mt-2 ml-1">Pick from the list, or type a new name and click Add. Use the trash icon to remove one.</p>
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
        const CSRF_TOKEN = "<?php echo $csrf; ?>";

        /* ===================== CATEGORY DROPDOWN ===================== */
        function toggleCatDropdown() {
            const menu = document.getElementById('category_menu');
            menu.classList.toggle('hidden');
            if(!menu.classList.contains('hidden')) document.getElementById('category_search').focus();
        }

        function selectCategory(name) {
            document.getElementById('category_input').value = name;
            const t = document.getElementById('selected_category_text');
            t.textContent = name;
            t.classList.remove('text-slate-400');
            t.classList.add('text-slate-800');
            document.getElementById('category_menu').classList.add('hidden');
        }

        function existingCategoryNames() {
            return Array.from(document.querySelectorAll('.category-item')).map(li => li.dataset.name.toLowerCase());
        }

        function onCatSearchKey(event) {
            const val = document.getElementById('category_search').value.trim();
            const up = val.toUpperCase();
            let firstVisible = null;
            document.querySelectorAll('.category-item').forEach(li => {
                const show = li.dataset.name.toUpperCase().indexOf(up) > -1;
                li.style.display = show ? '' : 'none';
                if(show && !firstVisible) firstVisible = li;
            });
            const exact = existingCategoryNames().includes(val.toLowerCase());
            document.getElementById('add_category_btn').classList.toggle('hidden', !(val.length > 0 && !exact));

            if(event.key === 'Enter') {
                event.preventDefault();
                if(val.length > 0 && !exact) { addCategory(); }
                else if(firstVisible) { selectCategory(firstVisible.dataset.name); }
            }
        }

        function resetCatSearch() {
            document.getElementById('category_search').value = '';
            document.querySelectorAll('.category-item').forEach(li => li.style.display = '');
            document.getElementById('add_category_btn').classList.add('hidden');
        }

        async function addCategory() {
            const name = document.getElementById('category_search').value.trim();
            if(!name) return;
            const fd = new FormData();
            fd.append('ajax_action', 'add_category');
            fd.append('csrf_token', CSRF_TOKEN);
            fd.append('name', name);
            try {
                const res = await fetch(window.location.href, { method: 'POST', body: fd });
                const data = await res.json();
                if(!data.ok) { alert(data.error || 'Could not add category.'); return; }
                if(!data.existed) addCategoryToList(data.id, data.name);
                selectCategory(data.name);
                resetCatSearch();
            } catch(e) {
                alert('Network error. Please try again.');
            }
        }

        function addCategoryToList(id, name) {
            const ul = document.getElementById('category_list');
            const li = document.createElement('li');
            li.className = 'category-item flex items-center justify-between border-t border-slate-50 hover:bg-slate-50 transition';
            li.dataset.id = id;
            li.dataset.name = name;

            const span = document.createElement('span');
            span.className = 'flex-1 px-5 py-3 cursor-pointer text-slate-700 font-bold text-sm';
            span.textContent = name;
            span.onclick = () => selectCategory(name);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Delete category';
            btn.className = 'px-4 py-3 text-slate-300 hover:text-red-500 transition shrink-0';
            btn.innerHTML = '<i class="fa-solid fa-trash text-xs"></i>';
            btn.onclick = () => deleteCategory(id, name);

            li.appendChild(span);
            li.appendChild(btn);

            // Insert alphabetically.
            const items = Array.from(ul.querySelectorAll('.category-item'));
            const before = items.find(it => it.dataset.name.toLowerCase() > name.toLowerCase());
            if(before) ul.insertBefore(li, before); else ul.appendChild(li);

            document.getElementById('category_empty').classList.add('hidden');
        }

        async function deleteCategory(id, name) {
            if(!confirm('Delete the category "' + name + '"?\n\nProducts already using this label keep it, but it won\'t be selectable here anymore.')) return;
            const fd = new FormData();
            fd.append('ajax_action', 'delete_category');
            fd.append('csrf_token', CSRF_TOKEN);
            fd.append('id', id);
            try {
                const res = await fetch(window.location.href, { method: 'POST', body: fd });
                const data = await res.json();
                if(!data.ok) { alert(data.error || 'Could not delete category.'); return; }

                const li = document.querySelector('.category-item[data-id="' + id + '"]');
                if(li) li.remove();

                // If the deleted category was the selected one, clear the selection.
                if(document.getElementById('category_input').value === name) {
                    document.getElementById('category_input').value = '';
                    const t = document.getElementById('selected_category_text');
                    t.textContent = '-- Select a category --';
                    t.classList.add('text-slate-400');
                    t.classList.remove('text-slate-800');
                }
                if(document.querySelectorAll('.category-item').length === 0)
                    document.getElementById('category_empty').classList.remove('hidden');

                if(data.in_use > 0)
                    console.info(data.in_use + ' product(s) still use the "' + name + '" label.');
            } catch(e) {
                alert('Network error. Please try again.');
            }
        }

        function validateProductForm() {
            if(!document.getElementById('category_input').value.trim()) {
                alert('Please select or add a category.');
                document.getElementById('category_menu').classList.remove('hidden');
                document.getElementById('category_search').focus();
                return false;
            }
            return true;
        }

        /* ===================== SUPPLIER (unchanged) ===================== */
        function toggleNewSupplierForm() {
            const form = document.getElementById('new_supplier_form');
            const wrapper = document.getElementById('existing_supplier_wrapper');
            const isNewFlag = document.getElementById('is_new_supplier');
            const toggleBtn = document.getElementById('btn_toggle_supplier');
            const nameInput = document.getElementById('new_supplier_name');

            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                wrapper.classList.add('hidden');
                isNewFlag.value = "1";
                nameInput.setAttribute('required', 'required');
                toggleBtn.innerHTML = '<i class="fa-solid fa-rotate-left mr-1"></i> Use Existing';
                toggleBtn.classList.replace('text-[#0097B2]', 'text-orange-500');
            } else {
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

        // Close custom dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const sWrapper = document.getElementById('existing_supplier_wrapper');
            const sMenu = document.getElementById('supplier_menu');
            if (sWrapper && !sWrapper.contains(event.target)) sMenu.classList.add('hidden');

            const cWrapper = document.getElementById('category_wrapper');
            const cMenu = document.getElementById('category_menu');
            if (cWrapper && !cWrapper.contains(event.target)) cMenu.classList.add('hidden');
        });
    </script>
</body>
</html>