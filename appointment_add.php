<?php 
include('config.php'); 

if(isset($_POST['book_appointment'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']); // FETCHING STAFF ID
    
    $date = mysqli_real_escape_string($conn, $_POST['appt_date']);
    $time = mysqli_real_escape_string($conn, $_POST['appt_time']);
    $datetime = $date . ' ' . $time . ':00';
    
    $status = 'Pending';

    // UPDATED SQL TO INCLUDE STAFF_ID
    $sql = "INSERT INTO APPOINTMENT (PATIENT_ID, STAFF_ID, APPOINTMENT_DATETIME, STATUS) 
            VALUES ('$patient_id', '$staff_id', '$datetime', '$status')";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: appointment.php?msg=booked");
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Book Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
        .select2-container .select2-selection--single {
            height: 56px !important; border: 1px solid #f1f5f9 !important; border-radius: 1rem !important; background-color: #f8fafc !important; display: flex; align-items: center; padding-left: 0.5rem; font-weight: 700;
        }
        .select2-container--default .select2-selection--single:focus { border-color: #0097B2 !important; outline: none !important; }
    </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <a href="appointment.php" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Schedule
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Book New Appointment</h1>
        </header>

        <form action="" method="POST" class="max-w-3xl bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Select Patient</label>
                    <select name="patient_id" required class="searchable-select w-full p-4 outline-none">
                        <option value="">-- Search Patient Name --</option>
                        <?php 
                        $p_res = mysqli_query($conn, "SELECT PATIENT_ID, NAME, PHONE_NUMBER FROM PATIENT ORDER BY NAME ASC");
                        while($p = mysqli_fetch_assoc($p_res)) {
                            echo "<option value='{$p['PATIENT_ID']}'>{$p['NAME']} ({$p['PHONE_NUMBER']})</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Attending Optometrist</label>
                    <select name="staff_id" required class="searchable-select w-full p-4 outline-none">
                        <option value="">-- Select Optometrist --</option>
                        <?php 
                        $u_res = mysqli_query($conn, "SELECT USER_ID, NAME FROM USER ORDER BY NAME ASC");
                        while($u = mysqli_fetch_assoc($u_res)) {
                            echo "<option value='{$u['USER_ID']}'>{$u['NAME']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 relative">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Date</label>
                    <input type="date" id="appt_date" name="appt_date" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    <p class="text-[9px] text-slate-400 mt-1 ml-1 font-semibold">* Closed on Wednesdays</p>
                </div>
                
                <div class="space-y-2 relative">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Time Slot</label>
                    <select id="appt_time" name="appt_time" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 appearance-none">
                        <option value="">-- Select Time --</option>
                        <?php
                        // Generates slots from 11:00 AM to 7:00 PM (19:00)
                        $start = strtotime('11:00');
                        $end = strtotime('19:00');
                        while ($start <= $end) {
                            $val = date('H:i', $start);
                            $lbl = date('h:i A', $start);
                            echo "<option value='{$val}'>{$lbl}</option>";
                            $start = strtotime('+30 minutes', $start);
                        }
                        ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-[3.2rem] text-slate-400 pointer-events-none"></i>
                    <p class="text-[9px] text-slate-400 mt-1 ml-1 font-semibold">* Available 11:00 AM - 7:00 PM</p>
                </div>
            </div>

            <div class="pt-4 flex justify-end items-center space-x-4 border-t border-slate-50">
                <a href="appointment.php" class="text-slate-400 font-bold px-6 py-4 hover:text-slate-600 transition">Cancel</a>
                <button type="submit" name="book_appointment" class="bg-[#0097B2] text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:bg-teal-600 transition-all flex items-center">
                    <i class="fa-solid fa-save mr-2"></i> Confirm Booking
                </button>
            </div>
        </form>
    </main>

    <script>
        $(document).ready(function() {
            $('.searchable-select').select2();
        });

        const dateInput = document.getElementById('appt_date');

        // Prevent selecting dates in the past
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        // Validate Day of the Week (Reject Wednesdays)
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const day = selectedDate.getDay(); 
            
            if (day === 3) {
                alert('⚠️ C More Optometry is closed on Wednesdays. Please select another day.');
                this.value = ''; 
            }
        });
    </script>
</body>
</html>