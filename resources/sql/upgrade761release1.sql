-- Update Indian state name from Orissa to Odisha
update `tblclients` set `state`='Odisha' where `state`='Orissa';
update `tblcontacts` set `state`='Odisha' where `state`='Orissa';
update `tblquotes` set `state`='Odisha' where `state`='Orissa';
update `tbltax` set `state`='Odisha' where `state`='Orissa';
