ALTER TABLE `categories` ADD `gst_rate` DECIMAL(18,2) NOT NULL DEFAULT '0' AFTER `created_at`, ADD `is_split` INT(11) NOT NULL DEFAULT '0' AFTER `gst_rate`;


ALTER TABLE parties
MODIFY mobile VARCHAR(15) NULL;

ALTER TABLE `invoices`
    ADD `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT '0.00' AFTER `discount_amount`;