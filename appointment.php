<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Appointments</title>
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
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Appointment Schedule</h1>
                <p class="text-slate-500 font-medium mt-1">Manage daily bookings and patient arrivals.</p>
            </div>
            <button class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                <i class="fa-solid fa-calendar-plus mr-2"></i> Book Appointment
            </button>
        </header>

        <div class="grid grid-cols-1 gap-6">
            <?php
            $sql = "SELECT A.*, P.NAME as PATIENT_NAME 
                    FROM APPOINTMENT A 
                    JOIN PATIENT P ON A.PATIENT_ID = P.PATIENT_ID 
                    ORDER BY A.APPOINTMENT_DATETIME ASC";
            $res = mysqli_query($conn, $sql);
            
            while($row = mysqli_fetch_assoc($res)):
                $is_completed = ($row['STATUS'] == 'Completed');
                $status_class = $is_completed 
                    ? 'bg-slate-100 text-slate-500' 
                    : 'bg-[#B9D977]/20 text-[#6d8a2a]';
            ?>
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between group hover:border-[#0097B2]/30 transition-all duration-300">
                <div class="flex items-center space-x-8">
                    <div class="text-center bg-slate-50 p-4 rounded-[2rem] min-w-[100px] border border-slate-100 group-hover:bg-[#0097B2] group-hover:text-white transition-colors">
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-60"><?php echo date('M', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                        <p class="text-3xl font-black"><?php echo date('d', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-xl text-slate-800"><?php echo $row['PATIENT_NAME']; ?></h4>
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-sm font-medium text-slate-400">
                                <i class="fa-regular fa-clock mr-2 text-[#0097B2]"></i><?php echo date('h:i A', strtotime($row['APPOINTMENT_DATETIME'])); ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?php echo $status_class; ?>">
                                <?php echo $row['STATUS']; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <?php if(!$is_completed): ?>
                        <button class="bg-slate-900 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#0097B2] transition-colors shadow-lg shadow-slate-200">
                            Check-In
                        </button>
                    <?php endif; ?>
                    <button class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-all">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>