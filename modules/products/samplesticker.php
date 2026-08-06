<?php
/**
 * Scan and List Local Windows Printer Names
 */
echo "<h2>Searching for your TSC Printer Name...</h2>";

if (PHP_OS_FAMILY === 'Windows') {
    // Execute native Windows management instrumentation command to fetch printer names
    exec('wmic printer get name', $output);
    
    if (!empty($output)) {
        echo "<p>Copy the exact name from the list below to use in your print scripts:</p>";
        echo "<ul style='font-family: monospace; font-size: 16px; background: #f4f4f4; padding: 20px; border: 1px solid #ccc; width: fit-content;'>";
        
        foreach ($output as $line) {
            $printer_name = trim($line);
            
            // Skip the column header and blank lines
            if ($printer_name == "Name" || empty($printer_name)) {
                continue;
            }
            
            // Highlight TSC printer if found to make it easier to see
            if (stripos($printer_name, 'TSC') !== false) {
                echo "<li style='color: green; font-weight: bold;'>⭐ " . htmlspecialchars($printer_name) . " (Recommended for your script)</li>";
            } else {
                echo "<li>" . htmlspecialchars($printer_name) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>No printers detected. Verify your TSC printer is turned on and connected via USB.</p>";
    }
} else {
    echo "<p style='color: red;'>This automated script only functions on Windows environments.</p>";
}
?>
<?php
/**
 * TSC 2-Up Exact Count Sticker Printing Script
 * Designed for local USB/Network printers shared on Windows
 */

// // ==========================================
// // 1. CONFIGURATION & STICKER DIMENSIONS
// // ==========================================
// //$printer_share_name = "\\\\localhost\\TSC244"; // Your Windows printer share path
// $printer_share_name = "\\\\localhost\\TSC TE244"; // Your Windows printer share path

// $label_width_mm  = 40; // Width of ONE individual sticker in mm
// $label_height_mm = 30; // Height of the sticker in mm
// $gap_between_mm  = 2;  // The physical horizontal gap between left & right sticker
// $edge_margin_mm  = 2;  // Left margin of the liner paper

// // Calculate total row layout parameters
// $total_width_mm  = ($label_width_mm * 2) + $gap_between_mm;
// $dots_per_mm     = 8;  // Standard for 200 DPI printers (Use 12 for 300 DPI)

// // Calculate precise horizontal (X) coordinate starting points
// $left_sticker_X  = $edge_margin_mm * $dots_per_mm;
// $right_sticker_X = ($edge_margin_mm + $label_width_mm + $gap_between_mm) * $dots_per_mm;

// // ==========================================
// // 2. YOUR DATA SOURCE (Replace with DB query)
// // ==========================================
// // This list can have an odd or even number of items.
// $items_to_print = [
//     "PROD-001",
//     "PROD-002",
//     // "PROD-003",
//     // "PROD-004",
//     // "PROD-005" // 5 items total (Will print 2 full rows and 1 item on the 3rd row)
// ];

// $total_stickers = count($items_to_print);
// $tspl_command = "";

// // ==========================================
// // 3. ROW-BY-ROW LOGIC (PAIRS OF TWO)
// // ==========================================
// for ($i = 0; $i < $total_stickers; $i += 2) {
    
//     // Page configuration header for this specific row
//     $tspl_command .= "SIZE " . $total_width_mm . " mm," . $label_height_mm . " mm\r\n";
//     $tspl_command .= "GAP " . $gap_between_mm . " mm,0\r\n";
//     $tspl_command .= "DIRECTION 1\r\n";
//     $tspl_command .= "CLS\r\n"; // Clear memory buffer for clean slate
    
//     // --- LEFT STICKER ---
//     $left_text = $items_to_print[$i];
//     // Add text element (X, Y, Font, Rotation, X-Multi, Y-Multi, "Text")
//     $tspl_command .= "TEXT " . $left_sticker_X . ",20,\"ROMAN.TTF\",0,1,1,\"" . $left_text . "\"\r\n";
//     // Add barcode element (X, Y, Type, Height, Human-Readable, Rotation, Narrow, Wide, "Data")
//     $tspl_command .= "BARCODE " . $left_sticker_X . ",70,\"128\",60,1,0,2,2,\"" . $left_text . "\"\r\n";
    
//     // --- RIGHT STICKER (Conditional Check) ---
//     // Prints only if there is a matching second item available for this row
//     if (isset($items_to_print[$i + 1])) {
//         $right_text = $items_to_print[$i + 1];
//         // Mirror the exact layout to the calculated right-side X coordinate
//         $tspl_command .= "TEXT " . $right_sticker_X . ",20,\"ROMAN.TTF\",0,1,1,\"" . $right_text . "\"\r\n";
//         $tspl_command .= "BARCODE " . $right_sticker_X . ",70,\"128\",60,1,0,2,2,\"" . $right_text . "\"\r\n";
//     }
    
//     // Execute print execution for this completed row (1 row, 1 copy)
//     $tspl_command .= "PRINT 1,1\r\n";
// }

// // ==========================================
// // 4. DIRECT STREAM TO WINDOWS PRINTER PORT
// // ==========================================
// try {
//     // Open a direct output handle to the shared device
//     $fp = fopen($printer_share_name, "w");
    
//     if (!$fp) {
//         throw new Exception("Could not open print stream. Ensure printer sharing is configured as 'TSC244'.");
//     }
    
//     // Stream raw TSPL buffer payload straight to hardware
//     fwrite($fp, $tspl_command);
//     fclose($fp);
    
//     echo "Success: Sent " . $total_stickers . " labels to the local TSC printer safely.";
    
// } catch (Exception $e) {
//     echo "Printing Failed: " . $e->getMessage();
// }
?>

