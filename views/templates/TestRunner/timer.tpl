<div class="container <?= get_data('scope'); ?>" <?php foreach(get_data('data') as $name => $value): ?>
data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>
<div class="toolbar"></div>
<div class="content table"></div>
</div>
