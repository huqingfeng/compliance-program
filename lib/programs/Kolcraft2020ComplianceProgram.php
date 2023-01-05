<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class Kolcraft2020RunWalkComplianceView extends CompleteArbitraryActivityComplianceView
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

            if($answers[220]->getAnswer() != $this->activityType) continue;

            $points += isset($answers[220]) && isset(self::$activityPerPoints[$answers[220]->getAnswer()]) ?
                self::$activityPerPoints[$answers[220]->getAnswer()] : 0;
        }

        $status =  new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private static $activityPerPoints = array(
        '5k/10k Walk or Run' => 10,
        'Triathlon/Biathlon' => 20,
        'Half Marathon' => 25,
        'Marathon' => 30
    );

    private $activityId;
}



class KolcraftMultipleAverageStepsComplianceView extends DateBasedComplianceView
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
        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $manualSteps = 0;
        foreach($records as $record) {
            $recordDate = date('Y-m-d', strtotime($record->getDate()));
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId])) {
                if(!isset($data['dates'])) $data['dates'] = array();

                $data['dates'][$recordDate] = (int)$answers[$this->questionId]->getAnswer();
                $manualSteps += (int)$answers[$this->questionId]->getAnswer();
            }
        }

        $points = 0;

        foreach($data['dates'] as $date) {
            if($date >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);

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


class Kolcraft2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');

        $totalFirstHalfView = $groupStatus->getComplianceViewStatus('total_first_half');
        $totalView = $groupStatus->getComplianceViewStatus('total');
        extract($groupStatus->getComplianceViewStatuses());
        $today = strtotime(date('Y'));
        ?>
        <tr class="headerRow">
            <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
                    ->getReportName()) ?></th>
            <td>Total # Earned</td>
            <td></td>
            <td>Minimum Points Needed</td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalFirstHalfView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalFirstHalfView->getPoints() ?></td>
            <td></td>
            <td class="points">≥ 300 points</td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalView->getPoints() ?></td>
            <td></td>
            <td class="points">≥ 475 points</td>
        </tr>

        <script>
            $(function(){

            });
        </script>

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

                $('.view-complete_hra').next().remove();
                $('.view-complete_hra').next().children(':eq(0)').css('padding-left', '2px');
                $('.view-complete_hra').next().children(':eq(0)').html('<strong>B</strong>. Annual Wellness Screening </td>');
                $('.view-complete_hra').next().children('.links').html('<a target="_self" href="/compliance/hmi-2016/schedule/content/wms2-appointment-center">Sign-Up</a> <a target="_self" href="/compliance/hmi-2016/my-results">Results</a>');

                $('.view-flu_vaccination').after('<tr><td><strong>M</strong>. Participate in a Fitness Event</td><td></td><td></td><td></td></tr>')
                $('.view-5k').children(':eq(0)').html('<span style="margin-left: 20px;">&#8226; 5k/10k Walk or Run</span>');
                $('.view-triathlon').children(':eq(0)').html('<span style="margin-left: 20px;">&#8226; Triathlon/Biathlon</span>');
                $('.view-half_marathon').children(':eq(0)').html('<span style="margin-left: 20px;">&#8226; Half Marathon</span>');
                $('.view-marathon').children(':eq(0)').html('<span style="margin-left: 20px;">&#8226; Marathon</span>');

                $('.view-healthy > td strong').html("N");
                $('.view-fruit_vegetable > td strong').html("O");
            });
        </script>

        <p>Hello Kolcraft Employee,</p>

        <p>We are excited to introduce a Wellness Rewards Program here at Kolcraft, beginning July 1, 2020. We hope this program will encourage you to be more aware of your own wellness and increase your daily physical activity to improve your health. The idea is to accumulate points by participating in wellness activities and making healthy choices over the next six months which will earn you a discount on your health insurance premium.</p>

        <p>In 2020, employees reaching the necessary number of points will receive a discount on their 2021 health insurance premium. In order to maintain this discount you will need to continue participation in the wellness program. There will be other incentives for participants throughout the year and for those employees who participate but are not on our health plans.</p>

        <p>In order to earn a premium discount, you must earn 300 points or more by December 17, 2020. You can earn these points from actions taken for your good health and wellbeing across the action categories below.
        </p>

        <?php
    }
}

class Kolcraft2020ComplianceProgram extends ComplianceProgram
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
            $printer = new Kolcraft2020ComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalFirstHalfView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2020-07-01', '2020-12-17'), array(), 300);
        $totalFirstHalfView->setReportName('<strong>By 12/17/2020</strong>');
        $totalFirstHalfView->setName('total_first_half');
        $totalsGroup->addComplianceView($totalFirstHalfView);

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2020-07-01', '2021-06-30'), array(), 475);
        $totalView->setReportName('<strong>By 06/30/2021</strong>');
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
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Annual Wellness Screening');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $screening->setAttribute('_screening_printer_hack', 4);
        $required->addComplianceView($screening);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setReportName('Blood Pressure - both #\'s less than 130/85');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0 ,0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setMergeScreenings(true);
        $required->addComplianceView($bloodPressureView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($startDate, $endDate);
        $hdlRatioView->setReportName('Total/HDL Cholesterol Ratio - 4.5 and below');
        $hdlRatioView->setName('hdl_ratio');
        $hdlRatioView->overrideTestRowData(null, null, 4.5, null, 'M');
        $hdlRatioView->overrideTestRowData(null, null, 4.5, null, 'F');
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $required->addComplianceView($hdlRatioView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setReportName('Glucose - 115 and below');
        $glucoseView->setName('glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0 ,0));
        $glucoseView->overrideTestRowData(null, null, 115, null);
        $required->addComplianceView($glucoseView);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(50);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each half hour of activity');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(30);
        $required->addComplianceView($physicalActivityView);

        $fitbitStep = new KolcraftMultipleAverageStepsComplianceView($startDate, $endDate, 10000, 1, 414, 110);
        $fitbitStep->setReportName('Daily Steps - 1 pt per 10,000 steps ');
        $fitbitStep->setMaximumNumberOfPoints(150);
        $fitbitStep->addLink(new Link('Fitbit Sync <br />', '/content/ucan-fitbit-individual'));
        $fitbitStep->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=414'));
        $required->addComplianceView($fitbitStep);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained - 10 pts for each screening done this year');
        $preventiveView->setMaximumNumberOfPoints(50);
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - 1 pt for each hour of volunteering');
        $volunteeringView->setMaximumNumberOfPoints(25);
        $volunteeringView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($volunteeringView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 15);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood - 15 pts per donation');
        $donateBlood->setMaximumNumberOfPoints(30);
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $required->addComplianceView($cert);


        $midlandPaper = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 5);
        $midlandPaper->setMaximumNumberOfPoints(30);
        $midlandPaper->setReportName('Participation in Wellness Activities - 5 pts per entry');
        $required->addComplianceView($midlandPaper);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 452, 20);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setReportName('Get an Annual Physical Exam');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $required->addComplianceView($annualPhysicalExamView);

        $flu = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1682, 20);
        $flu->setName('flu_vaccination');
        $flu->setReportName('Annual Flu Vaccination');
        $flu->setMaximumNumberOfPoints(20);
        $required->addComplianceView($flu);

        $walkRun = new Kolcraft2020RunWalkComplianceView($startDate, $endDate, 37071, '5k/10k Walk or Run');
        $walkRun->setMaximumNumberOfPoints(10);
        $walkRun->setName('5k');
        $walkRun->setReportName('5k/10k Walk or Run');
        $walkRun->setAttribute('points_per_activity', 10);
        $required->addComplianceView($walkRun);

        $triathlon = new Kolcraft2020RunWalkComplianceView($startDate, $endDate, 37071, 'Triathlon/Biathlon');
        $triathlon->setMaximumNumberOfPoints(20);
        $triathlon->setName('triathlon');
        $triathlon->setReportName('Triathlon/Biathlon');
        $triathlon->setAttribute('points_per_activity', 20);
        $required->addComplianceView($triathlon);

        $halfMarathon = new Kolcraft2020RunWalkComplianceView($startDate, $endDate, 37071, 'Half Marathon');
        $halfMarathon->setMaximumNumberOfPoints(25);
        $halfMarathon->setName('half_marathon');
        $halfMarathon->setReportName('Half Marathon');
        $halfMarathon->setAttribute('points_per_activity', 25);
        $required->addComplianceView($halfMarathon);

        $marathon = new Kolcraft2020RunWalkComplianceView($startDate, $endDate, 37071, 'Marathon');
        $marathon->setMaximumNumberOfPoints(30);
        $marathon->setName('marathon');
        $marathon->setReportName('Marathon');
        $marathon->setAttribute('points_per_activity', 30);
        $required->addComplianceView($marathon);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(100);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 8 glasses of pure water per day - 1 pt per day');
        $required->addComplianceView($healthy);

        $fruitVege = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1681, 2);
        $fruitVege->setMaximumNumberOfPoints(50);
        $fruitVege->setReportName('Eat 3-5 servings of fruits/vegetables a day - 2 pts per day');
        $fruitVege->setName('fruit_vegetable');
        $required->addComplianceView($fruitVege);

        $this->addComplianceViewGroup($required);
    }
}