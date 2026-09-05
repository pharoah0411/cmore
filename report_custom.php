<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$type = $_GET['type'] ?? 'sales';
$period = $_GET['period'] ?? 'last_6_months';
$month = $_GET['month'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category = trim($_GET['category'] ?? '');

if ($period === 'last_6_months') {
    $safe_start = date('Y-m-d', strtotime('-6 months'));
    $safe_end = date('Y-m-d');
} elseif ($period === 'last_12_months') {
    $safe_start = date('Y-m-d', strtotime('-12 months'));
    $safe_end = date('Y-m-d');
} elseif ($period === 'month' && $month) {
    $d = DateTime::createFromFormat('Y-m', $month);
    if ($d) {
        $safe_start = $d->format('Y-m-01');
        $safe_end = $d->format('Y-m-t');
    } else {
        $safe_start = date('Y-m-d', strtotime('-6 months'));
        $safe_end = date('Y-m-d');
    }
} elseif ($period === 'custom' && $start_date && $end_date) {
    $start_value = DateTime::createFromFormat('!Y-m-d', $start_date);
    $end_value = DateTime::createFromFormat('!Y-m-d', $end_date);
    if ($start_value && $end_value && $start_value <= $end_value) {
        $safe_start = $start_value->format('Y-m-d');
        $safe_end = $end_value->format('Y-m-d');
    } else {
        $safe_start = date('Y-m-d', strtotime('-6 months'));
        $safe_end = date('Y-m-d');
    }
} else {
    $safe_start = date('Y-m-d', strtotime('-6 months'));
    $safe_end = date('Y-m-d');
}

$safe_start_sql = mysqli_real_escape_string($conn, $safe_start);
$safe_end_sql = mysqli_real_escape_string($conn, $safe_end);
$category_sql = mysqli_real_escape_string($conn, $category);
$sales_date_sql = "s.SALE_DATE >= '{$safe_start_sql} 00:00:00' AND s.SALE_DATE < DATE_ADD('{$safe_end_sql}', INTERVAL 1 DAY)";
$exam_date_sql = "EXAM_DATE >= '{$safe_start_sql}' AND EXAM_DATE < DATE_ADD('{$safe_end_sql}', INTERVAL 1 DAY)";
$inventory_category_sql = $category !== '' ? " AND p.CATEGORY = '{$category_sql}'" : '';

$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $report_data = [];
    $export_rows = [];
    if($type === 'sales') {
        $report_rows = mysqli_query($conn, "SELECT s.SALE_ID, p.NAME AS patient_name, u.NAME AS staff_name, s.SALE_DATE, s.TOTAL_AMOUNT, s.PAID_AMOUNT, s.PAYMENT_METHOD, s.PAYMENT_STATUS FROM SALES s LEFT JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID LEFT JOIN USER u ON s.STAFF_ID = u.USER_ID WHERE {$sales_date_sql} ORDER BY s.SALE_DATE DESC LIMIT 100");
        while($row = mysqli_fetch_assoc($report_rows)) {
            $report_data[] = $row;
        }
        $export_headers = ['Invoice', 'Patient', 'Staff', 'Date', 'Total (RM)', 'Paid (RM)', 'Method', 'Status'];
        foreach ($report_data as $row) {
            $export_rows[] = [
                $row['SALE_ID'],
                $row['patient_name'] ?: 'Walk-in',
                $row['staff_name'] ?: 'N/A',
                date('d M Y', strtotime($row['SALE_DATE'])),
                number_format($row['TOTAL_AMOUNT'],2),
                number_format($row['PAID_AMOUNT'],2),
                $row['PAYMENT_METHOD'],
                $row['PAYMENT_STATUS']
            ];
        }
    } elseif($type === 'inventory') {
        $report_rows = mysqli_query($conn, "SELECT p.PRODUCT_ID, p.BRAND_NAME, p.CATEGORY, p.STOCK_QUANTITY, p.UNIT_PRICE, p.EXPIRY_DATE FROM PRODUCT p WHERE 1=1 {$inventory_category_sql} ORDER BY p.BRAND_NAME ASC LIMIT 100");
        while($row = mysqli_fetch_assoc($report_rows)) {
            $report_data[] = $row;
        }
        $export_headers = ['Product', 'Category', 'Stock', 'Unit Price', 'Expiry'];
        foreach ($report_data as $row) {
            $export_rows[] = [
                $row['BRAND_NAME'],
                $row['CATEGORY'] ?: 'Uncategorized',
                number_format($row['STOCK_QUANTITY']),
                number_format($row['UNIT_PRICE'],2),
                $row['EXPIRY_DATE'] ? date('d M Y', strtotime($row['EXPIRY_DATE'])) : 'N/A'
            ];
        }
    } elseif($type === 'patients') {
        $report_rows = mysqli_query($conn, "SELECT PATIENT_ID, NAME, IC_NUMBER, PHONE_NUMBER, EMAIL FROM PATIENT ORDER BY NAME ASC LIMIT 100");
        while($row = mysqli_fetch_assoc($report_rows)) {
            $report_data[] = $row;
        }
        $export_headers = ['Name', 'IC Number', 'Phone', 'Email'];
        foreach ($report_data as $row) {
            $export_rows[] = [
                $row['NAME'],
                $row['IC_NUMBER'] ?: 'N/A',
                $row['PHONE_NUMBER'] ?: 'N/A',
                $row['EMAIL'] ?: 'N/A'
            ];
        }
    } else {
        $report_rows = mysqli_query($conn, "SELECT EXAM_ID, PATIENT_ID, OPTOMETRIST_ID, EXAM_DATE FROM EYE_EXAMINATION WHERE {$exam_date_sql} ORDER BY EXAM_DATE DESC LIMIT 100");
        while($row = mysqli_fetch_assoc($report_rows)) {
            $report_data[] = $row;
        }
        $export_headers = ['Exam ID', 'Patient ID', 'Optometrist', 'Date'];
        foreach ($report_data as $row) {
            $export_rows[] = [
                $row['EXAM_ID'],
                $row['PATIENT_ID'],
                $row['OPTOMETRIST_ID'],
                date('d M Y', strtotime($row['EXAM_DATE']))
            ];
        }
    }

    if ($export === 'pdf') {
        export_pdf('custom-report-' . $type, 'Custom Report', $export_headers, $export_rows);
    } else {
        export_excel('custom-report-' . $type, $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Custom Report</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Custom Report</h1>
                <p class="text-slate-500 font-medium mt-2">Your selected custom report is shown below. Export options are available if your source data is present.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="printReport()" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" onclick="exportPdfPrintable(event)" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#0097B2] px-6 py-3 text-white font-bold shadow-sm hover:bg-teal-600 transition">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#B9D977] px-6 py-3 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <a href="reports.php" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </header>

        <?php
        $valid_types = ['sales' => 'Sales & Transactions', 'inventory' => 'Stock & Inventory Level', 'patients' => 'Patient Directory', 'appointments' => 'Clinical Appointments'];
        $report_label = $valid_types[$type] ?? 'Custom Report';
        ?>

        <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 mb-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Custom Parameters</p>
                    <h2 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($report_label); ?></h2>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 text-sm text-slate-500">
                    <span class="bg-slate-50 px-4 py-3 rounded-2xl border border-slate-200">Start: <?php echo htmlspecialchars($safe_start); ?></span>
                    <span class="bg-slate-50 px-4 py-3 rounded-2xl border border-slate-200">End: <?php echo htmlspecialchars($safe_end); ?></span>
                    <?php if ($category !== ''): ?><span class="bg-slate-50 px-4 py-3 rounded-2xl border border-slate-200">Category: <?php echo htmlspecialchars($category); ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <?php
        $report_data = [];
        if($type === 'sales') {
            $report_rows = mysqli_query($conn, "SELECT s.SALE_ID, p.NAME AS patient_name, u.NAME AS staff_name, s.SALE_DATE, s.TOTAL_AMOUNT, s.PAID_AMOUNT, s.PAYMENT_METHOD, s.PAYMENT_STATUS FROM SALES s LEFT JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID LEFT JOIN USER u ON s.STAFF_ID = u.USER_ID WHERE {$sales_date_sql} ORDER BY s.SALE_DATE DESC LIMIT 100");
            $heading = 'Sales Transactions';
            $columns = ['Invoice', 'Patient', 'Staff', 'Date', 'Total (RM)', 'Paid (RM)', 'Method', 'Status'];
            if($report_rows) {
                while($row = mysqli_fetch_assoc($report_rows)) {
                    $report_data[] = $row;
                }
            }
        } elseif($type === 'inventory') {
            $report_rows = mysqli_query($conn, "SELECT p.PRODUCT_ID, p.BRAND_NAME, p.CATEGORY, p.STOCK_QUANTITY, p.UNIT_PRICE, p.EXPIRY_DATE FROM PRODUCT p WHERE 1=1 {$inventory_category_sql} ORDER BY p.BRAND_NAME ASC LIMIT 100");
            $heading = 'Inventory Snapshot';
            $columns = ['Product', 'Category', 'Stock', 'Unit Price', 'Expiry'];
            if($report_rows) {
                while($row = mysqli_fetch_assoc($report_rows)) {
                    $report_data[] = $row;
                }
            }
        } elseif($type === 'patients') {
            $report_rows = mysqli_query($conn, "SELECT PATIENT_ID, NAME, IC_NUMBER, PHONE_NUMBER, EMAIL FROM PATIENT ORDER BY NAME ASC LIMIT 100");
            $heading = 'Patient Directory';
            $columns = ['Name', 'IC Number', 'Phone', 'Email'];
            if($report_rows) {
                while($row = mysqli_fetch_assoc($report_rows)) {
                    $report_data[] = $row;
                }
            }
        } else {
            $report_rows = mysqli_query($conn, "SELECT EXAM_ID, PATIENT_ID, OPTOMETRIST_ID, EXAM_DATE FROM EYE_EXAMINATION WHERE {$exam_date_sql} ORDER BY EXAM_DATE DESC LIMIT 100");
            $heading = 'Appointment & Exam History';
            $columns = ['Exam ID', 'Patient ID', 'Optometrist', 'Date'];
            if($report_rows) {
                while($row = mysqli_fetch_assoc($report_rows)) {
                    $report_data[] = $row;
                }
            }
        }

        ?>

        <section id="reportContent" class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Custom Output</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($heading); ?></h2>
                </div>
                <span class="text-sm text-slate-500"><?php echo number_format(count($report_data)); ?> matching rows</span>
            </div>

            <?php if(count($report_data) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <?php foreach($columns as $col): ?>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400"><?php echo $col; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($report_data as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <?php if($type === 'sales'): ?>
                                        <td class="px-6 py-5 font-semibold text-slate-900"><?php echo $row['SALE_ID']; ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['patient_name'] ?? 'Walk-in'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['staff_name'] ?? 'N/A'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo date('d M Y', strtotime($row['SALE_DATE'])); ?></td>
                                        <td class="px-6 py-5 text-slate-900">RM <?php echo number_format($row['TOTAL_AMOUNT'],2); ?></td>
                                        <td class="px-6 py-5 text-slate-900">RM <?php echo number_format($row['PAID_AMOUNT'],2); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['PAYMENT_METHOD']); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['PAYMENT_STATUS']); ?></td>
                                    <?php elseif($type === 'inventory'): ?>
                                        <td class="px-6 py-5 font-semibold text-slate-900"><?php echo htmlspecialchars($row['BRAND_NAME']); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['CATEGORY'] ?: 'Uncategorized'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo number_format($row['STOCK_QUANTITY']); ?></td>
                                        <td class="px-6 py-5 text-slate-900">RM <?php echo number_format($row['UNIT_PRICE'],2); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo $row['EXPIRY_DATE'] ? date('d M Y', strtotime($row['EXPIRY_DATE'])) : 'N/A'; ?></td>
                                    <?php elseif($type === 'patients'): ?>
                                        <td class="px-6 py-5 font-semibold text-slate-900"><?php echo htmlspecialchars($row['NAME']); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['IC_NUMBER'] ?: 'N/A'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['PHONE_NUMBER'] ?: 'N/A'); ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo htmlspecialchars($row['EMAIL'] ?: 'N/A'); ?></td>
                                    <?php else: ?>
                                        <td class="px-6 py-5 font-semibold text-slate-900"><?php echo $row['EXAM_ID']; ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo $row['PATIENT_ID']; ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo $row['OPTOMETRIST_ID']; ?></td>
                                        <td class="px-6 py-5 text-slate-700"><?php echo date('d M Y', strtotime($row['EXAM_DATE'])); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-500">No records matched the selected custom report parameters.</p>
            <?php endif; ?>
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
            setTimeout(() => { printWindow.focus(); printWindow.print(); /* do not auto-close to allow PDF save */ }, 300);
        }

        function exportPdfPrintable(e) {
            // If JS is enabled, open printable view and trigger print (user can choose Save as PDF)
            if (e) e.preventDefault();
            const reportEl = document.getElementById('reportContent');
            if (!reportEl) {
                // fallback to server-side PDF
                window.location.href = e.target.href || window.location.href;
                return;
            }
            const printWindow = window.open('', '', 'height=800,width=1000');
            const content = reportEl.outerHTML;
            printWindow.document.write(buildPrintableHTML(content));
            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); }, 300);
        }
    </script>
</body>
</html>
