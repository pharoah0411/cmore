<?php
// ==========================================
// C MORE - AI ASSISTANT BACKEND HANDLER
// ==========================================
session_start(); // Enables short-term memory for pronouns (he, she, his)
require_once 'config.php';
header('Content-Type: application/json');

// 1. Get the floating widget's input
$data = json_decode(file_get_contents('php://input'), true);
$json_error = json_last_error();
$user_query = trim($data['query'] ?? '');

if ($json_error !== JSON_ERROR_NONE || !is_array($data) || empty($user_query)) {
    echo json_encode(['reply' => 'Please ask a question.']);
    exit;
}

// Avoid an API round-trip for common requests that never need database routing.
$normalized_query = strtolower(preg_replace('/\s+/', ' ', $user_query));
$fast_replies = [
    'hi' => "Hello! I can help with patients, prescriptions, stock, appointments, billing, and suppliers.",
    'hello' => "Hello! I can help with patients, prescriptions, stock, appointments, billing, and suppliers.",
    'hey' => "Hello! I can help with patients, prescriptions, stock, appointments, billing, and suppliers.",
    'help' => "Try asking: <i>Find John</i>, <i>What is Jane's prescription?</i>, <i>Stock for Ray-Ban</i>, or <i>When is John's appointment?</i>"
];
if (isset($fast_replies[$normalized_query])) {
    echo json_encode(['reply' => $fast_replies[$normalized_query], 'redirect_url' => null]);
    exit;
}

// Keep model latency and prompt size bounded for the shared floating widget.
$user_query = substr($user_query, 0, 500);

// 2. Safely Extract the API Key from .env
$env_file_path = __DIR__ . '/.env';
$apiKey = '';

if (file_exists($env_file_path)) {
    $lines = file($env_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        if (strpos($line, 'GEMINI_API_KEY=') === 0) {
            $parts = explode('=', $line, 2); 
            $apiKey = trim($parts[1], " \t\n\r\0\x0B\"'"); 
            break;
        }
    }
}

if (empty($apiKey)) {
    $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
}

if (empty($apiKey)) {
    echo json_encode(['reply' => 'System Error: API Key configuration is missing.']);
    exit;
}

// 3. Prepare a concise routing prompt with the clinic's supported capabilities.
$system_instruction = "You are a backend database routing assistant for an optical clinic management system named 'C More'. "
                    . "The current date is " . date('Y-m-d') . ". The logged-in staff role is " . ($_SESSION['ROLE'] ?? 'Staff') . ". "
                    . "Read the staff's natural language query and convert it into a structured JSON routing object. "
                    . "Analyze the query and extract the 'intent' and the 'search_term'.\n\n"
                    . "Allowed values for 'intent':\n"
                    . "- 'find_patient' (when looking for a customer/patient name, contact info, or complaints)\n"
                    . "- 'check_prescription' (when looking for eye exam, visual acuity, or prescription details for a person)\n"
                    . "- 'check_stock' (when looking for product inventory, item stock levels, or prices)\n"
                    . "- 'check_appointment' (when looking for schedules, bookings, or visit dates for a person)\n"
                    . "- 'check_sales' (when looking for billing, amount owed, or payment status for a person)\n"
                    . "- 'find_supplier' (when looking for vendor, supplier, or wholesale company contact info)\n"
                    . "- 'check_low_stock' (when asking which products need restocking or have low stock)\n"
                    . "- 'check_expiry' (when asking about expired or soon-to-expire products)\n"
                    . "- 'check_today' (when asking about today's appointments or schedule)\n"
                    . "- 'market_insights' (when asking about target markets, customer segments, best-selling categories, or what to promote)\n"
                    . "- 'general' (for greetings, help, or unrelated questions)\n\n"
                    . "Use only the person's name or product/category name as search_term. Remove words such as 'find', 'stock', 'appointment', 'prescription', 'sales', and 'price' from it. For general questions, use an empty search_term. "
                    . "Return ONLY a raw JSON object with exactly the keys 'intent' and 'search_term'. Do not include markdown. "
                    . "Example Output: {\"intent\": \"check_appointment\", \"search_term\": \"John\"}";

// 4. Make the API call with short timeouts and one lightweight fallback.
$models = ["gemini-2.5-flash", "gemini-2.5-flash-lite"];
$response = false;
$http_code = 0;
$curl_error = '';

foreach ($models as $model_name) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model_name . ":generateContent?key=" . $apiKey;
    
    $payload = [
        "systemInstruction" => ["parts" => [["text" => $system_instruction]]],
        "contents" => [["role" => "user", "parts" => [["text" => $user_query]]]],
        "generationConfig" => [
            "temperature" => 0.1,
            "maxOutputTokens" => 80,
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code == 200 && !$curl_error) break;
}

if ($curl_error || $http_code != 200) {
    echo json_encode(['reply' => 'The AI engine is currently overloaded. Please try again in a moment.']);
    exit;
}

// 5. Parse JSON & Handle Short-Term Memory
$response_data = json_decode($response, true);
$ai_raw_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$ai_raw_text) {
    echo json_encode(['reply' => 'The assistant could not understand that request. Please try again.']);
    exit;
}

// Models occasionally wrap valid JSON in markdown despite the JSON response mode.
$routing_text = trim($ai_raw_text);
$routing_text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $routing_text);
$routing = json_decode($routing_text, true);
if (!is_array($routing)) {
    $json_start = strpos($routing_text, '{');
    $json_end = strrpos($routing_text, '}');
    if ($json_start !== false && $json_end > $json_start) {
        $routing = json_decode(substr($routing_text, $json_start, $json_end - $json_start + 1), true);
    }
}
$allowed_intents = ['find_patient', 'check_prescription', 'check_stock', 'check_appointment', 'check_sales', 'find_supplier', 'check_low_stock', 'check_expiry', 'check_today', 'market_insights', 'general'];
$intent = is_array($routing) && in_array($routing['intent'] ?? '', $allowed_intents, true)
    ? $routing['intent']
    : 'general';
$search_term = is_array($routing) ? trim((string)($routing['search_term'] ?? '')) : '';
$search_term = preg_replace('/\s+/', ' ', $search_term);
$search_term = trim($search_term, " \t\n\r\0\x0B'\"");

// Keep routing useful when the model repeats the request instead of extracting the entity.
if ($intent !== 'general' && $search_term !== '') {
    $search_term = preg_replace('/^(find|show|check|what is|what are|when is|has|contact for|stock for|price of)\s+/i', '', $search_term);
    $search_term = preg_replace('/\s+(prescription|appointment|appointments|sales|billing|stock|price|prices)\s*\??$/i', '', $search_term);
    $search_term = trim($search_term, " \t\n\r\0\x0B'\"");
}

// CONTEXT MEMORY: Inject previous patient name if pronoun is used
if (empty($search_term) || in_array(strtolower($search_term), ['he', 'she', 'his', 'her', 'him', 'they', 'them', 'their'])) {
    if (!empty($_SESSION['last_searched_patient']) && in_array($intent, ['find_patient', 'check_prescription', 'check_appointment', 'check_sales'])) {
        $search_term = $_SESSION['last_searched_patient'];
    }
}

// Save current search term to memory if it relates to a person
if (!empty($search_term) && in_array($intent, ['find_patient', 'check_prescription', 'check_appointment', 'check_sales'])) {
    $_SESSION['last_searched_patient'] = $search_term;
}

// 6. Execute Database Query Based on Intent
$reply = "";
$redirect_url = null; // <--- ADD THIS VARIABLE HERE

switch ($intent) {
    case 'find_patient':
        $stmt = $conn->prepare("SELECT PATIENT_ID, NAME, PHONE_NUMBER, FOLLOW_UP_INTERVAL FROM patient WHERE NAME LIKE ? LIMIT 3");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // NEW LOGIC: If exactly ONE patient is found, trigger a redirect!
            $row = $result->fetch_assoc();
            $reply = "I found <b>" . htmlspecialchars($row['NAME']) . "</b>. Taking you to their profile now...";
            $redirect_url = "patient_details.php?id=" . $row['PATIENT_ID'];
        } elseif ($result->num_rows > 1) {
            // Keep existing logic if multiple patients are found
            $reply = "I found these patients matching '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• <a href='patient_details.php?id=" . $row['PATIENT_ID'] . "' class='btn-link'>" . htmlspecialchars($row['NAME']) . "</a> - Tel: " . htmlspecialchars($row['PHONE_NUMBER']) . "<br>";
            }
        } else {
            $reply = "I couldn't find any patients named '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'check_prescription':
        $stmt = $conn->prepare("SELECT e.EXAM_ID AS exam_id, p.NAME, e.EXAM_DATE, e.PRESCRIPTION_RESULT FROM eye_examination e JOIN patient p ON e.PATIENT_ID = p.PATIENT_ID WHERE p.NAME LIKE ? ORDER BY e.EXAM_DATE DESC LIMIT 3");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reply = "Here are the prescription files found for '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $result_text = !empty($row['PRESCRIPTION_RESULT']) ? " - " . htmlspecialchars($row['PRESCRIPTION_RESULT']) : "";
                $reply .= "• <a href='exam_view.php?id=" . $row['exam_id'] . "'>Exam Record (" . $row['EXAM_DATE'] . ")</a>" . $result_text . "<br>";
            }
        } else {
            $reply = "No prescription history found matching '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'check_stock':
        $stmt = $conn->prepare("SELECT BRAND_NAME, STOCK_QUANTITY, UNIT_PRICE FROM product WHERE BRAND_NAME LIKE ? OR CATEGORY LIKE ? LIMIT 5");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("ss", $like_term, $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reply = "Inventory results for '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• " . htmlspecialchars($row['BRAND_NAME']) . ": <b>" . $row['STOCK_QUANTITY'] . " units</b> (RM " . number_format($row['UNIT_PRICE'], 2) . ")<br>";
            }
        } else {
            $reply = "I couldn't find any stock matching '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'check_appointment':
        $stmt = $conn->prepare("SELECT a.APPOINTMENT_DATETIME, a.STATUS, p.NAME FROM appointment a JOIN patient p ON a.PATIENT_ID = p.PATIENT_ID WHERE p.NAME LIKE ? ORDER BY a.APPOINTMENT_DATETIME DESC LIMIT 3");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reply = "Appointments found for '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• " . htmlspecialchars($row['NAME']) . " - " . date("d M Y, h:i A", strtotime($row['APPOINTMENT_DATETIME'])) . " (<b>" . htmlspecialchars($row['STATUS']) . "</b>)<br>";
            }
        } else {
            $reply = "No appointments found for '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'check_sales':
        $stmt = $conn->prepare("SELECT s.SALE_ID, s.SALE_DATE, s.TOTAL_AMOUNT, s.PAID_AMOUNT, s.PAYMENT_STATUS, p.NAME FROM sales s JOIN patient p ON s.PATIENT_ID = p.PATIENT_ID WHERE p.NAME LIKE ? ORDER BY s.SALE_DATE DESC LIMIT 3");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reply = "Billing records for '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $status_color = ($row['PAYMENT_STATUS'] == 'Completed') ? "green" : "red";
                $reply .= "• <a href='sales_view.php?id=" . $row['SALE_ID'] . "'>Sale on " . date("d M Y", strtotime($row['SALE_DATE'])) . "</a> - RM " . number_format($row['TOTAL_AMOUNT'], 2) . " (<span style='color:$status_color; font-weight:bold;'>" . htmlspecialchars($row['PAYMENT_STATUS']) . "</span>)<br>";
            }
        } else {
            $reply = "No sales records found for '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'find_supplier':
        $stmt = $conn->prepare("SELECT COMPANY_NAME, CONTACT_PERSON, PHONE_NUMBER, EMAIL FROM supplier WHERE COMPANY_NAME LIKE ? OR CONTACT_PERSON LIKE ? LIMIT 3");
        $like_term = "%" . $search_term . "%";
        $stmt->bind_param("ss", $like_term, $like_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reply = "Suppliers matching '<b>" . htmlspecialchars($search_term) . "</b>':<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• <b>" . htmlspecialchars($row['COMPANY_NAME']) . "</b> (" . htmlspecialchars($row['CONTACT_PERSON']) . ")<br>  Tel: " . htmlspecialchars($row['PHONE_NUMBER']) . " | Email: " . htmlspecialchars($row['EMAIL']) . "<br>";
            }
        } else {
            $reply = "I couldn't find any suppliers matching '" . htmlspecialchars($search_term) . "'.";
        }
        $stmt->close();
        break;

    case 'check_low_stock':
        $result = $conn->query("SELECT BRAND_NAME, STOCK_QUANTITY, UNIT_PRICE FROM product WHERE STOCK_QUANTITY < 5 ORDER BY STOCK_QUANTITY ASC LIMIT 10");
        if ($result && $result->num_rows > 0) {
            $reply = "Products needing restocking:<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• " . htmlspecialchars($row['BRAND_NAME']) . ": <b>" . (int)$row['STOCK_QUANTITY'] . " units</b> (RM " . number_format($row['UNIT_PRICE'], 2) . ")<br>";
            }
        } else {
            $reply = "No products are currently below the restock threshold.";
        }
        break;

    case 'check_expiry':
        $result = $conn->query("SELECT BRAND_NAME, STOCK_QUANTITY, EXPIRY_DATE FROM product WHERE EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY EXPIRY_DATE ASC LIMIT 10");
        if ($result && $result->num_rows > 0) {
            $reply = "Expired or soon-to-expire products:<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• " . htmlspecialchars($row['BRAND_NAME']) . ": <b>" . htmlspecialchars($row['EXPIRY_DATE']) . "</b> (" . (int)$row['STOCK_QUANTITY'] . " units)<br>";
            }
        } else {
            $reply = "No products are expired or due to expire within 90 days.";
        }
        break;

    case 'check_today':
        $result = $conn->query("SELECT a.APPOINTMENT_DATETIME, a.STATUS, p.NAME FROM appointment a JOIN patient p ON a.PATIENT_ID = p.PATIENT_ID WHERE DATE(a.APPOINTMENT_DATETIME) = CURDATE() ORDER BY a.APPOINTMENT_DATETIME ASC");
        if ($result && $result->num_rows > 0) {
            $reply = "Today's appointments:<br>";
            while ($row = $result->fetch_assoc()) {
                $reply .= "• " . htmlspecialchars($row['NAME']) . " - " . date("h:i A", strtotime($row['APPOINTMENT_DATETIME'])) . " (<b>" . htmlspecialchars($row['STATUS']) . "</b>)<br>";
            }
        } else {
            $reply = "There are no appointments scheduled for today.";
        }
        break;

    case 'market_insights':
        $category_result = $conn->query("SELECT COALESCE(NULLIF(p.CATEGORY, ''), 'Other') AS category, SUM(si.QUANTITY) AS units, SUM(si.QUANTITY * p.UNIT_PRICE) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON p.PRODUCT_ID = si.PRODUCT_ID GROUP BY p.CATEGORY ORDER BY revenue DESC LIMIT 5");
        $reply = "Target market signals from current sales:<br>";
        if ($category_result && $category_result->num_rows > 0) {
            while ($row = $category_result->fetch_assoc()) {
                $reply .= "• <b>" . htmlspecialchars($row['category']) . " audience</b>: " . (int)$row['units'] . " units sold, RM " . number_format($row['revenue'], 2) . " attributed revenue<br>";
            }
        } else {
            $reply .= "There is not enough sales data to identify a leading category yet.<br>";
        }
        $repeat_result = $conn->query("SELECT COUNT(*) AS total FROM (SELECT PATIENT_ID FROM SALES WHERE PATIENT_ID IS NOT NULL GROUP BY PATIENT_ID HAVING COUNT(*) >= 2) repeat_customers");
        $repeat_count = $repeat_result ? (int)$repeat_result->fetch_assoc()['total'] : 0;
        $reply .= "• <b>Repeat customer opportunity</b>: " . $repeat_count . " patients have made two or more purchases.<br>";
        $reply .= "Recommendation: promote the leading category first, then target repeat customers with upgrades and due recall patients with appointment reminders.";
        break;

    default:
        $reply = "I am your clinic assistant. You can ask me to:<br>"
               . "• Find a patient (e.g., <i>'Find Farah'</i>)<br>"
               . "• Check prescriptions (e.g., <i>'What is her prescription?'</i>)<br>"
               . "• Check stock & prices (e.g., <i>'Stock for Ray-Ban'</i>)<br>"
               . "• Check appointments (e.g., <i>'When is John's appointment?'</i>)<br>"
               . "• Check billing (e.g., <i>'Has Robert paid?'</i>)<br>"
               . "• Find suppliers (e.g., <i>'Contact for Luxottica'</i>)<br>"
               . "• Check low stock, expiry dates, or today's appointments";
        break;
}

echo json_encode([
    'reply' => $reply, 
    'redirect_url' => $redirect_url
]); 
?>