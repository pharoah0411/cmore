<?php
// check_expiry.php

// Query the 'product' table from your cmore database
// It checks for products with an EXPIRY_DATE within 30 days that are currently in stock
$query = "SELECT COUNT(*) as alert_count 
          FROM product 
          WHERE EXPIRY_DATE IS NOT NULL 
          AND EXPIRY_DATE <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND STOCK_QUANTITY > 0";

$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $alert_count = $row['alert_count'];

    // If there are expiring products, trigger the pop-up
    if ($alert_count > 0) {
        // Option 1: Standard Browser Alert
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                alert('Inventory Warning:\\n\\nYou have $alert_count product(s) in stock that are expired or expiring within the next 30 days. Please check the inventory.');
            });
        </script>";
        
        /* // Option 2: If you prefer SweetAlert (Uncomment below and delete Option 1 above)
        // Make sure <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> is in your header
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                  icon: 'warning',
                  title: 'Expiry Alert',
                  text: 'You have $alert_count product(s) in stock that are expired or expiring within the next 30 days.',
                  confirmButtonColor: '#d33'
                });
            });
        </script>";
        */
    }
}
?>