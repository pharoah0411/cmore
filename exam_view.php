<?php
include('config.php');

if (!isset($_SESSION['ROLE']) || ($_SESSION['ROLE'] !== 'Admin' && $_SESSION['ROLE'] !== 'Optometrist')) {
    header("Location: directory.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: exam.php");
    exit();
}

$exam_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT e.*, p.NAME as PATIENT_NAME, p.IC_NUMBER, p.PHONE_NUMBER, u.NAME as OPTOMETRIST_NAME 
          FROM eye_examination e 
          JOIN patient p ON e.PATIENT_ID = p.PATIENT_ID 
          JOIN user u ON e.OPTOMETRIST_ID = u.USER_ID 
          WHERE e.EXAM_ID = '$exam_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: exam.php");
    exit();
}

$exam = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Details | C-More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } @media print { sidebar, .no-print { display: none !important; } main { margin-left: 0 !important; padding: 0 !important; } } </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 h-screen overflow-y-auto p-10 custom-scrollbar">
        
        <div class="mb-8 flex justify-between items-end no-print">
            <div>
                <a href="exam.php" class="text-sm font-bold text-slate-400 hover:text-[#0097B2] transition flex items-center mb-2">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Exam Records
                </a>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">Clinical Record #<?php echo $exam['EXAM_ID']; ?></h1>
                <p class="text-slate-500 font-medium mt-1">Detailed patient refraction and health assessment.</p>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="printPrescription()" class="px-5 py-3 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition shadow-sm flex items-center">
                    <i class="fa-solid fa-print mr-2"></i> Print Prescription
                </button>
                <a href="exam_edit.php?id=<?php echo $exam['EXAM_ID']; ?>" class="px-5 py-3 bg-[#0097B2] text-white font-bold rounded-xl shadow-lg shadow-teal-100 hover:scale-105 transition flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Record
                </a>
            </div>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
            <div class="bg-teal-50 text-teal-600 p-4 rounded-2xl mb-6 text-sm font-bold border border-teal-100 flex items-center shadow-sm no-print">
                <i class="fa-solid fa-circle-check mr-3 text-lg"></i> Examination saved successfully!
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="bg-blue-50 text-blue-600 p-4 rounded-2xl mb-6 text-sm font-bold border border-blue-100 flex items-center shadow-sm no-print">
                <i class="fa-solid fa-circle-check mr-3 text-lg"></i> Examination updated successfully!
            </div>
        <?php endif; ?>

        <!-- Printable Report Area -->
        <div class="bg-white p-10 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100">
            
            <!-- Report Header -->
            <div class="border-b border-slate-100 pb-6 mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-[#0097B2]">C-More Optometry</h2>
                    <p class="text-sm text-slate-500 font-medium">Official Clinical Examination Record</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400">Exam Date</p>
                    <p class="font-bold text-slate-800"><?php echo date('d F Y', strtotime($exam['EXAM_DATE'])); ?></p>
                </div>
            </div>

            <!-- Patient & Doctor Info -->
            <div class="grid grid-cols-2 gap-8 mb-10">
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-4"><i class="fa-solid fa-user mr-2"></i> Patient Information</p>
                    <p class="text-lg font-black text-slate-800"><?php echo htmlspecialchars($exam['PATIENT_NAME']); ?></p>
                    <p class="text-sm text-slate-500 font-mono mt-1">IC: <?php echo htmlspecialchars($exam['IC_NUMBER']); ?></p>
                    <p class="text-sm text-slate-500 mt-1">Tel: <?php echo htmlspecialchars($exam['PHONE_NUMBER'] ?? 'N/A'); ?></p>
                </div>
                <div class="bg-[#0097B2]/5 p-6 rounded-2xl border border-[#0097B2]/10">
                    <p class="text-[10px] uppercase tracking-widest font-black text-[#0097B2] mb-4"><i class="fa-solid fa-user-doctor mr-2"></i> Attending Optometrist</p>
                    <p class="text-lg font-black text-slate-800"><?php echo htmlspecialchars($exam['OPTOMETRIST_NAME']); ?></p>
                    <p class="text-sm text-slate-500 mt-1">Diagnosis: <span class="font-bold text-slate-800"><?php echo htmlspecialchars($exam['PRESCRIPTION_RESULT'] ?? 'None'); ?></span></p>
                </div>
            </div>

            <!-- Refraction Table -->
            <div class="mb-10">
                <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-4"><i class="fa-solid fa-glasses mr-2"></i> Refraction Prescription</p>
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full text-center">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-widest text-slate-500 font-black">
                            <tr>
                                <th class="p-4 border-r border-slate-200 text-left">Eye</th>
                                <th class="p-4 border-r border-slate-200">Sphere (SPH)</th>
                                <th class="p-4 border-r border-slate-200">Cylinder (CYL)</th>
                                <th class="p-4 border-r border-slate-200">Axis</th>
                                <th class="p-4">ADD</th>
                            </tr>
                        </thead>
                        <tbody class="font-bold text-slate-800">
                            <tr class="border-b border-slate-100">
                                <td class="p-4 border-r border-slate-100 text-left bg-[#0097B2]/5 text-[#0097B2]">Right (OD)</td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['RE_SPH'] ?: '-'); ?></td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['RE_CYL'] ?: '-'); ?></td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['RE_AXIS'] ?: '-'); ?></td>
                                <td class="p-4 text-[#B9D977]"><?php echo htmlspecialchars($exam['RE_ADD'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="p-4 border-r border-slate-100 text-left bg-[#B9D977]/10 text-[#8db33e]">Left (OS)</td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['LE_SPH'] ?: '-'); ?></td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['LE_CYL'] ?: '-'); ?></td>
                                <td class="p-4 border-r border-slate-100"><?php echo htmlspecialchars($exam['LE_AXIS'] ?: '-'); ?></td>
                                <td class="p-4 text-[#B9D977]"><?php echo htmlspecialchars($exam['LE_ADD'] ?: '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-2">Visual Acuity (VA)</p>
                    <p class="font-bold text-slate-800 bg-slate-50 px-4 py-3 rounded-xl border border-slate-100"><?php echo htmlspecialchars($exam['VISUAL_ACUITY_RESULTS'] ?: 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-2">Pupillary Distance (PD)</p>
                    <p class="font-bold text-slate-800 bg-slate-50 px-4 py-3 rounded-xl border border-slate-100"><?php echo htmlspecialchars($exam['PD'] ?: 'N/A'); ?></p>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-2">Clinical Notes & Findings</p>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 text-slate-700 font-medium whitespace-pre-wrap leading-relaxed">
                    <?php echo htmlspecialchars($exam['CLINICAL_NOTES'] ?: 'No additional notes provided.'); ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Hidden Prescription Print Template -->
    <div id="prescriptionPrintTemplate" class="hidden">
        <div style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
            <!-- Header -->
            <div style="text-align: center; border-bottom: 2px solid #0097B2; padding-bottom: 20px; margin-bottom: 30px;">
                <h1 style="margin: 0; color: #0097B2; font-size: 28px;">C-More Optometry</h1>
                <p style="margin: 5px 0; color: #666; font-size: 14px;">Optical Prescription</p>
            </div>

            <!-- Patient Information -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Patient Information</h3>
                <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; width: 30%; font-weight: bold;">Name:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['PATIENT_NAME']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">IC Number:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['IC_NUMBER']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Phone:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['PHONE_NUMBER'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Exam Date:</td>
                        <td style="padding: 8px 0;"><?php echo date('d F Y', strtotime($exam['EXAM_DATE'])); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Refraction Prescription -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Refraction Prescription</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background-color: #f5f5f5; border-bottom: 2px solid #0097B2;">
                            <th style="padding: 10px; text-align: left; font-weight: bold;">Eye</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold;">Sphere (SPH)</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold;">Cylinder (CYL)</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold;">Axis</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold;">ADD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 12px; font-weight: bold; color: #0097B2;">Right Eye (OD)</td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['RE_SPH'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['RE_CYL'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['RE_AXIS'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['RE_ADD'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; font-weight: bold; color: #8db33e;">Left Eye (OS)</td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['LE_SPH'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['LE_CYL'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['LE_AXIS'] ?: '-'); ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo htmlspecialchars($exam['LE_ADD'] ?: '-'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Additional Information -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Assessment Details</h3>
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; width: 30%; font-weight: bold;">Diagnosis:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['PRESCRIPTION_RESULT'] ?? 'None'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Visual Acuity:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['VISUAL_ACUITY_RESULTS'] ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">PD:</td>
                        <td style="padding: 8px 0;"><?php echo htmlspecialchars($exam['PD'] ?: 'N/A'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Clinical Notes -->
            <?php if (!empty($exam['CLINICAL_NOTES'])): ?>
            <div style="margin-bottom: 30px;">
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Clinical Notes</h3>
                <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555; white-space: pre-wrap;"><?php echo htmlspecialchars($exam['CLINICAL_NOTES']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div style="margin-top: 40px; border-top: 1px solid #ccc; padding-top: 20px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 12px;">
                    <strong>Optometrist:</strong> <?php echo htmlspecialchars($exam['OPTOMETRIST_NAME']); ?><br>
                    <strong>Date:</strong> <?php echo date('d F Y', strtotime($exam['EXAM_DATE'])); ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        function printPrescription() {
            // Get the prescription template
            const prescriptionElement = document.getElementById('prescriptionPrintTemplate');
            const prescriptionContent = prescriptionElement.innerHTML;
            
            // Create a new window for printing
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write(prescriptionContent);
            printWindow.document.close();
            
            // Wait for content to load before printing
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
    </script>
</body>
</html>