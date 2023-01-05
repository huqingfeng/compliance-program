<?php
class ComplyWithBloodPressureScreeningTestComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate, $screening = null)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView(
            $this->getStartDateGetter(),
            $this->getEndDateGetter(),
            $screening
        );

        $this->diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView(
            $this->getStartDateGetter(),
            $this->getEndDateGetter(),
            $screening
        );
    }

    public function getDefaultName()
    {
        return 'comply_with_blood_pressure_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Blood Pressure';
    }

    public function setFilter($filter)
    {
        $this->systolicView->setFilter($filter);
        $this->diastolicView->setFilter($filter);
    }

    public function setIndicateSelfReportedResults($bool)
    {
        $this->systolicView->setIndicateSelfReportedResults($bool);
        $this->diastolicView->setIndicateSelfReportedResults($bool);
    }

    public function setMergeScreenings($boolean)
    {
        $this->systolicView->setMergeScreenings($boolean);
        $this->diastolicView->setMergeScreenings($boolean);
    }

    public function setUseDateForComment($boolean)
    {
        $this->useDate = $boolean;
        $this->systolicView->setUseDateForComment($boolean);
        $this->diastolicView->setUseDateForComment($boolean);
    }

    public function setUseHraFallback($boolean)
    {
        $this->systolicView->setUseHraFallback($boolean);
        $this->diastolicView->setUseHraFallback($boolean);
    }

    public function setNoScreeningResultStatus($status)
    {
        $this->systolicView->setNoScreeningResultStatus($status);
        $this->diastolicView->setNoScreeningResultStatus($status);
    }

    public function overrideSystolicTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->systolicView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function overrideDiastolicTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->diastolicView->overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender);
    }

    public function setSystolicTestFields($fields)
    {
        $this->systolicView->setFields($fields);
    }

    public function setDiastolicTestFields($fields)
    {
        $this->diastolicView->setFields($fields);
    }

    public function getSystolicView()
    {
        return $this->systolicView;
    }

    public function getDiastolicView()
    {
        return $this->diastolicView;
    }

    public function addSystolicResultMapping($result, $status, $comment = null)
    {
        $this->systolicView->addResultMapping($result, $status, $comment);
    }

    public function addDiastolicResultMapping($result, $status, $comment = null)
    {
        $this->diastolicView->addResultMapping($result, $status, $comment);
    }

    public function getDefaultStatusSummary($status)
    {
        $this->systolicView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->diastolicView->setComplianceViewGroup($this->getComplianceViewGroup());
        $systolicStatus = $this->systolicView->getDefaultStatusSummary($status);
        $diastolicStatus = $this->diastolicView->getDefaultStatusSummary($status);

        if($systolicStatus === null || $diastolicStatus === null) {
            return null;
        } else {
            return "{$systolicStatus}/{$diastolicStatus}";
        }
    }

    public function setEitherNonCompliantYieldsNonCompliant($bool = true)
    {
        $this->eitherNonCompliantYieldsNonCompliant = $bool;
    }

    public function getStatus(User $user)
    {
        $this->systolicView->setComplianceViewGroup($this->getComplianceViewGroup());
        $this->diastolicView->setComplianceViewGroup($this->getComplianceViewGroup());

        $systolicStatus = $this->systolicView->getStatus($user);
        $diastolicStatus = $this->diastolicView->getStatus($user);

        $systolicStatusConstant = $systolicStatus->getStatus();
        $diastolicStatusConstant = $diastolicStatus->getStatus();

        $systolicCompliant = $systolicStatusConstant == ComplianceStatus::COMPLIANT;
        $diastolicCompliant = $diastolicStatusConstant == ComplianceStatus::COMPLIANT;

        $systolicPartiallyCompliant = $systolicStatusConstant == ComplianceStatus::PARTIALLY_COMPLIANT;
        $diastolicPartiallyCompliant = $diastolicStatusConstant == ComplianceStatus::PARTIALLY_COMPLIANT;

        $systolicNonCompliant = $systolicStatusConstant == ComplianceStatus::NOT_COMPLIANT;
        $diastolicNonCompliant = $diastolicStatusConstant == ComplianceStatus::NOT_COMPLIANT;

        $systolicNaCompliant = $systolicStatusConstant == ComplianceStatus::NA_COMPLIANT;
        $diastolicNaCompliant = $diastolicStatusConstant == ComplianceStatus::NA_COMPLIANT;

        $systolicComment = $systolicStatus->getComment();
        $diastolicComment = $diastolicStatus->getComment();

        $textToUse = null;
        if($systolicComment !== null && $diastolicComment !== null) {
            if($systolicComment == ComplyWithScreeningTestComplianceView::NO_SCREENING_TEXT || $systolicComment == ComplyWithScreeningTestComplianceView::TEST_NOT_TAKEN_TEXT) {
                $textToUse = $systolicComment;
            } else {
                if($this->useDate && $systolicComment == $diastolicComment) {
                    $textToUse = $systolicComment;
                } else if($this->useDate) {
                    $textToUse = "Systolic: $systolicComment, Diastolic: $diastolicComment";
                } else {
                    $textToUse = $systolicComment.'/'.$diastolicComment;
                }

            }
        }

        // Both compliant => compliant
        // Either compliant => partial
        // Both partial => partial
        // Else => red

        $status = null;

        if($this->eitherNonCompliantYieldsNonCompliant && ($systolicNonCompliant || $diastolicNonCompliant)) {
            $status = ComplianceStatus::NOT_COMPLIANT;
        } else if($systolicCompliant && $diastolicCompliant) {
            $status = ComplianceViewStatus::COMPLIANT;
        } else if(($systolicCompliant || $diastolicCompliant)) {
            $status = ComplianceViewStatus::PARTIALLY_COMPLIANT;
        } else if(($systolicPartiallyCompliant && $diastolicPartiallyCompliant)) {
            $status = ComplianceViewStatus::PARTIALLY_COMPLIANT;
        } else if($systolicNaCompliant || $diastolicNaCompliant) {
            $status = ComplianceViewStatus::NA_COMPLIANT;
        } else {
            $status = ComplianceViewStatus::NOT_COMPLIANT;
        }

        $viewStatus = new ComplianceViewStatus($this, $status, null, $textToUse);

        if($systolicStatus->getAttribute('has_result') && $diastolicStatus->getAttribute('has_result')) {
            $viewStatus->setAttribute('date', $systolicStatus->getAttribute('date', $diastolicStatus->getAttribute('date')));
        }

        $viewStatus->setAttribute('has_result', $systolicStatus->getAttribute('has_result') && $diastolicStatus->getAttribute('has_result'));

        $viewStatus->setAttribute('real_result', $textToUse);

        $viewStatus->setAttribute('systolic_real_result', $systolicStatus->getAttribute('real_result'));
        $viewStatus->setAttribute('diastolic_real_result', $diastolicStatus->getAttribute('real_result'));


        return $viewStatus;
    }

    private $systolicView;
    private $diastolicView;
    private $eitherNonCompliantYieldsNonCompliant = false;
    private $useDate = false;
}