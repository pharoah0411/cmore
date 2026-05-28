<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

function pdf_escape_text($text)
{
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
}

function pdf_build($title, array $headers, array $rows)
{
    $content = "";
    $content .= "BT /F1 18 Tf 50 790 Td (" . pdf_escape_text($title) . ") Tj ET\n";

    $y = 760;
    $page_width = 520;
    $col_count = max(1, count($headers));
    $col_width = floor($page_width / $col_count);

    $content .= "BT /F1 12 Tf 50 " . $y . " Td\n";
    foreach ($headers as $index => $header) {
        $content .= "(" . pdf_escape_text(mb_strimwidth($header, 0, 40, '...')) . ") Tj ";
        $content .= sprintf("%.0f 0 Td ", $col_width);
    }
    $content .= "ET\n";
    $y -= 20;

    foreach ($rows as $row) {
        if ($y < 60) {
            break; // avoid multiple-page complexity
        }

        // Ensure row has enough cells to match headers
        $row = array_values($row);
        $row = array_pad($row, count($headers), '');

        $content .= "BT /F1 10 Tf 50 " . $y . " Td\n";
        foreach ($row as $cellIndex => $cell) {
            // Only output cells up to header count
            if ($cellIndex >= count($headers)) {
                break;
            }
            $cell_text = pdf_escape_text(mb_strimwidth((string)$cell, 0, 40, '...'));
            $content .= "(" . $cell_text . ") Tj ";
            $content .= sprintf("%.0f 0 Td ", $col_width);
        }
        $content .= "ET\n";
        $y -= 16;
    }

    $stream = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";
    $objects[] = "2 0 obj\n<< /ProcSet [/PDF /Text] /Font << /F1 1 0 R >> >>\nendobj";
    $objects[] = "3 0 obj\n" . $stream . "\nendobj";
    $objects[] = "4 0 obj\n<< /Type /Page /Parent 5 0 R /MediaBox [0 0 595 842] /Contents 3 0 R /Resources 2 0 R >>\nendobj";
    $objects[] = "5 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj";
    $objects[] = "6 0 obj\n<< /Type /Catalog /Pages 5 0 R >>\nendobj";

    $pdf = "%PDF-1.4\n%âãÏÓ\n";
    $offsets = [];
    $current_offset = strlen($pdf);
    foreach ($objects as $obj) {
        $offsets[] = $current_offset;
        $pdf .= $obj . "\n";
        $current_offset = strlen($pdf);
    }

    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $xref .= str_pad((string)$offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 6 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF";

    return $pdf;
}

function export_pdf($filename, $title, array $headers, array $rows)
{
    // Clean any existing output
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (headers_sent($file, $line)) {
        throw new Exception("Headers already sent in {$file} on line {$line}");
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo pdf_build($title, $headers, $rows);
    exit();
}

function export_excel($filename, array $headers, array $rows)
{
    // Clean any existing output
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (headers_sent($file, $line)) {
        throw new Exception("Headers already sent in {$file} on line {$line}");
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html><head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    echo '<style>
        th { background-color: #4472C4; color: white; font-weight: bold; }
        table { border-collapse: collapse; }
        td, th { border: 1px solid #ddd; padding: 4px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>';
    echo '</head><body>';
    echo '<table border="1" cellpadding="4" cellspacing="0">';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit();
}
