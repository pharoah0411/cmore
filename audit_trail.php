<?php
include('config.php');

// ==========================================
// ACCESS CONTROL
// ==========================================
// Kick out anyone who is not an Admin
if (!isset($_SESSION['ROLE']) || $_SESSION['ROLE'] !== 'Admin') {
    // Log their attempt to snoop!
    systemLog($conn, 'Attempted unauthorized access to Audit Trail');
    header("Location: directory.php");
    exit();
}

// ==========================================
// SEARCH & FILTER LOGIC
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';

$where_clauses = [];

// If user typed in the search bar (searches name or action)
if (!empty($search)) {
    $where_clauses[] = "(u.NAME LIKE '%$search%' OR a.ACTION LIKE '%$search%')";
}

// If user selected a specific table filter
if (!empty($filter)) {
    $where_clauses[] = "a.TABLE_NAME = '$filter'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch distinct tables for the filter dropdown
$tables_query = "SELECT DISTINCT TABLE_NAME FROM audit_log WHERE TABLE_NAME IS NOT NULL AND TABLE_NAME != '' ORDER BY TABLE_NAME ASC";
$tables_result = mysqli_query($conn, $tables_query);

// ==========================================
// FETCH AUDIT LOGS
// ==========================================
// Join with the user table to get the staff member's actual name
$query = "SELECT a.*, u.NAME 
          FROM audit_log a 
          LEFT JOIN user u ON a.USER_ID = u.USER_ID 
          $where_sql
          ORDER BY a.CREATED_AT DESC 
          LIMIT 200"; // Fetch the latest 200 actions matching criteria
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <!-- Include your sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content Area -->
    <main class="flex-1 ml-72 h-screen overflow-y-auto p-10">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">System Audit Trail</h1>
                <p class="text-slate-500 font-medium mt-1">Monitor staff actions and system changes in real-time.</p>
            </div>
            
            <!-- Security Badge -->
            <div class="bg-white px-5 py-3 rounded-2xl shadow-sm border border-slate-100 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400">Security Level</p>
                    <p class="text-sm font-bold text-slate-800">Admin Only Access</p>
                </div>
            </div>
        </div>

        <!-- Search & Filter Form -->
        <div class="mb-8">
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-4xl">
                <!-- Dropdown Filter -->
                <div class="relative w-full md:w-1/3">
                    <select name="filter" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700 cursor-pointer">
                        <option value="">All Tables</option>
                        <?php while($t = mysqli_fetch_assoc($tables_result)): ?>
                            <option value="<?php echo htmlspecialchars($t['TABLE_NAME']); ?>" <?php echo $filter === $t['TABLE_NAME'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($t['TABLE_NAME'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
                
                <!-- Search Input -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search by Staff Name or Action..." value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all font-medium text-slate-700">
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-bold hover:bg-[#0097B2] transition shadow-lg shrink-0">
                    <i class="fa-solid fa-filter mr-2"></i> Filter Logs
                </button>
                
                <!-- Clear Button (Only shows if search or filter is active) -->
                <?php if(!empty($search) || !empty($filter)): ?>
                <a href="audit_trail.php" class="bg-white border-2 border-slate-200 text-slate-600 px-8 py-4 rounded-[1.5rem] font-bold hover:bg-slate-50 hover:text-slate-900 transition shadow-sm shrink-0 flex items-center justify-center">
                    Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Audit Table -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="p-5 font-black">Timestamp</th>
                            <th class="p-5 font-black">Staff Member</th>
                            <th class="p-5 font-black">Action Performed</th>
                            <th class="p-5 font-black">Target Table</th>
                            <th class="p-5 font-black">Record ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-5 text-slate-500 whitespace-nowrap">
                                        <!-- Format date nicely -->
                                        <?php echo date('M d, Y - h:i A', strtotime($row['CREATED_AT'])); ?>
                                    </td>
                                    <td class="p-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-lg bg-[#0097B2]/10 text-[#0097B2] flex items-center justify-center font-bold text-xs">
                                                <?php echo $row['NAME'] ? strtoupper(substr($row['NAME'], 0, 2)) : '??'; ?>
                                            </div>
                                            <span class="font-bold text-slate-800">
                                                <?php echo htmlspecialchars($row['NAME'] ?? 'System / Unknown'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-5">
                                        <span class="bg-[#B9D977]/20 text-slate-800 py-1 px-3 rounded-lg font-semibold text-xs border border-[#B9D977]/30">
                                            <?php echo htmlspecialchars($row['ACTION']); ?>
                                        </span>
                                    </td>
                                    <td class="p-5 text-slate-500 font-mono text-xs">
                                        <?php echo $row['TABLE_NAME'] ? htmlspecialchars($row['TABLE_NAME']) : '<span class="text-slate-300">N/A</span>'; ?>
                                    </td>
                                    <td class="p-5 text-slate-500 font-mono text-xs">
                                        <?php echo $row['RECORD_ID'] ? '#' . htmlspecialchars($row['RECORD_ID']) : '<span class="text-slate-300">N/A</span>'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-10 text-center text-slate-400 font-bold">
                                    <i class="fa-solid fa-ghost text-3xl mb-3 opacity-50 block"></i>
                                    No audit logs found for that search. System is quiet!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>