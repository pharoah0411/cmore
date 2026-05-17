<?php 
include('config.php'); 
// Fetch dynamic counts from database tables
$p_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PATIENT"))['t'];
$a_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM APPOINTMENT WHERE STATUS != 'Completed'"))['t'];
$s_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PRODUCT WHERE STOCK_QUANTITY < 5"))['t'];

// NEW: Fetch count of expiring/expired products (within 90 days)
$e_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM PRODUCT WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"))['t'];
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
                </div>
            </div>
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
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <a href="patients.php" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden group hover:border-[#0097B2]/40 transition-all">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-teal-50 rounded-2xl flex items-center justify-center text-[#0097B2] shadow-sm group-hover:bg-[#0097B2] group-hover:text-white transition-colors">
                            <i class="fa-solid fa-user-check text-2xl"></i>
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
                <div class="w-16 h-16 <?php echo ($s_count > 0 || $e_count > 0) ? 'bg-red-50 text-red-500 group-hover:bg-red-500' : 'bg-slate-50 text-slate-400 group-hover:bg-slate-400'; ?> rounded-2xl flex items-center justify-center mb-6 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                </div>
                <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.15em]">Inventory Alerts</h3>
                <p class="text-6xl font-black <?php echo ($s_count > 0 || $e_count > 0) ? 'text-red-600' : 'text-slate-900'; ?> mt-2"><?php echo ($s_count + $e_count); ?></p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-10 h-10 bg-slate-900 text-[#B9D977] rounded-xl flex items-center justify-center shadow-lg"><i class="fa-solid fa-plus text-lg"></i></div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Quick Management</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="patient_add.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-user-plus text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Patient</span>
                    </a>
                    <a href="sales.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-receipt text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Sales</span>
                    </a>
                    <a href="inventory.php" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-100 rounded-[2rem] hover:bg-white hover:border-[#0097B2] hover:shadow-lg transition group">
                        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0097B2] mb-3 group-hover:scale-110 transition"><i class="fa-solid fa-box-open text-xl"></i></div>
                        <span class="text-xs font-bold text-slate-600 group-hover:text-[#0097B2]">Add Stock</span>
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>