<?php

class UpdateDoctorInformationComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate)->setEndDate($endDate);
        $this->addLink(new Link('Enter/Update Info', '/my_account/updateDoctor?redirect=/compliance_programs'));
    }

    public function getDefaultName()
    {
        return 'update_doctor_information';
    }

    public function getDefaultReportName()
    {
        return 'Update Doctor Information';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Update Doctor Information' : null;
    }

    public function getStatus(User $user)
    {
        $startDate = $this->getStartDate('U');
        $endDate = $this->getEndDate('U');

        foreach($user->getDataRecords('doctor_information_update') as $userDataRecord) {
            // Ignoring seconds
            $viewDate = strtotime(date('Y-m-d', strtotime($userDataRecord->getCreationDate())));

            if($startDate <= $viewDate && $endDate >= $viewDate) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, date('m/d/Y', $viewDate));
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);

    }
}