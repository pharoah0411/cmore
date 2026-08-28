<?php
include('config.php');

// ==========================================
// HANDLE FORM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $datetime = mysqli_real_escape_string($conn, $_POST['appointment_datetime']);
    
    // --- SERVER-SIDE BUSINESS HOURS VALIDATION ---
    $timestamp = strtotime($datetime);
    $day_of_week = date('w', $timestamp); // 0 = Sunday, 3 = Wednesday
    $hour = (int)date('H', $timestamp); // 24-hour format
    $minute = (int)date('i', $timestamp); // Get minutes

    if ($day_of_week == 3) {
        $error = "Error: The clinic is closed on Wednesdays.";
    } elseif ($hour < 11 || $hour >= 19 || ($hour == 18 && $minute > 30)) {
        // Must be between 11:00 AM and 6:30 PM exactly
        $error = "Error: Appointments must be scheduled between 11:00 AM and 6:30 PM.";
    } else {
        $conflict_sql = "SELECT APPOINTMENT_ID FROM appointment
                         WHERE STAFF_ID = '$staff_id'
                         AND APPOINTMENT_DATETIME = '$datetime'
                         AND (STATUS IS NULL OR STATUS NOT IN ('Cancelled'))
                         LIMIT 1";
        $conflict_result = mysqli_query($conn, $conflict_sql);

        if ($conflict_result && mysqli_num_rows($conflict_result) > 0) {
            $error = "This staff member already has an appointment at that date and time. Please choose another slot.";
        }

        $patient_id = '';

        // Check if the user is adding a NEW patient
        if (!isset($error) && isset($_POST['is_new_patient']) && $_POST['is_new_patient'] == '1') {
            $name = mysqli_real_escape_string($conn, $_POST['new_patient_name']);
            $phone = mysqli_real_escape_string($conn, $_POST['new_patient_phone']);
            
            $dummy_ic = 'NO-IC-' . time(); 
            $reg_date = date('Y-m-d');
            
            // Insert new patient
            $sql_patient = "INSERT INTO patient (NAME, PHONE_NUMBER, IC_NUMBER, REGISTRATION_DATE, ADDRESS, CONNECTION_RELATIONSHIP, FOLLOW_UP_INTERVAL, COMPLAINTS) 
                            VALUES ('$name', '$phone', '$dummy_ic', '$reg_date', 'Walk-in / Unrecorded', 'None', 'Not Set', 'None')";
            
            if (mysqli_query($conn, $sql_patient)) {
                $patient_id = mysqli_insert_id($conn); 
                systemLog($conn, "Added new walk-in patient: $name", 'patient', $patient_id);
            } else {
                $error = "Error adding new patient: " . mysqli_error($conn);
            }
        } elseif (!isset($error)) {
            // Existing patient
            $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        }

        // Insert the appointment
        if (!empty($patient_id) && !isset($error)) {
            $sql_appt = "INSERT INTO appointment (PATIENT_ID, STAFF_ID, APPOINTMENT_DATETIME, STATUS) 
                         VALUES ('$patient_id', '$staff_id', '$datetime', 'Pending')";
                         
            if (mysqli_query($conn, $sql_appt)) {
                $appt_id = mysqli_insert_id($conn);
                systemLog($conn, "Booked new appointment", 'appointment', $appt_id);
                header("Location: appointment.php?msg=added");
                exit();
            } else {
                $error = "Error booking appointment: " . mysqli_error($conn);
            }
        }
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
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <a href="appointment.php" class="text-slate-400 hover:text-[#0097B2] transition-colors text-sm font-bold flex items-center mb-4">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Schedule
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Book Appointment</h1>
            <p class="text-slate-500 font-medium mt-1">Schedule a new visit for an existing or walk-in patient.</p>
        </header>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold flex items-center">
                <i class="fa-solid fa-triangle-exclamation mr-3"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 max-w-3xl">
            <form method="POST" action="appointment_add.php">
                
                <div class="mb-8 p-6 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block font-extrabold text-slate-800 text-lg">Patient Details</label>
                        <button type="button" id="togglePatientBtn" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-[#0097B2] transition-colors shadow-md">
                            <i class="fa-solid fa-plus mr-1"></i> Add New Customer
                        </button>
                    </div>

                    <input type="hidden" name="is_new_patient" id="is_new_patient" value="0">

                    <div id="existingPatientDiv">
                        <select name="patient_id" id="patient_id" class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700" required>
                            <option value="">-- Select an Existing Patient --</option>
                            <?php
                            $p_res = mysqli_query($conn, "SELECT PATIENT_ID, NAME, PHONE_NUMBER FROM patient ORDER BY NAME ASC");
                            while($p = mysqli_fetch_assoc($p_res)){
                                $phone_display = empty($p['PHONE_NUMBER']) ? "No Phone" : htmlspecialchars($p['PHONE_NUMBER']);
                                echo "<option value='".$p['PATIENT_ID']."'>".htmlspecialchars($p['NAME'])." (" . $phone_display . ")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div id="newPatientDiv" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-600 mb-2">Full Name</label>
                            <input type="text" name="new_patient_name" id="new_patient_name" placeholder="Enter patient name" class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-600 mb-2">Phone Number</label>
                            <input type="tel" name="new_patient_phone" id="new_patient_phone" pattern="^01[0-9]-[0-9]{7,8}$" title="Format: 012-3456789" placeholder="012-3456789" maxlength="12" class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                        </div>
                        <div class="col-span-full mt-1">
                            <p class="text-xs text-slate-500"><i class="fa-solid fa-circle-info mr-1 text-[#0097B2]"></i> A temporary IC number will be assigned automatically.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-600 mb-2">Assigned Staff / Optometrist</label>
                    <select name="staff_id" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700" required>
                        <option value="">-- Select Staff --</option>
                        <?php
                        $u_res = mysqli_query($conn, "SELECT USER_ID, NAME, ROLE FROM user WHERE ROLE IN ('Optometrist', 'Staff') ORDER BY NAME ASC");
                        while($u = mysqli_fetch_assoc($u_res)){
                            echo "<option value='".$u['USER_ID']."'>".htmlspecialchars($u['NAME'])." (".htmlspecialchars($u['ROLE']).")</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-10">
                    <div class="flex justify-between items-end mb-2">
                        <label class="block text-sm font-bold text-slate-600">Appointment Date & Time</label>
                        <span class="text-xs font-bold text-[#0097B2] bg-teal-50 px-2 py-1 rounded-md">Open: 11 AM - 7 PM (Last Appt: 6:30 PM, Closed Wed)</span>
                    </div>
                    <input type="datetime-local" name="appointment_datetime" id="appointment_datetime" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                </div>

                <button type="submit" class="w-full bg-[#0097B2] text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-teal-600 transition-colors shadow-lg shadow-teal-100 flex justify-center items-center">
                    <i class="fa-solid fa-calendar-check mr-2"></i> Save Appointment
                </button>
            </form>
        </div>
    </main>

    <script>
        // --- 1. TOGGLE EXISTING VS NEW PATIENT ---
        document.getElementById('togglePatientBtn').addEventListener('click', function() {
            const existingDiv = document.getElementById('existingPatientDiv');
            const newDiv = document.getElementById('newPatientDiv');
            const isNewInput = document.getElementById('is_new_patient');
            const patientSelect = document.getElementById('patient_id');
            const newNameInput = document.getElementById('new_patient_name');
            const newPhoneInput = document.getElementById('new_patient_phone');

            if (newDiv.classList.contains('hidden')) {
                existingDiv.classList.add('hidden');
                newDiv.classList.remove('hidden');
                this.innerHTML = '<i class="fa-solid fa-xmark mr-1"></i> Cancel New';
                this.classList.replace('bg-slate-900', 'bg-red-500');
                this.classList.replace('hover:bg-[#0097B2]', 'hover:bg-red-600');
                isNewInput.value = '1';
                patientSelect.removeAttribute('required');
                newNameInput.setAttribute('required', 'required');
                newPhoneInput.setAttribute('required', 'required');
            } else {
                newDiv.classList.add('hidden');
                existingDiv.classList.remove('hidden');
                this.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> Add New Customer';
                this.classList.replace('bg-red-500', 'bg-slate-900');
                this.classList.replace('hover:bg-red-600', 'hover:bg-[#0097B2]');
                isNewInput.value = '0';
                newNameInput.removeAttribute('required');
                newPhoneInput.removeAttribute('required');
                patientSelect.setAttribute('required', 'required');
            }
        });

        // --- 2. FRONT-END BUSINESS HOURS VALIDATION ---
        const datetimeInput = document.getElementById('appointment_datetime');
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        datetimeInput.min = now.toISOString().slice(0,16);

        datetimeInput.addEventListener('change', function() {
            if(!this.value) return;
            const selectedDate = new Date(this.value);
            const day = selectedDate.getDay(); 
            const hour = selectedDate.getHours();
            const minute = selectedDate.getMinutes();

            if (day === 3) {
                alert("The clinic is closed on Wednesdays. Please select another day.");
                this.value = ''; 
            } else if (hour < 11 || hour >= 19 || (hour === 18 && minute > 30)) {
                alert("Appointments can only be scheduled between 11:00 AM and 6:30 PM.");
                this.value = ''; 
            }
        });

        // --- 3. AUTO-FORMAT PHONE NUMBER & RESTRICT LENGTH ---
        const phoneInput = document.getElementById('new_patient_phone');
        phoneInput.addEventListener('input', function (e) {
            // Strip out all non-numeric characters first
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,8})/);
            
            // Automatically insert the hyphen after the 3rd number (012-3456789)
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
        });
    </script>
</body>
</html>