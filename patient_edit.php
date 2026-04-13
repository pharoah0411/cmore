<?php 
include('config.php'); 

// Fetch the patient ID from the URL
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header("Location: patients.php");
    exit();
}

// Handle the update request
if(isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $ic = mysqli_real_escape_string($conn, $_POST['ic_number']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $connection = mysqli_real_escape_string($conn, $_POST['connection']);
    $interval = mysqli_real_escape_string($conn, $_POST['follow_up']);
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);

    // Update only the requested attributes
    $update_sql = "UPDATE PATIENT SET 
                   NAME='$name', 
                   IC_NUMBER='$ic', 
                   PHONE_NUMBER='$phone', 
                   ADDRESS='$address', 
                   CONNECTION_RELATIONSHIP='$connection', 
                   FOLLOW_UP_INTERVAL='$interval', 
                   COMPLAINTS='$complaints' 
                   WHERE PATIENT_ID='$id'";
    
    if(mysqli_query($conn, $update_sql)) {
        header("Location: patient_details.php?id=$id&msg=updated");
    }
}

// Fetch existing data to pre-fill the form
$res = mysqli_query($conn, "SELECT * FROM PATIENT WHERE PATIENT_ID = '$id'");
$row = mysqli_fetch_assoc($res);

if (!$row) {
    echo "Patient record not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Edit Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <a href="patient_details.php?id=<?php echo $id; ?>" class="text-[#0097B2] text-sm font-bold uppercase tracking-widest hover:opacity-70 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Profile
            </a>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4">Edit Patient Profile</h1>
            <p class="text-slate-500 font-medium mt-1">Update clinical records for <span class="text-[#0097B2] font-black"><?php echo $row['NAME']; ?></span>.</p>
        </header>

        <form action="" method="POST" class="max-w-5xl space-y-8">
            <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" value="<?php echo $row['NAME']; ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">IC Number</label>
                        <input type="text" name="ic_number" value="<?php echo $row['IC_NUMBER']; ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo $row['PHONE_NUMBER']; ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address</label>
                        <input type="text" name="address" value="<?php echo $row['ADDRESS']; ?>" required 
                               class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none focus:bg-white transition">
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Clinical Details</h3>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Relationship / Connection</label>
                            <input type="text" name="connection" value="<?php echo $row['CONNECTION_RELATIONSHIP']; ?>" 
                                   placeholder="e.g. Family Member Name" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2] focus:bg-white transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Patient Complaints</label>
                            <textarea name="complaints" rows="4" 
                                      class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-[#0097B2] focus:bg-white transition"><?php echo $row['COMPLAINTS']; ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Follow-up Interval</h3>
                    <div class="space-y-4">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Current Recall: <span class="text-slate-800"><?php echo !empty($row['FOLLOW_UP_INTERVAL']) ? $row['FOLLOW_UP_INTERVAL'] : 'None'; ?></span></p>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <?php 
                            $intervals = ["3 Months", "6 Months", "1 Year"];
                            foreach($intervals as $val):
                                $checked = ($row['FOLLOW_UP_INTERVAL'] == $val) ? "checked" : "";
                            ?>
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:border-[#B9D977] transition group">
                                <input type="radio" name="follow_up" value="<?php echo $val; ?>" <?php echo $checked; ?> class="w-4 h-4 text-[#0097B2] focus:ring-[#0097B2]">
                                <span class="ml-4 text-sm font-bold text-slate-700 group-hover:text-slate-900"><?php echo $val; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex justify-end items-center space-x-4 pt-4">
                <a href="patient_details.php?id=<?php echo $id; ?>" class="text-slate-400 font-bold px-6 py-4 hover:text-slate-600 transition">Cancel Changes</a>
                <button type="submit" name="update" 
                        class="bg-[#0097B2] text-white px-12 py-4 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </main>
</body>
</html>