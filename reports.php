<?php include('config.php'); ?>
<?php include_once('recall_helpers.php'); ?>
<?php
$recall_rows = get_due_recall_rows($conn);
$recall_priority = 0;
$recall_contactable = 0;
$recall_no_phone = 0;
foreach ($recall_rows as $recall_row) {
    if (empty($recall_row['PHONE_NUMBER'])) {
        $recall_no_phone++;
        continue;
    }
    $days_overdue = max(0, (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($recall_row['recall_date']))->days);
    $recall_contactable++;
    if ($days_overdue >= 90) $recall_priority++;
}

$market_categories = [];
$category_result = mysqli_query($conn, "SELECT COALESCE(NULLIF(p.CATEGORY, ''), 'Other') AS category, SUM(si.QUANTITY) AS units, SUM(si.QUANTITY * p.UNIT_PRICE) AS revenue FROM SALES_ITEM si JOIN PRODUCT p ON p.PRODUCT_ID = si.PRODUCT_ID JOIN SALES s ON s.SALE_ID = si.SALE_ID GROUP BY p.CATEGORY ORDER BY revenue DESC");
if ($category_result) {
    while ($category_row = mysqli_fetch_assoc($category_result)) $market_categories[] = $category_row;
}
$top_category = $market_categories[0] ?? ['category' => 'No sales data', 'units' => 0, 'revenue' => 0];
$category_revenue_total = array_sum(array_map(function ($row) { return (float)$row['revenue']; }, $market_categories));
$top_product = ['name' => 'No sales data', 'units' => 0];
$top_product_result = mysqli_query($conn, "SELECT p.BRAND_NAME AS name, SUM(si.QUANTITY) AS units FROM SALES_ITEM si JOIN PRODUCT p ON p.PRODUCT_ID = si.PRODUCT_ID GROUP BY p.PRODUCT_ID, p.BRAND_NAME ORDER BY units DESC LIMIT 1");
if ($top_product_result && ($top_product_row = mysqli_fetch_assoc($top_product_result))) $top_product = $top_product_row;
$repeat_customers = 0;
$repeat_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM (SELECT PATIENT_ID FROM SALES WHERE PATIENT_ID IS NOT NULL GROUP BY PATIENT_ID HAVING COUNT(*) >= 2) repeat_customers");
if ($repeat_result) $repeat_customers = (int)mysqli_fetch_assoc($repeat_result)['total'];
$unconverted_patients = 0;
$unconverted_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM PATIENT p LEFT JOIN SALES s ON s.PATIENT_ID = p.PATIENT_ID WHERE s.SALE_ID IS NULL");
if ($unconverted_result) $unconverted_patients = (int)mysqli_fetch_assoc($unconverted_result)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-More | Reports </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Toggle pills */
        .seg-btn { transition: all .18s ease; }
        .seg-btn.active { background:#0097B2; color:#fff; box-shadow:0 6px 16px -6px rgba(0,151,178,.6); }

        /* Stat strip number tick animation */
        .stat-pop { animation: statPop .35s ease; }
        @keyframes statPop { 0%{opacity:.3; transform:translateY(4px);} 100%{opacity:1; transform:translateY(0);} }

        /* Card reveal */
        .reveal { opacity:0; transform:translateY(14px); animation: revealUp .5s ease forwards; }
        @keyframes revealUp { to { opacity:1; transform:translateY(0); } }

        @media (prefers-reduced-motion: reduce) {
            .reveal, .stat-pop { animation:none !important; opacity:1 !important; transform:none !important; }
        }
    </style>
</head>
<body class="bg-[#f8fafc] flex min-h-screen text-slate-900">

    <?php include('sidebar.php'); ?>

    <main class="flex-1 ml-72 p-12">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Reports</h1>
                <p class="text-slate-500 font-medium mt-1">Generate comprehensive insights for clinic performance.</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-50 transition shadow-sm flex items-center">
                    <i class="fa-solid fa-print mr-2 text-slate-400"></i> Print View
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- ================= INTERACTIVE REVENUE WIDGET ================= -->
            <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <div class="flex items-center gap-3">
                        <h3 id="chartTitle" class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">6-Month Revenue Trend</h3>
                        <span class="bg-teal-50 text-[#0097B2] px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Live</span>
                    </div>

                    <!-- Chart type + compare -->
                    <div class="flex items-center gap-2">
                        <div class="flex bg-slate-100 rounded-xl p-1">
                            <button data-type="line" class="seg-btn type-btn active px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500">
                                <i class="fa-solid fa-chart-line"></i>
                            </button>
                            <button data-type="bar" class="seg-btn type-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500">
                                <i class="fa-solid fa-chart-column"></i>
                            </button>
                        </div>
                        <button id="compareBtn" class="seg-btn px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-200 text-slate-500 bg-white">
                            <i class="fa-solid fa-code-compare mr-1"></i> Compare
                        </button>
                    </div>
                </div>

                <!-- Metric + range controls -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div class="flex bg-slate-100 rounded-xl p-1 text-xs font-bold">
                        <button data-metric="gross"        class="seg-btn metric-btn active px-3 py-1.5 rounded-lg text-slate-500">Revenue</button>
                        <button data-metric="transactions" class="seg-btn metric-btn px-3 py-1.5 rounded-lg text-slate-500">Transactions</button>
                        <button data-metric="avg"          class="seg-btn metric-btn px-3 py-1.5 rounded-lg text-slate-500">Avg Sale</button>
                    </div>
                    <div class="flex bg-slate-100 rounded-xl p-1 text-xs font-bold">
                        <button data-range="6"  class="seg-btn range-btn active px-3 py-1.5 rounded-lg text-slate-500">6M</button>
                        <button data-range="12" class="seg-btn range-btn px-3 py-1.5 rounded-lg text-slate-500">12M</button>
                    </div>
                </div>

                <!-- Live stat strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Total</p>
                        <p id="statTotal" class="text-lg font-extrabold text-slate-800">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Monthly Avg</p>
                        <p id="statAvg" class="text-lg font-extrabold text-slate-800">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Growth</p>
                        <p id="statGrowth" class="text-lg font-extrabold text-slate-800">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Peak</p>
                        <p id="statPeak" class="text-lg font-extrabold text-slate-800">—</p>
                    </div>
                </div>

                <div class="relative h-64 w-full">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- ================= CUSTOM BUILDER (unchanged behaviour) ================= -->
            <div class="bg-slate-900 p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 w-40 h-40 bg-[#0097B2]/20 rounded-full -mr-16 -mt-16 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="w-12 h-12 bg-white/10 text-[#B9D977] rounded-xl flex items-center justify-center border border-white/10 mb-6 shadow-lg">
                        <i class="fa-solid fa-sliders text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white tracking-tight mb-2">Custom Builder</h2>
                    <p class="text-slate-400 text-sm mb-6">Define your parameters to generate a specific data export.</p>
                </div>

                <form action="report_custom.php" method="GET" class="relative z-10 space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white ml-1">Report Type</label>
                            <select name="type" class="w-full p-4 bg-white/5 border border-white/10 rounded-2xl outline-none text-white font-semibold focus:border-[#0097B2] transition appearance-none">
                                <option value="sales" class="text-slate-900">Sales & Transactions</option>
                                <option value="inventory" class="text-slate-900">Stock & Inventory Level</option>
                                <option value="patients" class="text-slate-900">Patient Directory</option>
                                <option value="appointments" class="text-slate-900">Clinical Appointments</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white ml-1">Timeframe</label>
                            <select id="report_period" name="period" onchange="updateReportPeriodFields()" class="w-full p-4 bg-white/5 border border-white/10 rounded-2xl outline-none text-white font-semibold focus:border-[#0097B2] transition appearance-none">
                                <option value="last_6_months" class="text-slate-900">Last 6 months</option>
                                <option value="last_12_months" class="text-slate-900">Last 12 months</option>
                                <option value="month" class="text-slate-900">Specific month</option>
                                <option value="custom" class="text-slate-900">Custom range</option>
                            </select>
                        </div>
                    </div>
                    <div id="month_picker_field" class="space-y-2 hidden">
                        <label class="text-[10px] font-black uppercase tracking-widest text-white ml-1">Choose month</label>
                        <input type="month" name="month" class="w-full p-4 bg-white/5 border border-white/10 rounded-2xl outline-none text-white font-semibold focus:border-[#0097B2] transition">
                    </div>
                    <div id="custom_range_fields" class="grid grid-cols-1 lg:grid-cols-2 gap-4 hidden">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white ml-1">Start date</label>
                            <input type="date" name="start_date" class="w-full p-4 bg-white/5 border border-white/10 rounded-2xl outline-none text-white font-semibold focus:border-[#0097B2] transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white ml-1">End date</label>
                            <input type="date" name="end_date" class="w-full p-4 bg-white/5 border border-white/10 rounded-2xl outline-none text-white font-semibold focus:border-[#0097B2] transition">
                        </div>
                    </div>
                    <div class="pt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <button type="submit" name="action" value="view" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-900/50 hover:bg-teal-500 transition-all flex justify-center items-center">
                            <i class="fa-solid fa-eye mr-2"></i> View Report
                        </button>
                        <button type="submit" name="export" value="pdf" class="w-full bg-[#0097B2] text-white py-4 rounded-2xl font-bold shadow-lg shadow-teal-900/50 hover:bg-teal-500 transition-all flex justify-center items-center">
                            <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                        </button>
                        <button type="submit" name="export" value="excel" class="w-full bg-[#B9D977] text-slate-900 py-4 rounded-2xl font-bold shadow-lg hover:bg-lime-400 transition-all flex justify-center items-center">
                            <i class="fa-solid fa-file-excel mr-2"></i> Excel
                        </button>
                    </div>
                </form>
                <script>
                    function updateReportPeriodFields() {
                        const period = document.getElementById('report_period').value;
                        document.getElementById('month_picker_field').classList.toggle('hidden', period !== 'month');
                        document.getElementById('custom_range_fields').classList.toggle('hidden', period !== 'custom');
                    }
                    document.addEventListener('DOMContentLoaded', updateReportPeriodFields);
                </script>
            </div>
        </div>

        <!-- ================= RECALL INTELLIGENCE ================= -->
        <section class="mb-12 overflow-hidden rounded-[2.5rem] bg-slate-900 p-8 text-white shadow-2xl reveal">
            <div class="relative z-10 flex flex-col gap-8 xl:flex-row xl:items-center xl:justify-between">
                <div class="max-w-xl">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#B9D977] text-slate-900"><i class="fa-solid fa-bullseye"></i></span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#B9D977]">Outreach Intelligence</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight">Turn recall data into action</h2>
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-slate-300">Your recall audience is automatically grouped by urgency and contactability. Start with patients overdue 90+ days, then work through the remaining due list.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="report_recall.php?segment=priority" class="inline-flex items-center gap-2 rounded-xl bg-[#B9D977] px-4 py-3 text-xs font-black text-slate-900 transition hover:bg-white"><i class="fa-solid fa-bolt"></i> Priority List</a>
                        <a href="whatsapp_messages.php" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-3 text-xs font-black text-white transition hover:bg-white/10"><i class="fa-brands fa-whatsapp"></i> WhatsApp Desk</a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:min-w-[560px]">
                    <a href="report_recall.php?segment=all" class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Due</p>
                        <p class="mt-2 text-3xl font-black text-white"><?php echo number_format(count($recall_rows)); ?></p>
                    </a>
                    <a href="report_recall.php?segment=priority" class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-5 transition hover:bg-amber-400/20">
                        <p class="text-[9px] font-black uppercase tracking-widest text-amber-200">Priority</p>
                        <p class="mt-2 text-3xl font-black text-amber-100"><?php echo number_format($recall_priority); ?></p>
                    </a>
                    <a href="report_recall.php?segment=recent" class="rounded-2xl border border-green-400/20 bg-green-400/10 p-5 transition hover:bg-green-400/20">
                        <p class="text-[9px] font-black uppercase tracking-widest text-green-200">Contactable</p>
                        <p class="mt-2 text-3xl font-black text-green-100"><?php echo number_format($recall_contactable); ?></p>
                    </a>
                    <a href="report_recall.php?segment=no_phone" class="rounded-2xl border border-red-400/20 bg-red-400/10 p-5 transition hover:bg-red-400/20">
                        <p class="text-[9px] font-black uppercase tracking-widest text-red-200">Missing Phone</p>
                        <p class="mt-2 text-3xl font-black text-red-100"><?php echo number_format($recall_no_phone); ?></p>
                    </a>
                </div>
            </div>
        </section>

        <!-- ================= MARKET INTELLIGENCE ================= -->
        <section class="mb-12 rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-xl shadow-slate-200/40 reveal">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-8">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0097B2]">Market Intelligence</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Who is buying from C-More?</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">Use actual sales behaviour to decide what to promote, which audience to follow up with, and where the next campaign should focus.</p>
                </div>
                <button type="button" onclick="openMarketAssistant()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0097B2] px-4 py-3 text-xs font-black text-white shadow-lg shadow-teal-100 transition hover:bg-[#007f96]"><i class="fa-solid fa-wand-magic-sparkles"></i> Ask AI About This</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-8">
                <div class="rounded-2xl bg-teal-50 p-5">
                    <p class="text-[9px] font-black uppercase tracking-widest text-[#0097B2]">Leading Category</p>
                    <p class="mt-2 truncate text-xl font-black text-slate-900"><?php echo htmlspecialchars($top_category['category']); ?></p>
                    <p class="mt-1 text-xs font-bold text-slate-500">RM <?php echo number_format((float)$top_category['revenue'], 2); ?> attributed revenue</p>
                </div>
                <div class="rounded-2xl bg-lime-50 p-5">
                    <p class="text-[9px] font-black uppercase tracking-widest text-[#85a643]">Top Product</p>
                    <p class="mt-2 truncate text-xl font-black text-slate-900"><?php echo htmlspecialchars($top_product['name']); ?></p>
                    <p class="mt-1 text-xs font-bold text-slate-500"><?php echo number_format((int)$top_product['units']); ?> units sold</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-5">
                    <p class="text-[9px] font-black uppercase tracking-widest text-amber-600">Repeat Customers</p>
                    <p class="mt-2 text-3xl font-black text-slate-900"><?php echo number_format($repeat_customers); ?></p>
                    <p class="mt-1 text-xs font-bold text-slate-500">Patients with two or more sales</p>
                </div>
                <div class="rounded-2xl bg-rose-50 p-5">
                    <p class="text-[9px] font-black uppercase tracking-widest text-rose-500">Untapped Patients</p>
                    <p class="mt-2 text-3xl font-black text-slate-900"><?php echo number_format($unconverted_patients); ?></p>
                    <p class="mt-1 text-xs font-bold text-slate-500">Registered with no recorded sale</p>
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Category demand</h3>
                        <span class="text-xs font-bold text-slate-400">By attributed sales revenue</span>
                    </div>
                    <div class="space-y-4">
                        <?php foreach(array_slice($market_categories, 0, 5) as $category):
                            $share = $category_revenue_total > 0 ? ((float)$category['revenue'] / $category_revenue_total) * 100 : 0;
                        ?>
                        <div>
                            <div class="mb-1 flex justify-between text-xs font-bold text-slate-600"><span><?php echo htmlspecialchars($category['category']); ?></span><span><?php echo number_format($share, 1); ?>%</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-[#0097B2]" style="width: <?php echo min(100, max(0, $share)); ?>%"></div></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($market_categories)): ?><p class="text-sm text-slate-400">Sales data will appear here after the first completed sale.</p><?php endif; ?>
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-6">
                    <p class="text-[9px] font-black uppercase tracking-widest text-[#0097B2]">Campaign Direction</p>
                    <h3 class="mt-3 text-lg font-black text-slate-900">Promote <?php echo htmlspecialchars($top_category['category']); ?> first.</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">Pair your strongest category with a recall campaign for repeat customers, then use the untapped patient count to plan a welcome or first-purchase offer.</p>
                    <a href="report_products.php" class="mt-5 inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#0097B2] hover:text-slate-900">View product performance <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <!-- ================= PRE-CONFIGURED REPORTS ================= -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 mt-12">
            <h3 class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 ml-2">Pre-configured Reports</h3>
            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                <input id="reportSearch" type="text" placeholder="Filter reports…"
                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl outline-none text-sm font-semibold text-slate-700 focus:border-[#0097B2] transition shadow-sm">
            </div>
        </div>

        <div id="reportGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <a href="report_sales.php" data-name="revenue analysis income payment" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-[#0097B2] transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 text-[#0097B2] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#0097B2] group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Revenue Analysis</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Breakdown of clinic income, pending balances, and payment methods.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-[#0097B2] tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="report_inventory.php" data-name="stock valuation inventory frames lenses" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-[#0097B2] transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-2xl mb-6 group-hover:bg-indigo-500 group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Stock Valuation</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Current asset value of all frames, lenses, and accessories on hand.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-indigo-500 tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="report_expiry.php" data-name="expiry wastage contact lenses solutions" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-orange-400 transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-2xl mb-6 group-hover:bg-orange-500 group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Expiry & Wastage</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Identify contact lenses and solutions expiring within the next 6 months.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-orange-500 tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="report_recall.php" data-name="patient recall list follow-up exams outreach target market whatsapp priority" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-purple-400 transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Patient Recall List</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Personalised recall dates, outreach segments, contactability, and WhatsApp actions.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-purple-600 tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="report_staff.php" data-name="staff performance optometrist sales appointments" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-rose-400 transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-2xl mb-6 group-hover:bg-rose-500 group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Staff Performance</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Sales generated and appointments handled per optometrist.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-rose-500 tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

            <a href="report_products.php" data-name="top selling products frames brands quarter" class="report-card bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/30 group hover:border-[#B9D977] transition-all cursor-pointer block">
                <div class="w-14 h-14 rounded-2xl bg-lime-50 text-[#85a643] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#B9D977] group-hover:text-white transition-colors shadow-sm">
                    <i class="fa-solid fa-award"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Top Selling Products</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6 h-10">Discover which frames and brands are performing best this quarter.</p>
                <div class="flex items-center text-[10px] font-black uppercase text-[#85a643] tracking-widest group-hover:translate-x-2 transition-transform">
                    Generate <i class="fa-solid fa-arrow-right ml-2"></i>
                </div>
            </a>

        </div>

        <p id="noResults" class="hidden text-center text-slate-400 font-semibold py-12">No reports match your filter.</p>
    </main>

    <script>
    /* =========================================================================
       INTERACTIVE REVENUE WIDGET
       Replace the numbers in REVENUE_DATA with values injected from PHP, e.g.:
         const REVENUE_DATA = { labels: [...], gross: [...], transactions: [...] };
       Expected shape: { labels:[...12], gross:[...12], transactions:[...12] }
       (ordered oldest -> newest). avg is derived = gross / transactions.
       ========================================================================= */
    const REVENUE_DATA = {
        labels: ['Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr','May'],
        gross:        [11800, 13100, 12400, 14000, 13600, 15800, 12500, 15200, 14800, 18500, 17200, 21000],
        transactions: [78,    88,    82,    95,    91,    104,   85,    102,   98,    120,   115,   140]
    };

    const state = { metric: 'gross', range: 6, type: 'line', compare: false };
    let chart;

    const fmtRM  = v => 'RM ' + Math.round(v).toLocaleString();
    const fmtNum = v => Math.round(v).toLocaleString();

    function seriesFor(metric, fullArr) {
        if (metric === 'gross') return REVENUE_DATA.gross.slice();
        if (metric === 'transactions') return REVENUE_DATA.transactions.slice();
        // avg sale value = gross / transactions
        return REVENUE_DATA.gross.map((g, i) => REVENUE_DATA.transactions[i] ? g / REVENUE_DATA.transactions[i] : 0);
    }

    function slice(arr, range) { return arr.slice(arr.length - range); }

    function metricFormatter() { return state.metric === 'transactions' ? fmtNum : fmtRM; }

    function metricLabel() {
        return state.metric === 'gross' ? 'Gross Revenue'
             : state.metric === 'transactions' ? 'Transactions'
             : 'Avg Sale Value';
    }

    function updateStats(current, previous) {
        const fmt = metricFormatter();
        const total = current.reduce((a, b) => a + b, 0);
        const avg = total / current.length;
        const first = current[0], last = current[current.length - 1];
        const growth = first ? ((last - first) / first) * 100 : 0;
        const peakIdx = current.indexOf(Math.max(...current));
        const labels = slice(REVENUE_DATA.labels, state.range);

        // Transactions total is a count; avg-sale "total" isn't meaningful so show average instead
        const totalEl = document.getElementById('statTotal');
        if (state.metric === 'avg') {
            totalEl.previousElementSibling.textContent = 'Overall Avg';
            totalEl.textContent = fmt(avg);
        } else {
            totalEl.previousElementSibling.textContent = 'Total';
            totalEl.textContent = fmt(total);
        }

        document.getElementById('statAvg').textContent = fmt(avg);

        const g = document.getElementById('statGrowth');
        const sign = growth >= 0 ? '+' : '';
        g.textContent = sign + growth.toFixed(1) + '%';
        g.className = 'text-lg font-extrabold ' + (growth >= 0 ? 'text-[#0097B2]' : 'text-rose-500');

        document.getElementById('statPeak').textContent = labels[peakIdx];

        document.querySelectorAll('#statTotal,#statAvg,#statGrowth,#statPeak').forEach(el => {
            el.classList.remove('stat-pop'); void el.offsetWidth; el.classList.add('stat-pop');
        });
    }

    function render() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const labels = slice(REVENUE_DATA.labels, state.range);
        const full = seriesFor(state.metric);
        const current = slice(full, state.range);

        // previous period = the window immediately before the current one
        const prevEnd = full.length - state.range;
        const previous = prevEnd >= state.range ? full.slice(prevEnd - state.range, prevEnd)
                                                : full.slice(0, state.range);

        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 151, 178, 0.5)');
        gradient.addColorStop(1, 'rgba(0, 151, 178, 0.0)');

        const datasets = [{
            label: metricLabel(),
            data: current,
            borderColor: '#0097B2',
            backgroundColor: state.type === 'bar' ? '#0097B2' : gradient,
            borderWidth: state.type === 'bar' ? 0 : 3,
            borderRadius: state.type === 'bar' ? 8 : 0,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#0097B2',
            pointBorderWidth: 2,
            pointRadius: state.type === 'bar' ? 0 : 4,
            pointHoverRadius: 6,
            fill: state.type === 'line',
            tension: 0.4
        }];

        if (state.compare) {
            datasets.push({
                label: 'Previous period',
                data: previous,
                borderColor: '#B9D977',
                backgroundColor: 'rgba(185,217,119,.15)',
                borderWidth: state.type === 'bar' ? 0 : 2,
                borderDash: state.type === 'bar' ? [] : [6, 5],
                borderRadius: state.type === 'bar' ? 8 : 0,
                pointRadius: 0,
                pointHoverRadius: 5,
                fill: false,
                tension: 0.4
            });
        }

        const fmt = metricFormatter();
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
            type: state.type,
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: state.compare,
                        position: 'bottom',
                        labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true,
                                  font: { family: 'Plus Jakarta Sans', weight: '700', size: 11 }, color: '#64748b' }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Plus Jakarta Sans', size: 13 },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 14, weight: 'bold' },
                        padding: 12, cornerRadius: 8, displayColors: state.compare,
                        callbacks: { label: c => ' ' + c.dataset.label + ': ' + fmt(c.parsed.y) }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { family: 'Plus Jakarta Sans', weight: '600' }, color: '#94a3b8',
                            callback: v => state.metric === 'transactions'
                                ? v
                                : (state.metric === 'avg' ? 'RM ' + v : 'RM ' + (v / 1000) + 'k')
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', weight: '600' }, color: '#94a3b8' }
                    }
                }
            }
        });

        document.getElementById('chartTitle').textContent = state.range + '-Month ' + metricLabel() + ' Trend';
        updateStats(current, previous);
    }

    function bindToggle(selector, attr, key, isNum) {
        document.querySelectorAll(selector).forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll(selector).forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                state[key] = isNum ? parseInt(btn.dataset[attr], 10) : btn.dataset[attr];
                render();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindToggle('.metric-btn', 'metric', 'metric', false);
        bindToggle('.range-btn', 'range', 'range', true);
        bindToggle('.type-btn', 'type', 'type', false);

        const cmp = document.getElementById('compareBtn');
        cmp.addEventListener('click', () => {
            state.compare = !state.compare;
            cmp.classList.toggle('active', state.compare);
            render();
        });

        render();

        /* ---- Report card filter + staggered reveal ---- */
        const cards = Array.from(document.querySelectorAll('.report-card'));
        cards.forEach((c, i) => { c.classList.add('reveal'); c.style.animationDelay = (i * 60) + 'ms'; });

        const search = document.getElementById('reportSearch');
        const noRes = document.getElementById('noResults');
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let visible = 0;
            cards.forEach(c => {
                const hit = c.dataset.name.includes(q) ||
                            c.querySelector('h3').textContent.toLowerCase().includes(q);
                c.style.display = hit ? '' : 'none';
                if (hit) visible++;
            });
            noRes.classList.toggle('hidden', visible !== 0);
        });

        window.openMarketAssistant = function() {
            const toggle = document.getElementById('ai-toggle');
            const input = document.getElementById('ai-input');
            const panel = document.getElementById('ai-panel');
            if (panel && panel.classList.contains('hidden') && toggle) toggle.click();
            if (input) {
                input.value = 'What are our strongest target market segments and what should we promote?';
                input.focus();
            }
        };
    });
    </script>
</body>
</html>