<?php

class ICar2019ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new ICar2019ComplianceProgramReportPrinter();

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

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities (mandatory for participation in the I-CAR Wellness Program)');
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
        $hraScore70->setStatusSummary(ComplianceStatus::COMPLIANT, 'A. Receive a Health Power Score ≥ 70');
        $hraScore70->setAttribute('points_per_activity', 5);
        $bonusGroup->addComplianceView($hraScore70);

        $hraScore80 = new HraScoreComplianceView($startDate, $endDate, 80);
        $hraScore80->setReportName('Receive a Health Power Score >= 80');
        $hraScore80->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $hraScore80->setStatusSummary(ComplianceStatus::COMPLIANT, 'B. Receive a Health Power Score ≥ 80');
        $hraScore80->setAttribute('points_per_activity', 10);
        $bonusGroup->addComplianceView($hraScore80);

        $hraScore90 = new HraScoreComplianceView($startDate, $endDate, 90);
        $hraScore90->setReportName('Receive a Health Power Score >= 90');
        $hraScore90->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hraScore90->setStatusSummary(ComplianceStatus::COMPLIANT, 'C. Receive a Health Power Score ≥ 90');
        $hraScore90->setAttribute('points_per_activity', 10);
        $bonusGroup->addComplianceView($hraScore90);


        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, 'D. Total Cholesterol < 200 mg/dL');
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
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'F. LDL Cholesterol < 100 mg/dL');
        $bonusGroup->addComplianceView($ldlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $triglyceridesView->setAttribute('points_per_activity', 10);
        $triglyceridesView->overrideTestRowData(null, null, 150, null);
        $triglyceridesView->setStatusSummary(ComplianceStatus::COMPLIANT, 'G. Triglycerides ≤ 150 mg/dL');
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

        $phyExam = new PlaceHolderComplianceView(null, 0);
        $phyExam->setMaximumNumberOfPoints(25);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Wellness Screening Follow-up');
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up the wellness screening and complete an annual exam');
        $phyExam->setAttribute('points_per_activity', 25);
        $phyExam->emptyLinks();
        $phyExam->addLink(new Link('Verification form', '/resources/10228/ICAR_2018_Preventive_Care.pdf'));
        $phyExam->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $selfCareGroup->addComplianceView($phyExam);

        $prevServ = new PlaceHolderComplianceView(null, 0);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->addLink(new Link('Verification form', '/resources/10228/ICAR_2018_Preventive_Care.pdf'));
        $prevServ->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $selfCareGroup->addComplianceView($prevServ);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Receive a flu shot');
        $fluShot->addLink(new Link('Verification form', '/resources/10228/ICAR_2018_Preventive_Care.pdf'));
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

        $BenefitEducation = new PlaceHolderComplianceView(null, 0);
        $BenefitEducation->setMaximumNumberOfPoints(10);
        $BenefitEducation->setName('401k');
        $BenefitEducation->setReportName('Employee Benefits Meetings');
        $BenefitEducation->setStatusSummary(ComplianceStatus::COMPLIANT, '401(k) Benefit Education');
        $BenefitEducation->setAttribute('points_per_activity', 10);
        $BenefitEducation->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($BenefitEducation);

        $bluebook = new PlaceHolderComplianceView(null, 0);
        $bluebook->setMaximumNumberOfPoints(10);
        $bluebook->setName('bluebook');
        $bluebook->setReportName('BlueBook & TeleDoc Utilization');
        $bluebook->setStatusSummary(ComplianceStatus::COMPLIANT, 'Register online and/or utilize the service');
        $bluebook->setAttribute('points_per_activity', 5);
        $bluebook->addLink(new FakeLink('Admin will enter', '#'));
        $eduGroup->addComplianceView($bluebook);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new Link('Verification Form', '/resources/10229/ICAR_2018_WellnessEventCert.pdf'));
        $smoking->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by your state quit line: 1-800-QUIT-NOW (1-800-784-8669)');
        $smoking->setAttribute('points_per_activity', 25);
        $eduGroup->addComplianceView($smoking);

        $healthDocumentary = new PlaceHolderComplianceView(null, 0);
        $healthDocumentary->setMaximumNumberOfPoints(15);
        $healthDocumentary->setName('health_documentary');
        $healthDocumentary->setReportName('MultiMedia Learning');
        $healthDocumentary->setStatusSummary(ComplianceStatus::COMPLIANT, 'Watch a health related documentary (i.e., Forks Over Knives, Food, Inc., Cowspiracy)');
        $healthDocumentary->setAttribute('points_per_activity', 5);
        $healthDocumentary->emptyLinks();
        $eduGroup->addComplianceView($healthDocumentary);

        $healthBook = new PlaceHolderComplianceView(null, 0);
        $healthBook->setMaximumNumberOfPoints(20);
        $healthBook->setName('health_book');
        $healthBook->setStatusSummary(ComplianceStatus::COMPLIANT, 'Read a health related book (i.e., Never Be Sick Again: Health is a Choice, Learn How to Choose It)');
        $healthBook->setAttribute('points_per_activity', 10);
        $healthBook->emptyLinks();
        $eduGroup->addComplianceView($healthBook);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, null, 5);
        $lessons->setAttribute('points_per_activity', 5);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(25);
        $eduGroup->addComplianceView($lessons);

        $this->addComplianceViewGroup($eduGroup);

        $physicalGroup = new ComplianceViewGroup('physical', 'Physical Activities');
        $physicalGroup->setPointsRequiredForCompliance(0);

        $tenWeeks = new PlaceHolderComplianceView(null, 0);
        $tenWeeks->setMaximumNumberOfPoints(30);
        $tenWeeks->setAttribute('points_per_activity', '3');
        $tenWeeks->setName('10_weeks_steps');
        $tenWeeks->setReportName('Wellness Walking Challenge');
        $tenWeeks->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 10 weeks of the Wellness Walking Challenge logging steps each day');
        $physicalGroup->addComplianceView($tenWeeks);

        $monthlyAverageSteps = new PlaceHolderComplianceView(null, 0);
        $monthlyAverageSteps->setMaximumNumberOfPoints(15);
        $monthlyAverageSteps->setAttribute('points_per_activity', '5');
        $monthlyAverageSteps->setName('monthly_average_steps');
        $monthlyAverageSteps->setStatusSummary(ComplianceStatus::COMPLIANT, 'Maintain a monthly average of 10,000 steps');
        $physicalGroup->addComplianceView($monthlyAverageSteps);

        $winWalkingChallenge = new PlaceHolderComplianceView(null, 0);
        $winWalkingChallenge->setMaximumNumberOfPoints(5);
        $winWalkingChallenge->setAttribute('points_per_activity', '5');
        $winWalkingChallenge->setName('win_walking_challenge');
        $winWalkingChallenge->setStatusSummary(ComplianceStatus::COMPLIANT, 'Win Wellness Walking Challenge');
        $physicalGroup->addComplianceView($winWalkingChallenge);

        $bikeChallenge = new PlaceHolderComplianceView(null, 0);
        $bikeChallenge->setMaximumNumberOfPoints(5);
        $bikeChallenge->setAttribute('points_per_activity', '5');
        $bikeChallenge->setName('national_bike_challenge');
        $bikeChallenge->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the National Bike Challenge');
        $bikeChallenge->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($bikeChallenge);

        $bike100 = new PlaceHolderComplianceView(null, 0);
        $bike100->setMaximumNumberOfPoints(5);
        $bike100->setAttribute('points_per_activity', '5');
        $bike100->setName('national_bike_challenge_100');
        $bike100->setStatusSummary(ComplianceStatus::COMPLIANT, 'Average at least 100 miles/month');
        $bike100->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($bike100);

        $bike150 = new PlaceHolderComplianceView(null, 0);
        $bike150->setMaximumNumberOfPoints(10);
        $bike150->setAttribute('points_per_activity', '5');
        $bike150->setName('national_bike_challenge_150');
        $bike150->setStatusSummary(ComplianceStatus::COMPLIANT, 'Average at least 150 miles/month');
        $bike150->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($bike150);

        $bike200 = new PlaceHolderComplianceView(null, 0);
        $bike200->setMaximumNumberOfPoints(25);
        $bike200->setAttribute('points_per_activity', '10');
        $bike200->setName('national_bike_challenge_200');
        $bike200->setStatusSummary(ComplianceStatus::COMPLIANT, 'Average at least 200 miles/month');
        $bike200->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($bike200);

        $fitnessActivity = new PhysicalActivityComplianceView($startDate, $endDate);
        $fitnessActivity->setMaximumNumberOfPoints(48);
        $fitnessActivity->setMonthlyPointLimit(4);
        $fitnessActivity->setMinutesDivisorForPoints(150);
        $fitnessActivity->setAttribute('points_per_activity', '1');
        $fitnessActivity->setName('fitness_activity');
        $fitnessActivity->setReportName('Regular Fitness Training');
        $fitnessActivity->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 150 minutes of activity up to 4 times/month ');
        $physicalGroup->addComplianceView($fitnessActivity);

        $fiveK = new PlaceHolderComplianceView(null, 0);
        $fiveK->setMaximumNumberOfPoints(20);
        $fiveK->setName('5k');
        $fiveK->setReportName('Run/Walk a Race');
        $fiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in 5k');
        $fiveK->setAttribute('points_per_activity', 10);
        $fiveK->addLink(new Link('Verification Form', '/resources/10229/ICAR_2018_WellnessEventCert.pdf'));
        $fiveK->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $physicalGroup->addComplianceView($fiveK);

        $tenK = new PlaceHolderComplianceView(null, 0);
        $tenK->setMaximumNumberOfPoints(30);
        $tenK->setName('10k');
        $tenK->setReportName('Run/Walk a Race');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 15);
        $physicalGroup->addComplianceView($tenK);

        $halfMar = new PlaceHolderComplianceView(null, 0);
        $halfMar->setMaximumNumberOfPoints(20);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Run/Walk a Race');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setAttribute('points_per_activity', 20);
        $physicalGroup->addComplianceView($halfMar);

        $fullMar = new PlaceHolderComplianceView(null, 0);
        $fullMar->setMaximumNumberOfPoints(30);
        $fullMar->setName('full_mar');
        $fullMar->setReportName('Run/Walk a Race');
        $fullMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a marathon or Olympic distance triathlon');
        $fullMar->setAttribute('points_per_activity', 30);
        $physicalGroup->addComplianceView($fullMar);

        $healthFair = new PlaceHolderComplianceView(null, 0);
        $healthFair->setMaximumNumberOfPoints(150);
        $healthFair->setName('health_fair');
        $healthFair->setReportName('Other I-CAR Wellness Activities');
        $healthFair->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a designated I-CAR Wellness Activity and earn the specified number of points.');
        $healthFair->setAttribute('points_per_activity', 'TBD');
        $healthFair->addLink(new FakeLink('Admin will enter', '#'));
        $physicalGroup->addComplianceView($healthFair);

        $this->addComplianceViewGroup($physicalGroup);
    }
}

class ICar2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

                $('.headerRow-required').children(':eq(0)').attr('colspan', 2);
                $('.headerRow-required').children(':eq(1)').remove();

                $('.points').each(function() {
                    $(this).remove();
                });

                $('.view-complete_screening_hra').children('td:eq(0)').css('width', '200px');
                $('.view-complete_screening_hra').children('td:eq(0)').html('A. Biometric Screening <br /><br />B. Health Power Assessment');
                $('.view-complete_screening_hra').children('td:eq(5)').html(
                    '<a href="/content/wms2-appointment-center">Sign up</a> <br />' +
                    '<a href="/compliance/hmi-2016/my-results">Results</a> <br />' +
                    '<a href="/compliance/hmi-2016/my-health">Take HPA</a> <br />'
                );

                $('.view-hra_score_70').children('td:eq(0)').html('');
                $('.view-hra_score_70').children('td:eq(0)').attr('rowspan', 9);
                $('.view-hra_score_70').children('td:eq(5)').html('<a href="/compliance/hmi-2016/my-results">Results</a>');
                $('.view-hra_score_70').children('td:eq(5)').attr('rowspan', 9);

                $('.view-hra_score_80').children('td:eq(0), td:eq(5)').remove('');
                $('.view-hra_score_90').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_total_cholesterol_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_hdl_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_ldl_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_triglycerides_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_glucose_screening_test').children('td:eq(0), td:eq(5)').remove('');
                $('.view-comply_with_blood_pressure_screening_test').children('td:eq(0), td:eq(5)').remove('');

                $('.view-phy_exam')
                    .children('td:eq(0)')
                    .html('<strong>A</strong>. Annual Physical Exam &amp; Screening Follow-up');

                $('.view-prev_serv').children('td:eq(0)').attr('rowspan', 2);
                $('.view-prev_serv').children('.links').attr('rowspan', 2);

                $('.view-flu_shot').children('td:eq(0)').remove();
                $('.view-flu_shot').children('.links').remove();

                $('.view-benefits_meeting').children('td:eq(0)').attr('rowspan', 2);
//                $('.view-lnl_presentation').children('td:eq(0)').remove();
                $('.view-401k').children('td:eq(0)').remove();

                $('.view-bluebook').children('td:eq(0)').html('<strong>B</strong>. BlueBook & TeleDoc Utilization');

                $('.view-smoking').children('td:eq(0)').html('<strong>C</strong>. Smoking Cessation');

                $('.view-health_documentary').children('td:eq(0)').attr('rowspan', 2);
                $('.view-health_documentary').children('td:eq(0)').html('<strong>D</strong>. MultiMedia Learning');
                $('.view-health_book').children('td:eq(0)').remove();

                $('[class*=view-complete_elearning_lessons]').children('td:eq(0)').html('<strong>E</strong>. eLearning Lessons');

                $('.view-10_weeks_steps').children('td:eq(0)').attr('rowspan', 3);
                $('.view-10_weeks_steps').children('td:eq(0)').html('<strong>A</strong>. Wellness Walking Challenge' +
                    '<br /><br /><span style="font-size:8pt;">Challenge: 6/3/19 – 8/30/19</span>');
                $('.view-monthly_average_steps').children('td:eq(0)').remove();
                $('.view-win_walking_challenge').children('td:eq(0)').remove();

                $('.view-10_weeks_steps').children('td:eq(5)').attr('rowspan', 3);
                $('.view-10_weeks_steps').children('td:eq(5)').html('Admin will enter');
                $('.view-monthly_average_steps').children('td:eq(4)').remove();
                $('.view-win_walking_challenge').children('td:eq(4)').remove();

                $('.view-national_bike_challenge').children('td:eq(0)').html('<strong>B</strong>. National Bike Challenge' +
                    '<br /><span style="font-size:8pt;">Challenge: May 2019 – September 2019</span>' +
                    '<div style="margin-top: 10px;">Join the I-CAR team and earn points based on the average miles biked each month.</div>');

                $('.view-national_bike_challenge').children('td:eq(0)').attr('rowspan', 4);
                $('.view-national_bike_challenge').children('td:eq(5)').attr('rowspan', 4);


                $('.view-national_bike_challenge_100').children('td:eq(0)').remove();
                $('.view-national_bike_challenge_150').children('td:eq(0)').remove();
                $('.view-national_bike_challenge_200').children('td:eq(0)').remove();

                $('.view-national_bike_challenge_100').children('td:eq(4)').remove();
                $('.view-national_bike_challenge_150').children('td:eq(4)').remove();
                $('.view-national_bike_challenge_200').children('td:eq(4)').remove();

                $('.view-fitness_activity').children('td:eq(0)').html('<strong>C</strong>. Fitness Activity');


                $('.view-exercise').children('td:eq(0)').remove();

                $('.view-5k').children('td:eq(0)').attr('rowspan', 4);
                $('.view-5k').children('td:eq(5)').attr('rowspan', 4);
                $('.view-5k').children('td:eq(0)').html('<strong>D</strong>. Run/Walk a Race');

                $('.view-10k').children('td:eq(0)').remove();
                $('.view-10k').children('td:eq(4)').remove();
                $('.view-half_mar').children('td:eq(0)').remove();
                $('.view-half_mar').children('td:eq(4)').remove();
                $('.view-full_mar').children('td:eq(0)').remove();
                $('.view-full_mar').children('td:eq(4)').remove();

                $('.view-health_fair').children('td:eq(0)').html('<strong>E</strong>. Other I-CAR Wellness Activities');

            });
        </script>

        <div style="border-bottom: 1px solid #eeeeee;">
            <img src="/resources/7721/i_car_logo_2016.jpg" style="width:98%; margin-left: 6px;">
        </div>

        <h4 style="text-align: right">Employee Wellness Program</h4>

        <p><strong>All Employees that complete the Wellness Screening and Health Assessment Questionnaire are
                eligible to participate</strong> and will earn points for completing any of the outlined
            health activities between <strong>1/1/19 – 11/30/19</strong>.</p>

        <p>To be eligible for the quarterly and grand prize raffles, employees must earn the points needed before
            each deadline. The number of points earned will also move employees toward the Bronze, Silver, and
            Gold levels of recognition for outstanding efforts toward better health and well-being. </p>


        <table style="width:100%" id="status-table">
            <tr>
                <th></th>
                <th>Requirements / Minimum Points Needed</th>
                <th>Recognition Status</th>
                <th>Reward(s)</th>
            </tr>
            <tr>
                <td>February 28,2019</td>
                <td>50 points <br /><br />
                    <span style="font-size:8pt;">(Complete the Wellness Screening & <br />
                        Health Risk Assessment Questionnaire)</span>
                </td>
                <td>
                    QUALIFIED
                </td>
                <td>
                    <span style="font-weight: normal;">TBD</span>
                </td>
            </tr>
            <tr>
                <td>May 31, 2019</td>
                <td>100 points</td>
                <td>BRONZE</td>
                <td rowspan="3">
                    Prizes to be Determined
                </td>
            </tr>
            <tr>
                <td>August 31, 2019</td>
                <td>150 points</td>
                <td>SILVER</td>
            </tr>
            <tr>
                <td>November 30, 2019</td>
                <td>200 points</td>
                <td>GOLD</td>
            </tr>
        </table>

        <p style="margin: 20px 0px;">
            I-CAR is committed to helping you achieve your best health. Rewards for participating in a wellness
            program are available to all employees. If you think you might be unable to meet a standard for a
            reward under this wellness program, you might qualify for an opportunity to earn the same reward by
            different means.  Please contact Health Maintenance Institute at (847) 635-6580 and we will work with
            you (and, if you wish, with your doctor) to find a wellness program with the same reward that is
            right for you in light of your health status.
        </p>
        <?php
    }
}