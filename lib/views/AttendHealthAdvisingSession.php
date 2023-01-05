<?php
class AttendHealthAdvisingSession extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::ATTEND_HEALTH_ADVISING_SESSION);
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