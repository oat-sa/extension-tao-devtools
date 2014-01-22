<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 * 
 */


/**
 * default action
 * must be in the actions folder
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package tao
 * @subpackage actions
 *
 */
class taoDevTools_actions_ExtensionsManager extends tao_actions_ExtensionsManager {

	/**
	 * Index page
	 */
	public function index() {

		$extensionManager = common_ext_ExtensionsManager::singleton();
		$all = array();
		$installed = array();
		foreach ($extensionManager->getInstalledExtensions() as $ext) {
		    $all[] = $ext;
		    $installed[] = $ext->getID();
		}
		foreach ($extensionManager->getAvailableExtensions() as $ext) {
		    $all[] = $ext;
		}
		usort($all, function($a, $b) { return strcasecmp($a->getID(),$b->getID());});
		$this->setData('extensions',$all);
		$this->setData('installedIds',$installed);
		$this->setView('extensionManager/view.tpl');

	}

}