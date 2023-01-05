<?php

use hpn\steel\query\SelectQuery;

class CHW18FebFtLauderdaleTrinityCompleteELearningLessonComplianceView extends CompleteELearningLessonComplianceView
{
    public function allowPointsOverride()
    {
        return true;
    }
}


class CHW18FebFtLauderdaleTrinityComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $eachCourseSection = sprintf('Status of Each Course as of %s', date('m/d/Y'));
        $otherStepsSection = 'Other Course Completion Steps';
        $attendanceSection = 'Attendance';
        $earlyWeekSection = 'Quizzes & Assignments';
        $extraSection = 'Extra Credit';

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $summaryGroup = new ComplianceViewGroup('about_me', 'Summary & Other Steps to Become Certified');

        $chwOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chwOneView->setReportName('Community Health Worker 1');
        $chwOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(410, 0, 0, 0));
        $chwOneView->setName('chw_01');
        $chwOneView->setAttribute('points_required', 410);
        $chwOneView->addLink(new FakeLink('See 2 below', '#'));
        $chwOneView->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($chwOneView, false, $eachCourseSection);

        $chwPracticum = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chwPracticum->setName('chw_practicum');
        $chwPracticum->setReportName('CHW Directed Practicum');
        $chwPracticum->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(285, 0, 0, 0));
        $chwPracticum->setAttribute('points_required', 244);
        $chwPracticum->addLink(new FakeLink('See 3 below', '#'));
        $chwPracticum->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($chwPracticum, false, $eachCourseSection);

        $trainingEvaluation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $trainingEvaluation->setName('training_evaluation');
        $trainingEvaluation->setReportName('Training Evaluation --> Your Feedback on the Courses');
        $trainingEvaluation->addLink(new FakeLink('Done in Class', '#'));
        $trainingEvaluation->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($trainingEvaluation, false, $otherStepsSection);

        $cprCard = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $cprCard->setName('cpr_card');
        $cprCard->setReportName('Provide copy of current CPR card');
        $cprCard->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($cprCard, false, $otherStepsSection);

        $hipaaTraining = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hipaaTraining->setName('hipaa_training');
        $hipaaTraining->setReportName('Provide copy of HIPAA training certificate');
        $hipaaTraining->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($hipaaTraining, false, $otherStepsSection);

        $this->addComplianceViewGroup($summaryGroup);


        $courseCHWGroup = new ComplianceViewGroup('course_chw', 'Course: Community Health Worker 1');

        $week1View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $week1View->setReportName('Week 1 - see course calendar &/or instructor');
        $week1View->setName('week1');
        $week1View->addLink(new Link('Manual & Lessons', '/content/chw_resources'));
        $week1View->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($week1View, false, $attendanceSection);

        $week2View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $week2View->setReportName('Week 2 - see course calendar &/or instructor');
        $week2View->setName('week2');
        $week2View->addLink(new Link('Manual & Lessons', '/content/chw_resources'));
        $week2View->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($week2View, false, $attendanceSection);

        $healthyMeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthyMeView->setReportName('Healthy Me');
        $healthyMeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $healthyMeView->setName('healthy_me');
        $healthyMeView->addLink(new Link('Document', '/resources/10280/CHWm PSH 2b Activity1_Healthy Me 090918.pdf'));
        $healthyMeView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($healthyMeView, false, $earlyWeekSection);

        $tableTalksView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tableTalksView->setReportName('Table Talks');
        $tableTalksView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $tableTalksView->setName('table_talks');
        $tableTalksView->addLink(new Link('Document', '/resources/5993/CHW%202710%20Teaching%20Plan%20Table%20Talk.doc'));
        $tableTalksView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($tableTalksView, false, $earlyWeekSection);

        $learningView = new CompleteELearningGroupSet($programStart, $programEnd, 'chw_lifespan');
        $learningView->setReportName('Complete All Lifespan Lessons');
        $learningView->setName('elearning');
        $learningView->setNumberRequired(7);
        $learningView->setMaximumNumberOfPoints(280);
        $learningView->setAllowPointsOverride(true);
        $learningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getAttribute('lessons_completed') && count($status->getAttribute('lessons_completed')) > 0) {
                $totalPoints = count($status->getAttribute('lessons_completed')) * 40;
                $status->setPoints($totalPoints);
            }
        });
        $courseCHWGroup->addComplianceView($learningView, false, $earlyWeekSection);

        $this->addComplianceViewGroup($courseCHWGroup);


        $extraGroup = new ComplianceViewGroup('extra', 'Extra Credit');

        $registerCHWView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $registerCHWView->setReportName('Register on CHW Site');
        $registerCHWView->setName('register_chw_site');
        $registerCHWView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
//        $registerCHWView->addLink(new Link('Details & Places', '/content/1094#2dflu'));
        $registerCHWView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $accounts = $user->getUserAccounts();

            $hasAccount = false;
            foreach($accounts as $account) {
                $hasAccount = true;
            }

            if($hasAccount) {
                $status->setPoints(2);
                $status->setStatus(ComplianceViewStatus::COMPLIANT);
            }
        });
        $registerCHWView->setAllowPointsOverride(true);
        $extraGroup->addComplianceView($registerCHWView, false, $extraSection);

        $updateInfo = new UpdateContactInformationComplianceView($programStart, $programEnd);
        $updateInfo->setReportName('Confirm/Update Contact Info');
        $updateInfo->setName('update_info');
        $updateInfo->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $extraGroup->addComplianceView($updateInfo, false, $extraSection);

        $wheelLifeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wheelLifeView->setReportName('Complete the Wheel of Life (can help with insights)');
        $wheelLifeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $wheelLifeView->setName('complete_wheel');
        $wheelLifeView->addLink(new Link('More Info', '/content/13047'));
        $wheelLifeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('wol');
            if($record->exists()) $status->setStatus(ComplianceViewStatus::COMPLIANT);
        });
        $wheelLifeView->setAllowPointsOverride(true);
        $extraGroup->addComplianceView($wheelLifeView, false, $extraSection);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setReportName('Complete the HPA (can help with goals)');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $extraGroup->addComplianceView($hraView, false, $extraSection);

        $this->addComplianceViewGroup($extraGroup);


        $courseCHWPracticumGroup = new ComplianceViewGroup('course_chw_practicum', 'Module 3 - CHW Directed Practicum');

        $hoursAchievedView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hoursAchievedView->setReportName('Required Hours Achieved');
        $hoursAchievedView->setName('hours_achieved');
        $hoursAchievedView->addLink(new Link('Time Task Log', '/resources/10281/CHWm supp PSH 02 TIME TASK LOG 030517 for 2019.pdf'));
        $hoursAchievedView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($hoursAchievedView);

        $reportOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportOneView->setReportName('Report 1');
        $reportOneView->setName('report_one');
        $reportOneView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $reportOneView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportOneView);

        $reportTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $reportTwoView->setReportName('Report 2');
        $reportTwoView->setName('report_two');
        $reportTwoView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportTwoView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportTwoView);

        $reportThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $reportThreeView->setReportName('Report 3');
        $reportThreeView->setName('report_three');
        $reportThreeView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportThreeView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportThreeView);

        $reportFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $reportFourView->setReportName('Report 4');
        $reportFourView->setName('report_four');
        $reportFourView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportFourView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportFourView);


        $selfEvaluationFinalView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $selfEvaluationFinalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $selfEvaluationFinalView->setReportName('Self Evaluation - Final');
        $selfEvaluationFinalView->setName('self_evaluation_final');
        $selfEvaluationFinalView->addLink(new Link('Document', '/resources/6011/CHW%202750%20Practicum%20Student%20Self-Evaluation.pdf'));
        $selfEvaluationFinalView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($selfEvaluationFinalView);

        $siteEvaluationView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $siteEvaluationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(65, 0, 0, 0));
        $siteEvaluationView->setReportName('Site Supervisor Evaluation - Mid-Term & Final');
        $siteEvaluationView->setName('site_evaluation');
        $siteEvaluationView->addLink(new Link('Document', '/resources/6008/CHW%202750%20Practicum%20Site%20Supervisor%20Evaluation%20of%20Student.pdf'));
        $siteEvaluationView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($siteEvaluationView);

        $instructorEvaluationView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $instructorEvaluationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $instructorEvaluationView->setReportName('Instructor Site Visit & Evaluation');
        $instructorEvaluationView->setName('instructor_evaluation');
        $instructorEvaluationView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($instructorEvaluationView);

        $this->addComplianceViewGroup($courseCHWPracticumGroup);



    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $fixStatus = function(ComplianceViewStatus $status) {
            if ($status->getPoints() >= $status->getComplianceView()->getAttribute('points_required')) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else if ($status->getPoints() > 0) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }
        };

        $status->getComplianceViewStatus('chw_01')->setPoints(
            $status->getComplianceViewGroupStatus('course_chw')->getPoints() + $status->getComplianceViewGroupStatus('extra')->getPoints()
        );


        $status->getComplianceViewStatus('chw_practicum')->setPoints(
            $status->getComplianceViewGroupStatus('course_chw_practicum')->getPoints()
        );

        $fixStatus($status->getComplianceViewStatus('chw_01'));
        $fixStatus($status->getComplianceViewStatus('chw_practicum'));
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new CHW18FebFtLauderdaleTrinityComplianceProgramReportPrinter();
        $printer->showCompleted = false;

        return $printer;
    }

}


class CHW18FebFtLauderdaleTrinityComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function __construct()
    {
        $this->screeningLinkArea = '<br/><br/>
          Green Range = 10 pts <br/>
          Yellow Range = 5 pts<br/>
          Red Range = 0 pts *<br/>
        ';

        $this->addStatusCallbackColumn('Date Confirmed', function(ComplianceViewStatus $status) {
            return $status->getComment();
        });

        $this->addStatusCallbackColumn('Points Possible', function(ComplianceViewStatus $status) {
            return $status->getComplianceView()->getMaximumNumberOfPoints();
        });

        $this->addStatusCallbackColumn('Points Earned', function(ComplianceViewStatus $status) {
            return $status->getPoints();
        });
    }

    protected function showGroup($group)
    {
        return $group->getName() != 'evaluators';
    }


    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(true);
        $courseCHWStatus = $status->getComplianceViewGroupStatus('course_chw');
        $courseCHWPracticumStatus = $status->getComplianceViewGroupStatus('course_chw_practicum');
        $extraGroupStatus = $status->getComplianceViewGroupStatus('extra');


        $user = $status->getUser();

        ?>
        <script type="text/javascript">
            $(function(){
                $('.book-journey-across-the-lifespan').removeClass('book-journey-across-the-lifespan').addClass('book-journey');
                $('.viewSectionRow th').attr('colspan', '6');
                $('.headerRow').not('.viewSectionRow').css('background-color', '#005CE6');
                $('.headerRow').not('.viewSectionRow').css('height', '50px');

                $('.view-chapter_three .links').html('<a href="/resources/7118/Module%201%20Book%20Bridges%20Out%20of%20Poverty%20Study%20Guide%20Qs%20021016.doc">Details &amp; Do</a>');
                $('.view-chapter_three .links').attr('rowspan', '4');
                $('.view-chapter_four .links').remove();
                $('.view-chapter_eight .links').remove();
                $('.view-chapter_eleven .links').remove();

                $('.view-question_chapter_two .links').html('<a target="_self" href="/resources/7127/Module%202%20Journeys%20textbook%20questions%20021016.pdf">Document</a>');
                $('.view-question_chapter_two .links').attr('rowspan', '4');
                $('.view-question_chapter_five .links').remove();
                $('.view-question_chapter_seven .links').remove();
                $('.view-question_chapter_eight .links').remove();

                $('.view-question_chapter_eleven .links').html('<a target="_self" href="/resources/7127/Module%202%20Journeys%20textbook%20questions%20021016.pdf">Document</a>');
                $('.view-question_chapter_eleven .links').attr('rowspan', '3');
                $('.view-question_chapter_twelve .links').remove();
                $('.view-question_chapter_fourteen .links').remove();

                $('.view-report_one .links').html('<a target="_self" href="/resources/10282/CHWt Weekly Practicum Reports 012519.pdf">Document</a>');
                $('.view-report_one .links').attr('rowspan', '4');
                $('.view-report_two .links').remove();
                $('.view-report_three .links').remove();
                $('.view-report_four .links').remove();

                $('.view-elearning').after('<tr style="text-align: center;"><td>Total & Status as of <?php echo date('m/d/Y') ?> = </td><td></td><td><?php echo $courseCHWStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $courseCHWStatus->getPoints() ?></td><td colspan="2"></td></tr>');


                $('.view-register_chw_site').children(':eq(0)').html('<strong>K</strong>. Register on CHW Site');
                $('.view-update_info').children(':eq(0)').html('<strong>L</strong>. Confirm/Update Contact Info');
                $('.view-complete_wheel').children(':eq(0)').html('<strong>M</strong>. Complete the Wheel of Life (can help with insights)');
                $('.view-complete_hra').children(':eq(0)').html('<strong>N</strong>. Complete the HPA (can help with goals)');
                $('.view-complete_hra').after('<tr style="text-align: center;"><td></td><td></td><td><?php echo $extraGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $extraGroupStatus->getPoints() ?></td><td colspan="2"></td></tr>');

                $('.headerRow-extra').hide();

                $('.view-instructor_evaluation').after('<tr style="text-align: center;"><td>Total & Status as of <?php echo date('m/d/Y') ?> = </td><td></td><td><?php echo $courseCHWPracticumStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $courseCHWPracticumStatus->getPoints() ?></td><td colspan="2"></td></tr>');
                $('.phipTable').children(':eq(2)').hide();

                $('.headerRow-course_chw_practicum').children(':eq(0)').html('<strong>3</strong>. Module 3 - CHW Directed Practicum');
            });
        </script>
        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
                margin-bottom: 20px;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }
        </style>

        <div id="altPageHeading">Community Health Workers Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>


        <div class="pageHeading">
            <a href="/content/1094">
                Click here to view the full details of all Reward Activities listed below
            </a>.
        </div>


        <?php
    }


    public $showUserNameInLegend = true;
}
