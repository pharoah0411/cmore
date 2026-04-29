<?php 
include('config.php'); 
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$show_rx = isset($_GET['rx']) && $_GET['rx'] == '1';

// Fetch Sale & Patient
$sql = "SELECT s.*, p.PATIENT_ID, p.NAME as P_NAME, p.PHONE_NUMBER, u.NAME as S_NAME 
        FROM SALES s JOIN PATIENT p ON s.PATIENT_ID = p.PATIENT_ID JOIN USER u ON s.STAFF_ID = u.USER_ID 
        WHERE s.SALE_ID = '$id'";
$sale = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Ensure balance never prints as a negative number
$balance = max(0, $sale['TOTAL_AMOUNT'] - $sale['PAID_AMOUNT']);

// Fetch latest Rx details if requested
$has_rx = false;
$rx_data = [];
if($show_rx) {
    $rx_sql = "SELECT * FROM EYE_EXAMINATION WHERE PATIENT_ID = '{$sale['PATIENT_ID']}' ORDER BY EXAM_DATE DESC LIMIT 1";
    $rx_res = mysqli_query($conn, $rx_sql);
    if(mysqli_num_rows($rx_res) > 0) {
        $has_rx = true;
        $rx_data = mysqli_fetch_assoc($rx_res);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #TXN-<?php echo $id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Force compact printing */
        @media print {
            @page { margin: 0.5cm; } /* Drastically reduce browser margins */
            body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center py-4" onload="window.print()">

    <div class="bg-white p-6 w-full max-w-lg border border-gray-200 shadow-sm relative text-sm">
        
        <div class="flex justify-between items-center border-b-2 border-gray-800 pb-4 mb-4">
            <div>
                <img src="logo.png" alt="C-More Logo" class="h-10 w-auto mb-1">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">C-More Optometry</p>
                <p class="text-[10px] text-gray-400">123 Clinical Way, Melaka | Tel: +60 12-345 6789</p>
            </div>
            <div class="text-right">
                <h1 class="text-xl font-black text-gray-800 tracking-tight">RECEIPT</h1>
                <p class="text-xs font-bold text-gray-500">#TXN-<?php echo $sale['SALE_ID']; ?></p>
                <p class="text-[10px] text-gray-400"><?php echo date('d M Y, h:i A', strtotime($sale['SALE_DATE'])); ?></p>
            </div>
        </div>

        <div class="mb-4 flex justify-between bg-gray-50 p-3 rounded border border-gray-100">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Bill To</p>
                <p class="font-bold text-gray-800 text-sm"><?php echo $sale['P_NAME']; ?></p>
                <p class="text-[11px] text-gray-600"><i class="fa-solid fa-phone text-[9px] mr-1 text-gray-400"></i> <?php echo $sale['PHONE_NUMBER']; ?></p>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Attending Staff</p>
                <p class="font-bold text-gray-800 text-sm"><?php echo $sale['S_NAME']; ?></p>
            </div>
        </div>

        <table class="w-full text-left mb-4">
            <thead>
                <tr class="border-b border-gray-400 text-xs">
                    <th class="py-1.5 font-bold text-gray-800">Description</th>
                    <th class="py-1.5 text-center font-bold text-gray-800">Qty</th>
                    <th class="py-1.5 text-right font-bold text-gray-800">Price</th>
                    <th class="py-1.5 text-right font-bold text-gray-800">Total</th>
                </tr>
            </thead>
            <tbody class="text-xs border-b border-gray-200">
                <?php
                $items = mysqli_query($conn, "SELECT si.QUANTITY, p.BRAND_NAME, p.UNIT_PRICE FROM SALES_ITEM si JOIN PRODUCT p ON si.PRODUCT_ID = p.PRODUCT_ID WHERE si.SALE_ID = '$id'");
                while($item = mysqli_fetch_assoc($items)):
                ?>
                <tr>
                    <td class="py-1.5 font-medium text-gray-700"><?php echo $item['BRAND_NAME']; ?></td>
                    <td class="py-1.5 text-center text-gray-600"><?php echo $item['QUANTITY']; ?></td>
                    <td class="py-1.5 text-right text-gray-600"><?php echo number_format($item['UNIT_PRICE'], 2); ?></td>
                    <td class="py-1.5 text-right font-bold text-gray-800"><?php echo number_format($item['QUANTITY'] * $item['UNIT_PRICE'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="flex justify-end mb-4">
            <div class="w-2/3 md:w-1/2 space-y-1 text-xs">
                <div class="flex justify-between"><p class="font-bold text-gray-500">Grand Total:</p><p class="font-bold text-gray-800">RM <?php echo number_format($sale['TOTAL_AMOUNT'], 2); ?></p></div>
                <div class="flex justify-between"><p class="font-bold text-gray-500">Paid:</p><p class="font-bold text-green-600">- RM <?php echo number_format($sale['PAID_AMOUNT'], 2); ?></p></div>
                <div class="flex justify-between pt-1 border-t border-gray-200"><p class="font-black text-gray-800">Balance Due:</p><p class="font-black text-gray-800">RM <?php echo number_format($balance, 2); ?></p></div>
            </div>
        </div>

        <?php if($show_rx): ?>
        <div class="border-t border-dashed border-gray-300 pt-3 mt-3">
            <p class="text-[9px] font-black uppercase tracking-widest text-[#0097B2] mb-2"><i class="fa-solid fa-eye mr-1"></i> Patient Prescription Details</p>
            
            <?php if($has_rx): ?>
            <table class="w-full text-center text-[11px] mb-1 border border-gray-200 rounded overflow-hidden">
                <thead class="bg-gray-50 font-bold text-gray-700">
                    <tr>
                        <th class="py-1 px-2 border-b border-gray-200">Eye</th>
                        <th class="py-1 px-2 border-b border-gray-200">SPH</th>
                        <th class="py-1 px-2 border-b border-gray-200">CYL</th>
                        <th class="py-1 px-2 border-b border-gray-200">AXIS</th>
                        <th class="py-1 px-2 border-b border-gray-200">ADD</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 font-mono">
                    <tr class="border-b border-gray-100">
                        <td class="py-1 px-2 font-bold border-r border-gray-100">RE</td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['RE_SPH']) ? $rx_data['RE_SPH'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['RE_CYL']) ? $rx_data['RE_CYL'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['RE_AXIS']) ? $rx_data['RE_AXIS'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['RE_ADD']) ? $rx_data['RE_ADD'] : '-'; ?></td>
                    </tr>
                    <tr>
                        <td class="py-1 px-2 font-bold border-r border-gray-100">LE</td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['LE_SPH']) ? $rx_data['LE_SPH'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['LE_CYL']) ? $rx_data['LE_CYL'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['LE_AXIS']) ? $rx_data['LE_AXIS'] : '-'; ?></td>
                        <td class="py-1 px-2"><?php echo !empty($rx_data['LE_ADD']) ? $rx_data['LE_ADD'] : '-'; ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if(!empty($rx_data['PD'])): ?>
                <p class="text-[10px] text-gray-700 mt-1 font-bold"><span class="uppercase tracking-widest text-gray-400 text-[8px]">PD:</span> <?php echo $rx_data['PD']; ?></p>
            <?php endif; ?>
            
            <?php else: ?>
                <p class="text-xs text-gray-500 italic bg-gray-50 p-2 rounded">No clinical examination recorded yet.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="mt-4 text-center text-[10px] text-gray-400 font-medium">
            <p>Thank you for choosing C-More Optometry!</p>
            <p>Goods sold are non-refundable.</p>
        </div>
    </div>
</body>
</html>