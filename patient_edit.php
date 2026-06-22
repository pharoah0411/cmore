<?php 
include('config.php'); 

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header("Location: patients.php");
    exit();
}

$error_msg = '';

$res = mysqli_query($conn, "SELECT * FROM PATIENT WHERE PATIENT_ID = '$id'");
$row = mysqli_fetch_assoc($res);

if (!$row) {
    echo "Patient record not found.";
    exit();
}

if(isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = trim(mysqli_real_escape_string($conn, $_POST['ic_number']));
    $phone = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $connection = mysqli_real_escape_string($conn, $_POST['connection']);
    $interval = mysqli_real_escape_string($conn, $_POST['follow_up']);
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);

    // Validate Malaysian Phone Number Format
    // Valid formats: 01X-XXXXXXX, 0XX-XXXXXXX, or without dash
    // Must start with 0 and be 10-11 digits total
    if (!empty($phone)) {
        $phone_digits = preg_replace('/\D/', '', $phone);
        
        // Check if it's 10-11 digits starting with 0
        if (strlen($phone_digits) < 10 || strlen($phone_digits) > 11 || $phone_digits[0] != '0') {
            $error_msg = "Invalid Phone Number. Malaysian phone numbers must be 10-11 digits starting with 0 (e.g., 012-3456789 or 03-87654321).";
        } 
        // Additional validation: second digit should be 1-9
        elseif (!preg_match("/^0[1-9]/", $phone_digits)) {
            $error_msg = "Invalid Phone Number. Format should be 0X(X)-XXXXXXX (e.g., 012-3456789 or 03-87654321).";
        }
    } else {
        $error_msg = "Phone Number is required.";
    }

    // Validate Malaysian IC Number (Optional)
    // Format: YYMMDD-PB-GGGC or YYMMDDPBGGGC (12 digits total)
    if (empty($error_msg) && !empty($ic)) {
        $ic_digits = preg_replace('/\D/', '', $ic);
        
        if (strlen($ic_digits) != 12) {
            $error_msg = "Invalid IC Number. Must be 12 digits (e.g., 900101-01-1234 or 900101011234).";
        } 
        // Validate format: YYMMDD (valid date format)
        elseif (!preg_match("/^(\d{2})(\d{2})(\d{2})/", $ic_digits, $date_match)) {
            $error_msg = "Invalid IC Number format.";
        } 
        // Check if date is valid (MM: 01-12, DD: 01-31)
        else {
            $month = intval($date_match[2]);
            $day = intval($date_match[3]);
            
            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                $error_msg = "Invalid IC Number. Birth date in IC is invalid (month must be 01-12, day must be 01-31).";
            }
            // State code should be 01-16
            elseif (!preg_match("/^\d{6}(\d{2})/", $ic_digits, $state_match)) {
                $error_msg = "Invalid IC Number format.";
            }
            else {
                $state_code = intval($state_match[1]);
                if ($state_code < 1 || $state_code > 16) {
                    $error_msg = "Invalid IC Number. State code must be between 01-16.";
                }
            }
        }
    }

    if(empty($error_msg)) {
        $update_sql = "UPDATE PATIENT SET 
                       NAME='$name', 
                       IC_NUMBER='$ic', 
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
                               pattern="^(\d{12}|\d{6}-\d{2}-\d{4})$" placeholder="900101-01-1234 or 900101011234" 
                               title="Malaysian IC format: 12 digits (YYMMDD-PB-GGGC or YYMMDDPBGGGC where PB=01-16)"
                               maxlength="14"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition invalid:border-red-400 invalid:text-red-600 focus:invalid:border-red-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : $row['PHONE_NUMBER']); ?>" required 
                               pattern="^0[1-9]-?\d{7,8}$|^0[1-9]{2}-?\d{6,7}$" placeholder="012-3456789 or 03-87654321" 
                               title="Malaysian phone number: 10-11 digits starting with 0 (e.g., 012-3456789 or 03-87654321)" 
                               maxlength="12"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition invalid:border-red-400 invalid:text-red-600 focus:invalid:border-red-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address <span class="text-slate-300 font-medium normal-case tracking-normal ml-1">(Optional)</span></label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : $row['ADDRESS']); ?>" 
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
    <script>
        // Auto-format Malaysian phone number with dash
        document.getElementById('phone').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').substring(0, 11); // Max 11 digits
            
            // Format as 0XX-XXXXXXX or 0X-XXXXXXXX based on second digit
            if (x.length > 0) {
                if (x.length <= 3) {
                    e.target.value = x;
                } else if (x.length > 3) {
                    e.target.value = x.substring(0, x.length - 7) + '-' + x.substring(x.length - 7);
                }
            } else {
                e.target.value = '';
            }
        });
    </script>
</body>
</html>