<?php 
include('config.php'); 

$page_error = ""; // To catch and display database errors

// ==========================================
// AJAX Handler for New Patient Pop-up
// ==========================================
if(isset($_POST['ajax_add_patient'])) {
    error_reporting(0); // Prevents hidden PHP warnings from breaking the JSON
    ob_clean();
    header('Content-Type: application/json');
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = mysqli_real_escape_string($conn, $_POST['ic_number']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // FOOLPROOF BYPASS: If IC is blank, give it a random unique string to satisfy strict database rules
    if(empty($ic)) {
        $ic_val = "'NO-IC-" . rand(10000, 99999) . "'";
    } else {
        $ic_val = "'$ic'";
    }
    
    $sql = "INSERT INTO PATIENT (NAME, IC_NUMBER, PHONE_NUMBER, ADDRESS, CONNECTION_RELATIONSHIP, FOLLOW_UP_INTERVAL, COMPLAINTS, REGISTRATION_DATE) 
            VALUES ('$name', $ic_val, '$phone', 'Walk-in / Unrecorded', 'None', 'Not Set', 'None', NOW())";
            
    if(mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode(['status' => 'success', 'id' => $new_id, 'name' => $name, 'phone' => $phone]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit(); 
}
// ==========================================

if(isset($_POST['process_sale'])) {
    $patient_id_raw = mysqli_real_escape_string($conn, $_POST['patient_id']);
    // Handle empty or walk-in patient selection as NULL for walk-in customers
    $patient_id = (!empty($patient_id_raw) && $patient_id_raw !== 'walkin') ? "'$patient_id_raw'" : "NULL"; 
    
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    $paid_amount = floatval($_POST['paid_amount']);
    $total_amount = floatval($_POST['total_amount']);
    
    // SERVER-SIDE CAP: Ensure paid amount never exceeds total amount
    if ($paid_amount > $total_amount) {
        $paid_amount = $total_amount;
    }
    
    if ($paid_amount >= $total_amount && $total_amount > 0) {
        $payment_status = 'Completed';
    } elseif ($paid_amount > 0 && $paid_amount < $total_amount) {
        $payment_status = 'Partial';
    } else {
        $payment_status = 'Pending';
    }

    $sale_date = date('Y-m-d H:i:s');

    // Retrieve products and quantities from POST for server-side validation
    $product_ids = isset($_POST['product_id']) ? $_POST['product_id'] : [];
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];

    $invalid_items = [];
    for($i = 0; $i < count($product_ids); $i++) {
        $pid = mysqli_real_escape_string($conn, $product_ids[$i]);
        $qty = intval($quantities[$i]);
        if(empty($pid) || $qty <= 0) continue;

        $prod_check = mysqli_query($conn, "SELECT STOCK_QUANTITY, BRAND_NAME FROM PRODUCT WHERE PRODUCT_ID = '$pid'");
        $prod_row = mysqli_fetch_assoc($prod_check);
        $available = $prod_row ? intval($prod_row['STOCK_QUANTITY']) : 0;
        $brand = $prod_row ? $prod_row['BRAND_NAME'] : ('ID ' . $pid);
        if ($qty > $available) {
            $invalid_items[] = "$brand: requested $qty, available $available";
        }
    }

    if (!empty($invalid_items)) {
        // Do not create the sale; show an error on the page
        $page_error = 'Insufficient stock for: ' . implode('; ', $invalid_items);
    } else {
        // Proceed to insert sale
        $insert_sale = "INSERT INTO SALES (PATIENT_ID, STAFF_ID, SALE_DATE, TOTAL_AMOUNT, PAID_AMOUNT, PAYMENT_METHOD, PAYMENT_STATUS) 
                        VALUES ($patient_id, '$staff_id', '$sale_date', '$total_amount', '$paid_amount', '$payment_method', '$payment_status')";
        
        if(mysqli_query($conn, $insert_sale)) {
            $sale_id = mysqli_insert_id($conn);

            for($i = 0; $i < count($product_ids); $i++) {
                $pid = mysqli_real_escape_string($conn, $product_ids[$i]);
                $qty = intval($quantities[$i]);

                if(!empty($pid) && $qty > 0) {
                    mysqli_query($conn, "INSERT INTO SALES_ITEM (SALE_ID, PRODUCT_ID, QUANTITY) VALUES ('$sale_id', '$pid', '$qty')");
                    mysqli_query($conn, "UPDATE PRODUCT SET STOCK_QUANTITY = STOCK_QUANTITY - $qty WHERE PRODUCT_ID = '$pid'");
                }
            }
            header("Location: sales.php?new_sale_id=$sale_id");
            exit();
        } else {
            // CATCH AND DISPLAY THE ERROR IF SALE FAILS
            $page_error = mysqli_error($conn);
        }
    }
}

$products_html = '<option value="" data-price="0" data-min="0" data-stock="0">-- Choose Product --</option>';
$prod_res = mysqli_query($conn, "SELECT * FROM PRODUCT WHERE STOCK_QUANTITY > 0 ORDER BY BRAND_NAME ASC");
while($prod = mysqli_fetch_assoc($prod_res)) {
    $min = isset($prod['MINIMUM_PRICE']) ? $prod['MINIMUM_PRICE'] : 0;
    $expiry_text = "";
    if(!empty($prod['EXPIRY_DATE'])) {
        $expiry_text = " [Exp: " . date('M y', strtotime($prod['EXPIRY_DATE'])) . "]";
    }
    $stock_attr = isset($prod['STOCK_QUANTITY']) ? intval($prod['STOCK_QUANTITY']) : 0;
    $products_html .= "<option value='{$prod['PRODUCT_ID']}' data-price='{$prod['UNIT_PRICE']}' data-min='{$min}' data-stock='{$stock_attr}'>{$prod['BRAND_NAME']}{$expiry_text} ({$prod['CATEGORY']}) - RM {$prod['UNIT_PRICE']}</option>";
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
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
        .select2-container .select2-selection--single {
            height: 56px !important;
            border: 1px solid #f1f5f9 !important;
            border-radius: 1rem !important;
            background-color: #f8fafc !important;
            display: flex;
            align-items: center;
            padding-left: 0.5rem;
            font-weight: 700;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 56px !important;
            right: 10px !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #0097B2 !important;
            outline: none !important;
        }
    </style>
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

        <?php if(!empty($page_error)): ?>
            <div class="bg-red-50 border border-red-500 text-red-700 p-6 rounded-2xl mb-8 shadow-sm">
                <h3 class="font-black text-lg"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Database Error Preventing Sale</h3>
                <p class="mt-2 font-medium">MySQL reported: <span class="font-mono bg-white px-2 py-1 rounded text-red-600 border border-red-200"><?php echo $page_error; ?></span></p>
                <p class="mt-2 text-sm italic">If the error says <strong>"Column 'PATIENT_ID' cannot be null"</strong>, you need to go to your Database Manager (phpMyAdmin) and ALTER the SALES table to allow NULL values so walk-in customers can be processed.</p>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="mainSaleForm" class="max-w-5xl space-y-8">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8">Transaction Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center ml-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">Select Patient</label>
                            <button type="button" onclick="openPatientModal()" class="text-[10px] font-black uppercase text-[#0097B2] hover:underline bg-teal-50 px-3 py-1.5 rounded-lg transition-colors">
                                <i class="fa-solid fa-user-plus mr-1"></i> Quick Add Patient
                            </button>
                        </div>
                        <select name="patient_id" id="patient_select" data-placeholder="Search patient name..." class="searchable-select w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold">
                            <option value=""></option>
                            <option value="walkin">-- Walk-in / No Patient Selected --</option>
                            <?php 
                            $p_res = mysqli_query($conn, "SELECT PATIENT_ID, NAME, PHONE_NUMBER FROM PATIENT ORDER BY NAME ASC");
                            while($p = mysqli_fetch_assoc($p_res)) {
                                // Display patient name and phone number
                                $display_phone = (!empty($p['PHONE_NUMBER'])) ? " - " . $p['PHONE_NUMBER'] : "";
                                echo "<option value='{$p['PATIENT_ID']}'>{$p['NAME']}{$display_phone}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Attending Staff (Optometrist)</label>
                        <select name="staff_id" required class="searchable-select w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold">
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
                                <select name="product_id[]" required onchange="updateRowPrice(this)" class="product-select searchable-select w-full p-4 bg-white border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-sm">
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
                            <p class="text-xs text-red-600 font-medium"><strong>Below minimum!</strong> Threshold is RM <span class="min-display font-bold"></span>.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-slate-900 p-10 rounded-[2.5rem] shadow-2xl text-white">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-center">
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
                        <label class="text-[10px] font-black uppercase text-[#B9D977] tracking-widest block mb-2">Amount Paid (RM)</label>
                        <input type="number" step="0.01" name="paid_amount" required oninput="calculateBalance()" placeholder="0.00" class="w-full p-3 bg-white border border-white rounded-xl outline-none font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#B9D977] transition-colors">
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Status (Auto)</label>
                        <select name="payment_status" id="auto_status" class="w-full p-3 bg-white/5 border border-white/10 rounded-xl outline-none text-sm font-bold pointer-events-none appearance-none text-slate-300">
                            <option value="Pending" class="text-black">Pending (No Payment)</option>
                            <option value="Partial" class="text-black">Partial / Deposit</option>
                            <option value="Completed" class="text-black">Completed (Paid Full)</option>
                        </select>
                    </div>

                    <div class="text-right border-r border-white/10 pr-6">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Total Due</p>
                        <p class="text-2xl font-black font-mono">RM <span id="total_display">0.00</span></p>
                        <input type="hidden" name="total_amount" id="total_input" value="0">
                    </div>

                    <div class="text-right pl-2">
                        <p class="text-[10px] font-black uppercase text-red-400 tracking-widest mb-1">Balance Due</p>
                        <p class="text-2xl font-black font-mono text-red-400">RM <span id="balance_display">0.00</span></p>
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

    <div id="patientModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full max-w-lg p-10 rounded-[2.5rem] shadow-2xl border border-slate-100 relative">
            <button type="button" onclick="closePatientModal()" class="absolute top-6 right-6 w-10 h-10 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition">
                <i class="fa-solid fa-times"></i>
            </button>
            
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Quick Add Patient</h2>
            <p class="text-sm text-slate-500 mb-8 font-medium">Add basic details. You can complete their clinical profile later.</p>
            
            <form id="ajaxPatientForm" class="space-y-5">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                    <input type="text" name="name" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">IC Number <span class="text-slate-300 font-medium normal-case tracking-normal ml-1">(Optional)</span></label>
                    <input type="text" name="ic_number" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                    <input type="text" name="phone" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none">
                </div>
                
                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closePatientModal()" class="px-6 py-3 font-bold text-slate-500 hover:text-slate-700 transition">Cancel</button>
                    <button type="submit" id="savePatientBtn" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:bg-teal-600 transition flex items-center">
                        <i class="fa-solid fa-save mr-2"></i> Save & Select
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // helper to format product option with stock
            window.formatProduct = function(state) {
                if (!state.id) return state.text;
                var $state = $(state.element);
                var stock = $state.data('stock');
                var text = state.text + (typeof stock !== 'undefined' ? ' (Available: ' + stock + ')' : '');
                return $('<span>' + text + '</span>');
            }

            window.initSelect2 = function($el) {
                var placeholder = $el.data('placeholder') || 'Search...';
                var dropdownParent = $el.closest('form').length ? $el.closest('form') : $('body');
                $el.select2({
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: true,
                    dropdownParent: dropdownParent,
                    templateResult: window.formatProduct,
                    templateSelection: window.formatProduct,
                    escapeMarkup: function(m) { return m; }
                });
            }

            $('.searchable-select').each(function() { initSelect2($(this)); });
        });

        function openPatientModal() {
            $('#patientModal').removeClass('hidden').addClass('flex');
        }
        function closePatientModal() {
            $('#patientModal').removeClass('flex').addClass('hidden');
            $('#ajaxPatientForm')[0].reset();
        }

        $('#ajaxPatientForm').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#savePatientBtn');
            let originalText = btn.html();
            btn.html('<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...').prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: '', 
                data: $(this).serialize() + '&ajax_add_patient=1',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        let optionText = response.name + (response.phone ? ' - ' + response.phone : '');
                        let newOption = new Option(optionText, response.id, true, true);
                        $('#patient_select').append(newOption).trigger('change');
                        closePatientModal();
                    } else {
                        alert('Database Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText); 
                    alert('A network error occurred. Check browser console.');
                },
                complete: function() {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });

        const rowTemplate = `
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-6 space-y-2">
                    <select name="product_id[]" required onchange="updateRowPrice(this)" class="product-select searchable-select w-full p-4 bg-white border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-sm" data-placeholder="Search product...">
                        <?php echo addslashes($products_html); ?>
                    </select>
                    <div class="text-xs text-slate-500 mt-1 stock-info">Available: -</div>
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
                <p class="text-xs text-red-600 font-medium"><strong>Below minimum!</strong> Threshold is RM <span class="min-display font-bold"></span>.</p>
            </div>
        `;

        function addReceiptItem() {
            const container = document.getElementById('items_container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row relative bg-slate-50 p-6 rounded-2xl border border-slate-100 mt-4';
            newRow.innerHTML = rowTemplate;
            container.appendChild(newRow);
            // Initialize select2 on the newly added select with stock formatting
            initSelect2($(newRow).find('.searchable-select'));
        }

        function removeReceiptItem(btnElement) {
            const row = btnElement.closest('.item-row');
            $(row).find('.searchable-select').select2('destroy');
            row.remove();
            calculateGrandTotal();
        }

        function updateRowPrice(selectElement) {
            const row = selectElement.closest('.item-row');
            const priceInput = row.querySelector('.custom-price-input');
            const qtyInput = row.querySelector('.qty-input');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const defaultPrice = selectedOption.getAttribute('data-price');
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;

            priceInput.value = (defaultPrice > 0) ? parseFloat(defaultPrice).toFixed(2) : '';

            // Enforce quantity maximum according to stock
            if (stock >= 0) {
                qtyInput.max = stock;
                if (parseInt(qtyInput.value) > stock) {
                    qtyInput.value = stock;
                }
            } else {
                qtyInput.removeAttribute('max');
            }

            // Update visible stock info in the row
            const stockInfo = row.querySelector('.stock-info');
            if (stockInfo) stockInfo.innerText = 'Available: ' + stock;

            checkRowThreshold(priceInput);
            calculateGrandTotal();
        }

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

        function calculateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const price = parseFloat(row.querySelector('.custom-price-input').value) || 0;
                const qty = parseInt(row.querySelector('.qty-input').value) || 0;
                total += (price * qty);
            });
            
            document.getElementById('total_display').innerText = total.toFixed(2);
            document.getElementById('total_input').value = total.toFixed(2);
            
            calculateBalance(); 
        }

        function calculateBalance() {
            const total = parseFloat(document.getElementById('total_input').value) || 0;
            const paidInput = document.querySelector('input[name="paid_amount"]');
            let paid = parseFloat(paidInput.value) || 0;
            const statusDropdown = document.getElementById('auto_status');
            
            // LIMIT CHECK: Prevent paid amount from exceeding total amount
            if (paid > total) {
                paid = total;
                paidInput.value = total > 0 ? total.toFixed(2) : '';
                
                // Optional: Flash a red border to indicate to the user it was capped
                paidInput.classList.add('border-red-500', 'bg-red-50');
                setTimeout(() => {
                    paidInput.classList.remove('border-red-500', 'bg-red-50');
                }, 500);
            }

            let balance = total - paid;
            if (balance < 0) balance = 0;
            
            document.getElementById('balance_display').innerText = balance.toFixed(2);

            if (total > 0 && paid >= total) {
                statusDropdown.value = 'Completed';
                statusDropdown.classList.replace('text-slate-300', 'text-[#B9D977]');
            } else if (paid > 0 && paid < total) {
                statusDropdown.value = 'Partial';
                statusDropdown.classList.replace('text-[#B9D977]', 'text-orange-400');
                statusDropdown.classList.replace('text-slate-300', 'text-orange-400'); 
            } else {
                statusDropdown.value = 'Pending';
                statusDropdown.classList.replace('text-[#B9D977]', 'text-slate-300');
                statusDropdown.classList.replace('text-orange-400', 'text-slate-300'); 
            }
        }

        // Clamp quantity inputs to available stock on-the-fly
        $(document).on('input', '.qty-input', function() {
            const qtyInput = this;
            const row = qtyInput.closest('.item-row');
            if(!row) return;
            const select = row.querySelector('.product-select');
            if(!select) return;
            const selectedOption = select.options[select.selectedIndex];
            const stock = parseInt(selectedOption?.getAttribute('data-stock')) || 0;
            let val = parseInt(qtyInput.value) || 0;
            if (stock >= 0 && val > stock) {
                qtyInput.value = stock;
                qtyInput.classList.add('border-red-400');
                setTimeout(() => qtyInput.classList.remove('border-red-400'), 1000);
            }
            calculateGrandTotal();
        });

        // Validate all rows before submitting the form
        document.getElementById('mainSaleForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('.item-row');
            for (const row of rows) {
                const select = row.querySelector('.product-select');
                const selectedOption = select.options[select.selectedIndex];
                const stock = parseInt(selectedOption?.getAttribute('data-stock')) || 0;
                const qty = parseInt(row.querySelector('.qty-input').value) || 0;
                if (qty > stock) {
                    alert('Quantity for "' + (selectedOption?.text || 'product') + '" exceeds available stock (' + stock + '). Please adjust quantities.');
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        });
    </script>
</body>
</html>