<?php 
include('config.php'); 

// Fetch the patient ID from the URL
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header("Location: patients.php");
    exit();
}

// Fetch basic patient details
$sql = "SELECT * FROM PATIENT WHERE PATIENT_ID = '$id'";
$res = mysqli_query($conn, $sql);
$patient = mysqli_fetch_assoc($res);

if (!$patient) {
    echo "Patient not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Patient Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-start mb-12">
            <div>
                <a href="patients.php" class="text-slate-400 text-sm font-bold uppercase tracking-widest hover:text-[#0097B2] transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Directory
                </a>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mt-4"><?php echo $patient['NAME']; ?></h1>
                <div class="flex items-center space-x-3 mt-2">
                    <span class="px-3 py-1 bg-slate-900 text-[#B9D977] rounded-lg text-[10px] font-black uppercase tracking-widest">
                        Patient ID: #<?php echo $patient['PATIENT_ID']; ?>
                    </span>
                    <span class="text-slate-400 text-xs font-medium">
                        Registered on <?php echo date('d M Y', strtotime($patient['REGISTRATION_DATE'])); ?>
                    </span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="patient_edit.php?id=<?php echo $id; ?>" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-50 transition shadow-sm">
                    <i class="fa-solid fa-pen mr-2"></i> Edit Profile
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-8 border-b border-slate-50 pb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">IC Number</p>
                            <p class="text-slate-800 font-bold font-mono text-lg"><?php echo $patient['IC_NUMBER']; ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Phone Number</p>
                            <p class="text-slate-800 font-bold text-lg"><?php echo $patient['PHONE_NUMBER']; ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Address</p>
                            <p class="text-slate-700 leading-relaxed font-medium"><?php echo $patient['ADDRESS']; ?></p>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-50 pb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-microscope text-[#0097B2]"></i>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Eye Exam Results</h3>
                        </div>
                        <a href="exam_add.php?patient_id=<?php echo $id; ?>" class="text-[10px] font-black uppercase text-[#0097B2] hover:underline">Add New Exam</a>
                    </div>
                    
                    <div class="space-y-6">
                        <?php
                        // Fetch exams for this specific patient joined with Optometrist name
                        $exam_sql = "SELECT E.*, U.NAME as DOC_NAME 
                                    FROM EYE_EXAMINATION E
                                    JOIN USER U ON E.OPTOMETRIST_ID = U.USER_ID
                                    WHERE E.PATIENT_ID = '$id'
                                    ORDER BY E.EXAM_DATE DESC";
                        $exam_res = mysqli_query($conn, $exam_sql);

                        if(mysqli_num_rows($exam_res) > 0):
                            while($exam = mysqli_fetch_assoc($exam_res)):
                        ?>
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 group hover:border-[#B9D977] transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Exam Date</span>
                                    <p class="font-bold text-slate-800"><?php echo date('d M Y', strtotime($exam['EXAM_DATE'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Optometrist</span>
                                    <p class="text-sm font-bold text-[#0097B2]"><?php echo $exam['DOC_NAME']; ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="bg-white p-4 rounded-2xl border border-slate-100">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Prescription</p>
                                    <p class="text-sm font-mono font-bold text-slate-700"><?php echo $exam['PRESCRIPTION_RESULT']; ?></p>
                                </div>
                                <div class="bg-white p-4 rounded-2xl border border-slate-100">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Visual Acuity</p>
                                    <p class="text-sm font-mono font-bold text-slate-700"><?php echo $exam['VISUAL_ACUITY_RESULTS']; ?></p>
                                </div>
                            </div>
                            <?php if(!empty($exam['CLINICAL_NOTES'])): ?>
                            <div class="mt-4 px-2">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Clinical Notes</p>
                                <p class="text-xs text-slate-500 leading-relaxed italic">"<?php echo $exam['CLINICAL_NOTES']; ?>"</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; else: ?>
                            <p class="text-center py-10 text-slate-400 italic text-sm">No clinical examinations recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="space-y-8">
                <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Initial Complaints</h3>
                    <div class="bg-red-50/50 p-5 rounded-2xl border border-red-100">
                        <p class="text-sm text-slate-600 leading-relaxed italic">
                            <?php echo !empty($patient['COMPLAINTS']) ? nl2br($patient['COMPLAINTS']) : 'No complaints recorded.'; ?>
                        </p>
                    </div>
                </section>

                <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Next Recall Interval</h4>
                        <p class="text-2xl font-black mt-1 text-[#B9D977]">
                            <?php echo !empty($patient['FOLLOW_UP_INTERVAL']) ? $patient['FOLLOW_UP_INTERVAL'] : 'Not Set'; ?>
                        </p>
                    </div>
                </div>

                <section class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Relationship</h3>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                            <i class="fa-solid fa-people-arrows"></i>
                        </div>
                        <p class="text-slate-800 font-bold"><?php echo !empty($patient['CONNECTION_RELATIONSHIP']) ? $patient['CONNECTION_RELATIONSHIP'] : 'None'; ?></p>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>