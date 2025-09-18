<?='<?php'?>


<?php include(BSROOT.'/HEADER');?>


<?php foreach($routeMap as $filePath) : ?>
require(__DIR__.'<?=$filePath;?>');
<?php endforeach; ?>