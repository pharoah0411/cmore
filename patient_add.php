<?php 
include('config.php'); 

if(isset($_POST['register'])) {
    // Existing fields
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = mysqli_real_escape_string($conn, $_POST['ic_number']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // New requested fields
    $connection = mysqli_real_escape_string($conn, $_POST['connection']);
    $interval = mysqli_real_escape_string($conn, $_POST['follow_up']);
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);

    $sql = "INSERT INTO PATIENT (NAME, IC_NUMBER, PHONE_NUMBER, ADDRESS, CONNECTION_RELATIONSHIP, FOLLOW_UP_INTERVAL, COMPLAINTS, REGISTRATION_DATE) 
            VALUES ('$name', '$ic', '$phone', '$address', '$connection', '$interval', '$complaints', NOW())";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: patients.php?msg=added");
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

        <form action="" method="POST" class="max-w-5xl space-y-8">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">IC Number</label>
                        <input type="text" name="ic_number" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                        <input type="text" name="phone" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address</label>
                        <input type="text" name="address" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
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
</body>
</html>