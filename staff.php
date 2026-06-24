<?php
include('config.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ==========================================
// ACCESS CONTROL
// ==========================================
// Kick out anyone who is not an Admin
if (!isset($_SESSION['ROLE']) || $_SESSION['ROLE'] !== 'Admin') {
    header("Location: directory.php");
    exit();
}

// ==========================================
// HANDLE EDIT & DELETE ACTIONS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit') {
        $user_id = (int)$_POST['user_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $sql = "UPDATE user SET NAME='$name', EMAIL='$email', ROLE='$role' WHERE USER_ID=$user_id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Staff profile updated successfully!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to update staff profile.'];
        }
        header("Location: staff.php");
        exit;
    }
    
    if ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Security check: Prevent the admin from deleting themselves
        if ($user_id == $_SESSION['USER_ID']) {
             $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Action denied: You cannot delete your own active account.'];
        } else {
            if(mysqli_query($conn, "DELETE FROM user WHERE USER_ID = $user_id")) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Staff member deleted successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to delete staff member.'];
            }
        }
        header("Location: staff.php");
        exit;
    }
}

// Flash Messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ==========================================
// SEARCH & FILTER LOGIC
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

$where_clauses = [];

if (!empty($search)) {
    $where_clauses[] = "(NAME LIKE '%$search%' OR EMAIL LIKE '%$search%')"; 
}

if (!empty($role_filter)) {
    $where_clauses[] = "ROLE = '$role_filter'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch distinct roles for the filter dropdown
$roles_query = "SELECT DISTINCT ROLE FROM user WHERE ROLE IS NOT NULL AND ROLE != '' ORDER BY ROLE ASC";
$roles_result = mysqli_query($conn, $roles_query);

// ==========================================
// FETCH STAFF DATA
// ==========================================
$query = "SELECT * FROM user $where_sql ORDER BY NAME ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Directory | C-More</title>
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

        function openEditModal(id, name, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            openModal('editModal');
        }

        function confirmDelete(id, name) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_user_name').innerText = name;
            openModal('deleteModal');
        }
    </script>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex min-h-screen overflow-x-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Staff Directory</h1>
                <p class="text-slate-500 font-medium mt-1">Manage system access, roles, and staff details.</p>
            </div>
            
            <a href="staff_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all inline-flex items-center border-2 border-[#0097B2]">
                <i class="fa-solid fa-user-plus mr-2"></i> Add New Staff
            </a>
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
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 max-w-4xl">
                <div class="relative w-full md:w-1/3">
                    <select name="role" class="w-full pl-6 pr-10 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none appearance-none font-bold text-slate-700 cursor-pointer">
                        <option value="">All Roles</option>
                        <?php while($r = mysqli_fetch_assoc($roles_result)): ?>
                            <option value="<?php echo htmlspecialchars($r['ROLE']); ?>" <?php echo $role_filter === $r['ROLE'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($r['ROLE'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
                
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search by Name or Email..." value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all font-medium text-slate-700">
                </div>
                
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-bold hover:bg-[#0097B2] transition shadow-lg shrink-0">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </button>
                
                <?php if(!empty($search) || !empty($role_filter)): ?>
                <a href="staff.php" class="bg-white border-2 border-slate-200 text-slate-600 px-8 py-4 rounded-[1.5rem] font-bold hover:bg-slate-50 hover:text-slate-900 transition shadow-sm shrink-0 flex items-center justify-center">
                    Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="p-6 font-black">Staff Member</th>
                            <th class="p-6 font-black text-center">Category / Role</th>
                            <th class="p-6 font-black text-center">System ID</th>
                            <th class="p-6 font-black text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                // Determine badge color based on role
                                $role = htmlspecialchars($row['ROLE']);
                                $badge_class = 'bg-slate-100 text-slate-600 border-slate-200'; // Default
                                
                                if (strcasecmp($role, 'Admin') == 0) {
                                    $badge_class = 'bg-red-50 text-red-600 border-red-200';
                                } elseif (strcasecmp($role, 'Optometrist') == 0) {
                                    $badge_class = 'bg-[#0097B2]/10 text-[#0097B2] border-[#0097B2]/20';
                                } elseif (strcasecmp($role, 'Staff') == 0 || strcasecmp($role, 'Sales') == 0) {
                                    $badge_class = 'bg-[#B9D977]/20 text-slate-800 border-[#B9D977]/40';
                                }
                            ?>
                                <tr class="hover:bg-slate-50/80 transition group">
                                    <td class="p-6">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0097B2] to-[#B9D977] text-white flex items-center justify-center font-black text-lg shadow-md shrink-0">
                                                <?php echo $row['NAME'] ? strtoupper(substr($row['NAME'], 0, 2)) : '??'; ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 text-lg leading-tight"><?php echo htmlspecialchars($row['NAME'] ?? 'Unknown'); ?></p>
                                                <?php if(isset($row['EMAIL']) && !empty($row['EMAIL'])): ?>
                                                    <p class="text-xs text-slate-400 font-bold mt-0.5"><i class="fa-solid fa-envelope mr-1"></i> <?php echo htmlspecialchars($row['EMAIL']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="<?php echo $badge_class; ?> px-4 py-1.5 rounded-xl font-black text-[10px] uppercase tracking-widest border">
                                            <?php echo $role; ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center font-mono text-xs text-slate-400 font-bold">
                                        #USR-<?php echo str_pad($row['USER_ID'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="p-6">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php 
                                                $js_name  = htmlspecialchars(json_encode($row['NAME']), ENT_QUOTES); 
                                                $js_email = htmlspecialchars(json_encode($row['EMAIL'] ?? ''), ENT_QUOTES); 
                                                $js_role  = htmlspecialchars(json_encode($row['ROLE']), ENT_QUOTES); 
                                            ?>
                                            <button onclick="openEditModal(<?php echo $row['USER_ID']; ?>, <?php echo $js_name; ?>, <?php echo $js_email; ?>, <?php echo $js_role; ?>)" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#0097B2] hover:text-white transition duration-300 shadow-sm" title="Edit Staff">
                                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                                            </button>
                                            
                                            <button onclick="confirmDelete(<?php echo $row['USER_ID']; ?>, <?php echo $js_name; ?>)" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition duration-300 shadow-sm" title="Delete Staff">
                                                <i class="fa-solid fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-slate-400 font-bold italic">
                                    No staff members found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div id="editModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-8">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-6">Edit Staff Profile</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Full Name *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">Email Address *</label>
                        <input type="email" name="email" id="edit_email" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-500 tracking-widest mb-2">System Role *</label>
                        <div class="relative">
                            <select name="role" id="edit_role" required class="w-full pl-5 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 appearance-none">
                                <option value="Admin">Admin</option>
                                <option value="Optometrist">Optometrist</option>
                                <option value="Staff">Staff</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('editModal')" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="flex-1 py-3 rounded-2xl bg-[#0097B2] text-white font-bold hover:bg-teal-600 transition shadow-lg shadow-teal-100">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mb-6 mx-auto">
                <i class="fa-solid fa-user-xmark text-2xl"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900 tracking-tight mb-2">Revoke Access?</h3>
            <p class="text-slate-500 font-medium">Are you sure you want to delete</p>
            <p id="delete_user_name" class="text-slate-900 font-bold text-lg mt-1 mb-5 break-words">—</p>
            
            <p class="text-xs text-slate-400 font-medium mb-6 bg-slate-50 p-3 rounded-xl">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> They will lose all access to the C-More system immediately.
            </p>
            
            <form method="POST" class="flex gap-3">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                
                <button type="button" onclick="closeModal('deleteModal')" class="flex-1 py-3 rounded-2xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="flex-1 py-3 rounded-2xl bg-red-500 text-white font-bold hover:bg-red-600 transition shadow-lg shadow-red-100">Confirm Delete</button>
            </form>
        </div>
    </div>

</body>
</html>