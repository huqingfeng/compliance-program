<?php

class CompleteScreeningComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start);
        $this->setEndDate($end);

        $this->addLink(new Link('Sign-Up', '/content/1051'));
        $this->addLink(new Link('Results', '/content/989'));
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete Screening' : null;
    }

    public function getDefaultName()
    {
        return 'complete_screening';
    }

    public function getDefaultReportName()
    {
        return 'Complete Screening';
    }

    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => $this->requireOnlineEntry,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter()
            )
        );

        return $data;
    }

    public function getStatus(User $user)
    {
        $data = $this->getData($user);

        $status = ComplianceStatus::NOT_COMPLIANT;
        $comment = null;
        $attributes = array();

        if(isset($data) && $data) {
            $status = $this->evaluateStatus($user, $data);

            if($status == ComplianceStatus::COMPLIANT) {
                $comment = date('m/d/Y', strtotime($data['date']));

                $attributes['id'] = $data['id'];
                $attributes['merge_ids'] = $data['merge_ids'];
                $attributes['range_ids'] = $data['range_ids'];
            }
        }

        if($status == ComplianceStatus::NOT_COMPLIANT && $this->checkAppointments) {
            $aptView = new ScheduleAppointmentComplianceView($this->getStartDate(), $this->getEndDate());
            $aptView->setAppointmentTypeId(1);

            // @TODO appointments hack to bind to typeid 1

            $aptStatus = $aptView->getStatus($user);

            if($aptStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Scheduled '.$aptStatus->getComment());
            }
        }

        $viewStatus = new ComplianceViewStatus($this, $status, null, $comment);

        foreach($attributes as $attrKey => $attrValue) {
            $viewStatus->setAttribute($attrKey, $attrValue);
        }

        return $viewStatus;
    }

    public function setCheckAppointmentsForPartial($boolean)
    {
        $this->checkAppointments = $boolean;

        return $this;
    }

    /**
     * @param boolean $boolean
     * @return CompleteScreeningComplianceView
     */
    public function setRequireOnlineEntry($boolean)
    {
        $this->requireOnlineEntry = $boolean;

        return $this;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Determines if a screening data row qualifies for credit. Do not evaluate
     * the date range / done field here as that is already done. This is meant
     * to be overridden by subclasses.
     *
     * @param User $user
     * @param array $array
     * @return int
     */
    protected function evaluateStatus(User $user, $array)
    {
        return Screening::dataHasNonBiometricResults($array) ?
            ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;
    }

    protected function getFilter()
    {
        return $this->filter !== null ?
            $this->filter : function(array $screening) { return true; };
    }

    private $filter;
    private $checkAppointments = false;
    private $requireOnlineEntry = false;
}