<?php

class ViewHPAComplianceView extends DateBasedComplianceView
{
    public function __construct($s, $d)
    {
        $this
            ->setStartDate($s)
            ->setEndDate($d);
    }

    public function getStatus(User $user)
    {
        $logQuery = "
      SELECT COUNT(*) as views
      FROM request_logs
      WHERE user_id = ?
      AND request_uri REGEXP ?
      AND ip_address NOT BETWEEN INET_ATON('10.0.0.0') AND INET_ATON('10.255.255.255') 
    ";

        $db = Database::getDatabase();
        $db->executeSelect($logQuery, $user->getID(), 'content/(751|752)');

        $count = $db->getNextRow();

        if($count['views']) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
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
        return 'view_hpa_report';
    }

    public function getDefaultReportName()
    {
        return 'View HPA';
    }
}

class NSKComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $group
            ->addComplianceView(CompleteHRAComplianceView::create($start, $end))
            ->addComplianceView(CompleteScreeningComplianceView::create($start, $end))
            ->addComplianceView(ViewHPAComplianceView::create($start, '2010-10-31'))
            ->addComplianceView(
            ComplyWithCotinineScreeningTestComplianceView::create($start, $end)
                ->setNoTestResultStatus(ComplianceStatus::NOT_COMPLIANT)
                ->setName('cotinine')
                ->setReportName('Cotinine')
        );

        $this->addComplianceViewGroup($group);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new CHPComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(null, null, null, false, true, null, null, true);

        return $printer;
    }
}