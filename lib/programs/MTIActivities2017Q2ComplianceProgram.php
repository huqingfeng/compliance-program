<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MTIActivities2017Q2WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '04/03/2017 through 04/09/2017',
        2 => '04/10/2017 through 04/16/2017',
        3 => '04/17/2017 through 04/23/2017',
        4 => '04/24/2017 through 04/30/2017',
        5 => '05/01/2017 through 05/07/2017',
        6 => '05/08/2017 through 05/14/2017',
        7 => '05/15/2017 through 05/21/2017',
        8 => '05/22/2017 through 05/28/2017',
        9 => '05/29/2017 through 06/04/2017',
        10 => '06/05/2017 through 06/11/2017',
        11 => '06/12/2017 through 06/18/2017',
        12 => '06/19/2017 through 06/25/2017',
        13 => '06/26/2017 through 07/02/2017'
    );

    public static function parseWeek($week)
    {
        return explode(',', str_replace(' through ', ',', $week));
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $threshold, $timeLength, $maxPoints, $additional = null)
    {
        $this->id = 416;

        parent::__construct('2017-04-03', '2017-07-02');

        $this->totalDays = $this->getDaysInPeriod();

        $this->additional = $additional;
        $this->questionId = $questionId;
        $this->threshold = $threshold;
        $this->timeLength = $timeLength;
        $this->maxPoints = $maxPoints;

    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, '2017-01-01', '2017-12-31');

        $byWeek = array();

        foreach ($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[144], $answers[$this->questionId])) {
                $byWeek[$answers[144]->getAnswer()] = (double) $answers[$this->questionId]->getAnswer();
            }
        }

        $total = array();

        foreach (self::$validWeeks as $text) {
            list($startDate, $endDate) = self::parseWeek($text);

            $startDateStamp = strtotime($startDate);
            $endDateStamp = strtotime($endDate);

            $result = 0;

            if (isset($byWeek[$text])) {
                $result += $byWeek[$text];
            }

            if ($this->additional) {
                $additionalResult = call_user_func($this->additional, $user);

                foreach ($additionalResult as $ts => $value) {
                    if ($ts >= $startDateStamp && $ts <= $endDateStamp) {
                        $result += $value;
                    }
                }
            }

            $total[$text] = $result;
        }

        $points = 0;

        foreach($total as $result) {
            if ($result >= $this->threshold) {
                $points += round($this->maxPoints / ($this->totalDays / $this->timeLength), 2);
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    private function getDaysInPeriod()
    {
        $days = 0;

        $startDate = $this->getStartDateTime();
        $endDate = $this->getEndDateTime();

        while($startDate <= $endDate) {
            $days++;

            $startDate->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    private $additional;
    private $id;
    private $questionId;
    private $totalDays;
    private $threshold;
    private $timeLength;
    private $maxPoints;
}

class MTIActivities2017Q2ComplianceProgram extends ComplianceProgram
{
    const MTI_2017Q2_ACTIVITY_START_DATE = '2017-04-03';

    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true)
    {
        if($pointsPerRecord === null){
            $pointsPerRecord = $points;
        }

        $view = new CompleteArbitraryActivityComplianceView(
            MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE,
            $this->getEndDate(),
            $activityId,
            $pointsPerRecord
        );

        $view->setMaximumNumberOfPoints($points);

        $view->setName($name);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        if(!$link) {
            $view->emptyLinks();
        }

        return $view;
    }

    protected function getPlaceHolderView($name, $points, $reportName = null)
    {
        $view = new PlaceHolderComplianceView(null, 0);
        $view->setName($name);
        $view->setMaximumNumberOfPoints($points);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        return $view;
    }

    protected function getSummableActivityView($name, $questionId, $threshold, $timeLength, $maxPoints, $reportName = null)
    {
        $program = $this;

        $additional = function(User $user) use($program, $questionId) {
            if ($questionId == 136) {
                $data = $program->getFitbitWater($user);

                foreach ($data as $key => $value) {
                    // water returned by getFitbitWater() is in OZ
                    $data[$key] = $value / 8;
                }

                return $data;
            } else if ($questionId == 132) {
                return $program->getFitbitActivity($user);
            } else if ($questionId == 135) {
                $data = $program->getFitbitSleep($user);

                foreach ($data as $key => $value) {
                    // Sleep returned by getFitbitSleep() is in minutes
                    $data[$key] = $value / 60;
                }

                return $data;
            } else {
                return array();
            }
        };

        $view = new MTIActivities2017Q2WeeklyLogComplianceView($questionId, $threshold, $timeLength, $maxPoints, $additional);

        $view->setName($name);

        if ($reportName !== null) {
            $view->setReportName($reportName);
        }

        $view->setMaximumNumberOfPoints($maxPoints);

        $view->emptyLinks();

        return $view;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MTIActivities2017WMS2ComplianceProgramPrinter();
    }

    public function getLocalActions()
    {
        return array(
            'fitbit_feed' => array($this, 'executeFitbitFeed'),
            'authorize_fitbit' => array($this, 'executeAuthorizeFitbit')
        );
    }

    public function executeAuthorizeFitbit(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        if(handle_fitbit_registration($actions, $user)) {
            $actions->redirect('/compliance_programs?id=784');
        } else {
            register_fitbit_user($actions, $user);
        }

        throw new \RuntimeException('Fitbit Error');
    }

    public function executeFitbitFeed(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $addForWeek = function($data) {
            $total = array();

            foreach($data as $k => $v) {
                $w = date('W', $k);

                if (isset(MTIActivities2017Q2WeeklyLogComplianceView::$validWeeks[$w])) {
                    $text = MTIActivities2017Q2WeeklyLogComplianceView::$validWeeks[$w];

                    if (!isset($total[$text])) {
                        $total[$text] = 0;
                    }

                    $total[$text] += $v;
                }
            }

            return $total;
        };

        $rows = array();

        $process = function($data, $key) use (&$rows) {
            foreach($data as $weekName => $value) {
                if (!isset($rows[$weekName])) {
                    $rows[$weekName] = array();
                }

                if (!isset($rows[$weekName][$key])) {
                    $rows[$weekName][$key] = 0;
                }

                $rows[$weekName][$key] += $value;
            }
        };

        $process($addForWeek($this->getFitbitWater($user)), 'water');
        $process($addForWeek($this->getFitbitSleep($user)), 'sleep');
        $process($addForWeek($this->getFitbitActivity($user)), 'activity');

        $finalRows = array();

        foreach($rows as $week => $data) {
            $row = $data;
            $row['week'] = $week;

            if ($row['water'] || $row['sleep'] || $row['activity']) {
                $finalRows[] = $row;
            }


        }

        $actions->setLayout(false);
        $actions->getResponse()->setContentType('application/json');

        echo json_encode($finalRows);
    }

    public function getFitbitActivity($user)
    {
        $wms2Data = $this->getWms3FitbitData($user);


        $start = strtotime(self::MTI_2017Q2_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        $fitbitData = isset($wms2Data['active_minutes']) && count($wms2Data['active_minutes']) > 0 ?
            $wms2Data['active_minutes'] : get_fitbit_activities_data($user);


        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function getFitbitSleep($user)
    {
        $wms2Data = $this->getWms3FitbitData($user);

        $fitbitData = isset($wms2Data['hours_asleep']) && count($wms2Data['hours_asleep']) > 0 ?
            $wms2Data['hours_asleep'] : get_fitbit_sleep_data($user);

        $start = strtotime(self::MTI_2017Q2_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function getFitbitWater($user)
    {
        $wms2Data = $this->getWms3FitbitData($user);

        $fitbitData = isset($wms2Data['water']) && count($wms2Data['water']) > 0 ?
            $wms2Data['water'] : array();

        if (count($fitbitData) < 1) {
            $fitbitData = get_fitbit_water_data($user);

            foreach($fitbitData as $ts => $waterInMl) {
                $waterOz = round($waterInMl / 29.5735, 0);

                $fitbitData[$ts] = $waterOz;
            }
        }

        $start = strtotime(self::MTI_2017Q2_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function hasFitbitData(User $user)
    {
        if (!empty($this->getWms3FitbitData($user)) && !is_null($this->getWms3FitbitData($user))) {
            foreach ($this->getWms3FitbitData($user) as $entries) {
                if (count($entries) > 0) {
                    return true;
                }
            }
            return refresh_fitbit_data($user);
        }
        return null;
    }


    protected function getWms3FitbitData($user)
    {
        /*
        $db = $this->getWms3Database();

        $query = $db->prepare("
            select
                t.label,
                t.readable,
                g.value as goal,
                max(d.value) as value,
                date_format(d.activity_date, '%Y-%m-%d') as date
            from fitnessTracking_participants p
            inner join fitnessTracking_data d on (d.participant = p.id)
            inner join fitnessTracking_types t on (t.id = d.type)
            inner join fitnessTracking_participants_goals g on (g.participant = p.id and g.type=d.type)
            where p.wms1Id = $user->id
            and d.type != 12
            and d.status = 1
            group by d.activity_date, d.type
            order by d.activity_date desc
            "
        );
        $query->execute();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $activitiesQuery = $query->fetchAll();


        $activities = array(
            'active_minutes' => array(),
            'hours_asleep'   => array(),
            'water'           => array()
        );

        foreach($activitiesQuery as $key => $value)
        {

            $date = date('Y-m-d', strtotime($value['date']));

            if($value['label'] == 'water') {
                $activities['water'][$date] = round((.000264172 * $value['value']), 2);
            } else if($value['label'] == 'active_minutes') {
                $activities['active_minutes'][$date] = $value['value'];
            } else if($value['label'] == 'hours_asleep') {
                $activities['hours_asleep'][$date] = $value['value'] * 60;
            }
        }

        return $activities;
        */
        return null;
    }


    protected function getWms3Database()
    {
        /*
        $db['servername'] = "mysql.hpn.com";
        $db['username'] = "jimbrashear";
        $db['password'] = "osvWp0ZgTzPSL2K4";
        $db['dbname'] = "wms3";


        $conn = new PDO("mysql:host=".$db['servername']."; dbname=".$db['dbname'], $db['username'], $db['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
        */
        return null;
    }

    public function setActiveUser(User $user = null)
    {
        if ($this->getMode() == ComplianceProgram::MODE_INDIVIDUAL) {
            refresh_fitbit_data($user);
        }

        return parent::setActiveUser($user);
    }

    public function filterFitbitData($data, $startDate, $endDate)
    {
        $ret = array();

        foreach($data as $k => $v) {
            $stamp = strtotime($k);

            if ($stamp >= $startDate && $stamp <= $endDate) {
                $ret[$stamp] = $v;
            }
        }

        return $ret;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $prevention = new ComplianceViewGroup('Prevention');
        $prevention->setPointsRequiredForCompliance(0);
        $prevention->setAttribute('available_points', 50);
        $prevention->setMaximumNumberOfPoints(50);

        $confirmationView = new PlaceHolderComplianceView(null, 0);
        $confirmationView->setName('screening_confirmation');
        $confirmationView->setReportName('Meet with a Beacon Health Coach');
        $confirmationView->setMaximumNumberOfPoints(15);
        $confirmationView->setPostEvaluateCallback(function($status, $user) use($startDate, $endDate) {
            if(!$user->insurancetype) {
                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 420, 10);

                if($alternative->getStatus($user)->getPoints() > 0) {
                    $status->setPoints(15);
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
                $status->getComplianceView()->addLink(new Link('Update', '/content/12048?action=showActivity&activityidentifier=420'));
            }
        });
        $prevention->addComplianceView($confirmationView);

        $examsView = new PlaceHolderComplianceView(null, 0);
        $examsView->setMaximumNumberOfPoints(20);
        $examsView->setName('exams');
        $examsView->setReportName(
            'Complete age-appropriate tests/exams <br/>
             <div style="padding-left:30px;">
                Complete a minimum of 2 of the following: <br/>
                <div style="padding-left:15px;">
                    Pelvic exam/Pap<br/>
                    Prostate exam<br/>
                    PSA test<br/>
                    Mammogram<br/>
                    Colonoscopy<br/>
                    Physical Exam<br/>
                    Dental Exam<br/>
                    Eye Exam
                </div>
            </div>
            '
        );
        $examsView->addLink(new Link('I did this', '/content/chp-document-uploader'));
        $prevention->addComplianceView($examsView);

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, array(30, 31));
        $fluTetView->setReportName('Flu/Pertussis/Tetanus Vaccine');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 399, 5));
        $preventionElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'mti_prevention');
        $preventionElearningView->setReportName('Complete an e-learning module');
        $preventionElearningView->setNumberRequired(1);
        $preventionElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $prevention->addComplianceView($preventionElearningView);

        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 132, 180, 7, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 133, 180, 7, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 409, 5));
        $fitnessElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'mti_fitness');
        $fitnessElearningView->setReportName('Complete an e-learning module');
        $fitnessElearningView->setNumberRequired(1);
        $fitnessElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $fitness->addComplianceView($fitnessElearningView);

        $fitness->setMaximumNumberOfPoints(60);
        $fitness->setAttribute('available_points', 70);

        $nutrition = new ComplianceViewGroup('Nutrition');
        $nutrition->setPointsRequiredForCompliance(0);
        $nutrition->addComplianceView($this->getActivityView('nutritionist', 400, 10));
        $nutrition->addComplianceView($this->getActivityView('grocery', 401, 15));
        $nutrition->addComplianceView($this->getSummableActivityView('water', 136, 56, 7, 30, 'Drink Enough Water'));
        $nutrition->addComplianceView($this->getSummableActivityView('fruit', 137, 28, 7, 30, 'Eat Enough Fruit & Vegetables'));

        $nutritionElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'light_activities');
        $nutritionElearningView->setReportName('Complete an e-learning module');
        $nutritionElearningView->setNumberRequired(1);
        $nutritionElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $nutrition->addComplianceView($nutritionElearningView);
        $nutrition->addComplianceView($this->getActivityView('nutrition_lnl', 408, 5));
        $nutrition->setMaximumNumberOfPoints(60);
        $nutrition->setAttribute('available_points', 95);

        $stress = new ComplianceViewGroup('Stress Management');
        $stress->setPointsRequiredForCompliance(0);
        $stress->addComplianceView($this->getActivityView('one_vacation', 403, 10));
        $stress->addComplianceView($this->getSummableActivityView('sleep', 135, 49, 7, 30, 'Sleep'));
        $stress->addComplianceView($this->getActivityView('eap', 406, 5));

        $stress->addComplianceView($this->getSummableActivityView('just_for_you', 134, 105, 7, 30, 'Just for you time'));

        $stressElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'mti_stress');
        $stressElearningView->setReportName('Complete an e-learning module');
        $stressElearningView->setNumberRequired(1);
        $stressElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $stress->addComplianceView($stressElearningView);
        $stress->addComplianceView($this->getActivityView('stress_lnl', 407, 5));
        $stress->setMaximumNumberOfPoints(75);
        $stress->setAttribute('available_points', 85);

        $financial = new ComplianceViewGroup('Financial');
        $financial->setPointsRequiredForCompliance(0);
        $financial->setAttribute('available_points', 80);

        $fairView = $this->getActivityView('retirement_fair', 359, 5);
        $fairView->setReportName('Attend Retirement Fair or Retirement Education Workshop');
        $fairViewLinks = $fairView->getLinks();
        $fairViewLink = reset($fairViewLinks);
        $fairViewLink->setLinkText('Update');

        $financial->addComplianceView($fairView);
        $financial->addComplianceView($this->getActivityView('plan_contribute', 376, 15));
        $financial->addComplianceView($this->getActivityView('plan_beneficiary', 377, 5));
        $financial->addComplianceView($this->getActivityView('budget', 363, 15));
        $financial->addComplianceView($this->getActivityView('pay_loan', 364, 5));
        $financial->addComplianceView($this->getActivityView('emergency_fund', 365, 15));
        $financial->addComplianceView($this->getActivityView('financial_lnl', 536, 5));
        $financial->addComplianceView($this->getActivityView('dave', 417, 10));

        $financialElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'legal_financial');
        $financialElearningView->setReportName('Complete an e-learning module');
        $financialElearningView->setNumberRequired(1);
        $financialElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $financial->addComplianceView($financialElearningView);
        $financial->setMaximumNumberOfPoints(50);

        $community = new ComplianceViewGroup('Community');
        $community->setPointsRequiredForCompliance(0);
        $community->addComplianceView($this->getActivityView('donate_blood', 346, 30, null, 10, false));
        $community->addComplianceView($this->getActivityView('mentor', 347, 10));
        $community->addComplianceView($this->getActivityView('donate_non_profit', 348, 10));
        $community->addComplianceView($this->getActivityView('church', 349, 20));
        $community->addComplianceView($this->getActivityView('volunteer_on_board', 389, 15));
        $community->addComplianceView($this->getActivityView('community_lnl', 539, 5));

        $communityElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'all_lessons');
        $communityElearningView->setReportName('Complete an e-learning module');
        $communityElearningView->setNumberRequired(1);
        $communityElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $community->addComplianceView($communityElearningView);

        $community->setMaximumNumberOfPoints(50);
        $community->setAttribute('available_points', 95);

        $brain = new ComplianceViewGroup('Mind');
        $brain->setPointsRequiredForCompliance(0);
        $brain->addComplianceView($this->getActivityView('crossword', 351, 10));
        $brain->addComplianceView($this->getActivityView('puzzle', 352, 10));
        $brain->addComplianceView($this->getActivityView('language', 353, 15));
        $brain->addComplianceView($this->getActivityView('instrument', 354, 15));
        $brain->addComplianceView($this->getActivityView('education_class', 356, 5));
        $brain->addComplianceView($this->getActivityView('meditation', 357, 10));
        $brain->addComplianceView($this->getActivityView('brain_lnl', 542, 5));

        $brainElearningView = new CompleteELearningGroupSet(MTIActivities2017Q2ComplianceProgram::MTI_2017Q2_ACTIVITY_START_DATE, $endDate, 'brain_nervous_system');
        $brainElearningView->setReportName('Complete an e-learning module');
        $brainElearningView->setNumberRequired(1);
        $brainElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $brain->addComplianceView($brainElearningView);
        $brain->setMaximumNumberOfPoints(50);
        $brain->setAttribute('available_points', 75);

        $other = new ComplianceViewGroup('MTI additional opportunities');
        $other->setAttribute('available_points', 85);
        $other->addComplianceView($this->getActivityView('sunburst', 512, 5, null, null, false));
        $other->addComplianceView($this->getActivityView('bike_for_hospice', 515, 5, null, null, false));
        $other->addComplianceView($this->getActivityView('biggest_loser', 518, 20, null, null, false));
        $other->addComplianceView($this->getActivityView('bike_to_work', 521, 10, null, null, false));
        $other->addComplianceView($this->getActivityView('mti_lunch', 524, 5, null, null, false));
        $other->addComplianceView($this->getActivityView('smoking_cessation', 527, 25, null, null, false));
        $other->addComplianceView($this->getActivityView('quarterly_coaching', 530, 15, null, null, false));
        $other->addComplianceView($this->getActivityView('mti_additional_opportunities', 546, 20, null, null, false));
        $other->setMaximumNumberOfPoints(50);
        $other->setPointsRequiredForCompliance(0);

        $this->addComplianceViewGroup($prevention);
        $this->addComplianceViewGroup($fitness);
        $this->addComplianceViewGroup($nutrition);
        $this->addComplianceViewGroup($stress);
        $this->addComplianceViewGroup($financial);
        $this->addComplianceViewGroup($community);
        $this->addComplianceViewGroup($brain);
        $this->addComplianceViewGroup($other);

        foreach(array('water', 'fruit', 'sleep', 'cardio', 'strength', 'just_for_you') as $dailyViewName) {
            $this->getComplianceView($dailyViewName)->addLink(new Link('Weekly Log', '/content/12048?action=showActivity&activityidentifier=416'));
        }

        foreach($this->getComplianceViews() as $view) {
            foreach($view->getLinks() as $link) {
                if($link->getLinkText() == 'Enter/Update Info') {
                    $link->setLinkText('Update');
                }
            }
        }
    }
}




class MTIActivities2017WMS2ComplianceProgramPrinter implements ComplianceProgramReportPrinter
{

    public function printReport(ComplianceProgramStatus $status)
    {

        $user = $status->getUser();

        $biometricRecord = ComplianceProgramRecordTable::getInstance()->find(919);

        if($biometricRecord) {
            $biometricProgram = $biometricRecord->getComplianceProgram();
            $biometricProgram->setActiveUser($user);
            $biometricStatus = $biometricProgram->getStatus();

            $biometricPoints = $biometricStatus->getPoints();
        }

        $quarterOneRecord = ComplianceProgramRecordTable::getInstance()->find(922);
        $quarterOneProgram = $quarterOneRecord->getComplianceProgram();
        $quarterOneProgram->setActiveUser($status->getUser());
        $quarterOneStatus = $quarterOneProgram->getStatus();

        $quarterTwoRecord = ComplianceProgramRecordTable::getInstance()->find(925);
        $quarterTwoProgram = $quarterTwoRecord->getComplianceProgram();
        $quarterTwoProgram->setActiveUser($status->getUser());
        $quarterTwoStatus = $quarterTwoProgram->getStatus();

        $quarterThreeRecord = ComplianceProgramRecordTable::getInstance()->find(928);
        $quarterThreeProgram = $quarterThreeRecord->getComplianceProgram();
        $quarterThreeProgram->setActiveUser($status->getUser());
        $quarterThreeStatus = $quarterThreeProgram->getStatus();

        $quarterFourRecord = ComplianceProgramRecordTable::getInstance()->find(931);
        $quarterFourProgram = $quarterFourRecord->getComplianceProgram();
        $quarterFourProgram->setActiveUser($status->getUser());
        $quarterFourStatus = $quarterFourProgram->getStatus();


        $classForPoints = function($points) {
            if ($points >= 125) {
                return 'success';
            } else if ($points >= 50.1) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($points, $text) use ($classForPoints) {
            $class = $points === 'beacon' ? 'beacon' : $classForPoints($points);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
                    <div style="font-size: 1.3em; line-height: 1.0em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();
            ?>
            <table class="details-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th class="points">Maximum</th>
                    <th class="points">Actual</th>
                    <th class="text-center">Progress</th>
                    <th class="text-center">Links</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1 ?>
                <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                    <?php $class = $classFor($pct) ?>
                    <tr>
                        <td class="name">
                            <?php echo $i ?>.
                            <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="points"><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                        <td class="points <?php echo $class ?>">
                            <?php echo $viewStatus->getPoints() ?>
                        </td>
                        <td class="text-center">
                            <div class="pgrs pgrs-tiny">
                                <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                <div><?php echo $link->getHTML() ?></div>
                            <?php endforeach ?>
                        </td>
                    </tr>
                    <?php $i++ ?>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();

            $points = $group->getPoints();
            $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints();

            $pct = $points / $target;

            $class = $classFor($pct);
            ?>
            <tr class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <strong><?php echo $target ?></strong><br/>
                    points
                </td>
                <td class="points <?php echo $class ?>">
                    <strong><?php echo $points ?></strong><br/>
                    points
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details closed">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        }

        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                min-width: 500px;
                border-collapse: separate;
                border-spacing: 5px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 200px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1.4em;
            }

            <?php if($status->getUser()->insurancetype) : ?>
            #physician-reviewed-link {
                display: none;
            }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });
            });
        </script>
        <div class="row">
            <div class="col-md-12">
                <h1>MY <small>ACTIVITIES</small></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <div class="row">
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-11 col-md-offset-1 activity">
                                <?php echo $circle(
                                    ($quarterOneStatus->getPoints() + $biometricPoints),
                                    '<span class="circle-points">'.($quarterOneStatus->getPoints() + $biometricPoints). '</span><br/><br/>1st Quarter<br/>Points'
                                ) ?>
                                <br/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-11 col-md-offset-1 activity">
                                <?php echo $circle(
                                    $quarterTwoStatus->getPoints(),
                                    '<span class="circle-points">'.$quarterTwoStatus->getPoints(). '</span><br/><br/>2nd Quarter<br/>Points'
                                ) ?>
                                <br/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-11 col-md-offset-1 activity">
                                <?php echo $circle(
                                    $quarterThreeStatus->getPoints(),
                                    '<span class="circle-points">'.$quarterThreeStatus->getPoints(). '</span><br/><br/>3rd Quarter<br/>Points'
                                ) ?>
                                <br/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-11 col-md-offset-1 activity">
                                <?php echo $circle(
                                    $quarterFourStatus->getPoints(),
                                    '<span class="circle-points">'.$quarterFourStatus->getPoints(). '</span><br/><br/>4th Quarter<br/>Points'
                                ) ?>
                                <br/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if ($status->getComplianceProgram()->hasFitbitData($status->getUser())) : ?>
                    <div class="alert alert-info">
                        Fitbit/Fitness Tracking syncing is enabled.

                        <a class="btn btn-primary btn-xs pull-right" href="/wms2/compliance/mti-program-2016/fitness-tracking">
                            Manage Fitness Tracking
                        </a>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning">
                        Fitbit and other devices can be used to import your cardio minutes, sleep and water intake.

                        <a class="btn btn-primary btn-xs pull-right" href="/wms2/compliance/mti-program-2016/fitness-tracking">
                            Manage Fitness Tracking
                        </a>
                    </div>
                <?php endif ?>

                <p>Below are activities in which you can accumulate points for healthy behaviors.
                    You need to earn 125 points per quarter, though there are many more point
                    opportunities to choose from!</p>

                <p><a href="#" id="more-info-toggle">More...</a></span></p>

                <div id="more-info" style="display: none">
                    <p>
                        In the Prevention section your HRA responses will automatically update your points for #3
                        (Flu/Pertussis/Tetanus). #2 (Preventative Exams) will be updated based on claims received
                        (if on the medical plan) or you can submit a form through the "I did this" link with proof
                        of your exams. Exams must take place 01/01/16 – 12/31/16 to receive points, points will be
                        updated from claims received 60-90 days post exam date.
                    </p>

                    <p>
                        Other activities can be updated for the current date or past date between 01/01/16 – 12/31/16
                        via the "Update" links to the right of each activity. Once these updates are made points will
                        automatically populate in your activity pages. "Weekly Log" links provide the opportunity to
                        log as often as you wish.
                    </p>

                    <p><a href="/resources/7391/MTI activities program detail - new Q2 3-1-16.pdf">Wellness Activity Details</a></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention', $status->getComplianceViewGroupStatus('Prevention')) ?>
                    <?php echo $tableRow('Exercise', $status->getComplianceViewGroupStatus('Exercise')) ?>
                    <?php echo $tableRow('Nutrition', $status->getComplianceViewGroupStatus('Nutrition')) ?>
                    <?php echo $tableRow('Stress Management', $status->getComplianceViewGroupStatus('Stress Management')) ?>
                    <?php echo $tableRow('Financial', $status->getComplianceViewGroupStatus('Financial')) ?>
                    <?php echo $tableRow('Community', $status->getComplianceViewGroupStatus('Community')) ?>
                    <?php echo $tableRow('Mind', $status->getComplianceViewGroupStatus('Mind')) ?>
                    <?php echo $tableRow('MTI additional opportunities', $status->getComplianceViewGroupStatus('MTI additional opportunities')) ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            $(function() {
                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'More...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('More...');
                    }
                });
            });
        </script>
        <?php
    }
}