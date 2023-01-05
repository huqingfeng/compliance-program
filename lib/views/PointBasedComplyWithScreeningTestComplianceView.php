<?php
/**
 * This class evaluates a subclass of ComplyWithScreeningTestComplianceView
 * for different ranges. This allows you to assign any arbitrary number of
 * points to any arbitrary number of ranges.
 */
class PointBasedComplyWithScreeningTestComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $className)
    {
        if(!class_exists($className)) {
            throw new \InvalidArgumentException("Class does not exist: $className");
        }

        $this->setDateRange($startDate, $endDate);
        $this->className = $className;
    }

    public function addDefaultStatusSummaryForGender($status, $gender, $summary)
    {
        if($gender == 'E') {
            $this->defaultStatuses[$status]['M'] = $summary;
            $this->defaultStatuses[$status]['F'] = $summary;
        } else {
            $this->defaultStatuses[$status][$gender] = $summary;
        }
    }

    public function addRange($points, $low, $high, $gender = 'E')
    {
        $this->ranges[] = array(
            'points' => $points,
            'low'    => $low,
            'high'   => $high,
            'gender' => $gender
        );
    }

    public function getDefaultName()
    {
        return "pb_{$this->className}";
    }

    public function getDefaultReportName()
    {
        return "Point Based {$this->className}";
    }

    public function getDefaultStatusSummary($status)
    {
        $user = $this->getComplianceViewGroup() ?
            $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser() :
            null;

        if($user && isset($this->defaultStatuses[$status][$user->gender])) {
            return $this->defaultStatuses[$status][$user->gender];
        } else {
            $summaries = array();

            if($user) {
                foreach($this->ranges as $range) {
                    if($status == $range['points'] && ($range['gender'] == 'E' || $user->gender == $range['gender'])) {
                        $view = $this->getView($range['low'], $range['high'], $range['gender']);

                        if(($summary = $view->getStatusSummary(ComplianceStatus::COMPLIANT)) !== null) {
                            $summaries[] = $summary;
                        }
                    }
                }
            }

            return count($summaries) ? implode(',', $summaries) : null;
        }
    }

    public function getStatus(User $user)
    {
        foreach($this->ranges as $range) {
            $view = $this->getView($range['low'], $range['high'], $range['gender']);

            $viewStatus = $view->getStatus($user);

            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $viewStatus->setPoints($range['points']);
                $viewStatus->setStatus(null);

                return $viewStatus;
            }
        }

        $viewStatus = $this->getView(0, 0, 'E')->getStatus($user);
        $viewStatus->setStatus(null);
        $viewStatus->setPoints(0);

        return $viewStatus;
    }

    public function getStatusPointValue($status)
    {
        return $status;
    }

    public function getSummarizableStatuses()
    {
        $points = array();

        foreach($this->ranges as $range) {
            $points[$range['points']] = true;
        }

        return array_keys($points);
    }

    public function setUseHraFallback($boolean)
    {
        $this->useHraFallback = $boolean;
    }

    public function setIndicateSelfReportedResults($bool)
    {
        $this->indicateSr = $bool;
    }

    /**
     * @param mixed $low
     * @param mixed $high
     * @param mixed $gender
     * @return ComplyWithScreeningTestComplianceView
     * @throws \RuntimeException
     */
    private function getView($low, $high, $gender)
    {
        $className = $this->className;

        $view = new $className($this->getStartDateGetter(), $this->getEndDateGetter());
        $view->setComplianceViewGroup($this->getComplianceViewGroup());

        if(!($view instanceof ComplyWithScreeningTestComplianceView)) {
            throw new \RuntimeException("Class must be a subclass of ComplyWithScreeningTestComplianceView");
        }

        $view->setIndicateSelfReportedResults($this->indicateSr);

        $view->setUseHraFallback($this->useHraFallback);

        $view->overrideTestRowData(
            null,
            $low,
            $high,
            null,
            $gender
        );

        return $view;
    }

    private $indicateSr = true;
    private $useHraFallback = false;
    private $defaultStatuses = array();
    private $ranges = array();
    private $className;
}