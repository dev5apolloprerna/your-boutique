<?php
ob_start();
$pageTitle = 'Edit Size - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Get size details
$sql = "SELECT * FROM sizes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Size not found');
    header('Location: list.php');
    exit();
}

$size = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sizeName = sanitize($_POST['size_name'] ?? '');
    $sizeCode = sanitize($_POST['size_code'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($sizeName) || empty($sizeCode)) {
        setFlashMessage('error', 'Please fill all required fields');
    } else if (strlen($sizeCode) !== 1 || !preg_match('/^[A-G]$/', $sizeCode)) {
        setFlashMessage('error', 'Size code must be single character (A-G)');
    } else {
        // Check if size code changed and if new code already exists
        if ($sizeCode !== $size['size_code']) {
            $checkSql = "SELECT id FROM sizes WHERE size_code = ? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('si', $sizeCode, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                setFlashMessage('error', 'Size code ' . $sizeCode . ' already exists!');
            } else {
                // Update size
                $updateSql = "UPDATE sizes SET size_name = ?, size_code = ?, sort_order = ?, is_active = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('ssiii', $sizeName, $sizeCode, $sortOrder, $isActive, $id);
                
                if ($updateStmt->execute()) {
                    setFlashMessage('success', 'Size updated successfully!');
                    header('Location: list.php');
                    exit();
                } else {
                    setFlashMessage('error', 'Error updating size');
                }
            }
        } else {
            // No code change, just update other fields
            $updateSql = "UPDATE sizes SET size_name = ?, sort_order = ?, is_active = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('siii', $sizeName, $sortOrder, $isActive, $id);
            
            if ($updateStmt->execute()) {
                setFlashMessage('success', 'Size updated successfully!');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error updating size');
            }
        }
    }
}

// Get all products using this size
$productsSql = "SELECT COUNT(*) as count FROM products p 
                INNER JOIN product_stock ps ON p.id = ps.product_id 
                WHERE ps.size_id = ?";
$productsStmt = $conn->prepare($productsSql);
$productsStmt->bind_param('i', $id);
$productsStmt->execute();
$productsResult = $productsStmt->get_result();
$productsCount = $productsResult->fetch_assoc();
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Size Master</a></li>
                <li class="breadcrumb-item active">Edit Size</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Edit Size
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label for="size_name" class="form-label">Size Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="size_name" name="size_name" 
                               value="<?php echo htmlspecialchars($size['size_name']); ?>" required autofocus>
                        <small class="text-muted">e.g., XS, S, M, L, XL, XXL, XXXL</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="size_code" class="form-label">Size Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="size_code" name="size_code" 
                               value="<?php echo $size['size_code']; ?>" maxlength="1" required>
                        <small class="text-muted">Single character (A-G)</small>
                        <div id="codeInfo"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?php echo $size['sort_order']; ?>" min="1">
                        <small class="text-muted">Order in which sizes appear</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $size['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Size
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Size Information
                </div>
                <div class="card-body">
                    <h6>Current Size Code: <span class="badge bg-primary" style="font-size: 1.1em; padding: 8px 12px;"><?php echo $size['size_code']; ?></span></h6>
                    <hr>
                    
                    <h6>Barcode Format:</h6>
                    <p class="small mb-3">
                        <strong>5001</strong> (Prefix) + 
                        <strong><?php echo $size['size_code']; ?></strong> (This Size) + 
                        <strong>000001</strong> (Product Number)
                    </p>
                    <p class="small text-muted mb-3">
                        <strong>Example:</strong> <code>5001<?php echo $size['size_code']; ?>000001</code><br>
                        <strong>Search With:</strong> <code><?php echo $size['size_code']; ?>000001</code>
                    </p>
                    
                    <hr>
                    
                    <h6>Usage:</h6>
                    <div class="alert alert-info mb-2">
                        <small>
                            <strong><?php echo $productsCount['count']; ?> product(s)</strong> use this size
                        </small>
                    </div>
                    
                    <hr>
                    
                    <h6>All Size Codes:</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>A</strong></td>
                            <td>XS (Extra Small)</td>
                        </tr>
                        <tr>
                            <td><strong>B</strong></td>
                            <td>S (Small)</td>
                        </tr>
                        <tr>
                            <td><strong>C</strong></td>
                            <td>M (Medium)</td>
                        </tr>
                        <tr>
                            <td><strong>D</strong></td>
                            <td>L (Large)</td>
                        </tr>
                        <tr>
                            <td><strong>E</strong></td>
                            <td>XL (Extra Large)</td>
                        </tr>
                        <tr>
                            <td><strong>F</strong></td>
                            <td>XXL (2XL)</td>
                        </tr>
                        <tr>
                            <td><strong>G</strong></td>
                            <td>XXXL (3XL)</td>
                        </tr>
                    </table>
                    
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <small>
                            <strong>Note:</strong> Changing the size code will affect all barcodes using this size. 
                            Make sure no active products use the old code.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const codeMap = {
        'A': 'XS (Extra Small)',
        'B': 'S (Small)',
        'C': 'M (Medium)',
        'D': 'L (Large)',
        'E': 'XL (Extra Large)',
        'F': 'XXL (2XL)',
        'G': 'XXXL (3XL)'
    };
    
    $('#size_code').on('input', function() {
        let code = $(this).val().toUpperCase();
        $(this).val(code);
        
        if (code && codeMap[code]) {
            $('#codeInfo').html('<small class="text-success">✓ ' + codeMap[code] + '</small>');
        } else if (code) {
            $('#codeInfo').html('<small class="text-danger">✗ Invalid code (use A-G)</small>');
        } else {
            $('#codeInfo').html('');
        }
    });
    
    // Show info on load
    $('#size_code').trigger('input');
});
</script>
