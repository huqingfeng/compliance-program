<?php

class UpdateContactInformationComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->addLink(new Link('Enter/Update Info', '/my_account/updateAll?redirect=/compliance_programs'));
    }

    public function getStatus(User $user)
    {
        $startDate = $this->getStartDate('U');
        $endDate = $this->getEndDate('U');

        foreach($user->getDataRecords('preference_update') as $userDataRecord) {
            // Ignoring seconds
            $viewDate = strtotime(date('Y-m-d', strtotime($userDataRecord->getCreationDate())));

            if($startDate <= $viewDate && $endDate >= $viewDate) {
                $formattedViewDate = date('m/d/Y', $viewDate);

                $status = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $formattedViewDate);
                $status->setAttribute('date', $formattedViewDate);

                return $status;
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    public function getDefaultName()
    {
        return 'update_contact_information';
    }

    public function getDefaultReportName()
    {
        return 'Update Contact Information';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Update Contact Information' : null;
    }
}
