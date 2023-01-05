<?php

class ComplyWithBMIWaistHipRatioScreeningTestComplianceView extends ComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->bmiView = new ComplyWithBMIScreeningTestComplianceView($this->startDate, $this->endDate);
        $this->waistView = new ComplyWithWaistHipRatioScreeningTestComplianceView($this->startDate, $this->endDate);
    }

    public function getDefaultName()
    {
        return 'comply_with_bmi_waist_hip_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'BMI/Hip to Waist Ratio';
    }


    public function overrideBMITestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->bmiView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function setUseHraFallback($boolean)
    {

        $this->bmiView->setUseHraFallback($boolean);
    }

    public function setWaistHipView(ComplianceView $view)
    {
        $this->waistView = $view;
    }

    public function setBmiView(ComplianceView $view)
    {
        $this->bmiView = $view;
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                $this->bmiView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->waistView->setComplianceViewGroup($this->getComplianceViewGroup());

                $bmi = $this->bmiView->getDefaultStatusSummary($status);
                $waist = $this->waistView->getDefaultStatusSummary($status);

                $returnTexts = array();

                if($bmi !== null) {
                    $returnTexts[] = "BMI: {$bmi}";
                }

                if($waist !== null) {
                    $returnTexts[] = "Waist/Hip Ratio: {$waist}";
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

        $this->bmiView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->waistView->setComplianceViewGroup($this->getComplianceViewGroup());


        $bmiStatus = $this->bmiView->getStatus($user);
        $waistStatus = $this->waistView->getStatus($user);


        $bmiConstant = $bmiStatus->getStatus();
        $waistConstant = $waistStatus->getStatus();


        $bmiResult = $bmiStatus->getComment();
        $waistResult = $waistStatus->getComment();


        $waistText = 'Waist to Hip Ratio: '.$waistResult;
        $bmiText = 'BMI: '.$bmiResult;

        if($bmiConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $bmiText);
        } else if($waistConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $waistText);
        } else if($bmiConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus =  new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, $bmiText);
        } else if($waistConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus =  new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, $waistText);
        } else {
            $finalComment = $waistStatus->getAttribute('has_result') && !$bmiStatus->getAttribute('has_result') ?
                $waistText : $bmiText;

            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, $finalComment);
        }

        $viewStatus->setAttribute('waist_has_result', $waistStatus->getAttribute('has_result'));
        $viewStatus->setAttribute('bmi_has_result',  $bmiStatus->getAttribute('has_result'));
        $viewStatus->setAttribute('waist_result', $waistResult);
        $viewStatus->setAttribute('bmi_result', $bmiResult);

        return $viewStatus;
    }

    private $startDate;
    private $endDate;
    protected $bmiView;
    protected $waistView;
}