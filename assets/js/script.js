// Custom JavaScript for Payal Arban Stichis

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Format currency inputs
    $('.currency-input').on('blur', function() {
        let value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
    
    // Print function
    $('.btn-print').on('click', function() {
        window.print();
    });
    
    // Number only input
    $('.number-only').on('keypress', function(e) {
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });
    
    // Mobile number validation
    $('.mobile-input').on('blur', function() {
        let mobile = $(this).val();
        if (mobile && !mobile.match(/^[6-9]\d{9}$/)) {
            alert('Please enter a valid 10-digit mobile number');
            $(this).focus();
        }
    });
});

// Show loading spinner
function showLoading() {
    $('.loading').addClass('show');
}

// Hide loading spinner
function hideLoading() {
    $('.loading').removeClass('show');
}

// Format number as currency
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

// Calculate GST breakdown
function calculateGST(mrp, gstRate) {
    let baseAmount = mrp / (1 + (gstRate / 100));
    let gstAmount = mrp - baseAmount;
    let cgst = gstAmount / 2;
    let sgst = gstAmount / 2;
    
    return {
        base: baseAmount.toFixed(2),
        gst: gstAmount.toFixed(2),
        cgst: cgst.toFixed(2),
        sgst: sgst.toFixed(2),
        total: parseFloat(mrp).toFixed(2)
    };
}

// Determine GST rate based on MRP
function getGSTRate(mrp) {
    return (mrp <= 2500) ? 5 : 18;
}
