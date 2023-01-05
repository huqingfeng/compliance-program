<?php

class ComplyWithLDLTotalHDLCholesterolScreeningTestComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->ldlView = new ComplyWithLDLScreeningTestComplianceView(
            $this->getStartDateGetter(),
            $this->getEndDateGetter()
        );

        $this->ratioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView(
            $this->getStartDateGetter(),
            $this->getEndDateGetter()
        );
    }

    public function getDefaultName()
    {
        return 'comply_with_ldl_total_hdl_cholesterol_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'LDL/Total/HDL Ratio';
    }

    public function overrideLDLTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->ldlView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function overrideTotalHDLCholesterolRatioTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->ratioView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
            case ComplianceViewStatus::PARTIALLY_COMPLIANT:

                $this->ldlView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->ratioView->setComplianceViewGroup($this->getComplianceViewGroup());

                $total = $this->ldlView->getDefaultStatusSummary($status);
                $ratio = $this->ratioView->getDefaultStatusSummary($status);

                $returnTexts = array();
                if($total !== null) {
                    $returnTexts[] = "LDL: {$total}";
                }

                if($ratio !== null) {
                    $returnTexts[] = "Total/HDL Ratio: {$ratio}";
                }

                if(count($returnTexts) > 0) {
                    return implode(', ', $returnTexts);
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
        $this->ldlView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->ratioView->setComplianceViewGroup($this->getComplianceViewGroup());

        $ldlStatus = $this->ldlView->getStatus($user);
        $ratioStatus = $this->ratioView->getStatus($user);

        $ldlValue = $ldlStatus->getComment();
        $ratioValue = $ratioStatus->getComment();

        $ldlConstant = $ldlStatus->getStatus();
        $ratioConstant = $ratioStatus->getStatus();

        if($ldlConstant === ComplianceViewStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'LDL: '.$ldlValue);
        } else if($ratioConstant === ComplianceViewStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Total/HDL Chol Ratio: '.$ratioValue);
        } else if($ldlConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'LDL: '.$ldlValue);
        } else if($ratioConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Total/HDL Chol Ratio: '.$ratioValue);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, 'LDL: '.$ldlValue);
        }
    }

    private $ldlView;
    private $ratioView;
}
