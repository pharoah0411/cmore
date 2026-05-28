<?php
include('config.php');

// ==========================================
// ACCESS CONTROL
// ==========================================
// Kick out anyone who is not an Admin or Optometrist
if (!isset($_SESSION['ROLE']) || ($_SESSION['ROLE'] !== 'Admin' && $_SESSION['ROLE'] !== 'Optometrist')) {
    systemLog($conn, 'Attempted unauthorized access to Clinical Exams');
    header("Location: directory.php");
    exit();
}

// ==========================================
// FETCH ALL EXAMS & HANDLE SEARCH
// ==========================================
$search_query = "";
$where_clause = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where_clause = "WHERE p.NAME LIKE '%$search_query%' 
                     OR p.IC_NUMBER LIKE '%$search_query%' 
                     OR e.PRESCRIPTION_RESULT LIKE '%$search_query%'";
}

// Join eye_examination with patient and user to get real names instead of IDs
$query = "SELECT e.*, p.NAME as PATIENT_NAME, p.IC_NUMBER, u.NAME as OPTOMETRIST_NAME 
          FROM eye_examination e 
          LEFT JOIN patient p ON e.PATIENT_ID = p.PATIENT_ID 
          LEFT JOIN user u ON e.OPTOMETRIST_ID = u.USER_ID 
          $where_clause
          ORDER BY e.EXAM_DATE DESC";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Exams | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 h-screen overflow-y-auto p-10 custom-scrollbar">
        
        <div class="mb-10 flex flex-col md:flex-row md:justify-between md:items-end space-y-4 md:space-y-0">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">Clinical Exam Records</h1>
                <p class="text-slate-500 font-medium mt-1">View and manage all patient refractions and ocular health assessments.</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <form action="exam.php" method="GET" class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search patient, IC, diagnosis..." 
                           class="pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-sm shadow-sm w-64 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-[0.95rem] text-slate-400"></i>
                    <?php if(!empty($search_query)): ?>
                        <a href="exam.php" class="absolute right-3 top-[0.95rem] text-red-400 hover:text-red-600"><i class="fa-solid fa-circle-xmark"></i></a>
                    <?php endif; ?>
                </form>

                <a href="exam_add.php" class="px-6 py-3 bg-[#0097B2] text-white font-bold rounded-xl shadow-lg shadow-teal-100 hover:scale-105 transition flex items-center text-sm">
                    <i class="fa-solid fa-plus mr-2"></i> New Exam
                </a>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="p-5 font-black">Exam Date</th>
                            <th class="p-5 font-black">Patient Details</th>
                            <th class="p-5 font-black">Optometrist</th>
                            <th class="p-5 font-black">Diagnosis / Prescription</th>
                            <th class="p-5 font-black text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-slate-50/50 transition group">
                                    <td class="p-5 text-slate-600 font-bold whitespace-nowrap">
                                        <?php echo date('d M Y', strtotime($row['EXAM_DATE'])); ?>
                                    </td>
                                    <td class="p-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($row['PATIENT_NAME'] ?? 'Unknown Patient'); ?></p>
                                                <p class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($row['IC_NUMBER'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-5 text-slate-500 whitespace-nowrap">
                                        <i class="fa-solid fa-user-doctor text-[#0097B2] mr-2 opacity-50"></i>
                                        <?php echo htmlspecialchars($row['OPTOMETRIST_NAME'] ?? 'Unknown'); ?>
                                    </td>
                                    <td class="p-5 min-w-[300px]">
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="bg-slate-100 text-slate-600 py-1 px-2 rounded font-bold text-[10px] uppercase tracking-wider border border-slate-200">
                                                <?php echo htmlspecialchars($row['PRESCRIPTION_RESULT'] ?? 'N/A'); ?>
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400">VA: <?php echo htmlspecialchars($row['VISUAL_ACUITY_RESULTS'] ?? '-'); ?></span>
                                        </div>
                                        
                                        <div class="bg-white border border-slate-100 rounded-lg overflow-hidden">
                                            <table class="w-full text-center text-[10px] font-mono">
                                                <thead class="bg-slate-50 text-slate-400 font-bold uppercase">
                                                    <tr>
                                                        <th class="py-1 px-2 border-r border-slate-100 font-sans">Eye</th>
                                                        <th class="py-1 px-2 border-r border-slate-100">SPH</th>
                                                        <th class="py-1 px-2 border-r border-slate-100">CYL</th>
                                                        <th class="py-1 px-2">AXIS</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-slate-600">
                                                    <tr class="border-b border-slate-50">
                                                        <td class="py-1 px-2 border-r border-slate-50 font-bold text-[#0097B2] bg-[#0097B2]/5 font-sans">OD</td>
                                                        <td class="py-1 px-2 border-r border-slate-50"><?php echo htmlspecialchars($row['RE_SPH'] ?: '-'); ?></td>
                                                        <td class="py-1 px-2 border-r border-slate-50"><?php echo htmlspecialchars($row['RE_CYL'] ?: '-'); ?></td>
                                                        <td class="py-1 px-2"><?php echo htmlspecialchars($row['RE_AXIS'] ?: '-'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-1 px-2 border-r border-slate-50 font-bold text-[#B9D977] bg-[#B9D977]/10 font-sans">OS</td>
                                                        <td class="py-1 px-2 border-r border-slate-50"><?php echo htmlspecialchars($row['LE_SPH'] ?: '-'); ?></td>
                                                        <td class="py-1 px-2 border-r border-slate-50"><?php echo htmlspecialchars($row['LE_CYL'] ?: '-'); ?></td>
                                                        <td class="py-1 px-2"><?php echo htmlspecialchars($row['LE_AXIS'] ?: '-'); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                    <td class="p-5 text-right whitespace-nowrap align-top pt-8">
                                        <a href="exam_view.php?id=<?php echo $row['EXAM_ID']; ?>" class="px-3 py-2 bg-slate-50 text-[#0097B2] font-bold rounded-lg hover:bg-[#0097B2] hover:text-white transition text-xs border border-slate-200 hover:border-[#0097B2] inline-block">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-10 text-center text-slate-400 font-bold">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl text-slate-300">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <?php echo !empty($search_query) ? 'No records matched your search.' : 'No clinical exams found. Click "New Exam" to start.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #0097B2; }
    </style>
</body>
</html>