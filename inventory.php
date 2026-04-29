<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
    <script>
        // Function to toggle the supplier details row
        function toggleSupplier(id, event) {
            // Prevent toggling if action buttons (edit/delete) are clicked
            if(event.target.closest('button.action-btn')) return;
            
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }
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
            <button class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                <i class="fa-solid fa-plus mr-2"></i> Add New Product
            </button>
        </header>

        <div class="mb-8">
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-4xl">
                <div class="relative w-full md:w-1/3">
                    <select name="category" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700">
                        <option value="">All Categories</option>
                        <?php
                        // Fetch dynamic categories
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

                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search Brand Name..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all font-medium text-slate-700">
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
                <tbody class="divide-y divide-slate-50">
                    <?php
                    // Build query based on filters
                    $where_clauses = [];
                    if (!empty($_GET['category'])) {
                        $c = mysqli_real_escape_string($conn, $_GET['category']);
                        $where_clauses[] = "p.CATEGORY = '$c'";
                    }
                    if (!empty($_GET['search'])) {
                        $s = mysqli_real_escape_string($conn, $_GET['search']);
                        $where_clauses[] = "p.BRAND_NAME LIKE '%$s%'";
                    }

                    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

                    // Join Product with Supplier
                    $sql = "SELECT p.*, s.COMPANY_NAME, s.CONTACT_PERSON, s.PHONE_NUMBER, s.EMAIL 
                            FROM PRODUCT p 
                            LEFT JOIN SUPPLIER s ON p.SUPPLIER_ID = s.SUPPLIER_ID 
                            $where_sql 
                            ORDER BY p.CATEGORY ASC, p.BRAND_NAME ASC";
                            
                    $res = mysqli_query($conn, $sql);

                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)):
                            $is_low = ($row['STOCK_QUANTITY'] < 5);
                            $supp_row_id = "supp_" . $row['PRODUCT_ID'];
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" onclick="toggleSupplier('<?php echo $supp_row_id; ?>', event)">
                        <td class="p-6">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-white group-hover:text-[#0097B2] shadow-sm transition-all border border-transparent group-hover:border-slate-100 shrink-0">
                                    <i class="fa-solid fa-box-open text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-0.5">#PRD-<?php echo str_pad($row['PRODUCT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                    <p class="font-bold text-slate-800 text-lg leading-tight truncate"><?php echo $row['BRAND_NAME']; ?></p>
                                    <span class="inline-block text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-tighter mt-1"><?php echo $row['CATEGORY']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-xl font-black <?php echo $is_low ? 'text-red-500' : 'text-slate-800'; ?>">
                                    <?php echo $row['STOCK_QUANTITY']; ?>
                                </span>
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
                                <span class="text-[10px] uppercase font-black tracking-widest text-[#0097B2] bg-teal-50 px-3 py-1.5 rounded-full mr-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click to view Supplier
                                </span>
                                <button class="action-btn w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300 shadow-sm">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </button>
                                <button class="action-btn w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition duration-300 shadow-sm">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr id="<?php echo $supp_row_id; ?>" class="hidden bg-slate-50/50 border-b border-slate-100">
                        <td colspan="4" class="p-6 pt-2">
                            <div class="ml-16 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                                <?php if(!empty($row['COMPANY_NAME'])): ?>
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-slate-900 text-[#B9D977] rounded-xl flex items-center justify-center shadow-lg">
                                            <i class="fa-solid fa-truck-field"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm"><?php echo $row['COMPANY_NAME']; ?></h4>
                                            <div class="flex items-center space-x-4 mt-1 text-xs text-slate-500 font-medium">
                                                <span><i class="fa-solid fa-user text-slate-400 mr-1"></i> <?php echo $row['CONTACT_PERSON']; ?></span>
                                                <span><i class="fa-solid fa-phone text-slate-400 mr-1"></i> <?php echo $row['PHONE_NUMBER']; ?></span>
                                                <span><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> <?php echo $row['EMAIL']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="action-btn text-[10px] uppercase font-black tracking-widest text-slate-400 border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-900 hover:text-white hover:border-transparent transition">
                                        Edit Supplier
                                    </button>
                                <?php else: ?>
                                    <div class="flex items-center space-x-3 text-slate-400 italic text-sm">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        <span>No supplier associated with this product.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="4" class="p-12 text-center text-slate-400 font-bold italic">No products found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>