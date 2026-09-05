<?php include('config.php');
include 'check_expiry.php';

// config.php normally starts the session; safety net + CSRF token for the delete action.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// Pull and clear any one-time flash message (set by inventory_delete.php).
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
    <script>
        function toggleSupplier(id, event) {
            // Catch any element with .action-btn class
            if(event.target.closest('.action-btn')) return;
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) el.classList.remove('hidden');
            else el.classList.add('hidden');
        }
// ---- View Image Modal ----
        function viewImage(src, event) {
            // Stop the row expansion from triggering
            event.stopPropagation(); 
            document.getElementById('viewImageModalSrc').src = src;
            document.getElementById('viewImageModal').classList.remove('hidden');
            document.getElementById('viewImageModal').classList.add('flex');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeViewImageModal() {
            document.getElementById('viewImageModal').classList.add('hidden');
            document.getElementById('viewImageModal').classList.remove('flex');
            document.body.style.overflow = '';
        }
        // ---- Delete confirmation modal ----
        function confirmDelete(id, name) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeDeleteModal() {
            const m = document.getElementById('deleteModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeDeleteModal(); });
    </script>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Product Inventory</h1>
                <p class="text-slate-500 font-medium mt-1">Monitor stock levels and manage clinical supplies.</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="supplier.php" class="bg-white border-2 border-slate-200 text-slate-600 px-6 py-3 rounded-2xl font-bold shadow-sm hover:border-[#0097B2] hover:text-[#0097B2] hover:shadow-md transition-all inline-flex items-center">
                    <i class="fa-solid fa-truck-fast mr-2"></i> View Suppliers
                </a>
                
                <a href="inventory_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center border-2 border-[#0097B2]">
                    <i class="fa-solid fa-plus mr-2"></i> Add New Product
                </a>
            </div>
        </header>

        <?php if ($flash): ?>
        <div id="flashBanner" class="mb-6 flex items-center justify-between gap-4 p-5 rounded-2xl border shadow-sm
            <?php echo $flash['type'] === 'success'
                ? 'bg-teal-50 border-teal-200 text-teal-800'
                : 'bg-red-50 border-red-200 text-red-800'; ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check text-[#0097B2]' : 'fa-circle-exclamation text-red-500'; ?> text-xl"></i>
                <span class="font-bold"><?php echo htmlspecialchars($flash['msg']); ?></span>
            </div>
            <button type="button" onclick="document.getElementById('flashBanner').remove()" class="text-slate-400 hover:text-slate-600 transition shrink-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <div class="mb-8">
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-4xl">
                <div class="relative w-full md:w-1/3">
                    <select name="category" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700">
                        <option value="">All Categories</option>
                        <?php
                        $cat_res = mysqli_query($conn, "SELECT DISTINCT CATEGORY FROM PRODUCT WHERE CATEGORY IS NOT NULL");
                        while($cat = mysqli_fetch_assoc($cat_res)):
                            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['CATEGORY']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($cat['CATEGORY']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($cat['CATEGORY']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
                <div class="relative w-full md:w-1/4">
                    <?php $filter_selected = isset($_GET['filter']) ? $_GET['filter'] : ''; ?>
                    <select name="filter" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700">
                        <option value="" <?php echo $filter_selected === '' ? 'selected' : ''; ?>>All Products</option>
                        <option value="low" <?php echo $filter_selected === 'low' ? 'selected' : ''; ?>>Low Stock (&lt; 5)</option>
                        <option value="expired" <?php echo $filter_selected === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="with_image" <?php echo $filter_selected === 'with_image' ? 'selected' : ''; ?>>With Picture</option>
                        <option value="no_image" <?php echo $filter_selected === 'no_image' ? 'selected' : ''; ?>>Without Picture</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search Brand Name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all font-medium text-slate-700">
                </div>
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-bold hover:bg-[#0097B2] transition shadow-lg shrink-0">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </button>
            </form>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left table-fixed">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="w-[35%] p-6 text-xs font-black uppercase tracking-widest text-slate-400">Product Info</th>
                        <th class="w-[20%] p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-center">Stock Level</th>
                        <th class="w-[20%] p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-right">Unit Price</th>
                        <th class="w-[25%] p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <div id="viewImageModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4 transition-all"
         onclick="if(event.target === this) closeViewImageModal()">
        <div class="relative max-w-2xl w-full flex flex-col items-center animate-fade-in-up">
            <button type="button" onclick="closeViewImageModal()" class="absolute -top-12 right-0 text-white hover:text-slate-300 transition text-3xl">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="bg-white p-2 rounded-3xl shadow-2xl w-full max-h-[85vh] flex items-center justify-center overflow-hidden relative">
                <img id="viewImageModalSrc" src="" alt="Product Full View" class="w-full h-full object-contain rounded-2xl max-h-[80vh]">
            </div>
            <p class="text-white/70 text-sm mt-4 font-medium"><i class="fa-solid fa-compress mr-1"></i> Click anywhere outside to close</p>
        </div>
    </div>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $where_clauses = [];
                    if (!empty($_GET['category'])) $where_clauses[] = "p.CATEGORY = '" . mysqli_real_escape_string($conn, $_GET['category']) . "'";
                    if (!empty($_GET['search'])) $where_clauses[] = "p.BRAND_NAME LIKE '%" . mysqli_real_escape_string($conn, $_GET['search']) . "%'";

                    // Apply filter: low stock, expired, or with image
                    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
                    if ($filter === 'low') {
                        $where_clauses[] = "p.STOCK_QUANTITY < 5";
                    } elseif ($filter === 'expired') {
                        $where_clauses[] = "p.EXPIRY_DATE IS NOT NULL AND DATE(p.EXPIRY_DATE) < CURDATE()";
                    } elseif ($filter === 'with_image') {
                        $where_clauses[] = "p.PRODUCT_IMAGE IS NOT NULL AND p.PRODUCT_IMAGE != ''";
                    } elseif ($filter === 'no_image') { // ADD THIS LINE!
                        $where_clauses[] = "(p.PRODUCT_IMAGE IS NULL OR p.PRODUCT_IMAGE = '')";
                    }

                    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

                    // Order low-stock products to the top
                    $sql = "SELECT p.*, s.COMPANY_NAME, s.CONTACT_PERSON, s.PHONE_NUMBER, s.EMAIL 
                        FROM product p 
                        LEFT JOIN supplier s ON p.SUPPLIER_ID = s.SUPPLIER_ID 
                        $where_sql 
                        ORDER BY (p.STOCK_QUANTITY < 5) DESC, p.STOCK_QUANTITY ASC, p.CATEGORY ASC, p.BRAND_NAME ASC";
                            
                    $res = mysqli_query($conn, $sql);

                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)):
                            $is_low = ($row['STOCK_QUANTITY'] < 5);
                            $supp_row_id = "supp_" . $row['PRODUCT_ID'];
                            $js_name = htmlspecialchars(json_encode($row['BRAND_NAME']), ENT_QUOTES);
                            
                            $expiry_badge = '';
                            if(!empty($row['EXPIRY_DATE'])) {
                                $exp_date = strtotime($row['EXPIRY_DATE']);
                                $days_left = ($exp_date - time()) / (60 * 60 * 24);
                                
                                if($days_left < 0) {
                                    $expiry_badge = '<span class="inline-block text-[9px] font-black bg-red-100 text-red-600 px-2 py-0.5 rounded uppercase tracking-tighter mt-1 ml-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i> EXPIRED</span>';
                                } elseif($days_left <= 90) {
                                    $expiry_badge = '<span class="inline-block text-[9px] font-black bg-orange-100 text-orange-600 px-2 py-0.5 rounded uppercase tracking-tighter mt-1 ml-2"><i class="fa-solid fa-clock mr-1"></i> Exp: ' . date('M Y', $exp_date) . '</span>';
                                } else {
                                    $expiry_badge = '<span class="inline-block text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-tighter mt-1 ml-2">Exp: ' . date('M Y', $exp_date) . '</span>';
                                }
                            }
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" onclick="toggleSupplier('<?php echo $supp_row_id; ?>', event)">
                        <td class="p-6">
                            <div class="flex items-start space-x-4">
                                <?php if(!empty($row['PRODUCT_IMAGE'])): ?>
                                    <div onclick="viewImage('<?php echo htmlspecialchars($row['PRODUCT_IMAGE']); ?>', event)" class="w-12 h-12 rounded-2xl shrink-0 overflow-hidden border border-slate-200 shadow-sm bg-white flex items-center justify-center cursor-pointer hover:border-[#0097B2] hover:shadow-md hover:opacity-80 transition-all group-hover:border-[#0097B2]" title="Click to view image">
                                        <img src="<?php echo htmlspecialchars($row['PRODUCT_IMAGE']); ?>" alt="Product Image" class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-white group-hover:text-[#0097B2] shadow-sm transition-all border border-transparent group-hover:border-slate-100 shrink-0">
                                        <i class="fa-solid fa-box-open text-lg"></i>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-0.5">#PRD-<?php echo str_pad($row['PRODUCT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                    <p class="font-bold text-slate-800 text-lg leading-tight truncate"><?php echo htmlspecialchars($row['BRAND_NAME']); ?></p>
                                    <div class="flex items-center">
                                        <span class="inline-block text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-tighter mt-1"><?php echo htmlspecialchars($row['CATEGORY']); ?></span>
                                        <?php echo $expiry_badge; ?> 
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-xl font-black <?php echo $is_low ? 'text-red-500' : 'text-slate-800'; ?>"><?php echo $row['STOCK_QUANTITY']; ?></span>
                                <?php if($is_low): ?>
                                    <span class="text-[8px] font-black text-red-400 uppercase tracking-widest animate-pulse">Low Stock</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-6 text-right font-mono text-lg font-bold text-slate-700">
                            <span class="text-slate-300 text-xs mr-1">RM</span><?php echo number_format($row['UNIT_PRICE'], 2); ?>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="inventory_edit.php?id=<?php echo $row['PRODUCT_ID']; ?>" class="action-btn w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300 shadow-sm" title="Edit Product">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>
                                <button type="button" onclick="confirmDelete(<?php echo $row['PRODUCT_ID']; ?>, <?php echo $js_name; ?>)" class="action-btn w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition duration-300 shadow-sm" title="Delete Product">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr id="<?php echo $supp_row_id; ?>" class="hidden bg-slate-50/50 border-b border-slate-100">
                        <td colspan="4">
                            <div class="p-6 px-10">
                                <div class="grid md:grid-cols-2 gap-10">
                                    
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-3">Supplier Information</p>
                                        <?php if(!empty($row['COMPANY_NAME'])): ?>
                                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm max-w-sm">
                                                <div class="flex items-center space-x-3 mb-3 border-b border-slate-50 pb-3">
                                                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-[#0097B2] flex items-center justify-center shrink-0">
                                                        <i class="fa-solid fa-building"></i>
                                                    </div>
                                                    <p class="text-slate-800 font-bold text-base leading-tight"><?php echo htmlspecialchars($row['COMPANY_NAME']); ?></p>
                                                </div>
                                                <div class="space-y-2">
                                                    <p class="text-slate-600 text-sm flex items-center">
                                                        <i class="fa-solid fa-user text-slate-400 w-5 mr-1 text-center"></i> 
                                                        <?php echo htmlspecialchars($row['CONTACT_PERSON']); ?>
                                                    </p>
                                                    <?php if(!empty($row['PHONE_NUMBER'])): ?>
                                                    <p class="text-slate-600 text-sm flex items-center">
                                                        <i class="fa-solid fa-phone text-slate-400 w-5 mr-1 text-center"></i> 
                                                        <?php echo htmlspecialchars($row['PHONE_NUMBER']); ?>
                                                    </p>
                                                    <?php endif; ?>
                                                    <?php if(!empty($row['EMAIL'])): ?>
                                                    <p class="text-slate-600 text-sm flex items-center truncate" title="<?php echo htmlspecialchars($row['EMAIL']); ?>">
                                                        <i class="fa-solid fa-envelope text-slate-400 w-5 mr-1 text-center"></i> 
                                                        <?php echo htmlspecialchars($row['EMAIL']); ?>
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-4 pt-4 border-t border-slate-50">
                                                    <a href="supplier.php?search=<?php echo urlencode($row['COMPANY_NAME']); ?>" class="inline-flex items-center text-sm font-bold text-[#0097B2] hover:text-teal-700 transition">
                                                        View & Manage Supplier <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-slate-400 italic mb-4 text-sm">No supplier assigned to this product.</p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex flex-col justify-between">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-3">Stock Overview</p>
                                            <div class="space-y-2">
                                                <div class="flex items-center">
                                                    <span class="text-slate-500 w-32 text-sm font-semibold">Current Quantity:</span>
                                                    <span class="text-slate-800 font-black text-lg"><?php echo $row['STOCK_QUANTITY']; ?></span>
                                                </div>
                                                <div class="flex items-center">
                                                    <span class="text-slate-500 w-32 text-sm font-semibold">Expiry Date:</span>
                                                    <span class="text-slate-800 font-black"><?php echo !empty($row['EXPIRY_DATE']) ? date('M d, Y', strtotime($row['EXPIRY_DATE'])) : '<span class="text-slate-400 font-normal">-</span>'; ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-6">
                                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-3">Actions</p>
                                            <div class="flex items-center space-x-3">
                                                <a href="inventory_view.php?product_id=<?php echo $row['PRODUCT_ID']; ?>" class="action-btn px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center hover:bg-[#0097B2] hover:border-[#0097B2] hover:text-white transition shadow-sm text-sm font-bold">
                                                    <i class="fa-solid fa-eye mr-2"></i> View
                                                </a>
                                                <a href="inventory_edit.php?id=<?php echo $row['PRODUCT_ID']; ?>" class="action-btn px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center hover:bg-[#0097B2] hover:border-[#0097B2] hover:text-white transition shadow-sm text-sm font-bold">
                                                    <i class="fa-solid fa-pen-to-square mr-2"></i> Edit
                                                </a>
                                                <a href="adjust_stock.php?product_id=<?php echo $row['PRODUCT_ID']; ?>" class="action-btn px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center hover:bg-[#0097B2] hover:border-[#0097B2] hover:text-white transition shadow-sm text-sm font-bold">
                                                    <i class="fa-solid fa-scale-unbalanced-flip mr-2"></i> Adjust
                                                </a>
                                                <button type="button" onclick="confirmDelete(<?php echo $row['PRODUCT_ID']; ?>, <?php echo $js_name; ?>)" class="action-btn px-4 py-2 rounded-xl bg-white border border-red-200 text-red-500 flex items-center hover:bg-red-500 hover:border-red-500 hover:text-white transition shadow-sm text-sm font-bold">
                                                    <i class="fa-solid fa-trash mr-2"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="p-12 text-center text-slate-400 font-bold italic">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
         onclick="if(event.target === this) closeDeleteModal()">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-8">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mb-6 mx-auto">
                <i class="fa-solid fa-trash-can text-2xl"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900 text-center tracking-tight mb-2">Delete this product?</h3>
            <p class="text-slate-500 text-center font-medium">You're about to permanently delete</p>
            <p id="deleteProductName" class="text-slate-900 font-bold text-center text-lg mt-1 mb-5 break-words">—</p>
            <p class="text-xs text-slate-400 text-center mb-8 bg-slate-50 rounded-xl py-3 px-4">
                <i class="fa-solid fa-circle-info mr-1"></i> This can't be undone. Products linked to sales history can't be deleted.
            </p>
            <form method="POST" action="inventory_delete.php" class="flex gap-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="product_id" id="deleteProductId" value="">
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES); ?>">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-3 rounded-2xl bg-red-500 text-white font-bold hover:bg-red-600 transition shadow-lg shadow-red-100">
                    <i class="fa-solid fa-trash mr-2"></i> Delete
                </button>
            </form>
        </div>
    </div>
</body>
</html>