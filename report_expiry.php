<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Product', 'Category', 'Qty', 'Unit Price', 'Expiry Date'];
    $export_rows = [];
    $expiry_export = mysqli_query($conn, "SELECT BRAND_NAME, CATEGORY, STOCK_QUANTITY, UNIT_PRICE, EXPIRY_DATE FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 180 DAY) ORDER BY EXPIRY_DATE ASC, BRAND_NAME ASC");
    while ($item = mysqli_fetch_assoc($expiry_export)) {
        $export_rows[] = [
            $item['BRAND_NAME'],
            $item['CATEGORY'] ?: 'Uncategorized',
            number_format($item['STOCK_QUANTITY']),
            number_format($item['UNIT_PRICE'],2),
            $item['EXPIRY_DATE'] ? date('d M Y', strtotime($item['EXPIRY_DATE'])) : 'N/A'
        ];
    }
    if ($export === 'pdf') {
        export_pdf('expiry-wastage', 'Expiring Product List', $export_headers, $export_rows);
    } else {
        export_excel('expiry-wastage', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Expiry & Wastage</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Expiry & Wastage</h1>
                <p class="text-slate-500 font-medium mt-2">Track products that will expire soon so you can minimize write-offs.</p>
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
        $expiry_items = mysqli_query($conn, "SELECT BRAND_NAME, CATEGORY, STOCK_QUANTITY, UNIT_PRICE, EXPIRY_DATE FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 180 DAY) ORDER BY EXPIRY_DATE ASC, BRAND_NAME ASC");
        $expiry_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(STOCK_QUANTITY * UNIT_PRICE),0) AS total_value FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 180 DAY)"));
        $expiry_count = mysqli_num_rows($expiry_items);

        ?>

        <div class="grid gap-6 xl:grid-cols-3 mb-10">
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#fb923c] mb-3">Expiring Soon</p>
                <p class="text-4xl font-black text-slate-900"><?php echo number_format($expiry_count); ?></p>
                <p class="text-sm text-slate-500 mt-3">Items expiring in the next 6 months.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Potential Value</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($expiry_total['total_value'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3">Estimated value at risk if unsold.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Priority</p>
                <p class="text-4xl font-black text-slate-900">Review Now</p>
                <p class="text-sm text-slate-500 mt-3">Plan promotions and transfers before expiry.</p>
            </div>
        </div>

        <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#fb923c]">Expiring Products</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">Products Due for Review</h2>
                </div>
                <span class="text-sm text-slate-500"><?php echo number_format($expiry_count); ?> records</span>
            </div>

            <?php if($expiry_items && mysqli_num_rows($expiry_items) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Product</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Category</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Stock</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Expiry Date</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php while($item = mysqli_fetch_assoc($expiry_items)): ?>
                                <?php $days = round((strtotime($item['EXPIRY_DATE']) - time()) / 86400); ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-5 font-semibold text-slate-800"><?php echo htmlspecialchars($item['BRAND_NAME']); ?></td>
                                    <td class="px-6 py-5 text-slate-500"><?php echo htmlspecialchars($item['CATEGORY'] ?: 'Uncategorized'); ?></td>
                                    <td class="px-6 py-5 text-slate-900"><?php echo number_format($item['STOCK_QUANTITY']); ?></td>
                                    <td class="px-6 py-5 text-slate-900"><?php echo date('d M Y', strtotime($item['EXPIRY_DATE'])); ?> <span class="ml-2 text-[10px] uppercase tracking-[0.3em] font-black <?php echo $days < 30 ? 'text-red-600' : 'text-orange-500'; ?>"><?php echo $days < 0 ? 'Expired' : ($days . 'd'); ?></span></td>
                                    <td class="px-6 py-5 font-bold text-slate-900">RM <?php echo number_format($item['STOCK_QUANTITY'] * $item['UNIT_PRICE'],2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-500">No expiring products were found for the next 180 days.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
