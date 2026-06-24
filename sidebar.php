<?php if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } ?>
<aside class="w-72 bg-[#0f172a] text-slate-300 flex flex-col fixed h-full shadow-2xl z-50">
    
    <div class="p-8 pb-4 flex flex-col items-center">
        <div class="relative group cursor-pointer">
            <div class="absolute -inset-1 bg-gradient-to-r from-[#0097B2] to-[#B9D977] rounded-full blur opacity-25 group-hover:opacity-60 transition duration-1000"></div>
            <img src="logo.png" alt="C-More Logo" class="relative w-44 h-auto drop-shadow-2xl">
        </div>
        <div class="mt-4 flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-[#B9D977] animate-pulse shadow-[0_0_8px_#B9D977]"></span>
            <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-500">Suite v1.0</p>
        </div>

        <div class="mt-6 w-full">
            <div class="bg-slate-900/80 py-3 rounded-2xl border border-slate-700/50 shadow-inner flex flex-col items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-[#0097B2]/10 to-transparent opacity-50"></div>
                <div id="live-time" class="text-xl font-black text-white tracking-widest relative z-10 drop-shadow-md">00:00:00</div>
                <div id="live-date" class="text-[9px] uppercase tracking-[0.2em] font-bold text-[#B9D977] mt-1 relative z-10">LOADING...</div>
            </div>
        </div>      
    </div>
                
    <nav class="flex-1 px-6 space-y-1 mt-2 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <?php 
        $current = basename($_SERVER['PHP_SELF']); 
        $user_role = !empty($_SESSION['ROLE']) ? $_SESSION['ROLE'] : 'Unassigned'; 

        function nav_item($link, $icon, $label, $current) {
            $active = ($current == $link);
            $class = $active 
                ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977] shadow-lg shadow-slate-900/20' 
                : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
            
            echo "
            <a href='$link' class='flex items-center space-x-4 p-4 rounded-r-xl transition-all duration-300 group $class'>
                <i class='$icon text-lg " . ($active ? 'text-[#0097B2] drop-shadow-[0_0_5px_rgba(0,151,178,0.5)]' : 'text-slate-500 group-hover:text-[#B9D977]') . " transition-colors'></i>
                <span class='text-sm font-semibold tracking-wide'>$label</span>
            </a>";
        }

        // Dashboard & Patients
        nav_item('directory.php', 'fa-solid fa-grid-2', 'Dashboard', $current);
        nav_item('patients.php', 'fa-solid fa-user-group', 'Patients', $current);

        // Appointments Submenu
        $is_appt = ($current == 'appointment.php');
        $appt_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $appt_class = $is_appt ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977]' : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
        $appt_icon = $is_appt ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]';
        
        echo "
        <div class='relative group'>
            <button onclick=\"document.getElementById('appt-submenu').classList.toggle('hidden')\" class='w-full flex items-center justify-between p-4 rounded-r-xl transition-all duration-300 group $appt_class'>
                <div class='flex items-center space-x-4'>
                    <i class='fa-solid fa-calendar-check text-lg $appt_icon transition-colors'></i>
                    <span class='text-sm font-semibold tracking-wide'>Appointments</span>
                </div>
                <i class='fa-solid fa-chevron-down text-xs text-slate-500 group-hover:text-white transition-colors'></i>
            </button>
            <div id='appt-submenu' class='" . ($is_appt ? '' : 'hidden') . " bg-slate-900/50 mt-1 rounded-xl py-2 px-3 space-y-1 border-l-2 border-slate-700 ml-5'>
                <a href='appointment.php?status=all' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($appt_status == 'all' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-calendar-days mr-2'></i> All Appointments</a>
                <a href='appointment.php?status=Pending' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($appt_status == 'Pending' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-clock-rotate-left mr-2'></i> Pending</a>
                <a href='appointment.php?status=Completed' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($appt_status == 'Completed' ? 'bg-[#B9D977] text-slate-900' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-circle-check mr-2'></i> Completed</a>
                <a href='appointment.php?status=Cancelled' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($appt_status == 'Cancelled' ? 'bg-red-500 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-circle-xmark mr-2'></i> Cancelled</a>
            </div>
        </div>";

        nav_item('reports.php', 'fa-solid fa-chart-line', 'Reports & Analytics', $current);

        // Sales Submenu
        $is_sales = ($current == 'sales.php');
        $sales_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $sales_class = $is_sales ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977]' : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
        $sales_icon = $is_sales ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]';
        
        echo "
        <div class='relative group'>
            <button onclick=\"document.getElementById('sales-submenu').classList.toggle('hidden')\" class='w-full flex items-center justify-between p-4 rounded-r-xl transition-all duration-300 group $sales_class'>
                <div class='flex items-center space-x-4'>
                    <i class='fa-solid fa-receipt text-lg $sales_icon transition-colors'></i>
                    <span class='text-sm font-semibold tracking-wide'>Sales & Invoices</span>
                </div>
                <i class='fa-solid fa-chevron-down text-xs text-slate-500 group-hover:text-white transition-colors'></i>
            </button>
            <div id='sales-submenu' class='" . ($is_sales ? '' : 'hidden') . " bg-slate-900/50 mt-1 rounded-xl py-2 px-3 space-y-1 border-l-2 border-slate-700 ml-5'>
                <a href='sales.php?status=all' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($sales_status == 'all' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-file-invoice-dollar mr-2'></i> All Sales</a>
                <a href='sales.php?status=Partial' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($sales_status == 'Partial' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-circle-half-stroke mr-2'></i> Partial Paid</a>
                <a href='sales.php?status=Completed' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($sales_status == 'Completed' ? 'bg-[#B9D977] text-slate-900' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-check-double mr-2'></i> Completed</a>
            </div>
        </div>";

        // Inventory Submenu
        $is_inv = ($current == 'inventory.php');
        $inv_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $inv_cat = isset($_GET['category']) ? $_GET['category'] : 'all';
        $inv_class = $is_inv ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977]' : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
        $inv_icon = $is_inv ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]';
        
        echo "
        <div class='relative group'>
            <button onclick=\"document.getElementById('inv-submenu').classList.toggle('hidden')\" class='w-full flex items-center justify-between p-4 rounded-r-xl transition-all duration-300 group $inv_class'>
                <div class='flex items-center space-x-4'>
                    <i class='fa-solid fa-box-archive text-lg $inv_icon transition-colors'></i>
                    <span class='text-sm font-semibold tracking-wide'>Inventory</span>
                </div>
                <i class='fa-solid fa-chevron-down text-xs text-slate-500 group-hover:text-white transition-colors'></i>
            </button>
            <div id='inv-submenu' class='" . ($is_inv ? '' : 'hidden') . " bg-slate-900/50 mt-1 rounded-xl py-2 px-3 space-y-1 border-l-2 border-slate-700 ml-5'>
                <p class='text-[9px] uppercase tracking-widest text-slate-500 font-bold px-4 pt-2 pb-1'>Image Filters</p>
                <a href='inventory.php?filter=all' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($inv_filter == 'all' && $inv_cat == 'all' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-boxes-stacked mr-2'></i> All Products</a>
                <a href='inventory.php?filter=with_image' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($inv_filter == 'with_image' ? 'bg-[#B9D977] text-slate-900' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-image mr-2'></i> With Picture</a>
                <a href='inventory.php?filter=no_image' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($inv_filter == 'no_image' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-image-slash mr-2'></i> Without Picture</a>
                
                <p class='text-[9px] uppercase tracking-widest text-slate-500 font-bold px-4 pt-3 pb-1'>Categories</p>
                <a href='inventory.php?category=Frames' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($inv_cat == 'Frames' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-glasses mr-2'></i> Frames</a>
                <a href='inventory.php?category=Contact Lenses' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($inv_cat == 'Contact Lenses' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'><i class='fa-solid fa-eye mr-2'></i> Contact Lenses</a>
            </div>
        </div>";

        // ADDED: Database Backup link now available to all staff!
        nav_item('backup.php', 'fa-solid fa-database', 'Database Backup', $current);

        // Clinical Exams (Optometrist & Admin)
        if ($user_role === 'Admin' || $user_role === 'Optometrist') {
            $is_exam = ($current == 'exam.php');
            $exam_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            $exam_class = $is_exam ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977]' : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
            $exam_icon = $is_exam ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]';
            
            echo "
            <div class='relative group'>
                <button onclick=\"document.getElementById('exam-submenu').classList.toggle('hidden')\" class='w-full flex items-center justify-between p-4 rounded-r-xl transition-all duration-300 group $exam_class'>
                    <div class='flex items-center space-x-4'>
                        <i class='fa-solid fa-eye text-lg $exam_icon transition-colors'></i>
                        <span class='text-sm font-semibold tracking-wide'>Clinical Exams</span>
                    </div>
                    <i class='fa-solid fa-chevron-down text-xs text-slate-500 group-hover:text-white transition-colors'></i>
                </button>
                
                <div id='exam-submenu' class='" . ($is_exam ? '' : 'hidden') . " bg-slate-900/50 mt-1 rounded-xl py-2 px-3 space-y-1 border-l-2 border-slate-700 ml-5'>
                    <a href='exam.php?filter=all' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($exam_filter == 'all' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-users mr-2'></i> All Patients
                    </a>
                    <a href='exam.php?filter=with_rx' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($exam_filter == 'with_rx' ? 'bg-[#B9D977] text-slate-900' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-file-prescription mr-2'></i> Has Prescription
                    </a>
                    <a href='exam.php?filter=no_rx' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($exam_filter == 'no_rx' ? 'bg-orange-400 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-user-xmark mr-2'></i> No Prescription
                    </a>
                </div>
            </div>";
        }

        // Admin Only links
        if ($user_role === 'Admin') {
            nav_item('staff.php', 'fa-solid fa-user-tie', 'Staff Directory', $current);
            nav_item('audit_trail.php', 'fa-solid fa-shield-halved', 'Audit Trail', $current);
        }
        ?>
    </nav>

    <div class="p-5 m-4 rounded-2xl bg-slate-800/40 border border-slate-700/50 hover:bg-slate-800/60 transition-colors">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-[#0097B2] to-[#B9D977] flex items-center justify-center text-slate-900 font-black shadow-lg">
                <?php echo isset($_SESSION['NAME']) ? strtoupper(substr($_SESSION['NAME'], 0, 2)) : 'UT'; ?>
            </div>
            <div class="overflow-hidden flex-1">
                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['NAME'] ?? 'Unknown User'); ?></p>
                <p class="text-[9px] text-[#B9D977] uppercase font-black tracking-wider"><i class="fa-solid fa-circle-check text-[8px] mr-1"></i><?php echo htmlspecialchars($user_role); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="auto_backup.php?logout=true" class="block w-full text-center py-3 rounded-xl bg-slate-700/50 hover:bg-[#0097B2] text-slate-300 hover:text-white text-xs font-bold uppercase tracking-[0.15em] transition-all duration-300 shadow-inner">
                <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Logout & Backup
            </a>
        </div>
    </div>
</aside>

<script>
    function updateLiveClock() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; 
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        document.getElementById('live-time').innerHTML = `${hours}:${minutes}:${seconds} <span class="text-[11px] text-[#0097B2] ml-1">${ampm}</span>`;
        document.getElementById('live-date').innerText = now.toLocaleDateString('en-US', dateOptions);
    }
    updateLiveClock(); setInterval(updateLiveClock, 1000);

    let idleTimeout;
    const idleDuration = 120000; 
    function resetIdleTimer() {
        clearTimeout(idleTimeout);
        idleTimeout = setTimeout(() => { 
            const currentUrl = window.location.pathname.split('/').pop() + window.location.search;
            fetch('store_last_page.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'last_page=' + encodeURIComponent(currentUrl || 'directory.php')
            }).then(() => window.location.href = 'lock.php')
              .catch(() => window.location.href = 'lock.php');
        }, idleDuration);
    }
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => window.addEventListener(evt, resetIdleTimer, false));
    resetIdleTimer();

    // ---- ADDED: Invisible Auto-Backup Beacon when Browser Tab is Closed ----
    window.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            navigator.sendBeacon('auto_backup.php?bg=true');
        }
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #0097B2; }
</style>