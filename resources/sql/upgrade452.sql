ALTER TABLE `tblsslorders` CHANGE `status` `status` TEXT NOT NULL;
UPDATE `tblsslorders` SET status='Awaiting Configuration' WHERE status='Incomplete';
ALTER TABLE `tblsslorders` ADD `configdata` TEXT NOT NULL AFTER `certtype`;
