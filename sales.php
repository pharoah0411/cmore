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
                    $sql = "SELECT s.*, p.NAME as PATIENT_NAME, u.NAME as STAFF_NAME 
                            FROM SALES s 
                            JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID 
                            JOIN USER u ON s.STAFF_ID = u.USER_ID 
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
                        <td class="p-5 font-bold text-slate-700 truncate"><?php echo $row['PATIENT_NAME']; ?></td>
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
                    <tr><td colspan="5" class="p-12 text-center italic text-slate-400">No sales transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>