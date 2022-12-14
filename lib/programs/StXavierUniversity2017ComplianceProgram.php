<?php

class StXavierUniversity2017ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new StXavierUniversity2017ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities');
        $reqGroup->setPointsRequiredForCompliance(0);

        $hraScreening = new CompleteHRAAndScreeningComplianceView($startDate, $endDate);
        $hraScreening->setReportName('Biometric Screening');
        $hraScreening->setAttribute('points_per_activity', 50);
        $hraScreening->setMaximumNumberOfPoints(50);
        $hraScreening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hraScreening->setStatusSummary(ComplianceStatus::COMPLIANT, '<span style="color:red;">Employees must complete the Biometric Screening or Physical Exam & HPA to be eligible for the wellness program & incentive rewards.</span>');
        $reqGroup->addComplianceView($hraScreening);
        $this->addComplianceViewGroup($reqGroup);

        $bonusGroup = new ComplianceViewGroup('bonus', 'Biometric Bonus Points');
        $bonusGroup->setPointsRequiredForCompliance(0);

        $hraScore70 = new HraScoreComplianceView($startDate, $endDate, 70);
        $hraScore70->setReportName('Receive a Health Power Score >= 70');
        $hraScore70->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore70->setStatusSummary(ComplianceStatus::COMPLIANT, 'A. Receive a Health Power Score >70');
        $hraScore70->setAttribute('points_per_activity', 5);
        $bonusGroup->addComplianceView($hraScore70);

        $hraScore80 = new HraScoreComplianceView($startDate, $endDate, 80);
        $hraScore80->setReportName('Receive a Health Power Score >= 80');
        $hraScore80->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $hraScore80->setStatusSummary(ComplianceStatus::COMPLIANT, 'B. Receive a Health Power Score >80');
        $hraScore80->setAttribute('points_per_activity', 15);
        $bonusGroup->addComplianceView($hraScore80);

        $hraScore90 = new HraScoreComplianceView($startDate, $endDate, 90);
        $hraScore90->setReportName('Receive a Health Power Score >= 90');
        $hraScore90->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hraScore90->setStatusSummary(ComplianceStatus::COMPLIANT, 'C. Receive a Health Power Score >90');
        $hraScore90->setAttribute('points_per_activity', 25);
        $bonusGroup->addComplianceView($hraScore90);


        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, 'D. Total Cholesterol <200 mg/dL');
        $bonusGroup->addComplianceView($tcView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdlView->setReportName('HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $hdlView->setAttribute('points_per_activity', 10);
        $hdlView->overrideTestRowData(null, 35.001, null, null, 'M');
        $hdlView->overrideTestRowData(null, 40.001, null, null, 'F');
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'E. HDL Cholesterol > 35 mg/dL  (men); >40 mg/dL (women)');
        $bonusGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 10);
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'F. LDL Cholesterol <100 mg/dL');
        $bonusGroup->addComplianceView($ldlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $triglyceridesView->setAttribute('points_per_activity', 10);
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setStatusSummary(ComplianceStatus::COMPLIANT, 'G. Triglycerides < 150 mg/dL');
        $bonusGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 10);
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, 'H. Fasting Glucose < 100 mg/dL');
        $bonusGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 10);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, null);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, 'I. Blood Pressure < 130/85');
        $bonusGroup->addComplianceView($bloodPressureView);

        $this->addComplianceViewGroup($bonusGroup);

        $selfCareGroup = new ComplianceViewGroup('self', 'Self-Care Wellness Activities');
        $selfCareGroup->setPointsRequiredForCompliance(0);


        $spouseStatus = new RelatedUserCompleteComplianceViewsComplianceView(
            $this,
            array('complete_screening_hra'),
            array(Relationship::OTHER_DEPENDENT, Relationship::SPOUSE)
        );
        $spouseStatus->setReportName('Wellness Screening for Spouse and Eligible Dependents');
        $spouseStatus->setName('spouse_hra_screening');
        $spouseStatus->setMaximumNumberOfPoints(30);
        $spouseStatus->setAttribute('points_per_activity', 15);
        $spouseStatus->setPointsPerCompletion(15);
        $spouseStatus->setStatusSummary(ComplianceStatus::COMPLIANT, 'Spouse and/or dependent(s) complete the 2016 Biometric Screening & HRA');
        $spouseStatus->addLink(new Link('Sign up', '/content/wms2-appointment-center'));
        $spouseStatus->addLink(new Link('Take HRA', '/content/989'));
        $selfCareGroup->addComplianceView($spouseStatus);

        $phyExam = new PlaceHolderComplianceView(null, 0);
        $phyExam->setMaximumNumberOfPoints(50);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Wellness Screening Follow-up');
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up the wellness screening and complete an annual exam');
        $phyExam->setAttribute('points_per_activity', 25);
        $phyExam->emptyLinks();
        $phyExam->addLink(new Link('Verification form', '/resources/9004/2016-17_PreventiveCare Cert.pdf'));
        $phyExam->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $selfCareGroup->addComplianceView($phyExam);

        $prevServ = new PlaceHolderComplianceView(null, 0);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->addLink(new Link('Verification form', '/resources/9004/2016-17_PreventiveCare Cert.pdf'));
        $prevServ->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $selfCareGroup->addComplianceView($prevServ);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Receive a flu shot');
        $fluShot->addLink(new Link('Verification form', '/resources/9004/2016-17_PreventiveCare Cert.pdf'));
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a flu shot');
        $fluShot->setAttribute('points_per_activity', 10);
        $selfCareGroup->addComplianceView($fluShot);

        $this->addComplianceViewGroup($selfCareGroup);


        $eduGroup = new ComplianceViewGroup('education', 'Educational Activities');
        $eduGroup->setPointsRequiredForCompliance(0);

        $benefitsMeeting = new PlaceHolderComplianceView(null, 0);
        $benefitsMeeting->setMaximumNumberOfPoints(10);
        $benefitsMeeting->setName('benefits_meeting');
        $benefitsMeeting->setReportName('Employee Benefits Meetings');
        $benefitsMeeting->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the Employee Benefits Meeting during Open Enrollment');
        $benefitsMeeting->setAttribute('points_per_activity', 10);
        $benefitsMeeting->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($benefitsMeeting);

        $Lnl = new PlaceHolderComplianceView(null, 0);
        $Lnl->setMaximumNumberOfPoints(60);
        $Lnl->setName('lnl_presentation');
        $Lnl->setReportName('Employee Benefits Meetings');
        $Lnl->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend a Lunch & Learn Presentation');
        $Lnl->setAttribute('points_per_activity', 10);
        $Lnl->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($Lnl);

        $employeeBenefits = new PlaceHolderComplianceView(null, 0);
        $employeeBenefits->setMaximumNumberOfPoints(60);
        $employeeBenefits->setName('employee_benefits');
        $employeeBenefits->setReportName('Employee Benefits Meetings');
        $employeeBenefits->setStatusSummary(ComplianceStatus::COMPLIANT, 'Other Employee Benefits Education');
        $employeeBenefits->setAttribute('points_per_activity', 10);
        $employeeBenefits->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($employeeBenefits);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new Link('SXU Wellness Form', '/resources/9007/2016-17_WellnessEventCert.pdf'));
        $smoking->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by the IL Quitline: <a href="http://www.quityes.org">www.quityes.org</a>');
        $smoking->setAttribute('points_per_activity', 25);
        $eduGroup->addComplianceView($smoking);

        $fitnessChallenge = new PlaceHolderComplianceView(null, 0);
        $fitnessChallenge->setMaximumNumberOfPoints(30);
        $fitnessChallenge->setName('fitness_challenge');
        $fitnessChallenge->setReportName('Health/Nutrition Program');
        $fitnessChallenge->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a Shannon Center fitness challenge such as Biggest Loser or Step up to Shape Up.');
        $fitnessChallenge->setAttribute('points_per_activity', 10);
        $fitnessChallenge->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($fitnessChallenge);

        $sixWeek5K = new PlaceHolderComplianceView(null, 0);
        $sixWeek5K->setMaximumNumberOfPoints(10);
        $sixWeek5K->setName('six_week_5K');
        $sixWeek5K->setReportName('Health/Nutrition Program');
        $sixWeek5K->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the Shannon Center 6 week 5K Training Program in the Fall or Spring');
        $sixWeek5K->setAttribute('points_per_activity', 10);
        $sixWeek5K->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($sixWeek5K);

        $nutritionWeightLoss = new PlaceHolderComplianceView(null, 0);
        $nutritionWeightLoss->setMaximumNumberOfPoints(20);
        $nutritionWeightLoss->setName('nutrition_weight_loss');
        $nutritionWeightLoss->setReportName('Health/Nutrition Program');
        $nutritionWeightLoss->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the Shannon Center Nutrition Program (8 weeks)');
        $nutritionWeightLoss->setAttribute('points_per_activity', 10);
        $nutritionWeightLoss->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($nutritionWeightLoss);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, null, 5);
        $lessons->setAttribute('points_per_activity', 5);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(25);
        $eduGroup->addComplianceView($lessons);

        $this->addComplianceViewGroup($eduGroup);

        $physicalGroup = new ComplianceViewGroup('physical', 'Physical Activity');
        $physicalGroup->setPointsRequiredForCompliance(0);

        $physAct = new PhysicalActivityComplianceView($startDate, $endDate);
        $physAct->setMaximumNumberOfPoints(48);
        $physAct->setMonthlyPointLimit(4);
        $physAct->setMinutesDivisorForPoints(150);
        $physAct->setAttribute('points_per_activity', '1');
        $physAct->setReportName('Regular Fitness Training');
        $physAct->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 150 minutes of activity up to 4 times/month ');
        $physicalGroup->addComplianceView($physAct);

        $fiveK = new PlaceHolderComplianceView(null, 0);
        $fiveK->setMaximumNumberOfPoints(40);
        $fiveK->setName('5k');
        $fiveK->setReportName('Run/Walk a Race');
        $fiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 5k');
        $fiveK->setAttribute('points_per_activity', 20);
        $fiveK->addLink(new Link('SXU Wellness Form', '/resources/9007/2016-17_WellnessEventCert.pdf'));
        $fiveK->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $physicalGroup->addComplianceView($fiveK);

        $cougarFiveK = new PlaceHolderComplianceView(null, 0);
        $cougarFiveK->setMaximumNumberOfPoints(30);
        $cougarFiveK->setName('cougar5k');
        $cougarFiveK->setReportName('Run/Walk a Race');
        $cougarFiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the Cougar 5K - Homecoming Weekend');
        $cougarFiveK->setAttribute('points_per_activity', 30);
        $physicalGroup->addComplianceView($cougarFiveK);

        $tenK = new PlaceHolderComplianceView(null, 0);
        $tenK->setMaximumNumberOfPoints(60);
        $tenK->setName('10k');
        $tenK->setReportName('Run/Walk a Race');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 30);
        $physicalGroup->addComplianceView($tenK);

        $halfMar = new PlaceHolderComplianceView(null, 0);
        $halfMar->setMaximumNumberOfPoints(50);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Run/Walk a Race');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setAttribute('points_per_activity', 50);
        $physicalGroup->addComplianceView($halfMar);

        $fullMar = new PlaceHolderComplianceView(null, 0);
        $fullMar->setMaximumNumberOfPoints(75);
        $fullMar->setName('full_mar');
        $fullMar->setReportName('Run/Walk a Race');
        $fullMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a marathon or Olympic distance triathlon');
        $fullMar->setAttribute('points_per_activity', 75);
        $physicalGroup->addComplianceView($fullMar);

        $healthFair = new PlaceHolderComplianceView(null, 0);
        $healthFair->setMaximumNumberOfPoints(5);
        $healthFair->setName('health_fair');
        $healthFair->setReportName('Other SXU Wellness Events');
        $healthFair->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend SXU Health Fair in the Shannon Center');
        $healthFair->setAttribute('points_per_activity', 5);
        $healthFair->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($healthFair);

        $cprCertified = new PlaceHolderComplianceView(null, 0);
        $cprCertified->setMaximumNumberOfPoints(15);
        $cprCertified->setName('cpr_certified');
        $cprCertified->setReportName('Other SXU Wellness Events');
        $cprCertified->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get CPR certified at the SXU Health Center');
        $cprCertified->setAttribute('points_per_activity', 15);
        $cprCertified->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($cprCertified);

        $referEmployee = new PlaceHolderComplianceView(null, 0);
        $referEmployee->setMaximumNumberOfPoints(50);
        $referEmployee->setName('refer_employee');
        $referEmployee->setReportName('Other SXU Wellness Events');
        $referEmployee->setStatusSummary(ComplianceStatus::COMPLIANT, 'Refer an Employee to the SXperience Wellness Program (points awarded upon qualification)');
        $referEmployee->setAttribute('points_per_activity', 5);
        $referEmployee->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($referEmployee);

        $cprCertified = new PlaceHolderComplianceView(null, 0);
        $cprCertified->setMaximumNumberOfPoints(150);
        $cprCertified->setName('other_events');
        $cprCertified->setReportName('Other SXU Wellness Events');
        $cprCertified->setStatusSummary(ComplianceStatus::COMPLIANT, 'Other Events');
        $cprCertified->setAttribute('points_per_activity', 'TBD');
        $cprCertified->addLink(new Link('SXU Wellness Form', '/resources/9007/2016-17_WellnessEventCert.pdf'));
        $cprCertified->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $physicalGroup->addComplianceView($cprCertified);


        $this->addComplianceViewGroup($physicalGroup);
    }
}

class StXavierUniversity2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(false);

        $this->addStatusCallbackColumn('Requirement', function($status) {
            return $status->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT);
        });

        $this->addStatusCallbackColumn('Points Per Activity', function($status) {
            return $status->getComplianceView()->getAttribute('points_per_activity');
        });

        $this->addStatusCallbackColumn('Max Points', function($status) {
            return $status->getComplianceView()->getMaximumNumberOfPoints();
        });

        $this->addStatusCallbackColumn('Points Earned', function($status) {
            return $status->getPoints();
        });
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
        <p style="font-size:smaller;margin-top:20px;">SXU is committed to helping you achieve your best health. Rewards for
            participating in a wellness program are available to all employees.
            If you think you might be unable to meet a standard for a reward
            under this wellness program, you might qualify for an opportunity to
            earn the same reward by different means.  Please contact Health
            Maintenance Institute at (847) 635-6580 and we will work with you
            (and, if you wish, with your doctor) to find a wellness program with
            the same reward that is right for you in light of your health status
        </p>

        <?php
    }

    protected function printCustomRows($status)
    {
        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $bonusGroupStatus = $status->getComplianceViewGroupStatus('bonus');
        $selfGroupStatus = $status->getComplianceViewGroupStatus('self');
        $educationGroupStatus = $status->getComplianceViewGroupStatus('education');
        $physicalGroupStatus = $status->getComplianceViewGroupStatus('physical');

        $totalMaxPoints = 0;
        $totalPointsPerActivity = 0;
        $totalPointsEarned = 0;
        foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $totalMaxPoints += $viewStatus->getComplianceView()->getMaximumNumberOfPoints();
            $totalPointsPerActivity += $viewStatus->getComplianceView()->getAttribute('points_per_activity');
            $totalPointsEarned += $viewStatus->getPoints();
        }

        foreach($bonusGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $totalMaxPoints += $viewStatus->getComplianceView()->getMaximumNumberOfPoints();
            $totalPointsPerActivity += $viewStatus->getComplianceView()->getAttribute('points_per_activity');
            $totalPointsEarned += $viewStatus->getPoints();
        }

        foreach($selfGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $totalMaxPoints += $viewStatus->getComplianceView()->getMaximumNumberOfPoints();
            $totalPointsPerActivity += $viewStatus->getComplianceView()->getAttribute('points_per_activity');
            $totalPointsEarned += $viewStatus->getPoints();
        }

        foreach($educationGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $totalMaxPoints += $viewStatus->getComplianceView()->getMaximumNumberOfPoints();
            $totalPointsPerActivity += $viewStatus->getComplianceView()->getAttribute('points_per_activity');
            $totalPointsEarned += $viewStatus->getPoints();
        }

        foreach($physicalGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $totalMaxPoints += $viewStatus->getComplianceView()->getMaximumNumberOfPoints();
            $totalPointsPerActivity += $viewStatus->getComplianceView()->getAttribute('points_per_activity');
            $totalPointsEarned += $viewStatus->getPoints();
        }

        ?>

        <tr style="height:50px;text-align:center;">
            <td>TOTALS</td>
            <td></td>
            <td><?php //echo $totalPointsPerActivity ?></td>
            <td><?php echo $totalMaxPoints ?></td>
            <td><?php echo $totalPointsEarned ?></td>
            <td></td>
        </tr>

        <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            .pageHeading { display:none; }

            #status-table th,
            .phipTable .headerRow {
                background-color:#007698;
                color:#FFF;
            }

            #status-table th,
            #status-table td {
                padding:5px;
                text-align:center;
                border:1px solid #CACACA;
                font-weight: bold;
            }

            .phipTable,
            .phipTable th,
            .phipTable td {
                font-size:0.95em;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                $('.headerRow').each(function() {
                    $(this).children('td:eq(0)').html('Requirement');
                    $(this).children('td:eq(1)').html('Points per Activity');
                    $(this).children('td:eq(2)').html('Max Points');
                    $(this).children('td:eq(3)').html('Points Earned');
                    $(this).children('td:eq(6)').html('Tracking Method');
                    $(this).children('td:eq(4), td:eq(5)').remove();
                });

                $('.points').each(function() {
                    $(this).remove();
                });

                $('.view-complete_screening_hra').children('td:eq(0)').html('A. Biometric Screening <br /><br />B. Health Power Assessment');
                $('.view-complete_screening_hra').children('td:eq(5)').html('<a href="/content/wms2-appointment-center">Sign up</a> <br /><br /><a href="/content/989">Take HRA</a>');

                $('.view-hra_score_70').children('td:eq(0)').html('');
                $('.view-hra_score_70').children('td:eq(0)').attr('rowspan', 9);
                $('.view-hra_score_70').children('td:eq(5)').html('<a href="/content/989">Take HRA</a>');
                $('.view-hra_score_70').children('td:eq(5)').attr('rowspan', 9);

                $('.view-hra_score_80').children('td:eq(0), td:eq(5)').remove('');
                $('.view-hra_score_90').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_total_cholesterol_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_hdl_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_ldl_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_triglycerides_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_glucose_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_blood_pressure_screening_test').children('td:eq(0), td:eq(5)').remove('');

                $('.view-spouse_hra_screening')
                    .children('td:eq(0)')
                    .html('<strong>A</strong>. Wellness Screening for Spouse and Eligible Dependents' +
                    '<br /><br /><span style="font-size:8pt;">(dependents covered on medical plan must be age 18 or over)</span>');

                $('.view-phy_exam')
                    .children('td:eq(0)')
                    .html('<strong>B</strong>. Annual Physical Exam &amp; Screening Follow-up' +
                    '<br /><br /><span style="font-size:8pt;">Credit will be awarded for an Employee AND Spouse or Eligible Dependent that submits proof of completion.</span>');

                $('.view-prev_serv').children('td:eq(0)').attr('rowspan', 2);
                $('.view-prev_serv').children('.links').attr('rowspan', 2);

                $('.view-flu_shot').children('td:eq(0)').remove();
                $('.view-flu_shot').children('.links').remove();

                $('.view-benefits_meeting').children('td:eq(0)').attr('rowspan', 3);
                $('.view-lnl_presentation').children('td:eq(0)').remove();
                $('.view-employee_benefits').children('td:eq(0)').remove();

                $('.view-smoking').children('td:eq(0)').html('<strong>B</strong>. Smoking Cessation');

                $('.view-fitness_challenge').children('td:eq(0)').attr('rowspan', 3);
                $('.view-fitness_challenge').children('td:eq(0)').html('<strong>C</strong>. Health/Nutrition Program'+
                    '<br /><br /><span style="font-size:8pt;">Programs are offered once per semester in the Shannon Center</span>');
                $('.view-six_week_5K').children('td:eq(0)').remove();
                $('.view-nutrition_weight_loss').children('td:eq(0)').remove();

                $('[class*=view-complete_elearning_lessons]').children('td:eq(0)').html('<strong>D</strong>. eLearning Lessons');

//                $('.view-activity_21').children('td:eq(0)').attr('rowspan', 2);
                $('.view-activity_21').children('td:eq(0)').html('<strong>A</strong>. Regular Fitness Training');
                $('.view-exercise').children('td:eq(0)').remove();

                $('.view-5k').children('td:eq(0)').attr('rowspan', 5);
                $('.view-5k').children('td:eq(5)').attr('rowspan', 5);
                $('.view-5k').children('td:eq(0)').html('<strong>B</strong>. Run/Walk a Race');
                $('.view-cougar5k').children('td:eq(0)').remove();
                $('.view-cougar5k').children('td:eq(4)').remove();
                $('.view-10k').children('td:eq(0)').remove();
                $('.view-10k').children('td:eq(4)').remove();
                $('.view-half_mar').children('td:eq(0)').remove();
                $('.view-half_mar').children('td:eq(4)').remove();
                $('.view-full_mar').children('td:eq(0)').remove();
                $('.view-full_mar').children('td:eq(4)').remove();

                $('.view-health_fair').children('td:eq(0)').attr('rowspan', 4);
                $('.view-health_fair').children('td:eq(0)').html('<strong>C</strong>. Other SXU Wellness Events');
                $('.view-cpr_certified').children('td:eq(0)').remove();
                $('.view-refer_employee').children('td:eq(0)').remove();
                $('.view-other_events').children('td:eq(0)').remove();




            });
        </script>

        <div class="page-header">
            <div>
                <img src="/resources/8956/saint_xavier_logo.jpg"  style="width: 150px; margin-left: 80%"/>
            </div>
            <h4>SXperience Wellness!</h4>
        </div>

        <p><strong>All Employees that complete the Wellness Screening and Health
                Assessment Questionnaire are eligible to participate</strong> and will earn
            points for completing any of the health activities outlined here.</p>

        <p>To be eligible for the quarterly and grand prize raffles, employees
            must earn the points needed before each deadline. The number of
            points earned will also move employees toward the Bronze, Silver,
            and Gold levels of recognition for outstanding efforts toward
            better health and well-being.</p>

        <p>Rewards will be based on points earned between 11/1/16 and 10/31/2017
            so plan your point accumulation accordingly.</p>


        <table style="width:100%" id="status-table">
            <tr>
                <th>Raffle Deadline</th>
                <th>Requirements / Minimum Points Needed</th>
                <th>Recognition Status</th>
                <th>Reward(s)</th>
            </tr>
            <tr>
                <td>December 31, 2016</td>
                <td>50 points <br /><br />
                    <span style="font-size:8pt;">((Complete the Wellness Screening & <br />
                        Health Power Assessment Questionnaire)</span>
                </td>
                <td>
                    QUALIFIED
                </td>
                <td>
                    <span style="font-weight: normal;">All QUALIFIED participants will receive a SXU Giveaway</span>
                </td>
            </tr>
            <tr>
                <td>March 30, 2017</td>
                <td>75 points</td>
                <td>BRONZE</td>
                <td>
                    5 Prizes raffled: <br /><br />
                    <span style="font-weight: normal;">Choice of $100 VISA Gift Card</span>
                </td>
            </tr>
            <tr>
                <td>June 30, 2017</td>
                <td>125 points</td>
                <td>SILVER</td>
                <td>
                    5 Prizes raffled: <br /><br />
                    <span style="font-weight: normal;">5 - $150 VISA Gift Cards</span>
                </td>
            </tr>
            <tr>
                <td>October 31, 2017</td>
                <td>175 points</td>
                <td>GOLD</td>
                <td>
                    5 Prizes raffled: <br /><br />
                    <span style="font-weight: normal;">5 - $250 VISA Gift Cards</span>
                </td>
            </tr>
        </table>

        <p style="text-align:center;color:red; padding: 10px 0;">Compliance will be reported monthly.
            All employees achieving recognition status will be announced
            online at SXU.org and  receive a certificate of achievement.
        </p>
        <?php
    }
}