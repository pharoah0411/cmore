<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Product', 'Category', 'Units Sold', 'Revenue (RM)'];
    $export_rows = [];
    $products_export = mysqli_query($conn, "SELECT p.BRAND_NAME, p.CATEGORY, COALESCE(SUM(si.QUANTITY),0) AS qty_sold, COALESCE(SUM(si.QUANTITY * p.UNIT_PRICE),0) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME, p.CATEGORY ORDER BY qty_sold DESC LIMIT 10");
    while ($product = mysqli_fetch_assoc($products_export)) {
        $export_rows[] = [
            $product['BRAND_NAME'],
            $product['CATEGORY'] ?: 'Uncategorized',
            number_format($product['qty_sold']),
            number_format($product['revenue'],2)
        ];
    }
    if ($export === 'pdf') {
        export_pdf('top-selling-products', 'Top Selling Products', $export_headers, $export_rows);
    } else {
        export_excel('top-selling-products', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Top Selling Products</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Top Selling Products</h1>
                <p class="text-slate-500 font-medium mt-2">Identify high-demand optics and maximize your bestseller stock.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="printReport()" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
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

        <section id="reportContent" class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">

            <?php
            $top_products = mysqli_query($conn, "SELECT p.PRODUCT_ID, p.BRAND_NAME, p.CATEGORY, COALESCE(SUM(si.QUANTITY),0) AS qty_sold, COALESCE(SUM(si.QUANTITY * p.UNIT_PRICE),0) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME, p.CATEGORY ORDER BY qty_sold DESC LIMIT 10");
            $product_count = mysqli_num_rows($top_products);
            $total_units = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(QUANTITY),0) AS total FROM SALES_ITEM"));
            $top_brand = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.BRAND_NAME, SUM(si.QUANTITY) AS units FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME ORDER BY units DESC LIMIT 1"));
            ?>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#a3e635]">Top Selling Products</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">Best-Selling Stock</h2>
                </div>
                <span class="text-sm text-slate-500">Top 10 products by quantity</span>
            </div>

            <div class="grid gap-6 xl:grid-cols-3 mb-10">
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#a3e635] mb-3">SKU Count</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($product_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Top product lines contributing to revenue.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Units Sold</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($total_units['total']); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Cumulative sales across all products.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Top Product</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo htmlspecialchars($top_brand['BRAND_NAME'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Highest-selling brand by volume.</p>
                </div>
            </div>

            <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#a3e635]">Sales Ranking</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Best-Selling Stock</h2>
                    </div>
                    <span class="text-sm text-slate-500">Top 10 products by quantity</span>
                </div>

                <?php if($top_products && mysqli_num_rows($top_products) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Product</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Category</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Units Sold</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php 
                                // Reset the pointer since we looped to count rows earlier
                                mysqli_data_seek($top_products, 0);
                                while($product = mysqli_fetch_assoc($top_products)): 
                                ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5 font-semibold text-slate-900"><?php echo htmlspecialchars($product['BRAND_NAME']); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($product['CATEGORY'] ?: 'Uncategorized'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo number_format($product['qty_sold']); ?></td>
                                        <td class="px-6 py-5 font-bold text-slate-900">RM <?php echo number_format($product['revenue'],2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500">No sales items were found for this report.</p>
                <?php endif; ?>
            </section>
        </section>
    </main>
    
    <script>
        function buildPrintableHTML(contentHtml) {
            const head = document.head.innerHTML;
            return `<!doctype html><html>${head}<body style="margin:0;padding:20px;background:#f8fafc;font-family: 'Plus Jakarta Sans', sans-serif;">${contentHtml}</body></html>`;
        }

        function printReport() {
            const reportEl = document.getElementById('reportContent');
            if (!reportEl) return window.print();
            const printWindow = window.open('', '', 'height=800,width=1000');
            const content = reportEl.outerHTML;
            printWindow.document.write(buildPrintableHTML(content));
            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); }, 300);
        }
    </script>
</body>
</html>