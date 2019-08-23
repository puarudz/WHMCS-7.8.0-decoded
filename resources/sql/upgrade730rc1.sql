-- Change `data` from TEXT to MEDIUMTEXT to allow storing larger datasets
ALTER TABLE `tbltransientdata` MODIFY COLUMN `data` MEDIUMTEXT NOT NULL;
