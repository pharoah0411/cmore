<?php 
include('config.php'); 

if(isset($_POST['process_sale'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    $paid_amount = mysqli_real_escape_string($conn, $_POST['paid_amount']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $sale_date = date('Y-m-d H:i:s');

    // 1. Insert into SALES table
    $insert_sale = "INSERT INTO SALES (PATIENT_ID, STAFF_ID, SALE_DATE, TOTAL_AMOUNT, PAID_AMOUNT, PAYMENT_METHOD, PAYMENT_STATUS) 
                    VALUES ('$patient_id', '$staff_id', '$sale_date', '$total_amount', '$paid_amount', '$payment_method', '$payment_status')";
    
    if(mysqli_query($conn, $insert_sale)) {
        $sale_id = mysqli_insert_id($conn);

        // 2. Loop through dynamic product arrays
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        
        for($i = 0; $i < count($product_ids); $i++) {
            $pid = mysqli_real_escape_string($conn, $product_ids[$i]);
            $qty = mysqli_real_escape_string($conn, $quantities[$i]);

            // Only insert if a product was actually selected
            if(!empty($pid) && $qty > 0) {
                // Insert Item into SALES_ITEM
                mysqli_query($conn, "INSERT INTO SALES_ITEM (SALE_ID, PRODUCT_ID, QUANTITY) VALUES ('$sale_id', '$pid', '$qty')");
                
                // 3. Auto-deduct from Inventory
                mysqli_query($conn, "UPDATE PRODUCT SET STOCK_QUANTITY = STOCK_QUANTITY - $qty WHERE PRODUCT_ID = '$pid'");
            }
        }
        header("Location: sales.php?new_sale_id=$sale_id");
        exit();
    }
}

// Fetch products once to use in JavaScript template
$products_html = '<option value="" data-price="0" data-min="0">-- Choose Product --</option>';
$prod_res = mysqli_query($conn, "SELECT * FROM PRODUCT WHERE STOCK_QUANTITY > 0");
while($prod = mysqli_fetch_assoc($prod_res)) {
    $min = isset($prod['MINIMUM_PRICE']) ? $prod['MINIMUM_PRICE'] : 0;
    $products_html .= "<option value='{$prod['PRODUCT_ID']}' data-price='{$prod['UNIT_PRICE']}' data-min='{$min}'>{$prod['BRAND_NAME']} ({$prod['CATEGORY']}) - RM {$prod['UNIT_PRICE']}</option>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | New Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <a href="sales.php" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Sales
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Process New Sale</h1>
        </header>

        <form action="" method="POST" class="max-w-5xl space-y-8">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Transaction Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Select Patient</label>
                        <select name="patient_id" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold">
                            <option value="">-- Choose Patient --</option>
                            <?php 
                            $p_res = mysqli_query($conn, "SELECT PATIENT_ID, NAME FROM PATIENT ORDER BY NAME ASC");
                            while($p = mysqli_fetch_assoc($p_res)) echo "<option value='{$p['PATIENT_ID']}'>{$p['NAME']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Attending Staff (Optometrist)</label>
                        <select name="staff_id" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold">
                            <option value="">-- Choose Staff --</option>
                            <?php 
                            $u_res = mysqli_query($conn, "SELECT USER_ID, NAME FROM USER ORDER BY NAME ASC");
                            while($u = mysqli_fetch_assoc($u_res)) echo "<option value='{$u['USER_ID']}'>{$u['NAME']}</option>";
                            ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                <div class="flex justify-between items-center mb-8 border-b border-slate-50 pb-4">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Receipt Items</h3>
                    <button type="button" onclick="addReceiptItem()" class="text-[10px] font-black uppercase tracking-widest text-[#0097B2] bg-teal-50 px-4 py-2 rounded-xl hover:bg-[#0097B2] hover:text-white transition">
                        <i class="fa-solid fa-plus mr-1"></i> Add Item
                    </button>
                </div>

                <div id="items_container" class="space-y-6">
                    <div class="item-row relative bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-6 space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Select Product</label>
                                <select name="product_id[]" required onchange="updateRowPrice(this)" class="product-select w-full p-4 bg-white border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-sm">
                                    <?php echo $products_html; ?>
                                </select>
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Qty</label>
                                <input type="number" name="quantity[]" value="1" min="1" required oninput="calculateGrandTotal()" class="qty-input w-full p-4 bg-white border border-slate-100 rounded-xl outline-none text-center font-bold text-sm">
                            </div>

                            <div class="md:col-span-3 space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Unit Price (RM)</label>
                                <input type="number" step="0.01" name="custom_price[]" oninput="checkRowThreshold(this); calculateGrandTotal();" class="custom-price-input w-full p-4 bg-white border border-slate-200 rounded-xl text-slate-900 font-bold outline-none focus:border-[#0097B2] transition text-sm">
                            </div>

                            <div class="md:col-span-1 flex justify-center pb-2">
                                <button type="button" class="w-10 h-10 rounded-xl bg-red-50 text-red-300 cursor-not-allowed flex items-center justify-center" disabled>
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="warning-msg hidden mt-4 bg-red-50 border border-red-100 p-3 rounded-xl flex items-center space-x-3 transition-all duration-300">
                            <i class="fa-solid fa-lock text-red-500 text-xs ml-2"></i>
                            <p class="text-xs text-red-600 font-medium">
                                <strong>Below minimum!</strong> Minimum threshold is RM <span class="min-display font-bold"></span>.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-slate-900 p-10 rounded-[2.5rem] shadow-2xl text-white">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Payment Method</label>
                        <select name="payment_method" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl outline-none text-sm font-bold">
                            <option value="Cash" class="text-black">Cash</option>
                            <option value="Card" class="text-black">Credit/Debit Card</option>
                            <option value="Online Banking" class="text-black">Online Banking</option>
                            <option value="E-wallet" class="text-black">E-Wallet</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Status</label>
                        <select name="payment_status" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl outline-none text-sm font-bold">
                            <option value="Completed" class="text-black">Completed</option>
                            <option value="Partial" class="text-black">Partial / Deposit</option>
                            <option value="Pending" class="text-black">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Amount Paid (RM)</label>
                        <input type="number" step="0.01" name="paid_amount" required class="w-full p-3 bg-white/10 border border-white/20 rounded-xl outline-none font-mono font-bold">
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black uppercase text-[#B9D977] tracking-widest mb-1">Total Due</p>
                        <p class="text-3xl font-black font-mono">RM <span id="total_display">0.00</span></p>
                        <input type="hidden" name="total_amount" id="total_input" value="0">
                    </div>
                </div>
            </section>

            <div class="flex justify-end pt-4">
                <button type="submit" name="process_sale" class="bg-[#0097B2] text-white px-12 py-4 rounded-2xl font-bold shadow-lg hover:scale-105 transition-all">
                    Complete Transaction
                </button>
            </div>
        </form>
    </main>

    <script>
        // HTML Template for a new row
        const rowTemplate = `
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-6 space-y-2">
                    <select name="product_id[]" required onchange="updateRowPrice(this)" class="product-select w-full p-4 bg-white border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-sm">
                        <?php echo addslashes($products_html); ?>
                    </select>
                </div>
                <div class="md:col-span-2 space-y-2">
                    <input type="number" name="quantity[]" value="1" min="1" required oninput="calculateGrandTotal()" class="qty-input w-full p-4 bg-white border border-slate-100 rounded-xl outline-none text-center font-bold text-sm">
                </div>
                <div class="md:col-span-3 space-y-2">
                    <input type="number" step="0.01" name="custom_price[]" oninput="checkRowThreshold(this); calculateGrandTotal();" class="custom-price-input w-full p-4 bg-white border border-slate-200 rounded-xl text-slate-900 font-bold outline-none focus:border-[#0097B2] transition text-sm">
                </div>
                <div class="md:col-span-1 flex justify-center pb-2">
                    <button type="button" onclick="removeReceiptItem(this)" class="w-10 h-10 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="warning-msg hidden mt-4 bg-red-50 border border-red-100 p-3 rounded-xl flex items-center space-x-3 transition-all duration-300">
                <i class="fa-solid fa-lock text-red-500 text-xs ml-2"></i>
                <p class="text-xs text-red-600 font-medium">
                    <strong>Below minimum!</strong> Minimum threshold is RM <span class="min-display font-bold"></span>.
                </p>
            </div>
        `;

        function addReceiptItem() {
            const container = document.getElementById('items_container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row relative bg-slate-50 p-6 rounded-2xl border border-slate-100 mt-4';
            newRow.innerHTML = rowTemplate;
            container.appendChild(newRow);
        }

        function removeReceiptItem(btnElement) {
            const row = btnElement.closest('.item-row');
            row.remove();
            calculateGrandTotal();
        }

        // Triggered when a product dropdown is changed
        function updateRowPrice(selectElement) {
            const row = selectElement.closest('.item-row');
            const priceInput = row.querySelector('.custom-price-input');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            
            const defaultPrice = selectedOption.getAttribute('data-price');
            
            if (defaultPrice > 0) {
                priceInput.value = parseFloat(defaultPrice).toFixed(2);
            } else {
                priceInput.value = '';
            }
            
            checkRowThreshold(priceInput);
            calculateGrandTotal();
        }

        // Triggered when someone types a custom price
        function checkRowThreshold(priceInputElement) {
            const row = priceInputElement.closest('.item-row');
            const selectElement = row.querySelector('.product-select');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            
            const minPrice = parseFloat(selectedOption.getAttribute('data-min'));
            const currentPrice = parseFloat(priceInputElement.value);
            
            const warningDiv = row.querySelector('.warning-msg');
            const minDisplay = row.querySelector('.min-display');

            if (!isNaN(currentPrice) && minPrice > 0 && currentPrice < minPrice) {
                warningDiv.classList.remove('hidden');
                minDisplay.innerText = minPrice.toFixed(2);
                priceInputElement.classList.replace('border-slate-200', 'border-red-400');
            } else {
                warningDiv.classList.add('hidden');
                priceInputElement.classList.replace('border-red-400', 'border-slate-200');
            }
        }

        // Iterates through all rows and calculates total
        function calculateGrandTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const price = parseFloat(row.querySelector('.custom-price-input').value) || 0;
                const qty = parseInt(row.querySelector('.qty-input').value) || 0;
                total += (price * qty);
            });
            
            document.getElementById('total_display').innerText = total.toFixed(2);
            document.getElementById('total_input').value = total.toFixed(2);
        }
    </script>
</body>
</html>