<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Sales Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12 relative">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Sales & Billing</h1>
                <p class="text-slate-500 font-medium mt-1">Manage patient transactions and payment statuses.</p>
            </div>
            <a href="sales_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all duration-300">
                <i class="fa-solid fa-receipt mr-2"></i> Create New Sale
            </a>
        </header>

        <?php if(isset($_GET['new_sale_id'])): $sid = htmlspecialchars($_GET['new_sale_id']); ?>
        <div id="receiptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl max-w-md w-full text-center border border-slate-100 animate-fade-in-up">
                <div class="w-20 h-20 bg-teal-50 text-[#0097B2] rounded-full flex items-center justify-center mx-auto mb-6 text-3xl shadow-inner">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-800 mb-2 tracking-tight">Sale Completed!</h2>
                <p class="text-slate-500 text-sm mb-8 font-medium">Would you like to print the receipt for this transaction?</p>
                <div class="space-y-3">
                    <a href="receipt.php?id=<?php echo $sid; ?>&rx=1" target="_blank" onclick="closeModal()" class="flex justify-center items-center w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                        <i class="fa-solid fa-file-prescription mr-2"></i> Print with Rx
                    </a>
                    <a href="receipt.php?id=<?php echo $sid; ?>&rx=0" target="_blank" onclick="closeModal()" class="flex justify-center items-center w-full bg-slate-900 text-white py-4 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all">
                        <i class="fa-solid fa-receipt mr-2"></i> Print without Rx
                    </a>
                    <button onclick="closeModal()" class="w-full text-slate-400 font-bold py-4 hover:text-slate-600 transition-colors uppercase tracking-widest text-[10px]">
                        Cancel / Don't Print
                    </button>
                </div>
            </div>
        </div>
        <script>function closeModal() { document.getElementById('receiptModal').style.display = 'none'; }</script>
        <?php endif; ?>

        <div class="mb-8">
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-4xl">
                
                <div class="relative w-full md:w-1/3">
                    <select name="status" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700">
                        <option value="">All Statuses</option>
                        <option value="Completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Partial" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Partial') ? 'selected' : ''; ?>>Partial / Deposit</option>
                        <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>

                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search Patient Name or TXN ID..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all font-medium text-slate-700">
                </div>
                
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-bold hover:bg-[#0097B2] transition shadow-lg shrink-0">
                    <i class="fa-solid fa-filter mr-2"></i> Search
                </button>
                
                <?php if(!empty($_GET['search']) || !empty($_GET['status'])): ?>
                <a href="sales.php" class="bg-red-50 text-red-500 px-6 py-4 rounded-[1.5rem] font-bold hover:bg-red-500 hover:text-white transition shadow-sm flex items-center shrink-0">
                    <i class="fa-solid fa-times"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left table-fixed">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="w-[15%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Date & TXN</th>
                        <th class="w-[25%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Patient</th>
                        <th class="w-[20%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Staff</th>
                        <th class="w-[25%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Amount & Status</th>
                        <th class="w-[15%] p-5 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    // Build Search & Filter Logic
                    $where_clauses = [];
                    
                    if (!empty($_GET['status'])) {
                        $status = mysqli_real_escape_string($conn, $_GET['status']);
                        $where_clauses[] = "s.PAYMENT_STATUS = '$status'";
                    }
                    
                    if (!empty($_GET['search'])) {
                        $search = mysqli_real_escape_string($conn, $_GET['search']);
                        // Clean TXN- prefix if user typed it
                        $clean_search = str_ireplace(['#TXN-', 'TXN-'], '', $search);
                        $where_clauses[] = "(p.NAME LIKE '%$search%' OR s.SALE_ID LIKE '%$clean_search%')";
                    }

                    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

                    // NOTE: Changed to LEFT JOIN so Walk-ins (NULL Patient ID) still show up in the table. 
                    // COALESCE replaces NULL names with 'Walk-in Customer'.
                    $sql = "SELECT s.*, COALESCE(p.NAME, 'Walk-in Customer') as PATIENT_NAME, u.NAME as STAFF_NAME 
                            FROM SALES s 
                            LEFT JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID 
                            LEFT JOIN USER u ON s.STAFF_ID = u.USER_ID 
                            $where_sql
                            ORDER BY s.SALE_DATE DESC";
                            
                    $res = mysqli_query($conn, $sql);
                    
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)): 
                            $status_color = $row['PAYMENT_STATUS'] == 'Completed' ? 'text-green-600 bg-green-50' : 
                                          ($row['PAYMENT_STATUS'] == 'Partial' ? 'text-orange-600 bg-orange-50' : 'text-red-600 bg-red-50');
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-5">
                            <p class="font-bold text-slate-800 text-sm"><?php echo date('d M Y', strtotime($row['SALE_DATE'])); ?></p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">#TXN-<?php echo $row['SALE_ID']; ?></p>
                        </td>
                        <td class="p-5 font-bold text-slate-700 truncate">
                            <?php 
                                echo $row['PATIENT_NAME']; 
                                if($row['PATIENT_NAME'] == 'Walk-in Customer') echo ' <i class="fa-solid fa-person-walking text-slate-400 ml-1"></i>';
                            ?>
                        </td>
                        <td class="p-5 text-sm text-slate-500 font-medium truncate"><i class="fa-solid fa-user-tag mr-2 text-[#0097B2]"></i><?php echo $row['STAFF_NAME']; ?></td>
                        <td class="p-5 text-center">
                            <p class="font-black text-[#0097B2] text-lg">RM <?php echo number_format($row['TOTAL_AMOUNT'], 2); ?></p>
                            <span class="inline-block px-2 py-1 mt-1 rounded text-[9px] font-black uppercase tracking-widest <?php echo $status_color; ?>">
                                <?php echo $row['PAYMENT_STATUS']; ?>
                            </span>
                        </td>
                        <td class="p-5 text-center">
                            <a href="sales_view.php?id=<?php echo $row['SALE_ID']; ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-[#0097B2] hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-eye text-sm"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="p-12 text-center italic text-slate-400 font-bold">No sales transactions found matching your search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>