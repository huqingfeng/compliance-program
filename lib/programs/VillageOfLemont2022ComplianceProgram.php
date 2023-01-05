<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class VillageOfLemont2022WMS3StepsComplianceView extends DateBasedComplianceView
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
        return "regular_physical_activity_wms3";
    }

    public function getDefaultReportName()
    {
        return "Regular Physical Activity ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $_db = Database::getDatabase();
        $startDate = date('Y-m-d', $this->startDate);
        $endDate = date('Y-m-d', $this->endDate);

        $fitnessTrackingQuery =
            "SELECT ftd.activity_date, ftd.value FROM wms3.fitnessTracking_data ftd
              LEFT JOIN wms3.fitnessTracking_participants ftp ON ftp.id = ftd.participant
              WHERE ftp.wms1Id = ".$user->id." AND ftd.type = 1 AND ftd.activity_date >= '".$startDate."'
              AND ftd.activity_date <= '".$endDate."' and ftd.status = 1;";

        $data = $_db->getResultsForQuery($fitnessTrackingQuery);

        $stepsData = array();
        foreach($data as $record) {
            $activityDate = date('Y-m-d', strtotime($record['activity_date']));

            $stepsData[$activityDate] += $record['value'] ?? 0;
        }

        $points = 0;

        foreach($stepsData as $date => $steps) {
            if($steps >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private $threshold;
    private $pointsPer;
    private $userId;
}



class VillageOfLemont2022TobaccoFormComplianceView extends ComplianceView
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
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 40);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class VillageOfLemontMeditationComplianceView extends CompleteActivityComplianceView
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

class VillageOfLemontMultipleAverageStepsComplianceView extends DateBasedComplianceView
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


class VillageOfLemont2022ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td class="points">≥ 325 points</td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalView->getPoints() ?></td>
            <td></td>
            <td class="points">≥ 525 points</td>
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
                $('.view-complete_hra').next().remove();
                $('.view-complete_hra').next().children(':eq(0)').css('padding-left', '2px');
                $('.view-complete_hra').next().children(':eq(0)').html('<strong>B</strong>. Annual Wellness Screening </td>');
                $('.view-complete_hra').next().children('.links').html('<a target="_self" href="/content/wms2-appointment-center">Sign-Up</a> <a target="_self" href="/content/my-health?tab=screening">Results</a>');
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>
            Welcome to your summary page for the 2022 Wellness Rewards benefit at Village of Lemont. This is the 9th year
            of the Village of Lemont's wellness rewards program.
        </p>

        <p>
            In 2022, employees reaching the necessary number of points will receive a discount on their 2022 health
            insurance premium. There will also be incentives for those employees who participate but are not on our
            health plans.
        </p>

        <p>
            In order to earn the first premium discount, you must earn at least 325 points by June 10th, 2022. In order
            to continue to receive a premium discount, you will need to have earned at least a total of 525 points by
            December 15, 2022. You can earn these points from actions taken for your good health and wellbeing across
            the action categories below.
        </p>

        <?php
    }
}

class VillageOfLemont2022ComplianceProgram extends ComplianceProgram
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
            $printer = new VillageOfLemont2022ComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalFirstHalfView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2022-01-01', '2022-06-10'), array(), 325);
        $totalFirstHalfView->setReportName('<strong>By 06/10/2022</strong>');
        $totalFirstHalfView->setName('total_first_half');
        $totalsGroup->addComplianceView($totalFirstHalfView);

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2022-01-01', '2022-12-15'), array(), 525);
        $totalView->setReportName('<strong>By 12/15/2022</strong>');
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
        $hra->addLink(new Link('Take HPA', '/content/my-health'));
        $hra->addLink(new Link('Results', '/content/my-health'));
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
        $elearningView->setMaximumNumberOfPoints(120);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each hour of activity');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($physicalActivityView);

        $steps = new VillageOfLemont2022WMS3StepsComplianceView($startDate, $endDate, 8000, 1);
        $steps->setReportName('Daily Fitbit Steps - 1 pt per 8,000 steps ');
        $steps->setMaximumNumberOfPoints(200);
        $steps->addLink(new Link('Fitness Tracker', '/content/fitness'));
        $required->addComplianceView($steps);

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
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setMaximumNumberOfPoints(40);
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(100);
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

        $nonSmokerView = new VillageOfLemont2022TobaccoFormComplianceView($startDate, $endDate);
        $nonSmokerView->setReportName('Non-Smoker');
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(40);
        $required->addComplianceView($nonSmokerView);

        $fruitVege = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1681, 1);
        $fruitVege->setMaximumNumberOfPoints(100);
        $fruitVege->setReportName('Eat 3-5 servings of fruits/vegetables a day - 1 pt per day');
        $fruitVege->setName('fruit_vegetable');
        $required->addComplianceView($fruitVege);

        $flu = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144518, 20);
        $flu->setName('flu_vaccination');
        $flu->setReportName('Annual Flu Shot & Other Immunizations - 20 points each');
        $flu->setMaximumNumberOfPoints(100);
        $required->addComplianceView($flu);

        $meditation = new VillageOfLemontMeditationComplianceView($startDate, $endDate, 1683, 1);
        $meditation->setName('meditation_relaxation_yoga');
        $meditation->setReportName('30 minutes of Guided Meditation/Relaxation/Yoga - 1pt per 30 minutes');
        $meditation->setMinutesDivisorForPoints(30);
        $meditation->setPointsMultiplier(1);
        $meditation->setMaximumNumberOfPoints(50);
        $meditation->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=1683'));
        $required->addComplianceView($meditation);

        $sleep = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1684, 1);
        $sleep->setMaximumNumberOfPoints(50);
        $sleep->setName('sleep');
        $sleep->setReportName('Get 7-9 hours of uninterrupted sleep per night - 1pt per night');
        $required->addComplianceView($sleep);

        $this->addComplianceViewGroup($required);
    }
}
