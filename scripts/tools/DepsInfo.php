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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDevTools\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use DirectoryIterator;
use common_report_Report as Report;
use SplFileInfo;
use Symfony\Component\Process\PhpProcess;

class DepsInfo extends ScriptAction
{

    private $classExistsCache = [];

    private $namespaceMap = [
        'oatbox' => 'generis',
        'common' => 'generis',
        'core' => 'generis',
        'kernel' => 'tao',
        'test' => 'tao',
        'helpers' => 'generis',
    ];

    /**
     * Provides the title of the script.
     *
     * @return string
     */
    protected function provideDescription()
    {
        return 'Analyzer of Tao extension dependencies' . PHP_EOL;
        ;
    }

    /**
     * Provides the possible options.
     *
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'render' => [
                'prefix'       => '-r',
                'longPrefix'   => 'render',
                'cast'         => 'string',
                'required'     => false,
                'description'  => 'JSON|HTML',
                'defaultValue' => 'JSON'
            ],
            'extension' => [
                'prefix'       => 'e',
                'longPrefix'   => 'extension',
                'cast'         => 'string',
                'required'     => false,
                'description'  => 'Analyze given extension. All extensions analyzed if not given',
                'defaultValue' => ''
            ]
        ];
    }

    public function run()
    {
        $result = $this->analyze();
        $renderer = $this->getOption('render');

        if ($renderer === 'JSON') {
            $result = $this->renderJson($result, $this->getOption('render'));
        } elseif ($renderer === 'HTML') {
            $result = $this->renderHtml($result, $this->getOption('render'));
        } else {
            throw new \Exception(sprintf('Renderer %s not found', $renderer));
        }

        return Report::createInfo($result, $result);
    }

    /**
     * @return array
     */
    private function analyze()
    {
        if ($this->getOption('extension')) {
            $extRoot = new SplFileInfo(ROOT_PATH . $this->getOption('extension'));
            $manifest = $this->getManifest($extRoot);
            $result[$manifest['name']] = $this->getUsedClasses($extRoot);
        } else {
            $result = [];
            foreach (new DirectoryIterator(ROOT_PATH) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }
                $manifest = $this->getManifest($fileInfo);
                $composer = $this->getComposer($fileInfo);
                if ($composer === null || $manifest === null) {
                    continue;
                }
                $result[$manifest['name']] = array_merge($result, $this->getUsedClasses($fileInfo));
            }
        }

        foreach ($result as $extId => &$extResult) {
            if (isset($manifest['requires']) && is_array($manifest['requires'])) {
                $manifestDeps = array_keys($manifest['requires']);
                sort($manifestDeps);
            } else {
                $manifestDeps = [];
            }
            $extResult['manifestDeps'] = $manifestDeps;

            $realExtDependencies = [];
            foreach ($extResult['classes'] as $class) {
                $realExtDependency = $this->classToExtensionId($class);
                if ($realExtDependency !== $extId) {
                    $realExtDependencies[] = $realExtDependency;
                }
            }
            $extResult['realDeps'] = array_values(array_unique(array_filter($realExtDependencies)));
            $extResult['redundantInManifest'] = array_values(array_unique(array_diff($extResult['manifestDeps'], $extResult['realDeps'])));
            $extResult['notMentionedInManifest'] = array_values(array_unique(array_diff($extResult['realDeps'], $extResult['manifestDeps'])));
            $this->getMissedClasses($extResult, $extId);
        }
        
        if (!$this->getOption('extension')) {
            $this->checkСyclicDep($result);
        }

        return $result;
    }

    private function getMissedClasses(&$extResult, $extId)
    {
        $autoloadScript = ROOT_PATH . 'vendor/autoload.php';
        $missedClasses = [];
        $missedExtensions = [];
        foreach ($extResult['classes'] as $class) {
            $classExtension = $this->classToExtensionId($class);
            if ($classExtension === $extId) {
                continue;
            }
            $extensions[] = $classExtension;
            if (!$this->_class_exists($autoloadScript, $class)) {
                $missedExtensions[] = $classExtension;
                $missedClasses[] = $class;
            }
        }
        $extResult['missedClasses'] = $missedClasses;
    }

    private function getUsedClasses(\SplFileInfo $fileInfo)
    {
        $classes = $this->getDependClasses($fileInfo);
        return  [
            'classes' => array_unique($classes)
        ];
    }

    /**
     * @param $result
     * @return false|string
     */
    private function renderJson($result)
    {
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param $result
     * @return string
     */
    private function renderHtml($result)
    {
        $resultHtml = '<style>
table, th, td {
  border: 1px solid black;
  font-family: monospace;
}
.classlist {
font-size: 12px;
max-height: 150px;
overflow-y: scroll;
}
.missed {
color: darkred;
}
</style>';
        $resultHtml .= '<table><tr>
<th>Extension ID</th>
<th>Used Classes</th>
<th>Dependencies in manifest</th>
<th>Real dependencies</th>
<th>Redundant (Mentioned in manifest but not used)</th>
<th>Missed (Not mentioned in manifest but used in code)</th>
<th>Cyclic dependencies</th>
</tr>';
        foreach ($result as $extId => $row) {
            $classes = $row['classes'];
            $resultHtml .= '<tr>
                <td>' . $extId . '</td>
                <td><div class="classlist">' . implode('<br>', $classes) . '</div></td>
                <td>' . implode('<br>', $row['manifestDeps']) . '</td>
                <td>' . implode('<br>', $row['realDeps']) . '</td>
                <td>' . implode('<br>', $row['redundantInManifest']) . '</td>
                <td><div class="missed">' . implode('<br>', $row['notMentionedInManifest']) . '</div></td>
                <td><div class="missed">' . implode('<br>', $row['cyclicDeps']) . '</div></td>
                </tr>';
        }
        $resultHtml .=  '</table>';

        return $resultHtml;
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return mixed|null
     */
    private function getManifest(SplFileInfo $fileInfo)
    {
        $path = $fileInfo->getRealPath() . DIRECTORY_SEPARATOR . 'manifest.php';
        if (file_exists($path)) {
            return require $path;
        }
        return null;
    }

    /**
     * @param SplFileInfo $fileInfo
     * @return false|string
     */
    private function getComposer(SplFileInfo $fileInfo)
    {
        $path = $fileInfo->getRealPath() . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return null;
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return string|null
     */
    private function getDependClasses(SplFileInfo $fileInfo)
    {
        $result = [];
        $pathToDephpend = ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR
            . 'dephpend' . DIRECTORY_SEPARATOR
            . 'dephpend' . DIRECTORY_SEPARATOR
            . 'bin' . DIRECTORY_SEPARATOR . 'dephpend';
        if (!file_exists($pathToDephpend)) {
            throw new \RuntimeException(
                'dePHPend extension is not installed, please install it before running command.'
            );
        }
        $command = 'php ' . $pathToDephpend . ' text ' . $fileInfo;
        $dependencies = shell_exec($command);
        $dependenciesArray = preg_split("/\r\n|\n|\r/", $dependencies);
        $dependenciesArray = array_filter($dependenciesArray);
        foreach ($dependenciesArray as $dependency) {
            $dependencyClass = explode(' --> ', $dependency);
            $result[] = $dependencyClass[1];
        }
        return array_unique($result);
    }

    /**
     * @param string $class
     * @return mixed|null
     */
    private function classToExtensionId(string $class)
    {
        if (preg_match('/^oat\\\([a-zA-Z]+).*/', $class, $matches) && isset($matches[1])) {
            return $this->mapNamespace($matches[1]);
        } elseif (preg_match('/^([^_\\\]+)_/', $class, $matches) && isset($matches[1])) {
            return $this->mapNamespace($matches[1]);
        }
        return null;
    }

    /**
     * @param $namespace
     * @return bool
     */
    private function mapNamespace($namespace)
    {
        return isset($this->namespaceMap[$namespace]) ? $this->namespaceMap[$namespace] : $namespace;
    }

    /**
     * @param $deps
     */
    private function checkСyclicDep(&$deps): void
    {
        foreach ($deps as $extId => &$extDeps) {
            $extDeps['cyclicDeps'] = [];
            foreach ($extDeps['realDeps'] as $extDep) {
                if (isset($deps[$extDep]) && in_array($extId, $deps[$extDep]['realDeps'])) {
                    $extDeps['cyclicDeps'][] = $extDep;
                }
            }
        }
    }

    private function _class_exists(string $autoloadScript, string $class, bool $autoload = true): bool
    {
        if (!isset($this->classExistsCache[$class])) {
            $process = new PhpProcess(sprintf(
                '<?php require_once %s; exit((class_exists(%s, true) || interface_exists(%s, true) || trait_exists(%s, true) || is_callable(%s)) ? 0 : 1);',
                var_export($autoloadScript, true),
                var_export($class, true),
                var_export($class, true),
                var_export($class, true),
                var_export($class, true)
            ));

            $this->classExistsCache[$class] = (1 !== $process->run());
        }
        return $this->classExistsCache[$class];
    }
}
