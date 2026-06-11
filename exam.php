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

// Ensure ALL patients show up by querying the 'patient' table first.
// Then LEFT JOIN their most recent eye exam and the optometrist info.
$query = "SELECT p.PATIENT_ID, p.NAME as PATIENT_NAME, p.IC_NUMBER, 
                 e.EXAM_ID, e.EXAM_DATE, e.PRESCRIPTION_RESULT, e.VISUAL_ACUITY_RESULTS,
                 e.RE_SPH, e.RE_CYL, e.RE_AXIS, e.LE_SPH, e.LE_CYL, e.LE_AXIS,
                 u.NAME as OPTOMETRIST_NAME 
          FROM patient p 
          LEFT JOIN (
              SELECT * FROM eye_examination WHERE EXAM_ID IN (
                  SELECT MAX(EXAM_ID) FROM eye_examination GROUP BY PATIENT_ID
              )
          ) e ON p.PATIENT_ID = e.PATIENT_ID 
          LEFT JOIN user u ON e.OPTOMETRIST_ID = u.USER_ID 
          $where_clause
          ORDER BY p.NAME ASC";
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
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $has_exam = !empty($row['EXAM_ID']);
                            ?>
                                <tr class="hover:bg-slate-50/50 transition group <?php echo !$has_exam ? 'bg-orange-50/20' : ''; ?>">
                                    
                                    <td class="p-5 whitespace-nowrap">
                                        <?php if($has_exam): ?>
                                            <span class="text-slate-600 font-bold"><?php echo date('d M Y', strtotime($row['EXAM_DATE'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300 font-bold italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-lg <?php echo $has_exam ? 'bg-slate-100 text-slate-500' : 'bg-orange-100 text-orange-400'; ?> flex items-center justify-center font-bold text-xs">
                                                <i class="fa-solid <?php echo $has_exam ? 'fa-user' : 'fa-clipboard-question'; ?>"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($row['PATIENT_NAME'] ?? 'Unknown Patient'); ?></p>
                                                <p class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($row['IC_NUMBER'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="p-5 whitespace-nowrap">
                                        <?php if($has_exam): ?>
                                            <span class="text-slate-500">
                                                <i class="fa-solid fa-user-doctor text-[#0097B2] mr-2 opacity-50"></i>
                                                <?php echo htmlspecialchars($row['OPTOMETRIST_NAME'] ?? 'Unknown'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300 italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-5 min-w-[300px]">
                                        <?php if($has_exam): ?>
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
                                        <?php else: ?>
                                            <div class="flex items-center">
                                                <span class="inline-block px-3 py-1.5 bg-orange-100 text-orange-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-orange-200">
                                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> No Record Yet
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-5 text-right whitespace-nowrap align-middle">
                                        <div class="flex flex-col space-y-2 items-end">
                                            <?php if($has_exam): ?>
                                                <a href="exam_view.php?id=<?php echo $row['EXAM_ID']; ?>" class="px-4 py-2 bg-slate-50 text-[#0097B2] font-bold rounded-lg hover:bg-[#0097B2] hover:text-white transition text-xs border border-slate-200 hover:border-[#0097B2] text-center w-28">
                                                    View Details
                                                </a>
                                            <?php endif; ?>
                                            <a href="exam_add.php?patient_id=<?php echo $row['PATIENT_ID']; ?>" class="px-4 py-2 <?php echo $has_exam ? 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-100' : 'bg-[#0097B2] text-white shadow-md hover:bg-teal-600 border border-transparent'; ?> font-bold rounded-lg transition text-xs text-center w-28">
                                                <i class="fa-solid fa-plus mr-1"></i> Add Exam
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-10 text-center text-slate-400 font-bold">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl text-slate-300">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <?php echo !empty($search_query) ? 'No records matched your search.' : 'No patients found in the system.'; ?>
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