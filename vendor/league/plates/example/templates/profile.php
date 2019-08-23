<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$this->layout('layout', ['title' => 'User Profile']);
?>

<h1>User Profile</h1>
<p>Hello, <?php 
echo $this->e($name);
?>!</p>

<?php 
$this->insert('sidebar');
?>

<?php 
$this->push('scripts');
?>
    <script>
        // Some JavaScript
    </script>
<?php 
$this->end();

?>