<?php
?>
<div>
    <ul>
        <?php foreach (get_data('extensions') as $name => $controllers) :?>
        <li><?=$name?>
            <ul>
            <?php foreach ($controllers as $controller) :?>
                <li><?=$controller->getClassName()?>
                    <ul>
                        <?php foreach ($controller->getActions() as $action) :?>
                            <li><?=$action->getName()?></li>
                        <?php endforeach;?>
                    </ul>
                </li>
               <?php endforeach;?> 
            </ul>
        </li>
       <?php endforeach;?> 
   </ul>
</div>
<!-- 
<script type="text/javascript">
require(['jquery', 'helpers','jsTree'], function($, helpers){
console.log('loaded');
$(document).ready(function () {
    // Create jqxTree
    $('#jqxTree').tree().set_theme('custom');
});
});
</script>
 -->
