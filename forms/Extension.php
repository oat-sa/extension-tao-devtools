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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDevTools\forms;

/**
 * Create a form to add extensions
 *
 * @access public
 * @author Joel Bout <joel@taotesting.com>
 * @package taoDevTools
 */
class Extension
    extends \tao_helpers_form_FormContainer
{
    // --- ASSOCIATIONS ---


    // --- ATTRIBUTES ---

    // --- OPERATIONS ---

    /**
     * Initialize the form
     *
     * @access protected
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @return mixed
     */
    protected function initForm()
    {
        // section 127-0-1-1-56df1631:1284f2fd9c5:-8000:000000000000249F begin
        
    	(isset($this->options['name'])) ? $name = $this->options['name'] : $name = ''; 
    	if(empty($name)){
			$name = 'form_'.(count(self::$forms)+1);
		}
		unset($this->options['name']);
			
		$this->form = \tao_helpers_form_FormFactory::getForm($name, $this->options);
		
		//create action in toolbar
		$createElt = \tao_helpers_form_FormFactory::getElement('create', 'Free');
		$createElt->setValue('<button class="btn-info form-submiter" type="button" id="addButton"><span class="icon-add"></span>'.__('Create').'</button>');
		$this->form->setActions(array(), 'top');
		$this->form->setActions(array($createElt), 'bottom');
    	
        // section 127-0-1-1-56df1631:1284f2fd9c5:-8000:000000000000249F end
    }

    /**
     * Initialize the form elements
     *
     * @access protected
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @return mixed
     */
    protected function initElements()
    {
        // section 127-0-1-1-56df1631:1284f2fd9c5:-8000:00000000000024A1 begin
        
		$idElt = \tao_helpers_form_FormFactory::getElement('id', 'Textbox');
		$idElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
		$idElt->addValidator(\tao_helpers_form_FormFactory::getValidator('AlphaNum'));
		$this->form->addElement($idElt);

		$idElt = \tao_helpers_form_FormFactory::getElement('name', 'Textbox');
		$idElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
		$this->form->addElement($idElt);

		$verElt = \tao_helpers_form_FormFactory::getElement('version', 'Textbox');
		$verElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
		$this->form->addElement($verElt);
		
		$authorElt = \tao_helpers_form_FormFactory::getElement('author', 'Textbox');
		$authValid = \tao_helpers_form_FormFactory::getValidator('NotEmpty');
		$authorElt->addValidator($authValid);
		$authorElt->setValue('Open Assessment Technologies SA');
		$this->form->addElement($authorElt);
		
		$nsElt = \tao_helpers_form_FormFactory::getElement('authorNs', 'Textbox');
		$nsElt->setDescription('Author namespace');
		$nsElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
		$nsElt->addValidator(\tao_helpers_form_FormFactory::getValidator('AlphaNum'));
		$nsElt->setValue('oat');
		$this->form->addElement($nsElt);
		
		$licenseElt = \tao_helpers_form_FormFactory::getElement('license', 'Textbox');
		$licenseElt->setValue('GPL-2.0');
		$this->form->addElement($licenseElt);
		
		$descElt = \tao_helpers_form_FormFactory::getElement('description', 'Textarea');
		//$descElt->setValue(__('Use the * character to replace any string'));
		$this->form->addElement($descElt);
		
		$extIds = array();
		foreach (\common_ext_ExtensionsManager::singleton()->getInstalledExtensions() as $ext) {
		    $extIds[$ext->getId()] = $ext->getId();
		}
		$depElt = \tao_helpers_form_FormFactory::getElement('dependencies', 'Checkbox');
		$depElt->setDescription(__('Depends on'));
		$depElt->setOptions($extIds);
		$depElt->setValue('tao');
		$this->form->addElement($depElt);
		
		$chainingElt = \tao_helpers_form_FormFactory::getElement('samples', 'Checkbox');
		$chainingElt->setDescription(__('Samples'));
		$chainingElt->setOptions(array(
		    'structure' =>  __('sample structure')
		    ,'model' => __('sample model (todo)')
		    ,'rdf' => __('sample rdf install (todo)')
		    ,'install' => __('sample post install script (todo)')
		    ,'uninstall' => __('sample uninstall script (todo)')
		    ,'entry' => __('sample entry point (todo)')
		    ,'itemmodel' => __('sample item model (todo)')
		    ,'testmodel' => __('sample test model todo)')
		    ,'deliverymodel' => __('sample delivery model (todo)')
		));
		$chainingElt->setValue('structure');
		$this->form->addElement($chainingElt);
    }

}