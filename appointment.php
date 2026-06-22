<?php 
include('config.php'); 

// ==========================================
// HANDLE STATUS UPDATES (Check-In, Complete, Cancel)
// ==========================================
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $new_status = "";
    if($action == 'checkin') $new_status = 'Checked-In';
    if($action == 'complete') $new_status = 'Completed';
    if($action == 'cancel') $new_status = 'Cancelled';
    
    if($new_status != "") {
        mysqli_query($conn, "UPDATE APPOINTMENT SET STATUS = '$new_status' WHERE APPOINTMENT_ID = '$id'");
        header("Location: appointment.php?msg=updated");
        exit();
    }
}

// ==========================================
// CAPTURE SEARCH & FILTER INPUTS
// ==========================================
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Appointment Schedule</h1>
                <p class="text-slate-500 font-medium mt-1">Manage daily bookings and patient arrivals.</p>
            </div>
            <a href="appointment_add.php" class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all flex items-center">
                <i class="fa-solid fa-calendar-plus mr-2"></i> Book Appointment
            </a>
        </header>

        <form method="GET" action="appointment.php" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 mb-8 flex flex-col lg:flex-row gap-4 items-center">
            
            <div class="flex-1 w-full relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" placeholder="Search patient name..." value="<?php echo htmlspecialchars($search_query); ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] focus:ring-1 focus:ring-[#0097B2] transition-colors font-medium text-slate-700">
            </div>
            
            <div class="w-full lg:w-48">
                <select name="status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] text-slate-600 font-medium appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php if($filter_status == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Checked-In" <?php if($filter_status == 'Checked-In') echo 'selected'; ?>>Checked-In</option>
                    <option value="Completed" <?php if($filter_status == 'Completed') echo 'selected'; ?>>Completed</option>
                    <option value="Cancelled" <?php if($filter_status == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>

            <div class="w-full lg:w-48">
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#0097B2] text-slate-600 font-medium cursor-pointer">
            </div>

            <div class="flex gap-2 w-full lg:w-auto">
                <button type="submit" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold hover:bg-[#0097B2] transition-colors flex-1 lg:flex-none shadow-md">
                    Filter
                </button>
                <a href="appointment.php" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-200 transition-colors flex-1 lg:flex-none text-center border border-slate-200">
                    Clear
                </a>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-6">
            <?php
            // Build the base query
            $sql = "SELECT A.*, P.NAME as PATIENT_NAME 
                    FROM APPOINTMENT A 
                    JOIN PATIENT P ON A.PATIENT_ID = P.PATIENT_ID 
                    WHERE 1=1";
            
            // Append Search logic
            if (!empty($search_query)) {
                $sql .= " AND P.NAME LIKE '%$search_query%'";
            }

            // Append Status Filter logic
            if ($filter_status !== '') {
                if ($filter_status == 'Pending') {
                    // Handle default DB values which might be empty, NULL, or 'Scheduled' for pending
                    $sql .= " AND (A.STATUS = 'Pending' OR A.STATUS = 'Scheduled' OR A.STATUS IS NULL OR A.STATUS = '')";
                } else {
                    $sql .= " AND A.STATUS = '$filter_status'";
                }
            }

            // Append Date Filter logic
            if (!empty($filter_date)) {
                $sql .= " AND DATE(A.APPOINTMENT_DATETIME) = '$filter_date'";
            }

            // Finally, order the results
            $sql .= " ORDER BY A.APPOINTMENT_DATETIME ASC";

            $res = mysqli_query($conn, $sql);
            
            if(mysqli_num_rows($res) > 0):
                while($row = mysqli_fetch_assoc($res)):
                    // Normalizing status for UI display
                    $status = (empty($row['STATUS']) || $row['STATUS'] == 'Scheduled') ? 'Pending' : $row['STATUS'];
                    
                    // Dynamic styling based on status
                    if ($status == 'Completed') {
                        $status_class = 'bg-slate-100 text-slate-500';
                        $border_hover = 'hover:border-slate-300';
                    } elseif ($status == 'Checked-In') {
                        $status_class = 'bg-blue-100 text-blue-600';
                        $border_hover = 'hover:border-blue-300';
                    } elseif ($status == 'Cancelled') {
                        $status_class = 'bg-red-100 text-red-600';
                        $border_hover = 'hover:border-red-300';
                    } else { // Pending
                        $status_class = 'bg-[#B9D977]/20 text-[#6d8a2a]';
                        $border_hover = 'hover:border-[#0097B2]/30';
                    }
            ?>
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex flex-col md:flex-row md:items-center justify-between group <?php echo $border_hover; ?> transition-all duration-300">
                <div class="flex items-center space-x-8 mb-4 md:mb-0">
                    <div class="text-center bg-slate-50 p-4 rounded-[2rem] min-w-[100px] border border-slate-100 group-hover:bg-[#0097B2] group-hover:text-white transition-colors">
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-60"><?php echo date('M', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                        <p class="text-3xl font-black"><?php echo date('d', strtotime($row['APPOINTMENT_DATETIME'])); ?></p>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-xl text-slate-800"><?php echo htmlspecialchars($row['PATIENT_NAME']); ?></h4>
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-sm font-medium text-slate-400">
                                <i class="fa-regular fa-clock mr-2 text-[#0097B2]"></i><?php echo date('h:i A', strtotime($row['APPOINTMENT_DATETIME'])); ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?php echo $status_class; ?>">
                                <?php echo $status; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <?php if($status == 'Pending' || $status == ''): ?>
                        <a href="appointment.php?action=checkin&id=<?php echo $row['APPOINTMENT_ID']; ?>" class="bg-slate-900 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#0097B2] transition-colors shadow-lg shadow-slate-200">
                            Check-In
                        </a>
                    <?php elseif($status == 'Checked-In'): ?>
                        <a href="appointment.php?action=complete&id=<?php echo $row['APPOINTMENT_ID']; ?>" class="bg-[#0097B2] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-teal-600 transition-colors shadow-lg shadow-teal-100">
                            <i class="fa-solid fa-check mr-2"></i> Complete
                        </a>
                    <?php endif; ?>

                    <a href="appointment_edit.php?id=<?php echo $row['APPOINTMENT_ID']; ?>" class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-[#B9D977] hover:text-slate-800 transition-all shadow-sm">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="bg-white p-12 rounded-[2.5rem] border border-slate-100 text-center shadow-xl shadow-slate-200/40">
                    <i class="fa-solid fa-magnifying-glass text-4xl text-slate-300 mb-4 mt-2"></i>
                    <h3 class="text-xl font-bold text-slate-700">No Appointments Found</h3>
                    <p class="text-slate-500 mt-2">No appointments match your current search criteria.</p>
                    <a href="appointment.php" class="inline-block mt-4 text-[#0097B2] font-bold hover:underline">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>