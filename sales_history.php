<?php 
include('config.php'); 
$patient_id = $_GET['patient_id'];
$p_res = mysqli_query($conn, "SELECT NAME FROM PATIENT WHERE PATIENT_ID = $patient_id");
$p_name = mysqli_fetch_assoc($p_res)['NAME'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Sales History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    <?php include('sidebar.php'); ?>
    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Sales History</h1>
            <p class="text-slate-500 font-medium mt-1">Transaction logs for <span class="text-[#0097B2] font-black"><?php echo $p_name; ?></span></p>
        </header>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Date</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Transaction ID</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400 text-right">Amount</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    // Assuming a SALES table exists with PATIENT_ID, SALE_DATE, and TOTAL_AMOUNT
                    $sql = "SELECT * FROM SALES WHERE PATIENT_ID = $patient_id ORDER BY SALE_DATE DESC";
                    $res = mysqli_query($conn, $sql);
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)):
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-6 font-bold text-slate-700"><?php echo date('d M Y', strtotime($row['SALE_DATE'])); ?></td>
                        <td class="p-6 text-slate-400 font-mono text-sm">#TXN-<?php echo $row['SALE_ID']; ?></td>
                        <td class="p-6 text-right font-black text-[#0097B2]">RM <?php echo number_format($row['TOTAL_AMOUNT'], 2); ?></td>
                        <td class="p-6 text-center">
                            <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-[#0097B2] transition">
                                <i class="fa-solid fa-file-invoice"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="p-20 text-center text-slate-400 italic font-bold">No purchase history found for this patient.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>