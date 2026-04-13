<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Eye Exams</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen">
    
    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Eye Examinations</h1>
                <p class="text-slate-500 font-medium mt-1">Review clinical results and prescription histories.</p>
            </div>
            <button class="bg-[#0097B2] text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 hover:scale-105 transition-all">
                <i class="fa-solid fa-microscope mr-2"></i> New Examination
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php
            $sql = "SELECT E.*, P.NAME as PATIENT_NAME, U.NAME as DOC_NAME 
                    FROM EYE_EXAMINATION E
                    JOIN PATIENT P ON E.PATIENT_ID = P.PATIENT_ID
                    JOIN USER U ON E.OPTOMETRIST_ID = U.USER_ID";
            $res = mysqli_query($conn, $sql);
            
            while($row = mysqli_fetch_assoc($res)):
            ?>
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-[#B9D977]/10 rounded-full -mr-12 -mt-12 blur-2xl"></div>
                
                <div class="flex justify-between items-start mb-8 relative z-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 leading-tight"><?php echo $row['PATIENT_NAME']; ?></h3>
                        <p class="text-xs font-bold text-[#0097B2] uppercase tracking-wider mt-1">
                            <i class="fa-solid fa-user-doctor mr-2"></i>Optometrist: <?php echo $row['DOC_NAME']; ?>
                        </p>
                    </div>
                    <span class="bg-slate-900 text-[#B9D977] px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg">
                        <?php echo date('d M Y', strtotime($row['EXAM_DATE'])); ?>
                    </span>
                </div>
                
                <div class="space-y-6 relative z-10">
                    <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <div class="flex items-center space-x-2 mb-3">
                            <i class="fa-solid fa-file-prescription text-[#0097B2]"></i>
                            <p class="text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Prescription Result</p>
                        </div>
                        <p class="text-slate-700 font-mono font-bold text-lg"><?php echo $row['PRESCRIPTION_RESULT']; ?></p>
                    </div>
                    
                    <div>
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-[0.15em] mb-2 px-1">Clinical Observations</p>
                        <p class="text-sm text-slate-500 leading-relaxed italic px-1">
                            "<?php echo $row['CLINICAL_NOTES']; ?>"
                        </p>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-50 flex justify-end">
                    <button class="text-[#0097B2] text-xs font-black uppercase tracking-widest hover:text-slate-900 transition-colors">
                        View Full Report <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>