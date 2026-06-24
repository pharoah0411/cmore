<?php
session_start();
include('config.php');

// 1. If not logged in at all, kick them to login
if(!isset($_SESSION['USER_ID'])) {
    header("Location: login.php");
    exit();
}

// 2. Mark the session as explicitly locked
$_SESSION['is_locked'] = true;

$error = "";

// 3. Handle Unlock Request
if(isset($_POST['unlock'])) {
    $passcode = $_POST['passcode'];
    $uid = $_SESSION['USER_ID'];
    
    // Check the entered passcode against their permanent password in the DB
    $sql = "SELECT PASSWORD FROM user WHERE USER_ID = $uid";
    $res = mysqli_query($conn, $sql);
    
    if($row = mysqli_fetch_assoc($res)) {
        // SECURE HASH VERIFICATION
        if(password_verify($passcode, $row['PASSWORD']) || $passcode === $row['PASSWORD']) {
            // Success! Remove lock and redirect back to the last page
            unset($_SESSION['is_locked']);
            
            // Get the last page from session, default to directory
            $redirect_page = 'directory.php'; // Default safe page
            if (isset($_SESSION['last_page']) && !empty($_SESSION['last_page'])) {
                // Sanitize the redirect URL to prevent open redirect attacks
                $last_page = $_SESSION['last_page'];
                // Only allow redirects to PHP files in the root directory
                if (preg_match('/^[a-zA-Z0-9_\\-\\.]+\\.php(\\?[a-zA-Z0-9_=&\\-]+)?$/', $last_page)) {
                    $redirect_page = $last_page;
                }
            }
            unset($_SESSION['last_page']); // Clear it after use
            
            header("Location: $redirect_page"); 
            exit();
        } else {
            $error = "Incorrect passcode. Please try again.";
        }
    }
}

// 4. Handle fully logging out from the lock screen
if(isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session Locked | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen items-center justify-center p-6 text-slate-900">

    <div class="max-w-md w-full bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 overflow-hidden border border-slate-100 p-10 relative">
        
        <div class="absolute top-0 right-0 w-48 h-48 bg-[#0097B2]/10 rounded-full -mr-24 -mt-24 blur-3xl z-0"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-[#B9D977]/20 rounded-full -ml-16 -mb-16 blur-2xl z-0"></div>

        <div class="relative z-10 text-center">
            <div class="w-20 h-20 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                <i class="fa-solid fa-lock text-3xl text-[#0097B2]"></i>
            </div>
            
            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Session Locked</h3>
            <p class="text-slate-500 font-medium mt-1 text-sm">
                Hi <span class="font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['NAME']); ?></span>, your session was locked due to inactivity.
            </p>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-3 rounded-xl mt-6 text-sm font-bold border border-red-100 flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="lock.php" method="POST" class="mt-8 space-y-6">
                <div class="space-y-2 relative text-left">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Enter Passcode</label>
                    <i class="fa-solid fa-key absolute left-5 top-[2.4rem] text-slate-400"></i>
                    <input type="password" name="passcode" required placeholder="••••••" autofocus
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center tracking-[0.5em] transition">
                </div>

                <button type="submit" name="unlock" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                    Unlock System <i class="fa-solid fa-unlock-keyhole ml-2"></i>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-50">
                <a href="lock.php?action=logout" class="text-sm font-bold text-slate-400 hover:text-red-500 transition">
                    Not you? Sign out entirely.
                </a>
            </div>
        </div>
    </div>

</body>
</html>