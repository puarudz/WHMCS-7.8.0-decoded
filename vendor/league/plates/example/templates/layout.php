<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

?>
<html>
<head>
    <title><?php 
echo $this->e($title);
?> | <?php 
echo $this->e($company);
?></title>
</head>
<body>

<?php 
echo $this->section('content');
?>

<?php 
echo $this->section('scripts');
?>

</body>
</html><?php 

?>