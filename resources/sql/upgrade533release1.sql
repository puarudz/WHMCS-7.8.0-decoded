DELETE FROM tbltickettags WHERE ticketid NOT IN (SELECT id FROM tbltickets);

DELETE FROM tblticketnotes WHERE ticketid NOT IN (SELECT id FROM tbltickets);

DELETE FROM tblticketlog WHERE tid NOT IN (SELECT id FROM tbltickets);

DELETE FROM tblcustomfieldsvalues WHERE fieldid IN (SELECT id FROM tblcustomfields WHERE type='client') AND relid NOT IN (SELECT id FROM tblclients);

DELETE FROM tblcustomfieldsvalues WHERE fieldid IN (SELECT id FROM tblcustomfields WHERE type='product') AND relid NOT IN (SELECT id FROM tblhosting);

DELETE FROM tblcustomfieldsvalues WHERE fieldid IN (SELECT id FROM tblcustomfields WHERE type='support') AND relid NOT IN (SELECT id FROM tbltickets);

UPDATE tbladdonmodules SET module='newtlds' WHERE module='enomnewtlds';
