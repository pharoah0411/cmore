<?php 
include('config.php'); 
// Fetch dynamic counts from database tables
$p_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PATIENT"))['t'];
$a_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM APPOINTMENT WHERE STATUS != 'Completed'"))['t'];
$s_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PRODUCT WHERE STOCK_QUANTITY < 5"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    <?php include('sidebar.php'); ?>
    
    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">System Overview</h1>
                <p class="text-slate-500 font-medium mt-1">Operational performance for <?php echo date('F d, Y'); ?></p>
            </div>
            <div class="flex space-x-3">
                <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 px-4">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-xs font-black text-slate-600 uppercase tracking-widest">Server Live: <?php echo $db; ?></span>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <a href="patients.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden group hover:border-[#0097B2]/40 transition-all">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-teal-50 rounded-2xl flex items-center justify-center text-[#0097B2] shadow-sm group-hover:bg-[#0097B2] group-hover:text-white transition-colors">
                            <i class="fa-solid fa-user-check text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center text-green-500 font-black text-sm">
                                <i class="fa-solid fa-caret-up mr-1"></i><span>12.5%</span>
                            </div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Growth</span>
                        </div>
                    </div>
                    </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Registered Patients</h3>
                <p class="text-6xl font-black text-slate-900 mt-2"><?php echo $p_count; ?></p>
            </a>

            <a href="appointment.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 group hover:border-[#0097B2]/40 transition-all">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-calendar-check text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Pending Sessions</h3>
                <p class="text-6xl font-black text-slate-900 mt-2"><?php echo $a_count; ?></p>
            </a>

            <a href="inventory.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 group hover:border-[#0097B2]/40 transition-all">
                <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 mb-6 group-hover:bg-red-500 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Low Stock Alerts</h3>
                <p class="text-6xl font-black <?php echo ($s_count > 0) ? 'text-red-600' : 'text-slate-900'; ?> mt-2"><?php echo $s_count; ?></p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-10 h-10 bg-slate-900 text-[#B9D977] rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-plus text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Quick Management</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="patient_add.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-user-plus text-xl"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Patient</span>
                    </a>
                    <a href="sales.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-receipt text-xl"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Sales</span>
                    </a>
                    <a href="inventory.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-box-open text-xl"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Stock</span>
                    </a>
                </div>
            </section>

            <section class="bg-slate-900 p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#0097B2]/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                
                <div class="flex items-center space-x-4 mb-8 relative z-10">
                    <div class="w-10 h-10 bg-white/10 text-[#B9D977] rounded-xl flex items-center justify-center border border-white/10">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Generate Clinical Report</h2>
                </div>

                <p class="text-slate-400 text-xs mb-8 relative z-10">Export summarized data for clinic analysis and auditing purposes.</p>

                <div class="space-y-4 relative z-10">
                    <button class="w-full flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-[#0097B2] hover:border-transparent transition text-white group">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-file-invoice-dollar text-slate-400 group-hover:text-white transition"></i>
                            <span class="text-sm font-semibold tracking-wide">Monthly Sales Performance</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600 group-hover:text-white"></i>
                    </button>

                    <button class="w-full flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-[#B9D977] hover:border-transparent transition text-white group">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-file-medical text-slate-400 group-hover:text-slate-900 transition"></i>
                            <span class="text-sm font-semibold tracking-wide group-hover:text-slate-900 transition">Stock & Inventory Audit</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600 group-hover:text-slate-900"></i>
                    </button>
                </div>
            </section>
        </div>
    </main>
</body>
</html>