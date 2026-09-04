<?php 
include('config.php'); 
include_once('recall_helpers.php');
$due_recalls = [];
foreach (get_due_recall_rows($conn) as $due_patient) {
    $due_recalls[$due_patient['PATIENT_ID']] = $due_patient;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | WhatsApp Messaging</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">WhatsApp Messaging</h1>
                <p class="text-slate-500 font-medium mt-1">Quick communication templates for follow-ups and reminders.</p>
            </div>
            <div class="bg-green-50 text-green-600 px-6 py-3 rounded-2xl font-bold shadow-sm border border-green-100 flex items-center">
                <i class="fa-brands fa-whatsapp text-xl mr-2"></i> Web API Active
            </div>
        </header>

        <div class="mb-8">
            <form action="" method="GET" class="relative max-w-xl">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" placeholder="Search Patient Name or Phone..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                       class="w-full pl-14 pr-6 py-4 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm focus:border-[#0097B2] outline-none transition-all">
            </form>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full text-left table-fixed">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="w-[30%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Patient Details</th>
                        <th class="w-[20%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact Number</th>
                        <th class="w-[50%] p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Quick Send Templates (Opens WhatsApp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    
                    // Fetch patients who actually have a phone number recorded
                    $sql = "SELECT PATIENT_ID, NAME, PHONE_NUMBER FROM patient WHERE PHONE_NUMBER IS NOT NULL AND PHONE_NUMBER != ''";
                    
                    if (!empty($search)) {
                        $sql .= " AND (NAME LIKE '%$search%' OR PHONE_NUMBER LIKE '%$search%')";
                    }
                    
                    $sql .= " ORDER BY NAME ASC";
                    $res = mysqli_query($conn, $sql);
                    
                    if(mysqli_num_rows($res) > 0):
                        while($row = mysqli_fetch_assoc($res)): 
                            $name = htmlspecialchars($row['NAME']);
                            $phone = htmlspecialchars($row['PHONE_NUMBER']);

                            // Format Malaysian Phone Number
                            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                            if (strpos($cleanPhone, '0') === 0) {
                                $cleanPhone = '6' . $cleanPhone; 
                            } elseif (strpos($cleanPhone, '60') !== 0) {
                                $cleanPhone = '60' . $cleanPhone; // Catch edge cases where neither 0 nor 60 was entered
                            }

                            // Define Message Templates
                            $msgFollowUp = "Hello $name, this is a friendly follow-up from C-More Optical. How are your new glasses treating you? Let us know if you need any adjustments!";
                            
                            $msgAppointment = "Hi $name, this is a reminder from C-More Optical regarding your upcoming eye examination appointment. Please let us know if you need to reschedule.";
                            
                            $msgTreatment = "Hello $name, we are reaching out from C-More Optical regarding your recent eye examination. Please ensure you are following the recommended eye care routine and contact us if you experience any discomfort.";

                            $msgRecall = "Hello $name, this is C-More Optical. Your eye examination follow-up is due. Please reply to arrange a convenient appointment.";

                            // URL Encode the messages for safe transport via link
                            $urlFollowUp = "https://wa.me/$cleanPhone?text=" . urlencode($msgFollowUp);
                            $urlAppointment = "https://wa.me/$cleanPhone?text=" . urlencode($msgAppointment);
                            $urlTreatment = "https://wa.me/$cleanPhone?text=" . urlencode($msgTreatment);
                            $urlRecall = "https://wa.me/$cleanPhone?text=" . urlencode($msgRecall);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-5">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-[#0097B2] shrink-0">
                                    <i class="fa-solid fa-user text-lg"></i>
                                </div>
                                <div class="truncate">
                                    <p class="font-bold text-slate-800 text-md leading-tight truncate"><?php echo $name; ?></p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mt-0.5">ID: P-<?php echo str_pad($row['PATIENT_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            </div>
                        </td>
                        
                        <td class="p-5">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-phone text-slate-400 text-xs"></i>
                                <span class="text-sm font-bold text-slate-700"><?php echo $phone; ?></span>
                            </div>
                        </td>

                        <td class="p-5">
                            <div class="flex items-center justify-center space-x-3">
                                <a href="<?php echo $urlFollowUp; ?>" target="_blank" class="flex items-center space-x-2 bg-[#0097B2] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#007b92] hover:shadow-md transition">
                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                    <span>Follow Up</span>
                                </a>
                                
                                <a href="<?php echo $urlAppointment; ?>" target="_blank" class="flex items-center space-x-2 bg-purple-500 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-purple-600 hover:shadow-md transition">
                                    <i class="fa-regular fa-calendar-check text-sm"></i>
                                    <span>Appointment</span>
                                </a>

                                <a href="<?php echo $urlTreatment; ?>" target="_blank" class="flex items-center space-x-2 bg-teal-500 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-teal-600 hover:shadow-md transition">
                                    <i class="fa-solid fa-notes-medical text-sm"></i>
                                    <span>Treatment</span>
                                </a>

                                <?php if(isset($due_recalls[$row['PATIENT_ID']])): ?>
                                <a href="<?php echo $urlRecall; ?>" target="_blank" class="flex items-center space-x-2 bg-green-500 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-green-600 hover:shadow-md transition">
                                    <i class="fa-solid fa-rotate text-sm"></i>
                                    <span>Recall Due</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="3" class="p-20 text-center">
                            <div class="inline-flex flex-col items-center justify-center text-slate-400">
                                <i class="fa-solid fa-address-book text-4xl mb-3 opacity-20"></i>
                                <p class="font-medium italic">No patients found with a recorded phone number.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>