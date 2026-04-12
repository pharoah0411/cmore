<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Management Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
        .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans flex text-slate-900">

    <aside class="w-72 bg-slate-900 text-white flex flex-col fixed h-full shadow-2xl">
        <div class="p-8 border-b border-slate-800">
            <h1 class="text-3xl font-black tracking-tighter text-blue-400">C-MORE</h1>
            <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest">Optical Management v1.0</p>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="flex items-center space-x-3 p-4 bg-blue-600/20 text-blue-400 rounded-xl border border-blue-600/50">
                <i class="fa-solid fa-house-chimney text-lg"></i>
                <span class="font-semibold">Dashboard</span>
            </a>
            <a href="patients.php" class="flex items-center space-x-3 p-4 hover:bg-slate-800 rounded-xl transition group">
                <i class="fa-solid fa-hospital-user text-slate-400 group-hover:text-white"></i>
                <span>Patients</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-4 hover:bg-slate-800 rounded-xl transition group">
                <i class="fa-solid fa-calendar-day text-slate-400 group-hover:text-white"></i>
                <span>Appointments</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-4 hover:bg-slate-800 rounded-xl transition group">
                <i class="fa-solid fa-stethoscope text-slate-400 group-hover:text-white"></i>
                <span>Eye Examinations</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-4 hover:bg-slate-800 rounded-xl transition group">
                <i class="fa-solid fa-glasses text-slate-400 group-hover:text-white"></i>
                <span>Inventory</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-4 hover:bg-slate-800 rounded-xl transition group">
                <i class="fa-solid fa-receipt text-slate-400 group-hover:text-white"></i>
                <span>Sales Records</span>
            </a>
        </nav>

        <div class="p-6 border-t border-slate-800 bg-slate-900/50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center font-bold">UT</div>
                <div>
                    <p class="text-sm font-bold">UTeM Student</p>
                    <p class="text-[10px] text-slate-500">FYP Developer</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-72 p-10">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-4xl font-bold text-slate-800">Welcome Back</h2>
                <p class="text-slate-500 mt-2">Here is what's happening at C-More today.</p>
            </div>
            <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border flex items-center space-x-4">
                <i class="fa-solid fa-clock text-blue-500"></i>
                <span class="font-medium text-slate-700"><?php echo date('l, d F Y'); ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <div onclick="location.href='patients.php';" class="glass-card p-8 rounded-3xl border border-white hover-lift cursor-pointer relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                    <i class="fa-solid fa-user-injured text-9xl"></i>
                </div>
                <div class="w-14 h-14 bg-blue-500 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-200">
                    <i class="fa-solid fa-id-card-clip text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Patient Management</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-4">View medical history, IC records, and registration dates for all clinic clients.</p>
                <span class="text-blue-600 font-bold flex items-center text-sm">Open Directory <i class="fa-solid fa-arrow-right ml-2 text-xs"></i></span>
            </div>

            <div class="glass-card p-8 rounded-3xl border border-white hover-lift cursor-pointer relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                    <i class="fa-solid fa-eye text-9xl"></i>
                </div>
                <div class="w-14 h-14 bg-purple-500 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-purple-200">
                    <i class="fa-solid fa-microscope text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Eye Examinations</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-4">Record prescriptions, visual acuity results, and optometrist's clinical notes.</p>
                <span class="text-purple-600 font-bold flex items-center text-sm">View Exams <i class="fa-solid fa-arrow-right ml-2 text-xs"></i></span>
            </div>

            <div class="glass-card p-8 rounded-3xl border border-white hover-lift cursor-pointer relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                    <i class="fa-solid fa-box text-9xl"></i>
                </div>
                <div class="w-14 h-14 bg-emerald-500 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Product Inventory</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-4">Monitor stock levels for frames, lenses, and contact lenses. Manage pricing.</p>
                <span class="text-emerald-600 font-bold flex items-center text-sm">Manage Stock <i class="fa-solid fa-arrow-right ml-2 text-xs"></i></span>
            </div>

        </div>

        <div class="mt-12 flex items-center space-x-2 text-slate-400 text-xs uppercase tracking-widest">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span>Database "<?php echo $db; ?>" is Live</span>
        </div>
    </main>

</body>
</html>