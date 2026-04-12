<?php include('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C-More | Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">
    <?php /* You can put your sidebar code here or use an include */ ?>
    
    <main class="flex-1 p-10">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Product Inventory</h1>
            <button class="bg-emerald-600 text-white px-5 py-2 rounded-xl hover:bg-emerald-700 transition">
                <i class="fa-solid fa-plus mr-2"></i> Add Product
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="p-5 font-semibold">Category</th>
                        <th class="p-5 font-semibold">Brand/Item Name</th>
                        <th class="p-5 font-semibold text-center">Stock Level</th>
                        <th class="p-5 font-semibold text-right">Unit Price</th>
                        <th class="p-5 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM PRODUCT ORDER BY CATEGORY ASC");
                    while($row = mysqli_fetch_assoc($res)):
                        $is_low = ($row['STOCK_QUANTITY'] < 5);
                    ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-5"><span class="px-3 py-1 bg-slate-100 rounded-full text-xs font-bold text-slate-600 uppercase"><?php echo $row['CATEGORY']; ?></span></td>
                        <td class="p-5 font-medium text-slate-800"><?php echo $row['BRAND_NAME']; ?></td>
                        <td class="p-5 text-center">
                            <span class="<?php echo $is_low ? 'text-red-600 font-bold' : 'text-slate-600'; ?>">
                                <?php echo $row['STOCK_QUANTITY']; ?>
                            </span>
                            <?php if($is_low) echo '<i class="fa-solid fa-triangle-exclamation text-red-500 ml-2 animate-pulse"></i>'; ?>
                        </td>
                        <td class="p-5 text-right font-mono text-emerald-600 font-bold">RM <?php echo number_format($row['UNIT_PRICE'], 2); ?></td>
                        <td class="p-5 text-center">
                            <button class="text-slate-400 hover:text-blue-600 mx-2"><i class="fa-solid fa-pen"></i></button>
                            <button class="text-slate-400 hover:text-red-600 mx-2"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>