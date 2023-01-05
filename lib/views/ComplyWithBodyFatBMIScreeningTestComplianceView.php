<?php
class ComplyWithBodyFatBMIScreeningTestComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate, $screening = null)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->bodyFatView = new ComplyWithBodyFatScreeningTestComplianceView($this->getStartDateGetter(), $this->getEndDateGetter(), $screening);
        $this->bmiView = new ComplyWithBMIScreeningTestComplianceView($this->getStartDateGetter(), $this->getEndDateGetter(), $screening);
    }

    public function getDefaultName()
    {
        return 'comply_with_body_fat_bmi_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Body Fat/BMI';
    }

    public function setIndicateSelfReportedResults($bool)
    {
        $this->bodyFatView->setIndicateSelfReportedResults($bool);
        $this->bmiView->setIndicateSelfReportedResults($bool);
    }

    public function setBodyFatView(ComplianceView $view)
    {
        $this->bodyFatView = $view;
    }

    public function setBmiView(ComplianceView $view)
    {
        $this->bmiView = $view;
    }

    public function setFilter($filter)
    {
        $this->bmiView->setFilter($filter);
        $this->bodyFatView->setFilter($filter);
    }

    public function setBMITestFields($fields)
    {
        $this->bmiView->setFields($fields);
    }

    public function setBodyfatTestFields($fields)
    {
        $this->bodyFatView->setFields($fields);
    }

    public function setStartDate($value)
    {
        if($this->bodyFatView && $this->bmiView) {
            $this->bodyFatView->setStartDate($this->getStartDateGetter());
            $this->bmiView->setStartDate($this->getStartDateGetter());
        }

        return parent::setStartDate($value);
    }

    public function setEndDate($value)
    {
        if($this->bodyFatView && $this->bmiView) {
            $this->bodyFatView->setEndDate($this->getEndDateGetter());
            $this->bmiView->setEndDate($this->getEndDateGetter());
        }

        return parent::setEndDate($value);
    }

    public function setUseDateForComment($boolean)
    {
        $this->bodyFatView->setUseDateForComment($boolean);
        $this->bmiView->setUseDateForComment($boolean);
        $this->useDate = $boolean;
    }

    public function setUsePoints($bool)
    {
        $this->usePoints = $bool;
    }

    public function setUseHraFallback($boolean)
    {
        $this->bodyFatView->setUseHraFallback($boolean);
        $this->bmiView->setUseHraFallback($boolean);
    }

    public function setNoScreeningResultStatus($status)
    {
        $this->bodyFatView->setNoScreeningResultStatus($status);
        $this->bmiView->setNoScreeningResultStatus($status);
    }

    public function overrideBodyFatTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->bodyFatView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function overrideBMITestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->bmiView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
            case ComplianceViewStatus::PARTIALLY_COMPLIANT:
            case ComplianceViewStatus::NOT_COMPLIANT:
                $this->bodyFatView->setComplianceViewGroup($this->getComplianceViewGroup());
                $this->bmiView->setComplianceViewGroup($this->getComplianceViewGroup());

                $bodyFat = $this->bodyFatView->getDefaultStatusSummary($status);
                $bmi = $this->bmiView->getDefaultStatusSummary($status);

                $returnTexts = array();
                if($bodyFat !== null) {
                    $returnTexts[] = "Body Fat: {$bodyFat}";
                }

                if($bmi !== null) {
                    $returnTexts[] = "BMI: {$bmi}";
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

        $bodyFatStatus = $this->bodyFatView->getStatus($user);
        $bmiStatus = $this->bmiView->getStatus($user);

        $bodyFatPoints = $bodyFatStatus->getPoints();
        $bmiPoints = $bmiStatus->getPoints();

        $bodyFatValue = $bodyFatStatus->getComment();
        $bmiValue = $bmiStatus->getComment();

        $bodyFatConstant = $bodyFatStatus->getStatus();
        $bmiConstant = $bmiStatus->getStatus();

        $bfText = $this->useDate ? $bodyFatValue : 'Body Fat: '.$bodyFatValue;
        $bmiText = $text = $this->useDate ? $bmiValue : 'BMI: '.$bmiValue;

        if($this->usePoints && $bodyFatPoints == 0 && $bmiPoints == 0) {
            $finalComment = $bodyFatStatus->getAttribute('has_result') && !$bmiStatus->getAttribute('has_result') ?
                $bfText : $bmiText;

            $viewStatus = new ComplianceViewStatus($this, null, 0, $finalComment);
        } elseif($this->usePoints && $bodyFatPoints >= $bmiPoints) {
            $viewStatus = new ComplianceViewStatus($this, null, $bodyFatPoints, $bfText);
        } elseif($this->usePoints) {
            $viewStatus = new ComplianceViewStatus($this, null, $bmiPoints, $bmiText);
        } elseif($bodyFatConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $bfText);
        } elseif($bmiConstant === ComplianceViewStatus::COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $bmiText);
        } elseif($bodyFatConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, $bfText);
        } elseif($bmiConstant === ComplianceViewStatus::PARTIALLY_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, $bmiText);
        } elseif($bodyFatConstant === ComplianceViewStatus::NA_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, null, $bfText);
        } elseif($bmiConstant === ComplianceViewStatus::NA_COMPLIANT) {
            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, null, $bmiText);
        } else {
            $finalComment = $bodyFatStatus->getAttribute('has_result') && !$bmiStatus->getAttribute('has_result') ?
                $bfText : $bmiText;

            $viewStatus = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, $finalComment);
        }

        $viewStatus->setAttribute('has_result', $bodyFatStatus->getAttribute('has_result') || $bmiStatus->getAttribute('has_result'));
        $viewStatus->setAttribute('body_fat_date', $bodyFatStatus->getAttribute('date'));
        $viewStatus->setAttribute('bmi_date', $bmiStatus->getAttribute('date'));
        $viewStatus->setAttribute('date', $bmiStatus->getAttribute('date', $bodyFatStatus->getAttribute('date')));

        return $viewStatus;
    }

    protected $bodyFatView;
    protected $bmiView;
    private $useDate = false;
    private $usePoints = false;
}