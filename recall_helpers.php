<?php
if (!function_exists('follow_up_months')) {
    function follow_up_months($interval) {
        $value = strtolower(trim((string)$interval));
        if (preg_match('/(\d+)\s*month/', $value, $matches)) return (int)$matches[1];
        if (preg_match('/(\d+)\s*year/', $value, $matches)) return (int)$matches[1] * 12;
        return null;
    }
}

if (!function_exists('get_due_recall_rows')) {
    function get_due_recall_rows($conn, $limit = 0) {
        $rows = [];
        $sql = "SELECT p.PATIENT_ID, p.NAME, p.PHONE_NUMBER, p.FOLLOW_UP_INTERVAL, e.EXAM_DATE AS last_exam
                FROM PATIENT p
                JOIN EYE_EXAMINATION e ON e.PATIENT_ID = p.PATIENT_ID
                JOIN (SELECT PATIENT_ID, MAX(EXAM_DATE) AS latest_exam FROM EYE_EXAMINATION GROUP BY PATIENT_ID) latest
                    ON latest.PATIENT_ID = e.PATIENT_ID AND latest.latest_exam = e.EXAM_DATE
                WHERE p.FOLLOW_UP_INTERVAL IS NOT NULL AND TRIM(p.FOLLOW_UP_INTERVAL) <> ''
                ORDER BY e.EXAM_DATE ASC, p.NAME ASC";
        $result = mysqli_query($conn, $sql);
        if (!$result) return $rows;

        $today = new DateTimeImmutable('today');
        while ($row = mysqli_fetch_assoc($result)) {
            $months = follow_up_months($row['FOLLOW_UP_INTERVAL']);
            if ($months === null) continue;
            $recall_date = (new DateTimeImmutable($row['last_exam']))->modify('+' . $months . ' months');
            if ($recall_date > $today) continue;
            $row['recall_date'] = $recall_date->format('Y-m-d');
            $rows[] = $row;
        }

        usort($rows, function ($first, $second) {
            $date_order = strcmp($first['recall_date'], $second['recall_date']);
            return $date_order !== 0 ? $date_order : strcasecmp($first['NAME'], $second['NAME']);
        });
            if ($limit > 0) $rows = array_slice($rows, 0, $limit);
        return $rows;
    }
}
