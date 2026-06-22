<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Product', 'Units Sold', 'Revenue (RM)'];
    $export_rows = [];
    $top_products_export = mysqli_query($conn, "SELECT p.BRAND_NAME, SUM(si.QUANTITY) AS qty_sold, COALESCE(SUM(si.QUANTITY * p.UNIT_PRICE),0) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME ORDER BY qty_sold DESC LIMIT 10");
    while ($product = mysqli_fetch_assoc($top_products_export)) {
        $export_rows[] = [
            $product['BRAND_NAME'],
            number_format($product['qty_sold']),
            number_format($product['revenue'], 2)
        ];
    }
    if ($export === 'pdf') {
        export_pdf('revenue-analysis', 'Top Selling Products', $export_headers, $export_rows);
    } else {
        export_excel('revenue-analysis', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Revenue Analysis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <?php include('sidebar.php'); ?>
    <main class="flex-1 ml-72 p-12">
        <div id="reportContent">
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-10">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Revenue Analysis</h1>
                <p class="text-slate-500 font-medium mt-2">View clinic income, payment status, and top-performing products.</p>
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
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');

        $month_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS sale_count, COALESCE(SUM(TOTAL_AMOUNT),0) AS gross_revenue, COALESCE(SUM(PAID_AMOUNT),0) AS paid_revenue FROM SALES WHERE SALE_DATE BETWEEN '$month_start' AND '$month_end'"));
        $outstanding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS open_sales, COALESCE(SUM(TOTAL_AMOUNT - PAID_AMOUNT),0) AS balance FROM SALES WHERE PAYMENT_STATUS <> 'Paid'"));
        $top_products = mysqli_query($conn, "SELECT p.BRAND_NAME, SUM(si.QUANTITY) AS qty_sold, COALESCE(SUM(si.QUANTITY * p.UNIT_PRICE),0) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME ORDER BY qty_sold DESC LIMIT 6");
        $top_customers = mysqli_query($conn, "SELECT COALESCE(p.NAME, 'Walk-in') AS customer_name, COALESCE(SUM(s.TOTAL_AMOUNT),0) AS total_spent FROM SALES s LEFT JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID GROUP BY s.PATIENT_ID, p.NAME ORDER BY total_spent DESC LIMIT 6");

        ?>

        <div class="grid gap-6 xl:grid-cols-3 mb-10">
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">This Month</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($month_data['gross_revenue'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3">Across <?php echo $month_data['sale_count']; ?> transactions.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Collected</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($month_data['paid_revenue'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3">Payment captured through all methods.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Outstanding</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($outstanding['balance'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3"><?php echo $outstanding['open_sales']; ?> invoices require follow-up.</p>
            </div>
        </div>

        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Revenue Analysis</p>
                <h2 class="text-2xl font-bold text-slate-900 mt-2">Sales Performance</h2>
            </div>
            <span class="text-sm text-slate-500">All-time metrics</span>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Top Selling Products</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Volume Leaders</h2>
                    </div>
                    <span class="text-[10px] uppercase font-black tracking-[0.25em] text-slate-400">Past 12 months</span>
                </div>
                <div class="space-y-4">
                    <?php if($top_products && mysqli_num_rows($top_products) > 0): ?>
                        <?php while($product = mysqli_fetch_assoc($top_products)): ?>
                            <div class="rounded-3xl bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($product['BRAND_NAME']); ?></p>
                                        <p class="text-sm text-slate-500 mt-1"><?php echo number_format($product['qty_sold']); ?> units sold</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-900">RM <?php echo number_format($product['revenue'],2); ?></p>
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Revenue</p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">No product sales data available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Top Customers</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Highest Spending</h2>
                    </div>
                    <span class="text-[10px] uppercase font-black tracking-[0.25em] text-slate-400">All time</span>
                </div>
                <div class="space-y-4">
                    <?php if($top_customers && mysqli_num_rows($top_customers) > 0): ?>
                        <?php while($customer = mysqli_fetch_assoc($top_customers)): ?>
                            <div class="rounded-3xl bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($customer['customer_name']); ?></p>
                                        <p class="text-sm text-slate-500 mt-1">Total spent</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-900">RM <?php echo number_format($customer['total_spent'],2); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">No customer spending information found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
