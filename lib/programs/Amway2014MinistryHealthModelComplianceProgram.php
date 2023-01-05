<?php

class Amway2014MinistryHealthModelComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('job_title', function (User $user) {
            return $user->getJobTitle();
        });

        $printer->addCallbackField('employee-health-homebase', function (User $user) {
            return (string) $user->getUserAdditionalFieldBySlug('employee-health-homebase');
        });

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        $printer->addCallbackField('newest_screening_date', function (User $user) {
            $screening = ScreeningQuery::createQuery('s')
                ->forUser($user)
                ->orderBy('s.date DESC')
                ->limit(1)
                ->fetchOne();

            if($screening) {
                return $screening->getDateTimeObject('date')->format('m/d/Y');
            } else {
                return '';
            }
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Met Standard', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partial Credit', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('No Points/Not Complete', '/images/ministryhealth/redblock1.jpg')
        )));

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        $mapper = new ComplianceStatusPointMapper(2, 1, 0, 0);

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->setShowDateTaken(false);
        $hraView->addLink(new Link('Complete', '/content/989'));
        $hraView->setReportName('Health Risk Assessment');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete between 03/01/2014 - %HRA_END_DATE%');
        $hraView->setName('hra');
        $hraView->setDefaultComment('HRA Not Taken');

        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $pointsGroup = new ComplianceViewGroup('points', 'Points');
        $pointsGroup->setPointsRequiredForCompliance(12);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $smokingView->setReportName('Tobacco Use (As answered in the HRA)');
        $smokingView->setName('smoking');
        $smokingView->setAttribute('ee_assigned_alias', 'tobacco');
        $smokingView->setAttribute('spouse_url', '/content/12088?print_course_id[]=2740');

        $physicalView = new CompleteAnyPreventionComplianceView($programStart, $programEnd);
        $physicalView->setReportName('Preventive Care Visit (per SAS Ministry plan claims)');
        $physicalView->setName('any_prevention');
        $physicalView->bindAlias('ministry_2013');
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($mapper);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 120, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 80, 89);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤120/≤80');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '121-140/81-90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>140/>90');
        // $bloodPressureView->addLink($altStandardLink);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setAttribute('ee_assigned_alias', 'blood_pressure');
        $bloodPressureView->setAttribute('assigned_alias', 'min_2014_bp_assigned');
        $bloodPressureView->setAttribute('spouse_url', '/content/bp_elearning_alternative');

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($mapper);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->overrideTestRowData(null, null, 99, 125);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100');
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>125');
        // $glucoseView->addLink($altStandardLink);
        $glucoseView->setMergeScreenings(true);
        $glucoseView->setAttribute('ee_assigned_alias', 'glucose');
        $glucoseView->setAttribute('assigned_alias', 'min_2014_diabetes_assigned');
        $glucoseView->setAttribute('spouse_url', '/content/diabetes_elearning_alternative');

        $tcOrHdlView = new Ministry2015TCOrHDLComplianceView($programStart, $programEnd);
        $tcOrHdlView->setComplianceStatusPointMapper($mapper);
        $tcOrHdlView->setName('tc_or_hdl');
        $tcOrHdlView->setReportName('Total Cholesterol <u>OR</u> HDL');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'TC: <201, HDL: > 60');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'TC: 201-240');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'TC: >240');
        //$tcOrHdlView->addLink($altStandardLink);
        $tcOrHdlView->setAttribute('ee_assigned_alias', 'tc_hdl');
        $tcOrHdlView->setAttribute('assigned_alias', 'min_2014_cholesterol_assigned');
        $tcOrHdlView->setAttribute('spouse_url', '/content/chol_elearning_alternative');

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($mapper);
        $trigView->setReportName('Triglycerides');
        $trigView->setName('triglycerides');
        $trigView->overrideTestRowData(null, null, 150, 200);
        $trigView->setStatusSummary(ComplianceStatus::COMPLIANT, '<151');
        $trigView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '151-200');
        $trigView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>200');
        // $trigView->addLink($altStandardLink);
        $trigView->setMergeScreenings(true);
        $trigView->setAttribute('ee_assigned_alias', 'trig');
        $trigView->setAttribute('assigned_alias', 'min_2014_triglycerides_assigned');
        $trigView->setAttribute('spouse_url', '/content/trig_elearning_alternative');

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setName('hdl');
        $hdlView->setReportName('HDL');
        $hdlView->overrideTestRowData(null, 40, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50, null, null, 'F');
        //$hdlView->addLink($altStandardLink);
        $hdlView->setMergeScreenings(true);
        $hdlView->setAttribute('ee_assigned_alias', 'hdl');
        $hdlView->setAttribute('assigned_alias', 'min_2014_hdl_assigned');
        $hdlView->setAttribute('spouse_url', '/content/hdl_elearning_alternative');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setComplianceStatusPointMapper($mapper);
        $ldlView->setName('ldl');
        $ldlView->setReportName('LDL');
        $ldlView->overrideTestRowData(null, null, 129, 159);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100-129');
        $ldlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '130-159');
        $ldlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '&ge; 160');
        // $ldlView->addLink($altStandardLink);
        $ldlView->setMergeScreenings(true);
        $ldlView->setAttribute('ee_assigned_alias', 'ldl');
        $ldlView->setAttribute('assigned_alias', 'min_2014_ldl_assigned');
        $ldlView->setAttribute('spouse_url', '/content/ldl_elearning_alternative');

        $bodyComposition = new Ministry2015BodyCompositionComplianceView($programStart, $programEnd);
        $bodyComposition->setComplianceStatusPointMapper($mapper);
        $bodyComposition->setName('body_composition');
        $bodyComposition->setReportName('Body Composition<div style="text-align:center"><br/><br/>Waist/Hip Ratio<br/><u>OR</u><br/>Waist/Abdominal Girth</div>');
        $bodyComposition->setStatusSummary(ComplianceStatus::COMPLIANT, 'W/H Ratio: <0.8, W/A Girth: <28.5-35');
        $bodyComposition->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'W/A Girth: 35.1-43');
        $bodyComposition->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'W/A: >43');
        // $bodyComposition->addLink($altStandardLink);
        $bodyComposition->setAttribute('ee_assigned_alias', 'body_composition');
        $bodyComposition->setAttribute('assigned_alias', 'min_2014_body_comp_assigned');
        $bodyComposition->setAttribute('spouse_url', '/content/bc_elearning_alternative');

        $pointsGroup
            ->addComplianceView($physicalView)
            ->addComplianceView($smokingView)
            ->addComplianceView($bloodPressureView)
            ->addComplianceView($glucoseView)
            ->addComplianceView($tcOrHdlView)
            ->addComplianceView($trigView)
            ->addComplianceView($hdlView)
            ->addComplianceView($ldlView)
            ->addComplianceView($bodyComposition);

        $this->addComplianceViewGroup($pointsGroup);
    }
}