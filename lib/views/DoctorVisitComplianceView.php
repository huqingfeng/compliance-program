<?php
class DoctorVisitComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::DOCTOR_VISIT);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
        if(count($records) > 0) {
            $first = current($records);

            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $first->getDate());
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }
}