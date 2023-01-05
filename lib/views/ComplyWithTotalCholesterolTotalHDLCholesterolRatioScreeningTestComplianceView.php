<?php

class ComplyWithTotalCholesterolTotalHDLCholesterolRatioScreeningTestComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->totalView = new ComplyWithTotalCholesterolScreeningTestComplianceView(
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
        return 'comply_with_total_cholesterol_total_hdl_cholesterol_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Total Cholesterol/Total/HDL Ratio';
    }

    public function overrideTotalCholesterolTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->totalView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
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

                $this->totalView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->ratioView->setComplianceViewGroup($this->getComplianceViewGroup());

                $total = $this->totalView->getDefaultStatusSummary($status);
                $ratio = $this->ratioView->getDefaultStatusSummary($status);

                $returnTexts = array();
                if($total !== null) {
                    $returnTexts[] = "Total Chol: {$total}";
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
        $this->totalView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->ratioView->setComplianceViewGroup($this->getComplianceViewGroup());

        $totalStatus = $this->totalView->getStatus($user);
        $ratioStatus = $this->ratioView->getStatus($user);

        $totalValue = $totalStatus->getComment();
        $ratioValue = $ratioStatus->getComment();

        $totalConstant = $totalStatus->getStatus();
        $ratioConstant = $ratioStatus->getStatus();

        if($totalConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Total Chol: '.$totalValue);
        } else if($ratioConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Total/HDL Chol Ratio: '.$ratioValue);
        } else if($totalConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Total Chol: '.$totalValue);
        } else if($ratioConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Total/HDL Chol Ratio: '.$ratioValue);
        } else {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, 'Total Chol: '.$totalValue);
        }


        $viewStatus->setAttribute('has_result', $totalStatus->getAttribute('has_result') && $ratioStatus->getAttribute('has_result'));

        return $viewStatus;
    }

    private $totalView;
    private $ratioView;
}
