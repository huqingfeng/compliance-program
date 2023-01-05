<?php
/**
 * Holds many ComplianceViews and belongs to a ComplianceProgram.
 */
class ComplianceViewGroup
{
    public function __toString()
    {
        return (string) $this->getReportName();
    }

    /**
     * Creates an instance of this compliance group by forwarding arguments to
     * the constructor and returning the created instance.
     * This is useful in making the API chainable, as one can do e.g.
     * CompleteHRA::create(xxx,yyy)->setStartDate('xxx')->setEndDate('xxx')->setReportName('complete it')
     *
     * @return ComplianceViewGroup
     */
    public static function create()
    {
        $r = new ReflectionClass(__CLASS__);

        return $r->newInstanceArgs(func_get_args());
    }

    public function __construct($name, $reportName = null)
    {
        $this->name = $name;
        $this->reportName = $reportName === null ? $name : $reportName;
        $this->views = array();
        $this->totalEvaluatorViews = array();
        $this->pointsRequiredForCompliance = null;
    }

    public function getStatusForUser(User $user)
    {
        $allowPartial = $this->getComplianceProgram()->hasPartiallyCompliantStatus();

        $groupStatus = new ComplianceViewGroupStatus($this);

        foreach($this->getComplianceViews() as $view) {
            $viewStatus = $view->getMappedStatus($user);

            if(!$allowPartial && $viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                $viewStatus->setPoints($viewStatus->getPoints());
                $viewStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

            $groupStatus->addComplianceViewStatus($viewStatus);
        }

        $this->importStatus($groupStatus);

        return $groupStatus;
    }

    public function getStatus()
    {
        $user = $this->getComplianceProgram()->getActiveUser();

        return $this->getStatusForUser($user);
    }

    public function importStatus(ComplianceViewGroupStatus $status)
    {
        $this->evaluateAndStoreOverallStatus($status);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceViewGroupStatus $status)
    {
        $compliantInAllViews = true;
        $points = null;
        $compliant = null;
        $viewsCompliant = 0;

        foreach($status->getComplianceViewStatuses() as $viewStatus) {
            if(!isset($this->totalEvaluatorViews[$viewStatus->getComplianceView()->getName()])) {
                $viewPoints = $viewStatus->getPoints();
                $compliant = $viewStatus->isCompliant();

                if(!$compliant) {
                    $compliantInAllViews = false;
                } else {
                    $viewsCompliant++;
                }

                if($viewPoints !== null) {
                    if($points === null) {
                        $points = 0;
                    }

                    $points += $viewPoints;
                }
            }
        }

        $pointsRequired = $this->getPointsRequiredForCompliance();

        if($pointsRequired === null) {
            if($this->viewsRequired === null) {
                $compliant = $compliantInAllViews;
            } else {
                $compliant = $viewsCompliant >= $this->viewsRequired;
            }
        } else {
            $compliant = $points !== null && $points >= $pointsRequired;
        }

        $status->setStatus($compliant ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);

        // Max points applys only if explicitly configured - we don't use the
        // auto-derived value that is the result of calling getMaximumNumberOfPoints()

        if($this->maximumNumberOfPoints !== null && $points !== null && $points > $this->maximumNumberOfPoints) {
            $status->setPoints($this->maximumNumberOfPoints);
        } else {
            $status->setPoints($points);
        }
    }

    public function setNumberOfViewsRequired($number)
    {
        $this->viewsRequired = $number;
    }

    public function getPointsRequiredForCompliance()
    {
        $pointsRequired = $this->pointsRequiredForCompliance;

        if($pointsRequired !== null && !is_numeric($pointsRequired) && is_callable($pointsRequired)) {
            return call_user_func(
                $pointsRequired, $this->getComplianceProgram()->getActiveUser()
            );
        } else {
            return $pointsRequired;
        }
    }

    public function getMaximumNumberOfPoints()
    {
        $points = null;

        foreach($this->getComplianceViews() as $view) {
            if(!isset($this->totalEvaluatorViews[$view->getName()])) {
                $viewPoints = $view->getMaximumNumberOfPoints();

                if($viewPoints !== null) {
                    $points = $points === null ? $viewPoints : $points + $viewPoints;
                }
            }
        }

        if($points !== null && $this->maximumNumberOfPoints !== null && $points > $this->maximumNumberOfPoints) {
            return $this->maximumNumberOfPoints;
        } else {
            return $points;
        }
    }

    /**
     * @param int $v
     * @return ComplianceViewGroup This instance.
     */
    public function setPointsRequiredForCompliance($v)
    {
        $this->pointsRequiredForCompliance = $v;

        return $this;
    }

    /**
     * @return boolean
     */
    public function pointBased()
    {
        return $this->pointsRequiredForCompliance !== null;
    }

    /**
     * Removes a view from this group.
     *
     * @param string $name
     * @return ComplianceViewGroup
     */
    public function removeComplianceView($name)
    {
        unset($this->views[$name]);
        unset($this->totalEvaluatorViews[$name]);

        return $this;
    }

    public function hasLinks()
    {
        foreach($this->views as $view) {
            if($view->hasLinks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ComplianceProgram
     */
    public function getComplianceProgram()
    {
        return $this->complianceProgram;
    }

    /**
     * Sets an arbitrary attribute to a value that can be obtained later.
     *
     * @param string $name
     * @param mixed $value
     * @return ComplianceViewGroup This instance.
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function setMaximumNumberOfPoints($points)
    {
        $this->maximumNumberOfPoints = $points;
    }

    /**
     * Returns given attribute or null
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * @param ComplianceProgram $program
     * @return ComplianceViewGroup This instance.
     */
    public function setComplianceProgram(ComplianceProgram $program)
    {
        $this->complianceProgram = $program;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getReportName()
    {
        return $this->reportName;
    }

    /**
     * @param string $v
     * @return ComplianceViewGroup
     */
    public function setName($v)
    {
        $this->name = $v;

        return $this;
    }

    /**
     * @param string $v
     * @return ComplianceViewGroup
     */
    public function setReportName($v)
    {
        $this->reportName = $v;

        return $this;
    }

    public function setEvaluateCallback($callback, $status = ComplianceStatus::NA_COMPLIANT, $comment = null)
    {
        foreach($this->getComplianceViews() as $view) {
            $view->setEvaluateCallback($callback, $status, $comment);
        }

        return $this;
    }

    /**
     * Adds a compliance view to the group.
     *
     * @param ComplianceView $v
     * @param boolean $totalEvaluator If this view is a total evaluator.
     * @param string $sectionText Views can be grouped by arbitrary text within a group. It is up to the printers to use this information.
     * @return ComplianceViewGroup This instance.
     */
    public function addComplianceView(ComplianceView $v, $totalEvaluator = false, $sectionText = null)
    {
        $this->views[$v->getName()] = $v;
        $v->setComplianceViewGroup($this);

        if($totalEvaluator) {
            $this->totalEvaluatorViews[$v->getName()] = true;
        }

        $this->viewSections[$sectionText][] = $v;

        return $this;
    }

    /**
     * @return array
     */
    public function getComplianceViews()
    {
        return $this->views;
    }

    public function getComplianceViewsBySection()
    {
        return $this->viewSections;
    }

    /**
     * @param string $name
     * @return ComplianceView
     */
    public function getComplianceView($name)
    {
        return isset($this->views[$name]) ? $this->views[$name] : null;
    }

    private $name;
    private $reportName;
    private $complianceProgram;
    private $views;
    private $viewSections = array();
    private $totalEvaluatorViews;
    private $pointsRequiredForCompliance;
    private $attributes = array();
    private $viewsRequired = null;
    private $maximumNumberOfPoints = null;
}