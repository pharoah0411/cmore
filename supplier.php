<?php 
include('config.php'); 
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle Add, Edit, Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $company = mysqli_real_escape_string($conn, $_POST['company_name']);
        $person = mysqli_real_escape_string($conn, $_POST['contact_person']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $sql = "INSERT INTO supplier (COMPANY_NAME, CONTACT_PERSON, PHONE_NUMBER, EMAIL) VALUES ('$company', '$person', '$phone', '$email')";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier added successfully!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to add supplier.'];
        }
        header("Location: supplier.php");
        exit;
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['supplier_id'];
        $company = mysqli_real_escape_string($conn, $_POST['company_name']);
        $person = mysqli_real_escape_string($conn, $_POST['contact_person']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $sql = "UPDATE supplier SET COMPANY_NAME='$company', CONTACT_PERSON='$person', PHONE_NUMBER='$phone', EMAIL='$email' WHERE SUPPLIER_ID=$id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier updated successfully!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to update supplier.'];
        }
        header("Location: supplier.php");
        exit;
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['supplier_id'];
        
        // Validation: Verify total stock is 0
        $check_sql = "SELECT COALESCE(SUM(STOCK_QUANTITY), 0) as total_stock FROM product WHERE SUPPLIER_ID = $id";
        $check_res = mysqli_query($conn, $check_sql);
        $check_row = mysqli_fetch_assoc($check_res);
        
        if ($check_row['total_stock'] > 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Cannot delete supplier! They still have active stock (' . $check_row['total_stock'] . ' items) in the inventory.'];
        } else {
            // Safe to delete (the DB constraint will SET NULL for products that have 0 stock)
            if(mysqli_query($conn, "DELETE FROM supplier WHERE SUPPLIER_ID = $id")) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier deleted successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to delete supplier.'];
            }
        }
        header("Location: supplier.php");
        exit;
    }
}

// Flash Messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Search logic
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = $search ? "WHERE s.COMPANY_NAME LIKE '%$search%'" : '';

// Get suppliers along with their product counts and total stock quantity
$query = "SELECT s.*, 
          COUNT(p.PRODUCT_ID) as product_count, 
          COALESCE(SUM(p.STOCK_QUANTITY), 0) as total_stock 
          FROM supplier s 
          LEFT JOIN product p ON s.SUPPLIER_ID = p.SUPPLIER_ID 
          $where
          GROUP BY s.SUPPLIER_ID 
          ORDER BY s.COMPANY_NAME ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Suppliers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.getElementById(modalId).classList.remove('flex');
            document.body.style.overflow = '';
        }

        function openEditModal(id, company, person, phone, email) {
            document.getElementById('edit_supplier_id').value = id;
            document.getElementById('edit_company_name').value = company;
            document.getElementById('edit_contact_person').value = person;
            document.getElementById('edit_phone_number').value = phone;
            document.getElementById('edit_email').value = email;
            openModal('editModal');
        }

        function confirmDelete(id, name, stock) {
            if(stock > 0) {
                alert(`Cannot delete ${name}. This supplier still provides ${stock} items in stock. Please adjust or remove the stock first.`);
                return;
            }
            document.getElementById('delete_supplier_id').value = id;
            document.getElementById('delete_supplier_name').innerText = name;
            openModal('deleteModal');
        }
    </script>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Suppliers</h1>
                <p class="text-slate-500 font-medium mt-1">Manage vendor contacts and supplier records.</p>
            </div>
            <button onclick="openModal('addModal')" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center">
                <i class="fa-solid fa-plus mr-2"></i> Add Supplier
            </button>
        </header>

        <?php if ($flash): ?>
        <div id="flashBanner" class="mb-6 flex items-center justify-between gap-4 p-5 rounded-2xl border shadow-sm
            <?php echo $flash['type'] === 'success' ? 'bg-teal-50 border-teal-200 text-teal-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check text-[#0097B2]' : 'fa-circle-exclamation text-red-500'; ?> text-xl"></i>
                <span class="font-bold"><?php echo htmlspecialchars($flash['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <div class="mb-8">
            <form method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-2xl">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search Supplier Company Name..." value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none font-medium text-slate-700">
                </div>
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-bold hover:bg-[#0097B2] transition shadow-lg shrink-0">
                    Search
                </button>
            </form>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400 w-1/3">Company</th>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-slate-400">Contact Details</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Inventory Link</th>
                        <th class="p-6 text-center text-xs font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(mysqli_num_rows($result) > 0): while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-[#0097B2] flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-building text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-lg leading-tight"><?php echo htmlspecialchars($row['COMPANY_NAME']); ?></p>
                                    <p class="text-xs text-slate-400 font-bold mt-0.5">ID: #SUP-<?php echo str_pad($row['SUPPLIER_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="space-y-1">
                                <p class="text-sm font-bold text-slate-700"><i class="fa-solid fa-user text-slate-400 mr-2 w-3"></i> <?php echo htmlspecialchars($row['CONTACT_PERSON'] ?? 'N/A'); ?></p>
                                <p class="text-sm text-slate-500"><i class="fa-solid fa-phone text-slate-400 mr-2 w-3"></i> <?php echo htmlspecialchars($row['PHONE_NUMBER'] ?? 'N/A'); ?></p>
                                <p class="text-sm text-slate-500"><i class="fa-solid fa-envelope text-slate-400 mr-2 w-3"></i> <?php echo htmlspecialchars($row['EMAIL'] ?? 'N/A'); ?></p>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <span class="block text-2xl font-black <?php echo $row['total_stock'] > 0 ? 'text-[#0097B2]' : 'text-slate-300'; ?>"><?php echo $row['total_stock']; ?></span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Stock</span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center justify-center space-x-2">
                                <?php 
                                    $js_company = htmlspecialchars(json_encode($row['COMPANY_NAME']), ENT_QUOTES); 
                                    $js_person  = htmlspecialchars(json_encode($row['CONTACT_PERSON']), ENT_QUOTES); 
                                    $js_phone   = htmlspecialchars(json_encode($row['PHONE_NUMBER']), ENT_QUOTES); 
                                    $js_email   = htmlspecialchars(json_encode($row['EMAIL']), ENT_QUOTES); 
                                ?>
                                <button onclick="openEditModal(<?php echo $row['SUPPLIER_ID']; ?>, <?php echo $js_company; ?>, <?php echo $js_person; ?>, <?php echo $js_phone; ?>, <?php echo $js_email; ?>)" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300 shadow-sm" title="Edit Supplier">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </button>
                                
                                <button onclick="confirmDelete(<?php echo $row['SUPPLIER_ID']; ?>, <?php echo $js_company; ?>, <?php echo $row['total_stock']; ?>)" 
                                        class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center transition duration-300 shadow-sm <?php echo $row['total_stock'] > 0 ? 'text-slate-300 cursor-not-allowed hover:bg-slate-100' : 'text-slate-400 hover:bg-red-500 hover:text-white'; ?>" 
                                        title="<?php echo $row['total_stock'] > 0 ? 'Cannot delete: Stock exists' : 'Delete Supplier'; ?>">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="p-12 text-center text-slate-400 font-bold italic">No suppliers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="addModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-8">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-6">Add New Supplier</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Company Name *</label>
                        <input type="text" name="company_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Contact Person</label>
                        <input type="text" name="contact_person" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Phone</label>
                            <input type="text" name="phone_number" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Email</label>
                            <input type="email" name="email" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('addModal')" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="flex-1 py-3 rounded-2xl bg-[#0097B2] text-white font-bold hover:bg-teal-600 transition shadow-lg shadow-teal-100">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-8">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-6">Edit Supplier</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="supplier_id" id="edit_supplier_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Company Name *</label>
                        <input type="text" name="company_name" id="edit_company_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Contact Person</label>
                        <input type="text" name="contact_person" id="edit_contact_person" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Phone</label>
                            <input type="text" name="phone_number" id="edit_phone_number" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Email</label>
                            <input type="email" name="email" id="edit_email" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-medium">
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('editModal')" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="flex-1 py-3 rounded-2xl bg-[#0097B2] text-white font-bold hover:bg-teal-600 transition shadow-lg shadow-teal-100">Update Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mb-6 mx-auto">
                <i class="fa-solid fa-trash-can text-2xl"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900 tracking-tight mb-2">Delete Supplier?</h3>
            <p class="text-slate-500 font-medium">Are you sure you want to remove</p>
            <p id="delete_supplier_name" class="text-slate-900 font-bold text-lg mt-1 mb-5 break-words">—</p>
            
            <form method="POST" class="flex gap-3">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="supplier_id" id="delete_supplier_id">
                
                <button type="button" onclick="closeModal('deleteModal')" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="flex-1 py-3 rounded-2xl bg-red-500 text-white font-bold hover:bg-red-600 transition shadow-lg shadow-red-100">Confirm Delete</button>
            </form>
        </div>
    </div>
</body>
</html>