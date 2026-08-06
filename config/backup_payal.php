<?php
// =========================
// PAYAL DATABASE BACKUP
// Core PHP + mysqldump
// =========================

// Database details
$dbHost = "localhost";
$dbUser = "root";
$dbPass = ""; // WAMP default blank password
$dbName = "payal";

// IMPORTANT: Change this path as per your WAMP MySQL version
$mysqldumpPath = "C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe";

// Backup folder
$backupDir = "D:\\Backup\\SQL";

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

function safeShell($value)
{
    return escapeshellarg($value);
}

if (isset($_POST['backup'])) {

    $date = date("Y-m-d_H-i-s");
    $backupFile = $backupDir . "/payal_full_backup_" . $date . ".sql";

    $command = safeShell($mysqldumpPath)
        . " --host=" . safeShell($dbHost)
        . " --user=" . safeShell($dbUser);

    if ($dbPass !== "") {
        $command .= " --password=" . safeShell($dbPass);
    }

    $command .= " --databases " . safeShell($dbName)
        . " --routines"
        . " --events"
        . " --triggers"
        . " --single-transaction"
        . " --add-drop-table"
        . " --result-file=" . safeShell($backupFile);

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($backupFile) . "\"");
        header("Content-Length: " . filesize($backupFile));
        readfile($backupFile);
        exit;

    } else {
        $error = "Backup failed. Please check mysqldump path, database name, username, or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payal Database Backup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 40px;
        }

        .box {
            max-width: 500px;
            background: #fff;
            padding: 30px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        button {
            background: #198754;
            color: #fff;
            border: none;
            padding: 14px 25px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #146c43;
        }

        .error {
            margin-top: 20px;
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Payal Database Full Backup</h2>
    <p>Click below button to export full backup of <b>payal</b> database.</p>

    <form method="post">
        <button type="submit" name="backup">
            Download Full Database Backup
        </button>
    </form>

    <?php if (!empty($error)) { ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>
</div>

</body>
</html>