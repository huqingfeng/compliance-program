<?php

class CompleteGoalComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $this->addLink(new Link('Enter Info', '/content/10049'));
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete a goal.' : null;
    }

    public function getDefaultName()
    {
        return 'complete_gac_goal';
    }

    public function getDefaultReportName()
    {
        return 'Complete A Goal';
    }

    public function getStatus(User $user)
    {
        $goalQuery = '
      SELECT COUNT(*) as number_of_goals_completed
      FROM goals
      WHERE user_id = ?
      AND goalstatus = 3
      AND completiondate BETWEEN ? AND ?
    ';

        $_db = Piranha::getInstance()->getDatabase();
        $_db->executeSelect($goalQuery, $user->getID(), $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));
        $goalRow = $_db->getNextRow();

        if($goalRow['number_of_goals_completed'] > 0) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Done');
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, 'Not Done');
        }
    }
}