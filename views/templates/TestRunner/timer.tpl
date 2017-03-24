<div class="container <?= get_data('scope'); ?>" <?php foreach(get_data('data') as $name => $value): ?>
data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>
<div class="toolbar"></div>
<h1 class="title"><?= get_data('title'); ?></h1>
<div class="content table"></div>
</div>
