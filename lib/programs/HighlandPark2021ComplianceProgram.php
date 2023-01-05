<?php

class HighlandPark2021HomePageComplianceProgram extends ComplianceProgram
{
    const SCHEDULE_BUTTON_OVERRIDE_TYPE = 'highland_park_schedule_button_override_2021';


    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('Procedure');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setAttribute('always_show_links_when_current', true);
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('View Results', '/content/989'));
        $screeningView->setReportName('Biometric Screening');

        $appointmentView = new ScheduleAppointmentComplianceView($programStart, $programEnd);
        $appointmentView->setAttribute('always_show_links_when_current', true);
        $appointmentView->setName('schedule_appointment');
        $appointmentView->setReportName('Screening Registration');
        $appointmentView->addLink(new Link('Register here', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        $appointmentView->setAlternativeComplianceView($screeningView);
        $appointmentView->setAttribute('continue', '?button_override=1');
        $appointmentView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($appointmentView) {
            if($user->getNewestDataRecord(HighlandPark2021HomePageComplianceProgram::SCHEDULE_BUTTON_OVERRIDE_TYPE, true)->clicked) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });

        $group->addComplianceView($appointmentView);

        $group->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);

        $hraView->setPostEvaluateCallback(function (ComplianceViewStatus $status) {
            if(!$status->isCompliant()) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });

        $hraView->setName('complete_hra');
        $hraView->setAttribute('always_show_links_when_current', true);
        $hraView->setReportName('Health Risk Assessment');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/613?quizid=2'));

        $group->addComplianceView($hraView);

        $coachingView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $coachingView->setAttribute('always_show_links_when_current', true);
        $coachingView->setAttribute('always_show_text', true);
        $coachingView->setName('coaching');
        $coachingView->setReportName('Health Coaching');
        $coachingView->setAttribute('text', 'Status Pending');
        $coachingView->addLink(new Link('View Status', '/compliance_programs'));
        $coachingView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {

            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();

            if($programStatus->getComplianceViewStatus('coaching')) {
                $coachingViewStatus = $programStatus->getComplianceViewStatus('coaching');
            } elseif ($programStatus->getComplianceViewStatus('health_coaching')) {
                $coachingViewStatus = $programStatus->getComplianceViewStatus('health_coaching');
            } elseif ($programStatus->getComplianceViewStatus('disease')) {
                $coachingViewStatus = $programStatus->getComplianceViewStatus('disease');
            }

            if(isset($coachingViewStatus)) {
                if($coachingViewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                    $status->getComplianceView()->setAttribute('text', 'Coaching Not Required or Completed');
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else {
                    $status->getComplianceView()->setAttribute('text', 'Coaching Required');
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                }
            }
        });
        $group->addComplianceView($coachingView);

        $winView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $winView->setAttribute('always_show_links_when_current', true);
        $winView->setName('win');
        $winView->setReportName('WIN Points');
        $winView->addLink(new Link('View WIN Points', '/compliance_programs'));
        $winView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {

            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();

            if($activeProgramRecord->id == 1562) {
                if($programStatus->getComplianceViewStatus('complete_screening')->getStatus() == ComplianceStatus::COMPLIANT
                    && $programStatus->getComplianceViewStatus('complete_hra')->getStatus() == ComplianceStatus::COMPLIANT
                    && $programStatus->getComplianceViewStatus('coaching')->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }

        });
        $group->addComplianceView($winView);

        $this->addComplianceViewGroup($group);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        if (($config = sfConfig::get('mod_compliance_programs_hmi_website_flow_record_integration'))
            && isset($config['compliance_program_record_id'], $config['views'])) {
            $record = ComplianceProgramRecordTable::getInstance()->find($config['compliance_program_record_id']);
            $extProgram = $record->getComplianceProgram() ;
            $extProgram->setActiveUser($status->getUser());
            $extStatus = $extProgram->getStatus();

            foreach($config['views'] as $extViewName => $localViewName) {
                if ($extStatus->getComplianceViewStatus($extViewName)->isCompliant()) {
                    $status->getComplianceViewStatus($localViewName)->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        }
    }
}


class HighlandPark2021ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('Advocate Track 2021', '0-1 Risk Factors');

            $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

            return $track;
        }
    }

    public function loadEvaluators()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $total = new ComplianceViewGroup('Total Points');
        $total->setPointsRequiredForCompliance(50);

        $mine = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($startDate, $endDate), array('Phase 1'), null, false);
        $mine->setName('mine');
        $mine->setReportName('My Total Points as of: '.date('m/d/Y').' =');
        $mine->setMaximumNumberOfPoints(415);
        $total->addComplianceView($mine, true);

        $spouse = new HighlandParkMyAndSpousePointsView($this->cloneForEvaluation($startDate, $endDate), array('Phase 1'), null, false);
        $spouse->setName('spouse');
        $spouse->setReportName('My Points + Spouse Points =');
        $spouse->setMaximumNumberOfPoints(680);
        $total->addComplianceView($spouse, true);

        $this->addComplianceViewGroup($total);
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $phase1 = new ComplianceViewGroup('Phase 1');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Biometric Screening');
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        $screening->addLink(new Link('Results', '/content/989'));
        //$screening->setAttribute('report_name_link', '/content/1094#1aBioScreen');

        $phase1->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Health Risk Assessment');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/1006'));
        $hra->addLink(new Link('Results', '/content/989'));
        //$hra->setAttribute('report_name_link', '/content/1094#1bHRA');
        $phase1->addComplianceView($hra);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching->setName('coaching');
        $coaching->setReportName('Health Coaching');
        $coaching->setAllowPointsOverride(true);
        //$coaching->setAttribute('report_name_link', '/content/1094#1cCoach');
        $phase1->addComplianceView($coaching);

        $this->addComplianceViewGroup($phase1);

        $phase2 = new ComplianceViewGroup('Phase 2 WIN Points Program');
        $phase2->setPointsRequiredForCompliance(0);

        $zeroRiskFactor = new PlaceHolderComplianceView(null, 0);
        $zeroRiskFactor->setName('zero_risk_factor');
        $zeroRiskFactor->setReportName('Zero Risk Factors');
        $zeroRiskFactor->setMaximumNumberOfPoints(30);
        $zeroRiskFactor->setAllowPointsOverride(true);
        $phase2->addComplianceView($zeroRiskFactor);

        $preventive = new CompleteAnyPreventionComplianceView($startDate, $endDate);
        $preventive->setName('milestone_screenings');
        $preventive->setReportName('Milestone Preventive Screens');
        $preventive->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $preventive->addLink(new Link('Learn More', '#popover_milestone_screenings'));
        $preventive->setAttribute('link_popover', 'Employees or spouses will earn 25 points for any required screening; only employees who fall into the age requirement to complete a screening are eligible.');
        //$preventive->setAttribute('report_name_link', '/content/1094#2dMilestone');
        $phase2->addComplianceView($preventive);

        $wellnessExam = new PlaceHolderComplianceView(null, 0);
        $wellnessExam->setName('wellness_exam');
        $wellnessExam->setReportName('Wellness Exam');
        $wellnessExam->setMaximumNumberOfPoints(20);
        $wellnessExam->setAllowPointsOverride(true);
        $wellnessExam->addLink(new Link('Learn More', '#popover_wellness_exam'));
        //$wellnessExam->setAttribute('report_name_link', '/content/1094#2eWellExam');
        $wellnessExam->setAttribute('link_popover', 'Employees or spouses who complete a physical will earn 15 points; must submit physical form to receive credit.');
        $phase2->addComplianceView($wellnessExam);

        $dentalVisit = new PlaceHolderComplianceView(null, 0);
        $dentalVisit->setName('dental_visit');
        $dentalVisit->setReportName('Annual Dental Exam');
        $dentalVisit->setMaximumNumberOfPoints(20);
        $dentalVisit->setAllowPointsOverride(true);
        $dentalVisit->addLink(new Link('Learn More', '#popover_dental_visit'));
        //$dentalVisit->setAttribute('report_name_link', '/content/1094#2fDental');
        $dentalVisit->setAttribute('link_popover', 'Employees or spouses will receive 20 points for a dental visit; must submit dental form to receive credit.');
        $phase2->addComplianceView($dentalVisit);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $lessons->setPointsPerLesson(5);
        $lessons->setReportName('Complete eLearning Lessons');
        $lessons->setMaximumNumberOfPoints(30);
        $lessons->emptyLinks();
        $lessons->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]='));
        //$lessons->setAttribute('report_name_link', '/content/1094#2heLearn');
        $phase2->addComplianceView($lessons);

        $fitnessTrainings = new PlaceHolderComplianceView(null, 0);
        $fitnessTrainings->setName('fitness_trainings');
        $fitnessTrainings->setReportName('Health and Fitness Trainings');
        $fitnessTrainings->setMaximumNumberOfPoints(90);
        $fitnessTrainings->setAllowPointsOverride(true);
        $fitnessTrainings->addLink(new Link('Learn More', '#popover_fitness_trainings'));
        //$fitnessTrainings->setAttribute('report_name_link', '/content/1094#2bTraining');
        $fitnessTrainings->setAttribute('link_popover', 'Employees will receive 15 points per training attended; sign in is required to receive points.');
        $phase2->addComplianceView($fitnessTrainings);

        $fitnessBonus = new PlaceHolderComplianceView(null, 0);
        $fitnessBonus->setName('fitness_bonus');
        $fitnessBonus->setReportName('Fitness Bonus Tests');
        $fitnessBonus->setMaximumNumberOfPoints(30);
        $fitnessBonus->setAllowPointsOverride(true);
        $fitnessBonus->addLink(new Link('Learn More', '#popover_fitness_bonus'));
        $fitnessBonus->setAttribute('link_popover', 'Employees are not required to pass to earn points; 10 points will be awarded per test—3 test limit.');
        //$fitnessBonus->setAttribute('report_name_link', '/content/1094#2cBonusTests');
        $phase2->addComplianceView($fitnessBonus);

        $useCenter = new PlaceHolderComplianceView(null, 0);
        $useCenter->setName('use_center');
        $useCenter->setReportName('Use of Fire/Police Fitness Center, Rec Center Track or Home Gym');
        $useCenter->setMaximumNumberOfPoints(20);
        $useCenter->setAllowPointsOverride(true);
        $useCenter->addLink(new Link('Learn More', '#popover_use_center'));
        $useCenter->setAttribute('link_popover', 'Employees or spouses who use the Fitness Center located at the Fire Station (Fire No.33) or Police employees who use the Police Fitness Center located in the Police Department, or the Rec Center Track will receive 2 points per visit; the maximum points an employee or spouse can receive per program year is 20 points.
        <br /><br />Employees or spouses will receive 2 points per visit (police station is only available to Police employees);  sign in is required to receive points.  Maximum points is 20 points per program year.  Individuals must sign a City consent form prior to using any fitness facilities.  ');
        //$useCenter->setAttribute('report_name_link', '/content/1094#2aFitnessCtr');
        $phase2->addComplianceView($useCenter);

        $gymMembershipView = new PlaceHolderComplianceView(null, 0);
        $gymMembershipView->setReportName('Gym membership');
        $gymMembershipView->setMaximumNumberOfPoints(20);
        $gymMembershipView->addLink(new Link('Learn More', '#popover_external'));
        //$gymMembershipView->setAttribute('report_name_link', '/content/1094#2heLearn');
        $phase2->addComplianceView($gymMembershipView);

        $external = new PlaceHolderComplianceView(null, 0);
        $external->setName('external');
        $external->setReportName('External Nutrition or Fitness Programs');
        $external->setMaximumNumberOfPoints(20);
        $external->setAllowPointsOverride(true);
        $external->addLink(new Link('Learn More', '#popover_external'));
        $external->setAttribute('link_popover', 'Employees and spouses who participate in an external weight loss or nutrition program such as Weight Watchers or work with a Nutritionist will receive a maximum of  20 points per calendar year.<br/><br/> Employees or spouses, who participate in any league, or club, such as a running group, will also receive a maximum of 20 points per calendar year. Participants may only receive 20 points for participating in either one of the programs.  <br/><br/>For example, an employee who joins Weight Watchers and is in a volleyball league may only receive 20 points for one of the activities. Participants must show proof of participation to the WIN Wellness Administrator to receive credit.');
        //$external->setAttribute('report_name_link', '/content/1094#2gExtProgram');
        $phase2->addComplianceView($external);

        $runWalk = new PlaceHolderComplianceView(null, 0);
        $runWalk->setName('run_walk');
        $runWalk->setReportName('Organized Run/Walk');
        $runWalk->setMaximumNumberOfPoints(10);
        $runWalk->setAllowPointsOverride(true);
        $phase2->addComplianceView($runWalk);

        $bonus = new PlaceHolderComplianceView(null, 0);
        $bonus->setName('bonus');
        $bonus->setReportName('Bonus Wellness Points');
        $bonus->setAllowPointsOverride(true);
        //$bonus->setAttribute('report_name_link', '/content/1094#2iBonusPts');
        $phase2->addComplianceView($bonus);
        $this->addComplianceViewGroup($phase2);

        $programs = new ComplianceViewGroup('Health Challenge Programs');
        $programs->setPointsRequiredForCompliance(0);

        $one = new PlaceHolderComplianceView(null, 0);
        $one->setAllowPointsOverride(true);
        $one->setName('activity_334');
        $one->setReportName('Challenge 1');
        $one->setMaximumNumberOfPoints(25);
        $one->addLink(new Link('Learn More', '#popover_activity_334'));
        $one->setAttribute('link_popover', '<span style="font-weight: bold;font-size: 10pt;">Gratitude Challenge</span> <br /><br />Timeframe: December to January<br /><br />Write down three things you are thankful for over a 30 day period.');
        //$one->setAttribute('report_name_link', '/content/1094#3Chall1');
        $programs->addComplianceView($one);

        $two = new PlaceHolderComplianceView(null, 0);
        $two->setAllowPointsOverride(true);
        $two->setName('activity_268');
        $two->setReportName('Challenge 2');
        $two->setMaximumNumberOfPoints(25);
        $two->addLink(new Link('Learn More', '#popover_activity_268'));
        $two->setAttribute('link_popover', '<span style="font-weight: bold;font-size: 10pt;">Step Challenge</span><br /><br />May<br /><br />Step Challenge can be completed by teams of individuals.');
        //$two->setAttribute('report_name_link', '/content/1094#3Chall2');
        $programs->addComplianceView($two);

        $three = new PlaceHolderComplianceView(null, 0);
        $three->setAllowPointsOverride(true);
        $three->setName('activity_269');
        $three->setReportName('Challenge 3');
        $three->setMaximumNumberOfPoints(25);
        $three->addLink(new Link('Learn More', '#popover_activity_269'));
        $three->setAttribute('link_popover', '<span style="font-weight: bold;font-size: 10pt;">Stress Reduction Challenge</span><br /><br />July 6th to August 2nd<br /><br />Completed 15 days of daily stress reduction practices including meditation, yoga, journaling, stretching, etc.');
        //$three->setAttribute('report_name_link', '/content/1094#3Chall3');
        $programs->addComplianceView($three);

        $four = new PlaceHolderComplianceView(null, 0);
        $four->setAllowPointsOverride(true);
        $four->setName('activity_270');
        $four->setReportName('Challenge 4');
        $four->setMaximumNumberOfPoints(25);
        $four->addLink(new Link('Learn More', '#popover_activity_270'));
        $four->setAttribute('link_popover', '<span style="font-weight: bold;font-size: 10pt;">Activity Challenge</span><br /><br />September<br /><br />150 minutes of activity a week over four weeks. Activity of participants’ choosing.');
        //$four->setAttribute('report_name_link', '/content/1094#3Chall4');
        $programs->addComplianceView($four);

        $this->addComplianceViewGroup($programs);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();
        $track = trim($this->getTrack($user));

        $coachingStatus = $status->getComplianceViewStatus('coaching');

        if($track == '2 Risk Factors') {
            $numberCompleted = $coachingStatus->getPoints();
            if(is_numeric($numberCompleted) && $numberCompleted >= 3) {
                $coachingStatus->setStatus(ComplianceStatus::COMPLIANT);
            } elseif(is_numeric($numberCompleted) && $numberCompleted > 0 & $numberCompleted < 3) {
                $coachingStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }

        } elseif ($track == '3+ Risk Factors') {
            $numberCompleted = $coachingStatus->getPoints();
            if(is_numeric($numberCompleted) && $numberCompleted >= 6) {
                $coachingStatus->setStatus(ComplianceStatus::COMPLIANT);
            } elseif(is_numeric($numberCompleted) && $numberCompleted > 0 & $numberCompleted < 6) {
                $coachingStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }

        }
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        $printer->addEndStatusFieldCallBack('Relationship Status', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $spouseUser = $this->hasSpouseUser($user);

            if($spouseUser) {
                return 'With Spous';
            } else {
                return 'Self';
            }
        });

        $printer->addEndStatusFieldCallBack('Earned 5%', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $points = $status->getComplianceViewStatus('spouse')->getPoints() >= 150;

            $spouseUser = $this->hasSpouseUser($user);

            if($spouseUser && $points >= 75) {
                return 'Yes';
            } elseif(!$spouseUser && $points >= 50) {
                return 'Yes';
            } else {
                return 'No';
            }
        });


        $printer->addEndStatusFieldCallBack('Earned 10%', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $points = $status->getComplianceViewStatus('spouse')->getPoints() >= 150;

            $spouseUser = $this->hasSpouseUser($user);

            if($spouseUser && $points >= 150) {
                return 'Yes';
            } elseif(!$spouseUser && $points >= 100) {
                return 'Yes';
            } else {
                return 'No';
            }
        });


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new HighlandPark2021ComplianceProgramReportPrinter();
    }


    public function hasSpouseUser($user)
    {
        $spouseUser = false;

        $relationshipUsers = array();

        if($user->relationship_user_id && !$user->relationshipUser->expired()) {
            $relationshipUsers[] = $user->relationshipUser;
        }

        foreach($user->relationshipUsers as $relatedUser) {
            if(!$relatedUser->expired()) {
                $relationshipUsers[] = $relatedUser;
            }
        }

        foreach($relationshipUsers as $relatedUser) {
            if(in_array($relatedUser->relationship_type, $this->relationshipTypes)) {
                $spouseUser = $relatedUser;

                break;
            }
        }

        return $spouseUser;
    }

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
    private $lastTrack = null;
}

class HighlandPark2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->setShowLegend(true);
        $this->pageHeading = '2021 WIN (Wellness Initiative Program)';
        $this->tableHeaders['completed'] = 'Date Done';
        $this->setShowTotal(false);

        parent::printReport($status);
        ?>
        <p><small>* Phase 1 requirements must also be met in addition to points required.</small></p>
        <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $track = $status->getComplianceProgram()->getTrack($user);

        $coachingStatus = $status->getComplianceViewStatus('coaching');

        ?>
        <style type="text/css">
            #overviewCriteria {
                width:100%;
                border-collapse:collapse;
            }

            #legendText {
                background-color:#90C4DE;
            }

            #overviewCriteria th {
                background-color:#42669A;
                color:#FFFFFF;
                font-weight:normal;
                font-size:11pt;
                padding:5px;
            }

            #overviewCriteria td {
                width:33.3%;
                vertical-align:top;
            }

            .phipTable .headerRow {
                background-color:#90C4DE;
            }

            .phipTable {
                font-size:12px;
                margin-bottom:10px;
            }

            .phipTable th, .phipTable td {
                padding:1px 2px;
                border:1px solid #a0a0a0;
            }

            .phipTable .points {
                width:80px;
            }

            .phipTable .links {
                width:250px;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
                <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <?php if($popoverContent = $view->getAttribute('link_popover')) : ?>
                $('a[href="#popover_<?php echo $view->getName() ?>"]').popover({
                    title: <?php echo json_encode(preg_replace('|<br[ ]*/?>.*|', '', $view->getReportName())) ?>,
                    content: <?php echo json_encode($popoverContent) ?>,
                    trigger: 'hover',
                    html: true
                });
                <?php endif ?>
                <?php endforeach ?>
                <?php endforeach ?>

                $('tr.view-mine').prev('tr.headerRow').find('td').last().html(
                    'Minimum Points Needed for Incentive By 10/31/2021'
                );

                $('tr.view-mine .links').html('If single: 100 points for 10%; 50 for 5%');
                $('tr.view-spouse .links').html('With spouse: 150 points for 10%; 75 for 5%');

                <?php if($track == '2 Risk Factors') : ?>
                $('.view-coaching').children(':eq(0)').html('<strong>C</strong>. Health Coaching: Complete 3 Sessions (Must begin by September 28, 2021)');
                $('.view-coaching').children('.result').html('<?php echo $coachingStatus->getPoints() ?>');
                $('.view-coaching').children('.links').html('To schedule an appointment, send email to <a href="mailto:AMG-AAWCallCenter@advocatehealth.com">AMG-AAWCallCenter@advocatehealth.com</a> OR Call the Advocate Aurora Contact Center at 800.761.5856');
                <?php elseif($track == '3+ Risk Factors') : ?>
                $('.view-coaching').children(':eq(0)').html('<strong>C</strong>. Health Coaching: Complete 6 Sessions (Must begin by August 10, 2021)');
                $('.view-coaching').children('.result').html('<?php echo $coachingStatus->getPoints() ?>');
                $('.view-coaching').children('.links').html('To schedule an appointment, send email to <a href="mailto:AMG-AAWCallCenter@advocatehealth.com">AMG-AAWCallCenter@advocatehealth.com</a> OR Call the Advocate Aurora Contact Center at 800.761.5856');
                <?php else : ?>

                $('.view-coaching').remove();

                <?php endif ?>
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>
        <p>The table below shows the status of your key actions and points that count toward the WIN program.</p>
        <p>Click on any link to learn more and get these things done for your wellbeing and other rewards!</p>
        <h5 style="text-align: center"><?php echo $track ?></h5>


        <?php
    }
}