<?php
include('config.php');

// ==========================================
// ACCESS CONTROL
// ==========================================
if (!isset($_SESSION['ROLE']) || ($_SESSION['ROLE'] !== 'Admin' && $_SESSION['ROLE'] !== 'Optometrist')) {
    systemLog($conn, 'Attempted unauthorized access to Add Clinical Exam');
    header("Location: directory.php");
    exit();
}

$error = "";

// ==========================================
// HANDLE EXAM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_exam'])) {
    $patient_id     = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $optometrist_id = $_SESSION['USER_ID']; 
    $exam_date      = date('Y-m-d'); 
    
    $visual_acuity  = mysqli_real_escape_string($conn, $_POST['visual_acuity_results']);
    $prescription   = mysqli_real_escape_string($conn, $_POST['prescription_result']);
    
    $re_sph  = mysqli_real_escape_string($conn, $_POST['re_sph']);
    $re_cyl  = mysqli_real_escape_string($conn, $_POST['re_cyl']);
    $re_axis = mysqli_real_escape_string($conn, $_POST['re_axis']);
    $re_add  = mysqli_real_escape_string($conn, $_POST['re_add']);
    
    $le_sph  = mysqli_real_escape_string($conn, $_POST['le_sph']);
    $le_cyl  = mysqli_real_escape_string($conn, $_POST['le_cyl']);
    $le_axis = mysqli_real_escape_string($conn, $_POST['le_axis']);
    $le_add  = mysqli_real_escape_string($conn, $_POST['le_add']);
    
    $pd             = mysqli_real_escape_string($conn, $_POST['pd']);
    $clinical_notes = mysqli_real_escape_string($conn, $_POST['clinical_notes']);

    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } else {
        $sql = "INSERT INTO eye_examination 
                (PATIENT_ID, OPTOMETRIST_ID, EXAM_DATE, VISUAL_ACUITY_RESULTS, PRESCRIPTION_RESULT, RE_SPH, RE_CYL, RE_AXIS, RE_ADD, LE_SPH, LE_CYL, LE_AXIS, LE_ADD, PD, CLINICAL_NOTES) 
                VALUES 
                ('$patient_id', '$optometrist_id', '$exam_date', '$visual_acuity', '$prescription', '$re_sph', '$re_cyl', '$re_axis', '$re_add', '$le_sph', '$le_cyl', '$le_axis', '$le_add', '$pd', '$clinical_notes')";
        
        if (mysqli_query($conn, $sql)) {
            $new_exam_id = mysqli_insert_id($conn);
            systemLog($conn, 'Created new clinical exam record', 'eye_examination', $new_exam_id);
            
            header("Location: exam_view.php?id=" . $new_exam_id . "&msg=created");
            exit();
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}

$patient_query = "SELECT PATIENT_ID, NAME, IC_NUMBER FROM patient ORDER BY NAME ASC";
$patient_result = mysqli_query($conn, $patient_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Clinical Exam | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 h-screen overflow-y-auto p-10 custom-scrollbar">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <a href="exam.php" class="text-sm font-bold text-slate-400 hover:text-[#0097B2] transition flex items-center mb-2">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Exam Records
                </a>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">New Clinical Exam</h1>
                <p class="text-slate-500 font-medium mt-1">Record patient refraction and ocular health details.</p>
            </div>
            
            <div class="bg-white px-5 py-3 rounded-2xl shadow-sm border border-slate-100 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-[#0097B2]/10 flex items-center justify-center text-[#0097B2]">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400">Attending</p>
                    <p class="text-sm font-bold text-slate-800"><?php echo $_SESSION['NAME']; ?></p>
                </div>
            </div>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded-2xl mb-6 text-sm font-bold border border-red-100 flex items-center shadow-sm">
                <i class="fa-solid fa-triangle-exclamation mr-3 text-lg"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="exam_add.php" method="POST" class="space-y-6">
            
            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100">
                <h3 class="text-lg font-black text-slate-800 mb-4 flex items-center"><i class="fa-solid fa-id-card text-[#0097B2] mr-3"></i> General Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="w-full relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Select Patient *</label>
                        <i class="fa-solid fa-magnifying-glass absolute left-5 top-[2.4rem] text-slate-400"></i>
                        <select name="patient_id" required class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition appearance-none">
                            <option value="">-- Select a patient --</option>
                            <?php while($p = mysqli_fetch_assoc($patient_result)): ?>
                                <option value="<?php echo $p['PATIENT_ID']; ?>"><?php echo htmlspecialchars($p['NAME'] . ' (' . $p['IC_NUMBER'] . ')'); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Visual Acuity Results</label>
                        <input type="text" name="visual_acuity_results" placeholder="e.g., 6/12 OD, 6/9 OS" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700 transition">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 border-t-4 border-t-[#0097B2]">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center justify-between">
                        <span><i class="fa-regular fa-eye text-[#0097B2] mr-3"></i> Right Eye (OD)</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Sphere (SPH)</label>
                            <input type="text" name="re_sph" placeholder="-0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Cylinder (CYL)</label>
                            <input type="text" name="re_cyl" placeholder="-0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Axis</label>
                            <input type="text" name="re_axis" placeholder="180" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">ADD Power</label>
                            <input type="text" name="re_add" placeholder="+1.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 border-t-4 border-t-[#B9D977]">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center justify-between">
                        <span><i class="fa-regular fa-eye text-[#B9D977] mr-3"></i> Left Eye (OS)</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Sphere (SPH)</label>
                            <input type="text" name="le_sph" placeholder="-0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Cylinder (CYL)</label>
                            <input type="text" name="le_cyl" placeholder="-0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Axis</label>
                            <input type="text" name="le_axis" placeholder="180" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">ADD Power</label>
                            <input type="text" name="le_add" placeholder="+1.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100">
                <h3 class="text-lg font-black text-slate-800 mb-4 flex items-center"><i class="fa-solid fa-file-medical text-[#0097B2] mr-3"></i> Clinical Assessment</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Prescription Result / Diagnosis</label>
                        <input type="text" name="prescription_result" placeholder="e.g., Myopia with Astigmatism" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Pupillary Distance (PD)</label>
                        <input type="text" name="pd" placeholder="e.g., 62mm" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Clinical Notes</label>
                    <textarea name="clinical_notes" rows="4" placeholder="Enter detailed findings, complaints, or specific lens recommendations..." class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-medium text-slate-700 resize-none"></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-4 pb-10">
                <button type="reset" class="px-8 py-4 bg-white text-slate-400 font-bold rounded-2xl hover:bg-slate-50 hover:text-slate-600 transition border border-slate-200 shadow-sm">
                    Clear Form
                </button>
                <button type="submit" name="save_exam" class="px-8 py-4 bg-[#0097B2] text-white font-bold rounded-2xl shadow-lg shadow-teal-100 hover:scale-105 transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Clinical Record
                </button>
            </div>
            
        </form>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const opticalFields = document.querySelectorAll('input[name="re_sph"], input[name="re_cyl"], input[name="re_add"], input[name="le_sph"], input[name="le_cyl"], input[name="le_add"]');

            opticalFields.forEach(field => {
                field.addEventListener('blur', function() {
                    let val = this.value.trim();
                    
                    if (val !== "") {
                        if (/^[-+]?\d+$/.test(val)) {
                            let numericVal = parseInt(val) / 100;
                            let formattedVal = numericVal.toFixed(2);
                            
                            if (numericVal > 0 && !formattedVal.startsWith('+')) {
                                formattedVal = '+' + formattedVal;
                            }
                            this.value = formattedVal;
                        } 
                        else if (!isNaN(parseFloat(val))) {
                             let numericVal = parseFloat(val);
                             let formattedVal = numericVal.toFixed(2);
                             if (numericVal > 0 && !formattedVal.startsWith('+')) {
                                formattedVal = '+' + formattedVal;
                            }
                            this.value = formattedVal;
                        }
                    }
                });
            });
        });
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #0097B2; }
    </style>
</body>
</html>