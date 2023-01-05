<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MTIActivities2015Q2WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validQ2Weeks = array(
        15 => '04/06/2015 through 04/12/2015',
        16 => '04/13/2015 through 04/19/2015',
        17 => '04/20/2015 through 04/26/2015',
        18 => '04/27/2015 through 05/03/2015',
        19 => '05/04/2015 through 05/10/2015',
        20 => '05/11/2015 through 05/17/2015',
        21 => '05/18/2015 through 05/24/2015',
        22 => '05/25/2015 through 05/31/2015',
        23 => '06/01/2015 through 06/07/2015',
        24 => '06/08/2015 through 06/14/2015',
        25 => '06/15/2015 through 06/21/2015',
        26 => '06/22/2015 through 06/28/2015',
        27 => '06/29/2015 through 07/05/2015'
    );

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $pointsPer, $divisor, $additional = null)
    {
        $this->id = 416;

        parent::__construct('2015-04-01', '2015-06-31');

        $this->additional = $additional;
        $this->questionId = $questionId;
        $this->pointsPer = $pointsPer;
        $this->divisor = $divisor;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, '2015-03-01', '2015-12-30');

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



class MTIActivities2015Q2ComplianceProgram extends ComplianceProgram
{
    const MTI_2014_ACTIVITY_START_DATE = '2015-04-01';

    protected function getActivityView($name, $activityId, $points, $reportName = null)
    {
        $view = new CompleteArbitraryActivityComplianceView(
            MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE,
            $this->getEndDate(),
            $activityId,
            $points
        );

        $view->setMaximumNumberOfPoints($points);

        $view->setName($name);

        if($reportName !== null) {
            $view->setReportName($reportName);
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
          return array_sum($program->getFitbitWater($user));
        } else if ($questionId == 132) {
          return array_sum($program->getFitbitActivity($user));
        } else if ($questionId == 135) {
          return array_sum($program->getFitbitSleep($user));
        } else {
          return 0;
        }
      };

      $view = new MTIActivities2015Q2WeeklyLogComplianceView($questionId, $pointsPer, $divisor, $additional);

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
        $printer = new MTIActivities2015Q2ComplianceProgramPrinter();
        $printer->hide_status_when_point_based = true;
        $printer->requirements = false;
        $printer->page_heading = 'My Wellness Activities (<a href="/compliance_programs?id=388">View Report Card</a>)';
        $printer->show_group_totals = true;

        return $printer;
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

                if (isset(MTIActivities2015Q2WeeklyLogComplianceView::$validQ2Weeks[$w])) {
                    $text = MTIActivities2015Q2WeeklyLogComplianceView::$validQ2Weeks[$w];

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
        $start = strtotime(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData(get_fitbit_activities_data($user), $start, $end);
    }

    public function getFitbitSleep($user)
    {
        $start = strtotime(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE);
        $end = $this->getEndDate();

        return $this->filterFitbitData(get_fitbit_sleep_data($user), $start, $end);
    }

    public function getFitbitWater($user)
    {
        $start = strtotime(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE);
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
        $prevention->setAttribute('available_points', 65);
        $prevention->setMaximumNumberOfPoints(55);

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
           }
        });
        $prevention->addComplianceView($confirmationView);

        $phoneCoach = new PlaceHolderComplianceView(null, 0);
        $phoneCoach->setMaximumNumberOfPoints(10);
        $phoneCoach->setName('phone_coach');
        $phoneCoach->setReportName('Phone Coaching Session');
        $prevention->addComplianceView($phoneCoach);

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

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE, $endDate, array(30, 31));
        $fluTetView->setReportName('Flu/Pertussis/Tetanus Vaccine');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 399, 10));
        $learningView = new CompleteELearningGroupSet(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE, $endDate, 'mti_prevention');
        $learningView->setReportName('Complete an e-learning module');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $prevention->addComplianceView($learningView);
        
        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 416, 132, 1/3, 20, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 416, 133, 1/3, 12, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 409, 5));
        $learningView = new CompleteELearningGroupSet(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE, $endDate, 'mti_fitness');
        $learningView->setReportName('Complete an e-learning module');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $fitness->addComplianceView($learningView);

        $fitness->setMaximumNumberOfPoints(60);
        $fitness->setAttribute('available_points', 70);

        $nutrition = new ComplianceViewGroup('Nutrition');
        $nutrition->setPointsRequiredForCompliance(0);
        $nutrition->addComplianceView($this->getActivityView('nutritionist', 400, 10));
        $nutrition->addComplianceView($this->getActivityView('grocery', 401, 15));
        $nutrition->addComplianceView($this->getSummableActivityView('water', 416, 136, 1/3, 8, 30, 'Drink Enough Water'));
        $nutrition->addComplianceView($this->getSummableActivityView('fruit', 416, 137, 1/3, 4, 30, 'Eat Enough Fruit & Vegetables'));

        $learningView = new CompleteELearningGroupSet(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE, $endDate, 'light_activities');
        $learningView->setReportName('Complete an e-learning module');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $nutrition->addComplianceView($learningView);
        $nutrition->addComplianceView($this->getActivityView('nutrition_lnl', 408, 5));
        $nutrition->setMaximumNumberOfPoints(75);
        $nutrition->setAttribute('available_points', 95);

        $stress = new ComplianceViewGroup('Stress Management');
        $stress->setPointsRequiredForCompliance(0);
        $stress->addComplianceView($this->getActivityView('one_vacation', 403, 10));
        $stress->addComplianceView($this->getSummableActivityView('sleep', 416, 135, 1/3, 6.5, 30, 'Sleep'));
        $stress->addComplianceView($this->getActivityView('financial', 405, 10));
        $stress->addComplianceView($this->getActivityView('eap', 406, 5));

        $stress->addComplianceView($this->getSummableActivityView('just_for_you', 416, 134, 1/3, 15, 30, 'Just for you time'));
        $stress->addComplianceView($this->getActivityView('dave', 417, 10));
        $learningView = new CompleteELearningGroupSet(MTIActivities2015Q2ComplianceProgram::MTI_2014_ACTIVITY_START_DATE, $endDate, 'mti_stress');
        $learningView->setReportName('Complete an e-learning module');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $stress->addComplianceView($learningView);
        $stress->addComplianceView($this->getActivityView('stress_lnl', 407, 10));
        $stress->setMaximumNumberOfPoints(75);
        $stress->setAttribute('available_points', 110);

        $other = new ComplianceViewGroup('Other');
        $other->setAttribute('available_points', 20);
        $other->addComplianceView($this->getActivityView('outside_event', 418, 5));
        $other->addComplianceView($this->getActivityView('additional_internal_event', 419, 5));
        $mtiDiscretion = new PlaceHolderComplianceView(null, 0);
        $mtiDiscretion->setName('mti_discretion');
        $mtiDiscretion->setReportName('Additional points up to MTI discretion');
        $mtiDiscretion->setMaximumNumberOfPoints(10);
        $other->addComplianceView($mtiDiscretion);
        $other->setPointsRequiredForCompliance(0);

        $this->addComplianceViewGroup($prevention);
        $this->addComplianceViewGroup($fitness);
        $this->addComplianceViewGroup($nutrition);
        $this->addComplianceViewGroup($stress);
        $this->addComplianceViewGroup($other);

        foreach(array('water', 'fruit', 'sleep', 'cardio', 'strength') as $dailyViewName) {
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

class MTIActivities2015Q2ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status)
    {
        if(!$status->getUser()->insurancetype) {
            $status->getComplianceViewStatus('screening_confirmation')->getComplianceView()->addLink(
                new Link('Update', '/content/12048?action=showActivity&activityidentifier=420')
            );
        }

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
                $.get('/compliance_programs?id=388', function(fullPage) {
                    var $page = $(fullPage);

                    var bioPoints = parseInt($page.find('#spectrum_points').html(), 10);

                    $.get('/compliance_programs?id=385', function(fullPage) {
                        var $page = $(fullPage);

                        var firstQuarterPoints = parseInt($page.find('#activity_points').html(), 10);

                        $('#1st_quarter_points').html(bioPoints + firstQuarterPoints);
                    });

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
            your exams. Exams must take place 01/01/15 – 12/31/15 to receive points, points will be
            updated from claims received 60-90 days post exam date.</p>
        <p class="hide">Other activities can be updated for the current date or past date between 01/01/15 – 12/31/15 via
            the “Update” links to the right of each activity. Once these updates are made points will automatically
            populate in your activity pages. “Weekly Log” links provide the opportunity to log as often as you
            wish and total points will accumulate at the end of the program as these are on-going activities.</p>
        <p><a href="/resources/5164/Wellness-program-detail.110714.pdf">Wellness Activity Details</a></p>
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
            <th colspan="2">My 2nd Quarter Wellness Points (370 possible)-</th>
            <td id="2nd_quarter_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 3rd Quarter Wellness Points (370 possible)-</th>
            <td id="3rd_quarter_points"></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My 4th Quarter Wellness Points (370 possible)-</th>
            <td id="4th_quarter_points"></td>
            <td></td>
            <td></td>
        </tr>
        <?php
    }
}


