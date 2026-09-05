<?php 
include('config.php'); 

$id = (int)($_GET['id'] ?? $_POST['appointment_id'] ?? 0);

if(empty($id)) {
    header("Location: appointment.php");
    exit();
}

$error = '';

// ==========================================
// HANDLE FORM SUBMISSION
// ==========================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $datetime_input = trim($_POST['appointment_datetime'] ?? '');
    $status_input = trim($_POST['status'] ?? '');
    $datetime_value = DateTime::createFromFormat('!Y-m-d\TH:i', $datetime_input);

    if ($patient_id <= 0 || $staff_id <= 0 || !$datetime_value || !in_array($status_input, ['Pending', 'Checked-In', 'Completed', 'Cancelled'], true)) {
        $error = "Please provide valid appointment details.";
    }

    $datetime = mysqli_real_escape_string($conn, $datetime_value ? $datetime_value->format('Y-m-d H:i:00') : '');
    $status = mysqli_real_escape_string($conn, $status_input);

    // --- SERVER-SIDE BUSINESS HOURS VALIDATION ---
    $timestamp = $datetime_value ? $datetime_value->getTimestamp() : false;
    $day_of_week = $timestamp !== false ? date('w', $timestamp) : null; // 0 = Sunday, 3 = Wednesday
    $hour = $timestamp !== false ? (int)date('H', $timestamp) : null; // 24-hour format

    if (!empty($error)) {
        // Validation message is already set above.
    } elseif ($day_of_week == 3) {
        $error = "Error: The clinic is closed on Wednesdays.";
    } elseif ($hour < 11 || $hour >= 19) {
        // Must be exactly 11:00 AM up to 6:59 PM
        $error = "Error: Appointments must be scheduled between 11:00 AM and 7:00 PM.";
    } else {
        $conflict_sql = "SELECT APPOINTMENT_ID FROM appointment
                         WHERE STAFF_ID = '$staff_id'
                         AND APPOINTMENT_DATETIME = '$datetime'
                         AND APPOINTMENT_ID != $id
                         AND (STATUS IS NULL OR STATUS NOT IN ('Cancelled'))
                         LIMIT 1";
        $conflict_result = mysqli_query($conn, $conflict_sql);

        if ($conflict_result && mysqli_num_rows($conflict_result) > 0) {
            $error = "This staff member already has an appointment at that date and time. Please choose another slot.";
        }

        if (empty($error)) {
                $sql = "UPDATE APPOINTMENT SET
                    PATIENT_ID = '$patient_id',
                    STAFF_ID = '$staff_id',
                    APPOINTMENT_DATETIME = '$datetime',
                    STATUS = '$status'
                    WHERE APPOINTMENT_ID = $id";

            if (mysqli_query($conn, $sql)) {
                systemLog($conn, "Updated appointment details", 'appointment', $id);
                header("Location: appointment.php?msg=updated");
                exit();
            }

            $error = "Error updating appointment: " . mysqli_error($conn);
        }
    }
}

// Fetch existing data
$res = mysqli_query($conn, "SELECT * FROM APPOINTMENT WHERE APPOINTMENT_ID = $id");
$appt = mysqli_fetch_assoc($res);

if(!$appt) {
    echo "Appointment not found."; exit();
}

// Format the DATETIME for the HTML5 input
$existing_datetime = date('Y-m-d\TH:i', strtotime($appt['APPOINTMENT_DATETIME']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Edit Appointment</title>
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
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Edit Appointment</h1>
            <p class="text-slate-500 font-medium mt-1">Modify scheduling details for this visit.</p>
        </header>

        <?php if(!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold flex items-center max-w-3xl">
                <i class="fa-solid fa-triangle-exclamation mr-3"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 max-w-3xl">
            <form method="POST" action="appointment_edit.php?id=<?php echo $id; ?>">
                <input type="hidden" name="appointment_id" value="<?php echo $id; ?>">
                
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-600 mb-2">Patient Details</label>
                    <select name="patient_id" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                        <?php 
                        $p_res = mysqli_query($conn, "SELECT PATIENT_ID, NAME, PHONE_NUMBER FROM PATIENT ORDER BY NAME ASC");
                        while($p = mysqli_fetch_assoc($p_res)) {
                            $selected = ($p['PATIENT_ID'] == $appt['PATIENT_ID']) ? 'selected' : '';
                            $phone_display = empty($p['PHONE_NUMBER']) ? "No Phone" : htmlspecialchars($p['PHONE_NUMBER']);
                            echo "<option value='{$p['PATIENT_ID']}' $selected>{$p['NAME']} ({$phone_display})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-600 mb-2">Attending Optometrist / Staff</label>
                    <select name="staff_id" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                        <?php 
                        // Exclude Admin from dropdown
                        $u_res = mysqli_query($conn, "SELECT USER_ID, NAME, ROLE FROM USER WHERE ROLE IN ('Optometrist', 'Staff') ORDER BY NAME ASC");
                        while($u = mysqli_fetch_assoc($u_res)) {
                            $selected = ($u['USER_ID'] == $appt['STAFF_ID']) ? 'selected' : '';
                            echo "<option value='{$u['USER_ID']}' $selected>{$u['NAME']} ({$u['ROLE']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-8">
                    <div class="flex justify-between items-end mb-2">
                        <label class="block text-sm font-bold text-slate-600">Appointment Date & Time</label>
                        <span class="text-xs font-bold text-[#0097B2] bg-teal-50 px-2 py-1 rounded-md">Open: 11 AM - 7 PM (Closed Wed)</span>
                    </div>
                    <input type="datetime-local" name="appointment_datetime" id="appointment_datetime" value="<?php echo $existing_datetime; ?>" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                </div>

                <div class="mb-10">
                    <label class="block text-sm font-bold text-slate-600 mb-2">Current Status</label>
                    <select name="status" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] transition-colors font-medium text-slate-700">
                        <option value="Pending" <?php if($appt['STATUS'] == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Checked-In" <?php if($appt['STATUS'] == 'Checked-In') echo 'selected'; ?>>Checked-In</option>
                        <option value="Completed" <?php if($appt['STATUS'] == 'Completed') echo 'selected'; ?>>Completed</option>
                        <option value="Cancelled" <?php if($appt['STATUS'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-4 border-t border-slate-100 pt-6">
                    <a href="appointment.php" class="px-8 py-4 text-slate-400 font-bold hover:text-slate-600 transition-colors">Cancel</a>
                    <button type="submit" name="update_appointment" class="bg-[#0097B2] text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-teal-600 transition-colors shadow-lg shadow-teal-100 flex items-center">
                        <i class="fa-solid fa-save mr-2"></i> Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // --- FRONT-END BUSINESS HOURS VALIDATION ---
        const datetimeInput = document.getElementById('appointment_datetime');

        datetimeInput.addEventListener('change', function() {
            if(!this.value) return;
            const selectedDate = new Date(this.value);
            const day = selectedDate.getDay(); 
            const hour = selectedDate.getHours();

            if (day === 3) {
                alert("The clinic is closed on Wednesdays. Please select another day.");
                this.value = ''; 
            } else if (hour < 11 || hour >= 19) {
                alert("Appointments can only be scheduled between 11:00 AM and 7:00 PM.");
                this.value = ''; 
            }
        });
    </script>
</body>
</html>