<?php

class Idex2019StepsComplianceView extends DateBasedComplianceView
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

class Idex2019WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '04/29/2019 through 05/05/2019',
        2 => '05/06/2019 through 05/12/2019',
        3 => '05/13/2019 through 05/19/2019',
        4 => '05/20/2019 through 05/26/2019',
        5 => '05/27/2019 through 06/02/2019',
        6 => '06/03/2019 through 06/09/2019',
        7 => '06/10/2019 through 06/16/2019',
        8 => '06/17/2019 through 06/23/2019',
        9 => '06/24/2019 through 06/30/2019',
        10 => '07/01/2019 through 07/07/2019',
        11 => '07/08/2019 through 07/14/2019',
        12 => '07/15/2019 through 07/21/2019',
        13 => '07/22/2019 through 07/28/2019',
        14 => '07/29/2019 through 08/04/2019',
        15 => '08/05/2019 through 08/11/2019',
        16 => '08/12/2019 through 08/18/2019',
        17 => '08/19/2019 through 08/25/2019',
        18 => '08/26/2019 through 09/01/2019',
        19 => '09/02/2019 through 09/08/2019',
        20 => '09/09/2019 through 09/15/2019',
        21 => '09/16/2019 through 09/22/2019',
        22 => '09/23/2019 through 09/29/2019',
        23 => '09/30/2019 through 10/06/2019',
        24 => '10/07/2019 through 10/13/2019',
        25 => '10/14/2019 through 10/20/2019',
        26 => '10/21/2019 through 10/27/2019',
        27 => '10/28/2019 through 11/03/2019',
        28 => '11/04/2019 through 11/10/2019',
        29 => '11/11/2019 through 11/17/2019',
        30 => '11/18/2019 through 11/24/2019',
        31 => '11/25/2019 through 12/01/2019',
        32 => '12/02/2019 through 12/08/2019',
        33 => '12/09/2019 through 12/15/2019',
        34 => '12/16/2019 through 12/22/2019',
        35 => '12/23/2019 through 12/29/2019',
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

class Idex2019TobaccoFormComplianceView extends ComplianceView
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
class Idex2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

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

        $screeningView = new PlaceHolderComplianceView(null, 0);
        $screeningView->setReportName('Participate in a Biometric Screening* (typically in the Fall or for new hires)');
        $screeningView->setMaximumNumberOfPoints(75);
        $screeningView->emptyLinks();
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRangeForHraScreening) {
            $pointAdded = false;
            foreach($quarterlyDateRangeForHraScreening as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 29915, 75);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(75);
                    }
                    $status->setAttribute($quarterName, 75);
                    $pointAdded = true;
                }
            }
        });
        $screeningView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=29915'));
        $numbers->addComplianceView($screeningView);


        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete e-Learning Lessons – 10 points for each lesson completed');
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

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=required'));
        $numbers->addComplianceView($elearn);

        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Regular Physical Activity – 5 points for each hour of activity');
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setMaximumNumberOfPoints(100);
//        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(12);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $numbers->addComplianceView($physicalActivityView);


        $steps10K = new PlaceHolderComplianceView(null, 0);
        $steps10K->setReportName('Daily Steps – 5 points per 10,000 daily steps');
        $steps10K->setName('daily_steps_10000');
        $steps10K->setMaximumNumberOfPoints(100);
        $steps10K->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new Idex2019StepsComplianceView($startDate, $endDate, 10000, 5, 414, 110);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $steps10K->addLink(new Link('Enter/Update Info <br />', '/content/12048?action=showActivity&activityidentifier=414'));
        $numbers->addComplianceView($steps10K);


        $preventiveExamsView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamsView->setReportName('Preventive Exams Obtained – 20 points for each exam completed');
        $preventiveExamsView->setMaximumNumberOfPoints(100);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5172, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $preventiveExamsView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5172'));
        $numbers->addComplianceView($preventiveExamsView);

        $flu = new PlaceHolderComplianceView(null, 0);
        $flu->setMaximumNumberOfPoints(10);
        $flu->setName('flu');
        $flu->setReportName('Receive Annual Flu Vaccination – 10 points*');
        $flu->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 20, 10);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();

                if($points > 0) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints($points);
                    }
                    $status->setAttribute($quarterName, $points);
                    $pointAdded = true;
                }
            }
        });
        $flu->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=20'));
        $numbers->addComplianceView($flu);

        $physician = new PlaceHolderComplianceView(null, 0);
        $physician->setMaximumNumberOfPoints(10);
        $physician->setName('physician');
        $physician->setReportName('Designate a Primary Care Physician – 10 points*');
        $physician->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new UpdateDoctorInformationComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(10);
                    }
                    $status->setAttribute($quarterName, 10);
                    $pointAdded = true;
                }
            }
        });
        $physician->addLink(new Link('Enter/Update Info', '/my_account/updateDoctor?redirect=/compliance_programs'));
        $numbers->addComplianceView($physician);

        $water = new PlaceHolderComplianceView(null, 0);
        $water->setMaximumNumberOfPoints(50);
        $water->setName('water');
        $water->setReportName('Drink 48 oz of water a day for a week – 5 points');
        $water->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new Idex2019WeeklyLogComplianceView($startDate, $endDate, 29952, 217, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $water->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=29952'));
        $numbers->addComplianceView($water);

        $vegetable = new PlaceHolderComplianceView(null, 0);
        $vegetable->setMaximumNumberOfPoints(50);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 5 servings of fruits/vegetables a day for a week – 5 points per week');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new Idex2019WeeklyLogComplianceView($startDate, $endDate, 29968, 217, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $vegetable->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=29968'));
        $numbers->addComplianceView($vegetable);


        $wellnessActivity = new PlaceHolderComplianceView(null, 0);
        $wellnessActivity->setMaximumNumberOfPoints(50);
        $wellnessActivity->setReportName('Participate in a company sponsored wellness activity – 25 points (will include mental health and financial wellness webinars, sponsored runs, etc.)');
        $wellnessActivity->setName('wellness_activity');
        $wellnessActivity->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 29969, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();

                if($points > 0) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints($points);
                    }
                    $status->setAttribute($quarterName, $points);
                    $pointAdded = true;
                }
            }
        });
        $wellnessActivity->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=29969'));
        $numbers->addComplianceView($wellnessActivity);



        $numbers->setPointsRequiredForCompliance(250);

        $this->addComplianceViewGroup($numbers);
    }

    protected function getQuerterlyRanges($isHraScreening = false)
    {
        if($isHraScreening) {
            $ranges = array(
                'quarter2' => array('2019-05-01', '2019-06-30'),
                'quarter3' => array('2019-07-01', '2019-09-30'),
                'quarter4' => array('2019-10-01', '2019-12-31'),
            );
        } else {
            $ranges = array(
                'quarter2' => array('2019-05-01', '2019-06-30'),
                'quarter3' => array('2019-07-01', '2019-09-30'),
                'quarter4' => array('2019-10-01', '2019-12-31'),
            );
        }

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($pointStatus->getComplianceViewStatuses() as $viewStatus) {

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
            $printer = new Idex2019ComplianceProgramReportPrinter();
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


class Idex2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>


        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>
            Welcome to your Wellness Rewards Program. We care about your health and overall well-being! Let's all take
            action and commit to a healthier lifestyle. Participants qualifying for all three quarters of the Wellness
            Rewards Program will be entered to win a tablet or smartwatch of their choice. Two winners will be named at
            the end of 2019.
        </p>

        <p>
            The activities below have all been assigned point values.  There are one-time point earning opportunities* as well as daily point earning options.
        </p>

        <p>
            In order to earn the full incentive each quarter, you must earn at least 100 points each quarter.  Since our
            program will begin May 1 and include only two months to participate for Q2, only 50 points are needed to
            qualify for May-June. Below are the available activities you can accomplish to earn the incentive.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

        <script type="text/javascript">
            $(function() {
                $('#legend').remove();

                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.headerRow-points').children(':eq(2)').html('Quarterly Points Possible');

                $('.view-wellness_activity').after('<tr class="headerRow headerRow-footer"><td class="center">2. Deadlines, Requirements & Status </td><td>Total # Earned</td><td>Incentive Status</td><td>Minimum Points Needed</td></tr>')

                $('.headerRow-footer').after('<tr class="quarter_two"><td style="text-align: right;">Quarter 2 Deadline: June 30, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 50 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">50</td></tr>')
                $('.quarter_two').after('<tr class="quarter_three"><td style="text-align: right;">Quarter 3 Deadline: September 30, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_3_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_3_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')
                $('.quarter_three').after('<tr class="quarter_four"><td style="text-align: right;">Quarter 4 Deadline: December 18, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_4_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_4_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')

            });
        </script>

        <?php

    }
}