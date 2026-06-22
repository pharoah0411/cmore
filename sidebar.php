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

        // ==========================================
        // 1. EVERYONE SEES THESE
        // ==========================================
        nav_item('directory.php', 'fa-solid fa-grid-2', 'Dashboard', $current);
        nav_item('patients.php', 'fa-solid fa-user-group', 'Patients', $current);
        nav_item('appointment.php', 'fa-solid fa-calendar-check', 'Appointments', $current);
        nav_item('reports.php', 'fa-solid fa-chart-line', 'Reports & Analytics', $current);
        nav_item('sales.php', 'fa-solid fa-receipt', 'Sales & Invoices', $current);
        nav_item('inventory.php', 'fa-solid fa-box-archive', 'Inventory', $current);
        
        // ==========================================
        // 2. OPTOMETRIST & ADMIN ONLY (WITH SUBMENU)
        // ==========================================
        if ($user_role === 'Admin' || $user_role === 'Optometrist') {
            $is_exam = ($current == 'exam.php');
            $exam_class = $is_exam ? 'bg-slate-800/70 text-white border-l-4 border-[#B9D977]' : 'hover:bg-slate-800/40 hover:text-white border-l-4 border-transparent';
            $exam_icon_class = $is_exam ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]';
            
            // Get current filter for styling
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            
            echo "
            <div class='relative group'>
                <button onclick=\"document.getElementById('exam-submenu').classList.toggle('hidden')\" class='w-full flex items-center justify-between p-4 rounded-r-xl transition-all duration-300 group $exam_class'>
                    <div class='flex items-center space-x-4'>
                        <i class='fa-solid fa-eye text-lg $exam_icon_class transition-colors'></i>
                        <span class='text-sm font-semibold tracking-wide'>Clinical Exams</span>
                    </div>
                    <i class='fa-solid fa-chevron-down text-xs text-slate-500 group-hover:text-white transition-colors'></i>
                </button>
                
                <div id='exam-submenu' class='" . ($is_exam ? '' : 'hidden') . " bg-slate-900/50 mt-1 rounded-xl py-2 px-3 space-y-1 border-l-2 border-slate-700 ml-5'>
                    <a href='exam.php?filter=all' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($filter == 'all' ? 'bg-[#0097B2] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-users mr-2'></i> All Patients
                    </a>
                    <a href='exam.php?filter=with_rx' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($filter == 'with_rx' ? 'bg-[#B9D977] text-slate-900' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-file-prescription mr-2'></i> Has Prescription
                    </a>
                    <a href='exam.php?filter=no_rx' class='block px-4 py-2 text-xs font-medium rounded-lg transition-colors " . ($filter == 'no_rx' ? 'bg-orange-400 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800') . "'>
                        <i class='fa-solid fa-user-xmark mr-2'></i> No Prescription
                    </a>
                </div>
            </div>";
        }

        // ==========================================
        // 3. ADMIN ONLY
        // ==========================================
        if ($user_role === 'Admin') {
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
            <a href="login.php?action=cancel" class="block w-full text-center py-3 rounded-xl bg-slate-700/50 hover:bg-[#0097B2] text-slate-300 hover:text-white text-xs font-bold uppercase tracking-[0.15em] transition-all duration-300 shadow-inner">
                <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>Logout
            </a>
        </div>
    </div>
</aside>

<script>
    // LIVE CLOCK SCRIPT
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
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        
        document.getElementById('live-time').innerHTML = `${hours}:${minutes}:${seconds} <span class="text-[11px] text-[#0097B2] ml-1">${ampm}</span>`;
        document.getElementById('live-date').innerText = dateString;
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    // IDLE TIMEOUT SCRIPT
    let idleTimeout;
    const idleDuration = 120000; 
    function resetIdleTimer() {
        clearTimeout(idleTimeout);
        idleTimeout = setTimeout(() => { 
            // Store the current page URL before locking (including query parameters)
            const currentUrl = window.location.pathname.split('/').pop() + window.location.search;
            const pageToStore = currentUrl || 'directory.php';
            
            fetch('store_last_page.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'last_page=' + encodeURIComponent(pageToStore)
            }).then(() => {
                window.location.href = 'lock.php';
            }).catch(() => {
                // If fetch fails, still redirect to lock
                window.location.href = 'lock.php';
            });
        }, idleDuration);
    }
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => {
        window.addEventListener(evt, resetIdleTimer, false);
    });
    resetIdleTimer();
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #0097B2; }
</style>