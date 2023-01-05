<?php

class DCMH2011RequiredCholesterolComplianceViewTemp extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultName()
    {
        return 'employee_required_cholesterol';
    }

    public function getDefaultReportName()
    {
        return 'TC/HDL Ratio & LDL Cholesterol';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceStatus::COMPLIANT ? '≤5.2 for Ratio AND ≤135 for LDL' : null;
    }

    public function getStatus(User $user)
    {
        // Evaluate LDL, HDL, Total
        // Compliant in all 3 => compliant
        // NA in all of 3 => NA
        // Else red

        $ratioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($this->startDate, $this->endDate);
        $ratioView->setComplianceViewGroup($this->getComplianceViewGroup());
        $ratioView->overrideTestRowData(0, null, 5.2, null);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($this->startDate, $this->endDate);
        $ldlView->setComplianceViewGroup($this->getComplianceViewGroup());
        $ldlView->overrideTestRowData(0, null, 135, null);

        $statusCounts = array_count_values(
            array(
                $ratioView->getStatus($user)->getStatus(),
                $ldlView->getStatus($user)->getStatus()
            )
        );

        if(isset($statusCounts[ComplianceStatus::COMPLIANT]) && $statusCounts[ComplianceStatus::COMPLIANT] == 2) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else if(isset($statusCounts[ComplianceStatus::NA_COMPLIANT]) && $statusCounts[ComplianceStatus::NA_COMPLIANT] == 2) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }

        return $parentStatus;
    }

    private $startDate;
    private $endDate;
}

class DCMH2011ComplianceProgramTemp extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $resourceGroup = new ComplianceViewGroup('Resource');

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $resourceGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 84, 100);
        $bloodPressureView->setUseHraFallback(true);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 134, 140);
        $resourceGroup->addComplianceView($bloodPressureView);

        $cholesterolView = new DCMH2011RequiredCholesterolComplianceViewTemp($programStart, $programEnd);
        $resourceGroup->addComplianceView($cholesterolView);

        $bmiBodyFatView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiBodyFatView->overrideBMITestRowData(0, 0, 27.4, 30);
        $bmiBodyFatView->setUseHraFallback(true);
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 27.4, 30, 'F');
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 19.7, 30, 'M');
        $resourceGroup->addComplianceView($bmiBodyFatView);

        $this->addComplianceViewGroup($resourceGroup);

    }

    private $statusPointMapper;
}