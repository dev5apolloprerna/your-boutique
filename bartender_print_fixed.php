<?php
/**
 * BarTender Local USB Print - Named Data Source version
 * Template must contain Named Data Sources exactly:
 *   ItemName
 *   ItemPrice
 * Printer: TSC TE244
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$bartenderExe  = 'C:\\Program Files (x86)\\Seagull\\BarTender UltraLite\\bartend.exe';
$labelTemplate = 'C:\\LABELS\\sticker.btw';
$printerName   = 'TSC TE244';

$products_to_print = [
    [
        'name'   => 'Wireless Mouse',
        'amount' => 'Rs. 499.00',
        'copies' => 2,
    ],
];

function xmlValue($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function winQuote($value) {
    return '"' . str_replace('"', '\\"', $value) . '"';
}

echo "<h2>Launching BarTender Batch Print Engine...</h2>";

$totalJobsSent = 0;
$jobNo = 1;

foreach ($products_to_print as $product) {
    $itemName   = $product['name'];
    $itemAmount = $product['amount'];
    $copyCount  = max(1, (int)$product['copies']);

    $xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
    $xml .= '<XMLScript Version="2.0">' . PHP_EOL;
    $xml .= '  <Command Name="Job' . $jobNo . '">' . PHP_EOL;
    $xml .= '    <Print JobName="Sticker_' . $jobNo . '">' . PHP_EOL;
    $xml .= '      <Format>' . xmlValue($labelTemplate) . '</Format>' . PHP_EOL;
    $xml .= '      <PrintSetup>' . PHP_EOL;
    $xml .= '        <Printer>' . xmlValue($printerName) . '</Printer>' . PHP_EOL;
    $xml .= '        <IdenticalCopiesOfLabel>' . $copyCount . '</IdenticalCopiesOfLabel>' . PHP_EOL;
    $xml .= '      </PrintSetup>' . PHP_EOL;
    $xml .= '      <NamedSubString Name="ItemName">' . PHP_EOL;
    $xml .= '        <Value>' . xmlValue($itemName) . '</Value>' . PHP_EOL;
    $xml .= '      </NamedSubString>' . PHP_EOL;
    $xml .= '      <NamedSubString Name="ItemPrice">' . PHP_EOL;
    $xml .= '        <Value>' . xmlValue($itemAmount) . '</Value>' . PHP_EOL;
    $xml .= '      </NamedSubString>' . PHP_EOL;
    $xml .= '    </Print>' . PHP_EOL;
    $xml .= '  </Command>' . PHP_EOL;
    $xml .= '</XMLScript>' . PHP_EOL;

    $xmlFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bt_job_' . date('Ymd_His') . '_' . $jobNo . '.xml';
    file_put_contents($xmlFile, $xml);

    echo '<p>Printing <strong>' . htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') . '</strong> - ' . $copyCount . ' copies...</p>';
    echo '<pre>' . htmlspecialchars($xml, ENT_QUOTES, 'UTF-8') . '</pre>';

    // IMPORTANT: /XMLScript must be the last argument.
    $command = winQuote($bartenderExe) . ' /XMLScript=' . winQuote($xmlFile);

    $output = [];
    $returnVar = null;
    exec($command . ' 2>&1', $output, $returnVar);

    echo '<pre>Command: ' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . PHP_EOL;
    echo 'Return Code: ' . (int)$returnVar . PHP_EOL;
    echo htmlspecialchars(implode(PHP_EOL, $output), ENT_QUOTES, 'UTF-8') . '</pre>';

    if ($returnVar === 0) {
        $totalJobsSent++;
    } else {
        echo '<p style="color:red;"><strong>Failed:</strong> ' . htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $jobNo++;
}

if ($totalJobsSent === count($products_to_print)) {
    echo '<hr><p style="color:green;font-size:18px;"><strong>All batch jobs sent successfully.</strong></p>';
} else {
    echo '<hr><p style="color:red;font-size:18px;"><strong>Some jobs failed. Check the command/output above.</strong></p>';
}
?>
