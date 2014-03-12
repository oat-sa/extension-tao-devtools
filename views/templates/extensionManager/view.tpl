<link rel="stylesheet" type="text/css" href="<?= BASE_WWW ?>css/extensionManager.css" />

<? if(isset($message)): ?>
<div id="message">
	<pre><?= $message; ?></pre>
</div>
<? endif; ?>
<div class="actions tao-scope">
  <button class="btn-success" type="button" id="installButton" disabled="disabled"><span class="icon-tools"></span> <?= __('Apply') ?></button>
  <button class="btn-info" type="button" id="addButton"><span class="icon-add"></span> <?= __('Create new') ?></button>
</div>
<div id="available-extensions-title" class="ui-widget-header ui-corner-top ui-state-default">
	<?= __('Extensions') ?>
</div>
<div id="available-extensions-container" class="ui-widget-content ui-corner-bottom">
	<form action="<?= BASE_URL; ?>/ExtensionsManager/install" metdod="post">
		<table summary="modules" class="maximal">
			<thead>
				<tr>
					<th class="bordered install"></th>
				<th class="bordered"></th>
					<th class="bordered"><?= __('Description'); ?></th>
					<th class="bordered version"><?= __('Version'); ?></th>
					<th class="bordered"><?= __('License'); ?></th>
					<th class="require"><?= __('Requires'); ?></th>
				</tr>
			</thead>
			<tbody>
				<? foreach(get_data('extensions') as $k => $ext): ?>
				<tr id="<?= $ext->getId();?>">
					<td class="bordered install">
					   <?php if (!common_ext_ExtensionsManager::singleton()->isInstalled($ext->getId())) :?>
						<input name="ext_<?= $ext->getId();?>" type="checkbox" />
						<?php else:?>
						<span class="icon-checkbox-checked" style="color: #0E914B"></span>
						<?php endif;?>
					</td>
				<td class="ext-name bordered"><?= $ext->getId(); ?></td>
					<td class="bordered"><?= $ext->getName(); ?> (<?= $ext->getManifest()->getDescription(); ?>)</td>
					<td class="bordered version"><?= $ext->getVersion(); ?></td>
					<td class="bordered"><?= $ext->getManifest()->getLicense(); ?></td>
					<td class="dependencies ">
						<ul>
						<? foreach ($ext->getDependencies() as $req => $version): ?>
						  <?php if (!in_array($req, get_data('installedIds'))) : ?>
							<li class="ext-id ext-<?= $req ?>" rel="<?= $req ?>"><?= $req ?></li>
							<?php endif;?>
						<? endforeach; ?>
						</ul>
					</td>
				</tr>
				<? endforeach; ?>
			</tbody>
		</table>
	</form>
</div>

<div id="installProgress" title="<?= __('Installation...') ?>">
	<div class="progress"><div class="bar"></div></div>
	<p class="status">...</p>
	<div class="console"></div>
</div>