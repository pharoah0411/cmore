<aside class="w-72 bg-[#0f172a] text-slate-300 flex flex-col fixed h-full shadow-2xl z-50">
    <div class="p-10 flex flex-col items-center">
        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-[#0097B2] to-[#B9D977] rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
            <img src="logo.png" alt="C-More Logo" class="relative w-44 h-auto drop-shadow-2xl">
        </div>
        <div class="mt-4 flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-[#B9D977] animate-pulse"></span>
            <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-500">Clinical Suite v1.0</p>
        </div>
    </div>
    
    <nav class="flex-1 px-6 space-y-1">
        <?php 
        $current = basename($_SERVER['PHP_SELF']); 
        function nav_item($link, $icon, $label, $current) {
            $active = ($current == $link);
            $class = $active 
                ? 'bg-slate-800/50 text-white border-l-4 border-[#B9D977] shadow-inner' 
                : 'hover:bg-slate-800/30 hover:text-white border-l-4 border-transparent';
            
            echo "
            <a href='$link' class='flex items-center space-x-4 p-4 rounded-r-xl transition-all duration-200 group $class'>
                <i class='$icon text-lg " . ($active ? 'text-[#0097B2]' : 'text-slate-500 group-hover:text-[#B9D977]') . "'></i>
                <span class='text-sm font-semibold tracking-wide'>$label</span>
            </a>";
        }

        nav_item('directory.php', 'fa-solid fa-grid-2', 'Dashboard', $current);
        nav_item('patients.php', 'fa-solid fa-user-group', 'Patients', $current);
        nav_item('appointment.php', 'fa-solid fa-calendar-check', 'Appointments', $current);
        nav_item('exam.php', 'fa-solid fa-microscope', 'Eye Exams', $current);
        nav_item('inventory.php', 'fa-solid fa-box-archive', 'Inventory', $current);
        ?>
    </nav>

    <div class="p-6 m-4 rounded-2xl bg-slate-800/40 border border-slate-700/50">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-[#0097B2] to-[#B9D977] flex items-center justify-center text-white font-black shadow-lg">
                UT
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold text-white truncate">UTeM Admin</p>
                <p class="text-[9px] text-slate-500 uppercase font-black">System Developer</p>
            </div>
        </div>
    </div>
</aside>