<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

?>
<div class="frames-container <?php 
echo $active_frames_tab == 'application' ? 'frames-container-application' : '';
?>">
  <?php 
$tpl->render($frame_list);
?>
</div><?php 

?>