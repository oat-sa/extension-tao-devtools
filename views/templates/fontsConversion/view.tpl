<div class="main-container">
    <h2><?=__('File upload')?></h2>
    <div class="form-content">
        <div class="xhtml_form tao-scope">
            <a href="<?=_url('downloadCurrentSelection','FontsConversion');?>" class="btn-success small" target="_blank"><span class="icon-download"></span>Download Selection.json</a>
            <div id="upload-container" data-url="<?=_url('fileUpload','FontsConversion');?>"></div>
        </div>
    </div>
</div>

<div class="data-container-wrapper"></div>

<?php if(has_data('warning')): ?>
    <script>
        require(['ui/feedback'], function(feedback){
            feedback().warning("<?= get_data('warning')?>");
        });
    </script>
<?php endif;?>
