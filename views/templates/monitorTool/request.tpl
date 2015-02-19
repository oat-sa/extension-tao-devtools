<div class="grid-row">
    <div class="col-12">
        <h1>Request</h1>
    </div>
</div>
<?php

/** @var $request RequestChunk */
$request = get_data('request');
$adapter = get_data('adapter');
use oat\tao\helpers\Template;
?>
<div class="grid-row">
    <div class="col-12">
        <div class="container">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        Request : <?php echo $request->getUrl() ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="panel panel-default">
                        <?php foreach($request->getClasses() as $className => $class) : ?>
                        <div class="panel-heading">
                            <h3 class="panel-title"><?= $className ?></h3>
                        </div>
                        <div class="panel-body">
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active"><a href="#instances" role="tab" data-toggle="tab">By instances :</a></li>
                                <li role="presentation"><a href="#methods" role="tab" data-toggle="tab">By Methods :</a></li>
                            </ul>
                        </div>
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="instances">
                                <?php Template::inc('monitorTool/class.tpl', null, array('class' => $class, 'adapter' => $adapter)); ?>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="methods">
                                <?php Template::inc('monitorTool/methods.tpl', null, array('methods' => $class->getMethods(), 'adapter' => $adapter)); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
