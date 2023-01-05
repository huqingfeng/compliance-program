<?php

class AFSCMEViewWorkbookComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start)->setEndDate($end);
        $this->addLink(new Link('View Health Navigator', '/content/12056'));
    }

    public function getDefaultName()
    {
        return 'view_workbook';
    }

    public function getDefaultReportName()
    {
        return 'View Workbook';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $numberOfViews = 0;

        $monthsViewed = array();

        $status = null;

        foreach($user->getDataRecords('workbook_view') as $userDataRecord) {
            $viewDate = strtotime(date('Y-m-d', strtotime($userDataRecord->getCreationDate()))); // Ignore seconds

            if($startDate <= $viewDate && $endDate >= $viewDate) {
                $formattedDate = date('m/d/Y', $viewDate);

                $monthsViewed[date('Y-m', $viewDate)] = true;

                $numberOfViews++;

                if($status === null) {
                    $status = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $formattedDate);
                }
            }
        }

        if($status === null) {
            $status = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }

        $status->setAttribute('number_of_views', $numberOfViews);
        $status->setAttribute('months_viewed', array_keys($monthsViewed));

        return $status;
    }
}