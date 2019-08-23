UPDATE `tblconfiguration` SET value='' WHERE setting='License';
UPDATE `tbladminroles` SET widgets = CONCAT(widgets,',supporttickets_overview');
