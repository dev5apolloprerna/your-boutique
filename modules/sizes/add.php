<?php
ob_start();
$pageTitle = 'Add Size - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get max sort order
$sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM sizes";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$nextOrder = $row['next_order'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sizeName = sanitize($_POST['size_name'] ?? '');
    $sizeCode = sanitize($_POST['size_code'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? $nextOrder);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($sizeName) || empty($sizeCode)) {
        setFlashMessage('error', 'Please fill all required fields');
    } else if (strlen($sizeCode) !== 1 || !preg_match('/^[A-G]$/', $sizeCode)) {
        setFlashMessage('error', 'Size code must be single character (A-G)');
    } else {
        // Check if size code already exists
        $checkSql = "SELECT id FROM sizes WHERE size_code = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $sizeCode);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            setFlashMessage('error', 'Size code ' . $sizeCode . ' already exists!');
        } else {
            $sql = "INSERT INTO sizes (size_name, size_code, sort_order, is_active) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssii', $sizeName, $sizeCode, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Size added successfully!');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error adding size');
            }
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Size Master</a></li>
                <li class="breadcrumb-item active">Add Size</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Add New Size
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label for="size_name" class="form-label">Size Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="size_name" name="size_name" 
                               placeholder="e.g., XS, S, M, L, XL, XXL, XXXL" required autofocus>
                        <small class="text-muted">Enter size name</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="size_code" class="form-label">Size Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="size_code" name="size_code" 
                               placeholder="A, B, C, D, E, F, or G" maxlength="1" required>
                        <small class="text-muted">Single character (A-G)</small>
                        <div id="codeInfo"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?php echo $nextOrder; ?>" min="1">
                        <small class="text-muted">Order in which sizes appear</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Size
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Size Code Guide
                </div>
                <div class="card-body">
                    <h6>Available Size Codes (A-G):</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Typical Use</th>
                                <th>Barcode Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>A</strong></td>
                                <td>XS (Extra Small)</td>
                                <td><code>5001A000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>B</strong></td>
                                <td>S (Small)</td>
                                <td><code>5001B000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>C</strong></td>
                                <td>M (Medium)</td>
                                <td><code>5001C000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>D</strong></td>
                                <td>L (Large)</td>
                                <td><code>5001D000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>E</strong></td>
                                <td>XL (Extra Large)</td>
                                <td><code>5001E000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>F</strong></td>
                                <td>XXL (2XL)</td>
                                <td><code>5001F000001</code></td>
                            </tr>
                            <tr>
                                <td><strong>G</strong></td>
                                <td>XXXL (3XL)</td>
                                <td><code>5001G000001</code></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <hr>
                    
                    <h6>Barcode Format:</h6>
                    <p class="small mb-2">
                        <strong>5001</strong> (Prefix) + 
                        <strong>A-G</strong> (Size Code) + 
                        <strong>000001</strong> (Product Number)
                    </p>
                    
                    <p class="small text-muted">
                        💡 When ordering, customers search with: 
                        <code>A000001</code> (Size Code + Product Number)
                    </p>
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
});
</script>
