<?php

/**
 * Contains all classes for AFSCME 2016 compliance program. Depends on classes
 * in the 2013 program that are unchanged for 2016.
 */
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/administration/userCenterLib.php', sfConfig::get('sf_root_dir'));

class AFSCME2016TobaccoFree extends ComplianceView
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

class AFSCME2016AssignedElearningComplianceView extends CompleteAssignedELearningLessonsComplianceView
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

class AFSCME2016HealthCoachingEvaluationComplianceView extends PlaceHolderComplianceView
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




class AFSCME2016ComplianceProgram extends ComplianceProgram
{
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
            $printer = new AFSCME2016ScreeningPrinter();
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
            $printer = new AFSCME2016Printer();
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

        $hraScreeningEndDate = strtotime('2016-04-01');

        $keyEndDate = strtotime('2016-10-15');

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($startDate, $hraScreeningEndDate);
        $nonSmokerView->setUseDateForComment(true);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Non-User of Tobacco');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');

        $start = new ComplianceViewGroup('start', 'Starting core actions required by deadline below:');

        $hpa = new CompleteHRAComplianceView($startDate, $hraScreeningEndDate);
        $hpa->setReportName('Complete Health Power Assessment');
        $hpa->setAttribute('report_name_link', '/content/1094new2016#1ahpa');
        $start->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView('2015-11-01', $hraScreeningEndDate);
        $scr->setReportName('Complete Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094new2016#1bscreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Sign-Up', '/content/5199'));
        $scr->addLink(new Link('Results', '/content/989'));
        $start->addComplianceView($scr);

        $doc = new UpdateDoctorInformationComplianceView($startDate, '2016-06-10');
        $doc->setReportName('Verify having a main doctor/primary care provider');
        $doc->setAttribute('report_name_link', '/content/1094new2016#1cmaindoc');
        $start->addComplianceView($doc);

        $updateInfo = new UpdateContactInformationComplianceView($startDate, '2016-06-10');
        $updateInfo->setReportName('Verify/Update my current contact information – email, address, phone');
        $updateInfo->setAttribute('report_name_link', '/content/1094new2016#1dpers');
        $start->addComplianceView($updateInfo);

        $workbook = new AFSCMEViewWorkbookComplianceView($startDate, '2016-06-10');
        $workbook->setReportName('View and Use Your Health Navigator Workbook');
        $workbook->setAttribute('report_name_link', '/content/1094new2016#1ehealthnav');
        $start->addComplianceView($workbook);

        $elearn = new CompleteRequiredELearningLessonsComplianceView('2015-11-01', '2016-09-20');
        $elearn->setReportName('Complete all mandatory e-learning lessons');
        $elearn->setAttribute('report_name_link', '/content/1094new2016#1felearn');
//        $elearn->emptyLinks();
//        $elearn->addLink(new Link('View/Do Lessons', '/content/elearning_middle_page'));
        $start->addComplianceView($elearn);

        $tobView = new AFSCME2016TobaccoFree($nonSmokerView);
        $tobView->setName('tob_view');
        $tobView->setReportName('Do Not Use Any Tobacco or Complete Cessation Program');
        $tobView->setAttribute('report_name_link', '/content/1094new2016#1gtobacco');
        $tobView->setAttribute('deadline', '09/01/2016');
        $tobView->emptyLinks();
        $tobView->addLink(new Link('Learn More', '/content/1094new2016#1gtobacco'));
        $start->addComplianceView($tobView);

        $this->addComplianceViewGroup($start);

        $ongoing = new ComplianceViewGroup('ongoing', 'Ongoing core actions all year');

        $calls = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $calls->setReportName('Make required calls to the Care Counselor BEFORE receiving certain types of health care and other times.  If a counselor calls you, return the call AND work with him/her until you are told you are finished.');
        $calls->setAttribute('report_name_link', '/content/1094new2016#2acounsel');
        $calls->setName('calls_1');
        $calls->setAttribute('deadline', 'Within 5 days of being called each time');
        $calls->addLink(new Link('Learn More', '/content/5317 '));
        $ongoing->addComplianceView($calls);

        $callsTwo = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);
        $callsTwo->setRequireTargeted(true);
        $callsTwo->setUseTargeted(true);
        $callsTwo->setReportName('When Red, schedule a time for a call from a health coach and work with them.  When Yellow, keep working with your health coach at scheduled times until told you are done.');
        $callsTwo->setAttribute('report_name_link', '/content/1094new2016#2bcoach');
        $callsTwo->setName('calls_2');
        $callsTwo->setAttribute('deadline', 'With 5 days of being notified.');
        $callsTwo->addLink(new Link('Learn More', '/content/1094new2016#2bcoach'));
        $ongoing->addComplianceView($callsTwo);

        $callsThree = new AFSCME2016AssignedElearningComplianceView($startDate, $keyEndDate);
        $callsThree->setReportName('Complete extra e-Learning lessons and decision tools recommended by Health Coach or Nurse.');
        $callsThree->setAttribute('report_name_link', '/content/1094new2016#2ccoacheLearn');
        $callsThree->setName('calls_3');
        $callsThree->setAttribute('deadline', 'Within 30 days of recommendation');
        current($callsThree->getLinks())->setLinkText('View/Do Lessons');
        $ongoing->addComplianceView($callsThree);

        $healthCoachEvaluation = new AFSCME2016HealthCoachingEvaluationComplianceView(ComplianceStatus::NA_COMPLIANT);
        $healthCoachEvaluation->setName('health_coach_evaluation');
        $healthCoachEvaluation->setReportName('Complete Coaching Evaluation After Coaching is Completed');
        $healthCoachEvaluation->setAttribute('report_name_link', '/content/1094new2016#2dcoachEval');
        $healthCoachEvaluation->setAttribute('deadline', 'Within 30 days after 2B is done');
        $healthCoachEvaluation->addLink(new Link('Start/Finish Survey', '/surveys'));
        $healthCoachEvaluation->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($startDate, $endDate) {
            $callsTwoStatus = $status->getComplianceView()->getComplianceViewGroup()->getComplianceView('calls_2');

            if($callsTwoStatus->getAttribute('completed_date')) {
                $endDate = strtotime('+30 days', strtotime($callsTwoStatus->getAttribute('completed_date')));
            }

            $surveyCompletions = SurveyCompletionTable::getInstance()
                ->findCompletionsForUser($user);

            foreach($surveyCompletions as $surveyCompletion) {
                if($surveyCompletion->completed_at < date('Y-m-d', $startDate) || $surveyCompletion->completed_at > date('Y-m-d', $endDate)) {
                    continue;
                }

                if($surveyCompletion->getSurvey()->getName() == 'Health Coaching Evaluation') {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });
        $ongoing->addComplianceView($healthCoachEvaluation);

        $this->addComplianceViewGroup($ongoing);


        $elearningAlternativeCallback = function(ComplianceViewStatus $status, ComplianceViewStatus $altStatus) {
            $status->setAttribute('lessons_completed', $altStatus->getAttribute('lessons_completed', array()));
            $status->setAttribute('alternate_status_object', $altStatus);

            $status->setStatus($status->getAttribute('original_status'));
            $status->setPoints($status->getAttribute('original_points'));
            $status->setComment($status->getAttribute('original_comment'));

            if($status->isCompliant()) {
                $altStatus->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        };

        $key = new ComplianceViewGroup('key', 'Key measures of health');

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setUseDateForComment(true);
        $totalCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $totalCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'cholesterol'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $hdlCholesterolView->setUseDateForComment(true);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $hdlCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'cholesterol'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $ldlCholesterolView->setUseDateForComment(true);
        $ldlCholesterolView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'cholesterol'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $trigView->setUseDateForComment(true);
        $trigView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'cholesterol'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $glucoseView->setUseDateForComment(true);
        $glucoseView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'blood_sugars'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $bloodPressureView->setUseDateForComment(true);
        $bloodPressureView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'blood_pressure'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $hraScreeningEndDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setUseDateForComment(true);
        $bodyFatBMIView->setNoScreeningResultStatus(ComplianceStatus::NA_COMPLIANT);
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $bodyFatBMIView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'body_fat'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($bodyFatBMIView);

        $nonSmokerView->setAlternativeComplianceView($this->getAlternateElearningView($key, $startDate, $keyEndDate, 'tobacco'), false, $elearningAlternativeCallback, true);
        $key->addComplianceView($nonSmokerView);

        $this->addComplianceViewGroup($key);

        $keyMeasureCallback = array($this, 'keyMeasuresCompletedSoNotRequired');

        $dem = new ComplianceViewGroup('demonstrate', 'Tools to help you achieve your health and wellbeing goals.');
        $dem->setNumberOfViewsRequired(4);

        $phy = new PhysicalActivityComplianceView($startDate, $endDate);
        $phy->_setID(241);
        $phy->setReportName('Exercise Tracker');
        $phy->setAttribute('report_name_link', '/content/1094new2016#4aphys');
        $phy->setFractionalDivisorForPoints(1);
        $phy->setMinutesDivisorForPoints(1);
        $phy->setCompliancePointStatusMapper(new CompliancePointStatusMapper(60 * 150, 1, 0, 0));
        $phy->emptyLinks();
        $phy->addLink(new Link('Click here', '/content/12048?action=showActivity&activityidentifier=241'));
        $dem->addComplianceView($phy);

        $prev = new CompletePreventiveExamWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $prev->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $prev->setMaximumNumberOfPoints(20);
        $prev->setReportName('Preventive Screenings Tracker');
        $prev->setAttribute('report_name_link', '/content/1094new2016#4dprevScreen');
        $prev->emptyLinks();
        $prev->addLink(new Link('Click here', '/content/12048?action=showActivity&activityidentifier=26'));
        $dem->addComplianceView($prev);

        $imm = new CompleteImmunizationsWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $imm->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $imm->setMaximumNumberOfPoints(20);
        $imm->setReportName('Record Immunizations');
        $imm->setAttribute('report_name_link', '/content/1094new2016#4eimmun');
        $imm->emptyLinks();
        $imm->addLink(new Link('Click here', '/content/12048?action=showActivity&activityidentifier=242'));
        $dem->addComplianceView($imm);

        $otherRes = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $otherRes->setReportName('Other Resources & Benefits');
        $otherRes->setAttribute('report_name_link', '/content/1094new2016#4bactions');
        $otherRes->setName('other_resources');
        $otherRes->addLink(new Link('Click here', '/content/1094new2016#4bactions'));
        $dem->addComplianceView($otherRes);

        $this->addComplianceViewGroup($dem);
    }

    public function keyMeasuresCompletedSoNotRequired(User $user)
    {
        $group = $this->getComplianceViewGroup('key');

        return !$group->getStatusForUser($user)->isCompliant();
    }

    private function getAlternateElearningView($group, $startDate, $endDate, $alias)
    {
        $view = new CompleteELearningGroupSet($startDate, $endDate, $alias);

        $view->useAlternateCode(true);

        $view->setNumberRequired(2);

        $view->setComplianceViewGroup($group);

        return $view;
    }
}

class AFSCME2016Printer extends BasicComplianceProgramReportPrinter
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

                break;
        }

        return true;
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have these measures in the healthy green zone or complete 2 related e-learning lessons to help improve each that is not green. This section will be completely red until the results of your screening/HPA are in the system.', '/content/1094new2016#3abio'));

        $this->pageHeading = 'Personal Health Improvement Prescription / To-Do Summary Page';

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

                $topLinks.append('<a href="?preferredPrinter=ScreeningProgramReportPrinter&id=391">Click here to view results for below, after screening results are received.</a>');

                $tc.append('<td class="links" rowspan="4"><a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Review/Do 2 Blood Fat Lessons</a></td>');

                $glucose.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review/Do 2 Blood Sugar Lessons</a></td>');

                $bp.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Review / Do 2 BP Lessons</a></td>');

                $bmi.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Review/Do 2 Body Metrics Lessons</a></td>');

                $tobacco.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=tobacco">Review/Do 2 Tobacco Lessons</a></td>');

                // Remove deadline column from last group, span name col
                $('.headerRow-demonstrate td:eq(0), .headerRow-demonstrate td:eq(2)').remove();
                $('.view-activity_241 td:eq(1), .view-activity_241 td:eq(3)').remove();
                $('.view-veri td:eq(1), .view-veri td:eq(3)').remove();
                $('.view-doc td:eq(1), .view-doc td:eq(3)').remove();
                $('.view-activity_26 td:eq(1), .view-activity_26 td:eq(3)').remove();
                $('.view-activity_242 td:eq(1), .view-activity_242 td:eq(3)').remove();
                $('.view-other_resources td:eq(1), .view-other_resources td:eq(3)').remove();

                $('.headerRow-demonstrate th:eq(0), .headerRow-demonstrate td:eq(0)').attr('colspan', 2);
                $('.view-activity_241 td:eq(0), .view-activity_241 td:eq(1)').attr('colspan', 2);
                $('.view-veri td:eq(0), .view-veri td:eq(1)').attr('colspan', 2);
                $('.view-doc td:eq(0), .view-doc td:eq(1)').attr('colspan', 2);
                $('.view-activity_26 td:eq(0), .view-activity_26 td:eq(1)').attr('colspan', 2);
                $('.view-activity_242 td:eq(0), .view-activity_242 td:eq(1)').attr('colspan', 2);
                $('.view-other_resources td:eq(0), .view-other_resources td:eq(1)').attr('colspan', 2);
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
        <p>Welcome to your new 2016 PHIP. As you know, all adult HIP participants must
            get certain things done throughout the year in order to maintain eligibility
            for the Health Improvement Plan benefit.</p>

        <p>All current requirements (To-Dos) are listed below:</p>

        <ul>
            <li>It is your responsibility to complete all of HIP’s requirements or risk
                disenrollment from HIP and transfer to the Standard Plan, which requires
                you to pay more of your health care costs.
            </li>
            <li>The enrollment agreement you signed with HIP obligates you to learn
                and do more to improve your health and work with health professionals
                to secure good quality, evidence-based health care.
            </li>
            <li>As a HIP member, you must demonstrate the you’re doing all you can do
                to become and stay healthy and make sound, evidence-based decisions
                concerning your use of health care.
            </li>
        </ul>
        <p>Here's what the table below says:</p>
        <ul>
            <li>The current requirements and your current status for each are
                summarized below.
            </li>
            <li>In the first column, click on the text in blue to learn why the
                action is important <a href="/content/1094new2016">or click here for all actions</a>.
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


class AFSCME2016ScreeningPrinter extends ScreeningProgramReportPrinter
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
                $('#screening-table-container').load('/content/1094new2016 #table3');
            });
        </script>
    <?php
    }
}