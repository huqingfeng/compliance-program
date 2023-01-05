<?php

class ComplyWithBodyFatBMIWaistRatioScreeningTestComplianceView extends ComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->bodyFatView = new ComplyWithBodyFatScreeningTestComplianceView($this->startDate, $this->endDate);
        $this->bmiView = new ComplyWithBMIScreeningTestComplianceView($this->startDate, $this->endDate);
        $this->waistView = new ComplyWithWaistHipRatioScreeningTestComplianceView($this->startDate, $this->endDate);
    }

    public function getDefaultName()
    {
        return 'comply_with_body_fat_bmi_waist_hip_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Body Fat/BMI/Hip to Waist Ratio';
    }

    public function overrideBodyFatTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->bodyFatView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function overrideBMITestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->bmiView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function setUseHraFallback($boolean)
    {
        $this->bodyFatView->setUseHraFallback($boolean);
        $this->bmiView->setUseHraFallback($boolean);
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                $this->bodyFatView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->bmiView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->waistView->setComplianceViewGroup($this->getComplianceViewGroup());

                $bodyFat = $this->bodyFatView->getDefaultStatusSummary($status);
                $bmi = $this->bmiView->getDefaultStatusSummary($status);
                $waist = $this->waistView->getDefaultStatusSummary($status);

                $returnTexts = array();
                if($bodyFat !== null) {
                    $returnTexts[] = "Body Fat: {$bodyFat}";
                }

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
        $this->bodyFatView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->bmiView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->waistView->setComplianceViewGroup($this->getComplianceViewGroup());

        $bodyFatStatus = $this->bodyFatView->getStatus($user);
        $bmiStatus = $this->bmiView->getStatus($user);
        $waistStatus = $this->waistView->getStatus($user);

        $bodyFatConstant = $bodyFatStatus->getStatus();
        $bmiConstant = $bmiStatus->getStatus();
        $waistConstant = $waistStatus->getStatus();

        $bodyFatResult = $bodyFatStatus->getComment();
        $bmiResult = $bmiStatus->getComment();
        $waistResult = $waistStatus->getComment();


        if($bodyFatConstant === ComplianceViewStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Body Fat: '.$bodyFatResult);
        } else if($bmiConstant === ComplianceViewStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BMI: '.$bmiResult);
        } else if($waistConstant === ComplianceViewStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Waist to Hip Ratio: '.$waistResult);
        } /*
    else if($bodyFatConstant === ComplianceViewStatus::NA_COMPLIANT) {
      return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, 'Using Body Fat');
    }
    else if($bmiConstant === ComplianceViewStatus::NA_COMPLIANT) {
      return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, 'Using BMI');
    }
    else if($waistConstant === ComplianceViewStatus::NA_COMPLIANT) {
      return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, 'Using Waist to Hip Ratio');
    }*/
        else if($bodyFatConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Body Fat: '.$bodyFatResult);
        } else if($bmiConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'BMI: '.$bmiResult);
        } else if($waistConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'Waist to Hip Ratio: '.$waistResult);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, 'BMI: '.$bmiResult);
        }
    }

    private $startDate;
    private $endDate;
    private $bodyFatView;
    private $bmiView;
    private $waistView;
}