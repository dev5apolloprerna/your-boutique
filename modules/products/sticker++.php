<?php
/**
 * Merged BarTender + Local Offline USB Controller (Multi-Product Loop)
 * Template Path: C:\Labels\sticker.btw
 * NO PRINTER SHARING REQUIRED
 */

// 1. SYSTEM ENVIRONMENT PATHS
 $bartender_path = '"C:\\Program Files (x86)\\Seagull\\BarTender UltraLite\\bartend.exe"'; 
$label_template = '"C:\\LABELS\\sticker.btw"';

// Use the EXACT printer name from your Windows "Printers & Scanners" panel
$printer_name   = "TSC TE244"; 

// 2. MULTI-PRODUCT DATA QUEUE
$products_to_print = [
    [
        "name"   => "Wireless Mouse",
        "amount" => "Rs. 499.00",
        "copies" => 2
    ]
  
];

echo "<h2>Launching BarTender Batch Print Engine...</h2>";
$total_jobs_sent = 0;

// 3. LOOP THROUGH EACH PRODUCT IN THE QUEUE
foreach ($products_to_print as $product) {
    $item_name   = $product['name'];
    $item_amount = $product['amount'];
    $copy_count  = $product['copies'];

    echo "<p>Streaming layout parameters for <strong>" . htmlspecialchars($item_name) . "</strong> (" . $copy_count . " copies)...</p>";

    // 4. CONSTRUCT THE AUTOMATION COMMAND (Bypassing network paths entirely)
    // We pass the clean $printer_name straight into /PRN without any slashes or IPs
    $command = $bartender_path . ' /F=' . $label_template . 
               ' /PRN="' . $printer_name . '"' . 
               ' /SetNamedSubString="ItemName=' . addslashes($item_name) . '"' . 
               ' /SetNamedSubString="ItemPrice=' . addslashes($item_amount) . '"' . 
               ' /C=' . $copy_count . 
               ' /P /X';

    // 5. EXECUTE THE PRINT FOR THIS PRODUCT
	echo $command;
    //exec($command, $output, $return_var);

    if ($return_var === 0) {
        $total_jobs_sent++;
    } else {
        echo "<p style='color:red;'><strong>❌ Failed to print:</strong> " . htmlspecialchars($item_name) . " (Error Code: " . $return_var . ")</p>";
    }
}

// 6. FINAL BATCH STATUS TRACKER
if ($total_jobs_sent === count($products_to_print)) {
    echo "<hr>";
    echo "<p style='color:green; font-size:18px;'><strong>✔️ ALL BATCH JOBS SUCCESSFUL!</strong></p>";
    echo "<p>Your TSC TE244 should have printed a total of 8 stickers cleanly across 4 rows.</p>";
} 
?>
