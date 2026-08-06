<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Require login for all pages except login.php
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php') {
    requireLogin();
}

$conn = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Your-boutique'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
    <?php if ($currentPage !== 'login.php'): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $web_url ?>dashboard.php">
                <i class="bi bi-shop"></i> Your-boutique
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="<?= $web_url ?>dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Masters
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/your-boutique/modules/categories/list.php">Category Master</a></li>
                            <li><a class="dropdown-item" href="/your-boutique/modules/sizes/list.php">Size Master</a></li>
                            <li><a class="dropdown-item" href="/your-boutique/modules/customers/list.php">Customer Master</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/your-boutique/modules/products/list.php">Product Master</a></li>
                            <li><a class="dropdown-item" href="/your-boutique/modules/stock/add_stock.php">Add Stock</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'create_invoice.php') ? 'active' : ''; ?>" href="/your-boutique/modules/invoice/create_invoice.php">
                            <i class="bi bi-receipt"></i> Create Invoice
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'list.php' && strpos($_SERVER['PHP_SELF'], 'invoice') !== false) ? 'active' : ''; ?>" href="/your-boutique/modules/invoice/list.php">
                            <i class="bi bi-list-ul"></i> All Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'create_credit_note.php') ? 'active' : ''; ?>" href="/your-boutique/modules/credit_note/create_credit_note.php">
                            <i class="bi bi-arrow-return-left"></i> Credit Note
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'list.php' && strpos($_SERVER['PHP_SELF'], 'credit_note') !== false) ? 'active' : ''; ?>" href="/your-boutique/modules/credit_note/list.php">
                            <i class="bi bi-list-check"></i> All Credit Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/your-boutique/modules/products/sticker.php">
                            <i class="bi bi-printer"></i> Print Stickers
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-text"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/your-boutique/reports/gst_report.php">GST Report</a></li>
                            <li><a class="dropdown-item" href="/your-boutique/reports/stock_report.php">Stock Report</a></li>
                            <li><a class="dropdown-item" href="/your-boutique/reports/sales_report.php">Sales Report</a></li>
		                	<!--<li><a class="dropdown-item" href="<?= $web_url ?>config/backup_payal.php">Sql Back Up</a></li>-->
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo getUserFullName(); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/your-boutique/modules/profile/change_password.php">Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/your-boutique/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="container-fluid mt-4">
