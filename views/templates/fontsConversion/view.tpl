<?php if(isset($message)): ?>
<div id="message">
	<pre><?= $message; ?></pre>
</div>
<?php endif; ?>
<div>
    <form action="<?= _url('runConversion', 'FontsConversion'); ?>" metdod="post" class="tao-scope">
        <p><?=__('In order to convert your icons please copy your source directory in taoDevTools and type its name here :')?></p>
        <p><span class="form-label"><?=__('Name')?></span><input id="src_directory" type="text" name="src_directory" /></p>
        <button type="submit"><?=__('Convert')?></button>
    </form>
</div>
