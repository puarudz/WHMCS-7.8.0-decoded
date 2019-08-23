-- Create the client snapshot data store table.
CREATE TABLE IF NOT EXISTS `mod_invoicedata` (
    `invoiceid` INT( 10)NOT NULL ,
    `clientsdetails` TEXT NOT NULL COLLATE utf8_unicode_ci,
    `customfields` TEXT NOT NULL COLLATE utf8_unicode_ci
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
