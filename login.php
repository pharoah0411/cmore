<?php
session_start();
include('config.php');

// ==========================================
// TELEGRAM BOT CONFIGURATION
// ==========================================
$bot_token = $_ENV['TELEGRAM_BOT_TOKEN']; // Replace with your BotFather token

$error = "";

// 1. Handle Email & Password Submission
if(isset($_POST['verify_credentials'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 

    // Find User by Email (Updated based on your schema)
    $sql = "SELECT * FROM user WHERE EMAIL = '$email'";
    $res = mysqli_query($conn, $sql);
    
    if($row = mysqli_fetch_assoc($res)) {
        // Checking the password directly against the database
        if($password === $row['PASSWORD']) {
            
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Store temporary session data
            $_SESSION['temp_user_id'] = $row['USER_ID'];
            $_SESSION['temp_name'] = $row['NAME'];
            $_SESSION['otp'] = $otp;
            
            // Send OTP via Telegram
            $chat_id = $row['TELEGRAM_CHAT_ID'];
            if(!empty($chat_id)) {
                $message = "🔐 *C-More Clinical Suite*\nHello {$row['NAME']},\nYour secure login OTP is: *{$otp}*\n\n_Do not share this code with anyone._";
                $url = "https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&parse_mode=Markdown&text=" . urlencode($message);
                
                // Suppress warnings if network fails
                @file_get_contents($url);
                
                $_SESSION['step'] = 'otp'; // Move to OTP step
            } else {
                $error = "No Telegram Chat ID linked to this account.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}

// 2. Handle OTP Verification Submission
if(isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp_code'];
    
    if($entered_otp == $_SESSION['otp']) {
        // Success! Log the user in
        $_SESSION['USER_ID'] = $_SESSION['temp_user_id'];
        $_SESSION['NAME'] = $_SESSION['temp_name'];
        
        // Clean up temp variables
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_name']);
        unset($_SESSION['otp']);
        unset($_SESSION['step']);
        
        // Redirect to Dashboard
        header("Location: directory.php");
        exit();
    } else {
        $error = "Incorrect OTP code. Please try again.";
    }
}

// 3. Handle 'Go Back / Cancel'
if(isset($_GET['action']) && $_GET['action'] == 'cancel') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Determine which step of the UI to show
$step = isset($_SESSION['step']) ? $_SESSION['step'] : 'credentials';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Secure Login</title>
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
            
            <h2 class="text-3xl font-extrabold text-white tracking-tight relative z-10">Clinical Suite</h2>
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

                    <button type="submit" name="verify_credentials" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all mt-4">
                        Login securely <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </form>

            <?php elseif($step == 'otp'): ?>
                <div class="mb-8 text-center">
                    <div class="w-16 h-16 bg-[#0088cc]/10 text-[#0088cc] rounded-full flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Verification Required</h3>
                    <p class="text-slate-500 font-medium mt-2 text-sm leading-relaxed">
                        We've sent a 6-digit code to the Telegram account registered to <span class="font-bold text-slate-800"><?php echo $_SESSION['temp_name']; ?></span>.
                    </p>
                </div>

                <form action="login.php" method="POST" class="space-y-6">
                    <div class="space-y-2 text-center">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Enter 6-Digit Code</label>
                        <input type="text" name="otp_code" required maxlength="6" placeholder="000000" autocomplete="off"
                               class="w-full text-center tracking-[0.5em] text-3xl py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-black text-slate-800 transition">
                    </div>

                    <div class="pt-4 space-y-3">
                        <button type="submit" name="verify_otp" class="w-full bg-[#B9D977] text-slate-900 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                            Verify & Enter System
                        </button>
                        <a href="login.php?action=cancel" class="block w-full text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition text-sm">
                            Cancel / Wrong Account?
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