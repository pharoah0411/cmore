<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Eye Exams</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Clinical Eye Examinations</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
            $sql = "SELECT E.*, P.NAME as PATIENT_NAME, U.NAME as DOC_NAME 
                    FROM EYE_EXAMINATION E
                    JOIN PATIENT P ON E.PATIENT_ID = P.PATIENT_ID
                    JOIN USER U ON E.OPTOMETRIST_ID = U.USER_ID";
            $res = mysqli_query($conn, $sql);
            
            while($row = mysqli_fetch_assoc($res)):
            ?>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800"><?php echo $row['PATIENT_NAME']; ?></h3>
                        <p class="text-sm text-slate-400 italic">Examined by: <?php echo $row['DOC_NAME']; ?></p>
                    </div>
                    <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-bold"><?php echo $row['EXAM_DATE']; ?></span>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-slate-50 p-4 rounded-xl border-l-4 border-blue-500">
                        <p class="text-[10px] uppercase font-bold text-slate-400 mb-1">Prescription Result</p>
                        <p class="text-slate-700 font-mono"><?php echo $row['PRESCRIPTION_RESULT']; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400 mb-1">Clinical Notes</p>
                        <p class="text-sm text-slate-600 leading-relaxed"><?php echo $row['CLINICAL_NOTES']; ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>