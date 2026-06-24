<?php
include('config.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Kick out anyone who is not logged in
if (!isset($_SESSION['USER_ID'])) {
    header("Location: login.php");
    exit();
}

$backup_dir = 'backups/';
$backup_file = $backup_dir . 'cmore_latest_backup.sql';

if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
    file_put_contents($backup_dir . '.htaccess', "Deny from all");
}

// ==========================================
// 1. HANDLE BACKUP GENERATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_backup') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Security token validation failed.'];
        header("Location: backup.php");
        exit;
    }

    include('auto_backup.php'); // Calls our auto-backup script
    systemLog($conn, 'Generated a manual database backup');
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Backup successfully generated and overwritten!'];
    
    header("Location: backup.php");
    exit;
}

// ==========================================
// 2. HANDLE SMART RESTORE (MERGE MISSING DATA)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Security token validation failed.'];
        header("Location: backup.php");
        exit;
    }

    if (!file_exists($backup_file)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No backup file found to restore.'];
        header("Location: backup.php");
        exit;
    }

    // Read the backup file
    $sql_content = file_get_contents($backup_file);

    // ---- THE SMART MERGE MAGIC ----
    // 1. Remove all DROP TABLE commands so we don't destroy current data
    $sql_content = preg_replace('/DROP TABLE IF EXISTS `[^`]+`;/i', '', $sql_content);
    
    // 2. Change CREATE TABLE to CREATE TABLE IF NOT EXISTS (just in case a table was deleted)
    $sql_content = str_ireplace('CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', $sql_content);
    
    // 3. Change INSERT INTO to INSERT IGNORE INTO (This ignores duplicates and only adds missing records!)
    $sql_content = str_ireplace('INSERT INTO `', 'INSERT IGNORE INTO `', $sql_content);

    // Disable foreign key checks temporarily to allow smooth merging
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0;");

    // Execute the massive modified SQL script
    if (mysqli_multi_query($conn, $sql_content)) {
        // We must clear the results buffer for multi_query so the page doesn't crash
        do {
            if ($res = mysqli_store_result($conn)) {
                mysqli_free_result($res);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        
        systemLog($conn, 'Performed a Smart Merge Restore from backup');
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Smart Recovery Complete! Missing records were restored, and existing data was kept safe.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error during restoration: ' . mysqli_error($conn)];
    }

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1;");
    
    header("Location: backup.php");
    exit;
}

// ==========================================
// 3. HANDLE FILE DOWNLOAD
// ==========================================
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    if (file_exists($backup_file)) {
        systemLog($conn, 'Downloaded the latest database backup file');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="cmore_backup_' . date('Y-m-d') . '.sql"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file));
        readfile($backup_file);
        exit;
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Backup file not found. Please generate one first.'];
        header("Location: backup.php");
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$file_exists = file_exists($backup_file);
$last_modified = $file_exists ? date('F d, Y @ h:i:s A', filemtime($backup_file)) : 'Never';
$file_size = $file_exists ? round(filesize($backup_file) / 1024, 2) . ' KB' : '0 KB';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Management | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 h-screen overflow-y-auto p-12">
        
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">System Backup & Recovery</h1>
                <p class="text-slate-500 font-medium mt-1">Secure, overwrite, download, and smartly restore your clinic data.</p>
            </div>
            
            <div class="bg-white px-5 py-3 rounded-2xl shadow-sm border border-slate-100 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-[#0097B2]">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400">Security Level</p>
                    <p class="text-sm font-bold text-slate-800">All Staff Authorized</p>
                </div>
            </div>
        </header>

        <?php if ($flash): ?>
        <div class="mb-8 flex items-center justify-between gap-4 p-5 rounded-2xl border shadow-sm
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mb-8">
            <!-- 1. Generate Backup Card -->
            <div class="bg-white p-10 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#0097B2]/5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                
                <div>
                    <div class="w-16 h-16 bg-[#0097B2]/10 text-[#0097B2] rounded-2xl flex items-center justify-center text-2xl mb-6">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Overwrite Data</h3>
                    <p class="text-slate-500 text-sm font-medium mt-3 leading-relaxed">
                        Running this process pulls all active records, patient ICs, sales, and inventory, and updates the single <code>cmore_latest_backup.sql</code> file.
                    </p>
                </div>

                <form method="POST" action="backup.php" class="mt-8 z-10">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="run_backup">
                    <button type="submit" onclick="return confirm('Overwrite current server backup with latest data?')" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all flex items-center justify-center">
                        <i class="fa-solid fa-rotate mr-2"></i> Generate Manual Backup
                    </button>
                </form>
            </div>

            <!-- 2. Download Backup Card -->
            <div class="bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border border-slate-800 flex flex-col justify-between relative overflow-hidden text-white">
                <div class="absolute bottom-0 right-0 w-48 h-48 bg-[#B9D977]/10 rounded-full -mr-16 -mb-16 blur-3xl"></div>
                
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-slate-800 text-[#B9D977] rounded-2xl flex items-center justify-center text-2xl">
                            <i class="fa-solid fa-download"></i>
                        </div>
                        <?php if($file_exists): ?>
                            <span class="px-3 py-1 bg-[#B9D977]/20 border border-[#B9D977]/30 text-[#B9D977] rounded-lg text-[10px] font-black uppercase tracking-widest">Available</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-red-500/20 border border-red-500/30 text-red-400 rounded-lg text-[10px] font-black uppercase tracking-widest">Missing</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-2xl font-black tracking-tight">Latest Backup File</h3>
                    
                    <div class="mt-6 space-y-4 bg-slate-800/50 p-5 rounded-2xl border border-slate-700/50">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">Last Saved</span>
                            <span class="font-mono text-sm text-white"><?php echo $last_modified; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">File Size</span>
                            <span class="font-mono text-sm text-white"><?php echo $file_size; ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 z-10">
                    <?php if($file_exists): ?>
                        <a href="backup.php?download=true" class="block w-full bg-[#B9D977] text-slate-900 text-center py-4 rounded-2xl font-black shadow-lg hover:bg-white transition-all">
                            <i class="fa-solid fa-cloud-arrow-down mr-2"></i> Download .SQL File
                        </a>
                    <?php else: ?>
                        <button disabled class="w-full bg-slate-800 text-slate-500 cursor-not-allowed py-4 rounded-2xl font-bold border border-slate-700">
                            No Backup Available Yet
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Smart Restore Card -->
        <div class="max-w-5xl bg-amber-50 rounded-[2.5rem] border border-amber-200 p-10 flex items-center justify-between shadow-sm">
            <div class="max-w-xl">
                <div class="flex items-center space-x-3 mb-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-500 text-xl"></i>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Smart Data Recovery</h3>
                </div>
                <p class="text-amber-700 font-medium text-sm leading-relaxed">
                    Did someone accidentally delete a patient or a product? Use this to recover missing data from the backup file. <strong>It will safely ignore existing records to prevent duplication</strong>, and only insert the data that is currently missing from your system!
                </p>
            </div>
            
            <div class="ml-6 shrink-0">
                <form method="POST" action="backup.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="restore_backup">
                    <button type="submit" 
                            <?php echo !$file_exists ? 'disabled' : ''; ?>
                            onclick="return confirm('This will scan the backup file and safely insert any missing records into the database. Existing data will NOT be overwritten. Proceed?')" 
                            class="px-8 py-4 rounded-2xl font-black shadow-md transition-all flex items-center 
                            <?php echo $file_exists ? 'bg-amber-500 text-white hover:bg-amber-600 hover:shadow-amber-200' : 'bg-amber-200 text-amber-400 cursor-not-allowed'; ?>">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i> Run Smart Restore
                    </button>
                </form>
            </div>
        </div>

    </main>
</body>
</html>