<?php
class ComplyWithCoreBiometricsComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate, $screening = null)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
        $this->screeningMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        if($screening !== null) {
            $this->passedScreening = $screening;
        }
    }

    public function setScreeningComplianceStatusPointMapper(ComplianceStatusPointMapper $mapper)
    {
        $this->screeningMapper = $mapper;

        return $this;
    }

    public function setClasses($array)
    {
        $this->classes = $array;

        return $this;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getComplianceViews()
    {
        $views = array();
        $group = $this->getComplianceViewGroup();

        foreach($this->classes as $class) {
            $view = new $class($this->getStartDate(), $this->getEndDate(), $this->passedScreening);
            $view->setComplianceStatusPointMapper($this->screeningMapper);

            if($group) {
                $view->setComplianceViewGroup($this->getComplianceViewGroup());
            }

            $views[] = $view;
        }

        return $views;
    }

    public function getComplianceViewStatuses(User $user)
    {
        $statuses = array();

        foreach($this->getComplianceViews() as $view) {
            $statuses[$view->getName()] = $view->getMappedStatus($user);
        }

        return $statuses;
    }

    public function getStatus(User $user)
    {
        $points = null;

        foreach($this->getComplianceViewStatuses($user) as $status) {
            $points = $points === null ? $status->getPoints() : ($points + $status->getPoints());
        }

        if($this->points !== null) {
            if($points >= $this->points) {
                $status = ComplianceStatus::COMPLIANT;
            } else if($points > 0) {
                $status = ComplianceStatus::PARTIALLY_COMPLIANT;
            } else {
                $status = ComplianceStatus::NOT_COMPLIANT;
            }

            $points = null;
        } else {
            $status = null;
        }

        return new ComplianceViewStatus($this, $status, $points);
    }

    public function setPointThreshold($points)
    {
        $this->points = $points;
    }

    public function getDefaultStatusSummary($status) { return null; }

    public function getDefaultName()
    {
        return 'comply_with_core_biometrics';
    }

    public function getDefaultReportName()
    {
        return 'Comply with core biometrics';
    }

    private $points = null;
    private $screeningMapper;
    private $passedScreening = null;
    private $classes = array(
        'ComplyWithTotalCholesterolScreeningTestComplianceView',
        'ComplyWithHDLScreeningTestComplianceView',
        'ComplyWithLDLScreeningTestComplianceView',
        'ComplyWithTriglyceridesScreeningTestComplianceView',
        'ComplyWithGlucoseScreeningTestComplianceView',
        'ComplyWithBloodPressureScreeningTestComplianceView',
        'ComplyWithBodyFatBMIScreeningTestComplianceView',
        'ComplyWithCotinineScreeningTestComplianceView'
    );
}