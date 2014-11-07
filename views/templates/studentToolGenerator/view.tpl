<header class="section-header flex-container-full">
    <h2><?=__('Generate a skeleton for a student tool')?></h2>
</header>
<div class="main-container flex-container-main-form">
    <div class="form-content">
        <div class="xhtml_form">
            <form method="post" id="sts-form" name="sts-form" action="<?= _url('run')?>">
                <div>
                    <label class="form_desc" for="sts-client"><?=__('Client')?></label><select name="client" id="sts-client">
                        <option value="PARCC"><?=__('PARCC')?></option>
                        <option value="OAT"><?=__('OAT')?></option>
                    </select>
                </div>
                <div>
                    <label class="form_desc" for="sts-title"><?=__('Tool name')?></label><input type="text" name="title" id="sts-title">
                </div>
                <div>
                    <label class="form_desc" for="sts-transparent"><?=__('Transparent background')?></label><select name="transparent" id="sts-transparent">
                        <option value="0"><?=__('No')?></option>
                        <option value="1"><?=__('Yes')?></option>
                    </select>
                </div>
                <div>
                    <label class="form_desc" for="sts-rotatable"><?=__('Rotatable')?></label><select name="rotatable" id="sts-rotatable">
                        <option value="0"><?=__('No')?></option>
                        <option value="1" selected><?=__('Yes')?></option>
                    </select>
                </div>
                <div>
                    <label class="form_desc" for="sts-movable"><?=__('Movable')?></label><select name="movable" id="sts-movable">
                        <option value="0"><?=__('No')?></option>
                        <option value="1" selected><?=__('Yes')?></option>
                    </select>
                </div>
                <div>
                    <label class="form_desc" for="sts-adjustx"><?=__('Adjustable on x-axis')?></label><select name="adjustx" id="sts-adjustx">
                        <option value="0"><?=__('No')?></option>
                        <option value="1" selected><?=__('Yes')?></option>
                    </select>
                </div>
                <div>
                    <label class="form_desc" for="sts-adjusty"><?=__('Adjustable on y-axis')?></label><select name="adjusty" id="sts-adjusty">
                        <option value="0"><?=__('No')?></option>
                        <option value="1" selected><?=__('Yes')?></option>
                    </select>
                </div>
                <div class="form-toolbar">
                    <a href="#" class="form-submitter btn-success small"><span class="icon-save"></span><?=__('Generate')?></a>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="data-container-wrapper flex-container-remaining">
</div>

