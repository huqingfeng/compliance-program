<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class VillageOfLementMultipleAverageStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
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

        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $points = 0;

        foreach($data['dates'] as $date) {
            if($date >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private $threshold;
    private $pointsPer;
}


class VillageOfLemont2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td style="text-align:center;font-style:italic;"><?php echo $totalView->isCompliant() ? 'Completed' : ($today > $totalView
                ->getComplianceView()->getEndDate() ? 'Incomplete' : 'In Progress') ?></td>
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
        <script type="text/javascript">
            $(function() {
                $('.phipTable').after('<br /><p style="font-weight:bold;">Those employees who earn 250 or more points by December 18, 2015 will earn a $25 gift card!</p>');
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2016 Wellness Rewards benefit at Village of Lemont.
            This year we are continuing our wellness rewards incentive program and have added a few extra incentives.</p>

        <p>In 2015, employees reaching the necessary number of points will receive a discount on their 2016 health
            insurance premium (Jan-June). There will also be prizes for those employees who participate but are not
            on our health plans. Also, monthly raffles will be held for all those actively participating.</p>

        <p>The deadline to receive the 150 points (and obtain the premium discount) is December 18, 2015
            (see below). You can earn these points from actions taken for your good health and wellbeing across
            the action categories below.</p>

        <p style="text-align:center;">
            <a href="/content/1094">
                Click here for more details about the 2016 Wellness Rewards benefit and requirements.
            </a>
        </p>

        <p><strong>Update Notice:</strong> To get actions done and earn points click on the links below.
            If the points or status did not change for an item you are working on, you may need to go back
            and enter missing information or entries to earn more points. We wish you much success in your
            healthy endeavors..</p>
        <?php
    }
}

class VillageOfLemont2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new VillageOfLemont2016ComplianceProgramReportPrinter();
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);
        $printer->setShowLegend(false);


        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2015-12-31'), array(), 150);
        $totalView->setReportName('<strong>By 12/18/2015</strong>');
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

//        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
//        $screening->setReportName('Annual Onsite Wellness Screening in June');
//        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
//        $screening->setAttribute('report_name_link', '/content/1094#ascreen');
//        $required->addComplianceView($screening);

//        $hra = new CompleteHRAComplianceView($startDate, $endDate);
//        $hra->setReportName('Complete the Health Power Assessment');
//        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
//        $hra->setAttribute('report_name_link', '/content/1094#bhpa');
//        $required->addComplianceView($hra);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning lessons');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(40);
        $elearningView->setAttribute('report_name_link', '/content/1094#aelearn');
        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(180);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#bphysact');
        $required->addComplianceView($physicalActivityView);

        $fitbitStep = new VillageOfLementMultipleAverageStepsComplianceView($startDate, $endDate, 10000, 1);
        $fitbitStep->setReportName('Daily Fitbit Steps');
        $fitbitStep->setMaximumNumberOfPoints(100);
        $fitbitStep->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $fitbitStep->setAttribute('report_name_link', '/content/1094#cfitbit');
        $required->addComplianceView($fitbitStep);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained');
        $preventiveView->setMaximumNumberOfPoints(50);
        $preventiveView->setAttribute('report_name_link', '/content/1094#dexams');
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering');
        $volunteeringView->setMaximumNumberOfPoints(40);
        $volunteeringView->setAttribute('report_name_link', '/content/1094#evol');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($volunteeringView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 10);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setMaximumNumberOfPoints(40);
        $donateBlood->setAttribute('report_name_link', '/content/1094#fdonate');
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView('2010-01-01', $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $cert->setAttribute('report_name_link', '/content/1094#gcpr');
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $doctorView->setAttribute('report_name_link', '/content/1094#hdoc');
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(50);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day');
        $healthy->setAttribute('report_name_link', '/content/1094#iwater');
        $required->addComplianceView($healthy);

//        $kickoff = new PlaceHolderComplianceView(null, 0);
//        $kickoff->setMaximumNumberOfPoints(5);
//        $kickoff->setName('kickoff_meeting');
//        $kickoff->setReportName('Attend Wellness Kickoff Meeting');
//        $kickoff->addLink(new FakeLink('Sign in at the Meeting', '#'));
//        $kickoff->setAttribute('report_name_link', '/content/1094#lkickoff');
//        $required->addComplianceView($kickoff);


        $midlandPaper = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 5);
        $midlandPaper->setMaximumNumberOfPoints(20);
        $midlandPaper->setReportName('Participation in Wellness Activities');
        $midlandPaper->setAttribute('report_name_link', '/content/1094#jwellAct');
        $required->addComplianceView($midlandPaper);

        $this->addComplianceViewGroup($required);
    }
}