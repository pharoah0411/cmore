<?php
include('config.php');

if (!isset($_SESSION['ROLE']) || !in_array($_SESSION['ROLE'], ['Admin', 'Optometrist'], true)) {
    systemLog($conn, 'Attempted unauthorized deletion of clinical exam');
    header('Location: directory.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['delete_exam'])) {
    header('Location: exam.php');
    exit();
}

$exam_id = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT);
if (!$exam_id) {
    header('Location: exam.php?msg=delete_error');
    exit();
}

$exam_stmt = mysqli_prepare($conn, "SELECT e.PATIENT_ID, p.NAME AS PATIENT_NAME FROM eye_examination e JOIN patient p ON e.PATIENT_ID = p.PATIENT_ID WHERE e.EXAM_ID = ?");
mysqli_stmt_bind_param($exam_stmt, 'i', $exam_id);
mysqli_stmt_execute($exam_stmt);
$exam_result = mysqli_stmt_get_result($exam_stmt);
$exam = mysqli_fetch_assoc($exam_result);
mysqli_stmt_close($exam_stmt);

if (!$exam) {
    header('Location: exam.php?msg=not_found');
    exit();
}

$delete_stmt = mysqli_prepare($conn, "DELETE FROM eye_examination WHERE EXAM_ID = ?");
mysqli_stmt_bind_param($delete_stmt, 'i', $exam_id);
$deleted = mysqli_stmt_execute($delete_stmt);
mysqli_stmt_close($delete_stmt);

if (!$deleted) {
    header('Location: exam.php?msg=delete_error');
    exit();
}

systemLog($conn, "Deleted clinical exam record for patient: {$exam['PATIENT_NAME']}", 'eye_examination', $exam_id);

$return_to = $_POST['return_to'] ?? 'exam';
if ($return_to === 'patient') {
    $patient_id = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
    if ($patient_id) {
        header('Location: patient_details.php?id=' . $patient_id . '&msg=exam_deleted');
        exit();
    }
}

header('Location: exam.php?msg=deleted');
exit();
