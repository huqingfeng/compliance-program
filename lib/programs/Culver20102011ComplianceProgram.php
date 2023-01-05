<?php

class Culver20102011ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $complianceStatusMapper = new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Healthy Range', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Borderline', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('At-Risk', '/images/lights/redlight.gif')
        ));

        $this->setComplianceStatusMapper($complianceStatusMapper);

        $requiredGroupEndDate = strtotime('2011-02-15');
        $requiredGroup = new ComplianceViewGroup('one', sprintf('Premium Contribution Reward - Actions Required by %s:', date('F d, Y', $requiredGroupEndDate)));

        $screeningView = new CompleteScreeningComplianceView($this->getStartDate(), $requiredGroupEndDate);
        $screeningView
            ->setReportName('Annual Wellness Screening')
            ->emptyLinks()
            ->addLink(new Link('Results', '/content/989'));
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($this->getStartDate(), $requiredGroupEndDate);
        $hraView->setReportName('Annual Health Power Assessment');
        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);


        $screeningGroup = new ComplianceViewGroup('two', sprintf('Flex Benefit Reward -- Earn 7 or more points by %s from A-J (below):', $this->getEndDate('F d, Y')));
        $screeningGroup->setPointsRequiredForCompliance(7);

        $cholesterolStatus = new ComplyWithTotalCholesterolScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $cholesterolStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $screeningGroup->addComplianceView($cholesterolStatus);

        $totalHDLRatioStatus = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $totalHDLRatioStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screeningGroup->addComplianceView($totalHDLRatioStatus);

        $triglyceridesStatus = new ComplyWithTriglyceridesScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $triglyceridesStatus->addLink(new Link('See all my Results', '/content/989'));
        $triglyceridesStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screeningGroup->addComplianceView($triglyceridesStatus);

        $glucoseStatus = new ComplyWithGlucoseScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $glucoseStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 1, 0, 0));
        $screeningGroup->addComplianceView($glucoseStatus);

        $bloodPressureStatus = new ComplyWithBloodPressureScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $bloodPressureStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureStatus->overrideSystolicTestRowData(null, null, 120, 139);
        $bloodPressureStatus->overrideDiastolicTestRowData(null, null, 80, 89);
        $screeningGroup->addComplianceView($bloodPressureStatus);

        $bmiBodyFatStatus = new ComplyWithBodyFatBMIScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $bmiBodyFatStatus->setReportName('Better of Body Fat or BMI');
        $bmiBodyFatStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $screeningGroup->addComplianceView($bmiBodyFatStatus);

        $smokingStatus = new ComplyWithSmokingHRAQuestionComplianceView('2010-08-01', '2011-03-31');
        $smokingStatus->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screeningGroup->addComplianceView($smokingStatus);

        $this->addComplianceViewGroup($screeningGroup);


        $requiredeLearning = new CompleteELearningLessonsComplianceView($this->getStartDate(), $this->getEndDate());
        $requiredeLearning->setPointsPerLesson(1);
        $requiredeLearning->setMaximumNumberOfPoints(5);

        $screeningGroup->addComplianceView($requiredeLearning);

        $aerobicExercise = new CulverCompleteAerobicExerciseComplianceView($this->getStartDate(), $this->getEndDate());

        $aerobicExercise->setReportName('Regular Exercise via Campus Fitness Center');
        $aerobicExercise->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(8, 0, 0, 0));
        $aerobicExercise->addLink(new Link('How to get credit', '/content/1175#fitCtr'));
        $screeningGroup->addComplianceView($aerobicExercise);

        $physicalExam = new CompletePreventionPhysicalExamComplianceView($this->getStartDate(), $this->getEndDate());

        $physicalExam->setReportName('Working with Doctor on Goals to Improve');
        $physicalExam->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(7, 0, 0, 0));
        $physicalExam->addLink(new Link('Get Dr. Form', '/resources/2242/Culver Incentive Doctor Letter Form 103108.pdf'));
        $screeningGroup->addComplianceView($physicalExam);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Culver20102011ComplianceProgramReportPrinter();
    }
}