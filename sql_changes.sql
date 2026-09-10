ALTER TABLE `categories` ADD `gst_rate` DECIMAL(18,2) NOT NULL DEFAULT '0' AFTER `created_at`, ADD `is_split` INT(11) NOT NULL DEFAULT '0' AFTER `gst_rate`;


ALTER TABLE parties
MODIFY mobile VARCHAR(15) NULL;

ALTER TABLE `invoices`
    ADD `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT '0.00' AFTER `discount_amount`;

ALTER TABLE `categories`
    ADD `hsn_code` VARCHAR(20) NULL AFTER `category_name`;

ALTER TABLE `invoices`
    MODIFY `payment_mode` ENUM('Cash', 'Card', 'UPI', 'Exchange') DEFAULT 'Cash';


ALTER TABLE `invoices` CHANGE `payment_mode` `payment_mode` ENUM('Cash','Card','UPI','Split') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Cash';

ALTER TABLE `invoices` CHANGE `payment_mode` `payment_mode` ENUM('Cash','Card','UPI','Split','Credit Note') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Cash';

-- Applications allow an exchange credit note to be spent over multiple invoices.
CREATE TABLE IF NOT EXISTS credit_note_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    credit_note_id INT NOT NULL,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (credit_note_id) REFERENCES credit_notes(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY uq_credit_note_invoice (credit_note_id, invoice_id),
    INDEX idx_credit_application_note (credit_note_id),
    INDEX idx_credit_application_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

