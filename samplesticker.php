<?php
/**
 * TSC TE244 2-Up Label Printer Controller
 * Layout: 2mm Left | 37mm Left Label | 4mm Center Gap | 37mm Right Label | 2mm Right
 * Canvas Total: 82.00 mm x 30.00 mm
 */

// 1. HARDCODED LOCAL PRINTER CONFIGURATION
$printer_name = "TSC TE244"; 

// 2. Physical Layout Constraints
$total_width_mm  = 82; // Total combined backing width
$label_height_mm = 30; // Physical sticker block height
$vertical_gap_mm = 3;  // Top-to-bottom edge row gap

// 3. Pixel Coordinates (8 dots per mm for 200 DPI resolution)
$dots_per_mm      = 8;  

// Left Sticker X coordinates
$left_start_x     = 2 * $dots_per_mm;                    // 16 dots
$left_center_x    = (2 + (37 / 2)) * $dots_per_mm;       // 164 dots (Perfect Center)

// Right Sticker X coordinates
$right_start_x    = (2 + 37 + 4) * $dots_per_mm;         // 344 dots
$right_center_x   = (43 + (37 / 2)) * $dots_per_mm;      // 492 dots (Perfect Center)

// 4. Print Payload Queue (Replace this array with your real database results)
$items_to_print = [
    "ITEM-001", 
    "ITEM-002" 
];

$total_stickers = count($items_to_print);
$tspl_command = "";

// 5. Build TSPL Spooler Engine Row-by-Row (Pairs of Two)
for ($i = 0; $i < $total_stickers; $i += 2) {
    
    // Core structure initializer for the active physical row block
    $tspl_command .= "SIZE " . $total_width_mm . " mm," . $label_height_mm . " mm\r\n";
    $tspl_command .= "GAP " . $vertical_gap_mm . " mm,0\r\n";
    $tspl_command .= "DIRECTION 1\r\n";
    $tspl_command .= "CLS\r\n"; // Flush previous memory buffer tracks
    
    // --- STICKER 1 (LEFT CANVAS) ---
    $left_text = $items_to_print[$i];
    
    // Centered Title Text (Note: '2' at the end enables true text center alignment layout)
    $tspl_command .= "TEXT " . $left_center_x . ",20,\"ROMAN.TTF\",0,1,1,2,\"" . $left_text . "\"\r\n";
    // Barcode: Slightly adjusted off the 2mm margin border so the barcode lines fit nicely
    $tspl_command .= "BARCODE " . ($left_start_x + 10) . ",65,\"128\",65,1,0,2,2,\"" . $left_text . "\"\r\n";
    
    // --- STICKER 2 (RIGHT CANVAS) ---
    // This block ONLY executes if a second item exists in the array sequence
    if (isset($items_to_print[$i + 1])) {
        $right_text = $items_to_print[$i + 1];
        
        // Centered Title Text
        $tspl_command .= "TEXT " . $right_center_x . ",20,\"ROMAN.TTF\",0,1,1,2,\"" . $right_text . "\"\r\n";
        // Barcode: Shifted past center gap zone safely onto the right tracking label
        $tspl_command .= "BARCODE " . ($right_start_x + 10) . ",65,\"128\",65,1,0,2,2,\"" . $right_text . "\"\r\n";
    }
    
    // Instructs printer engine to step forward exactly 1 row length and cut off
    $tspl_command .= "PRINT 1,1\r\n";
}

// 6. Direct Local USB Spooling Execution via Windows Command Line
try {
    // Save the raw TSPL instruction text string into a temporary system file
    $temp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tsc_label.txt";
    file_put_contents($temp_file, $tspl_command);

    // Use Windows command line to copy the binary file directly to the USB printer profile
    // Wrapping paths in quotes handles any unexpected spacing issues safely
    $command = 'copy /B "' . $temp_file . '" "\\\\127.0.0.1\\' . $printer_name . '"';
    exec($command, $output, $return_var);

    // Clean up the temporary file immediately from storage
    unlink($temp_file);

    if ($return_var === 0) {
        echo "<h3>Success!</h3>";
        echo "<p>Sent exact layout for " . $total_stickers . " items over to your <strong>" . $printer_name . "</strong> printer.</p>";
        echo "<p>Total rows fed: " . ceil($total_stickers / 2) . "</p>";
    } else {
        throw new Exception("Windows copy command failed. Exit code: " . $return_var . ". Verify your printer is turned on.");
    }
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Hardware Print Fault:</h3> " . $e->getMessage();
}
?>
