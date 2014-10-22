<div class="main-container">
    <h2><?=__('Update Tao Icon Font')?></h2>
    <ol>
        <li><?= __('For instructions, please follow the tutorial at')?><a href="http://style.taotesting.com/icon-listing/" target="_blank">The Tao Style Guide</a></li>
        <li><?= __('Download the latest version of')?>  <a href="<?=_url('downloadCurrentSelection','FontsConversion');?>" target="dwl">selection.json</a></li>

    </ol>
    <div class="form-content">
        <div class="xhtml_form tao-scope">
            <div id="upload-container" data-url="<?=_url('fileUpload','FontsConversion');?>"></div>
        </div>
    </div>
</div>


<iframe id="dwl">

</iframe>

<?php if(has_data('warning')): ?>
    <script>
        require(['ui/feedback'], function(feedback){
            feedback().warning("<?= get_data('warning')?>");
        });
    </script>
<?php endif;?>
