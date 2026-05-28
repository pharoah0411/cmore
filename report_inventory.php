<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Category', 'Quantity', 'Value (RM)'];
    $export_rows = [];
    $category_export = mysqli_query($conn, "SELECT CATEGORY, COALESCE(SUM(STOCK_QUANTITY),0) AS qty, COALESCE(SUM(STOCK_QUANTITY * UNIT_PRICE),0) AS value FROM PRODUCT GROUP BY CATEGORY ORDER BY value DESC");
    while ($category = mysqli_fetch_assoc($category_export)) {
        $export_rows[] = [
            $category['CATEGORY'] ?: 'Uncategorized',
            number_format($category['qty']),
            number_format($category['value'],2)
        ];
    }
    if ($export === 'pdf') {
        export_pdf('stock-valuation', 'Inventory Value by Category', $export_headers, $export_rows);
    } else {
        export_excel('stock-valuation', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Stock Valuation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-10">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Stock Valuation</h1>
                <p class="text-slate-500 font-medium mt-2">Review current inventory worth and track low-stock assets.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
                <a href="?export=pdf" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#0097B2] px-6 py-3 text-white font-bold shadow-sm hover:bg-teal-600 transition">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
                <a href="?export=excel" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#B9D977] px-6 py-3 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <a href="reports.php" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </header>

        <?php
        $stock_totals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(STOCK_QUANTITY),0) AS total_items, COALESCE(SUM(STOCK_QUANTITY * UNIT_PRICE),0) AS valuation FROM PRODUCT"));
        $category_totals = mysqli_query($conn, "SELECT CATEGORY, COALESCE(SUM(STOCK_QUANTITY),0) AS qty, COALESCE(SUM(STOCK_QUANTITY * UNIT_PRICE),0) AS value FROM PRODUCT GROUP BY CATEGORY ORDER BY value DESC LIMIT 7");
        $low_stock = mysqli_query($conn, "SELECT BRAND_NAME, CATEGORY, STOCK_QUANTITY, UNIT_PRICE FROM PRODUCT WHERE STOCK_QUANTITY <= 5 ORDER BY STOCK_QUANTITY ASC, BRAND_NAME ASC LIMIT 8");

        ?>

        <div class="grid gap-6 xl:grid-cols-3 mb-10">
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Total Stock On Hand</p>
                <p class="text-4xl font-black text-slate-900"><?php echo number_format($stock_totals['total_items']); ?></p>
                <p class="text-sm text-slate-500 mt-3">Items currently in inventory.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Inventory Value</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($stock_totals['valuation'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3">Current replacement value of stocked products.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Low Stock Alerts</p>
                <p class="text-4xl font-black text-slate-900"><?php echo mysqli_num_rows($low_stock); ?></p>
                <p class="text-sm text-slate-500 mt-3">Products that need replenishment.</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Value by Category</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Top Categories</h2>
                    </div>
                    <span class="text-[10px] uppercase font-black tracking-[0.25em] text-slate-400">Sorted by value</span>
                </div>
                <div class="space-y-4">
                    <?php if($category_totals && mysqli_num_rows($category_totals) > 0): ?>
                        <?php while($cat = mysqli_fetch_assoc($category_totals)): ?>
                            <div class="rounded-3xl bg-slate-50 p-5 border border-slate-100">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($cat['CATEGORY'] ?: 'Uncategorized'); ?></p>
                                        <p class="text-sm text-slate-500 mt-1"><?php echo number_format($cat['qty']); ?> units</p>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900">RM <?php echo number_format($cat['value'],2); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">No valuation breakdown available yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Reorder Candidates</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Low Stock</h2>
                    </div>
                    <span class="text-[10px] uppercase font-black tracking-[0.25em] text-slate-400">Quantity & value</span>
                </div>
                <?php if($low_stock && mysqli_num_rows($low_stock) > 0): ?>
                    <div class="space-y-3">
                        <?php while($item = mysqli_fetch_assoc($low_stock)): ?>
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-100">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($item['BRAND_NAME']); ?></p>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mt-1"><?php echo htmlspecialchars($item['CATEGORY'] ?: 'Uncategorized'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-slate-900"><?php echo number_format($item['STOCK_QUANTITY']); ?> pcs</p>
                                        <p class="text-[11px] text-slate-500">RM <?php echo number_format($item['UNIT_PRICE'],2); ?> each</p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500">No products are below the reorder threshold.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
