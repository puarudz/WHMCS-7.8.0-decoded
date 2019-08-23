<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

?>
<div class="frames-description <?php 
echo $has_frames_tabs ? 'frames-description-application' : '';
?>">
  <?php 
if ($has_frames_tabs) {
    ?>
    <?php 
    if ($active_frames_tab == 'application') {
        ?>
      <a href="#" id="application-frames-tab" class="frames-tab frames-tab-active">
        Application frames (<?php 
        echo $frames->countIsApplication();
        ?>)
      </a>
    <?php 
    } else {
        ?>
      <span href="#" id="application-frames-tab" class="frames-tab">
        Application frames (<?php 
        echo $frames->countIsApplication();
        ?>)
      </span>
    <?php 
    }
    ?>
    <a href="#" id="all-frames-tab" class="frames-tab <?php 
    echo $active_frames_tab == 'all' ? 'frames-tab-active' : '';
    ?>">
      All frames (<?php 
    echo count($frames);
    ?>)
    </a>
  <?php 
} else {
    ?>
    <span>
        Stack frames (<?php 
    echo count($frames);
    ?>)
    </span>
  <?php 
}
?>
</div><?php 

?>