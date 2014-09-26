<div class="main-container">
    <h2><?=__('Icons conversion')?></h2>
    <div class="form-content">
        <div class="xhtml_form">
            <form action="<?= _url('runConversion', 'FontsConversion'); ?>" metdod="post" class="tao-scope">
                <p><?=__('In order to convert your icons please copy your source directory in taoDevTools and type its name here :')?></p>
                <p><span class="form-label"><?=__('Name')?></span><input id="src_directory" type="text" name="src_directory" /></p>
                <div class="form-toolbar">
                    <button class="btn btn-success" type="submit"><?=__('Convert')?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if(has_data('errorMessage')):?>
<script>
    require(['ui/feedback'], function(feedback){
        feedback().error("<?=get_data('message')?>");
    });
</script>
<?php else:?>
<div class="data-container-wrapper">
    <pre><?= $message; ?></pre>
</div>
<?php endif ?>