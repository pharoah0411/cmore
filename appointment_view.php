<?php
include('config.php');

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header('Location: appointment.php');
    exit();
}

$sql = "SELECT A.*, P.NAME AS PATIENT_NAME, P.IC_NUMBER, P.PHONE_NUMBER, P.ADDRESS,
               P.REGISTRATION_DATE, P.CONNECTION_RELATIONSHIP, P.FOLLOW_UP_INTERVAL,
               P.COMPLAINTS, U.NAME AS STAFF_NAME, U.ROLE AS STAFF_ROLE
        FROM APPOINTMENT A
        JOIN PATIENT P ON A.PATIENT_ID = P.PATIENT_ID
        LEFT JOIN USER U ON A.STAFF_ID = U.USER_ID
        WHERE A.APPOINTMENT_ID = '$id'";
$res = mysqli_query($conn, $sql);
$appointment = $res ? mysqli_fetch_assoc($res) : null;

if (!$appointment) {
    header('Location: appointment.php');
    exit();
}

$status = (empty($appointment['STATUS']) || $appointment['STATUS'] === 'Scheduled')
    ? 'Pending'
    : $appointment['STATUS'];

if ($status === 'Completed') {
    $status_class = 'bg-slate-100 text-slate-500';
} elseif ($status === 'Checked-In') {
    $status_class = 'bg-blue-100 text-blue-600';
} elseif ($status === 'Cancelled') {
    $status_class = 'bg-red-100 text-red-600';
} else {
    $status_class = 'bg-[#B9D977]/20 text-[#6d8a2a]';
}

function display_value($value) {
    return !empty($value) ? htmlspecialchars($value) : 'Not provided';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Appointment Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-start mb-10">
            <div>
                <a href="appointment.php" class="text-slate-400 text-sm font-bold hover:text-[#0097B2] transition flex items-center mb-3">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Schedule
                </a>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Appointment Details</p>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-2"><?php echo display_value($appointment['PATIENT_NAME']); ?></h1>
            </div>
            <a href="appointment_edit.php?id=<?php echo $id; ?>" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-50 transition shadow-sm">
                <i class="fa-solid fa-pen mr-2"></i> Edit Appointment
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <section class="lg:col-span-2 bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex justify-between items-start border-b border-slate-100 pb-6 mb-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Scheduled Visit</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-2"><?php echo date('l, d M Y', strtotime($appointment['APPOINTMENT_DATETIME'])); ?></p>
                        <p class="text-lg font-bold text-[#0097B2] mt-1"><i class="fa-regular fa-clock mr-2"></i><?php echo date('h:i A', strtotime($appointment['APPOINTMENT_DATETIME'])); ?></p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-wider <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                </div>

                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-6">Patient Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-7">
                    <div><p class="field-label">Patient ID</p><p class="field-value">#<?php echo str_pad($appointment['PATIENT_ID'], 4, '0', STR_PAD_LEFT); ?></p></div>
                    <div><p class="field-label">IC Number</p><p class="field-value"><?php echo display_value($appointment['IC_NUMBER']); ?></p></div>
                    <div><p class="field-label">Phone Number</p><p class="field-value"><?php echo display_value($appointment['PHONE_NUMBER']); ?></p></div>
                    <div><p class="field-label">Registered On</p><p class="field-value"><?php echo !empty($appointment['REGISTRATION_DATE']) ? date('d M Y', strtotime($appointment['REGISTRATION_DATE'])) : 'Not provided'; ?></p></div>
                    <div class="md:col-span-2"><p class="field-label">Address</p><p class="field-value leading-relaxed"><?php echo display_value($appointment['ADDRESS']); ?></p></div>
                    <div><p class="field-label">Relationship</p><p class="field-value"><?php echo display_value($appointment['CONNECTION_RELATIONSHIP']); ?></p></div>
                    <div><p class="field-label">Follow-Up Interval</p><p class="field-value"><?php echo display_value($appointment['FOLLOW_UP_INTERVAL']); ?></p></div>
                    <div class="md:col-span-2"><p class="field-label">Complaints</p><p class="field-value leading-relaxed"><?php echo display_value($appointment['COMPLAINTS']); ?></p></div>
                </div>
            </section>

            <aside class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-300/40 h-fit">
                <div class="w-14 h-14 rounded-2xl bg-[#B9D977] text-slate-900 flex items-center justify-center text-2xl mb-6"><i class="fa-solid fa-user-doctor"></i></div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Assigned Staff</p>
                <h2 class="text-2xl font-extrabold mt-2"><?php echo display_value($appointment['STAFF_NAME']); ?></h2>
                <p class="text-[#B9D977] font-bold mt-1"><?php echo display_value($appointment['STAFF_ROLE']); ?></p>
                <div class="border-t border-slate-700 mt-8 pt-6">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Appointment ID</p>
                    <p class="font-mono text-lg mt-2">#<?php echo str_pad($appointment['APPOINTMENT_ID'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
            </aside>
        </div>
    </main>

    <style>
        .field-label { color: #94a3b8; font-size: 10px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 4px; }
        .field-value { color: #1e293b; font-size: 1rem; font-weight: 700; }
    </style>
</body>
</html>
