<?php
ob_start();
$pageTitle = 'Edit Customer - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Get customer details
$sql = "SELECT * FROM parties WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Customer not found');
    header('Location: list.php');
    exit();
}

$customer = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partyName = sanitize($_POST['party_name'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // if (empty($partyName) || empty($mobile)) {
    //     setFlashMessage('error', 'Name and Mobile are required');
    // } elseif (!isValidMobile($mobile)) {
    //     setFlashMessage('error', 'Please enter a valid 10-digit mobile number');
    if (empty($partyName)) {
        setFlashMessage('error', 'Customer name is required');
    } elseif ($mobile !== '' && !isValidMobile($mobile)) {
        setFlashMessage('error', 'Please enter a valid 10-digit mobile number or leave it blank');
    } else {
        // Check if mobile already exists (excluding current)
        // $checkSql = "SELECT id FROM parties WHERE mobile = ? AND id != ?";
        // $stmt = $conn->prepare($checkSql);
        // $stmt->bind_param('si', $mobile, $id);
        // $stmt->execute();
        // $result = $stmt->get_result();
        
        $result = null;
        if ($mobile !== '') {
            $checkSql = "SELECT id FROM parties WHERE mobile = ? AND id != ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param('si', $mobile, $id);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
                if ($result && $result->num_rows > 0) { // if ($result->num_rows > 0) {
            setFlashMessage('error', 'Mobile number already exists');
        } else {
            $sql = "UPDATE parties SET party_name = ?, mobile = ?, notes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            // $stmt->bind_param('sssi', $partyName, $mobile, $notes, $id);
            $mobileValue = $mobile === '' ? null : $mobile;
            $stmt->bind_param('sssi', $partyName, $mobileValue, $notes, $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Customer updated successfully');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error updating customer');
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
                <li class="breadcrumb-item"><a href="list.php">Customer Master</a></li>
                <li class="breadcrumb-item active">Edit Customer</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit Customer
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="party_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="party_name" name="party_name" 
                               value="<?php echo htmlspecialchars($customer['party_name']); ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <!--<label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>-->
                        <label for="mobile" class="form-label">Mobile Number (Optional)</label>
                        <input type="text" class="form-control mobile-input number-only" id="mobile" name="mobile" 
                               value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>" maxlength="10">
                               <!--value="<?php echo $customer['mobile']; ?>" maxlength="10" required>-->
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Customer
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
