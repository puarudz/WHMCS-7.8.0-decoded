<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
* Layout template file for Whoops's pretty error output.
*/
?>
<!DOCTYPE html><?php 
echo $preface;
?>
<html>
  <head>
    <meta charset="utf-8">
    <title><?php 
echo $tpl->escape($page_title);
?></title>

    <style><?php 
echo $stylesheet;
?></style>
  </head>
  <body>

    <div class="Whoops container">
      <div class="stack-container">

        <?php 
$tpl->render($panel_left_outer);
?>

        <?php 
$tpl->render($panel_details_outer);
?>

      </div>
    </div>

    <script><?php 
echo $zepto;
?></script>
    <script><?php 
echo $clipboard;
?></script>
    <script><?php 
echo $javascript;
?></script>
  </body>
</html>
<?php 

?>