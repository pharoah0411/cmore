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
                <p class="text-slate-500 font-medium mt-1">View, update, and track purchase history for all registered clients.</p>
            </div>
            <button class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all duration-300">
                <i class="fa-solid fa-user-plus mr-2"></i> Register New Patient
            </button>
        </header>

        <div class="mb-8 bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex items-center">
            <div class="flex-1 flex items-center px-4">
                <i class="fa-solid fa-magnifying-glass text-slate-400 mr-4"></i>
                <input type="text" placeholder="Search by name, IC, or phone number..." class="w-full bg-transparent border-none focus:outline-none text-sm font-medium text-slate-600">
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Registered Patient</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">IC Number</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Contact Details</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    // Fetch all patients from your database
                    $sql = "SELECT * FROM PATIENT ORDER BY NAME ASC";
                    $res = mysqli_query($conn, $sql);
                    
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)): 
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-teal-50 flex items-center justify-center text-[#0097B2] shadow-sm border border-teal-100/50 group-hover:scale-110 transition duration-300">
                                    <i class="fa-solid fa-user-check text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-lg leading-tight"><?php echo $row['NAME']; ?></p>
                                    <p class="text-[10px] font-black text-[#B9D977] uppercase tracking-tighter mt-1">Verified Client</p>
                                </div>
                            </div>
                        </td>

                        <td class="p-6 font-mono text-sm text-slate-500">
                            <?php echo $row['IC_NUMBER']; ?>
                        </td>

                        <td class="p-6">
                            <p class="text-slate-700 font-bold"><?php echo $row['PHONE_NUMBER']; ?></p>
                            <p class="text-xs text-slate-400 truncate max-w-[180px] font-medium"><?php echo $row['ADDRESS']; ?></p>
                        </td>

                        <td class="p-6 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="patient_details.php?id=<?php echo $row['PATIENT_ID']; ?>" title="View Details" 
                                   class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition duration-300 shadow-sm shadow-blue-100">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </a>

                                <a href="edit_patient.php?id=<?php echo $row['PATIENT_ID']; ?>" title="Edit Patient" 
                                   class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300 shadow-sm">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>

                                <a href="sales_history.php?patient_id=<?php echo $row['PATIENT_ID']; ?>" title="Sales History" 
                                   class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center hover:bg-[#B9D977] hover:text-[#0f172a] transition duration-300 shadow-sm shadow-green-100">
                                    <i class="fa-solid fa-receipt text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="p-20 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-users-slash text-5xl text-slate-100 mb-4"></i>
                                <p class="text-slate-400 font-bold italic">No patient records found in your database.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex items-center justify-between text-[10px] text-slate-400 font-black uppercase tracking-[0.2em]">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-[#B9D977] animate-pulse"></span>
                <span>Real-time Data Synchronized</span>
            </div>
            <span>Records Table: PATIENT</span>
        </div>
    </main>

</body>
</html>