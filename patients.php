<?php 
include('config.php'); 

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Delete from dependent tables first
    $delete_exams = "DELETE FROM eye_examination WHERE PATIENT_ID = '$delete_id'";
    $delete_appointments = "DELETE FROM appointment WHERE PATIENT_ID = '$delete_id'";
    $delete_sales = "DELETE FROM sales WHERE PATIENT_ID = '$delete_id'";
    
    mysqli_query($conn, $delete_exams);
    mysqli_query($conn, $delete_appointments);
    mysqli_query($conn, $delete_sales);
    
    // Finally delete the patient
    $delete_patient = "DELETE FROM patient WHERE PATIENT_ID = '$delete_id'";
    
    if (mysqli_query($conn, $delete_patient)) {
        header("Location: patients.php?msg=deleted");
        exit();
    } else {
        header("Location: patients.php?msg=delete_error");
        exit();
    }
}

// Function to extract Age and DOB from Malaysian IC
function parse_malaysian_ic($ic) {
    if (empty($ic)) return ['age' => 'N/A', 'dob' => 'N/A'];
    
    // Remove any dashes or non-numeric characters
    $clean_ic = preg_replace('/[^0-9]/', '', $ic);
    if (strlen($clean_ic) != 12) return ['age' => 'N/A', 'dob' => 'N/A'];
    
    $yy = substr($clean_ic, 0, 2);
    $mm = substr($clean_ic, 2, 2);
    $dd = substr($clean_ic, 4, 2);
    
    // Determine the century (Assuming current year is 2026, anything > 26 is 19XX)
    $current_yy = (int)date('y');
    $year = ((int)$yy > $current_yy) ? "19$yy" : "20$yy";
    
    // Check if the extracted date is valid
    if (checkdate((int)$mm, (int)$dd, (int)$year)) {
        $dob_date = new DateTime("$year-$mm-$dd");
        $now = new DateTime();
        $age = $now->diff($dob_date)->y;
        
        return [
            'age' => $age . ' Yrs',
            'dob' => $dob_date->format('d M Y')
        ];
    }
    
    return ['age' => 'N/A', 'dob' => 'N/A'];
}
?>
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
                <?php 
                if (isset($_GET['msg'])) {
                    if ($_GET['msg'] == 'deleted') {
                        echo '<div class="mt-3 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm flex items-center"><i class="fa-solid fa-check-circle mr-2"></i>Patient deleted successfully.</div>';
                    } elseif ($_GET['msg'] == 'delete_error') {
                        echo '<div class="mt-3 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm flex items-center"><i class="fa-solid fa-exclamation-circle mr-2"></i>Error deleting patient. Please try again.</div>';
                    }
                }
                ?>
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
                        <th class="w-[24%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Patient & IC</th>
                        <th class="w-[16%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Age & DOB</th>
                        <th class="w-[20%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact & Relation</th>
                        <th class="w-[30%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Latest Prescription (RE/LE)</th>
                        <th class="w-[10%] p-5 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    
                    $sql = "SELECT p.*, e.RE_SPH, e.RE_CYL, e.RE_AXIS, e.LE_SPH, e.LE_CYL, e.LE_AXIS 
                            FROM patient p
                            LEFT JOIN (
                                SELECT * FROM eye_examination WHERE EXAM_ID IN (
                                    SELECT MAX(EXAM_ID) FROM eye_examination GROUP BY PATIENT_ID
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
                            // Extract Age and DOB for each row
                            $ic_info = parse_malaysian_ic($row['IC_NUMBER']);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" onclick="window.location.href='patient_details.php?id=<?php echo $row['PATIENT_ID']; ?>'">
                        <td class="p-5">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-[#0097B2] shrink-0">
                                    <i class="fa-solid fa-user-check text-lg"></i>
                                </div>
                                <div class="truncate">
                                    <p class="font-bold text-slate-800 text-md leading-tight truncate"><?php echo htmlspecialchars($row['NAME']); ?></p>
                                    <?php if(!empty($row['IC_NUMBER'])): ?>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mt-0.5"><?php echo htmlspecialchars($row['IC_NUMBER']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        
                        <td class="p-5 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-sm font-bold <?php echo ($ic_info['age'] == 'N/A') ? 'text-slate-300' : 'text-slate-700'; ?>">
                                    <?php echo $ic_info['age']; ?>
                                </span>
                                <?php if($ic_info['dob'] != 'N/A'): ?>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mt-0.5">
                                        <?php echo $ic_info['dob']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="p-5">
                            <div class="space-y-0">
                                <p class="text-sm font-bold text-slate-700"><?php echo !empty($row['PHONE_NUMBER']) ? htmlspecialchars($row['PHONE_NUMBER']) : '<span class="text-slate-300 font-normal">No Phone</span>'; ?></p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase truncate">
                                    <i class="fa-solid fa-people-arrows mr-1 opacity-40"></i><?php echo !empty($row['CONNECTION_RELATIONSHIP']) ? htmlspecialchars($row['CONNECTION_RELATIONSHIP']) : 'None'; ?>
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
                                <a href="patient_details.php?id=<?php echo $row['PATIENT_ID']; ?>" onclick="event.stopPropagation();" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm" title="View details">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                                <a href="patient_edit.php?id=<?php echo $row['PATIENT_ID']; ?>" onclick="event.stopPropagation();" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition" title="Edit patient">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </a>
                                <button onclick="event.stopPropagation(); deletePatient('<?php echo $row['PATIENT_ID']; ?>', '<?php echo htmlspecialchars($row['NAME']); ?>')" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition" title="Delete patient">
                                    <i class="fa-solid fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="p-20 text-center italic text-slate-400">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        function deletePatient(patientId, patientName) {
            if (confirm(`Are you sure you want to delete patient "${patientName}"?\n\nThis will also delete all associated records (exams, appointments, sales history).\n\nThis action cannot be undone.`)) {
                window.location.href = `patients.php?delete_id=${patientId}`;
            }
        }
    </script>
</body>
</html>