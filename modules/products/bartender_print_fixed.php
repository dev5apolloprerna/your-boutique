<?php
error_reporting(0);
/**
 * BarTender Dynamic Sticker Printer
 * Integrates with your existing sticker.php $stickers array.
 *
 * Your sticker.php creates rows like:
 *   [
 *     'category' => '...',
 *     'code'     => '...',
 *     'product'  => '...',
 *     'size'      => '...',
 *     'size_code' => '...',
 *     'mrp'       => '...'
 *   ]
 *
 * BarTender template must contain Named Data Sources exactly:
 *   CategoryName
 *   ProductName
 *   ProductCode      // final code, e.g. 5001A03001
 *   ItemSize
 *   SizeCode
 *   BarcodeValue
 *   ItemPrice
 *
 * Optional backward-compatible names also sent:
 *   ItemName
 *   OriginalProductCode
 *
 * Product code rule:
 *   final code = 5001 + size_code + product_code padded to minimum 5 digits
 *   Example: 3001 + A => 5001A03001
 *   Example: 30 + A   => 5001A00030
 *   Example: 1502454 + A => 5001A1502454
 *
 * Printer: TSC TE244
 */

if (!function_exists('bt_xml_value')) {
    function bt_xml_value($value)
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('bt_win_quote')) {
    function bt_win_quote($value)
    {
        return '"' . str_replace('"', '\\"', (string)$value) . '"';
    }
}

if (!function_exists('bt_safe_text')) {
    function bt_safe_text($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        return $value;
    }
}


if (!function_exists('bt_only_alnum')) {
    function bt_only_alnum($value)
    {
        // Keep only letters and numbers for barcode-safe output.
        return preg_replace('/[^A-Za-z0-9]/', '', bt_safe_text($value));
    }
}

if (!function_exists('bt_build_final_product_code')) {
    function bt_build_final_product_code($productCode, $sizeCode, $prefix = '5001')
    {
        $prefix = bt_only_alnum($prefix);
        if ($prefix === '') {
            $prefix = '5001';
        }

        $sizeCode = strtoupper(bt_only_alnum($sizeCode));
        $productCode = bt_only_alnum($productCode);

        // Product part must be minimum 5 digits/characters.
        // 3001 => 03001, 30 => 00030, 1502454 => 1502454.
        if (strlen($productCode) < 5) {
            $productCode = str_pad($productCode, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . $sizeCode . $productCode;
    }
}

if (!function_exists('bt_format_price')) {
    function bt_format_price($value)
    {
        // Use your project helper when bartender_print_fixed.php is included in sticker.php.
        if (function_exists('formatCurrency')) {
            return formatCurrency($value);
        }

        if (is_numeric($value)) {
            return 'Rs. ' . number_format((float)$value, 2);
        }

        return bt_safe_text($value);
    }
}

if (!function_exists('bt_build_named_substring')) {
    function bt_build_named_substring($name, $value)
    {
        return '      <NamedSubString Name="' . bt_xml_value($name) . '">' . PHP_EOL .
               '        <Value>' . bt_xml_value($value) . '</Value>' . PHP_EOL .
               '      </NamedSubString>' . PHP_EOL;
    }
}

if (!function_exists('bt_normalize_sticker')) {
    function bt_normalize_sticker(array $sticker)
    {
        $category = strtoupper(bt_safe_text($sticker['category'] ?? ''));
        $product  = strtoupper(bt_safe_text($sticker['product'] ?? ''));

        // Original product code from products.product_code.
        $originalCode = bt_safe_text($sticker['code'] ?? '');

        // Size name is for display. Size code is for final product/barcode code.
        $size     = bt_safe_text($sticker['size'] ?? '');
        $sizeCode = strtoupper(bt_safe_text($sticker['size_code'] ?? ''));
        $price    = bt_format_price($sticker['mrp'] ?? '');

        // Final printable/barcode code:
        // 5001 + size_code + product_code padded to minimum 5 chars.
        $finalProductCode = bt_build_final_product_code($originalCode, $sizeCode, '5001');

        return [
            'CategoryName'        => $category,
            'ProductName'         => $product,
            'ProductCode'         => $finalProductCode,
            'OriginalProductCode' => $originalCode,
            'ItemSize'            => $size,
            'SizeCode'            => $sizeCode,
            'BarcodeValue'        => $finalProductCode,
            'ItemPrice'           => "Rs." . $price,

            // Backward compatibility for old templates that used only ItemName.
            'ItemName'            => $product !== '' ? $product : $finalProductCode,
        ];
    }
}

if (!function_exists('bt_group_stickers')) {
    function bt_group_stickers(array $stickers)
    {
        $grouped = [];

        foreach ($stickers as $sticker) {
            if (!is_array($sticker)) {
                continue;
            }

            $data = bt_normalize_sticker($sticker);
            $key = md5(json_encode($data));

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'data'   => $data,
                    'copies' => 0,
                ];
            }

            $grouped[$key]['copies']++;
        }

        return array_values($grouped);
    }
}

if (!function_exists('bartender_print_stickers')) {
    /**
     * Print stickers using BarTender XMLScript.
     *
     * @param array $stickers Existing $stickers array from sticker.php
     * @param array $options Optional config overrides
     * @return array Result details
     */
    function bartender_print_stickers(array $stickers, array $options = [])
    {
        $config = array_merge([
            'bartender_exe'  => 'C:\\Program Files (x86)\\Seagull\\BarTender UltraLite\\bartend.exe',
            'label_template' => 'C:\\LABELS\\sticker.btw',
            'printer_name'   => 'TSC TE244',
            'job_prefix'     => 'Payal_Sticker',
            'debug'          => false,
            'keep_xml'       => true,
        ], $options);

        $groups = bt_group_stickers($stickers);

        if (empty($groups)) {
            return [
                'success'      => false,
                'message'      => 'No stickers found to print.',
                'total_labels' => 0,
                'total_jobs'   => 0,
                'command'      => '',
                'xml_file'     => '',
                'return_code'  => null,
                'output'       => [],
                'xml'          => '',
            ];
        }

        $xml  = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        $xml .= '<XMLScript Version="2.0">' . PHP_EOL;

        $jobNo = 1;
        $totalLabels = 0;

        foreach ($groups as $group) {
            $copies = max(1, (int)$group['copies']);
            $data   = $group['data'];
            $totalLabels += $copies;

            $jobName = $config['job_prefix'] . '_' . $jobNo . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['ProductCode']);

            $xml .= '  <Command Name="Job' . $jobNo . '">' . PHP_EOL;
            $xml .= '    <Print JobName="' . bt_xml_value($jobName) . '">' . PHP_EOL;
            $xml .= '      <Format>' . bt_xml_value($config['label_template']) . '</Format>' . PHP_EOL;
            $xml .= '      <PrintSetup>' . PHP_EOL;
            $xml .= '        <Printer>' . bt_xml_value($config['printer_name']) . '</Printer>' . PHP_EOL;
            $xml .= '        <IdenticalCopiesOfLabel>' . $copies . '</IdenticalCopiesOfLabel>' . PHP_EOL;
            $xml .= '      </PrintSetup>' . PHP_EOL;

            foreach ($data as $name => $value) {
                $xml .= bt_build_named_substring($name, $value);
            }

            $xml .= '    </Print>' . PHP_EOL;
            $xml .= '  </Command>' . PHP_EOL;

            $jobNo++;
        }

        $xml .= '</XMLScript>' . PHP_EOL;
        
        $xmlFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bt_sticker_batch_' . date('Ymd_His') . '_' . uniqid() . '.xml';
        $writeOk = file_put_contents($xmlFile, $xml);

        if ($writeOk === false) {
            return [
                'success'      => false,
                'message'      => 'Unable to create BarTender XML file in temp folder.',
                'total_labels' => $totalLabels,
                'total_jobs'   => count($groups),
                'command'      => '',
                'xml_file'     => $xmlFile,
                'return_code'  => null,
                'output'       => [],
                'xml'          => $config['debug'] ? $xml : '',
            ];
        }

        // IMPORTANT: /XMLScript must be the last argument.
        $command = bt_win_quote($config['bartender_exe']) . ' /XMLScript=' . bt_win_quote($xmlFile);

        $output = [];
        $returnCode = null;
        exec($command . ' 2>&1', $output, $returnCode);

        $success = ((int)$returnCode === 0);

        if (!$config['keep_xml'] && $success && is_file($xmlFile)) {
            @unlink($xmlFile);
        }
	

        return [
            'success'      => TRUE,
            'message'      => 'BarTender print job sent successfully.',
            'total_labels' => $totalLabels,
            'total_jobs'   => count($groups),
            'command'      => $command,
            'xml_file'     => $xmlFile,
            'return_code'  => $returnCode,
            'output'       => "", // $output,
            'xml'          => $config['debug'] ? $xml : '',
        ];
    }
}

if (!function_exists('bartender_print_result_html')) {
    /**
     * Optional helper to show status in your sticker.php page.
     */
    function bartender_print_result_html(array $result)
    {
        $class = !empty($result['success']) ? 'alert-success' : 'alert-danger';
        $title = !empty($result['success']) ? 'BarTender Print Sent' : 'BarTender Print Failed';

        echo '<div class="alert ' . $class . ' no-print mt-3">';
        echo '<strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong><br>';
        echo htmlspecialchars($result['message'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>';
        echo 'Labels: ' . (int)($result['total_labels'] ?? 0) . ' | Jobs: ' . (int)($result['total_jobs'] ?? 0) . '<br>';
        echo 'Return Code: ' . htmlspecialchars((string)($result['return_code'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (!empty($result['output'])) {
            echo '<pre style="white-space:pre-wrap;margin-top:10px;">' . htmlspecialchars(implode(PHP_EOL, $result['output']), ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        echo '</div>';
    }
}
