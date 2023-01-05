<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MTIActivities2016Q2WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validQ2Weeks = array(
        1 => '04/04/2016 through 04/10/2016',
        2 => '04/11/2016 through 04/17/2016',
        3 => '04/18/2016 through 04/24/2016',
        4 => '04/25/2016 through 05/01/2016',
        5 => '05/02/2016 through 05/08/2016',
        6 => '05/09/2016 through 05/15/2016',
        7 => '05/16/2016 through 05/22/2016',
        8 => '05/23/2016 through 05/29/2016',
        9 => '05/30/2016 through 06/05/2016',
        10 => '06/06/2016 through 06/12/2016',
        11 => '06/13/2016 through 06/19/2016',
        12 => '06/20/2016 through 06/26/2016',
        13 => '06/27/2016 through 07/03/2016'
    );

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $pointsPer, $divisor, $additional = null)
    {
        $this->id = 416;

        parent::__construct('2016-04-01', '2016-06-31');

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



class MTIActivities2016Q2ComplianceProgram extends ComplianceProgram
{
    const MTI_2015_ACTIVITY_START_DATE = '2016-04-01';

    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true)
    {
        if($pointsPerRecord === null){
            $pointsPerRecord = $points;
        }

        $view = new CompleteArbitraryActivityComplianceView(
            MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE,
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

        $view = new MTIActivities2016Q2WeeklyLogComplianceView($questionId, $pointsPer, $divisor, $additional);

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
            $printer = new MTIActivities2016Q2ComplianceProgramPrinter();
            $printer->hide_status_when_point_based = true;
            $printer->requirements = false;
            $printer->page_heading = 'My Wellness Activities (<a href="/compliance_programs?id=569">View Report Card</a>)';
            $printer->show_group_totals = true;

            return $printer;
        }
    }

    public function getLocalActions()
    {
        return array('fitbit_feed' => array($this, 'executeFitbitFeed'));
    }
    
    public function executeFitbitFeed(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $addForWeek = function($data) {
            $total = array();

            foreach($data as $k => $v) {
                $w = date('W', $k);

                if (isset(MTIActivities2016Q2WeeklyLogComplianceView::$validQ2Weeks[$w])) {
                    $text = MTIActivities2016Q2WeeklyLogComplianceView::$validQ2Weeks[$w];

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
        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData(get_fitbit_activities_data($user), $start, $end);
    }

    public function getFitbitSleep($user)
    {
        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData(get_fitbit_sleep_data($user), $start, $end);
    }

    public function getFitbitWater($user)
    {
        $start = strtotime(self::MTI_2015_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        $water = $this->filterFitbitData(get_fitbit_water_data($user), $start, $end);

        $ret = array();

        foreach($water as $ts => $waterInMl) {
            $waterOz = round($waterInMl / 29.5735, 0);

            $ret[$ts] = $waterOz;
        }

        return $ret;
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

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, array(30, 31));
        $fluTetView->setReportName('Flu/Pertussis/Tetanus Vaccine');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 399, 5));
        $preventionElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_prevention');
        $preventionElearningView->setReportName('Complete an e-learning module');
        $preventionElearningView->setNumberRequired(1);
        $preventionElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $prevention->addComplianceView($preventionElearningView);

        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 416, 132, 1/3, 20, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 416, 133, 1/3, 12, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 409, 5));
        $fitnessElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_fitness');
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

        $nutritionElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'light_activities');
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

        $stressElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'mti_stress');
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

        $financialElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'legal_financial');
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

        $communityElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'all_lessons');
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

        $brainElearningView = new CompleteELearningGroupSet(MTIActivities2016Q2ComplianceProgram::MTI_2015_ACTIVITY_START_DATE, $endDate, 'brain_nervous_system');
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

class MTIActivities2016Q2ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
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
            plan) or you can submit a form through the “I did this” link with proof of
            your exams. Exams must take place 01/01/16 – 12/31/16 to receive points, points will be
            updated from claims received 60-90 days post exam date.</p>
        <p class="hide">Other activities can be updated for the current date or past date between 01/01/16 – 12/31/16 via
            the “Update” links to the right of each activity. Once these updates are made points will automatically
            populate in your activity pages. “Weekly Log” links provide the opportunity to log as often as you
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
            <td id="2nd_quarter_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 3rd Quarter Wellness Points (635 possible)-</th>
            <td id="3rd_quarter_points"></td>
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


class MTIActivities2016WMS2ComplianceProgramPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $quarterOneRecord = ComplianceProgramRecordTable::getInstance()->find(566);
        $quarterOneProgram = $quarterOneRecord->getComplianceProgram();
        $quarterOneProgram->setActiveUser($status->getUser());
        $quarterOneStatus = $quarterOneProgram->getStatus();

        $quarterTwoRecord = ComplianceProgramRecordTable::getInstance()->find(683);
        $quarterTwoProgram = $quarterTwoRecord->getComplianceProgram();
        $quarterTwoProgram->setActiveUser($status->getUser());
        $quarterTwoStatus = $quarterTwoProgram->getStatus();

        $quarterThreeRecord = ComplianceProgramRecordTable::getInstance()->find(784);
        $quarterThreeProgram = $quarterThreeRecord->getComplianceProgram();
        $quarterThreeProgram->setActiveUser($status->getUser());
        $quarterThreeStatus = $quarterThreeProgram->getStatus();

        $quarterFourRecord = ComplianceProgramRecordTable::getInstance()->find(891);
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
                                    $quarterOneStatus->getPoints(),
                                    '<span class="circle-points">'.$quarterOneStatus->getPoints(). '</span><br/><br/>1st Quarter<br/>Points'
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