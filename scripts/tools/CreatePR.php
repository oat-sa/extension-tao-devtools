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
use common_report_Report as Report;
use SplFileInfo;
use Cz\Git\GitRepository;
use Cz\Git\GitException;

/**
 * NOTE: install github cli: https://github.com/cli/cli
 *
 * Class CreatePR
 * @package oat\taoDevTools\scripts\tools
 */
class CreatePR extends ScriptAction
{
    private const GIT_BRANCH_NAME = 'ci/automated_release';
    private const GIT_COMMIT_MESSAGE = 'ci: add automated release github action';

    /** @var Report */
    private $report;

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
                'required'     => true,
                'description'  => 'Extensions id.',
                'defaultValue' => ''
            ]
        ];
    }

    public function run()
    {
        $this->report = new Report(Report::TYPE_INFO, 'Adding github actions');
        $extId = $this->getOption('extension');
        $path = realpath(ROOT_PATH . $extId);
        if (!file_exists(realpath(ROOT_PATH . $extId).'/manifest.php')) {
            $this->report->add(new Report(Report::TYPE_ERROR, $path.' - not a tao extension'));
            return $this->report;
        }
        $dir = new SplFileInfo(realpath(ROOT_PATH . $extId));
        $this->report = new Report(Report::TYPE_INFO, sprintf('Processing extension %s ...', $dir->getFilename()));
        $this->report->add($this->prepareLocalRepo($dir));
        $this->copyFiles($dir);
        $this->report->add($this->pushChanges($dir));
        $this->report->add($this->createPR($extId));
        return $this->report;
    }

    private function prepareLocalRepo(SplFileInfo $extDir)
    {
        $message = ['Prepare local repository:'];
        $repo = new GitRepository($extDir->getRealPath());
        $message = array_merge($message, $repo->execute(['add', '.']));
        $message = array_merge($message, $repo->execute(['reset', '--hard']));

        try {
            $message = array_merge($message, $repo->execute(['checkout', 'develop']));
        } catch (GitException $e) {
            $message = array_merge($message, $repo->execute(['checkout', '--track', 'origin/develop']));
        }
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
        $message = array_merge($message, $repo->execute(['commit', '-m', self::GIT_COMMIT_MESSAGE]));
        try {
            $message = array_merge($message, $repo->execute(['push', '-d', 'origin', self::GIT_BRANCH_NAME]));
        } catch (GitException $e) {
            //branch does not exist. Do nothing
        }
        $message = array_merge($message, $repo->execute(['push', 'origin', self::GIT_BRANCH_NAME]));
        return Report::createInfo(implode(PHP_EOL, $message));
    }

    private function createPR(string $extId)
    {
        exec('cd ./' . $extId . ' && gh pr create --title "ci: add automated release github action" --base develop --body "Automated release action and conventional commit CI check" --reviewer krampstudio,tikhanovichA,gitromba', $output);
        $output = implode(PHP_EOL, $output);
        return Report::createInfo($output);
    }

    private function copyFiles(SplFileInfo $extDir)
    {
        $dir = $extDir->getRealPath().'/.github/workflows';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        copy(ROOT_PATH.'tao/.github/workflows/continous-integration.yml', $dir.'/continous-integration.yml');
        copy(ROOT_PATH.'tao/.github/workflows/release.yml', $dir.'/release.yml');
    }
}
