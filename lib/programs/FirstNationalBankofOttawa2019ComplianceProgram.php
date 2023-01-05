<?php

class FirstNationalBankofOttawa2019StepsComplianceView extends DateBasedComplianceView
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
            if ($entry >= $this->threshold) $points += $this->pointsPer;
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

class FirstNationalBankofOttawa2019WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '10/01/2018 through 10/07/2018',
        2 => '10/08/2018 through 10/14/2018',
        3 => '10/15/2018 through 10/21/2018',
        4 => '10/22/2018 through 10/28/2018',
        5 => '10/29/2018 through 11/04/2018',
        6 => '11/05/2018 through 11/11/2018',
        7 => '11/12/2018 through 11/18/2018',
        8 => '11/19/2018 through 11/25/2018',
        9 => '11/26/2018 through 12/02/2018',
        10 => '12/03/2018 through 12/09/2018',
        11 => '12/10/2018 through 12/16/2018',
        12 => '12/17/2018 through 12/23/2018',
        13 => '12/24/2018 through 12/30/2018',
        14 => '12/31/2018 through 01/06/2019',
        15 => '01/07/2019 through 01/13/2019',
        16 => '01/14/2019 through 01/20/2019',
        17 => '01/21/2019 through 01/27/2019',
        18 => '01/28/2019 through 02/03/2019',
        19 => '02/04/2019 through 02/10/2019',
        20 => '02/11/2019 through 02/17/2019',
        21 => '02/18/2019 through 02/24/2019',
        22 => '02/25/2019 through 03/03/2019',
        23 => '03/04/2019 through 03/10/2019',
        24 => '03/11/2019 through 03/17/2019',
        25 => '03/18/2019 through 03/24/2019',
        26 => '03/25/2019 through 03/31/2019',
        27 => '04/01/2019 through 04/07/2019',
        28 => '04/08/2019 through 04/14/2019',
        29 => '04/15/2019 through 04/21/2019',
        30 => '04/22/2019 through 04/28/2019',
        31 => '04/29/2019 through 05/05/2019',
        32 => '05/06/2019 through 05/12/2019',
        33 => '05/13/2019 through 05/19/2019',
        34 => '05/20/2019 through 05/26/2019',
        35 => '05/27/2019 through 06/02/2019',
        36 => '06/03/2019 through 06/09/2019',
        37 => '06/10/2019 through 06/16/2019',
        38 => '06/17/2019 through 06/23/2019',
        39 => '06/24/2019 through 06/30/2019',
        40 => '07/01/2019 through 07/07/2019',
        41 => '07/08/2019 through 07/14/2019',
        42 => '07/15/2019 through 07/21/2019',
        43 => '07/22/2019 through 07/28/2019',
        44 => '07/29/2019 through 08/04/2019',
        45 => '08/05/2019 through 08/11/2019',
        46 => '08/12/2019 through 08/18/2019',
        47 => '08/19/2019 through 08/25/2019',
        48 => '08/26/2019 through 09/01/2019',
        49 => '09/02/2019 through 09/08/2019',
        50 => '09/09/2019 through 09/15/2019',
        51 => '09/16/2019 through 09/22/2019',
        52 => '09/23/2019 through 09/29/2019',
    );

    public static function parseWeek($week)
    {
        return explode(',', str_replace(' through ', ',', $week));
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $questionId, $pointPerWeek)
    {
        parent::__construct($startDate, $endDate);

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->activityId = $activityId;
        $this->questionId = $questionId;
        $this->pointPerWeek = $pointPerWeek;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->startDate, $this->endDate);

        $byWeek = array();

        foreach ($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->questionId])) {
                $byWeek[$answers[$this->questionId]->getAnswer()] = $answers[$this->questionId]->getAnswer();
            }
        }

        $points = 0;

        foreach(self::$validWeeks as $text) {
            if (isset($byWeek[$text])) {
                $points += $this->pointPerWeek;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }


    private $activityId;
    private $questionId;
    private $pointPerWeek;
}

class FirstNationalBankofOttawa2019TobaccoFormComplianceView extends ComplianceView
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
            && date('Y-m-d', strtotime($record->date)) >=  $this->start_date
            && date('Y-m-d', strtotime($record->date)) <=  $this->end_date) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 50);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class FirstNationalBankofOttawa2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Quarter 1 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('Quarter 2 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('Quarter 3 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('Quarter 4 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $quarterlyDateRange = $this->getQuerterlyRanges();
        $quarterlyDateRangeForHraScreening = $this->getQuerterlyRanges(true);

        // Build the core group

        $numbers = new ComplianceViewGroup('points', 'Gain points through actions in order to earn the quarterly reward.');

        $hraView = new PlaceHolderComplianceView(null, 0);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete the Health Power Assessment*');
        $hraView->setMaximumNumberOfPoints(50);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $hraView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRangeForHraScreening) {
            $pointAdded = false;
            $override_users = [3573850,3493524,3573993,3574065];
            foreach($quarterlyDateRangeForHraScreening as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteHRAComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                } else if(in_array($user->id, $override_users) && $quarterName == "quarter1") {
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($hraView);

        $screeningView = new PlaceHolderComplianceView(null, 0);
        $screeningView->setReportName('Participate in a Biometric Screening*');
        $screeningView->setMaximumNumberOfPoints(60);
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1114'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRangeForHraScreening) {
            $pointAdded = false;
            $override_users = [3573850,3493524,3573993,3574065];
            foreach($quarterlyDateRangeForHraScreening as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteScreeningComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(60);
                    }
                    $status->setAttribute($quarterName, 60);
                    $pointAdded = true;
                } else if(in_array($user->id, $override_users) && $quarterName == "quarter1") {
                    $status->setAttribute($quarterName, 60);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($screeningView);


        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete E-Learning Lessons – 10 points per lesson');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);


        $steps10K = new PlaceHolderComplianceView(null, 0);
        $steps10K->setReportName('Daily Steps – 5 points per 10,000 steps ');
        $steps10K->setName('daily_steps_10000');
        $steps10K->setMaximumNumberOfPoints(50);
        $steps10K->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2019StepsComplianceView($startDate, $endDate, 10000, 5, 414, 110);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $steps10K->addLink(new Link('Enter/Update Info <br />', '/content/12048?action=showActivity&activityidentifier=414'));
        $numbers->addComplianceView($steps10K);


        $preventiveExamsView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamsView->setReportName('Annual Physical/Preventive Exams Obtained – 25 points per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(50);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5172, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $preventiveExamsView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5172'));
        $numbers->addComplianceView($preventiveExamsView);


        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Regular Physical Activity – 5 points for each hour of activity');
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setMaximumNumberOfPoints(50);
//        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(12);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $numbers->addComplianceView($physicalActivityView);


        $vegetable = new PlaceHolderComplianceView(null, 0);
        $vegetable->setMaximumNumberOfPoints(50);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 10 servings of fruit/vegetables in a week – 5 points');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2019WeeklyLogComplianceView($startDate, $endDate, 4481, 215, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $vegetable->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4481'));
        $numbers->addComplianceView($vegetable);


        $sleep = new PlaceHolderComplianceView(null, 0);
        $sleep->setMaximumNumberOfPoints(50);
        $sleep->setName('sleep');
        $sleep->setReportName('Get 7-9 hours of uninterrupted sleep per night for a week – 5 points');
        $sleep->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2019WeeklyLogComplianceView($startDate, $endDate, 4482, 215, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $sleep->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4482'));
        $numbers->addComplianceView($sleep);


        $dentalExam = new PlaceHolderComplianceView(null, 0);
        $dentalExam->setMaximumNumberOfPoints(50);
        $dentalExam->setReportName('Annual Dental Exam*');
        $dentalExam->setName('annual_dental_exam');
        $dentalExam->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5171, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();

                if($points > 0) {
                    if($points > 50) {
                        $points = 50;
                    }

                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints($points);
                    }
                    $status->setAttribute($quarterName, $points);
                    $pointAdded = true;
                }
            }
        });
        $dentalExam->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5171'));
        $numbers->addComplianceView($dentalExam);



        $numbers->setPointsRequiredForCompliance(250);

        $this->addComplianceViewGroup($numbers);
    }

    protected function getQuerterlyRanges($isHraScreening = false)
    {
        if($isHraScreening) {
            $ranges = array(
                'quarter1' => array('2018-09-01', '2018-12-31'),
                'quarter2' => array('2019-01-01', '2019-03-31'),
                'quarter3' => array('2019-04-01', '2019-06-30'),
                'quarter4' => array('2019-07-01', '2019-09-30'),
            );
        } else {
            $ranges = array(
                'quarter1' => array('2018-10-01', '2018-12-31'),
                'quarter2' => array('2019-01-01', '2019-03-31'),
                'quarter3' => array('2019-04-01', '2019-06-30'),
                'quarter4' => array('2019-07-01', '2019-09-30'),
            );
        }

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($pointStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('quarter1')) {
                $quarter1Points += $viewStatus->getAttribute('quarter1');
            }

            if($viewStatus->getAttribute('quarter2')) {
                $quarter2Points += $viewStatus->getAttribute('quarter2');
            }

            if($viewStatus->getAttribute('quarter3')) {
                $quarter3Points += $viewStatus->getAttribute('quarter3');
            }

            if($viewStatus->getAttribute('quarter4')) {
                $quarter4Points += $viewStatus->getAttribute('quarter4');
            }
        }

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
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
            $printer = new FirstNationalBankofOttawa2019ComplianceProgramReportPrinter();
            $printer->setShowTotal(false);
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }
}


class FirstNationalBankofOttawa2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>


        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>
            Welcome to your Wellness Rewards Program at The First National Bank of Ottawa. In 2019, employees who completed
            the health screening and assessment in October 2018 will receive the same incentive on their health premium
            they received last year. As a further incentive, those employees who also reach the 150 points per deadline
            below will also receive a 2.5% premium discount. The activities below have all been assigned point values.
            There are one-time point earning opportunities* as well as daily point earning options.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

        <script type="text/javascript">
            $(function() {
                $('#legend tr td').children(':eq(2)').remove();
                $('#legend tr td').children(':eq(2)').remove();

                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.headerRow-points').children(':eq(2)').html('Quarterly Points Possible');


                $('.view-annual_dental_exam').after('<tr class="headerRow headerRow-footer"><td class="center">2. Deadlines, Requirements & Status </td><td>Total # Earned</td><td>Incentive Status</td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr class="quarter_one"><td style="text-align: right;">Deadline 1: October 1 - December 31, 2018</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_1_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_1_points') >= 110 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">110</td></tr>')
                $('.quarter_one').after('<tr class="quarter_two"><td style="text-align: right;">Deadline 2: January 1 – March 31, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 150 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">150</td></tr>')
                $('.quarter_two').after('<tr class="quarter_three"><td style="text-align: right;">Deadline 3: April 1 – June 30, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_3_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_3_points') >= 150 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">150</td></tr>')
                $('.quarter_three').after('<tr class="quarter_four"><td style="text-align: right;">Deadline 4: July 1 – September 30, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_4_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_4_points') >= 150 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">150</td></tr>')


            });
        </script>

        <?php

    }
}