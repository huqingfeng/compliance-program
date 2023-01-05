<?php

class ViewHRAComplianceView extends DateBasedComplianceView
{
    public function __construct($s, $d)
    {
        $this
            ->setStartDate($s)
            ->setEndDate($d);
    }

    public function getStatus(User $user)
    {
        $logQuery = '
      SELECT created_at
      FROM request_logs
      WHERE user_id = ?
      AND request_uri REGEXP ?
      AND for_services = 0
      AND created_at BETWEEN ? AND ?
      ORDER BY id ASC
      LIMIT 1
    ';

        $db = Database::getDatabase();

        $db->executeSelect($logQuery,
            $user->getID(),
            'content/(751|752)',
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d')
        );

        if($row = $db->getNextRow()) {
            return new ComplianceViewStatus(
                $this,
                ComplianceStatus::COMPLIANT,
                null,
                date('m/d/Y', strtotime($row['created_at']))
            );
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'view_hra_report';
    }

    public function getDefaultReportName()
    {
        return 'View HRA';
    }
}
