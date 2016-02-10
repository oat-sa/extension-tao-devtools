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

namespace oat\taoDevTools\models;

use Jig\Utils\StringUtils;

/**
 * Creates a new extension
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class ExtensionCreator {
    
    private $id;
    
    private $label;
    
    private $version;
    
    private $author;
    
    private $authorNamespace;
    
    private $license;
    
    private $description;
    
    private $requires;
    
    private $options;

    private $installScripts = array();

    public function __construct($id, $name, $version, $author, $namespace, $license, $description, $dependencies, $options) {
        $this->id = $id;
        $this->label = $name;
        $this->version = $version;
        $this->author = $author;
        $this->authorNamespace = $namespace;
        $this->license = $license;
        $this->description = $description;
        $this->requires = array();
        foreach ($dependencies as $extId) {
            $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById($extId);
            $this->requires[$extId] = '>='.$ext->getVersion();
        }
        $this->options = $options;
    }
    
    private function validate() {
        // is root writable
        // does extension exist?
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Extension can be created'));
    } 
    
    public function run() {
        try {
            $this->createDirectoryStructure();
            if (in_array('structure', $this->options)) {
                $this->addSampleStructure();
            }
            if (in_array('itemtheme', $this->options)) {
                $this->addItemThemes();
            }
            if (in_array('platformtheme', $this->options)) {
                $this->addPlatformTheme();
            }
            $this->writebaseFiles();
            $this->addAutoloader();
            $this->prepareLanguages();
            
            return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Extension %s created. Before you install the extension make sure you add it to /vendor/composer/autoload_psr4.php', $this->label));
        } catch (Exception $e) {
            \common_Logger::w('Failed creating extension "'.$this->id.'": '.$e->getMessage());
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Unable to create extension %s, please consult log.', $this->label));
        }
    }
    
    protected function createDirectoryStructure() {
        $extDir = $this->getDestinationDirectory();
        $dirs = array(
            $extDir.'locales',
            $extDir.'model'
        );
        
        foreach ($dirs as $dirPath) {
            if (!file_exists($dirPath) && !mkdir($dirPath, 0770, true)) {
                throw new \common_Exception('Could not create directory "'.$dirPath.'"');
            }
        }
        return $extDir;
    }
    
    protected function copyFile($file, $destination = null, $extra = array()) {
        $sample = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$file.'.sample');
        $destination = $this->getDestinationDirectory().(is_null($destination) ? $file : $destination);
        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0770, true);
        }
        $map = $this->getVariableMapping();
        $map['{installScripts}'] = $this->substituteConstantTemplates(\common_Utils::toHumanReadablePhpString($this->installScripts, 1));
        $map = array_merge($map, $extra);
        $content = str_replace(array_keys($map), array_values($map), $sample);
        return file_put_contents($destination, $content);
    }
    
    protected function writeBaseFiles() {
        $this->copyFile('manifest.php');
        $this->copyFile('composer.json');
        $this->copyFile('scripts/update/Updater.php');
    }
    
    protected function addSampleStructure() {
        $controllerName = ucfirst($this->id);
        $this->copyFile('controller'.DIRECTORY_SEPARATOR.'structures.xml', null, array('{classname}' => $controllerName));
        $this->copyFile('controller'.DIRECTORY_SEPARATOR.'extId.php', 'controller'.DIRECTORY_SEPARATOR.$controllerName.'.php', array('{classname}' => $controllerName));
        $this->copyFile('views'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'routes.js');
        $this->copyFile(
            'views'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'extId'.DIRECTORY_SEPARATOR.'templateExample.tpl',
            'views'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.'templateExample.tpl'
        );
    }

    /**
     * Adds sample code for theme support
     */
    protected function addItemThemes() {
        // replacements
        $values = array(
            '{itemThemeId}' => StringUtils::camelize($this->label . ' default item theme'),
            '{itemTheme}' => StringUtils::camelize($this->label . ' default item theme', true)
        );
        $pathValues = array();
        foreach($values as $key => $value) {
            $pathValues[trim($key, '{}')] = $value;
        }

        // copy templates
        $samplePath = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getDir()
            .'models'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
        $paths = array(
            array('views','scss','themes','items','*.sample'),
        );

        $templates = array();
        foreach($paths as $path) {
            $templates = array_merge($templates, glob($samplePath.implode(DIRECTORY_SEPARATOR, $path)));
        }
        
        foreach($templates as $template) {
            $template = \tao_helpers_File::getRelPath($samplePath, $template);
            $template = substr($template, 0, strrpos($template, '.'));
            $this->copyFile($template, str_replace(array_keys($pathValues), $pathValues, $template), $values);
        }
        
        $this->copyFile('scripts/install/AddItemThemes.php', null, $values);
        $this->addInstallScript('php', '{authorNs}\\{id}\\scripts\\install\\AddItemThemes');
        
    }

    protected function addPlatformTheme() {
        // replacements
        $values = array(
            '{themeLabel}' => $this->label . ' Theme',
            '{platformThemeId}' => StringUtils::camelize($this->label . ' default platform theme'),
            '{platformTheme}' => ucfirst($this->id).'Theme'
        );
        $pathValues = array();
        foreach($values as $key => $value) {
            $pathValues[trim($key, '{}')] = $value;
        }
    
        // copy templates
        $samplePath = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getDir()
        .'models'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
        $paths = array(
            array('views','templates','themes','platform','platformThemeId','*.sample'),
            array('views','scss','themes','platform','platformThemeId','*.sample')
        );
    
        $templates = array();
        foreach($paths as $path) {
            $templates = array_merge($templates, glob($samplePath.implode(DIRECTORY_SEPARATOR, $path)));
        }
    
        foreach($templates as $template) {
            $template = \tao_helpers_File::getRelPath($samplePath, $template);
            $template = substr($template, 0, strrpos($template, '.'));
            $this->copyFile($template, str_replace(array_keys($pathValues), $pathValues, $template), $values);
        }
        
        $this->copyFile('model/theme/platformTheme.php','model/theme/'.ucfirst($this->id).'Theme.php', $values);
        
        $this->copyFile('scripts/install/setPlatformTheme.php', null, $values);
        $this->addInstallScript('php', '{__DIR__}/scripts/install/setPlatformTheme.php');
        
    }

    protected function prepareLanguages() {
        $options = array(
            'output_mode' => 'log_only',
            'argv' => array('placeholder', '-action=create','-extension='.$this->id, '-language=en-US')
        );
        new \tao_scripts_TaoTranslate(array(), $options);
        $options = array(
            'output_mode' => 'log_only',
            'argv' => array('placeholder', '-action=compile','-extension='.$this->id, '-language=en-US')
        );
        new \tao_scripts_TaoTranslate(array(), $options);
    }
    
    /**
     * Add the autoloader manually to the composer,
     * will break on next update
     */
    protected function addAutoloader() {
        $autoloaderFile = VENDOR_PATH.'composer/autoload_psr4.php';
        $content = file_get_contents($autoloaderFile);
        
        $lineToAdd = PHP_EOL.'    \''.$this->authorNamespace.'\\\\'.$this->id.'\\\\\' => array($baseDir . \'/'.$this->id.'\'),';
        $content = str_replace('return array(', 'return array('.$lineToAdd, $content);
        
        $content = file_put_contents($autoloaderFile, $content);
    }
    
    // UTILS
    
    protected function getDestinationDirectory() {
        return EXTENSION_PATH . $this->id. DIRECTORY_SEPARATOR;
    }
    
    protected function getLicense() {
        $licenseDirectory = dirname(__FILE__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'licenses'.DIRECTORY_SEPARATOR;
        $candidate = $licenseDirectory.strtolower($this->license);
        if (file_exists($candidate)) {
            $content = file_get_contents($candidate);
        } else {
            $content = file_get_contents($licenseDirectory.'unknown');
        }
        return str_replace(
            array('{year}', '{author}', '{license}'),
            array(date("Y"), $this->author, $this->license),
            $content
        );
    }
    
    protected static function escape($value) {
        return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $value));
    }


    /**
     * Add install scripts to the manifest
     *
     * @param string $section
     * @param string $scriptPath without __DIR__
     */
    protected function addInstallScript($section, $scriptPath) {
        $this->installScripts[$section][] = $scriptPath;
    }
    
    protected function getVariableMapping() {
        return array(
            '{id}' => $this->id,
            '{gitId}' => str_replace('_', '-', StringUtils::underscorize($this->id)),
            '{name}' => self::escape($this->label),
            '{version}' => self::escape($this->version),
            '{author}' => self::escape($this->author),
            '{license}' => self::escape($this->license),
            '{description}' => self::escape($this->description),
            '{authorNs}' => $this->authorNamespace,
            '{dependencies}' => 'array(\''.implode('\',\'', array_keys($this->requires)).'\')',
            '{requires}' => \common_Utils::toHumanReadablePhpString($this->requires, 1),
            '{managementRole}' => GENERIS_NS.'#'.$this->id.'Manager',
            '{licenseBlock}' => $this->getLicense()
        );
    }
    
    /**
     * Formats 'foo/{CONSTANT_BAR}/quux' as 'foo/'.CONSTANT_BAR.'/quux'
     *
     * @param $value
     * @return string
     */
    protected function substituteConstantTemplates($value) {
        $lines = array();
        $map = $this->getVariableMapping();
        foreach(explode(PHP_EOL, $value) as $line) {
            $line = str_replace(array_keys($map), array_values($map), $line);
            $quote = substr(trim($line), 0, 1);
            $line = preg_replace_callback(
                '~{([\w]+)}~',
                function($matches) use ($quote) {
                    $matches[1] = $quote . '.' . $matches[1] . '.' . $quote;
                    return $matches[1];
                },
                $line
            );
            $lines[] = str_replace(array($quote.$quote . '.', '.' . $quote.$quote), '', $line);
        }
        return implode(PHP_EOL, $lines);
    }
}
