<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Patient', 'Phone', 'Last Exam', 'Recall Due'];
    $export_rows = [];
    $recall_export = mysqli_query($conn, "SELECT p.NAME, p.PHONE_NUMBER, MAX(e.EXAM_DATE) AS last_exam, DATE_ADD(MAX(e.EXAM_DATE), INTERVAL 180 DAY) AS recall_date FROM PATIENT p JOIN EYE_EXAMINATION e ON p.PATIENT_ID = e.PATIENT_ID GROUP BY p.PATIENT_ID, p.NAME, p.PHONE_NUMBER HAVING MAX(e.EXAM_DATE) <= DATE_SUB(CURDATE(), INTERVAL 180 DAY) ORDER BY recall_date ASC, p.NAME ASC");
    while ($patient = mysqli_fetch_assoc($recall_export)) {
        $export_rows[] = [
            $patient['NAME'],
            $patient['PHONE_NUMBER'] ?: 'No phone',
            date('d M Y', strtotime($patient['last_exam'])),
            date('d M Y', strtotime($patient['recall_date']))
        ];
    }
    if ($export === 'pdf') {
        export_pdf('patient-recall-list', 'Patient Recall List', $export_headers, $export_rows);
    } else {
        export_excel('patient-recall-list', $export_headers, $export_rows);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Patient Recall List</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Patient Recall List</h1>
                <p class="text-slate-500 font-medium mt-2">Find patients due for their next follow-up exam and accelerate outreach.</p>
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

        <div id="reportContent">
            <?php
            $recall_list = mysqli_query($conn, "SELECT p.PATIENT_ID, p.NAME, p.PHONE_NUMBER, MAX(e.EXAM_DATE) AS last_exam, DATE_ADD(MAX(e.EXAM_DATE), INTERVAL 180 DAY) AS recall_date FROM PATIENT p JOIN EYE_EXAMINATION e ON p.PATIENT_ID = e.PATIENT_ID GROUP BY p.PATIENT_ID, p.NAME, p.PHONE_NUMBER HAVING MAX(e.EXAM_DATE) <= DATE_SUB(CURDATE(), INTERVAL 180 DAY) ORDER BY recall_date ASC, p.NAME ASC LIMIT 80");
            $recall_count = $recall_list ? mysqli_num_rows($recall_list) : 0;
            ?>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd]">Patient Recall List</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">Patients to Contact</h2>
                </div>
                <span class="text-sm text-slate-500"><?php echo number_format($recall_count); ?> patients</span>
            </div>

            <div class="grid gap-6 xl:grid-cols-3 mb-10">
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd] mb-3">Due for Recall</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($recall_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Patients with last exam older than six months.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Follow-up Window</p>
                    <p class="text-4xl font-black text-slate-900">6 Months</p>
                    <p class="text-sm text-slate-500 mt-3">Automatic review based on the last exam date.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-3">Action</p>
                    <p class="text-4xl font-black text-slate-900">Contact List</p>
                    <p class="text-sm text-slate-500 mt-3">Export this list to reach patients quickly.</p>
                </div>
            </div>

            <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd]">Recall List</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2">Patients to Contact</h2>
                    </div>
                    <span class="text-sm text-slate-500"><?php echo number_format($recall_count); ?> patients</span>
                </div>

                <?php if($recall_list && mysqli_num_rows($recall_list) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Patient</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Phone</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Last Exam</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Recall Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php while($patient = mysqli_fetch_assoc($recall_list)): ?>
                                    <?php $dueDate = date('d M Y', strtotime($patient['recall_date'])); ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5 font-semibold text-slate-800"><?php echo htmlspecialchars($patient['NAME']); ?></td>
                                        <td class="px-6 py-5 text-slate-500"><?php echo htmlspecialchars($patient['PHONE_NUMBER'] ?: 'No phone'); ?></td>
                                        <td class="px-6 py-5 text-slate-900"><?php echo date('d M Y', strtotime($patient['last_exam'])); ?></td>
                                        <td class="px-6 py-5 text-slate-900 font-bold"><?php echo $dueDate; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500">No patients are currently due for recall.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <?php include('print_helper.php'); ?>
</body>
</html>