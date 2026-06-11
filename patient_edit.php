<?php 
include('config.php'); 

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header("Location: patients.php");
    exit();
}

$error_msg = '';

if(isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = trim(mysqli_real_escape_string($conn, $_POST['ic_number']));
    $phone = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $connection = mysqli_real_escape_string($conn, $_POST['connection']);
    $interval = mysqli_real_escape_string($conn, $_POST['follow_up']);
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);

    // Validation Logic
    // IC format: exactly 12 digits OR 6 digits - 2 digits - 4 digits (e.g., 850101-14-5567)
    if (!empty($ic) && !preg_match("/^(\d{12}|\d{6}-\d{2}-\d{4})$/", $ic)) {
        $error_msg = "Invalid IC Format. Please enter a 12-digit number (e.g., 900101011234 or 900101-01-1234).";
    }

    // Phone format: starts with 0, 1-2 digits, optional dash, 7-8 digits (e.g., 012-3456789 or 0123456789)
    if (empty($error_msg) && !preg_match("/^0\d{1,2}-?\d{7,8}$/", $phone)) {
        $error_msg = "Invalid Phone Number Format. It should look like 012-3456789 or 0123456789.";
    }

    // If no validation errors, proceed with the update
    if(empty($error_msg)) {
        $ic_val = !empty($ic) ? "'$ic'" : "NULL";

        $update_sql = "UPDATE PATIENT SET 
                       NAME='$name', 
                       IC_NUMBER=$ic_val, 
                       PHONE_NUMBER='$phone', 
                       ADDRESS='$address', 
                       CONNECTION_RELATIONSHIP='$connection', 
                       FOLLOW_UP_INTERVAL='$interval', 
                       COMPLAINTS='$complaints' 
                       WHERE PATIENT_ID='$id'";
        
        if(mysqli_query($conn, $update_sql)) {
            header("Location: patient_details.php?id=$id&msg=updated");
            exit();
        } else {
            $error_msg = "Database Error: " . mysqli_error($conn);
        }
    }
}

$res = mysqli_query($conn, "SELECT * FROM PATIENT WHERE PATIENT_ID = '$id'");
$row = mysqli_fetch_assoc($res);

if (!$row) {
    echo "Patient record not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Edit Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-8">
            <a href="patient_details.php?id=<?php echo $id; ?>" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Profile
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Edit Patient Profile</h1>
            <p class="text-slate-500 font-medium mt-1">Update clinical records for <span class="text-[#0097B2] font-black"><?php echo htmlspecialchars($row['NAME']); ?></span>.</p>
        </header>

        <?php if(!empty($error_msg)): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center shadow-sm max-w-5xl">
                <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="max-w-5xl space-y-8" id="editPatientForm">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : $row['NAME']); ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">IC Number <span class="text-slate-300 font-medium normal-case tracking-normal ml-1">(Optional)</span></label>
                        <input type="text" name="ic_number" value="<?php echo htmlspecialchars(isset($_POST['ic_number']) ? $_POST['ic_number'] : $row['IC_NUMBER']); ?>" 
                               pattern="^(\d{12}|\d{6}-\d{2}-\d{4})$" title="Must be a 12-digit number (e.g., 900101-01-1234 or 900101011234)"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition invalid:border-red-400 invalid:text-red-600 focus:invalid:border-red-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : $row['PHONE_NUMBER']); ?>" required 
                               pattern="^0\d{1,2}-?\d{7,8}$" title="Malaysian phone number format (e.g., 012-3456789)"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition invalid:border-red-400 invalid:text-red-600 focus:invalid:border-red-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : $row['ADDRESS']); ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Clinical Details</h3>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Relationship / Connection</label>
                            <input type="text" name="connection" value="<?php echo htmlspecialchars(isset($_POST['connection']) ? $_POST['connection'] : $row['CONNECTION_RELATIONSHIP']); ?>" 
                                   placeholder="e.g. Family Member Name" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2] focus:bg-white transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Patient Complaints</label>
                            <textarea name="complaints" rows="4" 
                                      class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2] focus:bg-white transition"><?php echo htmlspecialchars(isset($_POST['complaints']) ? $_POST['complaints'] : $row['COMPLAINTS']); ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Follow-up Interval</h3>
                    <div class="space-y-4">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Current Recall: <span class="text-slate-800"><?php echo !empty($row['FOLLOW_UP_INTERVAL']) ? $row['FOLLOW_UP_INTERVAL'] : 'None'; ?></span></p>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <?php 
                            $intervals = ["3 Months", "6 Months", "1 Year"];
                            $current_val = isset($_POST['follow_up']) ? $_POST['follow_up'] : $row['FOLLOW_UP_INTERVAL'];
                            foreach($intervals as $val):
                                $checked = ($current_val == $val) ? "checked" : "";
                            ?>
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:border-[#B9D977] transition group">
                                <input type="radio" name="follow_up" value="<?php echo $val; ?>" <?php echo $checked; ?> class="w-4 h-4 text-[#0097B2] focus:ring-[#0097B2]">
                                <span class="ml-4 text-sm font-bold text-slate-700 group-hover:text-slate-900"><?php echo $val; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex justify-end items-center space-x-4 pt-4">
                <a href="patient_details.php?id=<?php echo $id; ?>" class="text-slate-400 font-bold px-6 py-4 hover:text-slate-600 transition">Cancel Changes</a>
                <button type="submit" name="update" 
                        class="bg-[#0097B2] text-white px-12 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </main>
</body>
</html>