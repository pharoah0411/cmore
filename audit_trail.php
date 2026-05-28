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
// FETCH AUDIT LOGS
// ==========================================
// Join with the user table to get the staff member's actual name
$query = "SELECT a.*, u.NAME 
          FROM audit_log a 
          LEFT JOIN user u ON a.USER_ID = u.USER_ID 
          ORDER BY a.CREATED_AT DESC 
          LIMIT 200"; // Fetch the latest 200 actions
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
        
        <div class="mb-10 flex justify-between items-end">
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
                                    No audit logs found yet. System is quiet!
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