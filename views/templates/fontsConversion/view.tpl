<?php if(has_data('file_upload')):?>
<div class="main-container">
    <h2><?=__('File upload')?></h2>
    <div class="form-content">
        <div class="xhtml_form tao-scope">
            <div id="upload-container" data-url="<?=_url('fileUpload','FontsConversion');?>"></div>

        </div>
    </div>
</div>
<?php endif ?>

<div class="data-container-wrapper"></div>