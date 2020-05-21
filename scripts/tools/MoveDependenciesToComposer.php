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

use oat\oatbox\cache\SimpleCache;
use oat\oatbox\extension\script\ScriptAction;
use DirectoryIterator;
use common_report_Report as Report;
use SplFileInfo;
use Cz\Git\GitRepository;
use Cz\Git\GitException;

class MoveDependenciesToComposer extends ScriptAction
{
    private const DEPS_CACHE_KEY = 'DEPS_CACHE_KEY';
    private const GIT_BRANCH_NAME = 'TDR-22/move_dependencies_from_manifest_to_composer';

    /** @var Report */
    private $report;

    /** @var Report */
    private $extReport;

    private $extToSkip = [
        'taoDevTools'
    ];

    private $dependenciesToSkip = [
        'tao' => [
            'taoBackOffice',
        ],
        'generis' => [
            'tao',
            'taoWorkspace'
        ],
        'taoGroups' => [
            'taoDeliveryRdf',//check if taoDeliveryRdf enabled
        ],
        'taoTestTaker' => [
            'taoGroups',//check if taoGroups enabled
        ],
        'taoQtiTest' => [
            'taoDelivery',
            'taoProctoring',
            'taoDeliveryRdf',
        ],
        'taoDelivery' => [
            'taoProctoring',
            'taoResultServer',
        ],
        'taoDeliveryRdf' => [
            'taoResultServer'
        ]
    ];

    /**
     * Provides the title of the script.
     *
     * @return string
     */
    protected function provideDescription()
    {
        return '';
    }

    /**
     * Provides the possible options.
     *
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'extension' => [
                'prefix'       => 'e',
                'longPrefix'   => 'extension',
                'cast'         => 'string',
                'required'     => false,
                'description'  => 'Extensions id. All extensions will be precessed if not given',
                'defaultValue' => ''
            ],
            'no-cache'   => [
                'prefix'      => 'nc',
                'longPrefix'  => 'no-cache',
                'flag'        => true,
                'description' => 'Clear dependencies cache',
                'defaultValue' => false
            ],
        ];
    }

    public function run()
    {
        $this->report = new Report(Report::TYPE_INFO, 'Moving dependencies from manifest.php to composer.json');

        $deps = $this->getDependencies();
        $dir = new DirectoryIterator(ROOT_PATH);
        foreach ($dir as $extDir) {
            if ($extDir->isDot()) {
                continue;
            }
            $composerPath = $extDir->getRealPath() . DIRECTORY_SEPARATOR . 'composer.json';
            $composerArray = $this->getComposer($composerPath);
            $extId = $composerArray['extra']['tao-extension-name'];
            if ($extId !== 'taoGroups') {
                continue;
            }
            if ($composerArray === null || in_array($extId, $this->extToSkip)) {
                continue;
            }
            $this->extReport = new Report(Report::TYPE_INFO, sprintf('Processing extension %s ...', $extDir));
            $this->extReport->add($this->prepareLocalRepo($extDir));
            $this->extReport->add($this->updateComposerJson($extDir, $deps[$extId]));
            $this->extReport->add($this->createJenkinsFile($extDir, $composerArray['name'], $extId));
            if ($extId === 'taoGroups') {
                $this->extReport->add($this->pushChanges($extDir));
            }
            $this->report->add($this->extReport);
        }
        return $this->report;
    }

    private function getComposer($composerPath)
    {
        if (!file_exists($composerPath)) {
            return null;
        }
        $composerArray = json_decode(file_get_contents($composerPath), true);
        if (!isset($composerArray['extra']['tao-extension-name'])) {
            return null;
        }
        return $composerArray;
    }

    private function updateComposerJson($extDir, $extDeps)
    {
        $taoPackages = $this->getTaoPackages();
        $composerPath = $extDir->getRealPath() . DIRECTORY_SEPARATOR . 'composer.json';
        $composerArray = $this->getComposer($composerPath);
        $extId = $composerArray['extra']['tao-extension-name'];
        foreach ($extDeps['realDeps'] as $realDep) {
            if (isset($taoPackages[$realDep]) && !$this->skipDependency($extId, $realDep)) {
                $composerArray['require'][$taoPackages[$realDep]] = '*';
            }
        }
        $composerJson = json_encode($composerArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerPath, $composerJson);
        return Report::createInfo(sprintf('Composer file of %s has been updated', $extId));
    }

    private function createJenkinsFile(SplFileInfo $extDir, $extRepo, $extId)
    {
        $replacements = [
            '<repoName>' => $extRepo,
            '<extName>' => $extId,
        ];
        $filePath = $extDir->getRealPath() . DIRECTORY_SEPARATOR.'.Jenkinsfile';
        $code = strtr($this->getTemplate(), $replacements);
        file_put_contents($filePath, $code);
        return Report::createInfo(sprintf('Jenkinsfile file of %s has been created', $extId));
    }

    private function prepareLocalRepo(SplFileInfo $extDir)
    {
        $message = ['Prepare local repository:'];
        $repo = new GitRepository($extDir->getRealPath());
        $message = array_merge($message, $repo->execute(['add', '.']));
        $message = array_merge($message, $repo->execute(['reset', '--hard']));
        $message = array_merge($message, $repo->execute(['checkout', 'develop']));
        try {
            $message = array_merge($message, $repo->execute(['branch', '-D', self::GIT_BRANCH_NAME]));
        } catch (GitException $e) {
            //branch does not exist. Do nothing
        }
        $message = array_merge($message, $repo->execute(['checkout', '-b', self::GIT_BRANCH_NAME]));
        return Report::createInfo(implode(PHP_EOL, $message));
    }

    private function pushChanges(SplFileInfo $extDir)
    {
        $message = ['Push changes to the remote repository:'];
        $repo = new GitRepository($extDir->getRealPath());
        $message = array_merge($message, $repo->execute(['add', '.']));
        $message = array_merge($message, $repo->execute(['commit', '-m', 'Move dependencies to composer.json; Add Jenkinsfile;']));
        try {
            $message = array_merge($message, $repo->execute(['push', '-d', 'origin', self::GIT_BRANCH_NAME]));
        } catch (GitException $e) {
            //branch does not exist. Do nothing
        }
        $message = array_merge($message, $repo->execute(['push', 'origin', self::GIT_BRANCH_NAME]));
        return Report::createInfo(implode(PHP_EOL, $message));
    }

    /**
     * @return string
     */
    private function getTemplate()
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'jenkinsfile_tpl';
        return file_get_contents($path);
    }

    /**
     * @return array
     */
    private function getTaoPackages()
    {
        $taoPackages = [];
        $composerLock = json_decode(file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.lock'), true);
        foreach ($composerLock['packages'] as $package) {
            if (!isset($package['extra']['tao-extension-name'])) continue;
            $taoPackages[$package['name']] = $package['extra']['tao-extension-name'];
        }
        return array_flip($taoPackages);
    }

    private function getDependencies()
    {
        /** @var SimpleCache $cache */
        $cache = $this->getServiceLocator()->get(SimpleCache::SERVICE_ID);
        if (!$cache->has(self::DEPS_CACHE_KEY) || $this->getOption('no-cache') === true) {
            $action = new DepsInfo();
            $deps = $action([])->getData();
            $cache->set(self::DEPS_CACHE_KEY, $deps);
        } else {
            $deps = $cache->get(self::DEPS_CACHE_KEY);
        }
        return json_decode($deps, true);
    }

    private function skipDependency($extId, $dependency)
    {
        return isset($this->dependenciesToSkip[$extId]) && in_array($dependency, $this->dependenciesToSkip[$extId]);
    }
}
