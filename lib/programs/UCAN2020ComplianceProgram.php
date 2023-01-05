<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class Ucan2020RunWalkComplianceView extends CompleteArbitraryActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }


    public function __construct($startDate, $endDate, $activityId, $activityType)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->activityId = $activityId;
        $this->activityType = $activityType;

        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $points = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if($answers[219]->getAnswer() != $this->activityType) continue;

            $points += isset($answers[219]) && isset(self::$activityPerPoints[$answers[219]->getAnswer()]) ?
                self::$activityPerPoints[$answers[219]->getAnswer()] : 0;
        }

        $status =  new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private static $activityPerPoints = array(
        '5k' => 20,
        'Bike Race' => 30,
        '10k' => 40,
        'Half-Marathon' => 50,
        'Sprint Distance Triathlon' => 50
    );

    private $activityId;
}



class Ucan2020ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Ucan2020ComplianceProgramReportPrinter();

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

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities (Must be completed by June 30, 2020)');
        $reqGroup->setPointsRequiredForCompliance(0);

        $hraScreeningStart = '2020-05-26';
        $hraScreeningEnd = '2021-05-31';

        $hra = new CompleteHRAComplianceView($hraScreeningStart, $hraScreeningEnd);
        $hra->setReportName('Employee completes the Health Power Assessment (HPA)');
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $hra->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $reqGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($hraScreeningStart, $hraScreeningEnd);
        $scr->setReportName('Employee participates in the Offsite Wellness Screening (PPO members only) OR Visits Personal Physician for screening & lab work (all HMO members; alternative to offsite screening for PPO members).');
        $scr->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $scr->emptyLinks();
        $scr->addLink(new Link('Individual Program Form <br />', '/resources/10561/2020_UCAN_IP_040720.pdf'));
        $scr->addLink(new Link('Health Provider Form', '/resources/10562/2020_UCAN_HPF_040620.pdf'));
        $reqGroup->addComplianceView($scr);

        $personalPhysician = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $personalPhysician->setName('visit_personal_physician');
        $personalPhysician->setReportName('HMO, Visits Personal Physician for screening & lab work (all HMO members; alternative to offsite screening for PPO members).');
        $personalPhysician->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $personalPhysician->addLink(new FakeLink('Download Consent Form', '#'));
//        $reqGroup->addComplianceView($personalPhysician);

        $this->addComplianceViewGroup($reqGroup);


        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $phyExam = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30974, 20);
        $phyExam->setMaximumNumberOfPoints(20);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Screening follow up');
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam');
        $phyExam->setAttribute('points_per_activity', 20);
        $actGroup->addComplianceView($phyExam);

        $prevServ = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30975, 10);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $actGroup->addComplianceView($prevServ);

        $fluShot = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30976, 10);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Preventative Services - Flu Shot');
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a flu shot');
        $fluShot->setAttribute('points_per_activity', 10);
        $actGroup->addComplianceView($fluShot);

        $registerDownload = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 75678, 10);
        $registerDownload->setMaximumNumberOfPoints(10);
        $registerDownload->setName('register_download');
        $registerDownload->setReportName('Register and Download');
        $registerDownload->addLink(new Link('Link to BlueAccess for Members', 'https://members.hcsc.net/wps/portal/bam', false, '_blank'));
        $registerDownload->setStatusSummary(ComplianceStatus::COMPLIANT, '<a href="https://members.hcsc.net/wps/portal/bam" target="_blank">BlueAccess Member Registration</a> AlwaysOn App Download');
        $registerDownload->setAttribute('points_per_activity', 10);
        $actGroup->addComplianceView($registerDownload);

        $smoking = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 75679, 25);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by the Quitline: <a href="http://www.quityes.org">www.quityes.org</a>');
        $smoking->setAttribute('points_per_activity', 25);
        $actGroup->addComplianceView($smoking);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, null, 5);
        $lessons->setAttribute('points_per_activity', 5);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(25);
        $actGroup->addComplianceView($lessons);

        $onMyTime = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 75680, 25);
        $onMyTime->setMaximumNumberOfPoints(25);
        $onMyTime->setName('mytime');
        $onMyTime->setReportName('OnMyTime Courses <br /> <a href="http://www.WellonTarget.com" target="_blank">www.WellonTarget.com</a>');
        $onMyTime->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete BCBS Online Program via Well On Target* on Nutrition, Weight Management, Stress Management, Smoking Cessation, etc');
        $onMyTime->setAttribute('points_per_activity', 25);
        $actGroup->addComplianceView($onMyTime);

        $employeeBenefits = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30977, 10);
        $employeeBenefits->setMaximumNumberOfPoints(10);
        $employeeBenefits->setName('kickoff');
        $employeeBenefits->setReportName('Employee Benefits Fair');
        $employeeBenefits->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the Employee Benefits Fair in May');
        $employeeBenefits->setAttribute('points_per_activity', 10);
        $actGroup->addComplianceView($employeeBenefits);

        $hwagLnl = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30978, 15);
        $hwagLnl->setMaximumNumberOfPoints(30);
        $hwagLnl->setName('hwag_lnl');
        $hwagLnl->setReportName('HWAG Lunch & Learn Presentation');
        $hwagLnl->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend a HWAG Lunch and Learn Session');
        $hwagLnl->setAttribute('points_per_activity', 15);
        $actGroup->addComplianceView($hwagLnl);

        $hwagQuiz = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30979, 5);
        $hwagQuiz->setMaximumNumberOfPoints(30);
        $hwagQuiz->setName('hwag_quiz');
        $hwagQuiz->setReportName('HWAG Quiz');
        $hwagQuiz->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete the HWAG quiz');
        $hwagQuiz->setAttribute('points_per_activity', 5);
        $actGroup->addComplianceView($hwagQuiz);

        $nutProgram = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 30980, 10);
        $nutProgram->setMaximumNumberOfPoints(10);
        $nutProgram->setName('on_target_member');
        $nutProgram->setReportName('Well onTarget Member Portal');
        $nutProgram->setStatusSummary(ComplianceStatus::COMPLIANT, '$25 Monthly Gym Membership and Blue Points Rewards');
        $nutProgram->setAttribute('points_per_activity', 10);
        $actGroup->addComplianceView($nutProgram);

        $physAct = new PhysicalActivityComplianceView($startDate, $endDate);
        $physAct->setMaximumNumberOfPoints(160);
        $physAct->setMonthlyPointLimit(16);
        $physAct->setAttribute('points_per_activity', '16 points/month');
        $physAct->setReportName('Regular Fitness Training');
        $physAct->setStatusSummary(ComplianceStatus::COMPLIANT, 'Track a minimum of 90 minutes of activity/week on the HMI website');
        $actGroup->addComplianceView($physAct);

        $fiveK = new Ucan2020RunWalkComplianceView($startDate, $endDate, 30981, '5k');
        $fiveK->setMaximumNumberOfPoints(40);
        $fiveK->setName('5k');
        $fiveK->setReportName('Participate in a 5k');
        $fiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 5k');
        $fiveK->setAttribute('points_per_activity', 20);
        $actGroup->addComplianceView($fiveK);

        $bikeRace = new Ucan2020RunWalkComplianceView($startDate, $endDate, 30981, 'Bike Race');
        $bikeRace->setMaximumNumberOfPoints(60);
        $bikeRace->setName('bike_race');
        $bikeRace->setReportName('Participate in a Bike Race');
        $bikeRace->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a Bike Race');
        $bikeRace->setAttribute('points_per_activity', 30);
        $actGroup->addComplianceView($bikeRace);

        $tenK = new Ucan2020RunWalkComplianceView($startDate, $endDate, 30981, '10k');
        $tenK->setMaximumNumberOfPoints(80);
        $tenK->setName('10k');
        $tenK->setReportName('Participate in a 10K');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 40);
        $actGroup->addComplianceView($tenK);

        $halfMar = new Ucan2020RunWalkComplianceView($startDate, $endDate, 30981, 'Half-Marathon');
        $halfMar->setMaximumNumberOfPoints(100);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Participate in a half-marathon');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon');
        $halfMar->setAttribute('points_per_activity', 50);
        $actGroup->addComplianceView($halfMar);

        $sprintDistanceTriathlon = new Ucan2020RunWalkComplianceView($startDate, $endDate, 30981, 'Sprint Distance Triathlon');
        $sprintDistanceTriathlon->setMaximumNumberOfPoints(100);
        $sprintDistanceTriathlon->setName('sprint_distance_triathlon');
        $sprintDistanceTriathlon->setReportName('Participate in a Sprint distance triathlon');
        $sprintDistanceTriathlon->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a Sprint distance triathlon');
        $sprintDistanceTriathlon->setAttribute('points_per_activity', 50);
        $actGroup->addComplianceView($sprintDistanceTriathlon);

        $other = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 75825, 50);
        $other->setMaximumNumberOfPoints(100);
        $other->setName('other');
        $other->setReportName('Other HWAG Events');
        $other->setAttribute('points_per_activity', 50);
        $other->setStatusSummary(ComplianceStatus::COMPLIANT, 'Donate Blood CPR/AED Certified');

        $actGroup->addComplianceView($other);

        $walking = new PlaceHolderComplianceView(null, 0);
        $walking->setMaximumNumberOfPoints(60);
        $walking->setName('walking');
        $walking->setReportName('Quarterly Walking Challenge');
        $walking->setAttribute('points_per_activity', 15);
        $walking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in walking challenge');
        $walking->addLink(new FakeLink('Admin will Enter', '#'));
        $actGroup->addComplianceView($walking);

        $this->addComplianceViewGroup($actGroup);

    }
}

class Ucan2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(true);

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

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

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
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                    '<br/><strong>B</strong>. Preventative Services');

                // Span rows for quarterly challenges
                $('.view-big_win td:first')
                    .attr('rowspan', 4)
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                    '<br/><strong>A-D</strong>. <a href="/content/1094#4awtloss">Quarterly Health Challenge</a>');

                $('.view-intune_stress td:first').remove();
                $('.view-lucky_7 td:first').remove();
                $('.view-eat_right td:first').remove();

                // Span rows for individual walking challenge
                $('.view-walk_july_aug td:first')
                    .attr('rowspan', 3)
                    .html('<strong>A</strong>. <a href="/content/1094#4iindwalk">HealthTrails Individual/Team Challenges</a>' +
                    '<br/><br/>Points will be awarded at the end of each 6-week challenge based on the average steps logged during the period (individual and team based). ');
                $('.view-walk_july_aug td:last').attr('rowspan', '3');
                $('.view-walk_sep_oct td:first').remove();
                $('.view-walk_sep_oct td:last').remove();
                $('.view-walk_nov_dec td:first').remove();
                $('.view-walk_nov_dec td:last').remove();

                $('.view-hmi_multi_challenge_8000 td:first').remove();
                $('.view-hmi_multi_challenge_10000 td:first').remove();

                // Remove first 2 cols from first group

                $('.headerRow-required td:first').remove();
                $('.headerRow-required td:first').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-visit_personal_physician td:eq(1)').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-visit_personal_physician td:eq(1)').remove();
                $('.headerRow-required th').attr('colspan', 3);
                $('.view-complete_hra td:eq(0)').attr('colspan', 3);
                $('.view-complete_screening td:eq(0)').attr('colspan', 3);
                $('.view-visit_personal_physician td:eq(0)').attr('colspan', 3);



                // Missing headers
                $('.headerRow-bonus td:eq(0)').html('Requirement');
                $('.headerRow-bonus td:eq(1)').html('Result');
                $('.headerRow-activities td:eq(0)').html('Requirement');
                $('.headerRow-activities td:eq(1)').html('Points Per Activity');
                $('.headerRow-quarterly td:eq(0)').html('Points Per Activity');

                // Span 5k/10k etc events
                $('.view-5k td:first')
                    .attr('rowspan', '5')
                    .html('<strong>M-Q</strong>. Run/Walk an Independent or Sponsored Race<br/><br/><p>' +
                    'In addition to earning points, <br/>' +
                    'Entry fees will be covered for UCAN sponsored races');

                $('.view-5k td:last').attr('rowspan', '5');
                $('.view-bike_race td:first').remove();
                $('.view-bike_race td:last').remove();
                $('.view-10k td:first').remove();
                $('.view-10k td:last').remove();
                $('.view-half_mar td:first').remove();
                $('.view-half_mar td:last').remove();
                $('.view-sprint_distance_triathlon td:first').remove();
                $('.view-sprint_distance_triathlon td:last').remove();

                // Replace normal space with a nonbreaking space to prevent word wrapping
                $('tr.view-complete_screening td.links a')[2].innerHTML = "MD&nbsp;Links";
            });
        </script>

        <div class="page-header">
            <h4>UCAN 2020-21 Wellness Program</h4>
        </div>

        <p>UCAN cares about your health! We have partnered with HMI Health and Axion RMS to implement our Wellness Program.
            The wellness program provides you with fun, robust programming options geared towards specific areas of your
            health that need improvement. Take action and commit to a healthier, happier life with your Wellness Program</p>


        <p style="font-weight:bold;text-align:center;">
            HOW DOES THE PROGRAM WORK?</p>

        <p><strong>Employees that complete the 2020 Health Screening and Health Power Assessment (HPA) are eligible to participate.</strong>
            Participation in the program will earn wellness points that will be tracked according to the table below.
            Rewards will be based on points earned between 7/1/2020 and 5/31/2021.</p>

        <p> Participants can earn points in the UCAN Be Health Program by achieving designated health OUTCOMES and through
         participating in the program activities. Employees earn cash rewards when they reach the designated points for
          each of the levels outlines in the chart below. <strong>The maximum cash reward available per year is $450!</strong></p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Status Level</th>
                <th>Participation</th>
                <th>Points</th>
                <th>Reward</th>
            </tr>
            <tr>
                <td>Bronze</td>
                <td>Complete Health Power Assessment</td>
                <td><strong>25</strong></td>
                <td>$25</td>
            </tr>
            <tr>
                <td>Silver</td>
                <td>Complete Health Screening</td>
                <td><strong>50</strong></td>
                <td>$50</td>
            </tr>
            <tr>
                <td>Gold</td>
                <td>Complete Silver Level + earn 75 additional points</td>
                <td><strong>150</strong></td>
                <td>$150</td>
            </tr>
            <tr>
                <td>Platinum</td>
                <td>Complete Silver + Gold Levels + earn 75 additional points</td>
                <td><strong>225</strong></td>
                <td>$225</td>
            </tr>
        </table>


        <p style="text-align:center">Compliance reports will be generated
            monthly and rewards will be distributed via payroll as earned.</p>
        <?php
    }
}
