<?php 
include('config.php');

// Check if user is Admin
if($_SESSION['ROLE'] !== 'Admin') {
    header("Location: directory.php");
    exit();
}

$error_msg = '';
$success_msg = '';

if(isset($_POST['add_staff'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $temp_password = trim(mysqli_real_escape_string($conn, $_POST['temp_password']));
    
    // Validation
    if(empty($name)) {
        $error_msg = "Staff name is required.";
    } elseif(empty($email)) {
        $error_msg = "Email is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif(empty($temp_password)) {
        $error_msg = "Temporary password is required.";
    } elseif(strlen($temp_password) < 4) {
        $error_msg = "Temporary password must be at least 4 characters.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT USER_ID FROM user WHERE EMAIL = '$email'";
        $check_res = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($check_res) > 0) {
            $error_msg = "Email already exists in the system.";
        } else {
            // Get next USER_ID
            $id_sql = "SELECT MAX(USER_ID) as max_id FROM user";
            $id_res = mysqli_query($conn, $id_sql);
            $id_row = mysqli_fetch_assoc($id_res);
            $new_id = ($id_row['max_id'] ?? 0) + 1;
            
            // SECURE HASHING APPLIED HERE
            $hashed_temp_pw = password_hash($temp_password, PASSWORD_DEFAULT);
            $safe_hash = mysqli_real_escape_string($conn, $hashed_temp_pw);
            
            // Insert new staff with hashed password and FIRST_LOGIN_OTP = 1
            $insert_sql = "INSERT INTO user (USER_ID, NAME, EMAIL, PASSWORD, ROLE, FIRST_LOGIN_OTP) 
                          VALUES ($new_id, '$name', '$email', '$safe_hash', 'Staff', 1)";
            
            if(mysqli_query($conn, $insert_sql)) {
                // Log the action
                systemLog($conn, "Added new staff member: $name ($email)", "user", $new_id);
                
                $success_msg = "Staff member added successfully! They can now log in with email: <strong>$email</strong> and temporary password: <strong>$temp_password</strong>. They will be prompted to change their password on first login.";
                
                // Reset form
                $_POST = array();
            } else {
                $error_msg = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Add Staff Member</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-8">
            <a href="directory.php" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Add Staff Member</h1>
            <p class="text-slate-500 font-medium mt-1">Register a new staff member to the system with temporary login credentials.</p>
        </header>

        <?php if(!empty($error_msg)): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center shadow-sm max-w-2xl">
                <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if(!empty($success_msg)): ?>
            <div class="mb-8 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl flex items-start shadow-sm max-w-2xl">
                <i class="fa-solid fa-check-circle text-xl mr-3 mt-1 flex-shrink-0"></i>
                <div>
                    <span class="font-bold block"><?php echo $success_msg; ?></span>
                    <p class="text-sm mt-2 text-green-600">Make sure to share these credentials securely. The staff member will set their own password on first login.</p>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="max-w-2xl">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Staff Information</h3>
                
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required 
                               placeholder="e.g., John Doe"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email Address</label>
                        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required 
                               placeholder="e.g., john@clinic.com"
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                        <p class="text-[10px] text-slate-500 mt-1 ml-1">This email will be used for login.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Temporary Password</label>
                        <div class="flex space-x-2">
                            <input type="text" name="temp_password" id="temp_password" value="<?php echo isset($_POST['temp_password']) ? htmlspecialchars($_POST['temp_password']) : ''; ?>" required 
                                   placeholder="Enter temporary password (min 4 characters)"
                                   class="flex-1 p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                            <button type="button" onclick="generatePassword()" class="px-6 py-4 bg-slate-100 hover:bg-slate-200 rounded-2xl font-semibold text-sm transition">
                                <i class="fa-solid fa-shuffle mr-2"></i>Generate
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1 ml-1">Staff must change this on their first login. Keep it simple (e.g., clinic name + date).</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mt-6">
                        <div class="flex items-start space-x-3">
                            <i class="fa-solid fa-circle-info text-blue-600 mt-1 flex-shrink-0"></i>
                            <div>
                                <p class="text-sm font-bold text-blue-900">Important Information</p>
                                <ul class="text-xs text-blue-700 mt-2 space-y-1 ml-1">
                                    <li><i class="fa-solid fa-check text-blue-600 mr-2"></i>Staff will receive an OTP via email on first login</li>
                                    <li><i class="fa-solid fa-check text-blue-600 mr-2"></i>They must set their own secure password after verification</li>
                                    <li><i class="fa-solid fa-check text-blue-600 mr-2"></i>Account role is set to "Staff"</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end items-center space-x-4 pt-8">
                <a href="directory.php" class="text-slate-400 font-bold px-6 py-4 hover:text-slate-600 transition">Cancel</a>
                <button type="submit" name="add_staff" 
                        class="bg-[#0097B2] text-white px-12 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Add Staff Member</span>
                </button>
            </div>
        </form>
    </main>

    <script>
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let password = '';
            for (let i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('temp_password').value = password;
        }
    </script>
</body>
</html>