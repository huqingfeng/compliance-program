<?php

class ProgramStatusEvaluatorComplianceView extends ComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function __construct(ComplianceProgram $program, $groupsRequired = array(), $minimumPoints = null, $countPointsFromGroupsRequired = true)
    {
        $this->evaluationProgram = $program;
        $this->groupsComplianceRequired = $groupsRequired;
        $this->pointsRequired = $minimumPoints;
        $this->countPointsFromGroupsRequired = $countPointsFromGroupsRequired;
    }

    public function getStartDate($format = 'U')
    {
        return $this->evaluationProgram->getStartDate($format);
    }

    public function getEndDate($format = 'U')
    {
        return $this->evaluationProgram->getEndDate($format);
    }

    public function getDefaultReportName()
    {
        if($this->getName() == $this->getDefaultName()) {
            return 'Through '.$this->evaluationProgram->getEndDate('m/d/Y');
        } else {
            return sprintf('%s (%s-%s)', sfInflector::humanize($this->getName()), $this->evaluationProgram->getStartDate('m/d/Y'), $this->evaluationProgram->getEndDate('m/d/Y'));
        }
    }

    public function getDefaultName()
    {
        return 'program_status_evaluator'.$this->evaluationProgram->getStartDate('U').$this->evaluationProgram->getEndDate('U').$this->pointsRequired;
    }

    public function getPointsRequired()
    {
        return $this->pointsRequired;
    }

    public function getStatus(User $user)
    {
        $this->evaluationProgram->setActiveUser($this->getComplianceViewGroup()->getComplianceProgram()
            ->getActiveUser());
        $overallStatus = $this->evaluationProgram->getStatus();

        $finishedGroups = true;
        $pointsToRemove = 0;

        foreach($this->groupsComplianceRequired as $group) {
            $groupStatus = $overallStatus->getComplianceViewGroupStatus($group);
            $finishedGroups = $finishedGroups && $groupStatus->isCompliant();

            $pointsToRemove += $this->countPointsFromGroupsRequired ? 0 : $groupStatus->getPoints();
        }

        $numberOfPoints = $overallStatus->getPoints() - $pointsToRemove;

        if(($this->pointsRequired === null || $numberOfPoints >= $this->pointsRequired) && $finishedGroups) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, $numberOfPoints);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, $numberOfPoints);
        }
    }

    private $evaluationProgram;
    private $pointsRequired;
    private $groupsComplianceRequired;
    private $countPointsFromGroupsRequired;
}