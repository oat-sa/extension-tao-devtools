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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */

namespace oat\taoDevTools\actions;

use oat\taoDelivery\helper\Delivery as DeliveryHelper;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\SessionStateService;
use oat\taoQtiTest\models\TestSessionService;
use oat\taoTests\models\runner\time\TimePoint;
use qtism\common\datatypes\Duration;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

/**
 * Class TestRunner
 *
 * Displays running sessions for the logged on user.
 *
 * @package oat\taoDevTools\actions
 */
class TestRunner extends \tao_actions_SinglePageModule
{
    /**
     * Gets the path to the layout
     * @return array
     */
    protected function getLayout()
    {
        $webPath = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConstant('BASE_WWW');
        \tao_helpers_Scriptloader::addCssFile($webPath . '/css/testrunner.css');

        return parent::getLayout();
    }


    /**
     *
     */
    public function index()
    {
        $user = \common_session_SessionManager::getSession()->getUser();

        $service = $this->getServiceManager()->get(\taoDelivery_models_classes_DeliveryServerService::CONFIG_ID);

        $runningDeliveries = array();
        foreach ($service->getResumableDeliveries($user) as $de) {
            $delivery = DeliveryHelper::buildFromDeliveryExecution($de);
            $delivery[DeliveryHelper::LAUNCH_URL] = _url('timer', 'TestRunner', 'taoDevTools', [
                'deliveryExecution' => $delivery[DeliveryHelper::ID]
            ]);
            $runningDeliveries[] = $delivery;
        }
        $this->setData('deliveries', $runningDeliveries);
        $this->composeView('test-runner', null, 'TestRunner/index.tpl');
    }

    /**
     *
     */
    public function timer()
    {
        $sessionId = $this->getRequestParameter('deliveryExecution');
        $deliveryExecution = $this->getDeliveryExecution($sessionId);
        $this->setData('sessionId', $sessionId);
        $this->setData('title', $deliveryExecution->getLabel());
        $this->composeView('test-runner', null, 'TestRunner/timer.tpl');
    }

    /**
     *
     */
    public function deliveryExecutionData()
    {
        $sessionId = $this->getRequestParameter('deliveryExecution');
        $deliveryExecution = $this->getDeliveryExecution($sessionId);
        $session = $this->getTestSession($deliveryExecution);

        $remaining = null;
        $identifiers = null;
        $durations = null;
        $extraTime = null;
        $timers = null;
        $running = false;
        $stateUri = $deliveryExecution->getState()->getUri();
        $state = $this->getStateLabel($deliveryExecution->getState());

        if ($session) {
            if ($session->isRunning()) {
                $running = true;
                $extraTime = $this->getExtraTime($session);
                $timers = $this->getTimeConstraints($session);

                $currentRoute = $session->getRoute()->current();
                $itemRef = $currentRoute->getAssessmentItemRef();
                $currentItemTags = $session->getItemAttemptTag($currentRoute);

                $occurrence = $currentRoute->getOccurence();
                $itemSession = $session->getAssessmentItemSessionStore()->getAssessmentItemSession($itemRef, $occurrence);
                $attempt = $itemSession['numAttempts']->getValue();

                $title = $session->getAssessmentTest()->getTitle();
                $remaining = $this->getRemainingTime($session);
                $position = $this->getSessionPosition($session);
                $identifiers = [
                    'attempt' => $attempt,
                    'occurence' => $occurrence,
                    'current' => $currentItemTags,
                    'item' => $session->getCurrentAssessmentItemRef()->getIdentifier(),
                    'section' => $session->getCurrentAssessmentSection()->getIdentifier(),
                    'testPart' => $session->getCurrentTestPart()->getIdentifier(),
                    'test' => $session->getAssessmentTest()->getIdentifier(),
                ];
                $durations = [
                    'server' => [
                        'attempt' => $this->formatDuration($session->getTimerDuration($currentItemTags, TimePoint::TARGET_SERVER)),
                        'item' => $this->formatDuration($session->computeItemTime(TimePoint::TARGET_SERVER)),
                        'section' => $this->formatDuration($session->computeSectionTime(TimePoint::TARGET_SERVER)),
                        'testPart' => $this->formatDuration($session->computeTestPartTime(TimePoint::TARGET_SERVER)),
                        'test' => $this->formatDuration($session->computeTestTime(TimePoint::TARGET_SERVER)),
                    ],
                    'client' => [
                        'attempt' => $this->formatDuration($session->getTimerDuration($currentItemTags, TimePoint::TARGET_CLIENT)),
                        'item' => $this->formatDuration($session->computeItemTime(TimePoint::TARGET_CLIENT)),
                        'section' => $this->formatDuration($session->computeSectionTime(TimePoint::TARGET_CLIENT)),
                        'testPart' => $this->formatDuration($session->computeTestPartTime(TimePoint::TARGET_CLIENT)),
                        'test' => $this->formatDuration($session->computeTestTime(TimePoint::TARGET_CLIENT)),
                    ],
                ];
            } else {
                $position = 'finished';
            }
        } else {
            $position = 'starting';
        }

        $this->returnJson([
            'success' => true,
            'data' => [
                'id' => md5($sessionId . $remaining . $position),
                'running' => $running,
                'state' => $state,
                'stateUri' => $stateUri,
                'sessionId' => $sessionId,
                'remaining' => $remaining,
                'position' => $position,
                'identifiers' => $identifiers,
                'durations' => $durations,
                'extraTime' => $extraTime,
                'timers' => $timers,
            ]
        ]);
    }

    /**
     * @param Duration $duration
     * @return int|null
     */
    protected function formatDuration($duration)
    {
        if ($duration) {
            if (method_exists($duration, 'getMicroseconds')) {
                return $duration->getMicroseconds(true) / 1e6;
            }
            return $duration->getSeconds(true);
        }
        return null;
    }

    /**
     * @param $sessionId
     * @return DeliveryExecution
     */
    protected function getDeliveryExecution($sessionId)
    {
        return \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($sessionId);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return TestSession
     */
    protected function getTestSession($deliveryExecution)
    {
        return $this->getServiceManager()->get(TestSessionService::SERVICE_ID)->getTestSession($deliveryExecution);
    }

    /**
     * @param TestSession $session
     * @return null|string
     */
    protected function getSessionPosition($session)
    {
        if ($session) {
            $sessionService = $this->getServiceManager()->get(SessionStateService::SERVICE_ID);
            return $sessionService->getSessionDescription($session);
        }
        return null;
    }

    /**
     * @param TestSession $session
     * @return null|string
     */
    protected function getRemainingTime($session)
    {
        $result = null;
        $remaining = 0;
        $hasTimer = false;

        if ($session !== null && $session->isRunning()) {
            $remaining = PHP_INT_MAX;
            if ($session instanceof TestSession) {
                $timeConstraints = $session->getRegularTimeConstraints();
            } else {
                $timeConstraints = $session->getTimeConstraints();
            }
            foreach ($timeConstraints as $tc) {
                // Only consider time constraints in force.
                if ($tc->getMaximumRemainingTime() !== false) {
                    $hasTimer = true;
                    $remaining = min($remaining, $this->formatDuration($tc->getMaximumRemainingTime()));
                }
            }
        }

        if ($hasTimer) {
            $result = $remaining . 's';
        }

        return $result;
    }

    /**
     * @param TestSession $session
     * @return array
     */
    protected function getTimeConstraints($session)
    {
        $constraints = array();

        foreach ($session->getRegularTimeConstraints() as $tc) {
            $timeRemaining = $tc->getMaximumRemainingTime();
            if ($timeRemaining !== false) {

                $source = $tc->getSource();
                $identifier = $source->getIdentifier();
                $constraints[] = array(
                    'id' => $identifier,
                    'label' => method_exists($source, 'getTitle') ? $source->getTitle() : $identifier,
                    'remaining' => $this->formatDuration($timeRemaining),
                    'type' => $source->getQtiClassName()
                );
            }
        }

        return $constraints;
    }

    /**
     * @param TestSession $session
     * @return array
     */
    protected function getExtraTime($session)
    {
        $timer = $session->getTimer();

        return [
            'total' => $timer->getExtraTime(),
            'consumed' => $timer->getConsumedExtraTime(),
            'remaining' => $timer->getRemainingExtraTime(),
        ];
    }

    /**
     * @return array
     */
    protected function getStateLabels()
    {
        return [
            ProctoredDeliveryExecution::STATE_AWAITING => __('Awaiting'),
            ProctoredDeliveryExecution::STATE_AUTHORIZED => __('Authorized but not started'),
            ProctoredDeliveryExecution::STATE_ACTIVE => __('In Progress'),
            ProctoredDeliveryExecution::STATE_PAUSED => __('Paused'),
            ProctoredDeliveryExecution::STATE_FINISHED => __('Completed'),
            ProctoredDeliveryExecution::STATE_TERMINATED => __('Terminated'),
            ProctoredDeliveryExecution::STATE_CANCELED => __('Paused'),
        ];
    }

    /**
     * @param string|\core_kernel_classes_Resource $state
     * @return string
     */
    protected function getStateLabel($state)
    {
        $label = '';
        $uri = $state;

        if ($state instanceof \core_kernel_classes_Resource) {
            $label = $state->getLabel();
            $uri = $state->getUri();
        }

        $stateLabels = $this->getStateLabels();
        if (isset($stateLabels[$uri])) {
            $label = $stateLabels[$uri];
        }

        return $label;
    }
}
