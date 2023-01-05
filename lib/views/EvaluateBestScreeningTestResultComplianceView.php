<?php

use hpn\wms\model\ScreeningTestModel;

class EvaluateBestScreeningTestResultComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct(ComplianceScreeningModel $screeningModel, array $testNames, $startDate, $endDate, $alias = 'default')
    {
        if(!count($testNames)) {
            throw new \InvalidArgumentException('You must pass at least one test name.');
        }

        $this->screeningModel = $screeningModel;

        $this->setDateRange($startDate, $endDate);

        $this->testNames = $testNames;

        $this->alias = $alias;

        foreach($this->testNames as $testName) {
            $this->views[$testName] = new EvaluateScreeningTestResultComplianceView($this->screeningModel, $testName, $this->getStartDate(), $this->getEndDate(), $this->alias);
        }
    }

    public function getDefaultName()
    {
        $defaultName = implode('_', $this->testNames);

        return "comply_with_best_screening_test_{$defaultName}";
    }

    public function getDefaultReportName()
    {
        $nameComponents = array();

        foreach($this->getViews() as $view) {
            $nameComponents[] = $view->getReportName();
        }

        return implode('/', $nameComponents);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $mapStatuses = array(
            ComplianceStatus::COMPLIANT           => 100,
            ComplianceStatus::NA_COMPLIANT        => 80,
            ComplianceStatus::PARTIALLY_COMPLIANT => 60,
            ComplianceStatus::NOT_COMPLIANT       => 40,
            null                                  => 0
        );

        $bestViewStatus = null;

        if($this->defaultTestName) {
            if(is_callable($this->defaultTestName)) {
                $defaultTestName = call_user_func($this->defaultTestName, $user);
            } else {
                $defaultTestName = $this->defaultTestName;
            }

            if($defaultTestName) {
                $view = $this->getView($defaultTestName);

                if ($view) {
                    $defaultTestStatus = $view->getStatus($user);

                    if ($defaultTestStatus->getAttribute('has_result')) {
                        $bestViewStatus = $defaultTestStatus;
                    }
                }
            }
        }

        if($bestViewStatus === null) {
            foreach($this->getViews() as $view) {
                $viewStatus = $view->getStatus($user);

                if(!$bestViewStatus) {
                    $bestViewStatus = $viewStatus;
                } elseif($viewStatus->getPoints() > $bestViewStatus->getPoints()) {
                    $bestViewStatus = $viewStatus;
                } elseif($viewStatus->getPoints() == $bestViewStatus->getPoints() && $mapStatuses[$viewStatus->getStatus()] > $mapStatuses[$bestViewStatus->getStatus()]) {
                    $bestViewStatus = $viewStatus;
                } elseif($bestViewStatus->getPoints() === null && $mapStatuses[$viewStatus->getStatus()] > $mapStatuses[$bestViewStatus->getStatus()]) {
                    $bestViewStatus = $viewStatus;
                } else if(!$bestViewStatus->getAttribute('has_result') && $viewStatus->getAttribute('has_result')) {
                    $bestViewStatus = $viewStatus;
                }
            }
        }

        return new ComplianceViewStatus(
            $this,
            $bestViewStatus->getStatus(),
            $bestViewStatus->getPoints(),
            sprintf('%s: %s', $bestViewStatus->getComplianceView()->getTestAbbreviation(), $bestViewStatus->getComment())
        );
    }

    public function getView($name)
    {
        $views = $this->getViews();

        if(!isset($views[$name])) {
            throw new \InvalidArgumentException("Invalid view: {$name}");
        }

        return $views[$name];
    }

    /**
     * @return array
     */
    public function getViews()
    {
        foreach($this->views as $view) {
            $view->setComplianceViewGroup($this->getComplianceViewGroup());
        }

        return $this->views;
    }

    /**
     * If called, the configured test will always be used for points if there
     * is a result.
     *
     * @param $testName
     */
    public function setDefaultTestName($testName)
    {
        $this->defaultTestName = $testName;
    }

    public function setRequiredTests(array $tests)
    {
        foreach($this->views as $view) {
            $view->setRequiredTests($tests);
        }
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }

    protected $defaultTestName;
    protected $views = array();
    protected $screeningModel;
    protected $screeningTestModel;
    protected $testNames;
    protected $alias;
    private $allowPointsOverride = null;
}