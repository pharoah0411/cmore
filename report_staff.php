<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Staff', 'Exams', 'Sales', 'Revenue (RM)'];
    $export_rows = [];
    $staff_export = mysqli_query($conn, "SELECT u.NAME, COALESCE(x.exam_count,0) AS exam_count, COALESCE(y.sales_count,0) AS sales_count, COALESCE(y.revenue,0) AS revenue FROM USER u LEFT JOIN (SELECT OPTOMETRIST_ID, COUNT(*) AS exam_count FROM EYE_EXAMINATION GROUP BY OPTOMETRIST_ID) x ON x.OPTOMETRIST_ID = u.USER_ID LEFT JOIN (SELECT STAFF_ID, COUNT(*) AS sales_count, SUM(TOTAL_AMOUNT) AS revenue FROM SALES GROUP BY STAFF_ID) y ON y.STAFF_ID = u.USER_ID ORDER BY revenue DESC, exam_count DESC");
    while ($staff = mysqli_fetch_assoc($staff_export)) {
        $export_rows[] = [
            $staff['NAME'],
            number_format($staff['exam_count']),
            number_format($staff['sales_count']),
            number_format($staff['revenue'],2)
        ];
    }
    if ($export === 'pdf') {
        export_pdf('staff-performance', 'Staff Performance', $export_headers, $export_rows);
    } else {
        export_excel('staff-performance', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Staff Performance</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Staff Performance</h1>
                <p class="text-slate-500 font-medium mt-2">Evaluate optometrist productivity, appointment handling, and sales impact.</p>
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
        $staff_stats = mysqli_query($conn, "SELECT u.USER_ID, u.NAME, COALESCE(x.exam_count,0) AS exam_count, COALESCE(y.sales_count,0) AS sales_count, COALESCE(y.revenue,0) AS revenue FROM USER u LEFT JOIN (SELECT OPTOMETRIST_ID, COUNT(*) AS exam_count FROM EYE_EXAMINATION GROUP BY OPTOMETRIST_ID) x ON x.OPTOMETRIST_ID = u.USER_ID LEFT JOIN (SELECT STAFF_ID, COUNT(*) AS sales_count, SUM(TOTAL_AMOUNT) AS revenue FROM SALES GROUP BY STAFF_ID) y ON y.STAFF_ID = u.USER_ID ORDER BY revenue DESC, exam_count DESC");

        ?>

        <div class="grid gap-6 xl:grid-cols-3 mb-10">
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#f472b6] mb-3">Optometrists</p>
                <p class="text-4xl font-black text-slate-900"><?php echo mysqli_num_rows($staff_stats); ?></p>
                <p class="text-sm text-slate-500 mt-3">Active staff members with assigned sales or exams.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <?php $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(TOTAL_AMOUNT),0) AS total FROM SALES")); ?>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Total Revenue</p>
                <p class="text-4xl font-black text-slate-900">RM <?php echo number_format($total_revenue['total'],2); ?></p>
                <p class="text-sm text-slate-500 mt-3">Revenue generated by clinical staff.</p>
            </div>
            <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <?php $appointment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM EYE_EXAMINATION")); ?>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Total Exams</p>
                <p class="text-4xl font-black text-slate-900"><?php echo number_format($appointment_count['total']); ?></p>
                <p class="text-sm text-slate-500 mt-3">Clinical consultations logged in the system.</p>
            </div>
        </div>

        <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#f472b6]">Performance Table</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">Staff Productivity</h2>
                </div>
                <span class="text-sm text-slate-500">Ranked by revenue</span>
            </div>

            <?php if($staff_stats && mysqli_num_rows($staff_stats) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Staff</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Exams</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Sales</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php while($staff = mysqli_fetch_assoc($staff_stats)): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-5 font-semibold text-slate-900"><?php echo htmlspecialchars($staff['NAME']); ?></td>
                                    <td class="px-6 py-5 text-slate-700"><?php echo number_format($staff['exam_count']); ?></td>
                                    <td class="px-6 py-5 text-slate-700"><?php echo number_format($staff['sales_count']); ?></td>
                                    <td class="px-6 py-5 font-bold text-slate-900">RM <?php echo number_format($staff['revenue'],2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-500">No staff performance data is currently available.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
