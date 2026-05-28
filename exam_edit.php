<?php
include('config.php');

if (!isset($_SESSION['ROLE']) || ($_SESSION['ROLE'] !== 'Admin' && $_SESSION['ROLE'] !== 'Optometrist')) {
    systemLog($conn, 'Attempted unauthorized access to Edit Clinical Exam');
    header("Location: directory.php");
    exit();
}

$error = "";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: exam.php");
    exit();
}

$exam_id = mysqli_real_escape_string($conn, $_GET['id']);

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_exam'])) {
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

    $sql = "UPDATE eye_examination SET 
            VISUAL_ACUITY_RESULTS = '$visual_acuity', PRESCRIPTION_RESULT = '$prescription', 
            RE_SPH = '$re_sph', RE_CYL = '$re_cyl', RE_AXIS = '$re_axis', RE_ADD = '$re_add', 
            LE_SPH = '$le_sph', LE_CYL = '$le_cyl', LE_AXIS = '$le_axis', LE_ADD = '$le_add', 
            PD = '$pd', CLINICAL_NOTES = '$clinical_notes' 
            WHERE EXAM_ID = '$exam_id'";
            
    if (mysqli_query($conn, $sql)) {
        systemLog($conn, 'Updated clinical exam record', 'eye_examination', $exam_id);
        header("Location: exam_view.php?id=" . $exam_id . "&msg=updated");
        exit();
    } else {
        $error = "Database Error: " . mysqli_error($conn);
    }
}

// Fetch Existing Data
$query = "SELECT e.*, p.NAME as PATIENT_NAME FROM eye_examination e JOIN patient p ON e.PATIENT_ID = p.PATIENT_ID WHERE e.EXAM_ID = '$exam_id'";
$result = mysqli_query($conn, $query);
$exam = mysqli_fetch_assoc($result);

if (!$exam) {
    header("Location: exam.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Exam | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 h-screen overflow-y-auto p-10 custom-scrollbar">
        
        <div class="mb-8">
            <a href="exam_view.php?id=<?php echo $exam_id; ?>" class="text-sm font-bold text-slate-400 hover:text-[#0097B2] transition flex items-center mb-2">
                <i class="fa-solid fa-arrow-left mr-2"></i> Cancel Editing
            </a>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Edit Exam: <?php echo htmlspecialchars($exam['PATIENT_NAME']); ?></h1>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded-2xl mb-6 text-sm font-bold border border-red-100 flex items-center shadow-sm">
                <i class="fa-solid fa-triangle-exclamation mr-3"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="exam_edit.php?id=<?php echo $exam_id; ?>" method="POST" class="space-y-6">
            
            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Visual Acuity Results</label>
                        <input type="text" name="visual_acuity_results" value="<?php echo htmlspecialchars($exam['VISUAL_ACUITY_RESULTS']); ?>" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 border-t-4 border-t-[#0097B2]">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center"><span><i class="fa-regular fa-eye text-[#0097B2] mr-3"></i> Right Eye (OD)</span></h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sphere (SPH)</label>
                            <input type="text" name="re_sph" value="<?php echo htmlspecialchars($exam['RE_SPH']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cylinder (CYL)</label>
                            <input type="text" name="re_cyl" value="<?php echo htmlspecialchars($exam['RE_CYL']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Axis</label>
                            <input type="text" name="re_axis" value="<?php echo htmlspecialchars($exam['RE_AXIS']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">ADD Power</label>
                            <input type="text" name="re_add" value="<?php echo htmlspecialchars($exam['RE_ADD']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700 text-center">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 border-t-4 border-t-[#B9D977]">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center"><span><i class="fa-regular fa-eye text-[#B9D977] mr-3"></i> Left Eye (OS)</span></h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sphere (SPH)</label>
                            <input type="text" name="le_sph" value="<?php echo htmlspecialchars($exam['LE_SPH']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cylinder (CYL)</label>
                            <input type="text" name="le_cyl" value="<?php echo htmlspecialchars($exam['LE_CYL']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Axis</label>
                            <input type="text" name="le_axis" value="<?php echo htmlspecialchars($exam['LE_AXIS']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">ADD Power</label>
                            <input type="text" name="le_add" value="<?php echo htmlspecialchars($exam['LE_ADD']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#B9D977] outline-none font-bold text-slate-700 text-center">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100">
                <h3 class="text-lg font-black text-slate-800 mb-4"><i class="fa-solid fa-file-medical text-[#0097B2] mr-3"></i> Clinical Assessment</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Diagnosis / Result</label>
                        <input type="text" name="prescription_result" value="<?php echo htmlspecialchars($exam['PRESCRIPTION_RESULT']); ?>" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">PD (Pupillary Distance)</label>
                        <input type="text" name="pd" value="<?php echo htmlspecialchars($exam['PD']); ?>" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-bold text-slate-700">
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Notes</label>
                    <textarea name="clinical_notes" rows="4" class="w-full px-4 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#0097B2] outline-none font-medium text-slate-700 resize-none"><?php echo htmlspecialchars($exam['CLINICAL_NOTES']); ?></textarea>
                </div>
            </div>

            <div class="flex justify-end pb-10">
                <button type="submit" name="update_exam" class="px-8 py-4 bg-[#0097B2] text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Update Record
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