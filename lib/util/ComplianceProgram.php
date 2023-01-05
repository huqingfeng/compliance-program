<?php
/**
 * Base compliance program class. To define a program, extend this class
 * and define the loadGroup method. Optionally add a loadEvaluators method.
 */
abstract class ComplianceProgram implements MenagerieSecurable
{
    const MODE_ALL = 'all';
    const MODE_INDIVIDUAL = 'individual';
    const MODE_ADMIN = 'admin';
    const WHITELIST = array(
        '127.0.0.1',
        '172.17.0.1',
        '192.168.200.1',
        '192.168.200.128',
        '192.168.92.128',
        '192.168.10.210',
        '192.168.10.107',
        '192.168.50.120',
        '::1'
    );

    public function getDomain()
    {
        if (in_array($_SERVER['REMOTE_ADDR'], ComplianceProgram::WHITELIST)){
            return "http://127.0.0.1/";
        } else {
            return "https://master.hpn.com/";
        }
    }

    public function getOption($name, $default = null)
    {
        return array_key_exists($name, $this->options) ?
            $this->options[$name] : $default;
    }

    public function getOptionCanManageTeamsAndBuddies()
    {
        $endDate = $this->getOption('team_buddy_management_end_date');

        return !$endDate || strtotime($endDate) >= time() || sfConfig::get('app_service_request');
    }

    public function getOptionCanCreateNewTeam()
    {
        $endDate = $this->getOption('team_create_end_date');

        return !$endDate || strtotime($endDate) >= time();
    }

    public function secureAccess(sfProjectUser $user, $action = null)
    {
        foreach($this->getComplianceViewGroups() as $group) {
            foreach($group->getComplianceViews() as $view) {
                if(!$view->secureAccess($user, $action)) {
                    $group->removeComplianceView($view->getName());
                }
            }
        }

        return true;
    }

    public function getLocalActions()
    {
        return array();
    }

    public function getActionTemplateCustomizations()
    {
        return '';
    }

    public function getEmailContent(array $variables)
    {
        return array();
    }

    /**
     * @return TeamDashboardPrinter
     * @throws RuntimeException
     */
    public function getTeamDashboardPrinter()
    {
        throw new \RuntimeException('Basic dashboard printers are not available yet.');
    }

    public function getTeamData(sfACtions $actions)
    {
        return false;
    }

    public function getRegistrationForm()
    {
        throw new \RuntimeException('Basic registration form is not available yet.');
    }

    /**
     * @return RegistrationFormPrinter
     * @throws RuntimeException
     */
    public function getRegistrationFormPrinter()
    {
        throw new \RuntimeException('Basic registration form printers are not available yet.');
    }

    public function summarizeUserStatusForTeamLeaderboard(ComplianceProgramStatus $status)
    {
        throw new \RuntimeException('Basic definition not available yet.');
    }

    /**
     * @return BuddyDashboardPrinter
     * @throws RuntimeException
     */
    public function getBuddyDashboardPrinter()
    {
        throw new \RuntimeException('Basic dashboard printers are not available yet.');
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new BasicComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        return new BasicComplianceProgramAdminReportPrinter();
    }

    public function printReport($preferredPrinter = null)
    {
        $reportPrinter = $this->getProgramReportPrinter($preferredPrinter);
        $userStatus = $this->getStatus();

        if($reportPrinter != null) {
            $reportPrinter->printReport($userStatus);
        }
    }

    public function render($preferredPrinter = null)
    {
        ob_start();
        $this->printReport($preferredPrinter);

        return ob_get_clean();
    }

    public abstract function loadGroups();

    public function loadEvaluators()
    {

    }

    public function __construct($startDate, $endDate, $includeEvaluators = false, $options = null)
    {
        $this->id = null;
        $this->setActiveUser(null);
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
        $this->viewGroups = array();
        $this->complianceStatusMapper = new ComplianceStatusMapper();
        
        if ($options !== null) {
            $this->options = array_merge($this->options, $options);
        }
        
        $this->loadGroups();

        if($includeEvaluators) {
            $this->loadEvaluators();
        }
    }

    public function cloneForEvaluation($startDate, $endDate)
    {
        $class = get_class($this);
        $i = new $class($startDate, $endDate);
        $i->setComplianceProgramRecord($this->getComplianceProgramRecord());

        return $i;
    }

    /**
     * Sets the start date.
     *
     * @param mixed $startDate
     * @return ComplianceProgram This instance
     */
    public function setStartDate($date)
    {
        $this->startDate = is_numeric($date) ? $date : strtotime($date);

        if($this->startDate === false) {
            throw new InvalidArgumentException('Invalid date: '.$date);
        }

        return $this;
    }

    /**
     * Sets the end date.
     *
     * @param mixed $date
     * @return ComplianceProgram This instance
     */
    public function setEndDate($date)
    {
        $this->endDate = is_numeric($date) ? $date : strtotime($date);

        if($this->endDate === false) {
            throw new InvalidArgumentException('Invalid date: '.$date);
        }

        return $this;
    }

    public function getStartDate($format = 'U')
    {
        return date($format, $this->startDate);
    }

    public function getEndDate($format = 'U')
    {
        return date($format, $this->endDate);
    }

    /**
     * If instantiated based on the compliance_programs table and the
     * factory method, this will return the id column for this program instance.
     */
    public function getID()
    {
        if($this->record) {
            return $this->record->getID();
        }

        throw new MenagerieException('Unknown id: Compliance program record not set.');
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function getComplianceStatusMapper()
    {
        return $this->complianceStatusMapper;
    }

    public function getComplianceStatusMappings()
    {
        $allowPartial = $this->hasPartiallyCompliantStatus();

        return array_filter($this->getComplianceStatusMapper()->getMappings(), function ($element) use ($allowPartial) {
            return $allowPartial || $status == ComplianceStatus::PARTIALLY_COMPLIANT;
        });
    }

    /**
     * @param ComplianceStatusMapper $mapper
     * @return ComplianceProgram This instance
     */
    public function setComplianceStatusMapper(ComplianceStatusMapper $mapper)
    {
        $this->complianceStatusMapper = $mapper;

        return $this;
    }

    public function getComplianceViewGroups()
    {
        return $this->viewGroups;
    }

    public function getPointsRequiredForCompliance()
    {
        return null;
    }

    public function getMaximumNumberOfPoints()
    {
        $totalPoints = null;

        foreach($this->getComplianceViewGroups() as $group) {
            $groupPoints = $group->getMaximumNumberOfPoints();

            if($groupPoints !== null) {
                $totalPoints = $totalPoints === null ? $groupPoints : $totalPoints + $groupPoints;
            }
        }

        return $totalPoints;
    }

    /**
     * Finds a compliance view based on its name. While it is technically
     * possible for a compliance program to have two views with the same name,
     * it is not reccomended, so this call should work fine.
     *
     * @param string $name
     * @return ComplianceView|null
     */
    public function getComplianceView($name)
    {
        foreach($this->getComplianceViewGroups() as $group) {
            foreach($group->getComplianceViews() as $view) {
                if($view->getName() == $name) {
                    return $view;
                }
            }
        }

        return null;
    }

    /**
     * Returns all of the views in this program.
     *
     * @return array
     */
    public function getComplianceViews()
    {
        $views = array();

        foreach($this->getComplianceViewGroups() as $group) {
            foreach($group->getComplianceViews() as $view) {
                $views[] = $view;
            }
        }

        return $views;
    }

    /**
     * Finds a compliance view based on its class name. If there is more than one,
     * the first matching is returned.
     *
     * @param string $className
     * @return ComplianceView|null
     */
    public function getComplianceViewByClassName($className)
    {
        foreach($this->getComplianceViewGroups() as $group) {
            foreach($group->getComplianceViews() as $view) {
                if($view instanceof $className) {
                    return $view;
                }
            }
        }

        return null;
    }

    public function getComplianceViewGroup($name)
    {
        return isset($this->viewGroups[$name]) ? $this->viewGroups[$name] : null;
    }

    /**
     * Adds a compliance group to this program.
     *
     * @param ComplianceViewGroup $viewGroup
     * @return ComplianceProgram This instance
     */
    protected function addComplianceViewGroup(ComplianceViewGroup $viewGroup)
    {
        $this->viewGroups[$viewGroup->getName()] = $viewGroup;
        $viewGroup->setComplianceProgram($this);

        return $this;
    }

    public function getStatus()
    {
        if($this->activeUser === null) {
            return null;
        } else {
            $programStatus = new ComplianceProgramStatus($this, $this->activeUser);

            foreach($this->getComplianceViewGroups() as $group) {
                $programStatus->addComplianceViewGroupStatus($group->getStatus());
            }

            $this->importStatus($programStatus);

            return $programStatus;
        }
    }

    public function setDoDispatch($bool)
    {
        $this->doDispatch = $bool;
    }

    public function importStatus(ComplianceProgramStatus $programStatus)
    {
        $this->evaluateAndStoreOverallStatus($programStatus);

        // This event is dispatched right now to allow the UI to attach to it
        // and calculate items completed. There might be a better way to do this.

        if($this->doDispatch) {
            $this->dispatch('compliance_program.status_calculated');
        }
    }
    
    public function useParallelReport()
    {
        return true;
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $compliantInAllGroups = true;

        $points = null;
        $compliant = null;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $compliantInAllGroups = $compliantInAllGroups && $groupStatus->isCompliant();
            $groupPoints = $groupStatus->getPoints();

            if($groupPoints !== null) {
                $points = $points === null ? $groupPoints : $points + $groupPoints;
            }
        }

        $pointsRequired = $this->getPointsRequiredForCompliance();

        if($pointsRequired === null) {
            $compliant = $compliantInAllGroups;
        } else {
            $compliant = $points !== null && $points >= $pointsRequired;
        }

        $status->setStatus($compliant ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
        $status->setPoints($points);
    }

    /**
     * @return User
     */
    public function getActiveUser()
    {
        return $this->activeUser;
    }

    /**
     * @param User $user
     * @return ComplianceProgram This instance.
     */
    public function setActiveUser(User $user = null)
    {
        $this->activeUser = $user;

        return $this;
    }

    /**
     * This method is called by the controller if the session user is not
     * a valid user for the program -- i.e. it is not in the result set of
     * the users query.
     *
     * @param sfActions $actions
     */
    public function handleInvalidUser(sfActions $actions)
    {
        $actions->forward404();
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        if(count($this->boundUserIds) && (
            $this->boundMode == ComplianceProgram::MODE_ALL || $this->boundMode == $this->mode
        )
        ) {

            $query->andWhereIn(sprintf('%s.id', $query->getRootAlias()), $this->boundUserIds);
        }

        if($withViews) {
            foreach($this->viewGroups as $viewGroup) {
                foreach($viewGroup->getComplianceViews() as $view) {
                    $view->preQuery($query);
                }
            }
        }
    }

    public function getComplianceProgramRecord()
    {
        return $this->record;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function setComplianceProgramRecord(ComplianceProgramRecord $record = null)
    {
        $this->record = $record;

        $this->boundMode = $record->bind_mode;

        $this->boundUserIds = array();

        if($record->bind_user_ids) {
            foreach(explode(',', $record->bind_user_ids) as $userId) {
                $userId = trim($userId);

                if($userId) {
                    $this->boundUserIds[] = $userId;
                }
            }
        }
    }

    public function setDispatcher(sfEventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    protected function dispatch($event)
    {
        if($this->dispatcher) {
            $this->dispatcher->notify(new sfEvent($this, $event, array('user' => $this->activeUser)));
        }
    }

    protected function getMode()
    {
        return $this->mode;
    }

    protected function setBoundUserIds(array $ids, $mode)
    {
        $this->boundUserIds = $ids;
        $this->boundMode = $mode;
    }

    protected $options = array(
        'allow_teams'                    => false,
        'team_members_minimum'           => 1,
        'team_members_maximum'           => 999,
        'team_members_maximum_for_accepted_users'   => true,
        'team_members_invite_end_date'   =>  false,
        'team_create_end_date'          => false,
        'require_registration'           => false,
        'team_buddy_management_end_date' => false,
        'team_leaderboard'               => false,
        'points_label'                   => 'points',
        'total_steps'                    => true,
        'force_spouse_with_employee'     => false,
        'force_spouse_with_employee_excluded_spouses'  => false,
        'registration_redirect'          => false
    );

    private $mode = null;
    private $id;
    private $activeUser;
    private $startDate;
    private $endDate;
    private $viewGroups;
    private $complianceStatusMapper;
    private $record;
    private $dispatcher;
    private $boundUserIds = array();
    private $boundMode = null;
    private $doDispatch = true;
}