# INSTALLATION GUIDE - Payal Arban Stichis
## Step-by-Step Installation for Windows (XAMPP)

### Prerequisites
- Download XAMPP (PHP 8.2): https://www.apachefriends.org/download.html
- Text Editor (VS Code, Sublime, Notepad++)

---

## STEP 1: Install XAMPP
1. Download XAMPP with PHP 8.2 or higher
2. Install to `C:\xampp` (default location)
3. During installation, select:
   ☑ Apache
   ☑ MySQL
   ☑ PHP
   ☑ phpMyAdmin

---

## STEP 2: Extract Project Files
1. Extract the `payal_arban_stichis` folder
2. Copy to: `C:\xampp\htdocs\`
3. Final path should be: `C:\xampp\htdocs\payal_arban_stichis\`

---

## STEP 3: Start XAMPP
1. Open **XAMPP Control Panel**
2. Start **Apache** (click Start button)
3. Start **MySQL** (click Start button)
4. Both should show **green** status

---

## STEP 4: Create Database
1. Open browser and go to: **http://localhost/phpmyadmin**
2. Click **"New"** in left sidebar
3. Database name: `payal_arban_stichis`
4. Click **"Create"**
5. Select the newly created database
6. Click **"Import"** tab at top
7. Click **"Choose File"**
8. Navigate to: `C:\xampp\htdocs\payal_arban_stichis\database.sql`
9. Click **"Go"** button at bottom
10. Wait for success message: **"Import has been successfully finished"**

---

## STEP 5: Verify Database
1. In phpMyAdmin, select `payal_arban_stichis` database
2. You should see these tables:
   - users
   - sizes
   - products
   - product_stock
   - stock_transactions
   - parties
   - invoices
   - invoice_items
   - credit_notes
   - credit_note_items
   - settings

---

## STEP 6: Configure Database Connection (Optional)
**Only if you changed MySQL password:**
1. Open: `C:\xampp\htdocs\payal_arban_stichis\config\database.php`
2. Update these lines:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Enter your MySQL password here
define('DB_NAME', 'payal_arban_stichis');
```
3. Save the file

---

## STEP 7: Access the System
1. Open browser (Chrome, Firefox, Edge)
2. Go to: **http://localhost/payal_arban_stichis**
3. You'll be redirected to login page

---

## STEP 8: Login
**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

Click **"Login"** button

---

## STEP 9: Change Password (Recommended)
1. After login, click on your name (top-right)
2. Click **"Change Password"**
3. Enter current password: `admin123`
4. Enter new password (minimum 6 characters)
5. Click **"Update Password"**

---

## STEP 10: Test the System
1. Go to **Masters → Size Master**
2. You should see default sizes (XS, S, M, L, XL, XXL, XXXL)
3. Go to **Dashboard**
4. You should see statistics cards

---

## Optional: Install Barcode Library
This step is **optional** but recommended for barcode sticker generation.

### Method 1: Using Composer (Recommended)
1. Download Composer: https://getcomposer.org/download/
2. Install Composer for Windows
3. Open Command Prompt
4. Navigate to project:
   ```
   cd C:\xampp\htdocs\payal_arban_stichis
   ```
5. Run:
   ```
   composer require picqer/php-barcode-generator
   ```

### Method 2: Manual Installation
1. Download library: https://github.com/picqer/php-barcode-generator
2. Extract to: `C:\xampp\htdocs\payal_arban_stichis\vendor\`
3. Include in sticker.php file

---

## Troubleshooting

### Problem 1: Apache won't start
**Solution:**
- Port 80 might be in use
- Close Skype or IIS
- OR change Apache port in XAMPP

### Problem 2: MySQL won't start
**Solution:**
- Port 3306 might be in use
- Stop other MySQL services
- Check Task Manager

### Problem 3: Blank page after login
**Solution:**
- Check if database.sql imported correctly
- Check browser console for errors (F12)
- Check PHP error logs in `C:\xampp\apache\logs\error.log`

### Problem 4: Cannot connect to database
**Solution:**
- Verify MySQL is running in XAMPP
- Check credentials in `config/database.php`
- Test connection in phpMyAdmin

### Problem 5: Permission errors
**Solution (Windows):**
- Right-click `payal_arban_stichis` folder
- Properties → Security → Edit
- Give full control to Users

---

## Quick Start Workflow

### 1. Add Your First Product
1. Go to **Masters → Product Master**
2. Click **"Add New Product"**
3. Enter product details:
   - Product Name: "Blue Cotton Shirt"
   - MRP: 1500 (including GST)
   - Select sizes and quantities
4. Click **"Save Product"**

### 2. Add Stock
1. Go to **Masters → Add Stock**
2. Select product
3. Select size
4. Enter quantity to add
5. Add notes (optional)
6. Click **"Add Stock"**

### 3. Create Invoice
1. Click **"Create Invoice"** from menu
2. Enter customer mobile number
3. If new customer, fill name and address
4. Search product by code
5. Select size and quantity
6. Apply discount if any
7. Select payment mode
8. Click **"Generate Invoice"**
9. Print invoice (A5 format)

### 4. Print Stickers
1. Go to **"Print Stickers"**
2. Select product
3. Select sizes
4. Enter quantity for each size
5. Click **"Generate Stickers"**
6. Print on label sheet

### 5. View Reports
1. **GST Report:**
   - Reports → GST Report
   - Select date range
   - View CGST/SGST breakup
   
2. **Stock Report:**
   - Reports → Stock Report
   - View current stock
   - Check low stock items

---

## System URLs

| Module | URL |
|--------|-----|
| Login | http://localhost/payal_arban_stichis/login.php |
| Dashboard | http://localhost/payal_arban_stichis/dashboard.php |
| Size Master | http://localhost/payal_arban_stichis/modules/sizes/list.php |
| Product Master | http://localhost/payal_arban_stichis/modules/products/list.php |
| Add Stock | http://localhost/payal_arban_stichis/modules/stock/add_stock.php |
| Create Invoice | http://localhost/payal_arban_stichis/modules/invoice/create_invoice.php |
| Credit Note | http://localhost/payal_arban_stichis/modules/credit_note/create_credit_note.php |
| Print Stickers | http://localhost/payal_arban_stichis/modules/products/sticker.php |
| GST Report | http://localhost/payal_arban_stichis/reports/gst_report.php |
| Stock Report | http://localhost/payal_arban_stichis/reports/stock_report.php |

---

## Default Settings

| Setting | Value |
|---------|-------|
| Admin Username | admin |
| Admin Password | admin123 |
| Invoice Prefix | INV |
| Credit Note Prefix | CN |
| Product Code Prefix | PY |
| GST Rate (≤₹2500) | 5% |
| GST Rate (>₹2500) | 18% |
| Return Period | 30 days |

---

## Important Notes

1. **Backup Database Regularly:**
   - phpMyAdmin → Select database → Export
   - Save .sql file to safe location

2. **Security:**
   - Change default admin password immediately
   - Don't expose system to internet without firewall
   - Keep PHP and MySQL updated

3. **Performance:**
   - Clear browser cache if facing issues
   - Restart Apache/MySQL if system slows down

4. **Support:**
   - Keep this guide for reference
   - Check README.md for detailed documentation

---

## Next Steps After Installation

1. ✅ Change admin password
2. ✅ Add your products
3. ✅ Add initial stock
4. ✅ Create test invoice
5. ✅ Print test sticker
6. ✅ Generate sample report

---

## Contact & Support

For technical support or queries:
- Check documentation in README.md
- Review database.sql for structure
- Test with sample data first

**Congratulations! Your Payal Arban Stichis system is ready to use!** 🎉

---

*Installation Guide Version 1.0*  
*Last Updated: May 2026*
