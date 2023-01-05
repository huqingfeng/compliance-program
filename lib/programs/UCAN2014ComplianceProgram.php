<?php

class UCAN2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new UCAN2014ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Steps', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('hmi_multi_challenge_10000')->getAttribute('total_steps');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities');

        $hraScrStartDate = '2014-04-01';

        $hra = new CompleteHRAComplianceView($hraScrStartDate, '2015-05-01');
        $hra->setReportName('Employee completes the Health Power Assessment');
        $hra->setAttribute('report_name_link', '/content/1094#1ahpa');
        $reqGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($hraScrStartDate, '2015-05-01');
        $scr->setReportName('Employee participates in the 2014 Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094#1bannscreen');
        $reqGroup->addComplianceView($scr);

        $this->addComplianceViewGroup($reqGroup);

        $bonusGroup = new ComplianceViewGroup('bonus', 'Biometric Bonus Points');
        $bonusGroup->setPointsRequiredForCompliance(0);

        $hraScore70 = new HraScoreComplianceView($hraScrStartDate, $endDate, 70);
        $hraScore70->setReportName('Receive a Health Power Score >= 70');
        $hraScore70->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore70->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 70');
        $hraScore70->setAttribute('points_per_activity', 5);
        $hraScore70->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $bonusGroup->addComplianceView($hraScore70);

        $hraScore80 = new HraScoreComplianceView($hraScrStartDate, $endDate, 80);
        $hraScore80->setReportName('Receive a Health Power Score >= 80');
        $hraScore80->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore80->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 80');
        $hraScore80->setAttribute('points_per_activity', 10);
        $hraScore80->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $bonusGroup->addComplianceView($hraScore80);

        $hraScore90 = new HraScoreComplianceView($hraScrStartDate, $endDate, 90);
        $hraScore90->setReportName('Receive a Health Power Score >= 90');
        $hraScore90->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore90->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 90');
        $hraScore90->setAttribute('points_per_activity', 10);
        $hraScore90->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $bonusGroup->addComplianceView($hraScore90);


        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($hraScrStartDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, '<200');
        $bonusGroup->addComplianceView($tcView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($hraScrStartDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 10);
        $ldlView->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $ldlView->overrideTestRowData(null, null, 100.999, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100');
        $bonusGroup->addComplianceView($ldlView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($hraScrStartDate, $endDate);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $bmiView->setReportName('BMI');
        $bmiView->setAttribute('points_per_activity', 10);
        $bmiView->setAttribute('report_name_link', '/content/1094#2abiometrics');
        $bmiView->overrideTestRowData(null, null, 26.999, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '<27.5');
        $bonusGroup->addComplianceView($bmiView);

        $this->addComplianceViewGroup($bonusGroup);

        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $phyExam = new PlaceHolderComplianceView(null, 0);
        $phyExam->setMaximumNumberOfPoints(20);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Screening follow up');
        $phyExam->addLink(new Link('Verification form', '/resources/4874/2014_PreventiveCare_Cert-2.pdf'));
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam');
        $phyExam->setAttribute('points_per_activity', 20);
        $phyExam->setAttribute('report_name_link', '/content/1094#3aannphys');
        $actGroup->addComplianceView($phyExam);

        $prevServ = new PlaceHolderComplianceView(null, 0);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->addLink(new Link('Verification form', '/resources/4874/2014_PreventiveCare_Cert-2.pdf'));
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $prevServ->setAttribute('report_name_link', '/content/1094#3bprev');
        $actGroup->addComplianceView($prevServ);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Receive a flu shot');
        $fluShot->addLink(new Link('Verification form', '/resources/4874/2014_PreventiveCare_Cert-2.pdf'));
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a flu shot');
        $fluShot->setAttribute('points_per_activity', 10);
        $fluShot->setAttribute('report_name_link', '/content/1094#3cflushot');
        $actGroup->addComplianceView($fluShot);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new Link('Illinois Tobacco Quitline', 'http://www.quityes.org'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by the IL Quitline ');
        $smoking->setAttribute('points_per_activity', 25);
        $smoking->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($smoking);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, null, 5);
        $lessons->setAttribute('points_per_activity', 5);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(25);
        $lessons->setAttribute('report_name_link', '/content/1094#3eonmytime');
        $actGroup->addComplianceView($lessons);

        $onMyTime = new PlaceHolderComplianceView(null, 0);
        $onMyTime->setMaximumNumberOfPoints(25);
        $onMyTime->setName('mytime');
        $onMyTime->setReportName('OnMyTime Courses');
        $onMyTime->addLink(new Link('Well On Target', 'http://www.wellontarget.com/'));
        $onMyTime->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete BCBS Online Program via Well On Target* on Nutrition, Weight Management, Stress Management, Smoking Cessation, etc');
        $onMyTime->setAttribute('points_per_activity', 25);
        $onMyTime->setAttribute('report_name_link', '/content/1094#3eonmytime');
        $actGroup->addComplianceView($onMyTime);

        $kickOff = new PlaceHolderComplianceView(null, 0);
        $kickOff->setMaximumNumberOfPoints(10);
        $kickOff->setName('kickoff');
        $kickOff->setReportName('Wellness Kick off');
        $kickOff->addLink(new FakeLink('Sign in at Event', '#'));
        $kickOff->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the wellness kick off meeting');
        $kickOff->setAttribute('points_per_activity', 10);
        $kickOff->setAttribute('report_name_link', '/content/1094#3fkickoff');
        $actGroup->addComplianceView($kickOff);

        $nutProgram = new PlaceHolderComplianceView(null, 0);
        $nutProgram->setMaximumNumberOfPoints(30);
        $nutProgram->setName('nut_program');
        $nutProgram->setReportName('Health/Nutrition Program Annual Membership');
        $nutProgram->addLink(new Link('HWAG Certification Form', '/resources/4873/2014_nonHWAG_Event_Cert-2.pdf'));
        $nutProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'Membership in health program such as health club, weight watchers, etc. or a healthy food program such as a Fruit of the month club, Community Supported Agriculture, etc');
        $nutProgram->setAttribute('points_per_activity', 10);
        $nutProgram->setAttribute('report_name_link', '/content/1094#3gnutrition');
        $actGroup->addComplianceView($nutProgram);

        $physAct = new PhysicalActivityComplianceView($startDate, $endDate);
        $physAct->setMaximumNumberOfPoints(160);
        $physAct->setMonthlyPointLimit(16);
        $physAct->setAttribute('points_per_activity', '16 points/month');
        $physAct->setReportName('Regular Fitness Training');
        $physAct->setStatusSummary(ComplianceStatus::COMPLIANT, 'Track a minimum of 90 minutes of activity/week on the HMI website');
        $physAct->setAttribute('report_name_link', '/content/1094#3hphysact');
        $actGroup->addComplianceView($physAct);

        $fiveK = new PlaceHolderComplianceView(null, 0);
        $fiveK->setMaximumNumberOfPoints(40);
        $fiveK->setName('5k');
        $fiveK->setReportName('Participate in a 5k');
        $fiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 5k');
        $fiveK->setAttribute('points_per_activity', 20);
        $fiveK->addLink(new Link('HWAG Certification Form', '/resources/4873/2014_nonHWAG_Event_Cert-2.pdf'));
        $fiveK->setAttribute('report_name_link', '/content/1094#3hfitness');
        $actGroup->addComplianceView($fiveK);

        $tenK = new PlaceHolderComplianceView(null, 0);
        $tenK->setMaximumNumberOfPoints(60);
        $tenK->setName('10k');
        $tenK->setReportName('Participate in a 10K');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 30);
        $tenK->setAttribute('report_name_link', '/content/1094#3hfitness');
        $actGroup->addComplianceView($tenK);

        $halfMar = new PlaceHolderComplianceView(null, 0);
        $halfMar->setMaximumNumberOfPoints(100);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setAttribute('points_per_activity', 50);
        $halfMar->setAttribute('report_name_link', '/content/1094#3hfitness');
        $actGroup->addComplianceView($halfMar);

        $fullMar = new PlaceHolderComplianceView(null, 0);
        $fullMar->setMaximumNumberOfPoints(100);
        $fullMar->setName('full_mar');
        $fullMar->setReportName('Participate in a marathon or Olympic distance triathlon');
        $fullMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a marathon or Olympic distance triathlon');
        $fullMar->setAttribute('points_per_activity', 100);
        $fullMar->setAttribute('report_name_link', '/content/1094#3hfitness');
        $actGroup->addComplianceView($fullMar);

        $other = new PlaceHolderComplianceView(null, 0);
        $other->setName('other');
        $other->setReportName('Other HWAG Events');
        $other->setAttribute('report_name_link', '/content/1094#3iother');
        $actGroup->addComplianceView($other);

        $this->addComplianceViewGroup($actGroup);

        $quarterGroup = new ComplianceViewGroup('quarterly', 'Quarterly Wellness Activities');
        $quarterGroup->setPointsRequiredForCompliance(0);

        $bigWinView = new PlaceHolderComplianceView(null, 0);
        $bigWinView->setMaximumNumberOfPoints(50);
        $bigWinView->setName('big_win');
        $bigWinView->setReportName('Biggest Winner Challenge (July-Sept)');
        $bigWinView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Biggest Winner Challenge (July-Sept)');
        $bigWinView->setAttribute('points_per_activity', 'See Program Guide');
        $bigWinView->setAttribute('report_name_link', '/content/1094#4awtloss');
        $bigWinView->addLink(new FakeLink('Admin will enter', '#'));
        $quarterGroup->addComplianceView($bigWinView);

        $intune = new PlaceHolderComplianceView(null, 0);
        $intune->setMaximumNumberOfPoints(25);
        $intune->setName('intune_stress');
        $intune->setReportName('InTune Stress Management (Oct-Dec)');
        $intune->setStatusSummary(ComplianceStatus::COMPLIANT, 'InTune Stress Management (Oct-Dec)');
        $intune->setAttribute('points_per_activity', 25);
        $intune->setAttribute('report_name_link', '/content/1094#4bstress');
        $intune->addLink(new FakeLink('Admin will enter', '#'));
        $quarterGroup->addComplianceView($intune);

        $lucky7 = new PlaceHolderComplianceView(null, 0);
        $lucky7->setMaximumNumberOfPoints(25);
        $lucky7->setName('lucky_7');
        $lucky7->setReportName('Lucky 7 Activity Challenge (Jan-Mar)');
        $lucky7->setStatusSummary(ComplianceStatus::COMPLIANT, 'Lucky 7 Activity Challenge (Jan-Mar)');
        $lucky7->setAttribute('points_per_activity', 25);
        $lucky7->setAttribute('report_name_link', '/content/1094#4clucky');
        $lucky7->addLink(new FakeLink('Admin will enter', '#'));
        $quarterGroup->addComplianceView($lucky7);

        $eatRight = new PlaceHolderComplianceView(null, 0);
        $eatRight->setMaximumNumberOfPoints(25);
        $eatRight->setName('eat_right');
        $eatRight->setReportName('Eat Right for Life (April-June)');
        $eatRight->setStatusSummary(ComplianceStatus::COMPLIANT, 'Eat Right for Life (April-June)');
        $eatRight->setAttribute('points_per_activity', 25);
        $eatRight->setAttribute('report_name_link', '/content/1094#4deat');
        $eatRight->addLink(new FakeLink('Admin will enter', '#'));
        $quarterGroup->addComplianceView($eatRight);

        $hwagLnl = new PlaceHolderComplianceView(null, 0);
        $hwagLnl->setMaximumNumberOfPoints(50);
        $hwagLnl->setName('hwag_lnl');
        $hwagLnl->setReportName('HWAG Lunch & Learn Presentation');
        $hwagLnl->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend a HWAG Lunch & Learn Session');
        $hwagLnl->addLink(new FakeLink('Sign in at event', '#'));
        $hwagLnl->setAttribute('points_per_activity', 10);
        $hwagLnl->setAttribute('report_name_link', '/content/1094#4ehwaglnl');
        $quarterGroup->addComplianceView($hwagLnl);

        $hwagQuiz = new PlaceHolderComplianceView(null, 0);
        $hwagQuiz->setMaximumNumberOfPoints(30);
        $hwagQuiz->setName('hwag_quiz');
        $hwagQuiz->setReportName('HWAG Quiz');
        $hwagQuiz->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete the HWAG quiz');
        $hwagQuiz->setAttribute('points_per_activity', 5);
        $hwagQuiz->setAttribute('report_name_link', '/content/1094#4fhwagquiz');
        $hwagQuiz->addLink(new FakeLink('Admin will enter', '#'));
        $quarterGroup->addComplianceView($hwagQuiz);

        $teamAlias = 'ucan-2014-walking';

        $teamPart = new PlaceHolderComplianceView(null, 0);
        $teamPart->setMaximumNumberOfPoints(100);
        $teamPart->setName('team_participate');
        $teamPart->setReportName('Team Walking Challenge');
        $teamPart->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a Quarterly Team Walking Challenge.');
        $teamPart->setAttribute('points_per_activity', 25);
        $teamPart->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamPart->setAttribute('report_name_link', '/content/1094#4gteamwalk');
        $quarterGroup->addComplianceView($teamPart);

        $teamWinner = new PlaceHolderComplianceView(null, 0);
        $teamWinner->setMaximumNumberOfPoints(40);
        $teamWinner->setName('team_winner');
        $teamWinner->setReportName('Team Walking Challenge Winner');
        $teamWinner->setStatusSummary(ComplianceStatus::COMPLIANT, 'Team that wins the Quarterly Walking Challenge');
        $teamWinner->setAttribute('points_per_activity', 10);
        $teamWinner->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamWinner->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $quarterGroup->addComplianceView($teamWinner);

        $walk6000 = new HmiMultipleAverageStepsComplianceView(6000, 10);
        $walk6000->setReportName('Walk an average of 6,000 steps/day');
        $walk6000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 6,000 steps/day');
        $walk6000->setMaximumNumberOfPoints(40);
        $walk6000->setAttribute('points_per_activity', 10);
        $walk6000->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $walk6000->setAttribute('report_name_link', '/content/1094#4iindwalk');
        $quarterGroup->addComplianceView($walk6000);

        $walk8000 = new HmiMultipleAverageStepsComplianceView(8000, 10);
        $walk8000->setReportName('Walk an average of 8,000 steps/day');
        $walk8000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 8,000 steps/day');
        $walk8000->setMaximumNumberOfPoints(40);
        $walk8000->setAttribute('points_per_activity', 10);
        $walk8000->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $walk8000->setAttribute('report_name_link', '/content/1094#4iindwalk');
        $quarterGroup->addComplianceView($walk8000);

        $walk10000 = new HmiMultipleAverageStepsComplianceView(10000, 10);
        $walk10000->setReportName('Walk an average of 10,000 steps/day');
        $walk10000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 10,000 steps/day');
        $walk10000->setMaximumNumberOfPoints(40);
        $walk10000->setAttribute('points_per_activity', 10);
        $walk10000->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $walk10000->setAttribute('report_name_link', '/content/1094#4iindwalk');
        $quarterGroup->addComplianceView($walk10000);

        foreach(array(array('2014-06-01', '2014-08-31'), array('2014-09-01', '2014-11-30'), array('2014-12-01', '2015-02-28'), array('2015-03-01', '2015-05-31')) as $dateRanges) {
            $walk6000->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk8000->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk10000->addDateRange($dateRanges[0], $dateRanges[1]);
        }

        $this->addComplianceViewGroup($quarterGroup);
    }
}

class UCAN2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(false);

        $this->addStatusCallbackColumn('Requirement', function($status) {
           return $status->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT);
        });

        $this->addStatusCallbackColumn('Points Per Activity', function($status) {
            if($status->getComplianceView()->getComplianceViewGroup()->getName() == 'bonus') {
                return $status->getComment();
            } else {
                return $status->getComplianceView()->getAttribute('points_per_activity');
            }
        });
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
            }

            .phipTable,
            .phipTable th,
            .phipTable td {
                font-size:0.95em;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                // Expand quarterly group header to be two columns
                $('.headerRow-quarterly th').attr('colspan', 2);
                $('.headerRow-quarterly td:first').remove();

                // Span rows for prev services / flushot
                $('.view-prev_serv td:first')
                    .attr('rowspan', 2)
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                          '<br/><strong>B-C</strong>. <a href="/content/1094#3bprev">Preventative Services</a>');


                $('.view-flu_shot td:first').remove();



                // Span rows for quarterly challenges
                $('.view-big_win td:first')
                    .attr('rowspan', 4)
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                          '<br/><strong>A-D</strong>. <a href="/content/1094#4awtloss">Quarterly Health Challenge</a>');

                $('.view-intune_stress td:first').remove();
                $('.view-lucky_7 td:first').remove();
                $('.view-eat_right td:first').remove();

                // Span rows for individual walking challenge
                $('.view-hmi_multi_challenge_6000 td:first')
                    .attr('rowspan', 3)
                    .html('<strong>I</strong>. <a href="/content/1094#4iindwalk">Individual Walking Challenge</a>' +
                          '<br/><br/>Points will be awarded at the end of' +
                          ' each quarter based on the average steps logged' +
                          ' during the period.  Max Points listed are for' +
                          ' the entire year, all 4 quarters.');

                $('.view-hmi_multi_challenge_8000 td:first').remove();
                $('.view-hmi_multi_challenge_10000 td:first').remove();

                // Remove first 2 cols from first group

                $('.headerRow-required td:first').remove();
                $('.headerRow-required td:first').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.headerRow-required th').attr('colspan', 3);
                $('.view-complete_hra td:eq(0)').attr('colspan', 3);
                $('.view-complete_screening td:eq(0)').attr('colspan', 3);

                // Missing headers
                $('.headerRow-bonus td:eq(0)').html('Requirement');
                $('.headerRow-bonus td:eq(1)').html('Result');
                $('.headerRow-activities td:eq(0)').html('Requirement');
                $('.headerRow-activities td:eq(1)').html('Points Per Activity');
                $('.headerRow-quarterly td:eq(0)').html('Points Per Activity');

                // Span 5k/10k etc events
                $('.view-5k td:first')
                    .attr('rowspan', '4')
                    .html('<strong>J-M</strong>. Run/Walk a Race<br/><br/><p>In addition to ' +
                          'earning points, Entry fees will be covered ' +
                          'for UCAN sponsored races: <br/> ' +
                          '&bull; Lawndale 5k (Sept) <br/> ' +
                          '&bull; AIDS Run/Walk (Oct) <br/> ' +
                          '&bull; Turkey Trot (Nov) <br/> ' +
                          '&bull; Earth Day 5K (April) <br/> ');

                $('.view-5k td:last').attr('rowspan', '4');
                $('.view-10k td:first').remove();
                $('.view-10k td:last').remove();
                $('.view-half_mar td:first').remove();
                $('.view-half_mar td:last').remove();
                $('.view-full_mar td:first').remove();
                $('.view-full_mar td:last').remove();

                // New row for misc links / info

                $('<tr><td colspan="6" style="text-align:center">' +
                  '<a href="http://intranet.uhlich.org/fmc/default.aspx">' +
                  'Employee Intranet</a>' +
                  '</td></tr>').insertAfter($('.headerRow-quarterly'));
            });
        </script>

        <div class="page-header">
            <h4>UCAN 2014-15 Wellness Program</h4>
        </div>

        <p>UCAN cares about your health! We have partnered with HMI Health and Axion RMS
            to implement our Wellness Program. The wellness
        program provides you with fun, robust programming options geared
        towards specific areas of your health that need improvement. This
        Wellness Program is your way to better, healthier living.</p>


        <p style="font-weight:bold;text-align:center;">
            HOW DOES THE PROGRAM WORK?</p>

        <p><strong>Employees that complete the 2014 Health Screening and
            Health Power Assessment (HPA) are eligible to participate.</strong>
            Participation in the program will earn wellness points that will be
            tracked in the table below.  Rewards will be based on points earned
            between 6/1/14 and 5/31/2015.</p>

        <p> Please note that the incentive structure has changed!  Employees
            now will earn cash rewards when they reach the designated points
            for each of the levels outlined in the chart below.  The maximum
            cash reward available per year is $450!</p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Status Level</th>
                <th>Participation</th>
                <th>Points</th>
                <th>Reward</th>
            </tr>
            <tr>
                <td>Bronze</td>
                <td>Employee completes the Wellness Screening and
                    Health Power Assessment</td>
                <td><strong>Requirement for Program Participation</strong></td>
                <td>$75</td>
            </tr>
            <tr>
                <td>Silver</td>
                <td>Complete Bronze level and accumulate 75 points</td>
                <td><strong>75 Total Points</strong></td>
                <td>$100</td>
            </tr>
            <tr>
                <td>Gold</td>
                <td>Complete Bronze and Silver levels and accumulate 75
                    additional points</td>
                <td><strong>150 Total Points</strong></td>
                <td>$125</td>
            </tr>
            <tr>
                <td>Platinum</td>
                <td>Complete Bronze, Silver, and Gold levels and accumulate 100
                    additional points</td>
                <td><strong>250 Total Points</strong></td>
                <td>$150</td>
            </tr>
        </table>

        <p style="text-align:center">Compliance reports will be generated
            monthly and rewards will be distributed via payroll as earned.
            Employee achievements will be recognized on HWAG site and via
            email announcement.</p>
        <?php
    }
}
