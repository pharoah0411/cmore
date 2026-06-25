<?php if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } ?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<aside class="w-72 bg-[#0f172a] text-slate-300 flex flex-col fixed h-full shadow-2xl z-50 overflow-hidden">

    <!-- ambient brand glow -->
    <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-[#0097B2]/12 via-[#0097B2]/0 to-transparent"></div>

    <!-- ================= HEADER ================= -->
    <div class="relative px-6 pt-8 pb-5">
        <div class="flex flex-col items-center">
            <div class="relative">
                <div class="absolute -inset-6 -z-10 bg-[#0097B2]/20 blur-3xl rounded-full"></div>
                <img src="logo.png" alt="C-More Logo" class="relative w-56 h-auto drop-shadow-2xl">
            </div>
            <p class="mt-3 text-[10px] uppercase tracking-[0.35em] font-bold text-slate-500">Optometry Suite</p>
        </div>

        <!-- slim live status bar (replaces the old clock card) -->
        <div class="mt-6 flex items-center justify-between bg-white/[0.04] border border-white/10 rounded-2xl px-4 py-2.5 shadow-inner">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#B9D977] animate-pulse shadow-[0_0_8px_#B9D977]"></span>
                <span class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Online</span>
            </div>
            <div class="text-right leading-none">
                <div id="live-time" class="text-sm font-black text-white tracking-wider tabular-nums">00:00:00</div>
                <div id="live-date" class="text-[9px] uppercase tracking-[0.15em] font-bold text-[#B9D977] mt-1.5">LOADING...</div>
            </div>
        </div>
    </div>

    <!-- ================= NAV ================= -->
    <nav class="relative flex-1 px-5 pb-2 space-y-0.5 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <?php 
        $current = basename($_SERVER['PHP_SELF']); 
        $user_role = !empty($_SESSION['ROLE']) ? $_SESSION['ROLE'] : 'Unassigned'; 

        // Single nav link — icon chip + clean active state
        function nav_item($link, $icon, $label, $current) {
            $active = ($current == $link);
            $wrap = $active
                ? 'bg-gradient-to-r from-[#0097B2]/20 to-transparent text-white'
                : 'text-slate-400 hover:text-white hover:bg-white/[0.04]';
            $bar  = $active ? "<span class='absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-[#B9D977]'></span>" : '';
            $chip = $active
                ? 'bg-[#0097B2] text-white shadow-md shadow-[#0097B2]/30'
                : 'bg-white/[0.04] text-slate-400 group-hover:text-[#B9D977] group-hover:bg-white/[0.08]';

            echo "
            <a href='$link' class='relative flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group $wrap'>
                $bar
                <span class='flex items-center justify-center w-9 h-9 rounded-lg $chip transition-colors'><i class='$icon text-sm'></i></span>
                <span class='text-sm font-semibold tracking-wide'>$label</span>
            </a>";
        }

        // Collapsible parent button (kept visually identical to nav_item)
        function submenu_btn($id, $icon, $label, $is_active) {
            $wrap = $is_active
                ? 'bg-gradient-to-r from-[#0097B2]/20 to-transparent text-white'
                : 'text-slate-400 hover:text-white hover:bg-white/[0.04]';
            $bar  = $is_active ? "<span class='absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-[#B9D977]'></span>" : '';
            $chip = $is_active
                ? 'bg-[#0097B2] text-white shadow-md shadow-[#0097B2]/30'
                : 'bg-white/[0.04] text-slate-400 group-hover:text-[#B9D977] group-hover:bg-white/[0.08]';

            return "
            <button onclick=\"document.getElementById('$id').classList.toggle('hidden')\" class='relative w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 group $wrap'>
                $bar
                <span class='flex items-center gap-3'>
                    <span class='flex items-center justify-center w-9 h-9 rounded-lg $chip transition-colors'><i class='$icon text-sm'></i></span>
                    <span class='text-sm font-semibold tracking-wide'>$label</span>
                </span>
                <i class='fa-solid fa-chevron-down text-[10px] text-slate-500 group-hover:text-white transition-colors'></i>
            </button>";
        }

        // Section heading with a hairline divider
        function nav_section($label) {
            echo "
            <div class='flex items-center gap-3 px-3 pt-5 pb-2'>
                <span class='text-[10px] uppercase tracking-[0.25em] font-bold text-slate-500 select-none'>$label</span>
                <span class='flex-1 h-px bg-gradient-to-r from-slate-700/70 to-transparent'></span>
            </div>";
        }

        // Sub-link inside a submenu (active colour kept meaningful)
        function sub_link($href, $icon, $label, $active, $active_class) {
            $cls = $active ? $active_class : 'text-slate-400 hover:text-white hover:bg-white/[0.05]';
            echo "<a href='$href' class='flex items-center gap-2.5 px-3 py-2 text-xs font-medium rounded-lg transition-colors $cls'><i class='$icon text-[11px] w-4 text-center'></i> $label</a>";
        }

        /* ---------------------------------- MAIN ---------------------------------- */
        nav_item('directory.php', 'fa-solid fa-table-cells-large', 'Dashboard', $current);

        /* --------------------------------- CLINIC --------------------------------- */
        nav_section('Clinic');
        nav_item('patients.php', 'fa-solid fa-user-group', 'Patients', $current);

        // Appointments Submenu
        $is_appt = ($current == 'appointment.php');
        $appt_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        echo "<div class='relative'>" . submenu_btn('appt-submenu', 'fa-solid fa-calendar-check', 'Appointments', $is_appt) . "
            <div id='appt-submenu' class='" . ($is_appt ? '' : 'hidden') . " mt-1 ml-5 pl-3 border-l border-slate-700/60 space-y-0.5 py-1'>";
                sub_link('appointment.php',                'fa-solid fa-calendar-days',     'All Appointments', $is_appt && $appt_status == 'all',       'bg-[#0097B2] text-white');
                sub_link('appointment.php?status=Pending',   'fa-solid fa-clock-rotate-left', 'Pending',          $is_appt && $appt_status == 'Pending',   'bg-amber-500 text-white');
                sub_link('appointment.php?status=Completed', 'fa-solid fa-circle-check',      'Completed',        $is_appt && $appt_status == 'Completed', 'bg-[#B9D977] text-slate-900');
                sub_link('appointment.php?status=Cancelled', 'fa-solid fa-circle-xmark',      'Cancelled',        $is_appt && $appt_status == 'Cancelled', 'bg-red-500 text-white');
        echo "  </div>
        </div>";

        // Clinical Exams (Optometrist & Admin only)
        if ($user_role === 'Admin' || $user_role === 'Optometrist') {
            $is_exam = ($current == 'exam.php');
            $exam_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            echo "<div class='relative'>" . submenu_btn('exam-submenu', 'fa-solid fa-eye', 'Clinical Exams', $is_exam) . "
                <div id='exam-submenu' class='" . ($is_exam ? '' : 'hidden') . " mt-1 ml-5 pl-3 border-l border-slate-700/60 space-y-0.5 py-1'>";
                    sub_link('exam.php',                'fa-solid fa-users',             'All Patients',     $is_exam && $exam_filter == 'all',     'bg-[#0097B2] text-white');
                    sub_link('exam.php?filter=with_rx', 'fa-solid fa-file-prescription', 'Has Prescription', $is_exam && $exam_filter == 'with_rx', 'bg-[#B9D977] text-slate-900');
                    sub_link('exam.php?filter=no_rx',   'fa-solid fa-user-xmark',        'No Prescription',  $is_exam && $exam_filter == 'no_rx',   'bg-orange-400 text-white');
            echo "  </div>
            </div>";
        }

        /* ------------------------------ SALES & STOCK ----------------------------- */
        nav_section('Sales &amp; Stock');

        // Sales Submenu
        $is_sales = ($current == 'sales.php');
        $sales_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        echo "<div class='relative'>" . submenu_btn('sales-submenu', 'fa-solid fa-receipt', 'Sales & Invoices', $is_sales) . "
            <div id='sales-submenu' class='" . ($is_sales ? '' : 'hidden') . " mt-1 ml-5 pl-3 border-l border-slate-700/60 space-y-0.5 py-1'>";
                sub_link('sales.php',                  'fa-solid fa-file-invoice-dollar', 'All Sales',    $is_sales && $sales_status == 'all',       'bg-[#0097B2] text-white');
                sub_link('sales.php?status=Partial',   'fa-solid fa-circle-half-stroke',  'Partial Paid', $is_sales && $sales_status == 'Partial',   'bg-amber-500 text-white');
                sub_link('sales.php?status=Completed', 'fa-solid fa-check-double',        'Completed',    $is_sales && $sales_status == 'Completed', 'bg-[#B9D977] text-slate-900');
        echo "  </div>
        </div>";

        // Inventory Submenu
        $is_inv = ($current == 'inventory.php');
        $inv_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $inv_cat = isset($_GET['category']) ? $_GET['category'] : 'all';
        echo "<div class='relative'>" . submenu_btn('inv-submenu', 'fa-solid fa-box-archive', 'Inventory', $is_inv) . "
            <div id='inv-submenu' class='" . ($is_inv ? '' : 'hidden') . " mt-1 ml-5 pl-3 border-l border-slate-700/60 space-y-0.5 py-1'>";
                sub_link('inventory.php', 'fa-solid fa-boxes-stacked', 'All Products', $is_inv && $inv_filter == 'all' && $inv_cat == 'all', 'bg-[#0097B2] text-white');
                echo "<p class='text-[9px] uppercase tracking-widest text-slate-500 font-bold px-3 pt-3 pb-1'>Image Filters</p>";
                sub_link('inventory.php?filter=with_image', 'fa-solid fa-image',       'With Picture',    $is_inv && $inv_filter == 'with_image', 'bg-[#B9D977] text-slate-900');
                sub_link('inventory.php?filter=no_image',   'fa-solid fa-image-slash', 'Without Picture', $is_inv && $inv_filter == 'no_image',   'bg-slate-700 text-white');
                echo "<p class='text-[9px] uppercase tracking-widest text-slate-500 font-bold px-3 pt-3 pb-1'>Categories</p>";
                sub_link('inventory.php?category=Frames',         'fa-solid fa-glasses', 'Frames',         $is_inv && $inv_cat == 'Frames',         'bg-[#0097B2] text-white');
                sub_link('inventory.php?category=Contact Lenses', 'fa-solid fa-eye',     'Contact Lenses', $is_inv && $inv_cat == 'Contact Lenses', 'bg-[#0097B2] text-white');
        echo "  </div>
        </div>";

        /* -------------------------------- INSIGHTS -------------------------------- */
        nav_section('Insights');

        $is_reports = ($current == 'reports.php' || $current == 'analytics.php');
        echo "<div class='relative'>" . submenu_btn('reports-submenu', 'fa-solid fa-chart-line', 'Reports & Analytics', $is_reports) . "
            <div id='reports-submenu' class='" . ($is_reports ? '' : 'hidden') . " mt-1 ml-5 pl-3 border-l border-slate-700/60 space-y-0.5 py-1'>";
                sub_link('reports.php',   'fa-solid fa-file-lines', 'Reports',   $current == 'reports.php',   'bg-[#0097B2] text-white');
                sub_link('analytics.php', 'fa-solid fa-chart-pie',  'Analytics', $current == 'analytics.php', 'bg-[#B9D977] text-slate-900');
        echo "  </div>
        </div>";

        /* --------------------------------- SYSTEM --------------------------------- */
        nav_section('System');

        // Database Backup available to all staff
        nav_item('backup.php', 'fa-solid fa-database', 'Database Backup', $current);

        // Admin Only links
        if ($user_role === 'Admin') {
            nav_item('staff.php', 'fa-solid fa-user-tie', 'Staff Directory', $current);
            nav_item('audit_trail.php', 'fa-solid fa-shield-halved', 'Audit Trail', $current);
        }
        ?>
    </nav>

    <!-- ================= FOOTER ================= -->
    <div class="relative p-4">
        <div class="rounded-2xl bg-white/[0.04] border border-white/10 p-4 shadow-inner">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br from-[#0097B2] to-[#B9D977] flex items-center justify-center text-slate-900 font-black text-sm shadow-lg">
                    <?php echo isset($_SESSION['NAME']) ? strtoupper(substr($_SESSION['NAME'], 0, 2)) : 'UT'; ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['NAME'] ?? 'Unknown User'); ?></p>
                    <p class="text-[9px] text-[#B9D977] uppercase font-black tracking-[0.15em] mt-0.5"><i class="fa-solid fa-circle-check text-[8px] mr-1"></i><?php echo htmlspecialchars($user_role); ?></p>
                </div>
            </div>
            <a href="auto_backup.php?logout=true" class="mt-4 group flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-slate-700/40 hover:bg-[#0097B2] text-slate-300 hover:text-white text-xs font-bold uppercase tracking-[0.15em] transition-all duration-300">
                <i class="fa-solid fa-cloud-arrow-up group-hover:-translate-y-0.5 transition-transform"></i> Logout & Backup
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
        document.getElementById('live-time').innerHTML = `${hours}:${minutes}:${seconds} <span class="text-[11px] text-[#0097B2] ml-0.5">${ampm}</span>`;
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

    // ---- Invisible Auto-Backup Beacon when Browser Tab is Closed ----
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