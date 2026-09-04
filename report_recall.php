<?php include('config.php'); ?>
<?php include_once('export_helpers.php'); ?>
<?php include_once('recall_helpers.php'); ?>
<?php
$export = $_GET['export'] ?? '';
$segment = $_GET['segment'] ?? 'all';

function recall_segment($patient) {
    $days_overdue = max(0, (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($patient['recall_date']))->days);
    if (empty($patient['PHONE_NUMBER'])) return 'no_phone';
    if ($days_overdue >= 90) return 'priority';
    return 'recent';
}

function filter_recall_segment($rows, $segment) {
    if (!in_array($segment, ['all', 'priority', 'recent', 'no_phone'], true)) $segment = 'all';
    if ($segment === 'all') return $rows;
    return array_values(array_filter($rows, function ($patient) use ($segment) {
        return recall_segment($patient) === $segment;
    }));
}

$all_recall_rows = get_due_recall_rows($conn);
if ($export === 'pdf' || $export === 'excel') {
    $export_headers = ['Patient', 'Phone', 'Follow-up Interval', 'Last Exam', 'Recall Due'];
    $export_rows = [];
    foreach (filter_recall_segment($all_recall_rows, $segment) as $patient) {
        $export_rows[] = [
            $patient['NAME'],
            $patient['PHONE_NUMBER'] ?: 'No phone',
            $patient['FOLLOW_UP_INTERVAL'],
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
                <a href="?segment=<?php echo urlencode($segment); ?>&export=pdf" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#0097B2] px-6 py-3 text-white font-bold shadow-sm hover:bg-teal-600 transition">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
                <a href="?segment=<?php echo urlencode($segment); ?>&export=excel" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-[#B9D977] px-6 py-3 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <a href="reports.php" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-slate-700 font-bold shadow-sm hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </header>

        <div id="reportContent">
            <?php
            $recall_list = array_slice(filter_recall_segment($all_recall_rows, $segment), 0, 80);
            $recall_count = count($recall_list);
            $priority_count = count(filter_recall_segment($all_recall_rows, 'priority'));
            $recent_count = count(filter_recall_segment($all_recall_rows, 'recent'));
            $no_phone_count = count(filter_recall_segment($all_recall_rows, 'no_phone'));
            $contactable_count = $priority_count + $recent_count;
            $segment_label = ['all' => 'All Due Patients', 'priority' => 'Priority Outreach', 'recent' => 'Recently Due', 'no_phone' => 'Missing Phone Numbers'][$segment] ?? 'All Due Patients';
            ?>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd]">Patient Recall List</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($segment_label); ?></h2>
                </div>
                <span class="text-sm text-slate-500"><?php echo number_format($recall_count); ?> patients</span>
            </div>

            <div class="grid gap-6 xl:grid-cols-4 mb-10">
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd] mb-3">Due for Recall</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($recall_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Personalised follow-up dates that have arrived.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-600 mb-3">Priority Outreach</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($priority_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Due for 90 or more days with a phone number.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-green-600 mb-3">Contactable Audience</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($contactable_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Due patients with a recorded phone number.</p>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-red-500 mb-3">Data To Fix</p>
                    <p class="text-4xl font-black text-slate-900"><?php echo number_format($no_phone_count); ?></p>
                    <p class="text-sm text-slate-500 mt-3">Due patients without a WhatsApp-ready number.</p>
                </div>
            </div>

            <section class="mb-10 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-[2rem] bg-slate-900 p-8 text-white shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#B9D977]">Suggested Campaign</p>
                            <h2 class="mt-3 text-2xl font-black">Bring patients back with a personal check-in</h2>
                        </div>
                        <i class="fa-solid fa-bullseye text-2xl text-[#B9D977]"></i>
                    </div>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300">Start with the priority audience, then follow up with recently due patients. Use WhatsApp for direct outreach and update missing phone numbers during the next visit.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="?segment=priority" class="inline-flex items-center gap-2 rounded-xl bg-[#B9D977] px-4 py-3 text-xs font-black text-slate-900 hover:bg-white transition"><i class="fa-solid fa-bolt"></i> View Priority Patients</a>
                        <a href="whatsapp_messages.php" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-3 text-xs font-black text-white hover:bg-white/10 transition"><i class="fa-brands fa-whatsapp"></i> Open WhatsApp Desk</a>
                    </div>
                </div>
                <div class="rounded-[2rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Audience Segments</p>
                    <div class="mt-5 space-y-3 text-sm font-bold">
                        <a href="?segment=all" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-slate-700 hover:bg-slate-100"><span>All due patients</span><span><?php echo count($all_recall_rows); ?></span></a>
                        <a href="?segment=priority" class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3 text-amber-800 hover:bg-amber-100"><span>Priority, 90+ days</span><span><?php echo $priority_count; ?></span></a>
                        <a href="?segment=recent" class="flex items-center justify-between rounded-xl bg-green-50 px-4 py-3 text-green-800 hover:bg-green-100"><span>Recently due</span><span><?php echo $recent_count; ?></span></a>
                        <a href="?segment=no_phone" class="flex items-center justify-between rounded-xl bg-red-50 px-4 py-3 text-red-800 hover:bg-red-100"><span>Missing phone data</span><span><?php echo $no_phone_count; ?></span></a>
                    </div>
                </div>
            </section>

            <section class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#9d7edd]">Recall List</p>
                        <h2 class="text-2xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($segment_label); ?></h2>
                    </div>
                    <span class="text-sm text-slate-500"><?php echo number_format($recall_count); ?> patients</span>
                </div>

                <?php if(count($recall_list) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Patient</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Phone</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Interval</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Last Exam</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Recall Due</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($recall_list as $patient): ?>
                                    <?php $dueDate = date('d M Y', strtotime($patient['recall_date'])); ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5 font-semibold text-slate-800"><?php echo htmlspecialchars($patient['NAME']); ?></td>
                                        <td class="px-6 py-5 text-slate-500"><?php echo htmlspecialchars($patient['PHONE_NUMBER'] ?: 'No phone'); ?></td>
                                        <td class="px-6 py-5 text-slate-500"><?php echo htmlspecialchars($patient['FOLLOW_UP_INTERVAL']); ?></td>
                                        <td class="px-6 py-5 text-slate-900"><?php echo date('d M Y', strtotime($patient['last_exam'])); ?></td>
                                        <td class="px-6 py-5 text-slate-900 font-bold"><?php echo $dueDate; ?></td>
                                        <td class="px-6 py-5">
                                            <?php if(!empty($patient['PHONE_NUMBER'])): ?>
                                                <?php $phone = preg_replace('/[^0-9]/', '', $patient['PHONE_NUMBER']); $phone = strpos($phone, '0') === 0 ? '6' . $phone : (strpos($phone, '60') === 0 ? $phone : '60' . $phone); ?>
                                                <?php $message = "Hello " . $patient['NAME'] . ", this is C-More Optical. Your eye examination follow-up is due. Please reply to arrange a convenient appointment."; ?>
                                                <a target="_blank" href="https://wa.me/<?php echo $phone; ?>?text=<?php echo urlencode($message); ?>" class="inline-flex items-center gap-2 rounded-xl bg-green-500 px-3 py-2 text-xs font-bold text-white hover:bg-green-600 transition"><i class="fa-brands fa-whatsapp"></i> Remind</a>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">No phone</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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