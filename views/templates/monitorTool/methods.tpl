<div class="grid-row">
    <div class="col-12">
        <h1>Methods</h1>
    </div>
</div>
<?php
use oat\tao\helpers\Template;
$methods = get_data('methods');
$adapter = get_data('adapter');
?>

<?php foreach($methods as $methodName => $method) : ?>
    <?php Template::inc('monitorTool/method.tpl', null, array('method' => $method, 'adapter' => $adapter)); ?>
<?php endforeach; ?>

