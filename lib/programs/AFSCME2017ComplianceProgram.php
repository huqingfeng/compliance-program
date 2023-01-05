<?php

/**
 * Contains all classes for AFSCME 2016 compliance program. Depends on classes
 * in the 2013 program that are unchanged for 2016.
 */
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/administration/userCenterLib.php', sfConfig::get('sf_root_dir'));

class AFSCME2017TobaccoFree extends ComplianceView
{
    public function __construct(ComplianceView $view)
    {
        $this->view = $view;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('afscme_tobacco_free_2016');

        if($this->view->getStatus($user)->isCompliant()) {
            $status = ComplianceStatus::COMPLIANT;
        } elseif($record->partial) {
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
        }

        return new ComplianceViewStatus($this, $status);
    }

    public function getDefaultName()
    {
        return 'tobacco';
    }

    public function getDefaultReportName()
    {
        return 'Tobacco';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;

    }

    private $view;
}

class AFSCME2017AssignedElearningComplianceView extends CompleteAssignedELearningLessonsComplianceView
{
    public function getStatus(User $user)
    {
        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);

        if(validForCoaching($user)) {
            $status =  parent::getStatus($user);
            if($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
            } else {
                return $status;
            }
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class AFSCME2017HealthCoachingEvaluationComplianceView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        if(validForCoaching($user)) {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}




class AFSCME2017ComplianceProgram extends ComplianceProgram
{
    public function getLocalActions()
    {
        return array(
            'dashboardCounts' => array($this, 'executeDashboardCounts')
        );
    }

    public function executeDashboardCounts(sfActions $actions)
    {
        $this->setActiveUser($actions->getSessionUser());

        $data = array('compliant' => 0, 'naCompliant' => 0, 'partiallyCompliant' => 0, 'notCompliant' => 0);

        $status = $this->getStatus();

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $groupName = $groupStatus->getComplianceViewGroup()->getName();

            if ($groupName === 'start' || $groupName === 'key' || $groupName === 'ongoing') {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    if ($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        $data['compliant']++;
                    } else if ($viewStatus->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                        $data['naCompliant']++;
                    } else if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $data['partiallyCompliant']++;
                    } else {
                        $data['notCompliant']++;
                    }
                }
            }
        }

        $actions->getResponse()->setContentType('application/json');
        
        $actions->setLayout(false);

        echo json_encode($data);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('insurance_plan_type', function (User $user) {
            return $user->insurance_plan_type;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new AFSCME2017ScreeningPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(false);

            foreach($this->getComplianceViewGroups() as $group) {
                foreach($group->getComplianceViews() as $view) {
                    if(is_callable(array($view, 'setUseDateForComment'))) {
                        $view->setUseDateForComment(false);
                    }
                }
            }
        } else {
            $printer = new AFSCME2017Printer();
        }

        return $printer;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

//        if($status->getComplianceViewGroupStatus('key')->isCompliant()) {
//            $status->getComplianceViewStatus('doc')->setStatus(ComplianceStatus::COMPLIANT);
//        }

        $status->setPoints(null);

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($rec = $viewStatus->getAttribute('newest_record')) {
                    $viewStatus->setComment($rec);
                }
            }
        }
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMapping(
            ComplianceStatus::NA_COMPLIANT, new ComplianceStatusMapping('N/A *', '/images/lights/whitelight.gif')
        );

        $this->setComplianceStatusMapper($mapping);

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();


        $coreEndDate = strtotime('2017-05-01');

        $hraScreeningEndDate = strtotime('2017-04-01');

        $keyEndDate = strtotime('2017-10-15');

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($startDate, '2017-07-01');
        $nonSmokerView->setUseDateForComment(true);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Non-User of Tobacco');
        $nonSmokerView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        });

        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');

        $start = new ComplianceViewGroup('start', 'Starting core actions required by deadline below:');

        $elearn = new CompleteRequiredELearningLessonsComplianceView('2016-12-01', $coreEndDate);
        $elearn->setReportName('Complete core e-Learning Lessons');
        $elearn->setAttribute('report_name_link', '/content/1094new2017#1aelearn');
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Review/Do Lessons', '/content/9420?action=lessonManager&tab_alias[]=required'));
        $start->addComplianceView($elearn);

        $surveyView = new CompleteSurveyComplianceView(36);
        $surveyView->setName('resource_survey');
        $surveyView->setReportName('Complete Resource Survey');
        $surveyView->setAttribute('deadline', '04/15/2017');
        $surveyView->setAttribute('report_name_link', '/content/1094new2017#1bSurvey');
        $surveyView->emptyLinks();
        $surveyView->addLink(new Link('Start/Finish Survey', '/surveys/36'));
        $start->addComplianceView($surveyView);


        $hpa = new CompleteHRAComplianceView($startDate, $coreEndDate);
        $hpa->setReportName('Complete Health Power Assessment (HPA)');
        $hpa->setAttribute('report_name_link', '/content/1094new2017#1chpa');
        $hpa->emptyLinks();
        $hpa->addLink(new Link('My HPA & Results', '/wms2/compliance/afscme/my-health'));
        $start->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView('2016-12-01', $coreEndDate);
        $scr->setReportName('Complete Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094new2017#1dscreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Sign-Up', '/content/1094new2017#1dscreen'));
        $scr->addLink(new Link('Results', '/wms2/compliance/afscme/my-health'));
        $start->addComplianceView($scr);

        $doc = new UpdateDoctorInformationComplianceView($startDate, $coreEndDate);
        $doc->setReportName('Have a Main Doctor');
        $doc->setAttribute('report_name_link', '/content/1094new2017#1emaindoc');
        $start->addComplianceView($doc);

        $updateInfo = new UpdateContactInformationComplianceView($startDate, $coreEndDate);
        $updateInfo->setReportName('Confirm/Update Key Contact Info – email, address, phone');
        $updateInfo->setAttribute('report_name_link', '/content/1094new2017#1fpers');
        $updateInfo->emptyLinks();
        $updateInfo->addLink(new Link('Enter/Update Info', '/wms2/profile/contact?redirect='.urlencode('/compliance/afscme/phip/compliance_programs?id=946')));
        $start->addComplianceView($updateInfo);

        $workbook = new AFSCMEViewWorkbookComplianceView($startDate, $coreEndDate);
        $workbook->setReportName('View & Use Your Health Navigator Workbook');
        $workbook->setAttribute('report_name_link', '/content/1094new2017#1ghealthnav');
        $workbook->emptyLinks();
        $workbook->addLink(new Link('View / Update Workbook', '/content/12056'));
        $start->addComplianceView($workbook);


        $tobView = new AFSCME2017TobaccoFree($nonSmokerView);
        $tobView->setName('tob_view');
        $tobView->setReportName('Use No Type of Tobacco or Complete Cessation Program');
        $tobView->setAttribute('report_name_link', '/content/1094new2017#1htobacco');
        $tobView->setAttribute('deadline', '07/01/2017');
        $tobView->emptyLinks();
        $tobView->addLink(new Link('Enroll in Cessation Program', '/content/1094new2017#1htobacco'));
        $start->addComplianceView($tobView);

        $this->addComplianceViewGroup($start);

        $ongoing = new ComplianceViewGroup('ongoing', 'Ongoing core actions all year');

        $calls = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $calls->setReportName('Make required calls to the Care Counselor nurse BEFORE receiving certain types of health care and other times. If a nurse calls you, return the call AND work with them until you are told you are done.');
        $calls->setAttribute('report_name_link', '/content/1094new2017#2acounsel');
        $calls->setName('calls_1');
        $calls->setAttribute('deadline', 'Within 5 days of being called each time');
        $calls->addLink(new Link('Learn More', '/content/5317 '));
        $ongoing->addComplianceView($calls);

        $callsTwo = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);
        $callsTwo->setRequireTargeted(true);
        $callsTwo->setUseTargeted(true);
        $callsTwo->setReportName('Return Calls of a Health Coach if they call you AND work with them until you are told you are done.');
        $callsTwo->setAttribute('report_name_link', '/content/1094new2017#2bcoach');
        $callsTwo->setName('calls_2');
        $callsTwo->setAttribute('deadline', 'With 5 days of being called each time.');
        $callsTwo->addLink(new Link('Learn More', '/content/1094new2017#2bcoach'));
        $ongoing->addComplianceView($callsTwo);

        $callsThree = new AFSCME2017AssignedElearningComplianceView($startDate, $keyEndDate);
        $callsThree->setReportName('Complete extra e-Learning lessons and decision tools recommended by Health Coach or Nurse.');
        $callsThree->setAttribute('report_name_link', '/content/1094new2017#2ccoacheLearn');
        $callsThree->setName('calls_3');
        $callsThree->setAttribute('deadline', 'Within 30 days of recommendation');
        current($callsThree->getLinks())->setLinkText('View/Do Lessons');
        $ongoing->addComplianceView($callsThree);


        $this->addComplianceViewGroup($ongoing);


        $key = new ComplianceViewGroup('key', 'Key measures of health');

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setUseDateForComment(true);
        $totalCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $this->configureViewForElearningAlternative($totalCholesterolView, $startDate, $keyEndDate, 'cholesterol');
        $key->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $hdlCholesterolView->setUseDateForComment(true);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $this->configureViewForElearningAlternative($hdlCholesterolView, $startDate, $keyEndDate, 'cholesterol');
        $key->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $ldlCholesterolView->setUseDateForComment(true);
        $ldlCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $this->configureViewForElearningAlternative($ldlCholesterolView, $startDate, $keyEndDate, 'cholesterol');
        $key->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $trigView->setUseDateForComment(true);
        $trigView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $this->configureViewForElearningAlternative($trigView, $startDate, $keyEndDate, 'cholesterol');
        $key->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $glucoseView->setUseDateForComment(true);
        $glucoseView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $this->configureViewForElearningAlternative($glucoseView, $startDate, $keyEndDate, 'blood_sugars');
        $key->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $bloodPressureView->setUseDateForComment(true);
        $bloodPressureView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $this->configureViewForElearningAlternative($bloodPressureView, $startDate, $keyEndDate, 'blood_pressure');
        $key->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setUseDateForComment(true);
        $bodyFatBMIView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $this->configureViewForElearningAlternative($bodyFatBMIView, $startDate, $keyEndDate, 'body_fat');
        $key->addComplianceView($bodyFatBMIView);

        $this->configureViewForElearningAlternative($nonSmokerView, $startDate, $keyEndDate, 'tobacco');
        $key->addComplianceView($nonSmokerView);

        $this->addComplianceViewGroup($key);

        $keyMeasureCallback = array($this, 'keyMeasuresCompletedSoNotRequired');

        $dem = new ComplianceViewGroup('demonstrate', 'Tools to help you achieve your health and wellbeing goals.');
        $dem->setNumberOfViewsRequired(4);

        $otherKeyHealth = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $otherKeyHealth->setReportName('Learn more about resources to help you achieve you health goals');
        $otherKeyHealth->setAttribute('report_name_link', '/content/1094new2017#4aKeyActions');
        $otherKeyHealth->setName('other_key_health');
        $otherKeyHealth->addLink(new Link('Learn More', '/compliance/afscme/phip/content/1094new2017#4aKeyActions'));
        $dem->addComplianceView($otherKeyHealth);

        $withDoctor = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $withDoctor->setReportName('Work with Doctor to Improve Your Results.Health');
        $withDoctor->setAttribute('report_name_link', '/content/1094new2017#4bWorkDoctor');
        $withDoctor->setName('with_doctor');
        $withDoctor->addLink(new Link('Learn More', '/compliance/afscme/phip/content/1094new2017#4bWorkDoctor'));
        $dem->addComplianceView($withDoctor);

        $prev = new CompletePreventiveExamWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $prev->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $prev->setMaximumNumberOfPoints(20);
        $prev->setReportName('Record Preventive Screenings/Exams Obtained');
        $prev->setAttribute('report_name_link', '/content/1094new2017#4cprevScreen');
        $prev->emptyLinks();
        $prev->addLink(new Link('Enter or Update Info', '/content/12048?action=showActivity&activityidentifier=26'));
        $dem->addComplianceView($prev);

        $imm = new CompleteImmunizationsWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $imm->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $imm->setMaximumNumberOfPoints(20);
        $imm->setReportName('Record Immunizations Obtained');
        $imm->setAttribute('report_name_link', '/content/1094new2017#4dimmun');
        $imm->emptyLinks();
        $imm->addLink(new Link('Enter or Update Info', '/content/12048?action=showActivity&activityidentifier=242'));
        $dem->addComplianceView($imm);


        $this->addComplianceViewGroup($dem);
    }

    public function keyMeasuresCompletedSoNotRequired(User $user)
    {
        $group = $this->getComplianceViewGroup('key');

        return !$group->getStatusForUser($user)->isCompliant();
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $startDate, $endDate, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($startDate, $endDate, $alias) {
            $view = $status->getComplianceView();

            $elearningView = new CompleteELearningGroupSet($startDate, $endDate, $alias);
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(2);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', $elearningStatus->getAttribute('lessons_completed', array()));

            if($elearningStatus->getStatus() == ComplianceStatus::NA_COMPLIANT
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT )) {
                $elearningStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            } elseif ($elearningStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT
                && ($status->getStatus() == ComplianceStatus::COMPLIANT || $status->getStatus() == ComplianceStatus::NA_COMPLIANT)) {
                $elearningStatus->setStatus(ComplianceStatus::NA_COMPLIANT);
            }

            $status->setAttribute('alternate_status_object', $elearningStatus);
        });

    }
}

class AFSCME2017Printer extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
        <p style="font-size:smaller;">* Not applicable at this time. Will change
            if a Care Counselor or Health Coach is trying to reach you, made
            recommendations to complete and/or required calls are not being made.</p>
        <?php
    }

    public function showGroup($group)
    {
        $this->tableHeaders['column_one'] = 'Deadline';
        $this->tableHeaders['column_two'] = 'Date Done';
        $this->tableHeaders['column_three'] = 'Status';

        switch($group->getName()) {
            case 'start':


                break;

            case 'ongoing':


                break;

            case 'key':
                $this->tableHeaders['column_one'] = 'Risk Status';
                $this->tableHeaders['column_two'] = '# Lessons Done';
                $this->tableHeaders['column_three'] = 'Lesson Status';

                break;

            case 'demonstrate':
                $this->tableHeaders['column_two'] = 'Last Update';
                $this->tableHeaders['column_three'] = '';

                break;
        }

        return true;
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have these measures in the healthy green zone or complete 2 related e-learning lessons to help improve each that is not green.', '/content/1094new2017#3abio'));

        $this->pageHeading = 'My Personal Health Improvement Prescription • PHIP / To-Dos';

        $this->numberScreeningCategory = false;
        $this->showName = true;
        $this->showCompleted = false;
        $this->showStatus = false;
        $this->setShowTotal(false);

        $this->screeningAllResultsArea = '';
        $this->screeningLinkText = '';

        $this->addStatusCallbackColumn('column_one', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            if($view->getComplianceViewGroup()->getName() == 'key') {
                return sprintf('<img src="%s" class="light" alt="" />', $status->getLight());
            } else {
                $default = $view instanceof DateBasedComplianceView ?
                    $view->getEndDate('m/d/Y') : '';

                return $view->getAttribute('deadline', $default);
            }
        });

        $this->addStatusCallbackColumn('column_two', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            if($view->getComplianceViewGroup()->getName() == 'key') {
                return count($status->getAttribute('lessons_completed'));
            } else {
                return $status->getComment();
            }
        });

        $this->addStatusCallbackColumn('column_three', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            if ($view->getComplianceViewGroup()->getName() == 'demonstrate') {
                return '';
            }

            $whiteLightPath = $status
                ->getComplianceView()
                ->getComplianceViewGroup()
                ->getComplianceProgram()
                ->getComplianceStatusMapper()
                ->getLight(ComplianceStatus::NA_COMPLIANT);

            if($view->getComplianceViewGroup()->getName() == 'key') {
                if($alternateStatus = $status->getAttribute('alternate_status_object')) {
                    $light = $alternateStatus->getLight();
                } else {
                    $light = $whiteLightPath;
                }
            } else {
                $light = $status->getLight();
            }

            return sprintf('<img src="%s" class="light" alt="" />', $light);
        });

        $this->screeningLinkArea = '';

        $this->tableHeaders['links'] = 'Action Links';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <script type="text/javascript">
            $(function() {
                // Rework Action Links to line up eLearning links with appropriate views

                var $tc = $('td.links[rowspan="8"]').parent('tr');

                $tc.find('td.links').remove();

                var $topLinks = $tc.prev('tr').find('.links');

                var $glucose = $tc.next('tr').next('tr').next('tr').next('tr');

                var $bp = $glucose.next('tr');

                var $bmi = $bp.next('tr');

                var $tobacco = $bmi.next('tr');

                $topLinks.parent().find('.callback').remove();

                $topLinks.attr('colspan', 4);

                $topLinks.append('<a href="?preferredPrinter=ScreeningProgramReportPrinter&id=391">Click here to view results for below, after screening results are received.</a>');

                $tc.append('<td class="links" rowspan="4"><a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Review/Do 2 Blood Fat Lessons</a></td>');

                $glucose.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review/Do 2 Blood Sugar Lessons</a></td>');

                $bp.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Review / Do 2 BP Lessons</a></td>');

                $bmi.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Review/Do 2 Body Metrics Lessons</a></td>');

                $tobacco.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=tobacco">Review/Do 2 Tobacco Lessons</a></td>');

                // Remove deadline column from last group, span name col
//                $('.headerRow-demonstrate td:eq(0), .headerRow-demonstrate td:eq(2)').remove();
//                $('.view-activity_241 td:eq(1), .view-activity_241 td:eq(3)').remove();
//                $('.view-veri td:eq(1), .view-veri td:eq(3)').remove();
//                $('.view-doc td:eq(1), .view-doc td:eq(3)').remove();
//                $('.view-activity_26 td:eq(1), .view-activity_26 td:eq(3)').remove();
//                $('.view-activity_242 td:eq(1), .view-activity_242 td:eq(3)').remove();
//                $('.view-other_key_health td:eq(1), .view-other_key_health td:eq(3)').remove();
//                $('.view-with_doctor td:eq(1), .view-with_doctor td:eq(3)').remove();
//
//                $('.headerRow-demonstrate th:eq(0), .headerRow-demonstrate td:eq(0)').attr('colspan', 2);
//                $('.view-activity_241 td:eq(0), .view-activity_241 td:eq(1)').attr('colspan', 2);
//                $('.view-veri td:eq(0), .view-veri td:eq(1)').attr('colspan', 2);
//                $('.view-doc td:eq(0), .view-doc td:eq(1)').attr('colspan', 2);
//                $('.view-activity_26 td:eq(0), .view-activity_26 td:eq(1)').attr('colspan', 2);
//                $('.view-activity_242 td:eq(0), .view-activity_242 td:eq(1)').attr('colspan', 2);
//                $('.view-other_key_health td:eq(0), .view-other_key_health td:eq(1)').attr('colspan', 2);
//                $('.view-with_doctor td:eq(0), .view-with_doctor td:eq(1)').attr('colspan', 2);
            });
        </script>
        <style type="text/css">
            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .headerRow, #legendText {
                background-color:#002AAE;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .status img {
                width:25px;
            }
        </style>
        <p>Welcome to your new 2017 PHIP. As you know, all adult HIP participants must
            get certain things done throughout the year in order to receive discounts
            on your health plan monthly premiums.</p>

        <p>All current requirements (To-Dos) are listed below:</p>

        <ul>
            <li>It is your responsibility to complete all of HIP’s requirements or risk
                paying more costs in your monthly health plan premium.
            </li>
            <li>The Health Improvement Program requires you and your spouse to to learn
                and do more to improve your health and work with health professionals
                in securing the best possible health care.
            </li>
            <li>As a HIP participant, you must demonstrate the you’re doing all you can do
                to get and stay healthy and make sound, evidence-based decisions
                concerning your use of health care.
            </li>
        </ul>
        <p>Here's what the table below says:</p>
        <ul>
            <li>The current requirements and your current status for each are
                summarized below.
            </li>
            <li>In the first column, click on the text in blue to learn why the
                action is important or <a href="/content/1094new2017">click here</a> for all actions.
            </li>
            <li>Use the Action Links in the right column to get things done or for more
                information.
            </li>
            <li>Please visit this page often to check your status, get To-Dos done
                and to see if new lessons or requirements have been added.
            </li>
        </ul>
        <?php
    }
}


class AFSCME2017ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
        <a href="/compliance_programs">Back to Rewards</a>
        <br/>
        <br/>
        <?php parent::printReport($status) ?>
        <br/>
        <br/>
        <div id="screening-table-container"></div>
        <script type="text/javascript">
            $(function() {
                $('#screening-table-container').load('/content/1094new2017 #table3');
            });
        </script>
        <?php
    }
}