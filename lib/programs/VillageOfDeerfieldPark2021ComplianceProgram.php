<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class VillageOfDeerfieldPark2021WalkingComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;
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
            if(isset($answers[1])) {
                $totalMinutes += $answers[1]->getAnswer();
            }
        }

        $pointsDivisor = $this->pointDivisor;
        $points = floor(($totalMinutes / $this->pointDivisor) * $this->multiplier);

        return new ComplianceViewStatus($this, null, $points);
    }

    private $multiplier = 1;
    private $pointDivisor = 1;
    private $activityId = 61061;
}



class VillageOfDeerfieldPark2021TobaccoComplianceView extends ComplianceView
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
        $record = $user->getNewestDataRecord('deerfield_park_tobacco_declaration_2019');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 100);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class VillageOfDeerfieldPark2021VacationTimeComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'vacation_time';
    }

    public function getDefaultReportName()
    {
        return 'Use Your Vacation Time - 40 hours max carry over and less than 24 hours in Comp. Time on the books';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('deerfield_park_vacation_time_2019');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 30);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class VillageOfDeerfieldPark2021BiometricComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'improve_biometric';
    }

    public function getDefaultReportName()
    {
        return 'Improve One Biometric';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('deerfield_park_biometric_2019');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class VillageOfDeerfieldParkMeditationComplianceView extends CompleteActivityComplianceView
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

class VillageOfDeerfieldParkMultipleAverageStepsComplianceView extends DateBasedComplianceView
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


class VillageOfDeerfieldPark2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td></td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalView->getPoints() ?></td>
            <td></td>
            <td class="points"></td>
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

            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the fourth year of the Wellness Rewards incentive program. Use this page to
            log activities that will earn dollars from actions taken for your good health and well-being across the categories below.</p>

        <p>Earn $1.00 for each point that you log. There is no minimum required. Earn up to $300.00 throughout the year
            (November 1, 2021 - October 31, 2022). Plus, earn 100 bonus dollars by participating in the 2022 Wellness
            Screening event that will take place in early October of next year. Log points by October 31, 2022 for your
            reward in 2022.</p>

        <?php
    }
}

class VillageOfDeerfieldPark2021ComplianceProgram extends ComplianceProgram
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
            $printer = new VillageOfDeerfieldPark2021ComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2021-11-01', '2022-10-31'), array(), 525);
        $totalView->setReportName('<strong>By 10/31/2022</strong>');
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

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Participate in the Annual Wellness Screening Biometrics (blood tests)');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign Up ', '/content/wms2-appointment-center'));
        $screening->addLink(new Link('Results', '/content/my-health?tab=screening'));
        $required->addComplianceView($screening);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(120);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setMaximumNumberOfPoints(150);
        $physicalActivityView->setMinutesDivisorForPoints(30);
        $physicalActivityView->emptyLinks();
        $physicalActivityView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=21"));
        $required->addComplianceView($physicalActivityView);

        $fitbitStep = new VillageOfDeerfieldParkMultipleAverageStepsComplianceView($startDate, $endDate, 8000, 1, 414, 110);
        $fitbitStep->setReportName('Daily Steps - 1 pt per 8,000 steps');
        $fitbitStep->setMaximumNumberOfPoints(100);
        $fitbitStep->emptyLinks();
        $fitbitStep->addLink(new Link('Enter Steps Manually', '/content/12048?action=showActivity&activityidentifier=414'));
        $required->addComplianceView($fitbitStep);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained - 10 pts for each screening done');
        $preventiveView->setMaximumNumberOfPoints(50);
        $preventiveView->emptyLinks();
        $preventiveView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=26"));
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - 1 pt for each hour of volunteering');
        $volunteeringView->setMaximumNumberOfPoints(40);
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->emptyLinks();
        $volunteeringView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=24"));
        $required->addComplianceView($volunteeringView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 20);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood - 20pts per donation');
        $donateBlood->setMaximumNumberOfPoints(40);
        $donateBlood->emptyLinks();
        $donateBlood->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=340"));
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView('2020-10-01', $endDate, 144501, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED Certified');
        $cert->setMaximumNumberOfPoints(15);
        $cert->emptyLinks();
        $cert->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=144501"));
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $doctorView->emptyLinks();
        $doctorView->addLink(new Link("Enter/Update Info", "/my_account/updateDoctor?redirect=/compliance_programs"));
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(100);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day - 7pts per week');
        $healthy->emptyLinks();
        $healthy->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=338"));
        $required->addComplianceView($healthy);

        $midlandPaper = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 10);
        $midlandPaper->setMaximumNumberOfPoints(30);
        $midlandPaper->setReportName('Participate in Wellness Activities - 10 pts per entry');
        $midlandPaper->emptyLinks();
        $midlandPaper->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=424"));
        $required->addComplianceView($midlandPaper);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 452, 20);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setReportName('Get an Annual Physical Exam');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=452"));
        $required->addComplianceView($annualPhysicalExamView);

        $nonSmokerView = new VillageOfDeerfieldPark2021TobaccoComplianceView($startDate, $endDate);
        $nonSmokerView->setReportName('Quit Smoking (this will require a confirmation test)');
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Enter/Update Info', '/content/deerfield_park_program?activity=tobacco'));
        $nonSmokerView->setMaximumNumberOfPoints(100);
        $required->addComplianceView($nonSmokerView);

        $flu = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1682, 20);
        $flu->setName('flu_vaccination');
        $flu->setReportName('Get an Annual Flu Vaccination');
        $flu->setMaximumNumberOfPoints(20);
        $flu->emptyLinks();
        $flu->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=1682'));
        $required->addComplianceView($flu);

        $meditation = new VillageOfDeerfieldParkMeditationComplianceView($startDate, $endDate, 1683, 1);
        $meditation->setName('meditation_relaxation_yoga');
        $meditation->setReportName('30 minutes of Guided Meditation/Relaxation/Yoga - 1pt per 30 minutes');
        $meditation->setMinutesDivisorForPoints(30);
        $meditation->setPointsMultiplier(1);
        $meditation->setMaximumNumberOfPoints(50);
        $meditation->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=1683'));
        $required->addComplianceView($meditation);

        $vacationTime = new VillageOfDeerfieldPark2021VacationTimeComplianceView($startDate, $endDate);
        $vacationTime->setName('vacation_time');
        $vacationTime->setReportName('Use Your Vacation Time - 40 hours max carry over and less than 24 hours in Comp. Time on the books');
        $vacationTime->setMaximumNumberOfPoints(20);
        $vacationTime->emptyLinks();
        $vacationTime->addLink(new Link('Enter/Update Info', '/content/deerfield_park_program?activity=vacation'));
        $required->addComplianceView($vacationTime);

        $improveBiometric = new VillageOfDeerfieldPark2021BiometricComplianceView($startDate, $endDate);
        $improveBiometric->setName('improve_biometric');
        $improveBiometric->setReportName('Improve One Biometric');
        $improveBiometric->setMaximumNumberOfPoints(25);
        $improveBiometric->emptyLinks();
        $improveBiometric->addLink(new Link('Enter/Update Info', '/content/deerfield_park_program?activity=biometric'));
        $required->addComplianceView($improveBiometric);

        $lunchWalkingView = new VillageOfDeerfieldParkMeditationComplianceView($startDate, $endDate, 61061, 1);
        $lunchWalkingView->setReportName('Lunch-Time Walking - 1 point per 30 minutes of activity');
        $lunchWalkingView->setName('lunch_walking');
        $lunchWalkingView->setMaximumNumberOfPoints(100);
        $lunchWalkingView->setMinutesDivisorForPoints(30);
        $lunchWalkingView->emptyLinks();
        $lunchWalkingView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=61061"));
        $required->addComplianceView($lunchWalkingView);


        $loseWeightView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 61062, 100);
        $loseWeightView->setMaximumNumberOfPoints(100);
        $loseWeightView->setReportName('Lose 10% or more of Body Weight (this will require confirmation)');
        $loseWeightView->setName('lose_weight');
        $loseWeightView->emptyLinks();
        $loseWeightView->addLink(new Link("Enter/Update Info", "/content/12048?action=showActivity&activityidentifier=61062"));
        $required->addComplianceView($loseWeightView);

        $this->addComplianceViewGroup($required);
    }
}
