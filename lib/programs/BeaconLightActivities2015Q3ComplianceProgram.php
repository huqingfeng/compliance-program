<?php
use hpn\steel\query\SelectQuery;

class BeaconActivities2015Q3DailyLogComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $threshold, $timeLength, $maxPoints)
    {
        $this->id = 343;

        parent::__construct('2015-07-01', '2015-09-30');

        $this->questionId = $questionId;
        $this->threshold = $threshold;
        $this->timeLength = $timeLength;
        $this->maxPoints = $maxPoints;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $total = array();
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->questionId])) {
                if(!isset($total[$record->getDate('W')])) $total[$record->getDate('W')] = 0;
                if(!isset($total[$record->getDate('Y-m-d')])) $total[$record->getDate('Y-m-d')] = 0;

                if($this->timeLength == 7) {
                    $total[$record->getDate('W')] += $answers[$this->questionId]->getAnswer();
                } else {
                    $total[$record->getDate('Y-m-d')] += $answers[$this->questionId]->getAnswer();
                }
            }
        }

        $points = 0;
        foreach($total as $result) {
            $points += $result >= $this->threshold ? round($this->maxPoints/(90/$this->timeLength), 2) : 0;
        }
        return new ComplianceViewStatus($this, null, $points);
    }

    private $id;
    private $questionId;
    private $threshold;
    private $timeLength;
    private $maxPoints;
}


class BeaconLightActivities2015Q3ScreeningConfirmationComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultName()
    {
        return 'screening_confirmation';
    }

    public function getDefaultReportName()
    {
        return 'Confirmation of physician reviewed Wellness Screening';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $startDate = sfConfig::get('app_legacy_beacon_physician_review_report_start_date', '2014-06-01');

        $record = $user->getNewestDataRecord('releaseHPA');
        if($record->exists() && $startDate <= $record->released_date && $record->isViewable) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 10);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}


class BeaconLightActivities2015Q3ComplianceProgram extends ComplianceProgram
{
    protected function getActivityView($name, $activityId, $points, $reportName = null)
    {
        $view = new CompleteArbitraryActivityComplianceView(
            $this->getStartDate(),
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

    protected function getSummableActivityView($name, $questionId, $threshold, $timeLength, $maxPoints, $reportName = null)
    {
        $view = new BeaconActivities2015Q3DailyLogComplianceView($questionId, $threshold, $timeLength, $maxPoints);

        $view->setName($name);

        if ($reportName !== null) {
            $view->setReportName($reportName);
        }

        $view->setMaximumNumberOfPoints($maxPoints);

        $view->emptyLinks();

        return $view;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $spectrumProgram = ComplianceProgramRecordTable::getInstance()->find(BeaconLightActivities2015Q3ComplianceProgram::LIGHT_SPECTRUM_2014_RECORD_ID)->getComplianceProgram();

        $activitiesProgram = ComplianceProgramRecordTable::getInstance()->find(BeaconLightActivities2015Q3ComplianceProgram::LIGHT_ACTIVITIES_2015_Q3_RECORD_ID)->getComplianceProgram();

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($spectrumProgram, $activitiesProgram) {
            $getProgramStatus = function(ComplianceProgram $program, User $user) {
                $program->setActiveUser($user);

                $ret = $program->getStatus();

                $program->setActiveUser(null);

                return $ret;
            };

            $user = $status->getUser();
            $employeeUser = $user->getRelationshipType() == Relationship::EMPLOYEE ? $user : $user->getEmployeeUser();
            $spouseUser = $user->getRelationshipType() == Relationship::SPOUSE ? $user : $user->getSpouseUser();

            $userActivityStatus = $status;
            $userSpectrumStatus = $getProgramStatus($spectrumProgram, $user);

            $employeeActivityStatus = !$employeeUser ? false : (
                $user->id == $employeeUser->id ?
                    $userActivityStatus : $getProgramStatus($activitiesProgram, $employeeUser)
            );

            $employeeSpectrumStatus = !$employeeUser ? false : (
                $user->id == $employeeUser->id ?
                    $userSpectrumStatus : $getProgramStatus($spectrumProgram, $employeeUser)
            );

            $spouseActivityStatus = !$spouseUser ? false : (
                $user->id == $spouseUser->id ?
                    $userActivityStatus : $getProgramStatus($activitiesProgram, $spouseUser)
            );

            $spouseSpectrumStatus = !$spouseUser ? false : (
                $user->id == $spouseUser->id ?
                    $userSpectrumStatus : $getProgramStatus($spectrumProgram, $spouseUser)
            );

            $totalEmpBiometricPoints = $employeeSpectrumStatus ? $employeeSpectrumStatus->getPoints() : 0;
            $totalEmpActivityPoints = $employeeActivityStatus ? $employeeActivityStatus->getPoints() : 0;
            $totalSpBiometricPoints = $spouseSpectrumStatus ? $spouseSpectrumStatus->getPoints() : 0;
            $totalSpActivityPoints = $spouseActivityStatus ? $spouseActivityStatus->getPoints() : 0;

            return array(
                'Biometric Points' => $userSpectrumStatus->getPoints(),
                'Grand Total Points' => $userSpectrumStatus->getPoints() + $status->getPoints(),
                'Total employee biometric points' => $totalEmpBiometricPoints,
                'Total employee LiGHT Activity points' => $totalEmpActivityPoints,
                'Total employee points' => $totalEmpBiometricPoints + $totalEmpActivityPoints,
                'Total Spouse biometric points' => $totalSpBiometricPoints,
                'Total spouse LiGHT Activity points' => $totalSpActivityPoints,
                'Total spouse points' => $totalSpBiometricPoints + $totalSpActivityPoints,
                'Total combined points for both employee and spouse' =>
                    $totalEmpBiometricPoints + $totalEmpActivityPoints + $totalSpBiometricPoints + $totalSpActivityPoints
            );
        });

        $printer->addCallbackField('Employee Id', function (User $user) {
            return (string) $user->employeeid;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new BeaconLightActivities2015Q3ComplianceProgramPrinter();
        $printer->hide_status_when_point_based = true;
        $printer->requirements = false;
        $printer->show_progress = true;
        $printer->page_heading = 'My LiGHT Activities (<a href="/compliance_programs?id=335">View LiGHT Spectrum</a>)';
        $printer->show_group_totals = true;

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '6000M');

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $prevention = new ComplianceViewGroup('Prevention');
        $prevention->setPointsRequiredForCompliance(0);
        $prevention->setMaximumNumberOfPoints(50);
        $prevention->setAttribute('available_points', 60);

        $confirmationView = new BeaconLightActivities2015Q3ScreeningConfirmationComplianceView($startDate, $endDate);
        $confirmationView->setName('screening_confirmation');
        $confirmationView->setReportName('Confirmation of physician reviewed Wellness Screening');
        $confirmationView->setMaximumNumberOfPoints(10);
        $confirmationView->setPostEvaluateCallback(function($status, $user) use($startDate, $endDate) {
           if(!$user->insurancetype) {
               $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 373, 10);

               if($alternative->getStatus($user)->getPoints() > 0) {
                   $status->setPoints(10);
                   $status->setStatus(ComplianceStatus::COMPLIANT);
               }
           }
        });
        $prevention->addComplianceView($confirmationView);

        $examsView = new PlaceHolderComplianceView(null, 0);
        $examsView->setMaximumNumberOfPoints(30);
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
        $examsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($startDate, $endDate) {
            $numberCompleted = SelectQuery::create()
               ->hydrateSingleScalar()
                ->from('prevention_data')
                ->select('COUNT(DISTINCT code)')
                ->where('user_id = ?', array($user->id))
                ->andWhere('date BETWEEN ? AND ?', array(date('Y-m-d', $startDate), date('Y-m-d', $endDate)))
                ->andWhere('type IN ?', array(array(
                    PreventionType::PAP_TEST,
                    PreventionType::PROSTATE,
                    PreventionType::PSA,
                    PreventionType::MAMMOGRAPHY,
                    PreventionType::COLO_RECTAL_COLONOSCOPY,
                    PreventionType::PHYSICAL,
                    PreventionType::DENTAL,
                    PreventionType::VISION
                )))
                ->execute();

            if ($numberCompleted >= 2) {
                $status->setPoints(30);
            }
        });
        $prevention->addComplianceView($examsView);

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView($startDate, $endDate, array(30, 31));
        $fluTetView->setReportName('Tetanus &amp; Flu Vaccinations');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $fluTetView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getActivityView('prevention_lnl', 381, 5));
        $prevention->addComplianceView($this->getActivityView('rewards_counselor', 388, 10));

        $community = new ComplianceViewGroup('Community');
        $community->setPointsRequiredForCompliance(0);
        $community->addComplianceView($this->getActivityView('donate_blood', 346, 10));
        $community->addComplianceView($this->getActivityView('mentor', 347, 10));
        $community->addComplianceView($this->getActivityView('donate_non_profit', 348, 10));
        $community->addComplianceView($this->getActivityView('church', 349, 20));
        $community->addComplianceView($this->getActivityView('volunteer_on_board', 389, 15));
        $community->addComplianceView($this->getActivityView('community_lnl', 382, 5));
        $community->setMaximumNumberOfPoints(50);
        $community->setAttribute('available_points', 70);

        $brain = new ComplianceViewGroup('Mind');
        $brain->setPointsRequiredForCompliance(0);
        $brain->addComplianceView($this->getActivityView('crossword', 351, 10));
        $brain->addComplianceView($this->getActivityView('puzzle', 352, 10));
        $brain->addComplianceView($this->getActivityView('language', 353, 15));
        $brain->addComplianceView($this->getActivityView('instrument', 354, 15));
        $brain->addComplianceView($this->getActivityView('cognitive_program', 355, 10));
        $brain->addComplianceView($this->getActivityView('education_class', 356, 5));
        $brain->addComplianceView($this->getActivityView('meditation', 357, 10));
        $brain->addComplianceView($this->getActivityView('brain_lnl', 383, 5));
        $brain->setMaximumNumberOfPoints(50);
        $brain->setAttribute('available_points', 80);

        $financial = new ComplianceViewGroup('Financial');
        $financial->setPointsRequiredForCompliance(0);
        $financial->setAttribute('available_points', 70);

        $fairView = $this->getActivityView('retirement_fair', 359, 5);
        $fairView->setReportName('Attend Retirement Fair or Retirement Education Workshop');
        $fairViewLinks = $fairView->getLinks();
        $fairViewLink = reset($fairViewLinks);
        $fairViewLink->setLinkText('Update');

        $financial->addComplianceView($fairView);
        $financial->addComplianceView($this->getActivityView('retirement_rep', 375, 5));
        $financial->addComplianceView($this->getActivityView('plan_contribute', 376, 15));
        $financial->addComplianceView($this->getActivityView('plan_beneficiary', 377, 5));
        $financial->addComplianceView($this->getActivityView('budget', 363, 15));
        $financial->addComplianceView($this->getActivityView('pay_loan', 364, 5));
        $financial->addComplianceView($this->getActivityView('emergency_fund', 365, 15));
        $financial->addComplianceView($this->getActivityView('financial_lnl', 384, 5));
        $financial->setMaximumNumberOfPoints(50);

        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getSummableActivityView('cardio', 132, 180, 7, 30, 'Cardio Exercise'));
        $fitness->addComplianceView($this->getSummableActivityView('strength', 133, 180, 7, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 385, 5));
        $fitness->setMaximumNumberOfPoints(50);
        $fitness->setAttribute('available_points', 65);

        $nutrition = new ComplianceViewGroup('Nutrition');
        $nutrition->setPointsRequiredForCompliance(0);
        $nutrition->addComplianceView($this->getActivityView('nutritionist', 368, 5));
        $nutrition->addComplianceView($this->getSummableActivityView('water', 136, 7, 1, 30, 'Drink Enough Water'));
        $nutrition->addComplianceView($this->getSummableActivityView('fruit', 137, 28, 7, 30, 'Eat Enough Fruit & Vegetables'));

        $learningView = new CompleteELearningGroupSet($startDate, $endDate, 'light_activities');
        $learningView->setReportName('E-learning lessons (complete one from the link to the right)');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $nutrition->addComplianceView($learningView);
        $nutrition->addComplianceView($this->getActivityView('nutrition_lnl', 386, 5));
        $nutrition->setMaximumNumberOfPoints(50);
        $nutrition->setAttribute('available_points', 75);

        $stress = new ComplianceViewGroup('De-stress');
        $stress->setPointsRequiredForCompliance(0);
        $stress->addComplianceView($this->getActivityView('one_vacation', 370, 5));
        $stress->addComplianceView($this->getActivityView('two_vacation', 371, 10));
        $stress->addComplianceView($this->getSummableActivityView('relax', 134, 15, 1, 30, 'Relax / Take Time for Yourself'));
        $stress->addComplianceView($this->getSummableActivityView('sleep', 135, 7, 1, 30, 'Sleep'));
        $stress->addComplianceView($this->getActivityView('stress_lnl', 387, 5));
        $stress->setMaximumNumberOfPoints(50);
        $stress->setAttribute('available_points', 80);

        $this->addComplianceViewGroup($prevention);
        $this->addComplianceViewGroup($community);
        $this->addComplianceViewGroup($brain);
        $this->addComplianceViewGroup($financial);
        $this->addComplianceViewGroup($fitness);
        $this->addComplianceViewGroup($nutrition);
        $this->addComplianceViewGroup($stress);

        foreach(array('prevention_lnl', 'community_lnl', 'brain_lnl', 'financial_lnl', 'fitness_lnl', 'nutrition_lnl', 'stress_lnl') as $lnlViewName) {
            $this->configureViewForElearning($this->getComplianceView($lnlViewName));
        }

        foreach(array('cardio', 'strength', 'water', 'fruit', 'relax', 'sleep') as $dailyViewName) {
            $this->getComplianceView($dailyViewName)->addLink(new Link('Daily Log <span style="color:red; font-weight: bolder">*</span>', '/content/12048?action=showActivity&activityidentifier=343'));
        }

        foreach($this->getComplianceViews() as $view) {
            foreach($view->getLinks() as $link) {
                if($link->getLinkText() == 'Enter/Update Info') {
                    $link->setLinkText('Update');
                }
            }
        }
    }

    protected function configureViewForElearning(ComplianceView $view)
    {
        static $completedLessons = array();

        $alternativeView = new CompleteELearningLessonsComplianceView($this->getStartDate(), $this->getEndDate(), null, 1);

        $view->setPostEvaluateCallback(function(ComplianceStatus $status, User $user) use($view, $completedLessons, $alternativeView) {
            if(!isset($completedLessons[$user->id])) {
                $completedLessons[$user->id] = $alternativeView->getStatus($user)->getAttribute('lessons_completed', array());
            }

            if(!$status->isCompliant() && ($lessonIdDone = array_shift($completedLessons[$user->id])) !== null) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });
    }

    const LIGHT_ACTIVITIES_2015_Q3_RECORD_ID = 539;
    const LIGHT_SPECTRUM_2014_RECORD_ID = 335;
}



class BeaconLightActivities2015Q3ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status)
    {
        if(!$status->getUser()->insurancetype) {
            $status->getComplianceViewStatus('screening_confirmation')->getComplianceView()->addLink(
                new Link('Update', '/content/12048?action=showActivity&activityidentifier=373')
            );
        }

        ?>
        <style type="text/css">
            .phipTable {
                font-size:0.9em;
            }

            #legend {
                display:none;
            }
        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=335', function(fullPage) {
                    var $page = $(fullPage);

                    $('#combined_points').html(
                        '' + (parseInt($page.find('#spectrum_points').html(), 10) + <?php echo $status->getPoints() ?>)
                    );
                });
                
                $('.show_more').toggle(function(){                   
                   $('.hide').show(); 
                   $('.show_more a').html('Less...');
                }, function(){
                   $('.hide').hide(); 
                   $('.show_more a').html('More...');
                });

                $('.pageHeading').after(
                    '<p style="color: red; font-weight: bold">' +
                    'The “Daily Log” activities listed below are tracked by each entry within each category. points will ' +
                    'populate at the end of the program year. The system accurately tracks these points even though you do not show partial point ' +
                    'values during the program year. Activity can be logged through 9/30/2015 and points will populate on 10/1/2015.</p>'
                );

                $('.progress').each(function() {
                    $(this).hide();
                })

                $('.phipTable tbody').children(':eq(36)').children(':eq(1)').html('0');
                $('.phipTable tbody').children(':eq(37)').children(':eq(1)').html('0');
                $('.phipTable tbody').children(':eq(42)').children(':eq(1)').html('0');
                $('.phipTable tbody').children(':eq(43)').children(':eq(1)').html('0');
                $('.phipTable tbody').children(':eq(50)').children(':eq(1)').html('0');
                $('.phipTable tbody').children(':eq(51)').children(':eq(1)').html('0');

                $('.phipTable tbody').children(':eq(36)').find('.progress').show();
                $('.phipTable tbody').children(':eq(37)').find('.progress').show();
                $('.phipTable tbody').children(':eq(42)').find('.progress').show();
                $('.phipTable tbody').children(':eq(43)').find('.progress').show();
                $('.phipTable tbody').children(':eq(50)').find('.progress').show();
                $('.phipTable tbody').children(':eq(51)').find('.progress').show();
            });
        </script>
        <?php
        parent::printReport($status);
    }

    public function printClientMessage()
    {
        ?>
        <p>Below are activities in which you can accumulate wellness points through the LiGHT Program for
            healthy behaviors. You can accumulate a maximum of 350 activity points, though there are many more
            point opportunities to choose from!</p>
        <p><span class="show_more"><a href="#">More...</a></span></p>
        <p class="hide">In the Prevention category below, your HRA responses will automatically update your points for #1
            (Confirmation of physician reviewed wellness screening) and #3 (Tetanus & Flu vaccinations). #2 (Preventative Exams)
            will be updated based on claims received (if on the medical plan) or you can submit a form through the “I did this”
            link with proof of your exams.  Exams must take place 9/2/2014-9/30/2015 to receive points in this category, points
            will be updated from claims received 60-90 days post exam date.</p>
        <p class="hide">Other activities can be updated for the current date or past date between 9/2/2014-9/30/2015 via
            the “Update” links to the right of each activity. Once these updates are made points will automatically populate
            in your activity pages. “Daily Log” links provide the opportunity to log as often as you wish and total
            points will accumulate at the end of the program as these are on-going activities.</p>
        <p><a href="/resources/5033/Beacon-LiGHT-program-detail.090214.pdf">LiGHT Activity Details</a></p>
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
        // This row is here so the other reportcard can grab its content via
        // ajax
        ?>
        <tr class="headerRow" style="display:none">
            <th colspan="2">My Total LiGHT Activity Points (350 possible)-</th>
            <td id="activity_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
        </tr>
        <?php
    }
}


