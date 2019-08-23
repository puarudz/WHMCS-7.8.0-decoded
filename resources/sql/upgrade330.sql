ALTER TABLE `tblproducts` ADD `order` INT( 1 ) NOT NULL ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('LateFeeType', 'Percentage');

CREATE TABLE IF NOT EXISTS `tblnotes` (
`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`userid` INT( 10 ) UNSIGNED ZEROFILL NOT NULL ,
`adminid` INT( 1 ) NOT NULL ,
`created` DATETIME NOT NULL ,
`modified` DATETIME NOT NULL ,
`note` TEXT COLLATE utf8_unicode_ci NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tblproductconfigoptionssub` ADD `setup` DECIMAL( 10, 2 ) NOT NULL AFTER `optionname` ;

ALTER TABLE `tblemailtemplates` ADD `copyto` TEXT NOT NULL ;
ALTER TABLE `tblemailtemplates` ADD `plaintext` INT( 1 ) NOT NULL ;

ALTER TABLE `tblinvoices` ADD `invoicenum` TEXT NOT NULL AFTER `id` ;

ALTER TABLE `tbldownloads` ADD `productdownload` TEXT NOT NULL ;
ALTER TABLE `tblproducts` ADD `downloads` TEXT NOT NULL AFTER `affiliatepayamount` ;

UPDATE tblconfiguration SET setting='SendFirstOverdueInvoiceReminder' WHERE setting='SendOverdueInvoiceReminders';
INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('SendSecondOverdueInvoiceReminder', '0');
INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('SendThirdOverdueInvoiceReminder', '0');

UPDATE tblemailtemplates SET name='First Invoice Overdue Notice' WHERE name='Invoice Overdue Notice';
UPDATE tblemailtemplates SET subject='First Invoice Overdue Notice' WHERE subject='Invoice Overdue Notice';

INSERT INTO `tblemailtemplates` (type,name,subject,message) VALUES ( 'invoice', 'Second Invoice Overdue Notice', 'Second Invoice Overdue Notice', '<p> Dear [CustomerName], </p> <p> This is the second billing notice that your invoice no. [InvoiceNo] which was generated on [InvoiceDate] is now overdue. </p> <p> Your payment method is: [PaymentMethod] </p> <p> Invoice: [InvoiceNo]<br /> Balance Due: [Balance]<br /> Due Date: [DueDate] </p> <p> You can login to your client area to view and pay the invoice at [InvoiceLink] </p> <p> Your login details are as follows: </p> <p> Email Address: [CustomerEmail]<br /> Password: [CAPassword] </p> <p> [Signature] </p>');
INSERT INTO `tblemailtemplates` (type,name,subject,message) VALUES ( 'invoice', 'Third Invoice Overdue Notice', 'Third Invoice Overdue Notice', '<p> Dear [CustomerName], </p> <p> This is the third and final billing notice that your invoice no. [InvoiceNo] which was generated on [InvoiceDate] is now overdue. Failure to make payment will result in account suspension.</p> <p> Your payment method is: [PaymentMethod] </p> <p> Invoice: [InvoiceNo]<br /> Balance Due: [Balance]<br /> Due Date: [DueDate] </p> <p> You can login to your client area to view and pay the invoice at [InvoiceLink] </p> <p> Your login details are as follows: </p> <p> Email Address: [CustomerEmail]<br /> Password: [CAPassword] </p> <p> [Signature] </p>');
