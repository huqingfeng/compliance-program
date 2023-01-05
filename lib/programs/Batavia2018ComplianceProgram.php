<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));


class Batavia2018TobaccoFormComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'non_smoker_view';
    }

    public function getDefaultReportName()
    {
        return 'Non Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 20);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class BataviaMeditationComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $questionId)
    {
        $this->setDateRange($startDate, $endDate);
        $this->activityId = $activityId;
        $this->questionId = $questionId;
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function setPointsMultiplier($multiplier)
    {
        $this->multiplier = $multiplier;
    }

    public function setMinutesDivisorForPoints($value)
    {
        $this->pointDivisor = $value;

        return $this;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
        $totalMinutes = 0;
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();
            if(isset($answers[$this->questionId])) {
                $totalMinutes += $answers[$this->questionId]->getAnswer();
            }
        }

        $points = floor(($totalMinutes / $this->pointDivisor) * $this->multiplier);

        return new ComplianceViewStatus($this, null, $points);
    }

    private $multiplier = 1;
    private $pointDivisor = 1;
}

class BataviaMultipleAverageStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer, $activityId, $questionId)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
        $this->activityId = $activityId;
        $this->questionId = $questionId;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_multi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Multi Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $records = $this->getRecords($user);

        $manualSteps = [];
        foreach($records as $record) {
            $recordDate = date('Y-m-d', strtotime($record->getDate()));
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId])) {
                if (isset($manualSteps[$recordDate])) {
                    $manualSteps[$recordDate] += (int)$answers[$this->questionId]->getAnswer();
                } else {
                    $manualSteps[$recordDate] = (int)$answers[$this->questionId]->getAnswer();
                }
            }
        }

        $points = 0;
        foreach ($manualSteps as $entry) {
            if ($entry >= $this->threshold) $points++;
        }

        $status = new ComplianceViewStatus($this, null, floor($points));

        return $status;
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    private $threshold;
    private $pointsPer;
}


class Batavia2018ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');

        $totalView = $groupStatus->getComplianceViewStatus('total');
        extract($groupStatus->getComplianceViewStatuses());
        $today = strtotime(date('Y'));
        ?>
        <tr class="headerRow">
            <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
                    ->getReportName()) ?></th>
            <td>Total # Earned</td>
            <td>Status</td>
            <td>Minimum Points Needed</td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalView->getPoints() ?></td>
            <td class="points"><?= ($totalView->getPoints()>=150) ? "Done" : "Not Done" ?></td>
            <td class="points">≥ 150 points</td>
        </tr>

        <?php
    }

    protected function printStatusView($view)
    {

    }

    protected function showGroup($group)
    {
        return $group->getName() == 'required';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <style>
            .phipTable .headerRow {
                background: #2B3A91;
            }

            .bata_container {
                width: 660px;
                margin: auto;
                margin-bottom: 5px;
            }

            .bata_container .table_container {
                border: 1px solid #5B9BD5;
            }

            .bata_header {
                background: #2B3A91;
                text-transform: uppercase;
                color: white;
                font-weight: 600;
                text-align: center;
                font-size: 1.8rem;
                letter-spacing: 1px;
                padding-bottom: 1px;
                padding-top: 7px;
                margin-bottom: 5px;
            }

            .bata_table {
                display: flex;
                flex-wrap: nowrap;
            }

            .table_container .separator {
                height: 1px;
                width: 100%;
                background: #5B9BD5;
            }

            .bata_table.blue {
                background: #2F3D90;
                color: #fff;
            }

            .bata_table .bata_cell {
                display: inline-flex;
                width: 25%;
                align-items: center;
                text-align: center;
                justify-content: center;
                border-right: 1px solid #5B9BD5;
                padding: 4px;
                box-sizing: border-box;
            }

            .bata_table.triple {
                min-height: 50px;
            }

            .bata_table.blue .bata_cell {
                font-weight: 600;
            }

            .bata_table .bata_cell:nth-child(1) {
                width: 30%;
            }

            .bata_table .bata_cell:nth-child(3) {
                width: 20%;
            }

            .bata_table.triple .bata_cell:nth-child(1) {
                width: 55%;
            }

            .bata_table.triple .bata_cell:nth-child(2) {
                width: 20%;
            }

            .bata_table.triple .bata_cell:nth-child(3) {
                width: 25%;
            }

            .bata_table .bata_cell:last-child {
                border-right: none;
            }

            .image_banner {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 40px;
            }

            .image_banner img {
                height: max-content;
            }

        </style>

        <script type="text/javascript">

        </script>

<!--        <p>Hello --><?php //echo $status->getUser()->getFullName() ?><!--,</p>-->

        <div class="image_banner">
            <img src="https://services-0cec4a470321434cb661f0db35a6a868.hpn.com/resources/10071/BCIFIT.png">
            <img src="https://services-fe184f27c1c048a8848330c22b67573b.hpn.com/resources/10072/HMI Logo.png">
        </div>

        <p>Welcome to your summary page for Batavia Container’s 2018 <strong>“Where Fit Happens”</strong> Wellness Program!</p>

        <p>Batavia Container cares about your health and overall well-being! We have partnered with HMI and Assurance to implement our <strong style="display:inline-block; line-height: 1; border-bottom: 2px solid #000;">NEW</strong> Wellness Program. This year’s wellness program provides you with fun, robust programming options geared towards specific areas of your health that may need improvement.</p>

        <p>Employees reaching the necessary number of points are eligible to receive up to $150 in cash rewards! Those participating will also be entered into a raffle to receive other incentives such as lunch with President Clay Shaw, a front row parking spot for a week, and a variety of gift card options!</p>

        <p>In order to earn the $150 reward, you must earn at least 150 points by December 17th, 2018. These points will be tracked through the HMI Wellness Website at <a href="http://www.myhmihealth.com">www.myhmihealth.com</a>. You can earn these points from actions taken for your good health and wellbeing across the action categories below.</p>

        <div class="bata_container">
            <div class="bata_header">
                <p>Wellness Rewards Structure</p>
            </div>

            <div class="table_container">
                <div class="bata_table blue">
                    <div class="bata_cell">INCENTIVE PERIOD</div>
                    <div class="bata_cell">ANNUAL<br>INCENTIVE<br>REQUIREMENT</div>
                    <div class="bata_cell">REWARD*</div>
                    <div class="bata_cell">REWARD<br>DISTRIBUTION*</div>
                </div>
                <div class="separator"></div>
                <div class="bata_table">
                    <div class="bata_cell">July 1 — December 17,<br>2018</div>
                    <div class="bata_cell">Earn 150 Points</div>
                    <div class="bata_cell">$150.00</div>
                    <div class="bata_cell"><em>12/20/2018</em></div>
                </div>
                <div class="separator"></div>
                <div class="bata_table blue triple">
                    <div class="bata_cell">TOTAL REWARDS</div>
                    <div class="bata_cell">$150.00</div>
                    <div class="bata_cell"></div>
                </div>
            </div>
        </div>
        <?php
    }
}

class Batavia2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();

            $query = ScreeningTable::getInstance()
                ->getScreeningsForUser($this->getActiveUser(), array('execute' => false));
            $screenings = ScreeningTable::getInstance()
                ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
            $screening = $screenings->getFirst();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new Batavia2018ComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2018-01-01', '2018-12-17'), array(), 150);
        $totalView->setReportName('<strong>By 12/17/2018</strong>');
        $totalView->setName('total');
        $totalsGroup->addComplianceView($totalView);

        $this->addComplianceViewGroup($totalsGroup);
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'Document the points you earn from any of these action areas by using the action links');
        $required->setPointsRequiredForCompliance(0);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Complete the Health Power Assessment');
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Annual Wellness Screening');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign-Up', '/compliance/hmi-2016/schedule/content/wms2-appointment-center'));
        $screening->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $required->addComplianceView($screening);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(25);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each hour of activity');
        $physicalActivityView->setMaximumNumberOfPoints(40);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($physicalActivityView);

        $fitbitStep = new BataviaMultipleAverageStepsComplianceView($startDate, $endDate, 6000, 1, 414, 110);
        $fitbitStep->setReportName('Daily Steps - 1 pt per 6,000 steps (Track via Moves App, Fitbit, Apple Watch, etc.)');
        $fitbitStep->setMaximumNumberOfPoints(50);
        $fitbitStep->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=414'));
        $required->addComplianceView($fitbitStep);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained - 10 pts for each screening done this year<br>(Mammogram, Prostate, Routine dental/eye exam, etc.)');
        $preventiveView->setMaximumNumberOfPoints(50);
        $preventiveView->emptyLinks();
        $preventiveView->addLink(new Link("Download Form", "https://services-21ec195632ea49408bec018ea4d9e353.hpn.com/resources/10185/Batavia Container Healthcare Provider Form 2018.pdf",false,"_blank"));
        $preventiveView->addLink(new Link("Submit Form", "/compliance/hmi-2016/my-rewards/wms1/content/chp-document-uploader"));
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - 1 pt for each hour of volunteering<br>(Submit proof by uploading receipt, sign-in form, etc.)');
        $volunteeringView->setMaximumNumberOfPoints(30);
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->addLink(new Link("Submit Form", "/compliance/hmi-2016/my-rewards/wms1/content/chp-document-uploader"));
        $required->addComplianceView($volunteeringView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 5);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood – 5 points for each time donated');
        $donateBlood->setMaximumNumberOfPoints(25);
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor or Primary Care Physician');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 5);
        $healthy->setMaximumNumberOfPoints(40);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day - 5 pts per entry');
        $required->addComplianceView($healthy);

        $juneQuarterlyChallenge = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 15);
        $juneQuarterlyChallenge->setMaximumNumberOfPoints(15);
        $juneQuarterlyChallenge->setReportName('Participate in July Quarterly Challenge - “Where Fit Happens” Wellness Kick-Off');
        $juneQuarterlyChallenge->emptyLinks();
        $juneQuarterlyChallenge->addLink(new Link("Sign in at the event", "", false, "_self",false, true));
        $required->addComplianceView($juneQuarterlyChallenge);

        $septemberQuarterlyChallenge = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 425, 15);
        $septemberQuarterlyChallenge->setMaximumNumberOfPoints(15);
        $septemberQuarterlyChallenge->setReportName('Participate in 1 of 4 September Quarterly Challenges – “Get Financially Fit” Seminar or “Back to Fit” 30 Minute Physical Fitness Seminar');
        $septemberQuarterlyChallenge->emptyLinks();
        $septemberQuarterlyChallenge->addLink(new Link("Sign in at the event", "", false, "_self",false, true));
        $required->addComplianceView($septemberQuarterlyChallenge);

        $novemberQuarterlyChallenge = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 426, 15);
        $novemberQuarterlyChallenge->setMaximumNumberOfPoints(15);
        $novemberQuarterlyChallenge->setReportName('Participate in November Quarterly Challenge – Batavia Sponsored Event – TBD');
        $novemberQuarterlyChallenge->emptyLinks();
        $novemberQuarterlyChallenge->addLink(new Link("Sign in at the event", "", false, "_self",false, true));
        $required->addComplianceView($novemberQuarterlyChallenge);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 452, 20);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setReportName('Get an Annual Physical Exam');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->addLink(new Link("Download Form", "https://services-21ec195632ea49408bec018ea4d9e353.hpn.com/resources/10185/Batavia Container Healthcare Provider Form 2018.pdf",false,"_blank"));
        $annualPhysicalExamView->addLink(new Link("Submit Form", "/compliance/hmi-2016/my-rewards/wms1/content/chp-document-uploader"));
        $required->addComplianceView($annualPhysicalExamView);

        $nonSmokerView = new Batavia2018TobaccoFormComplianceView($startDate, $endDate);
        $nonSmokerView->setReportName('Non-Smoker');
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(20);
        $required->addComplianceView($nonSmokerView);

        $this->addComplianceViewGroup($required);
    }
}