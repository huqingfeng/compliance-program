<?php

class CompleteGoalsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerGoal)
    {
        $this
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->addLink(new Link('Enter Information', '/content/10049'))
            ->pointsPerGoal = $pointsPerGoal;
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                return 'Complete a goal.';
                break;
            default:
                return null;
        }
    }

    public function getDefaultName()
    {
        return 'complete_gac_goals';
    }

    public function getDefaultReportName()
    {
        return 'Complete Goals';
    }

    public function getStatus(User $user)
    {
        $goalQuery = "
      SELECT COUNT(*) as number_of_goals_completed
      FROM goals
      WHERE user_id = ?
      AND goalstatus = 3
      AND completiondate BETWEEN ? AND ?
    ";

        $_db = Piranha::getInstance()->getDatabase();
        $_db->executeSelect($goalQuery, $user->getID(), $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));
        $goalRow = $_db->getNextRow();
        $numberOfPoints = $this->pointsPerGoal * $goalRow['number_of_goals_completed'];

        return new ComplianceViewStatus($this, null, $numberOfPoints);
    }

    private $pointsPerGoal;
}
