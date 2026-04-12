<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Appointment Schedule</h1>
        
        <div class="grid grid-cols-1 gap-4">
            <?php
            // SQL JOIN: Fetch Appointment info along with Patient Name
            $sql = "SELECT A.*, P.NAME as PATIENT_NAME 
                    FROM APPOINTMENT A 
                    JOIN PATIENT P ON A.PATIENT_ID = P.PATIENT_ID 
                    ORDER BY A.APPOINTMENT_DATETIME ASC";
            $res = mysqli_query($conn, $sql);
            
            while($row = mysqli_fetch_assoc($res)):
                $status_color = ($row['STATUS'] == 'Completed') ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700';
            ?>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 flex items-center justify-between shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-6">
                    <div class="text-center bg-slate-100 p-3 rounded-xl min-w-[80px]">
                        <p class="text-xs uppercase font-bold text-slate-400"><?php echo date('M', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                        <p class="text-2xl font-black text-slate-800"><?php echo date('d', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-slate-800"><?php echo $row['PATIENT_NAME']; ?></h4>
                        <p class="text-slate-500"><i class="fa-regular fa-clock mr-2 text-blue-500"></i><?php echo date('h:i A', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-4 py-1 rounded-full text-xs font-bold <?php echo $status_color; ?>">
                        <?php echo $row['STATUS']; ?>
                    </span>
                    <button class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-slate-700">Check-In</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>