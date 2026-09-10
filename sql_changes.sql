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
