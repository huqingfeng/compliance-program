<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MTIActivities2017Q1WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '01/02/2017 through 01/08/2017',
        2 => '01/09/2017 through 01/15/2017',
        3 => '01/16/2017 through 01/22/2017',
        4 => '01/23/2017 through 01/29/2017',
        5 => '01/30/2017 through 02/05/2017',
        6 => '02/06/2017 through 02/12/2017',
        7 => '02/13/2017 through 02/19/2017',
        8 => '02/20/2017 through 02/26/2017',
        9 => '02/27/2017 through 03/05/2017',
        10 => '03/06/2017 through 03/12/2017',
        11 => '03/13/2017 through 03/19/2017',
        12 => '03/20/2017 through 03/26/2017',
        13 => '03/27/2017 through 04/02/2017'
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

        parent::__construct('2017-01-01', '2017-04-02');

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

class MTIActivities2017Q1ComplianceProgram extends ComplianceProgram
{
    const MTI_2017Q1_ACTIVITY_START_DATE = '2017-01-01';

    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true)
    {
        if($pointsPerRecord === null){
            $pointsPerRecord = $points;
        }

        $view = new CompleteArbitraryActivityComplianceView(
            MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE,
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

        $view = new MTIActivities2017Q1WeeklyLogComplianceView($questionId, $threshold, $timeLength, $maxPoints, $additional);

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

                if (isset(MTIActivities2017Q1WeeklyLogComplianceView::$validWeeks[$w])) {
                    $text = MTIActivities2017Q1WeeklyLogComplianceView::$validWeeks[$w];

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
        $wms2Data = $this->getWms2FitbitData($user);

        $start = strtotime(self::MTI_2017Q1_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        $fitbitData = isset($wms2Data['minutesActive']) && count($wms2Data['minutesActive']) > 0 ?
            $wms2Data['minutesActive'] : get_fitbit_activities_data($user);

        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function getFitbitSleep($user)
    {
        $wms2Data = $this->getWms2FitbitData($user);

        $fitbitData = isset($wms2Data['hoursAsleep']) && count($wms2Data['hoursAsleep']) > 0 ?
            $wms2Data['hoursAsleep'] : get_fitbit_sleep_data($user);

        $start = strtotime(self::MTI_2017Q1_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function getFitbitWater($user)
    {
        $wms2Data = $this->getWms2FitbitData($user);

        $fitbitData = isset($wms2Data['ouncesWaterConsumed']) && count($wms2Data['ouncesWaterConsumed']) > 0 ?
            $wms2Data['ouncesWaterConsumed'] : array();

        if (count($fitbitData) < 1) {
            $fitbitData = get_fitbit_water_data($user);

            foreach($fitbitData as $ts => $waterInMl) {
                $waterOz = round($waterInMl / 29.5735, 0);

                $fitbitData[$ts] = $waterOz;
            }
        }

        $start = strtotime(self::MTI_2017Q1_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData($fitbitData, $start, $end);
    }

    public function hasFitbitData(User $user)
    {
        foreach ($this->getWms2FitbitData($user) as $entries) {
            if (count($entries) > 0) {
                return true;
            }
        }

        return refresh_fitbit_data($user);
    }

    private $wms2FitbitData = array('user_id' => null, 'data' => null);

    private function getWms2FitbitData(User $user)
    {
        if ($this->wms2FitbitData['user_id'] != $user->id) {
            $this->wms2FitbitData = array('user_id' => $user->id, 'data' => Wms2Model::fitnessData($user->id));
        }

        return $this->wms2FitbitData['data'];
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

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, array(30, 31));
        $fluTetView->setReportName('Flu/Pertussis/Tetanus Vaccine');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 399, 5));
        $preventionElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'mti_prevention');
        $preventionElearningView->setReportName('Complete an e-learning module');
        $preventionElearningView->setNumberRequired(1);
        $preventionElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $prevention->addComplianceView($preventionElearningView);

        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 132, 180, 7, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 133, 180, 7, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 409, 5));
        $fitnessElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'mti_fitness');
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

        $nutritionElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'light_activities');
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

        $stressElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'mti_stress');
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

        $financialElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'legal_financial');
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

        $communityElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'all_lessons');
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

        $brainElearningView = new CompleteELearningGroupSet(MTIActivities2017Q1ComplianceProgram::MTI_2017Q1_ACTIVITY_START_DATE, $endDate, 'brain_nervous_system');
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

