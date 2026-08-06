# COMPLETE FILE LIST - Payal Arban Stichis

## Files Created (✅ Complete)
1. ✅ config/database.php - Database connection
2. ✅ config/session.php - Session management
3. ✅ config/functions.php - Helper functions
4. ✅ includes/header.php - Common header
5. ✅ includes/footer.php - Common footer
6. ✅ assets/css/style.css - Custom styles
7. ✅ assets/js/script.js - Custom JavaScript
8. ✅ database.sql - Complete database schema
9. ✅ login.php - Admin login
10. ✅ logout.php - Logout handler
11. ✅ dashboard.php - Main dashboard
12. ✅ index.php - Root redirect
13. ✅ modules/sizes/list.php - Size master list
14. ✅ modules/sizes/add.php - Add size
15. ✅ modules/sizes/edit.php - Edit size
16. ✅ README.md - Project documentation
17. ✅ INSTALLATION_GUIDE.md - Installation steps

## Files To Be Created (Remaining Core Modules)

### Product Master Module
1. modules/products/list.php - Product list with search/filter
2. modules/products/add.php - Add product with size-wise qty
3. modules/products/edit.php - Edit product
4. modules/products/view.php - View product details
5. modules/products/sticker.php - Generate barcode stickers

### Stock Module
6. modules/stock/add_stock.php - Add stock interface
7. modules/stock/stock_history.php - View stock transactions

### Invoice Module
8. modules/invoice/create_invoice.php - Create invoice (main billing)
9. modules/invoice/list.php - Invoice list
10. modules/invoice/view.php - View/print invoice (A5)
11. modules/invoice/print.php - Print-only invoice page

### Credit Note Module
12. modules/credit_note/create_credit_note.php - Create credit note
13. modules/credit_note/list.php - Credit note list
14. modules/credit_note/view.php - View credit note

### Party Module (Customers)
15. modules/party/ajax_search.php - AJAX search by mobile
16. modules/party/get_details.php - Get party details API

### Profile Module
17. modules/profile/change_password.php - Change password

### Reports
18. reports/gst_report.php - GST report with CGST/SGST
19. reports/stock_report.php - Current stock report
20. reports/sales_report.php - Sales summary report

### API/AJAX Files
21. api/get_product.php - Get product by code (AJAX)
22. api/get_stock.php - Get available stock (AJAX)
23. api/calculate_gst.php - Calculate GST breakdown (AJAX)

---

## DETAILED CODE STRUCTURE FOR REMAINING FILES

### 1. modules/products/add.php (Product Master)
**Purpose:** Add new product with auto-generated code and size-wise quantities

**Key Features:**
- Auto-generate product code (PY000001)
- Product name, MRP input
- Display all sizes from size master
- Input quantity for each size
- Auto-determine GST rate based on MRP
- Calculate base amount, CGST, SGST

**Form Fields:**
- Product Code (auto, readonly)
- Product Name (text, required)
- MRP (number, required, with GST)
- For each size: Quantity (number, default 0)
- Active status (checkbox)

**Process:**
1. Generate next product code
2. Validate MRP > 0
3. Determine GST rate (≤2500 = 5%, >2500 = 18%)
4. Insert into products table
5. Insert size-wise quantities into product_stock table
6. Record stock transaction for each size
7. Redirect to product list

---

### 2. modules/invoice/create_invoice.php (Billing)
**Purpose:** Main billing interface with party search and product selection

**Layout Sections:**
- **Section 1:** Party Details
  - Mobile number input with search button
  - If found: Auto-fill name, address
  - If not found: Show name, mobile, address, notes fields
  
- **Section 2:** Product Selection
  - Product code input (with autocomplete)
  - On select: Show product name, MRP, available sizes
  - Size dropdown (only sizes with stock > 0)
  - Quantity input (validate against stock)
  - Discount input (per item or bill level)
  - Add to cart button

- **Section 3:** Cart Table
  - Columns: Product, Size, Qty, MRP, Discount, Base, CGST, SGST, Total
  - Remove button for each item
  - Show totals at bottom

- **Section 4:** Payment
  - Subtotal (readonly)
  - Total Discount (readonly)
  - Total CGST (readonly)
  - Total SGST (readonly)
  - Grand Total (readonly)
  - Payment Mode (Cash/Card/UPI)
  - Notes (textarea)
  - Generate Invoice button

**JavaScript:**
- AJAX search party by mobile
- AJAX get product details by code
- Calculate line totals with GST
- Update cart totals on add/remove
- Validate stock before adding

**Process:**
1. Search/Create party
2. Add items to session cart
3. Calculate GST on discounted amount
4. On submit:
   - Insert into invoices table
   - Insert items into invoice_items table
   - Reduce stock in product_stock table
   - Record stock transactions (OUT)
   - Generate invoice number
   - Redirect to view/print invoice

---

### 3. modules/products/sticker.php (Barcode Stickers)
**Purpose:** Generate printable barcode stickers for products

**Interface:**
- Select product (dropdown)
- Show all sizes for selected product
- For each size: Quantity input (how many stickers)
- Generate button

**Sticker Format (2" x 1.5"):**
```
+---------------------------+
|      PY000001            |
|        XL                |
|    MRP: ₹1500.00         |
|  |||||||||||||||||||     | (Barcode)
+---------------------------+
```

**Libraries:**
- Use picqer/php-barcode-generator
- Or use online barcode API
- Format: CODE128 or CODE39

**Print CSS:**
- Set page to landscape
- Multiple stickers per row
- Print-friendly styles
- Page break control

---

### 4. modules/credit_note/create_credit_note.php (Returns)
**Purpose:** Process returns and exchanges

**Interface:**
- **Section 1:** Select Invoice
  - Invoice number search
  - Load invoice details and items
  
- **Section 2:** Return Items
  - Show all invoice items
  - Checkbox to select items for return
  - Quantity input (can be partial)
  - Show: Product, Size, Original Qty, Return Qty, Amount

- **Section 3:** Exchange (Optional)
  - Checkbox: "Exchange with new product"
  - If checked: Show product selection interface
  - Select new product, size, quantity
  - Calculate price difference

- **Section 4:** Summary
  - Return Amount (auto-calculated)
  - Exchange Amount (if applicable)
  - Balance to refund/pay
  - Refund Mode (Cash/Card/UPI/Exchange)
  - Notes
  - Generate Credit Note button

**Process:**
1. Validate return within 30 days
2. Check quantities don't exceed original
3. Insert into credit_notes table
4. Insert items into credit_note_items table
5. Increase stock (restore returned items)
6. Record stock transactions (RETURN)
7. If exchange: Create new invoice for new items
8. Generate credit note number
9. Redirect to view/print credit note

---

### 5. reports/gst_report.php (GST Report)
**Purpose:** Generate GST summary for filing

**Filters:**
- From Date
- To Date
- View button

**Report Layout:**
```
GST SUMMARY REPORT
Period: 01-05-2026 to 31-05-2026

Sales Summary:
- Total Invoices: 150
- Total Sales: ₹2,50,000.00
- Total Discount: ₹5,000.00
- Taxable Amount: ₹2,45,000.00

Tax Breakup:
╔════════════════════╦════════════╦═══════════╦═══════════╦════════════╗
║ GST Rate          ║ Taxable    ║ CGST      ║ SGST      ║ Total Tax  ║
╠════════════════════╬════════════╬═══════════╬═══════════╬════════════╣
║ 5%                ║ ₹1,50,000  ║ ₹3,750    ║ ₹3,750    ║ ₹7,500     ║
║ 18%               ║ ₹95,000    ║ ₹8,550    ║ ₹8,550    ║ ₹17,100    ║
╠════════════════════╬════════════╬═══════════╬═══════════╬════════════╣
║ TOTAL             ║ ₹2,45,000  ║ ₹12,300   ║ ₹12,300   ║ ₹24,600    ║
╚════════════════════╩════════════╩═══════════╩═══════════╩════════════╝

Credit Notes: -₹5,000.00
Tax on Returns: -₹500.00

Net Tax Liability: ₹24,100.00
```

**Export Options:**
- Print
- Export to Excel
- Export to PDF

---

### 6. reports/stock_report.php (Stock Report)
**Purpose:** Current stock summary

**Filters:**
- Search by product name/code
- Filter by size (optional)
- Low stock alert (< 5 pcs)

**Report Layout:**
```
STOCK REPORT
As on: 12-05-2026

╔══════════╦════════════════════╦══════╦═══════╦═════════╦═════════════╗
║ Code     ║ Product Name       ║ Size ║ Qty   ║ MRP     ║ Stock Value ║
╠══════════╬════════════════════╬══════╬═══════╬═════════╬═════════════╣
║ PY000001 ║ Blue Cotton Shirt  ║ M    ║ 15    ║ 1,500   ║ 22,500      ║
║ PY000001 ║ Blue Cotton Shirt  ║ L    ║ 20    ║ 1,500   ║ 30,000      ║
║ PY000001 ║ Blue Cotton Shirt  ║ XL   ║ 10    ║ 1,500   ║ 15,000      ║
╠══════════╬════════════════════╬══════╬═══════╬═════════╬═════════════╣
║          ║                    ║      ║ 45    ║         ║ 67,500      ║
╚══════════╩════════════════════╩══════╩═══════╩═════════╩═════════════╝

Summary:
- Total Products: 25
- Total Pieces: 1,250
- Total Stock Value: ₹15,75,000
```

---

## DATABASE QUERIES REFERENCE

### Get Product with Stock
```sql
SELECT p.*, 
       ps.size_id, s.size_name, ps.quantity
FROM products p
LEFT JOIN product_stock ps ON p.id = ps.product_id
LEFT JOIN sizes s ON ps.size_id = s.id
WHERE p.product_code = ?
```

### Calculate GST Breakdown
```php
function calculateGST($mrp, $discount = 0) {
    $discounted = $mrp - $discount;
    $gstRate = ($mrp <= 2500) ? 5 : 18;
    
    $baseAmount = $discounted / (1 + ($gstRate / 100));
    $gstAmount = $discounted - $baseAmount;
    $cgst = $gstAmount / 2;
    $sgst = $gstAmount / 2;
    
    return [
        'base' => round($baseAmount, 2),
        'gst_rate' => $gstRate,
        'cgst' => round($cgst, 2),
        'sgst' => round($sgst, 2),
        'total' => round($discounted, 2)
    ];
}
```

### GST Report Query
```sql
SELECT 
    i.invoice_date,
    i.invoice_no,
    p.party_name,
    SUM(ii.base_amount) as taxable,
    ii.gst_rate,
    SUM(ii.cgst_amount) as cgst,
    SUM(ii.sgst_amount) as sgst,
    SUM(ii.total_amount) as total
FROM invoices i
INNER JOIN invoice_items ii ON i.id = ii.invoice_id
INNER JOIN parties p ON i.party_id = p.id
WHERE i.invoice_date BETWEEN ? AND ?
GROUP BY i.id, ii.gst_rate
ORDER BY i.invoice_date DESC
```

---

## IMPORTANT JAVASCRIPT FUNCTIONS

### Search Party by Mobile (AJAX)
```javascript
$('#mobile').on('blur', function() {
    let mobile = $(this).val();
    if (mobile.length === 10) {
        $.ajax({
            url: '/modules/party/ajax_search.php',
            method: 'POST',
            data: { mobile: mobile },
            success: function(data) {
                if (data.found) {
                    $('#party_name').val(data.name);
                    $('#address').val(data.address);
                    $('#notes').val(data.notes);
                }
            }
        });
    }
});
```

### Get Product Details (AJAX)
```javascript
$('#product_code').on('change', function() {
    let code = $(this).val();
    $.ajax({
        url: '/api/get_product.php',
        method: 'POST',
        data: { code: code },
        success: function(data) {
            $('#product_name').text(data.name);
            $('#mrp').text(data.mrp);
            // Populate size dropdown with stock
            let options = '<option value="">Select Size</option>';
            data.sizes.forEach(function(size) {
                if (size.quantity > 0) {
                    options += `<option value="${size.id}">${size.name} (${size.quantity} available)</option>`;
                }
            });
            $('#size_id').html(options);
        }
    });
});
```

---

## NEXT STEPS FOR COMPLETION

1. Create all remaining PHP files listed above
2. Test each module thoroughly
3. Generate sample invoices
4. Test credit note process
5. Verify GST calculations
6. Test barcode generation
7. Print test invoice (A5)
8. Generate reports with sample data

---

**Total Files:** 41 files (17 created, 24 remaining)  
**Estimated Completion Time:** 2-3 hours for experienced developer  
**Priority Files:** Invoice creation, Product master, Stock management
