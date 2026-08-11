<?php
ob_start();
$pageTitle = 'Add Customer - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

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
        // Check if mobile already exists
        // $checkSql = "SELECT id FROM parties WHERE mobile = ?";
        // $stmt = $conn->prepare($checkSql);
        // $stmt->bind_param('s', $mobile);
        // $stmt->execute();
        // $result = $stmt->get_result();
        
        $result = null;
        if ($mobile !== '') {
            $checkSql = "SELECT id FROM parties WHERE mobile = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param('s', $mobile);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($result && $result->num_rows > 0) { // if ($result->num_rows > 0) {
            setFlashMessage('error', 'Mobile number already exists');
        } else {
            $sql = "INSERT INTO parties (party_name, mobile, notes) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // $stmt->bind_param('sss', $partyName, $mobile, $notes);
            $mobileValue = $mobile === '' ? null : $mobile;
            $stmt->bind_param('sss', $partyName, $mobileValue, $notes);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Customer added successfully');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error adding customer');
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
                <li class="breadcrumb-item active">Add Customer</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Customer
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="party_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="party_name" name="party_name" 
                               placeholder="Enter customer name" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <!--<label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>-->
                        <label for="mobile" class="form-label">Mobile Number (Optional)</label>
                        <input type="text" class="form-control mobile-input number-only" id="mobile" name="mobile" 
                                placeholder="10-digit mobile" maxlength="10">
                        <small class="text-muted">If provided, it must be a unique 10-digit number</small>
                        <!--       placeholder="10-digit mobile" maxlength="10" required>-->
                        <!--<small class="text-muted">Must be unique 10-digit number</small>-->
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any additional notes about customer"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Customer
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb"></i> Tips:</h6>
                <ul>
                    <!--<li>Mobile number must be unique</li>-->
                    <li>Mobile number is optional, but must be unique when provided</li>
                    <li>Address field removed - not required</li>
                    <li>Use notes for any special information</li>
                    <li>Customer details auto-fill during invoice</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
