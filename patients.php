<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Patient Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Large font for quick clinical reading */
        .prescription-chip { font-family: 'monospace'; font-size: 14px; font-weight: 800; }
    </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Patient Management</h1>
                <p class="text-slate-500 font-medium mt-1">Clinical overview and quick prescription access.</p>
            </div>
            <a href="patient_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all duration-300">
                <i class="fa-solid fa-user-plus mr-2"></i> Register New Patient
            </a>
        </header>

        <div class="mb-8">
            <form action="" method="GET" class="relative max-w-xl">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" placeholder="Search Name, IC, or Connection..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                       class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all">
            </form>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left table-fixed">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="w-[28%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Patient & IC</th>
                        <th class="w-[22%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact & Relation</th>
                        <th class="w-[35%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Latest Prescription (RE/LE)</th>
                        <th class="w-[15%] p-5 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    
                    $sql = "SELECT p.*, e.RE_SPH, e.RE_CYL, e.RE_AXIS, e.LE_SPH, e.LE_CYL, e.LE_AXIS 
                            FROM PATIENT p
                            LEFT JOIN (
                                SELECT * FROM EYE_EXAMINATION WHERE EXAM_ID IN (
                                    SELECT MAX(EXAM_ID) FROM EYE_EXAMINATION GROUP BY PATIENT_ID
                                )
                            ) e ON p.PATIENT_ID = e.PATIENT_ID";
                    
                    if (!empty($search)) {
                        $sql .= " WHERE p.NAME LIKE '%$search%' 
                                 OR p.IC_NUMBER LIKE '%$search%' 
                                 OR p.CONNECTION_RELATIONSHIP LIKE '%$search%'";
                    }
                    
                    $sql .= " ORDER BY p.NAME ASC";
                    $res = mysqli_query($conn, $sql);
                    
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)): 
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-5">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-[#0097B2] shrink-0">
                                    <i class="fa-solid fa-user-check text-lg"></i>
                                </div>
                                <div class="truncate">
                                    <p class="font-bold text-slate-800 text-md leading-tight truncate"><?php echo $row['NAME']; ?></p>
                                    <?php if(!empty($row['IC_NUMBER'])): ?>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mt-0.5"><?php echo $row['IC_NUMBER']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        
                        <td class="p-5">
                            <div class="space-y-0">
                                <p class="text-sm font-bold text-slate-700"><?php echo !empty($row['PHONE_NUMBER']) ? $row['PHONE_NUMBER'] : '<span class="text-slate-300 font-normal">No Phone</span>'; ?></p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase truncate">
                                    <i class="fa-solid fa-people-arrows mr-1 opacity-40"></i><?php echo !empty($row['CONNECTION_RELATIONSHIP']) ? $row['CONNECTION_RELATIONSHIP'] : 'None'; ?>
                                </p>
                            </div>
                        </td>

                        <td class="p-5 text-center">
                            <?php if(!empty($row['RE_SPH']) || !empty($row['LE_SPH'])): ?>
                                <div class="inline-block text-left bg-slate-50/50 px-4 py-2 rounded-2xl border border-slate-100">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-[9px] font-black text-[#0097B2] w-4">RE</span>
                                        <span class="prescription-chip text-slate-800">
                                            <?php echo "{$row['RE_SPH']} / {$row['RE_CYL']} x {$row['RE_AXIS']}°"; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-[9px] font-black text-slate-400 w-4">LE</span>
                                        <span class="prescription-chip text-slate-800">
                                            <?php echo "{$row['LE_SPH']} / {$row['LE_CYL']} x {$row['LE_AXIS']}°"; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-[11px] text-slate-300 italic">No record</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-5 text-center">
                            <div class="flex items-center justify-center space-x-1.5">
                                <a href="patient_details.php?id=<?php echo $row['PATIENT_ID']; ?>" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                                <a href="patient_edit.php?id=<?php echo $row['PATIENT_ID']; ?>" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="p-20 text-center italic text-slate-400">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>