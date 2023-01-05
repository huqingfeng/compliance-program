<?php
use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
require_once sprintf('%s/apps/frontend/modules/compliance_programs/lib/programs/Beacon5for5Campaign2016ComplianceProgram.php', sfConfig::get('sf_root_dir'));

class BeaconFiveForFive2016ComplianceView extends ComplianceView
{
    public function __construct($pointsPerWeek)
    {
        $this->pointsPerWeek = $pointsPerWeek;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return '5_for_5';
    }

    public function getDefaultReportName()
    {
        return '5 for 5 campaign(3/14-4/15/16)';
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord(BEACON_FIVE_FOR_FIVE_CAMPAIGN, true);
        $points = BeaconFiveForFiveCampaignCommitmentForm::getPoints($record);
        $weeks = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user));

        $totalPoints = 0;
        foreach($weeks as $week){
            if(isset($points[$week]['completed_all_five'])
                && isset($points[$week]['completed_lessons'])
                && $points[$week]['completed_all_five']
                && $points[$week]['completed_lessons']) {

                $totalPoints += $this->pointsPerWeek;
            }
        }

        return new ComplianceViewStatus($this, null, $totalPoints);
    }
}


class BeaconWeeklyStepsComplianceView extends ComplianceView
{
    public function __construct($threshold, $pointsPer)
    {
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
    }

    public function addDateRange($startDate, $endDate)
    {
        $this->ranges[] = array($startDate, $endDate);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "beacon_weekly_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "Beacon Weekly Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $points = 0;

        foreach($this->ranges as $range) {
            $fitBitView = new CHPWalkingCampaignFitbitComplianceView($range[0], $range[1]);
            $fitBitViewStatus = $fitBitView->getStatus($user);

            $manualStepsView = new SumStepsInArbitraryActivityComplianceView($range[0], $range[1], 332, 110);
            $manualStepsViewStatus = $manualStepsView->getStatus($user);

            $minutesToStepsView = new CHPWalkingCampaignExerciseToSteps($range[0], $range[1], 333, 0);
            $minutesToStepsViewStatus = $minutesToStepsView->getStatus($user);

            $weekPoints = $fitBitViewStatus->getPoints() + $manualStepsViewStatus->getPoints() + $minutesToStepsViewStatus->getPoints();

            if($weekPoints >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private $threshold;
    private $pointsPer;
    private $ranges = array();
}

class BeaconActivities2015DailyLogComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($questionId, $threshold, $timeLength, $maxPoints)
    {
        $this->id = 343;
        $this->totalDays = 365;

        parent::__construct('2015-09-01', '2016-09-30');

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
            $points += $result >= $this->threshold ? round($this->maxPoints /($this->totalDays / $this->timeLength), 2) : 0;
        }
        return new ComplianceViewStatus($this, null, $points);
    }

    private $id;
    private $questionId;
    private $threshold;
    private $timeLength;
    private $maxPoints;
}


class BeaconLightActivities2015ScreeningConfirmationComplianceView extends ComplianceView
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
        $startDate = sfConfig::get('app_legacy_beacon_physician_review_report_start_date', '2015-06-01');

        $record = $user->getNewestDataRecord('release_hpa_2016');
        if($record->exists() && $startDate <= $record->released_date && $record->isViewable) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 10);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}


class BeaconLightActivities2015ComplianceProgram extends ComplianceProgram
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
        $view = new BeaconActivities2015DailyLogComplianceView($questionId, $threshold, $timeLength, $maxPoints);

        $view->setName($name);

        if ($reportName !== null) {
            $view->setReportName($reportName);
        }

        $view->setMaximumNumberOfPoints($maxPoints);

        $view->emptyLinks();

        return $view;
    }

    protected function getStepsView($name, $requiredSteps, $pointsPerWeek, $maxPoints, $reportName = null)
    {
        $view = new BeaconWeeklyStepsComplianceView($requiredSteps, $pointsPerWeek);
        $view->setMaximumNumberOfPoints($maxPoints);
        $view->setName($name);
        $view->setReportName($reportName);

        $ranges = array(
            'week1' => array('2015-08-31', '2015-09-06'),
            'week2' => array('2015-09-07', '2015-09-13'),
            'week3' => array('2015-09-14', '2015-09-20'),
            'week4' => array('2015-09-21', '2015-09-27'),
            'week5' => array('2015-09-28', '2015-10-04')
        );

        foreach($ranges as $name => $dates) {
            $view->addDateRange($dates[0], $dates[1]);
        }

        return $view;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(null, null, true, false, true);

        $spectrumProgram = ComplianceProgramRecordTable::getInstance()->find(BeaconLightActivities2015ComplianceProgram::LIGHT_SPECTRUM_2015_RECORD_ID)->getComplianceProgram();

        $activitiesProgram = ComplianceProgramRecordTable::getInstance()->find(BeaconLightActivities2015ComplianceProgram::LIGHT_ACTIVITIES_2015_RECORD_ID)->getComplianceProgram();

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
            $totalEmpBiometricIgnoringAlternativesPoints = $employeeSpectrumStatus ? $employeeSpectrumStatus->getAttribute('total_points_ignoring_alternatives')  : 0;
            $totalEmpActivityPoints = $employeeActivityStatus ? $employeeActivityStatus->getPoints() : 0;
            $totalEmpActivityIgnoringAlternativesPoints = $employeeActivityStatus ? $employeeActivityStatus->getAttribute('total_points_ignoring_alternatives')  : 0;
            $totalSpBiometricPoints = $spouseSpectrumStatus ? $spouseSpectrumStatus->getPoints() : 0;
            $totalSpBiometricIgnoringAlternativesPoints = $spouseSpectrumStatus ? $spouseSpectrumStatus->getAttribute('total_points_ignoring_alternatives')  : 0;
            $totalSpActivityPoints = $spouseActivityStatus ? $spouseActivityStatus->getPoints() : 0;
            $totalSpActivityIgnoringAlternativesPoints = $spouseActivityStatus ? $spouseActivityStatus->getAttribute('total_points_ignoring_alternatives')  : 0;

            $isSingle = true;
            if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                $isSingle = $user->getSpouseUser() ? false : true;
            } elseif($user->getRelationshipType() == Relationship::SPOUSE) {
                $isSingle = $user->getEmployeeUser() ? false : true;
            }

            $totalAveragePoints = 0;
            $totalAverageIgnoringAlternativesPoints = 0;
            if($isSingle) {
                if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                    $totalAveragePoints = $totalEmpBiometricPoints + $totalEmpActivityPoints;
                    $totalAverageIgnoringAlternativesPoints = $totalEmpBiometricIgnoringAlternativesPoints + $totalEmpActivityIgnoringAlternativesPoints;
                } elseif ($user->getRelationshipType() == Relationship::SPOUSE) {
                    $totalAveragePoints = $totalSpBiometricPoints + $totalSpActivityPoints;
                    $totalAverageIgnoringAlternativesPoints = $totalSpBiometricIgnoringAlternativesPoints + $totalSpActivityIgnoringAlternativesPoints;
                }
            } else {
                $totalAveragePoints = ($totalEmpBiometricPoints + $totalEmpActivityPoints + $totalSpBiometricPoints + $totalSpActivityPoints) / 2;
                $totalAverageIgnoringAlternativesPoints = ($totalEmpBiometricIgnoringAlternativesPoints + $totalEmpActivityIgnoringAlternativesPoints + $totalSpBiometricIgnoringAlternativesPoints + $totalSpActivityIgnoringAlternativesPoints) / 2;
            }

            $hraStatus = $userSpectrumStatus->getComplianceViewStatus('hra');
            $screeningStatus  = $userSpectrumStatus->getComplianceViewStatus('screening');

            $appointmentDate = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('a.date')
                ->from('appointment_times at')
                ->innerJoin('appointments a')
                ->on('a.id = at.appointmentid')
                ->where('at.user_id = ?', array($user->id))
                ->andWhere('a.typeid IN ?', array(array(1, 41)))
                ->andWhere('a.date BETWEEN ? AND ?', array('2015-10-10', '2016-09-30'))
                ->groupBy('a.date DESC')
                ->execute();

            return array(
                'Biometric Points' => $userSpectrumStatus->getPoints(),
                'Hra Date'          => $hraStatus->getComment(),
                'Hra Compliant'     => $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No',
                'Screening Date'          => $screeningStatus->getComment(),
                'Screening Compliant'     => $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No',
                'Appointment Date'      => $appointmentDate ? $appointmentDate : '',
                'Grand Total Points' => $userSpectrumStatus->getPoints() + $status->getPoints(),
                'Total employee biometric points' => $totalEmpBiometricPoints,
                'Total employee LiGHT Activity points' => $totalEmpActivityPoints,
                'Total employee points' => $totalEmpBiometricPoints + $totalEmpActivityPoints,
                'Total employee points (Ignoring Alternatives)' => $totalEmpBiometricIgnoringAlternativesPoints + $totalEmpActivityIgnoringAlternativesPoints,
                'Total Spouse biometric points' => $totalSpBiometricPoints,
                'Total spouse LiGHT Activity points' => $totalSpActivityPoints,
                'Total spouse points' => $totalSpBiometricPoints + $totalSpActivityPoints,
                'Total spouse points (Ignoring Alternatives)' => $totalSpBiometricIgnoringAlternativesPoints + $totalSpActivityIgnoringAlternativesPoints,
                'Average Points'       => $totalAveragePoints,
                'Average points (Ignoring Alternatives)'    => $totalAverageIgnoringAlternativesPoints,
                'Total combined points for both employee and spouse' =>
                    $totalEmpBiometricPoints + $totalEmpActivityPoints + $totalSpBiometricPoints + $totalSpActivityPoints,
                'Total combined points for both employee and spouse (Ignoring Alternatives)' =>
                    $totalEmpBiometricIgnoringAlternativesPoints + $totalEmpActivityIgnoringAlternativesPoints + $totalSpBiometricIgnoringAlternativesPoints + $totalSpActivityIgnoringAlternativesPoints
            );
        });

        $printer->addCallbackField('Employee Id', function (User $user) {
            return (string) $user->employeeid;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if (sfConfig::get('app_wms2')) {
            return new BeaconLightActivities2015WMS2ComplianceProgramPrinter();
        } else {
            $printer = new BeaconLightActivities2015ComplianceProgramPrinter();
            $printer->hide_status_when_point_based = true;
            $printer->requirements = false;
            $printer->show_progress = true;
            $printer->page_heading = 'My LiGHT Activities (<a href="/compliance_programs?id=533">View LiGHT Spectrum</a>)';
            $printer->show_group_totals = true;

            return $printer;
        }
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

//        $confirmationView = new BeaconLightActivities2015ScreeningConfirmationComplianceView($startDate, $endDate);
        $confirmationView = new PlaceHolderComplianceView(null, 0);
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

        if (sfConfig::get('app_wms2')) {
            $confirmationView->addLink(
              new Link('I did this', '/resources/8217/Provider Checklist_2016.pdf', 'physician-reviewed-link', '_target')
            );
            $confirmationView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
                $hraLink = sprintf('/' . sfConfig::get('wms3_hpa_report_type', 'content/751') . '?user_id=%s', $user->id);
                $status->getComplianceView()->addLink(
                    new Link('HRA Report', $hraLink, 'most-recent-hra-link', '_target')
                );
            });
        }

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

//        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView($startDate, $endDate, array(30, 31));
        $fluTetView = new PlaceHolderComplianceView(null, 0);
        $fluTetView->setName('comply_with_multiple_hra_questions_30_31');
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
        $fitness->addComplianceView($this->getSummableActivityView('strength', 133, 60, 7, 30, 'Strength Training'));
        $fitness->addComplianceView($this->getActivityView('fitness_lnl', 385, 5));
//        $fitness->addComplianceView($this->getStepsView('get_up_and_go', 50000, 5, 25, "Get Up & Go Campaign (9/1-9/30/2015)"));
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

        $fiveForFiveView = new BeaconFiveForFive2016ComplianceView(5);
        $fiveForFiveView->setMaximumNumberOfPoints(25);

        $nutrition->addComplianceView($learningView);
        $nutrition->addComplianceView($this->getActivityView('nutrition_lnl', 386, 5));
        $nutrition->addComplianceView($fiveForFiveView);
        $nutrition->setMaximumNumberOfPoints(50);
        if(date('Y-m-d') > '2016-03-31') {
            $nutrition->setAttribute('available_points', 75);
        } else {
            $nutrition->setAttribute('available_points', 105);
        }

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
            $this->getComplianceView($dailyViewName)->addLink(
                new Link(
                    sfConfig::get('app_wms2') ? 'Daily Log' : 'Daily Log <span style="color:red; font-weight: bolder">*</span>',
                    '/content/12048?action=showActivity&activityidentifier=343'
                )
            );
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

        $view->setPostEvaluateCallback(function(ComplianceStatus $status, User $user) use($view, &$completedLessons, $alternativeView)  {
            if(!isset($completedLessons[$user->id])) {
                $completedLessons[$user->id] = $alternativeView->getStatus($user)->getAttribute('lessons_completed', array());
            }

            if(!$status->isCompliant() && ($lessonIdDone = array_shift($completedLessons[$user->id])) !== null) {
                $status->setPoints(5);
            }

            $view->emptyLinks();
            $view->addLink(new Link('E-learning', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        });
    }


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $status->setAttribute('total_points_ignoring_alternatives', $status->getPoints() - $extraPoints);
    }

    const LIGHT_ACTIVITIES_2015_RECORD_ID = 527;
    const LIGHT_SPECTRUM_2015_RECORD_ID = 533;
}

class BeaconLightActivities2015WMS2ComplianceProgramPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $spectrumProgramRecord = ComplianceProgramRecordTable::getInstance()->find(533);
        $spectrumProgram = $spectrumProgramRecord->getComplianceProgram();
        $spectrumProgram->setActiveUser($status->getUser());
        $spectrumStatus = $spectrumProgram->getStatus();
        $totalStatus = ComplianceStatus::NOT_COMPLIANT;
        $totalPoints = $spectrumStatus->getPoints() + $status->getPoints();

        if (!$status->getUser()->insurancetype) {
            $spectrumStatus->getComplianceViewGroupStatus('required')->getComplianceViewGroup()->setMaximumNumberOfPoints(100);
        }

        if ($totalPoints >= 800) {
            $totalStatus = ComplianceStatus::COMPLIANT;
        } else if ($totalPoints >= 400) {
            $totalStatus = ComplianceStatus::PARTIALLY_COMPLIANT;
        }

        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($status, $text) use ($classForStatus) {
            $class = $status === 'beacon' ? 'beacon' : $classForStatus($status);
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
                <h1>MY LiGHT <small>ACTIVITIES</small></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a href="/content/beacon-previous-program-years">Previous Program Years</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 col-md-offset-2 text-center">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1 activity">
                                <?php echo $circle(
                                    'beacon',
                                    '<span class="circle-points">'.$status->getPoints(). '</span><br/><br/>Activity<br/>points'
                                ) ?>
                                <br/>
                                <strong><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?></strong> points possible
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                <?php echo $circle(
                                    'beacon',
                                    '<span class="circle-points">'.$spectrumStatus->getPoints().'</span><br/><br/>Spectrum<br/>points'
                                ) ?>
                                <br/>
                                <strong><?php echo $spectrumProgram->getMaximumNumberOfPoints() ?></strong> points possible
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                <?php echo $circle(
                                    $totalStatus,
                                    '<span class="circle-points">'.$totalPoints. '</span><br/><br/>Total<br/>points'
                                ) ?>
                                <br/>
                                <strong>
                                    <?php echo
                                        $status->getComplianceProgram()->getMaximumNumberOfPoints() +
                                        $spectrumProgram->getMaximumNumberOfPoints()
                                    ?>
                                </strong> points possible
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p><a href="#" id="more-info-toggle">More...</a></span></p>

                <div id="more-info" style="display: none">
                    <p>On this page are activities in which you can accumulate wellness points through the LiGHT Program for
                        healthy behaviors. You can accumulate a maximum of 350 activity points, though there are many
                        more point opportunities to choose from!</p>

                    <p>
                        In the Prevention category, #1 (Confirmation of physician reviewed wellness screening)- those
                        on the plan with physician reviewed reports are awarded 10 points post physician review.
                        Non-plan members must submit proof via the Provider Checklist.#2 (Preventative Exams)- will
                        be updated based on claims received (if on the medical/dental plan) or you can submit a form
                        through the “I did this” link with proof of your exams. Exams must take place 10/13/2015-9/30/2016
                        to receive points in this category, points will be updated from medical/dental claims received
                        60-90 days post exam date.Your HRA response will automatically update your points for #3 (Tetanus & Flu vaccinations).
                    </p>

                    <p>
                        Other activities can be updated for the current date or past date between 10/13/2015-9/30/2016
                        via the “Update” links to the right of each activity. Once these updates are made points will automatically
                        populate in your activity pages. “Daily Log” links provide the opportunity to log as often as you wish.
                    </p>
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
                    <?php echo $tableRow('Community', $status->getComplianceViewGroupStatus('Community')) ?>
                    <?php echo $tableRow('Mind', $status->getComplianceViewGroupStatus('Mind')) ?>
                    <?php echo $tableRow('Financial', $status->getComplianceViewGroupStatus('Financial')) ?>
                    <?php echo $tableRow('Exercise', $status->getComplianceViewGroupStatus('Exercise')) ?>
                    <?php echo $tableRow('Nutrition', $status->getComplianceViewGroupStatus('Nutrition')) ?>
                    <?php echo $tableRow('De-stress', $status->getComplianceViewGroupStatus('De-stress')) ?>
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

class BeaconLightActivities2015ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
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

            .phipTable tbody .links{
                width: 100px;
            }

            #legend {
                display:none;
            }
        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=533', function(fullPage) {
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

                $('.progress').each(function() {
                    $(this).hide();
                })

                $('.phipTable tbody').children(':eq(36)').find('.progress').show();
                $('.phipTable tbody').children(':eq(37)').find('.progress').show();
                $('.phipTable tbody').children(':eq(42)').find('.progress').show();
                $('.phipTable tbody').children(':eq(43)').find('.progress').show();
                $('.phipTable tbody').children(':eq(50)').find('.progress').show();
                $('.phipTable tbody').children(':eq(51)').find('.progress').show();

                $('.phipTable tbody').children(':eq(2)').find('.points, .maxpoints, .links').css('vertical-align', 'top');
            });
        </script>
        <?php
        parent::printReport($status);
    }

    public function printClientMessage()
    {
        ?>
        <img src=" /resources/6479/icon_2016.png " style="width:200px; position:relative; top:-150px; left:580px;"/>
        <p>Below are activities in which you can accumulate wellness points through the LiGHT Program for
            healthy behaviors. You can accumulate a maximum of 350 activity points, though there are many more
            point opportunities to choose from!</p>
        <p><span class="show_more"><a href="#">More...</a></span></p>
        <p class="hide">In the Prevention category below, your HRA responses will automatically update your points for #1
            (Confirmation of physician reviewed wellness screening) and #3 (Tetanus & Flu vaccinations). #2 (Preventative Exams)
            will be updated based on claims received (if on the medical plan) or you can submit a form through the “I did this”
            link with proof of your exams.  Exams must take place 10/13/2015-9/30/2016 to receive points in this category, points
            will be updated from claims received 60-90 days post exam date.</p>
        <p class="hide">Other activities can be updated for the current date or past date between 10/13/2015-9/30/2016
            via the “Update” links to the right of each activity. Once these updates are made points will automatically
            populate in your activity pages. “Daily Log” links provide the opportunity to log as often as you wish.</p>
        <p><a href="/resources/5033/Beacon-LiGHT-program-detail.090214.pdf">LiGHT Activity Details</a></p>

        <div style="margin-top: 10px; margin-bottom: 10px;">
            <a href="/compliance_programs?id=335">2015 program LiGHT Spectrum</a> <br />
            <a href="/compliance_programs?id=365">2015 program LiGHT Activities</a>
        </div>
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
