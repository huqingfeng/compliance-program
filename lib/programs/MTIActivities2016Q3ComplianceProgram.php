<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MTIActivities2016Q3WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validQ2Weeks = array(
        1 => '07/04/2016 through 07/10/2016',
        2 => '07/11/2016 through 07/17/2016',
        3 => '07/18/2016 through 07/24/2016',
        4 => '07/25/2016 through 07/31/2016',
        5 => '08/01/2016 through 08/07/2016',
        6 => '08/08/2016 through 08/14/2016',
        7 => '08/15/2016 through 08/21/2016',
        8 => '08/22/2016 through 08/28/2016',
        9 => '08/29/2016 through 09/04/2016',
        10 => '09/05/2016 through 09/11/2016',
        11 => '09/12/2016 through 09/18/2016',
        12 => '09/19/2016 through 09/25/2016',
        13 => '09/26/2016 through 10/02/2016'
    );

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $pointsPer, $divisor, $additional = null)
    {
        $this->id = 416;

        parent::__construct('2016-07-01', '2016-09-30');

        $this->additional = $additional;
        $this->questionId = $questionId;
        $this->pointsPer = $pointsPer;
        $this->divisor = $divisor;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, '2016-01-01', '2016-12-31');

        $total = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[144]) && !in_array($answers[144]->getAnswer(), self::$validQ2Weeks)) continue;

            if (isset($answers[$this->questionId])) {
                $total += $answers[$this->questionId]->getAnswer();
            }
        }

        if ($this->additional) {
            $total += call_user_func($this->additional, $user);
        }

        $points = round( (float)((int)($total / $this->divisor)) * $this->pointsPer, 2);

        return new ComplianceViewStatus($this, null, $points);
    }

    private $additional;
    private $id;
    private $questionId;
    private $pointsPer;
    private $divisor;
}



class MTIActivities2016Q3ComplianceProgram extends ComplianceProgram
{
    const MTI_2015_ACTIVITY_START_DATE = '2016-07-01';

    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true)
    {
        if($pointsPerRecord === null){
            $pointsPerRecord = $points;
        }

        $view = new CompleteArbitraryActivityComplianceView(
            MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE,
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

    protected function getSummableActivityView($name, $activityId, $questionId, $pointsPer, $divisor, $maxPoints, $reportName = null)
    {
        $program = $this;

        $additional = function(User $user) use($program, $questionId) {
            if ($questionId == 136) {
                return array_sum($program->getFitbitWater($user)) / 8; // water returned by getFitbitWater() is in OZ
            } else if ($questionId == 132) {
                return array_sum($program->getFitbitActivity($user));
            } else if ($questionId == 135) {
                return array_sum($program->getFitbitSleep($user)) / 60; // Sleep returned by getFitbitSleep() is in minutes
            } else {
                return 0;
            }
        };

        $view = new MTIActivities2016Q3WeeklyLogComplianceView($questionId, $pointsPer, $divisor, $additional);

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
        if (sfConfig::get('app_wms2')) {
            return new MTIActivities2016WMS2ComplianceProgramPrinter();
        } else {
            $printer = new MTIActivities2016Q3ComplianceProgramPrinter();
            $printer->hide_status_when_point_based = true;
            $printer->requirements = false;
            $printer->page_heading = 'My Wellness Activities (<a href="/compliance_programs?id=569">View Report Card</a>)';
            $printer->show_group_totals = true;

            return $printer;
        }
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

                if (isset(MTIActivities2016Q3WeeklyLogComplianceView::$validQ2Weeks[$w])) {
                    $text = MTIActivities2016Q3WeeklyLogComplianceView::$validQ2Weeks[$w];

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

        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
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

        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
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

        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
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

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, array(30, 31));
        $fluTetView->setReportName('Flu/Pertussis/Tetanus Vaccine');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 399, 5));
        $preventionElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_prevention');
        $preventionElearningView->setReportName('Complete an e-learning module');
        $preventionElearningView->setNumberRequired(1);
        $preventionElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $prevention->addComplianceView($preventionElearningView);

        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 416, 132, 1/3, 20, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 416, 133, 1/3, 12, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 409, 5));
        $fitnessElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_fitness');
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
        $nutrition->addComplianceView($this->getSummableActivityView('water', 416, 136, 1/3, 8, 30, 'Drink Enough Water'));
        $nutrition->addComplianceView($this->getSummableActivityView('fruit', 416, 137, 1/3, 4, 30, 'Eat Enough Fruit & Vegetables'));

        $nutritionElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'light_activities');
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
        $stress->addComplianceView($this->getSummableActivityView('sleep', 416, 135, 1/3, 6.5, 30, 'Sleep'));
        $stress->addComplianceView($this->getActivityView('eap', 406, 5));

        $stress->addComplianceView($this->getSummableActivityView('just_for_you', 416, 134, 1/3, 15, 30, 'Just for you time'));

        $stressElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_stress');
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

        $financialElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'legal_financial');
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

        $communityElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'all_lessons');
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

        $brainElearningView = new CompleteELearningGroupSet(MTIActivities2016Q3ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'brain_nervous_system');
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

class MTIActivities2016Q3ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status)
    {
        $totalPoints = 0;
        $totalMaxPoints = 0;
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $totalPoints += $groupStatus->getPoints();
            $totalMaxPoints += $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints();
        }


        ?>
        <style type="text/css">
            .phipTable {
                font-size:0.9em;
            }

            #legend {
                display:none;
            }

            .totalRow td{
                background-color: #42669A;
                color: white;
                text-align: center;
            }

        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=566', function(fullPage) {
                    var $page = $(fullPage);

                    var firstQuarterPoints = parseInt($page.find('#1st_quarter_points').html(), 10);

                    $('#1st_quarter_points').html(firstQuarterPoints);
                });


                $.get('/compliance_programs?id=683', function(fullPage) {
                    var $page = $(fullPage);

                    var secondQuarterPoints = parseInt($page.find('#2nd_quarter_points').html(), 10);

                    $('#2nd_quarter_points').html(secondQuarterPoints);
                })

                $('.show_more').toggle(function(){
                    $('.hide').show();
                    $('.show_more a').html('Less...');
                }, function(){
                    $('.hide').hide();
                    $('.show_more a').html('More...');
                });
            });
        </script>
        <?php
        parent::printReport($status);
    }

    public function printClientMessage()
    {
        ?>
        <p>Below are activities in which you can accumulate points for healthy behaviors. You need to earn 125 points per quarter, though there are many more point opportunities to choose from!.</p>
        <p><span class="show_more"><a href="#">More...</a></span></p>
        <p class="hide">In the Prevention section your HRA responses will automatically update your points
            for #3 (Flu/Pertussis/Tetanus). #2 (Preventative Exams) will be updated based on claims received (if on the medical
            plan) or you can submit a form through the "I did this" link with proof of
            your exams. Exams must take place 01/01/16 – 12/31/16 to receive points, points will be
            updated from claims received 60-90 days post exam date.</p>
        <p class="hide">Other activities can be updated for the current date or past date between 01/01/16 – 12/31/16 via
            the “Update” links to the right of each activity. Once these updates are made points will automatically
            populate in your activity pages. "Weekly Log" links provide the opportunity to log as often as you
            wish.</p>
        <p><a href="/resources/7391/MTI activities program detail - new Q2 3-1-16.pdf">Wellness Activity Details</a></p>
        <?php
    }

    public function printClientNote()
    {

    }

    protected function printMaximumNumberOfGroupPoints(ComplianceViewGroup $group)
    {
        $maxPoints = $group->getMaximumNumberOfPoints();
        $availablePoints = $group->getAttribute('available_points');
        ?>
        <td class="maxpoints">
            <?php echo $this->getFormattedPoints($maxPoints); ?> Maximum Points Possible <br/>
            (<?php echo $this->getFormattedPoints($availablePoints); ?> Available Points)
        </td>
        <?php
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow">
            <th colspan="2">My 1st Quarter Wellness Points (370 activity & 80 biometric)-</th>
            <td id="1st_quarter_points"></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 2nd Quarter Wellness Points (635 possible)-</th>
            <td id="2nd_quarter_points"></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 3rd Quarter Wellness Points (635 possible)-</th>
            <td id="3rd_quarter_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 4th Quarter Wellness Points (635 possible)-</th>
            <td id="4th_quarter_points"></td>
            <td></td>
            <td></td>
        </tr>
        <?php
    }
}

