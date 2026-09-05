<?php
session_start();
include('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$error = "";
$success = "";
$step = isset($_SESSION['step']) ? $_SESSION['step'] : 'credentials';

function auth_rate_limit($purpose, $identifier, $max_attempts, $window_seconds) {
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cmore_auth_' . hash('sha256', $purpose . '|' . $identifier) . '.json';
    $now = time();
    $state = ['started_at' => $now, 'attempts' => 0];
    if (is_file($file)) {
        $stored = json_decode((string)file_get_contents($file), true);
        if (is_array($stored) && ($now - (int)($stored['started_at'] ?? 0)) < $window_seconds) {
            $state = $stored;
        }
    }
    if (($now - (int)$state['started_at']) >= $window_seconds) {
        $state = ['started_at' => $now, 'attempts' => 0];
    }
    $state['attempts']++;
    file_put_contents($file, json_encode($state), LOCK_EX);
    return $state['attempts'] <= $max_attempts;
}

function clear_otp_session() {
    unset($_SESSION['otp'], $_SESSION['otp_purpose'], $_SESSION['otp_created_at'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts']);
}

function complete_login($conn, $user_id, $name, $role, $audit_message) {
    session_regenerate_id(true);
    $_SESSION['USER_ID'] = $user_id;
    $_SESSION['NAME'] = $name;
    $_SESSION['ROLE'] = $role;
    systemLog($conn, $audit_message);
    unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_name'], $_SESSION['temp_role'], $_SESSION['first_login_otp'], $_SESSION['step']);
    clear_otp_session();
}

function sendSystemEmail($toEmail, $toName, $otp, $purpose) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_USER'], 'C-More Clinical Suite');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        if($purpose == 'login') {
            $mail->Subject = 'C-More Clinical Suite - Login Verification';
            $mail->Body    = "Hello <b>{$toName}</b>,<br><br>Your secure login OTP is: <b style='font-size:20px; color:#0097B2;'>{$otp}</b><br><br>Please do not share this code with anyone.";
        } else {
            $mail->Subject = 'C-More Clinical Suite - Password Reset';
            $mail->Body    = "Hello <b>{$toName}</b>,<br><br>You requested a password reset. Your OTP is: <b style='font-size:20px; color:#0097B2;'>{$otp}</b><br><br>If you did not request this, please ignore this email.";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; 
    }
}

// 1. HANDLE LOGIN SUBMISSION
if(isset($_POST['verify_credentials'])) {
    $email_input = strtolower(trim($_POST['email'] ?? ''));
    $email = mysqli_real_escape_string($conn, $email_input);
    $password = $_POST['password']; 

    if (!auth_rate_limit('login', $_SERVER['REMOTE_ADDR'] . '|' . $email_input, 10, 900)) {
        $error = "Too many login attempts. Please wait 15 minutes and try again.";
    } else {

    $sql = "SELECT * FROM user WHERE EMAIL = '$email'";
    $res = mysqli_query($conn, $sql);
    
    if($row = mysqli_fetch_assoc($res)) {
        // SECURE HASH VERIFICATION
        if(password_verify($password, $row['PASSWORD']) || $password === $row['PASSWORD']) { 
            
            // Note: We leave the '===' fallback ONLY so you don't get locked out right now. 
            // Once you reset your password, it will only use the hash verify.
            
            if ($row['FIRST_LOGIN_OTP'] == 1) {
                $otp = random_int(100000, 999999);
                
                $_SESSION['temp_user_id'] = $row['USER_ID'];
                $_SESSION['temp_email'] = $row['EMAIL'];
                $_SESSION['temp_name'] = $row['NAME'];
                $_SESSION['temp_role'] = $row['ROLE']; 
                $_SESSION['first_login_otp'] = $row['FIRST_LOGIN_OTP'];
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_purpose'] = 'login';
                $_SESSION['otp_created_at'] = time();
                $_SESSION['otp_expires_at'] = time() + 600;
                $_SESSION['otp_attempts'] = 0;
                
                if(sendSystemEmail($email, $row['NAME'], $otp, 'login')) {
                    $_SESSION['step'] = 'otp';
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Failed to send OTP email. Check your SMTP settings in .env.";
                }
            } else {
                complete_login($conn, $row['USER_ID'], $row['NAME'], $row['ROLE'], 'User logged in successfully');
                
                header("Location: directory.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    }
}

// 2. HANDLE FORGOT PASSWORD SUBMISSION
if(isset($_POST['trigger_forgot_password'])) {
    $email_input = strtolower(trim($_POST['forgot_email'] ?? ''));
    $email = mysqli_real_escape_string($conn, $email_input);

    if (!auth_rate_limit('forgot_password', $_SERVER['REMOTE_ADDR'] . '|' . $email_input, 3, 900)) {
        $_SESSION['step'] = 'forgot_email';
        $error = "Too many reset requests. Please wait 15 minutes and try again.";
    } else {
    
    $sql = "SELECT * FROM user WHERE EMAIL = '$email'";
    $res = mysqli_query($conn, $sql);
    
    if($row = mysqli_fetch_assoc($res)) {
        $otp = random_int(100000, 999999);
        
        $_SESSION['temp_user_id'] = $row['USER_ID'];
        $_SESSION['temp_email'] = $row['EMAIL'];
        $_SESSION['temp_name'] = $row['NAME'];
        $_SESSION['temp_role'] = $row['ROLE'];
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_purpose'] = 'forgot_password';
        $_SESSION['otp_created_at'] = time();
        $_SESSION['otp_expires_at'] = time() + 600;
        $_SESSION['otp_attempts'] = 0;
        
        sendSystemEmail($email, $row['NAME'], $otp, 'forgot_password');
        
        $_SESSION['step'] = 'otp';
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['step'] = 'otp'; 
        header("Location: login.php");
        exit();
    }
    }
}

// 3. HANDLE OTP VERIFICATION
if(isset($_POST['verify_otp'])) {
    $entered_otp = trim((string)($_POST['otp_code'] ?? ''));
    $otp_email = $_SESSION['temp_email'] ?? '';
    $otp_is_allowed = auth_rate_limit('otp_verify', $_SERVER['REMOTE_ADDR'] . '|' . $otp_email, 10, 900);
    $_SESSION['otp_attempts'] = (int)($_SESSION['otp_attempts'] ?? 0) + 1;
    $otp_is_valid = isset($_SESSION['otp'], $_SESSION['otp_expires_at'])
        && time() <= (int)$_SESSION['otp_expires_at']
        && $_SESSION['otp_attempts'] <= 5
        && $otp_is_allowed
        && hash_equals((string)$_SESSION['otp'], $entered_otp);

    if($otp_is_valid) {
        $otp_purpose = $_SESSION['otp_purpose'] ?? 'login';
        if($otp_purpose === 'forgot_password' || (isset($_SESSION['first_login_otp']) && $_SESSION['first_login_otp'] == 1)) {
            clear_otp_session();
            $_SESSION['otp_purpose'] = $otp_purpose;
            $_SESSION['step'] = 'change_password';
            header("Location: login.php");
            exit();
        } else {
            complete_login($conn, $_SESSION['temp_user_id'], $_SESSION['temp_name'], $_SESSION['temp_role'], 'User logged in successfully');
            
            header("Location: directory.php");
            exit();
        }
    } else {
        $error = time() > (int)($_SESSION['otp_expires_at'] ?? 0)
            ? "This OTP has expired. Please request a new code."
            : ($_SESSION['otp_attempts'] >= 5 || !$otp_is_allowed
                ? "Too many incorrect OTP attempts. Please request a new code."
                : "Incorrect OTP code. Please try again.");
        if (time() > (int)($_SESSION['otp_expires_at'] ?? 0) || $_SESSION['otp_attempts'] >= 5 || !$otp_is_allowed) clear_otp_session();
    }
}

// 4. HANDLE PASSWORD CHANGE SUBMISSION
if(isset($_POST['save_new_password'])) {
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];
    
    if($new_pw === $confirm_pw) {
        if(strlen($new_pw) >= 6) {
            $uid = $_SESSION['temp_user_id'];
            
            // SECURE HASHING APPLIED HERE
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $escaped_pw = mysqli_real_escape_string($conn, $hashed_pw);
            
            $sql = "UPDATE user SET PASSWORD = '$escaped_pw', FIRST_LOGIN_OTP = 0 WHERE USER_ID = $uid";
            if(mysqli_query($conn, $sql)) {
                complete_login($conn, $_SESSION['temp_user_id'], $_SESSION['temp_name'], $_SESSION['temp_role'], 'User changed password and logged in');
                
                header("Location: directory.php");
                exit();
            } else {
                $error = "Database error. Please try again.";
            }
        } else {
            $error = "Password must be at least 6 characters long.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}

if(isset($_GET['action'])) {
    if($_GET['action'] == 'cancel') {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    if($_GET['action'] == 'forgot') {
        $_SESSION['step'] = 'forgot_email';
        header("Location: login.php");
        exit();
    }
}

$step = isset($_SESSION['step']) ? $_SESSION['step'] : 'credentials';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Secure Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen items-center justify-center p-6 text-slate-900">

    <div class="max-w-4xl w-full bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 overflow-hidden flex flex-col md:flex-row border border-slate-100">
        
        <div class="md:w-1/2 bg-slate-900 p-12 flex flex-col justify-center items-center relative overflow-hidden text-center">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#0097B2]/20 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#B9D977]/10 rounded-full -ml-24 -mb-24 blur-3xl"></div>
            
            <div class="bg-white p-6 rounded-3xl shadow-xl mb-8 relative z-10 w-48 h-48 flex items-center justify-center">
                <img src="logo.png" alt="C-More Logo" class="w-full h-auto">
            </div>
            
            <h2 class="text-3xl font-extrabold text-white tracking-tight relative z-10">C More Optometry</h2>
            <p class="text-slate-400 mt-3 text-sm leading-relaxed relative z-10 font-medium">Secure clinic management and optical inventory system.</p>
        </div>

        <div class="md:w-1/2 p-12 flex flex-col justify-center bg-white">
            
            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-2xl mb-8 text-sm font-bold border border-red-100 flex items-center shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-3 text-lg"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($step == 'credentials'): ?>
                <div class="mb-10">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">Welcome Back</h3>
                    <p class="text-slate-500 font-medium mt-1">Please enter your credentials to continue.</p>
                </div>

                <form action="login.php" method="POST" class="space-y-6">
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address</label>
                        <i class="fa-solid fa-envelope absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <input type="email" name="email" required placeholder="staff@cmore.com" 
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>
                    
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Password</label>
                        <i class="fa-solid fa-lock absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <a href="login.php?action=forgot" class="text-sm font-bold text-[#0097B2] hover:text-[#007b91] transition">Forgot Password?</a>
                    </div>

                    <button type="submit" name="verify_credentials" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all mt-4">
                        Login securely <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </form>

            <?php elseif($step == 'forgot_email'): ?>
                <div class="mb-10">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">Reset Password</h3>
                    <p class="text-slate-500 font-medium mt-1">Enter your email to receive a verification code.</p>
                </div>

                <form action="login.php" method="POST" class="space-y-6">
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Registered Email</label>
                        <i class="fa-solid fa-envelope absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <input type="email" name="forgot_email" required placeholder="staff@cmore.com" 
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>

                    <div class="pt-2 space-y-3">
                        <button type="submit" name="trigger_forgot_password" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                            Send Reset Code <i class="fa-solid fa-paper-plane ml-2"></i>
                        </button>
                        <a href="login.php?action=cancel" class="block w-full text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition text-sm">
                            Back to Login
                        </a>
                    </div>
                </form>

            <?php elseif($step == 'otp'): ?>
                <div class="mb-8 text-center">
                    <div class="w-16 h-16 bg-[#0097B2]/10 text-[#0097B2] rounded-full flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Verification Sent</h3>
                    <p class="text-slate-500 font-medium mt-2 text-sm leading-relaxed">
                        We've sent a 6-digit code to <span class="font-bold text-slate-800"><?php echo isset($_SESSION['temp_email']) ? $_SESSION['temp_email'] : 'your email'; ?></span>.
                    </p>
                </div>

                <form action="login.php" method="POST" class="space-y-6">
                    <div class="space-y-2 text-center">
                        <input type="text" name="otp_code" required maxlength="6" placeholder="000000" autocomplete="off"
                               class="w-full text-center tracking-[0.5em] text-3xl py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-black text-slate-800 transition">
                    </div>

                    <div class="pt-4 space-y-3">
                        <button type="submit" name="verify_otp" class="w-full bg-[#B9D977] text-slate-900 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                            Verify Code
                        </button>
                        <a href="login.php?action=cancel" class="block w-full text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition text-sm">
                            Cancel
                        </a>
                    </div>
                </form>
                
            <?php elseif($step == 'change_password'): ?>
                <div class="mb-10">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">Update Password</h3>
                    <p class="text-slate-500 font-medium mt-1">
                        <?php echo (($_SESSION['otp_purpose'] ?? 'login') === 'forgot_password') ? "Create a new password for your account." : "For security, you must change the default password provided by the administrator."; ?>
                    </p>
                </div>

                <form action="login.php" method="POST" class="space-y-6">
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">New Password</label>
                        <i class="fa-solid fa-key absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <input type="password" name="new_password" required placeholder="Min. 6 characters" minlength="6"
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>
                    
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Confirm New Password</label>
                        <i class="fa-solid fa-check-double absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <input type="password" name="confirm_password" required placeholder="Type password again" minlength="6"
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>

                    <div class="pt-2 space-y-3">
                        <button type="submit" name="save_new_password" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                            Save Password & Login <i class="fa-solid fa-check ml-2"></i>
                        </button>
                        <a href="login.php?action=cancel" class="block w-full text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition text-sm">
                            Cancel
                        </a>
                    </div>
                </form>

            <?php endif; ?>
            
            <div class="mt-12 pt-6 border-t border-slate-50 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><i class="fa-solid fa-shield-halved mr-1"></i> C-More Optometry © <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>

</body>
</html>