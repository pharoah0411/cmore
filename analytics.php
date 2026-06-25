<?php
include('config.php');

/* =========================================================================
   C-MORE · ANALYTICS DATA LAYER
   Expects a mysqli connection from config.php. Most C-More pages use $conn —
   if yours is named differently, set it here:  $conn = $your_connection;
   Every query is wrapped so a failure degrades to 0 rather than a blank page.
   ========================================================================= */

if (!isset($conn) || !$conn) {
    // Fallbacks so the page still loads if the connection isn't shared here.
    if (isset($mysqli)) $conn = $mysqli;
    elseif (isset($con)) $conn = $con;
    elseif (isset($db))  $conn = $db;
}

function rows($conn, $sql){
    $out = [];
    if ($conn && ($res = @mysqli_query($conn, $sql))) {
        while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
    }
    return $out;
}
function one($conn, $sql){ $r = rows($conn, $sql); return $r[0] ?? []; }
function f($v){ return (float)($v ?? 0); }
function i($v){ return (int)($v ?? 0); }

/* ---- normalise the inconsistent CATEGORY spellings into 4 buckets ---- */
$CAT_CASE = "CASE
    WHEN CATEGORY IN ('Frame','Frames') THEN 'Frames'
    WHEN CATEGORY IN ('Contact Lens','Contact Lenses') THEN 'Contact Lenses'
    WHEN CATEGORY IN ('Solution','Solutions') THEN 'Solutions'
    ELSE 'Accessories' END";

/* ---- KPIs ---- */
$k = one($conn, "SELECT
        COALESCE(SUM(TOTAL_AMOUNT),0) revenue,
        COALESCE(SUM(PAID_AMOUNT),0) collected,
        COALESCE(SUM(GREATEST(TOTAL_AMOUNT-PAID_AMOUNT,0)),0) outstanding,
        COUNT(*) txns,
        COALESCE(AVG(TOTAL_AMOUNT),0) avgSale
    FROM sales");
$stock = one($conn, "SELECT
        COALESCE(SUM(STOCK_QUANTITY*UNIT_PRICE),0) stockValue,
        COALESCE(SUM(STOCK_QUANTITY),0) stockUnits,
        COUNT(*) skus
    FROM product");
$pCount = i(one($conn, "SELECT COUNT(*) c FROM patient")['c'] ?? 0);
$aCount = i(one($conn, "SELECT COUNT(*) c FROM appointment")['c'] ?? 0);
$collRate = f($k['revenue']) > 0 ? round(f($k['collected'])/f($k['revenue'])*100, 1) : 0;

/* ---- monthly series (filled across the full span so gaps show as 0) ---- */
$salesMonthly = rows($conn, "SELECT DATE_FORMAT(SALE_DATE,'%Y-%m') ym,
        SUM(TOTAL_AMOUNT) gross, SUM(PAID_AMOUNT) collected, COUNT(*) txns
    FROM sales GROUP BY ym ORDER BY ym");
$patMonthly = rows($conn, "SELECT DATE_FORMAT(REGISTRATION_DATE,'%Y-%m') ym, COUNT(*) c
    FROM patient GROUP BY ym ORDER BY ym");

function month_span($a, $b){
    $months = [];
    if (!$a) return $months;
    $cur = strtotime($a.'-01'); $end = strtotime($b.'-01');
    while ($cur <= $end){ $months[] = date('Y-m', $cur); $cur = strtotime('+1 month', $cur); }
    return $months;
}
$allYm = array_merge(array_column($salesMonthly,'ym'), array_column($patMonthly,'ym'));
sort($allYm);
$span = $allYm ? month_span($allYm[0], end($allYm)) : [];

$monthLabels = []; $gross = []; $collectedSeries = []; $txns = [];
$salesIdx = []; foreach ($salesMonthly as $r) $salesIdx[$r['ym']] = $r;
foreach ($span as $ym){
    $monthLabels[]   = date('M', strtotime($ym.'-01'));
    $gross[]         = f($salesIdx[$ym]['gross'] ?? 0);
    $collectedSeries[] = f($salesIdx[$ym]['collected'] ?? 0);
    $txns[]          = i($salesIdx[$ym]['txns'] ?? 0);
}
$patIdx = []; foreach ($patMonthly as $r) $patIdx[$r['ym']] = i($r['c']);
$patCumulative = []; $run = 0;
foreach ($span as $ym){ $run += ($patIdx[$ym] ?? 0); $patCumulative[] = $run; }

$rangeLabel = $span ? date('M', strtotime($span[0].'-01')).' – '.date('M Y', strtotime(end($span).'-01')) : 'No data';

/* ---- payment methods (fixed enum order) ---- */
$pmRaw = rows($conn, "SELECT PAYMENT_METHOD m, COUNT(*) c, SUM(TOTAL_AMOUNT) rev FROM sales GROUP BY PAYMENT_METHOD");
$pmIdx = []; foreach ($pmRaw as $r) $pmIdx[$r['m']] = $r;
$pmOrder = ['Card','Cash','E-wallet','Online Banking'];
$payRevenue = []; $payCount = [];
foreach ($pmOrder as $m){ $payRevenue[] = f($pmIdx[$m]['rev'] ?? 0); $payCount[] = i($pmIdx[$m]['c'] ?? 0); }

/* ---- inventory by category ---- */
$catRaw = rows($conn, "SELECT $CAT_CASE cat,
        SUM(STOCK_QUANTITY*UNIT_PRICE) val, SUM(STOCK_QUANTITY) units, COUNT(*) skus
    FROM product GROUP BY cat");
$catOrder = ['Frames','Contact Lenses','Solutions','Accessories'];
$catIdx = []; foreach ($catRaw as $r) $catIdx[$r['cat']] = $r;
$catValue = []; $catUnits = [];
foreach ($catOrder as $c){ $catValue[] = f($catIdx[$c]['val'] ?? 0); $catUnits[] = i($catIdx[$c]['units'] ?? 0); }

/* ---- top sellers ---- */
$topRaw = rows($conn, "SELECT p.BRAND_NAME name, SUM(si.QUANTITY) units, SUM(si.QUANTITY*p.UNIT_PRICE) revenue
    FROM sales_item si JOIN product p ON p.PRODUCT_ID = si.PRODUCT_ID
    GROUP BY p.PRODUCT_ID, p.BRAND_NAME ORDER BY units DESC, revenue DESC LIMIT 8");
$top = array_map(fn($r)=>['name'=>$r['name'],'units'=>i($r['units']),'revenue'=>f($r['revenue'])], $topRaw);

/* ---- staff performance (only those with activity) ---- */
$staffRaw = rows($conn, "SELECT u.NAME name, u.ROLE role,
        COALESCE(s.rev,0) revenue, COALESCE(s.cnt,0) sales,
        COALESCE(a.cnt,0) appts, COALESCE(e.cnt,0) exams
    FROM user u
    LEFT JOIN (SELECT STAFF_ID, SUM(TOTAL_AMOUNT) rev, COUNT(*) cnt FROM sales GROUP BY STAFF_ID) s ON s.STAFF_ID = u.USER_ID
    LEFT JOIN (SELECT STAFF_ID, COUNT(*) cnt FROM appointment GROUP BY STAFF_ID) a ON a.STAFF_ID = u.USER_ID
    LEFT JOIN (SELECT OPTOMETRIST_ID, COUNT(*) cnt FROM eye_examination GROUP BY OPTOMETRIST_ID) e ON e.OPTOMETRIST_ID = u.USER_ID
    HAVING (sales + appts + exams) > 0
    ORDER BY revenue DESC");
$staff = array_map(fn($r)=>[
    'name'=>$r['name'],'role'=>$r['role'],'revenue'=>f($r['revenue']),
    'sales'=>i($r['sales']),'appts'=>i($r['appts']),'exams'=>i($r['exams'])], $staffRaw);

/* ---- follow-up / recall schedule ---- */
$fuRaw = rows($conn, "SELECT COALESCE(NULLIF(TRIM(FOLLOW_UP_INTERVAL),''),'Not set') fu, COUNT(*) c
    FROM patient GROUP BY fu");
$fuMap = ['3 Months'=>0,'6 Months'=>0,'1 Year'=>0,'Not set'=>0];
foreach ($fuRaw as $r){
    $key = in_array($r['fu'], ['3 Months','6 Months','1 Year']) ? $r['fu'] : 'Not set';
    $fuMap[$key] += i($r['c']);
}

/* ---- inventory at risk (expired / expiring within 180d / low stock <=5) ---- */
$riskRaw = rows($conn, "SELECT BRAND_NAME name, $CAT_CASE cat, STOCK_QUANTITY qty,
        (STOCK_QUANTITY*UNIT_PRICE) value, EXPIRY_DATE,
        DATEDIFF(EXPIRY_DATE, CURDATE()) days
    FROM product
    WHERE STOCK_QUANTITY <= 5
       OR (EXPIRY_DATE IS NOT NULL AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 180 DAY))
    ORDER BY (EXPIRY_DATE IS NULL), EXPIRY_DATE ASC, STOCK_QUANTITY ASC");
$risk = [];
foreach ($riskRaw as $r){
    $tags = []; $note = [];
    $days = $r['days']; $hasExp = $r['EXPIRY_DATE'] !== null;
    if ($hasExp && $days < 0){ $tags[]='expired';  $note[] = 'Expired '.abs((int)$days).' days ago'; }
    elseif ($hasExp && $days <= 180){ $tags[]='expiring'; $note[] = 'Expires in '.(int)$days.' days'; }
    if (i($r['qty']) <= 5){ $tags[]='low'; $note[] = 'Only '.i($r['qty']).' left'; }
    if (!$tags) continue;
    $risk[] = ['name'=>$r['name'],'cat'=>$r['cat'],'qty'=>i($r['qty']),
               'value'=>f($r['value']),'tags'=>array_values(array_unique($tags)),
               'note'=>implode(' · ', array_unique($note))];
}

/* ---- audit-log activity ---- */
$act = one($conn, "SELECT
        SUM(ACTION LIKE '%logged in successfully%') logins,
        SUM(ACTION LIKE '%changed password%') pwResets,
        SUM(ACTION LIKE 'Created%' OR ACTION LIKE 'Updated%') recordEdits,
        SUM(ACTION LIKE '%unauthorized%') blocked
    FROM audit_log");
$blockedUserRow = one($conn, "SELECT u.NAME name, u.ROLE role, COUNT(*) c
    FROM audit_log l JOIN user u ON u.USER_ID = l.USER_ID
    WHERE l.ACTION LIKE '%unauthorized%' GROUP BY l.USER_ID ORDER BY c DESC LIMIT 1");
$blockedUser = $blockedUserRow ? $blockedUserRow['name'].' ('.$blockedUserRow['role'].')' : '';

/* ---- assemble payload for the frontend ---- */
$A = [
    'rangeLabel' => $rangeLabel,
    'kpi' => [
        'revenue'=>f($k['revenue']), 'collected'=>f($k['collected']), 'outstanding'=>f($k['outstanding']),
        'txns'=>i($k['txns']), 'avgSale'=>f($k['avgSale']), 'collectionRate'=>$collRate,
        'stockValue'=>f($stock['stockValue']), 'stockUnits'=>i($stock['stockUnits']),
        'patients'=>$pCount, 'products'=>i($stock['skus']), 'appointments'=>$aCount,
    ],
    'months'=>$monthLabels, 'gross'=>$gross, 'collected'=>$collectedSeries, 'txns'=>$txns,
    'pay'=>['labels'=>$pmOrder, 'revenue'=>$payRevenue, 'count'=>$payCount],
    'cat'=>['labels'=>$catOrder, 'value'=>$catValue, 'units'=>$catUnits],
    'top'=>$top,
    'staff'=>$staff,
    'patientsCumulative'=>$patCumulative,
    'followUp'=>['labels'=>array_keys($fuMap), 'data'=>array_values($fuMap)],
    'risk'=>$risk,
    'activity'=>[
        'logins'=>i($act['logins']), 'pwResets'=>i($act['pwResets']),
        'recordEdits'=>i($act['recordEdits']), 'blocked'=>i($act['blocked']),
        'blockedUser'=>$blockedUser,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .seg-btn { transition: all .18s ease; }
        .seg-btn.active { background:#0097B2; color:#fff; box-shadow:0 6px 16px -6px rgba(0,151,178,.6); }
        .reveal { opacity:0; transform:translateY(14px); animation: revealUp .6s ease forwards; }
        @keyframes revealUp { to { opacity:1; transform:translateY(0); } }
        .risk-row { transition: background .15s ease; }
        .tab-btn.active { background:#0f172a; color:#fff; }
        @media (prefers-reduced-motion: reduce) {
            .reveal { animation:none !important; opacity:1 !important; transform:none !important; }
        }
        /* preview-only sidebar */
        .side-link:hover { background: rgba(255,255,255,.06); }
    </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <!-- ===== Header ===== -->
        <header class="flex flex-wrap justify-between items-end gap-4 mb-10">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Analytics Dashboard</h1>
                    <span class="bg-teal-50 text-[#0097B2] px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Live</span>
                </div>
                <p class="text-slate-500 font-medium mt-1">Everything happening across the clinic, in one place. <span id="dataRange" class="text-slate-400"></span></p>
            </div>
            <div class="flex space-x-3">
                <a href="reports.php" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-50 transition shadow-sm flex items-center">
                    <i class="fa-solid fa-file-lines mr-2 text-slate-400"></i> Reports
                </a>
                <button onclick="window.print()" class="bg-[#0097B2] text-white px-6 py-3 rounded-xl font-bold hover:bg-teal-500 transition shadow-sm flex items-center">
                    <i class="fa-solid fa-print mr-2"></i> Print
                </button>
            </div>
        </header>

        <!-- ===== KPI strip ===== -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8" id="kpiStrip"></div>

        <!-- ===== Row: revenue trend + collection ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="reveal lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h3 id="trendTitle" class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Revenue Trend</h3>
                    <div class="flex items-center gap-2">
                        <div class="flex bg-slate-100 rounded-xl p-1 text-xs font-bold">
                            <button data-metric="gross"     class="seg-btn tm-btn active px-3 py-1.5 rounded-lg text-slate-500">Billed</button>
                            <button data-metric="collected" class="seg-btn tm-btn px-3 py-1.5 rounded-lg text-slate-500">Collected</button>
                            <button data-metric="txns"      class="seg-btn tm-btn px-3 py-1.5 rounded-lg text-slate-500">Sales</button>
                        </div>
                        <div class="flex bg-slate-100 rounded-xl p-1">
                            <button data-type="line" class="seg-btn tt-btn active px-3 py-1.5 rounded-lg text-xs text-slate-500"><i class="fa-solid fa-chart-line"></i></button>
                            <button data-type="bar"  class="seg-btn tt-btn px-3 py-1.5 rounded-lg text-xs text-slate-500"><i class="fa-solid fa-chart-column"></i></button>
                        </div>
                    </div>
                </div>
                <div class="relative h-72 w-full"><canvas id="trendChart"></canvas></div>
            </div>

            <div class="reveal bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 w-40 h-40 bg-[#0097B2]/20 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                <h3 class="relative z-10 text-xs font-black uppercase tracking-[0.2em] text-[#B9D977] mb-1">Collection</h3>
                <p class="relative z-10 text-slate-400 text-sm mb-4">How much of what you billed is in the bank.</p>
                <div class="relative z-10 mx-auto h-40 w-40 my-2"><canvas id="collectChart"></canvas></div>
                <div class="relative z-10 space-y-3 mt-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="flex items-center gap-2 text-slate-300"><span class="w-2.5 h-2.5 rounded-full bg-[#0097B2]"></span> Collected</span>
                        <span class="font-bold text-white" id="collectedAmt"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="flex items-center gap-2 text-slate-300"><span class="w-2.5 h-2.5 rounded-full bg-slate-600"></span> Outstanding</span>
                        <span class="font-bold text-white" id="outstandingAmt"></span>
                    </div>
                    <div class="pt-3 border-t border-white/10 flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Completed sales</span>
                        <span class="font-bold text-[#B9D977]" id="statusPct"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Row: 3 toggleable charts ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Payment methods -->
            <div class="reveal bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Payment Methods</h3>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] font-bold">
                        <button data-pm="revenue" class="seg-btn pm-btn active px-2.5 py-1 rounded-lg text-slate-500">RM</button>
                        <button data-pm="count"   class="seg-btn pm-btn px-2.5 py-1 rounded-lg text-slate-500">#</button>
                    </div>
                </div>
                <div class="relative h-56 w-full"><canvas id="payChart"></canvas></div>
            </div>
            <!-- Inventory by category -->
            <div class="reveal bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Stock by Category</h3>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] font-bold">
                        <button data-cat="value" class="seg-btn cat-btn active px-2.5 py-1 rounded-lg text-slate-500">Value</button>
                        <button data-cat="units" class="seg-btn cat-btn px-2.5 py-1 rounded-lg text-slate-500">Units</button>
                    </div>
                </div>
                <div class="relative h-56 w-full"><canvas id="catChart"></canvas></div>
            </div>
            <!-- Top products -->
            <div class="reveal bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Top Sellers</h3>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] font-bold">
                        <button data-tp="units"   class="seg-btn tp-btn active px-2.5 py-1 rounded-lg text-slate-500">Units</button>
                        <button data-tp="revenue" class="seg-btn tp-btn px-2.5 py-1 rounded-lg text-slate-500">RM</button>
                    </div>
                </div>
                <div class="relative h-56 w-full"><canvas id="topChart"></canvas></div>
            </div>
        </div>

        <!-- ===== Row: staff + patients ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="reveal lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Staff Performance</h3>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] font-bold">
                        <button data-sf="revenue" class="seg-btn sf-btn active px-2.5 py-1 rounded-lg text-slate-500">Revenue</button>
                        <button data-sf="activity" class="seg-btn sf-btn px-2.5 py-1 rounded-lg text-slate-500">Activity</button>
                    </div>
                </div>
                <div class="relative h-64 w-full"><canvas id="staffChart"></canvas></div>
            </div>
            <div class="reveal bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex flex-col">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2] mb-1">Patient Growth</h3>
                <p class="text-slate-400 text-sm mb-3">Cumulative registered patients.</p>
                <div class="relative h-32 w-full mb-4"><canvas id="patientChart"></canvas></div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Recall schedule</p>
                <div id="followUp" class="space-y-2"></div>
            </div>
        </div>

        <!-- ===== Row: inventory risk + activity ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-4">
            <div class="reveal lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-5">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-orange-500">Inventory at Risk</h3>
                        <p class="text-slate-400 text-sm mt-1"><span id="riskValue" class="font-bold text-slate-600"></span> tied up in expired, expiring or low stock.</p>
                    </div>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] font-bold">
                        <button data-tab="all"      class="tab-btn active px-3 py-1.5 rounded-lg text-slate-500 transition">All</button>
                        <button data-tab="expired"  class="tab-btn px-3 py-1.5 rounded-lg text-slate-500 transition">Expired</button>
                        <button data-tab="expiring" class="tab-btn px-3 py-1.5 rounded-lg text-slate-500 transition">Expiring</button>
                        <button data-tab="low"      class="tab-btn px-3 py-1.5 rounded-lg text-slate-500 transition">Low stock</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                <th class="text-left py-2 font-black">Product</th>
                                <th class="text-left py-2 font-black">Category</th>
                                <th class="text-center py-2 font-black">Qty</th>
                                <th class="text-right py-2 font-black">Value</th>
                                <th class="text-right py-2 font-black">Status</th>
                            </tr>
                        </thead>
                        <tbody id="riskBody"></tbody>
                    </table>
                </div>
                <p id="riskEmpty" class="hidden text-center text-slate-400 font-semibold py-8">Nothing in this category — all clear.</p>
            </div>

            <div class="reveal bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col">
                <div class="absolute bottom-0 left-0 w-40 h-40 bg-rose-500/20 rounded-full -ml-16 -mb-16 blur-3xl"></div>
                <h3 class="relative z-10 text-xs font-black uppercase tracking-[0.2em] text-[#B9D977] mb-1">System Activity</h3>
                <p class="relative z-10 text-slate-400 text-sm mb-6">From the audit log.</p>
                <div id="activityBars" class="relative z-10 space-y-4 flex-1"></div>
                <div id="securityNote" class="relative z-10 hidden mt-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl p-4">
                    <p class="text-rose-300 text-xs font-bold flex items-center gap-2"><i class="fa-solid fa-shield-halved"></i> <span id="securityText"></span></p>
                </div>
            </div>
        </div>
    </main>

<script>
/* All analytics come straight from the database (see PHP block above). */
const A = <?php echo json_encode($A); ?>;

/* ---------- formatters ---------- */
const rm  = v => "RM " + Math.round(v).toLocaleString();
const rm2 = v => "RM " + v.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
const num = v => Math.round(v).toLocaleString();
const PALETTE = ["#0097B2","#B9D977","#6366f1","#f59e0b","#a855f7","#f43f5e","#14b8a6"];
const FONT = { family:"Plus Jakarta Sans", weight:"600" };

/* ---------- count-up ---------- */
function countUp(el, target, fmt, dur=900){
    const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduce){ el.textContent = fmt(target); return; }
    const start = performance.now();
    (function step(now){
        const p = Math.min((now-start)/dur, 1);
        const e = 1 - Math.pow(1-p, 3);
        el.textContent = fmt(target*e);
        if (p<1) requestAnimationFrame(step);
    })(start);
}

/* ---------- KPI strip ---------- */
const KPIS = [
    {label:"Total Billed",   icon:"fa-sack-dollar",     color:"text-[#0097B2] bg-teal-50",   val:A.kpi.revenue,    fmt:rm},
    {label:"Collected",      icon:"fa-circle-check",    color:"text-emerald-500 bg-emerald-50", val:A.kpi.collected, fmt:rm, sub:A.kpi.collectionRate.toFixed(0)+"% of billed"},
    {label:"Outstanding",    icon:"fa-hourglass-half",  color:"text-orange-500 bg-orange-50", val:A.kpi.outstanding, fmt:rm},
    {label:"Sales",          icon:"fa-cash-register",   color:"text-indigo-500 bg-indigo-50", val:A.kpi.txns,       fmt:num, sub:"Avg "+rm(A.kpi.avgSale)},
    {label:"Stock Value",    icon:"fa-boxes-stacked",   color:"text-[#85a643] bg-lime-50",   val:A.kpi.stockValue, fmt:rm, sub:A.kpi.stockUnits+" units · "+A.kpi.products+" SKUs"},
    {label:"Patients",       icon:"fa-users",           color:"text-purple-500 bg-purple-50", val:A.kpi.patients,  fmt:num, sub:A.kpi.appointments+" appointments"}
];
function buildKpis(){
    const wrap = document.getElementById("kpiStrip");
    KPIS.forEach((k,i)=>{
        const card = document.createElement("div");
        card.className = "reveal bg-white p-5 rounded-[1.75rem] border border-slate-100 shadow-lg shadow-slate-200/30";
        card.style.animationDelay = (i*55)+"ms";
        card.innerHTML = `
            <div class="w-10 h-10 rounded-xl ${k.color} flex items-center justify-center text-lg mb-4 shadow-sm">
                <i class="fa-solid ${k.icon}"></i>
            </div>
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">${k.label}</p>
            <p class="text-xl font-extrabold text-slate-800 tabular-nums" data-target="${k.val}"></p>
            ${k.sub ? `<p class="text-[10px] font-bold text-slate-400 mt-1">${k.sub}</p>` : ``}`;
        wrap.appendChild(card);
        countUp(card.querySelector("[data-target]"), k.val, k.fmt);
    });
}

/* ---------- main trend chart ---------- */
const tstate = { metric:"gross", type:"line" };
let trendChart;
function trendSeries(){ return tstate.metric==="txns" ? A.txns : tstate.metric==="collected" ? A.collected : A.gross; }
function trendFmt(){ return tstate.metric==="txns" ? num : rm; }
function renderTrend(){
    const ctx = document.getElementById("trendChart").getContext("2d");
    const data = trendSeries();
    const g = ctx.createLinearGradient(0,0,0,300);
    g.addColorStop(0,"rgba(0,151,178,0.45)"); g.addColorStop(1,"rgba(0,151,178,0)");
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(ctx,{
        type: tstate.type,
        data:{ labels:A.months, datasets:[{
            label: tstate.metric==="txns"?"Sales":tstate.metric==="collected"?"Collected":"Billed",
            data, borderColor:"#0097B2",
            backgroundColor: tstate.type==="bar" ? "#0097B2" : g,
            borderWidth: tstate.type==="bar"?0:3, borderRadius: tstate.type==="bar"?10:0,
            pointBackgroundColor:"#fff", pointBorderColor:"#0097B2", pointBorderWidth:2,
            pointRadius: tstate.type==="bar"?0:4, pointHoverRadius:6,
            fill: tstate.type==="line", tension:.4 }]},
        options:{ responsive:true, maintainAspectRatio:false,
            interaction:{mode:"index",intersect:false},
            plugins:{ legend:{display:false},
                tooltip:{ backgroundColor:"#0f172a", padding:12, cornerRadius:8, displayColors:false,
                    titleFont:{family:"Plus Jakarta Sans",size:13}, bodyFont:{family:"Plus Jakarta Sans",size:14,weight:"bold"},
                    callbacks:{ label:c=> " "+trendFmt()(c.parsed.y) } } },
            scales:{ y:{ beginAtZero:true, grid:{color:"#f1f5f9",drawBorder:false},
                    ticks:{ font:FONT, color:"#94a3b8", callback:v=> tstate.metric==="txns"?v:"RM "+(v/1000)+"k" } },
                x:{ grid:{display:false,drawBorder:false}, ticks:{font:FONT,color:"#94a3b8"} } } }
    });
    document.getElementById("trendTitle").textContent =
        (tstate.metric==="txns"?"Sales Volume":tstate.metric==="collected"?"Cash Collected":"Revenue Billed")+" Trend";
}

/* ---------- collection donut ---------- */
function renderCollect(){
    new Chart(document.getElementById("collectChart").getContext("2d"),{
        type:"doughnut",
        data:{ labels:["Collected","Outstanding"],
            datasets:[{ data:[A.kpi.collected, A.kpi.outstanding],
                backgroundColor:["#0097B2","#334155"], borderWidth:0, cutout:"72%" }]},
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false},
                tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, displayColors:false,
                    bodyFont:{family:"Plus Jakarta Sans",weight:"bold"}, callbacks:{label:c=>" "+c.label+": "+rm(c.parsed)} } } }
    });
    document.getElementById("collectedAmt").textContent = rm(A.kpi.collected);
    document.getElementById("outstandingAmt").textContent = rm(A.kpi.outstanding);
    document.getElementById("statusPct").textContent = A.kpi.collectionRate.toFixed(0)+"%";
}

/* ---------- payment methods ---------- */
const pstate={mode:"revenue"}; let payChart;
function renderPay(){
    const d = pstate.mode==="revenue"?A.pay.revenue:A.pay.count;
    if(payChart) payChart.destroy();
    payChart=new Chart(document.getElementById("payChart").getContext("2d"),{
        type:"doughnut",
        data:{ labels:A.pay.labels, datasets:[{ data:d, backgroundColor:PALETTE, borderWidth:0, cutout:"60%" }]},
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:"bottom", labels:{ boxWidth:9, boxHeight:9, usePointStyle:true, padding:14, font:{family:"Plus Jakarta Sans",weight:"700",size:11}, color:"#64748b" } },
                tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, displayColors:false, bodyFont:{family:"Plus Jakarta Sans",weight:"bold"},
                    callbacks:{ label:c=> " "+(pstate.mode==="revenue"?rm(c.parsed):c.parsed+" sales") } } } }
    });
}

/* ---------- inventory category ---------- */
const cstate={mode:"value"}; let catChart;
function renderCat(){
    const d = cstate.mode==="value"?A.cat.value:A.cat.units;
    if(catChart) catChart.destroy();
    catChart=new Chart(document.getElementById("catChart").getContext("2d"),{
        type:"doughnut",
        data:{ labels:A.cat.labels, datasets:[{ data:d, backgroundColor:PALETTE, borderWidth:0, cutout:"60%" }]},
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:"bottom", labels:{ boxWidth:9, boxHeight:9, usePointStyle:true, padding:14, font:{family:"Plus Jakarta Sans",weight:"700",size:11}, color:"#64748b" } },
                tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, displayColors:false, bodyFont:{family:"Plus Jakarta Sans",weight:"bold"},
                    callbacks:{ label:c=> " "+(cstate.mode==="value"?rm(c.parsed):c.parsed+" units") } } } }
    });
}

/* ---------- top products ---------- */
const tpstate={mode:"units"}; let topChart;
function renderTop(){
    const sorted=[...A.top].sort((a,b)=> b[tpstate.mode]-a[tpstate.mode]);
    if(topChart) topChart.destroy();
    topChart=new Chart(document.getElementById("topChart").getContext("2d"),{
        type:"bar",
        data:{ labels:sorted.map(p=>p.name), datasets:[{ data:sorted.map(p=>p[tpstate.mode]),
            backgroundColor:"#0097B2", borderRadius:8, barThickness:18 }]},
        options:{ indexAxis:"y", responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false},
                tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, displayColors:false, bodyFont:{family:"Plus Jakarta Sans",weight:"bold"},
                    callbacks:{ label:c=> " "+(tpstate.mode==="revenue"?rm(c.parsed.x):c.parsed.x+" units") } } },
            scales:{ x:{ beginAtZero:true, grid:{color:"#f1f5f9",drawBorder:false}, ticks:{font:FONT,color:"#94a3b8",
                        callback:v=> tpstate.mode==="revenue"?"RM "+(v/1000)+"k":v } },
                y:{ grid:{display:false,drawBorder:false}, ticks:{font:{family:"Plus Jakarta Sans",weight:"700",size:10},color:"#475569"} } } }
    });
}

/* ---------- staff ---------- */
const sstate={mode:"revenue"}; let staffChart;
function renderStaff(){
    if(staffChart) staffChart.destroy();
    const ctx=document.getElementById("staffChart").getContext("2d");
    let datasets;
    if(sstate.mode==="revenue"){
        datasets=[{ label:"Revenue", data:A.staff.map(s=>s.revenue), backgroundColor:"#0097B2", borderRadius:10, barThickness:46 }];
    } else {
        datasets=[
            { label:"Sales",        data:A.staff.map(s=>s.sales), backgroundColor:"#0097B2", borderRadius:8 },
            { label:"Appointments", data:A.staff.map(s=>s.appts), backgroundColor:"#B9D977", borderRadius:8 },
            { label:"Exams",        data:A.staff.map(s=>s.exams), backgroundColor:"#6366f1", borderRadius:8 } ];
    }
    staffChart=new Chart(ctx,{
        type:"bar",
        data:{ labels:A.staff.map(s=>s.name), datasets },
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:sstate.mode==="activity", position:"bottom",
                    labels:{boxWidth:9,boxHeight:9,usePointStyle:true,padding:14,font:{family:"Plus Jakarta Sans",weight:"700",size:11},color:"#64748b"} },
                tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, bodyFont:{family:"Plus Jakarta Sans",weight:"bold"},
                    callbacks:{ label:c=> sstate.mode==="revenue"? " "+rm(c.parsed.y) : " "+c.dataset.label+": "+c.parsed.y } } },
            scales:{ y:{ beginAtZero:true, grid:{color:"#f1f5f9",drawBorder:false}, ticks:{font:FONT,color:"#94a3b8",
                        callback:v=> sstate.mode==="revenue"?"RM "+(v/1000)+"k":v } },
                x:{ grid:{display:false,drawBorder:false}, ticks:{font:{family:"Plus Jakarta Sans",weight:"700"},color:"#475569"} } } }
    });
}

/* ---------- patient growth + follow-up ---------- */
function renderPatients(){
    const ctx=document.getElementById("patientChart").getContext("2d");
    const g=ctx.createLinearGradient(0,0,0,130);
    g.addColorStop(0,"rgba(185,217,119,0.6)"); g.addColorStop(1,"rgba(185,217,119,0)");
    new Chart(ctx,{ type:"line",
        data:{ labels:A.months, datasets:[{ data:A.patientsCumulative, borderColor:"#85a643",
            backgroundColor:g, borderWidth:3, fill:true, tension:.4, pointRadius:0, pointHoverRadius:5 }]},
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false}, tooltip:{ backgroundColor:"#0f172a", padding:10, cornerRadius:8, displayColors:false,
                bodyFont:{family:"Plus Jakarta Sans",weight:"bold"}, callbacks:{label:c=>" "+c.parsed.y+" patients"} } },
            scales:{ y:{display:false,beginAtZero:true}, x:{ grid:{display:false,drawBorder:false}, ticks:{font:{family:"Plus Jakarta Sans",weight:"600",size:10},color:"#94a3b8"} } } }
    });
    const max=Math.max(...A.followUp.data);
    const colors=["#0097B2","#B9D977","#6366f1","#cbd5e1"];
    document.getElementById("followUp").innerHTML = A.followUp.labels.map((l,i)=>`
        <div>
            <div class="flex justify-between text-[11px] font-bold mb-1"><span class="text-slate-600">${l}</span><span class="text-slate-400">${A.followUp.data[i]}</span></div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full" style="width:${(A.followUp.data[i]/max*100)}%;background:${colors[i]}"></div></div>
        </div>`).join("");
}

/* ---------- inventory at risk ---------- */
function statusBadge(tags){
    if(tags.includes("expired"))  return `<span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wide bg-rose-50 text-rose-600">Expired</span>`;
    if(tags.includes("expiring")) return `<span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wide bg-orange-50 text-orange-600">Expiring</span>`;
    return `<span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wide bg-amber-50 text-amber-600">Low stock</span>`;
}
function renderRisk(tab="all"){
    const body=document.getElementById("riskBody");
    const rows = A.risk.filter(r=> tab==="all" || r.tags.includes(tab));
    document.getElementById("riskEmpty").classList.toggle("hidden", rows.length!==0);
    body.innerHTML = rows.map(r=>`
        <tr class="risk-row border-b border-slate-50 hover:bg-slate-50">
            <td class="py-3 pr-2">
                <p class="font-bold text-slate-700">${r.name}</p>
                <p class="text-[11px] text-slate-400">${r.note}</p>
            </td>
            <td class="py-3 text-slate-500">${r.cat}</td>
            <td class="py-3 text-center"><span class="font-bold ${r.qty<=5?'text-rose-500':'text-slate-700'}">${r.qty}</span></td>
            <td class="py-3 text-right font-bold text-slate-700">${rm(r.value)}</td>
            <td class="py-3 text-right">${statusBadge(r.tags)}</td>
        </tr>`).join("");
    const totalRisk = A.risk.reduce((s,r)=>s+r.value,0);
    document.getElementById("riskValue").textContent = rm(totalRisk);
}

/* ---------- activity ---------- */
function renderActivity(){
    const items=[
        {label:"Logins", val:A.activity.logins, color:"#0097B2"},
        {label:"Password resets", val:A.activity.pwResets, color:"#B9D977"},
        {label:"Record changes", val:A.activity.recordEdits, color:"#6366f1"},
        {label:"Blocked access", val:A.activity.blocked, color:"#f43f5e"}
    ];
    const max=Math.max(...items.map(i=>i.val));
    document.getElementById("activityBars").innerHTML = items.map(i=>`
        <div>
            <div class="flex justify-between text-xs font-bold mb-1"><span class="text-slate-300">${i.label}</span><span class="text-white">${i.val}</span></div>
            <div class="h-2.5 rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full" style="width:${(i.val/max*100)}%;background:${i.color}"></div></div>
        </div>`).join("");
    if(A.activity.blocked>0){
        document.getElementById("securityNote").classList.remove("hidden");
        document.getElementById("securityText").textContent =
            A.activity.blocked+" blocked access attempts by "+A.activity.blockedUser+" — worth reviewing.";
    }
}

/* ---------- toggle wiring ---------- */
function wire(sel, key, store, cb, isNum){
    document.querySelectorAll(sel).forEach(btn=>{
        btn.addEventListener("click",()=>{
            document.querySelectorAll(sel).forEach(b=>b.classList.remove("active"));
            btn.classList.add("active");
            store[key.field] = btn.dataset[key.attr];
            cb();
        });
    });
}

document.addEventListener("DOMContentLoaded",()=>{
    document.getElementById("dataRange").textContent = "· "+A.rangeLabel;
    buildKpis();
    renderTrend(); renderCollect(); renderPay(); renderCat(); renderTop();
    renderStaff(); renderPatients(); renderRisk(); renderActivity();

    // trend metric + type
    document.querySelectorAll(".tm-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".tm-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        tstate.metric=b.dataset.metric; renderTrend();
    }));
    document.querySelectorAll(".tt-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".tt-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        tstate.type=b.dataset.type; renderTrend();
    }));
    // pay / cat / top / staff
    document.querySelectorAll(".pm-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".pm-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        pstate.mode=b.dataset.pm; renderPay();
    }));
    document.querySelectorAll(".cat-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".cat-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        cstate.mode=b.dataset.cat; renderCat();
    }));
    document.querySelectorAll(".tp-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".tp-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        tpstate.mode=b.dataset.tp; renderTop();
    }));
    document.querySelectorAll(".sf-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".sf-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        sstate.mode=b.dataset.sf; renderStaff();
    }));
    // risk tabs
    document.querySelectorAll(".tab-btn").forEach(b=>b.addEventListener("click",()=>{
        document.querySelectorAll(".tab-btn").forEach(x=>x.classList.remove("active")); b.classList.add("active");
        renderRisk(b.dataset.tab);
    }));
});
</script>
</body>
</html>