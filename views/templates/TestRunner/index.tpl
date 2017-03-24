<?php
use oat\tao\helpers\Template;
use oat\taoDelivery\helper\Delivery;

$deliveries = get_data('deliveries');
?>
<div class="container <?= get_data('scope'); ?>" <?php foreach(get_data('data') as $name => $value): ?>
data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>

    <div class="toolbar"></div>
    <div class="content listing">
        <h1><?= __("My Tests"); ?></h1>

        <div class="permanent-feedback"></div>

        <?php if (count($deliveries) > 0) : ?>
        <h2 class="info">
            <?= __("In progress") ?>: <?= count($deliveries); ?>
        </h2>

        <ul class="entry-point-box plain">
            <?php foreach ($deliveries as $delivery): ?>
            <li>
                <a class="block entry-point entry-point-started-deliveries" href="<?= $delivery[Delivery::LAUNCH_URL] ?>">
                    <h3><?= _dh($delivery[Delivery::LABEL]) ?></h3>

                    <?php foreach ($delivery[Delivery::DESCRIPTION] as $desc) : ?>
                    <p><?= $desc?></p>
                    <?php endforeach; ?>

                    <div class="clearfix">
                        <span class="text-link" href="<?= $delivery[Delivery::LAUNCH_URL] ?>"><span class="icon-clock"></span> <?= __("Watch") ?> </span>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
