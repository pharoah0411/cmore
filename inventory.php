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
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Product Inventory</h1>
                <p class="text-slate-500 font-medium mt-1">Monitor stock levels and manage clinical supplies.</p>
            </div>
            <button class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                <i class="fa-solid fa-plus mr-2"></i> Add New Product
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Category & Brand</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-center">Stock Level</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-right">Unit Price</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM PRODUCT ORDER BY CATEGORY ASC");
                    while($row = mysqli_fetch_assoc($res)):
                        $is_low = ($row['STOCK_QUANTITY'] < 5);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-white group-hover:text-[#0097B2] shadow-sm transition-all border border-transparent group-hover:border-slate-100">
                                    <i class="fa-solid fa-box-open text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-lg leading-tight"><?php echo $row['BRAND_NAME']; ?></p>
                                    <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-tighter"><?php echo $row['CATEGORY']; ?></span>
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
                                <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </button>
                                <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition duration-300">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>