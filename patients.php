<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Patient Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Patient Management</h1>
                <p class="text-slate-500 font-medium mt-1">View and manage clinical records and follow-up schedules.</p>
            </div>
            <a href="patient_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all duration-300">
                <i class="fa-solid fa-user-plus mr-2"></i> Register New Patient
            </a>
        </header>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Patient</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Phone Number</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-center">Next Appt.</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $sql = "SELECT * FROM PATIENT ORDER BY NAME ASC";
                    $res = mysqli_query($conn, $sql);
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)): 
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-teal-50 flex items-center justify-center text-[#0097B2]">
                                    <i class="fa-solid fa-user-check text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-lg leading-tight"><?php echo $row['NAME']; ?></p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mt-1"><?php echo $row['IC_NUMBER']; ?></p>
                                </div>
                            </div>
                        </td>
                        
                        <td class="p-6">
                            <p class="text-sm font-bold text-slate-700"><?php echo !empty($row['PHONE_NUMBER']) ? $row['PHONE_NUMBER'] : '<span class="text-slate-300 font-normal">N/A</span>'; ?></p>
                        </td>

                        <td class="p-6 text-center">
                            <span class="px-3 py-1 bg-[#B9D977]/20 text-[#6d8a2a] rounded-full text-[10px] font-black uppercase">
                                <?php echo !empty($row['FOLLOW_UP_INTERVAL']) ? $row['FOLLOW_UP_INTERVAL'] : 'Not Set'; ?>
                            </span>
                        </td>
                        <td class="p-6 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="patient_details.php?id=<?php echo $row['PATIENT_ID']; ?>" title="View Full Profile" class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </a>
                                <a href="patient_edit.php?id=<?php echo $row['PATIENT_ID']; ?>" title="Edit Patient" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="p-20 text-center italic text-slate-400">No patient records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>