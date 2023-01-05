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


class VillageOfLemont2016SecondHalfYearComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td class="points">≥ 250 points</td>
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
                $('.view-complete_hra').next().children(':eq(0)').html('<strong>C</strong>. Biometric Results')
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2016 Wellness Rewards benefit at Village of Lemont. This year
            we are continuing our wellness rewards incentive program and have added a few extra incentives.</p>

        <p>In 2016, employees reaching the necessary number of points will receive a discount on their 2017 health
            insurance premium (Jan-June). There will also be prizes for those employees who participate but are not
            on our health plans. Also, monthly raffles will be held for all those actively participating.</p>

        <p>The deadline to receive the 250 points (and obtain the premium discount) is December 16th, 2016 (see below).
            You can earn these points from actions taken for your good health and wellbeing across the action
            categories below.</p>

        <p><strong>Update Notice:</strong> To get actions done and earn points click on the links below. If
            the points or status did not change for an item you are working on, you may need to go back and
            enter missing information or entries to earn more points. We wish you much success in your healthy endeavors.</p>
        <?php
    }
}

class VillageOfLemont2016SecondHalfYearComplianceProgram extends ComplianceProgram
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
            $printer = new VillageOfLemont2016SecondHalfYearComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), $this->getEndDate()), array(), 250);
        $totalView->setReportName('<strong>By 12/16/2016</strong>');
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

        $quizHeartDiseaseView = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(99));
        $quizHeartDiseaseView->setReportName('Complete How to Prevent Heart Diseases e-Learning Lesson');
        $quizHeartDiseaseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $quizHeartDiseaseView->setName('quiz_heart_disease');
        $required->addComplianceView($quizHeartDiseaseView);

        $quizDiabeteView = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(105));
        $quizDiabeteView->setReportName('Complete Type 2 Diabetes e-Learning Lesson');
        $quizDiabeteView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $quizDiabeteView->setName('quiz_diabete');
        $required->addComplianceView($quizDiabeteView);

        $quizPrehypertensionView = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(1309));
        $quizPrehypertensionView->setReportName('Complete Prehypertension e-Learning Lesson');
        $quizPrehypertensionView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $quizPrehypertensionView->setName('quiz_prehypertension');
        $required->addComplianceView($quizPrehypertensionView);

        $quizWeightManagementView = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(1118));
        $quizWeightManagementView->setReportName('Complete Weight Management e-Learning Lesson');
        $quizWeightManagementView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $quizWeightManagementView->setName('quiz_weight_management');
        $required->addComplianceView($quizWeightManagementView);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, array(99, 105, 1309, 1118));
        $elearningView->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(40);
        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setMaximumNumberOfPoints(180);
        $physicalActivityView->setMinutesDivisorForPoints(30);
        $required->addComplianceView($physicalActivityView);

        $fitbitStep = new VillageOfLementMultipleAverageStepsComplianceView($startDate, $endDate, 8000, 1);
        $fitbitStep->setReportName('Fitbit Steps - 1 pt per 8,000 steps');
        $fitbitStep->setMaximumNumberOfPoints(100);
        $fitbitStep->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $required->addComplianceView($fitbitStep);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained - 10 pts for each screening done this year');
        $preventiveView->setMaximumNumberOfPoints(50);
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - 1 pt for each hour of volunteering');
        $volunteeringView->setMaximumNumberOfPoints(40);
        $volunteeringView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($volunteeringView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 10);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood - 10 pts per activity');
        $donateBlood->setMaximumNumberOfPoints(40);
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView('2016-01-01', $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(50);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day - 1 pt per day');
        $required->addComplianceView($healthy);

        $midlandPaper = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 5);
        $midlandPaper->setMaximumNumberOfPoints(30);
        $midlandPaper->setReportName('Participation in Wellness Activities - 5 pts per entry');
        $required->addComplianceView($midlandPaper);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 452, 20);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setReportName('Get an Annual Physical Exam');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $required->addComplianceView($annualPhysicalExamView);

        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 342, 10);
        $blueCrossBlueShield->setMaximumNumberOfPoints(10);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield\'s Blue Access for Members');
        $blueCrossBlueShield->addLink(new Link('BCBS', 'http://www.bcbsil.com/member'));
        $required->addComplianceView($blueCrossBlueShield);


        $this->addComplianceViewGroup($required);
    }
}