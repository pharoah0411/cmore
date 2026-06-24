<?php 
include('config.php'); 

$error_msg = '';

if(isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = trim(mysqli_real_escape_string($conn, $_POST['ic_number']));
    $phone = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $connection = mysqli_real_escape_string($conn, $_POST['connection']);
    $interval = mysqli_real_escape_string($conn, $_POST['follow_up']);
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);

   // Validate Malaysian Phone Number Format
    // Valid formats: 01X-XXXXXXX, 03-XXXXXXXX, 06-XXXXXXX
    // Must start with 0 and be 9-11 digits total
    if (!empty($phone)) {
        $phone_digits = preg_replace('/\D/', '', $phone);
        
        // Check if it's 9-11 digits starting with 0
        if (strlen($phone_digits) < 9 || strlen($phone_digits) > 11 || $phone_digits[0] != '0') {
            $error_msg = "Invalid Phone Number. Malaysian phone numbers must be 9-11 digits starting with 0 (e.g., 012-3456789, 03-87654321, or 06-1234567).";
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

    if (empty($error_msg)) {
        // FOOLPROOF BYPASS: Protects against strict unique constraints
        if(empty($ic)) {
            $ic_val = "'NO-IC-" . rand(10000, 99999) . "'";
        } else {
            $ic_val = "'$ic'";
        }

        $sql = "INSERT INTO PATIENT (NAME, IC_NUMBER, PHONE_NUMBER, ADDRESS, CONNECTION_RELATIONSHIP, FOLLOW_UP_INTERVAL, COMPLAINTS, REGISTRATION_DATE) 
                VALUES ('$name', $ic_val, '$phone', '$address', '$connection', '$interval', '$complaints', NOW())";
        
        if(mysqli_query($conn, $sql)) {
            header("Location: patients.php?msg=added");
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
    <title>C-More | Register Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    <?php include('sidebar.php'); ?>
    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <a href="patients.php" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Directory
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Register New Patient</h1>
        </header>

        <?php if(!empty($error_msg)): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center shadow-sm max-w-5xl">
                <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="max-w-5xl space-y-8">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">IC Number <span class="text-slate-300 font-medium normal-case tracking-normal ml-1">(Optional)</span></label>
                        <input type="text" name="ic_number" value="<?php echo htmlspecialchars(isset($_POST['ic_number']) ? $_POST['ic_number'] : ''); ?>" 
                               pattern="^(\d{12}|\d{6}-\d{2}-\d{4})$" placeholder="900101-01-1234 or 900101011234"
                               title="Malaysian IC format: 12 digits (YYMMDD-PB-GGGC or YYMMDDPBGGGC where PB=01-16)"
                               maxlength="14"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>" required 
                               pattern="^0[1-9]-?\d{7,8}$|^0[1-9]{2}-?\d{6,7}$" placeholder="012-3456789 or 03-87654321"
                               title="Malaysian phone number: 10-11 digits starting with 0 (e.g., 012-3456789 or 03-87654321)"
                               maxlength="12"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address <span class="text-slate-300 font-medium normal-case tracking-normal ml-1">(Optional)</span></label>
                        <input type="text" name="address" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Clinical Details</h3>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Relationship / Connection</label>
                            <input type="text" name="connection" placeholder="e.g. Family Member Name" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2]">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Patient Complaints</label>
                            <textarea name="complaints" rows="4" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2]"></textarea>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Next Appointment Follow-up</h3>
                    <div class="space-y-6">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Select Recall Interval:</p>
                        <div class="grid grid-cols-1 gap-4">
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:border-[#B9D977] transition">
                                <input type="radio" name="follow_up" value="3 Months" class="w-4 h-4 text-[#0097B2]">
                                <span class="ml-4 text-sm font-bold text-slate-700">3 Months (Children)</span>
                            </label>
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:border-[#B9D977] transition">
                                <input type="radio" name="follow_up" value="6 Months" class="w-4 h-4 text-[#0097B2]">
                                <span class="ml-4 text-sm font-bold text-slate-700">6 Months (Standard)</span>
                            </label>
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:border-[#B9D977] transition">
                                <input type="radio" name="follow_up" value="1 Year" class="w-4 h-4 text-[#0097B2]">
                                <span class="ml-4 text-sm font-bold text-slate-700">1 Year (Comprehensive)</span>
                            </label>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="register" class="bg-[#0097B2] text-white px-12 py-4 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all">
                    Register Patient
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