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
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 * 
 */

/**
 * This container initialize the settings form.
 *
 * @access public
 * @author Joel Bout, <joel.bout@tudor.lu>
 * @package tao
 * @subpackage actions_form
 */
class taoDevTools_views_form_UserDebugRoles
    extends tao_helpers_form_FormContainer
{
    // --- ASSOCIATIONS ---


    // --- ATTRIBUTES ---

    // --- OPERATIONS ---

    /**
     * Short description of method initForm
     *
     * @access protected
     * @author Joel Bout, <joel.bout@tudor.lu>
     * @return mixed
     */
    protected function initForm()
    {
		$this->form = tao_helpers_form_FormFactory::getForm('roleDebug');
		
		$action = tao_helpers_form_FormFactory::getElement('save', 'Free');
		$action->setValue("<a href='#' class='form-submiter' ><img src='".TAOBASE_WWW."/img/wf_ico.png' /> ".__('Restrict')."</a>");
		
		$this->form->setActions(array(), 'top');
		$this->form->setActions(array($action), 'bottom');
    }

    /**
     * Short description of method initElements
     *
     * @access protected
     * @author Joel Bout, <joel.bout@tudor.lu>
     * @return mixed
     */
    protected function initElements()
    {
        $userElement = tao_helpers_form_FormFactory::getElement('user', 'Hidden');
        $userElement->setValue(common_session_SessionManager::getSession()->getUserUri());
        $this->form->addElement($userElement);
        
        $roleOptions = array();
        foreach(common_session_SessionManager::getSession()->getUserRoles() as $role){
            $roleResource = new core_kernel_classes_Resource($role);
			$roleOptions[$role] = $roleResource->getLabel();
		}
        
        $roleElement = tao_helpers_form_FormFactory::getElement('rolefilter', 'Checkbox');
        $roleElement->setDescription(__('Keep roles'));
        $roleElement->setOptions($roleOptions);
        $roleElement->setValues(array_keys($roleOptions));

        $this->form->addElement($roleElement);
    }

}