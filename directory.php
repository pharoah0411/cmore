<?php 
include('config.php'); 
include 'check_expiry.php';
// Fetch dynamic counts from database tables
$p_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PATIENT"))['t'];
$a_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM APPOINTMENT WHERE STATUS != 'Completed'"))['t'];
$s_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PRODUCT WHERE STOCK_QUANTITY < 5"))['t'];

// Fetch count of expiring/expired products (within 90 days)
$e_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"))['t'];

// --- NEW: Fetch Today's Appointments ---
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

$today_appt_count_query = "SELECT COUNT(*) as t FROM APPOINTMENT WHERE DATE(APPOINTMENT_DATETIME) = '$today' AND STATUS != 'Cancelled'";
$today_appt_count = mysqli_fetch_assoc(mysqli_query($conn, $today_appt_count_query))['t'];

$today_appt_sql = "SELECT a.*, p.NAME as PATIENT_NAME, u.NAME as OPTOMETRIST_NAME 
                   FROM APPOINTMENT a
                   JOIN PATIENT p ON a.PATIENT_ID = p.PATIENT_ID
                   LEFT JOIN USER u ON a.STAFF_ID = u.USER_ID
                   WHERE DATE(a.APPOINTMENT_DATETIME) = '$today'
                   ORDER BY a.APPOINTMENT_DATETIME ASC";
$today_appt_res = mysqli_query($conn, $today_appt_sql);

// Total number of things that need staff attention right now (used for the summary line)
$attention_total = $s_count + $e_count;
// ---------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    <?php include('sidebar.php'); ?>
    
    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">System Overview</h1>
                <p class="text-slate-500 font-medium mt-1">Operational performance for <?php echo date('F d, Y'); ?></p>
            </div>
            <div class="flex space-x-3">
                <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 px-4" title="Connected to <?php echo $db; ?>">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-xs font-black text-slate-600 uppercase tracking-widest">System Online</span>
                </div>
            </div>
        </header>

        <!-- Plain-language "what's happening" line so the whole page has a one-sentence summary -->
        <div class="mb-10 flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-500 font-medium">
            <i class="fa-solid fa-circle-info text-[#0097B2]"></i>
            <span>Today you have</span>
            <span class="font-extrabold text-slate-800"><?php echo $today_appt_count; ?> appointment<?php echo $today_appt_count == 1 ? '' : 's'; ?></span>
            <?php if($attention_total > 0): ?>
                <span>and</span>
                <span class="font-extrabold text-red-600"><?php echo $attention_total; ?> inventory item<?php echo $attention_total == 1 ? '' : 's'; ?></span>
                <span>that need attention.</span>
            <?php else: ?>
                <span>and</span>
                <span class="font-extrabold text-[#0097B2]">no inventory issues.</span>
            <?php endif; ?>
        </div>

        <div class="bg-[#0097B2] rounded-[2rem] p-8 mb-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between border border-teal-600">
            <div class="mb-6 md:mb-0 md:pr-8">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-teal-100 mb-2">Daily Schedule</p>
                <h2 class="text-4xl font-extrabold tracking-tight">Today's Appointments</h2>
                <p class="text-teal-50 font-medium mt-2">You have <span class="font-black text-white text-lg"><?php echo $today_appt_count; ?></span> appointment(s) scheduled for today.</p>
            </div>
            
            <?php if($today_appt_count > 0): ?>
            <div class="w-full md:w-1/2 bg-white/10 rounded-2xl p-4 max-h-56 overflow-y-auto backdrop-blur-md border border-white/20 shadow-inner">
                <ul class="space-y-3">
                    <?php while($appt = mysqli_fetch_assoc($today_appt_res)): 
                        $time = date('h:i A', strtotime($appt['APPOINTMENT_DATETIME']));
                    ?>
                    <li class="flex justify-between items-center bg-white/10 hover:bg-white/20 transition px-5 py-4 rounded-xl border border-white/5">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center font-bold text-lg text-white shadow-sm">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <p class="font-bold text-white text-base leading-tight"><?php echo htmlspecialchars($appt['PATIENT_NAME']); ?></p>
                                <p class="text-[10px] font-bold text-teal-100 uppercase tracking-widest mt-1">
                                    <i class="fa-solid fa-user-doctor mr-1"></i> <?php echo htmlspecialchars($appt['OPTOMETRIST_NAME']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-xl text-white bg-white/10 px-3 py-1 rounded-lg"><?php echo $time; ?></span>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="w-full md:w-auto bg-white/10 px-8 py-6 rounded-2xl backdrop-blur-md border border-white/20 shadow-inner text-center flex items-center space-x-3">
                <i class="fa-solid fa-mug-hot text-3xl text-teal-100"></i>
                <p class="font-bold text-teal-50 text-lg">No appointments for today. Enjoy the free time!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if($s_count > 0): ?>
        <div class="bg-red-50 border border-red-200 rounded-[2rem] p-8 mb-6 flex items-start space-x-6 shadow-sm animate-fade-in">
            <div class="bg-red-100 text-red-500 w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 shadow-inner">
                <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-red-800 font-extrabold text-xl tracking-tight">Action Required: Low Inventory</h3>
                <p class="text-red-600 font-medium mt-1 mb-4">You have <?php echo $s_count; ?> product(s) running dangerously low (below 5 items).</p>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $low_stock_res = mysqli_query($conn, "SELECT BRAND_NAME, STOCK_QUANTITY FROM PRODUCT WHERE STOCK_QUANTITY < 5 ORDER BY STOCK_QUANTITY ASC LIMIT 5");
                    while($ls = mysqli_fetch_assoc($low_stock_res)):
                    ?>
                    <span class="bg-white border border-red-200 text-red-600 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-sm">
                        <?php echo htmlspecialchars($ls['BRAND_NAME']); ?> <span class="bg-red-100 text-red-600 ml-2 px-2 py-0.5 rounded-md font-bold"><?php echo $ls['STOCK_QUANTITY']; ?> left</span>
                    </span>
                    <?php endwhile; ?>
                    <?php if($s_count > 5): ?>
                    <a href="inventory.php" class="bg-red-500 text-white border border-red-600 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl hover:bg-red-600 transition shadow-sm flex items-center">
                        + <?php echo ($s_count - 5); ?> More
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <a href="inventory.php" class="shrink-0 text-red-400 hover:text-red-600 transition bg-white p-3 rounded-xl border border-red-100 shadow-sm">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>

        <?php if($e_count > 0): ?>
        <div class="bg-orange-50 border border-orange-200 rounded-[2rem] p-8 mb-8 flex items-start space-x-6 shadow-sm animate-fade-in">
            <div class="bg-orange-100 text-orange-500 w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 shadow-inner">
                <i class="fa-solid fa-clock text-3xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-orange-800 font-extrabold text-xl tracking-tight">Warning: Expiring Products</h3>
                <p class="text-orange-600 font-medium mt-1 mb-4">You have <?php echo $e_count; ?> product(s) (like lenses or solutions) that are expired or expiring within 90 days.</p>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $exp_res = mysqli_query($conn, "SELECT BRAND_NAME, EXPIRY_DATE FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY EXPIRY_DATE ASC LIMIT 5");
                    while($ex = mysqli_fetch_assoc($exp_res)):
                        $is_expired = (strtotime($ex['EXPIRY_DATE']) < time());
                        $chip_class = $is_expired ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600';
                    ?>
                    <span class="bg-white border border-orange-200 text-orange-600 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-sm">
                        <?php echo htmlspecialchars($ex['BRAND_NAME']); ?> 
                        <span class="<?php echo $chip_class; ?> ml-2 px-2 py-0.5 rounded-md font-bold">
                            <?php echo $is_expired ? 'EXPIRED' : 'Exp: ' . date('M Y', strtotime($ex['EXPIRY_DATE'])); ?>
                        </span>
                    </span>
                    <?php endwhile; ?>
                    <?php if($e_count > 5): ?>
                    <a href="inventory.php" class="bg-orange-500 text-white border border-orange-600 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl hover:bg-orange-600 transition shadow-sm flex items-center">
                        + <?php echo ($e_count - 5); ?> More
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <a href="inventory.php" class="shrink-0 text-orange-400 hover:text-orange-600 transition bg-white p-3 rounded-xl border border-orange-100 shadow-sm">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>

        <!-- ================= KEY NUMBERS: each card now explains what it means + what to do ================= -->
        <h3 class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 mb-6 ml-2">At a Glance</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">

            <a href="patients.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 group hover:border-[#0097B2]/40 transition-all flex flex-col">
                <div class="w-16 h-16 bg-teal-50 rounded-2xl flex items-center justify-center text-[#0097B2] mb-6 shadow-sm group-hover:bg-[#0097B2] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-user-check text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Registered Patients</h3>
                <p class="text-6xl font-black text-slate-900 mt-2 leading-none"><?php echo $p_count; ?></p>
                <p class="text-sm text-slate-400 font-medium mt-3">Total patient records on file</p>
                <div class="mt-6 flex items-center text-[10px] font-black uppercase tracking-widest text-[#0097B2] group-hover:translate-x-2 transition-transform">
                    View directory <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="appointment.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 group hover:border-[#0097B2]/40 transition-all flex flex-col">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-calendar-check text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Open Appointments</h3>
                <p class="text-6xl font-black text-slate-900 mt-2 leading-none"><?php echo $a_count; ?></p>
                <p class="text-sm text-slate-400 font-medium mt-3">Booked but not yet completed</p>
                <div class="mt-6 flex items-center text-[10px] font-black uppercase tracking-widest text-purple-600 group-hover:translate-x-2 transition-transform">
                    Manage schedule <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="inventory.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 group transition-all flex flex-col <?php echo $attention_total > 0 ? 'hover:border-red-300' : 'hover:border-[#0097B2]/40'; ?>">
                <div class="w-16 h-16 <?php echo $attention_total > 0 ? 'bg-red-50 text-red-500 group-hover:bg-red-500' : 'bg-slate-50 text-slate-400 group-hover:bg-slate-400'; ?> rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:text-white transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Inventory Needing Attention</h3>
                <p class="text-6xl font-black <?php echo $attention_total > 0 ? 'text-red-600' : 'text-slate-900'; ?> mt-2 leading-none"><?php echo $attention_total; ?></p>
                <p class="text-sm font-medium mt-3 <?php echo $attention_total > 0 ? 'text-red-500' : 'text-slate-400'; ?>">
                    <?php if($attention_total > 0): ?>
                        <span class="font-bold"><?php echo $s_count; ?></span> low stock &middot; <span class="font-bold"><?php echo $e_count; ?></span> expiring soon
                    <?php else: ?>
                        Stock levels are healthy
                    <?php endif; ?>
                </p>
                <div class="mt-6 flex items-center text-[10px] font-black uppercase tracking-widest <?php echo $attention_total > 0 ? 'text-red-500' : 'text-[#0097B2]'; ?> group-hover:translate-x-2 transition-transform">
                    Review inventory <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center space-x-4 mb-2">
                    <div class="w-10 h-10 bg-slate-900 text-[#B9D977] rounded-xl flex items-center justify-center shadow-lg"><i class="fa-solid fa-plus text-lg"></i></div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Quick Management</h2>
                </div>
                <p class="text-slate-400 text-sm font-medium mb-8 ml-1">Jump straight to the task you need.</p>
                <!-- 4 columns to fit the WhatsApp button -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <a href="patient_add.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-user-plus text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Patient</span>
                    </a>
                    <a href="sales_add.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-receipt text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Record Sale</span>
                    </a>
                    <a href="inventory_add.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-box-open text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Stock</span>
                    </a>
                    <a href="whatsapp_messages.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-green-500 hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-green-500 mb-3 group-hover:scale-110 transition"><i class="fa-brands fa-whatsapp text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-green-500">Messages</span>
                    </a>
                </div>
            </section>

            <?php if($_SESSION['ROLE'] === 'Admin'): ?>
            <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center shadow-lg"><i class="fa-solid fa-users-gear text-lg"></i></div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Staff Management</h2>
                </div>
                <p class="text-slate-600 text-sm mb-6">Add new staff members or optometrists to the system.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="staff_add.php" class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-2xl hover:bg-blue-100 hover:border-blue-300 transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-blue-600 group-hover:text-blue-700">
                                <i class="fa-solid fa-user-tie text-lg"></i>
                            </div>
                            <div>
                                <p class="font-bold text-blue-900">Add Staff Member</p>
                                <p class="text-xs text-blue-600">Register new staff</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-right text-blue-400 group-hover:text-blue-600"></i>
                    </a>
                    <a href="optometrist_add.php" class="flex items-center justify-between p-4 bg-purple-50 border border-purple-200 rounded-2xl hover:bg-purple-100 hover:border-purple-300 transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-purple-600 group-hover:text-purple-700">
                                <i class="fa-solid fa-user-doctor text-lg"></i>
                            </div>
                            <div>
                                <p class="font-bold text-purple-900">Add Optometrist</p>
                                <p class="text-xs text-purple-600">Register new optometrist</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-right text-purple-400 group-hover:text-purple-600"></i>
                    </a>
                </div>
            </section>
            <?php endif; ?>

            <section class="bg-slate-900 p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#0097B2]/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                
                <div class="flex items-center space-x-4 mb-2 relative z-10">
                    <div class="w-10 h-10 bg-white/10 text-[#B9D977] rounded-xl flex items-center justify-center border border-white/10">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Generate Reports</h2>
                </div>

                <p class="text-slate-400 text-xs mb-8 relative z-10 ml-1">Export summarized data for clinic analysis and auditing.</p>

                <div class="space-y-4 relative z-10">
                    <a href="report_sales.php" class="w-full flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-[#0097B2] hover:border-transparent transition text-white group cursor-pointer block">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-file-invoice-dollar text-slate-400 group-hover:text-white transition"></i>
                            <span class="text-sm font-semibold tracking-wide">Monthly Sales Performance</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600 group-hover:text-white"></i>
                    </a>

                    <a href="report_inventory.php" class="w-full flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-[#B9D977] hover:border-transparent transition text-white group cursor-pointer block">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-file-medical text-slate-400 group-hover:text-slate-900 transition"></i>
                            <span class="text-sm font-semibold tracking-wide group-hover:text-slate-900 transition">Stock & Inventory Audit</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600 group-hover:text-slate-900"></i>
                    </a>

                    <a href="reports.php" class="w-full flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition text-white group cursor-pointer block">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-table-list text-slate-400 group-hover:text-white transition"></i>
                            <span class="text-sm font-semibold tracking-wide">See all reports</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600 group-hover:text-white"></i>
                    </a>
                </div>
                
            </section>

        </div>
    </main>
</body>
</html>