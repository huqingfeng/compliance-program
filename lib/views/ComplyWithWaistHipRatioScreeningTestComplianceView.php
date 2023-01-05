<?php
class ComplyWithWaistHipRatioScreeningTestComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate)
    {
        $this
            ->setStartDate($startDate)
            ->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'comply_with_waist_hip_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Waist to Hip Ratio';
    }

    public function getDefaultStatusSummary($status)
    {
        $gender = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser()->getGender();

        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                if($gender == 'M') {
                    return '< .90';
                } else if($gender == 'F') {
                    return '< .88';
                } else {
                    return null;
                }
                break;

            case ComplianceViewStatus::NOT_COMPLIANT:
                if($gender == 'M') {
                    return '≥ .90';
                } else if($gender == 'F') {
                    return '≥ .88';
                } else {
                    return null;
                }
                break;

            default:
                return null;
        }
    }

    public function getStatus(User $user)
    {
        $query = ScreeningTable::getInstance()
            ->getScreeningsForUser($user, array('execute' => false));
        $screenings = ScreeningTable::getInstance()
            ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
        $screening = $screenings->getFirst();

        if(!$screening || $screening->getWaist() === null || $screening->getHips() === null || (double) $screening->getHips() === 0) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        } else {
            $fieldValue = (double) $screening->getWaist() / (double) $screening->getHips();

            if(($fieldValue < .88 && $user->getGender() == 'M') || ($fieldValue < .90 && $user->getGender() == 'F')) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, $fieldValue);
            } else {
                return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $fieldValue);
            }
        }
    }
}
