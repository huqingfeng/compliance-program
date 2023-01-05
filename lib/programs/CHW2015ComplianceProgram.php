<?php

use hpn\steel\query\SelectQuery;

class CHW2015CompleteELearningLessonComplianceView extends CompleteELearningLessonComplianceView
{
    public function allowPointsOverride()
    {
        return true;
    }
}


class CHW2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $eachCourseSection = sprintf('Status of Each Course as of %s', date('m/d/Y'));
        $otherStepsSection = 'Other Steps After Course Completion';
        $attendanceSection = 'Attendance';
        $earlyWeekSection = 'Early Weeks - Knowledge & Skills';
        $bookBridgeSection = 'Book: Bridges Out of Poverty';
        $lastWeekSection = 'Last Weeks - Knowledge & Skills';
        $extraCreditSection = 'Extra Credit';
        $bookJourneySection = 'Book: Journey Across the Lifespan';


        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $summaryGroup = new ComplianceViewGroup('about_me', 'Summary & Other Steps to Become Certified');

        $chwOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chwOneView->setReportName('Community Health Worker 1');
        $chwOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(320, 0, 0, 0));
        $chwOneView->setName('chw_01');
        $chwOneView->addLink(new FakeLink('See 2 below', '#'));
        $chwOneView->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($chwOneView, false, $eachCourseSection);

        $healthCare = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthCare->setName('health_care');
        $healthCare->setReportName('Health Care Across the Lifespan (or Eligible Option)');
        $healthCare->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(490, 0, 0, 0));
        $healthCare->addLink(new FakeLink('See 3 below', '#'));
        $healthCare->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($healthCare, false, $eachCourseSection);

        $chwPracticum = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chwPracticum->setName('chw_practicum');
        $chwPracticum->setReportName('CHW Directed Practicum');
        $chwPracticum->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(415, 0, 0, 0));
        $chwPracticum->addLink(new FakeLink('See 4 below', '#'));
        $chwPracticum->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($chwPracticum, false, $eachCourseSection);

        $trainingEvaluation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $trainingEvaluation->setName('training_evaluation');
        $trainingEvaluation->setReportName('Training Evaluation --> Your Feedback on the Courses');
        $trainingEvaluation->addLink(new FakeLink('Not Available Yet', '#'));
        $trainingEvaluation->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($trainingEvaluation, false, $otherStepsSection);

        $certificateForm = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $certificateForm->setName('certificate_form');
        $certificateForm->setReportName('Certification Forms Completed & Received for Sending to State');
        $certificateForm->addLink(new FakeLink('Not Available Yet', '#'));
        $certificateForm->setAllowPointsOverride(true);
        $summaryGroup->addComplianceView($certificateForm, false, $otherStepsSection);

        $this->addComplianceViewGroup($summaryGroup);


        $courseCHWGroup = new ComplianceViewGroup('course_chw', 'Course 2710:49 - Community Health Worker 1');

        $week1View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $week1View->setReportName('Week 1 M-TH - Sept 14-17');
        $week1View->setName('week1');
        $week1View->addLink(new Link('Manual & Lessons', '/content/chw_resources'));
        $week1View->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($week1View, false, $attendanceSection);

        $week2View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $week2View->setReportName('Week 2 M-TH - Oct 5-8');
        $week2View->setName('week2');
        $week2View->addLink(new Link('Manual & Lessons', '/content/chw_resources'));
        $week2View->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($week2View, false, $attendanceSection);

        $week3View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $week3View->setReportName('Week 3 TH-F - Dec 3-4');
        $week3View->setName('week3');
        $week3View->addLink(new Link('Manual & Lessons', '/content/chw_resources'));
        $week3View->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($week3View, false, $attendanceSection);

        $quizLawsView = new CHW2015CompleteELearningLessonComplianceView($programStart, $programEnd, new ELearningLesson_v2(1365));
        $quizLawsView->setReportName('Quiz - Laws, Regs & Ethics (CHW Profession & State)');
        $quizLawsView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $quizLawsView->setName('quiz_law');
        $courseCHWGroup->addComplianceView($quizLawsView, false, $earlyWeekSection);


        $quizVitalView = new CHW2015CompleteELearningLessonComplianceView($programStart, $programEnd, new ELearningLesson_v2(1406));
        $quizVitalView->setReportName('Quiz - Vital Signs');
        $quizVitalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $quizVitalView->setName('quiz_vital');
        $courseCHWGroup->addComplianceView($quizVitalView, false, $earlyWeekSection);

        $vitalSignView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $vitalSignView->setReportName('Vital Signs Skill Check');
        $vitalSignView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $vitalSignView->setName('vital_sign');
        $vitalSignView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($vitalSignView, false, $earlyWeekSection);

        $activityOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $activityOneView->setReportName('Activity 1 - Definition, Helpful Roles & Traits of a CHW');
        $activityOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $activityOneView->setName('activity_one');
        $activityOneView->addLink(new Link('Document', '/resources/7739/CHWt_06_IRC_2G_Activity_1_Healthy_Me_050916.pdf'));
        $activityOneView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($activityOneView, false, $earlyWeekSection);

        $chapterThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chapterThreeView->setReportName('Questions Due - Intro to Chapter 3');
        $chapterThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(22, 0, 0, 0));
        $chapterThreeView->setName('chapter_three');
        $chapterThreeView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $chapterThreeView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($chapterThreeView, false, $bookBridgeSection);

        $chapterFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chapterFourView->setReportName('Questions Due - Chapters 4-7');
        $chapterFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(26, 0, 0, 0));
        $chapterFourView->setName('chapter_four');
        $chapterFourView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $chapterFourView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($chapterFourView, false, $bookBridgeSection);

        $chapterEightView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chapterEightView->setReportName('Questions Due - Chapters 8-10');
        $chapterEightView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(26, 0, 0, 0));
        $chapterEightView->setName('chapter_eight');
        $chapterEightView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $chapterEightView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($chapterEightView, false, $bookBridgeSection);

        $chapterElevenView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $chapterElevenView->setReportName('Questions Due - Chapters 11-12');
        $chapterElevenView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(26, 0, 0, 0));
        $chapterElevenView->setName('chapter_eleven');
        $chapterElevenView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $chapterElevenView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($chapterElevenView, false, $bookBridgeSection);

        $tableTalksView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tableTalksView->setReportName('Table Talks');
        $tableTalksView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $tableTalksView->setName('table_talks');
        $tableTalksView->addLink(new Link('Document', '/resources/5993/CHW%202710%20Teaching%20Plan%20Table%20Talk.doc'));
        $tableTalksView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($tableTalksView, false, $lastWeekSection);

        $crpCardView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $crpCardView->setReportName('Copy of Current CPR Card');
        $crpCardView->setName('crp_card');
        $crpCardView->setAllowPointsOverride(true);
        $courseCHWGroup->addComplianceView($crpCardView, false, $lastWeekSection);

        $this->addComplianceViewGroup($courseCHWGroup);

        $courseHealthCareGroup = new ComplianceViewGroup('course_health_care', 'Course 2730:925 - Health Care Across the Lifespan');

        $questionChapterTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterTwoView->setReportName('Questions 1-2 Due for Each - Chapters 2 & 3');
        $questionChapterTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $questionChapterTwoView->setName('question_chapter_two');
        $questionChapterTwoView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterTwoView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterTwoView, false, $bookJourneySection);

        $questionChapterFiveView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterFiveView->setReportName('Questions 1-3 Due for - Chapter 5');
        $questionChapterFiveView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $questionChapterFiveView->setName('question_chapter_five');
        $questionChapterFiveView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterFiveView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterFiveView, false, $bookJourneySection);

        $questionChapterSevenView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterSevenView->setReportName('Questions 1-3 Due for - Chapter 7');
        $questionChapterSevenView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $questionChapterSevenView->setName('question_chapter_seven');
        $questionChapterSevenView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterSevenView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterSevenView, false, $bookJourneySection);

        $questionChapterEightView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterEightView->setReportName('Questions 1-3 Due for Each - Chapters 8 & 9');
        $questionChapterEightView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $questionChapterEightView->setName('question_chapter_eight');
        $questionChapterEightView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterEightView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterEightView, false, $bookJourneySection);

        $calculateSurveyPoints = function(\User $user, $surveyId) {
            static $last = array('user_id' => null, 'data' => null);

            if ($last['user_id'] != $user->id) {
                $last = array(
                    'user_id' => $user->id,
                    'data' => SurveyCompletionTable::getInstance()->findCompletionsForUser($user)
                );
            }

            foreach($last['data'] as $completion) {
                /**
                 * @var SurveyCompletion $completion
                 */

                if ($completion->survey_id == $surveyId && $completion->complete) {
                    return $completion->getScorePercentage();
                }
            }

            return null;
        };

        $midtermView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $midtermView->setReportName('Mid-Term Exam - Chapters 2, 3, 5-9');
        $midtermView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $midtermView->setName('midterm_exam');
        $midtermView->addLink(new Link('Details & Do', '/content/midtermExam'));
        $midtermView->setAllowPointsOverride(true);

        $midtermView->setPostEvaluateCallback(function($status, $user) use ($calculateSurveyPoints) {
            $points = $calculateSurveyPoints($user, 17);

            $status->setPoints($points ? $points : 0);
            $status->setStatus($points === null ? ComplianceStatus::NOT_COMPLIANT : ComplianceStatus::COMPLIANT);
        });
        $courseHealthCareGroup->addComplianceView($midtermView, false, $bookJourneySection);

        $questionChapterElevenView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterElevenView->setReportName('Questions 1-5 Due for Chapter 10 & Qs 1-3 Due for Chapter 11');
        $questionChapterElevenView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(40, 0, 0, 0));
        $questionChapterElevenView->setName('question_chapter_eleven');
        $questionChapterElevenView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterElevenView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterElevenView, false, $bookJourneySection);

        $questionChapterTwelveView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterTwelveView->setReportName('Questions 1-3 Due for Each - Chapters 12 & 13');
        $questionChapterTwelveView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $questionChapterTwelveView->setName('question_chapter_twelve');
        $questionChapterTwelveView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterTwelveView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterTwelveView, false, $bookJourneySection);

        $questionChapterFourteenView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $questionChapterFourteenView->setReportName('Questions 1-2 Due for - Chapter 14');
        $questionChapterFourteenView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $questionChapterFourteenView->setName('question_chapter_fourteen');
        $questionChapterFourteenView->addLink(new Link('Document', '/content/1094#2dflu'));
        $questionChapterFourteenView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($questionChapterFourteenView, false, $bookJourneySection);

        $finalExamView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $finalExamView->setReportName('Final Exam - Chapters 10-14');
        $finalExamView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $finalExamView->setName('final_exam');
        $finalExamView->addLink(new link('Take Final', '/surveys/20'));
        $finalExamView->setAllowPointsOverride(true);
        $finalExamView->setPostEvaluateCallback(function($status, $user) use ($calculateSurveyPoints) {
            $points = $calculateSurveyPoints($user, 20);

            $status->setPoints($points ? $points : 0);
            $status->setStatus($points === null ? ComplianceStatus::NOT_COMPLIANT : ComplianceStatus::COMPLIANT);
        });
        $courseHealthCareGroup->addComplianceView($finalExamView, false, $bookJourneySection);

        $investigationView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $investigationView->setReportName('Investigation - Lifespan');
        $investigationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $investigationView->setName('investigation');
        $investigationView->addLink(new Link('Document', '/content/resources/5999/CHW%202730%20Book%20Life%20Span%20Investgation%20Paper.doc'));
        $investigationView->setAllowPointsOverride(true);
        $courseHealthCareGroup->addComplianceView($investigationView, false, $bookJourneySection);

        $this->addComplianceViewGroup($courseHealthCareGroup);


        $courseCHWPracticumGroup = new ComplianceViewGroup('course_chw_practicum', 'Course 2750:907 - CHW Directed Practicum');

        $hoursAchievedView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hoursAchievedView->setReportName('Required Hours Achieved');
        $hoursAchievedView->setName('hours_achieved');
        $hoursAchievedView->addLink(new Link('Details', '/resources/6026/CHWa%202750%20MASTER%20SYLLABUS%20081915.pdf'));
        $hoursAchievedView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($hoursAchievedView);

        $reportOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportOneView->setReportName('Report 1: Parts A & B Due');
        $reportOneView->setName('report_one');
        $reportOneView->addLink(new Link('Details & Do', '/content/1094#2dflu'));
        $reportOneView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportOneView);

        $reportTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $reportTwoView->setReportName('Report 2: Parts A & B Due');
        $reportTwoView->setName('report_two');
        $reportTwoView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportTwoView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportTwoView);

        $reportThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $reportThreeView->setReportName('Report 3: Parts A & B Due');
        $reportThreeView->setName('report_three');
        $reportThreeView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportThreeView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportThreeView);

        $reportFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $reportFourView->setReportName('Report 4: Parts A & B Due');
        $reportFourView->setName('report_four');
        $reportFourView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportFourView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportFourView);

        $reportFiveView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportFiveView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportFiveView->setReportName('Report 5: Parts A & B Due');
        $reportFiveView->setName('report_five');
        $reportFiveView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportFiveView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportFiveView);

        $reportSixView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $reportSixView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $reportSixView->setReportName('Report 6: Parts A & B Due');
        $reportSixView->setName('report_six');
        $reportSixView->addLink(new Link('Document', '/content/1094#2dflu'));
        $reportSixView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($reportSixView);

        $selfEvaluationMidtermView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $selfEvaluationMidtermView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $selfEvaluationMidtermView->setReportName('Self Evaluation - Mid Term');
        $selfEvaluationMidtermView->setName('self_evaluation_midterm');
        $selfEvaluationMidtermView->addLink(new Link('Document', '/resources/6011/CHW%202750%20Practicum%20Student%20Self-Evaluation.pdf'));
        $selfEvaluationMidtermView->setAllowPointsOverride(true);
        $courseCHWPracticumGroup->addComplianceView($selfEvaluationMidtermView);

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


        $extraGroup = new ComplianceViewGroup('extra', 'Extra Credit');

        $registerCHWView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $registerCHWView->setReportName('Register on CHW Site');
        $registerCHWView->setName('register_chw_site');
        $registerCHWView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $registerCHWView->addLink(new Link('Details & Places', '/content/1094#2dflu'));
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
        $extraGroup->addComplianceView($registerCHWView);

        $updateInfo = new UpdateContactInformationComplianceView($programStart, $programEnd);
        $updateInfo->setReportName('Confirm/Update Contact Info');
        $updateInfo->setName('update_info');
        $updateInfo->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $extraGroup->addComplianceView($updateInfo);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setReportName('Complete Health Power Assessment - can also use for D goals');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $extraGroup->addComplianceView($hraView);

        $wheelLifeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wheelLifeView->setReportName('Complete Wheel of Life - can add meaning to C insights');
        $wheelLifeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $wheelLifeView->setName('complete_wheel');
        $wheelLifeView->addLink(new Link('More Info', '/content/1094#2dflu'));
        $wheelLifeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('wol');
            if($record->exists()) $status->setStatus(ComplianceViewStatus::COMPLIANT);
        });
        $wheelLifeView->setAllowPointsOverride(true);
        $extraGroup->addComplianceView($wheelLifeView);

        $this->addComplianceViewGroup($extraGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $fixStatus = function(ComplianceViewStatus $status) {
          if ($status->getPoints() >= $status->getComplianceView()->getMaximumNumberOfPoints()) {
              $status->setStatus(ComplianceStatus::COMPLIANT);
          } else if ($status->getPoints() > 0) {
              $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
          }
        };

        $status->getComplianceViewStatus('chw_01')->setPoints(
            $status->getComplianceViewGroupStatus('course_chw')->getPoints()
        );

        $status->getComplianceViewStatus('health_care')->setPoints(
            $status->getComplianceViewGroupStatus('course_health_care')->getPoints()
        );

        $status->getComplianceViewStatus('chw_practicum')->setPoints(
            $status->getComplianceViewGroupStatus('course_chw_practicum')->getPoints()
        );

        $fixStatus($status->getComplianceViewStatus('chw_01'));
        $fixStatus($status->getComplianceViewStatus('health_care'));
        $fixStatus($status->getComplianceViewStatus('chw_practicum'));
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new CHW2015ComplianceProgramReportPrinter();
        $printer->showCompleted = false;

        return $printer;
    }

}


class CHW2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $courseHealthCareStatus = $status->getComplianceViewGroupStatus('course_health_care');
        $courseCHWPracticumStatus = $status->getComplianceViewGroupStatus('course_chw_practicum');
        $registerCHWSiteStatus = $status->getComplianceViewStatus('register_chw_site');
        $updateInfoStatus = $status->getComplianceViewStatus('update_info');
        $completeHRAStatus = $status->getComplianceViewStatus('complete_hra');
        $completeWheelStatus = $status->getComplianceViewStatus('complete_wheel');

        $user = $status->getUser();

        ?>
        <script type="text/javascript">
            $(function(){
                $('.book-journey-across-the-lifespan').removeClass('book-journey-across-the-lifespan').addClass('book-journey');
                $('.viewSectionRow th').attr('colspan', '6');
                $('.headerRow').not('.viewSectionRow').css('background-color', '#005CE6');
                $('.headerRow').not('.viewSectionRow').css('height', '50px');

                $('.view-chapter_three .links').html('<a href="/resources/5990/CHW%202710%20Book%20Bridges%20Out%20of%20Poverty%20Study%20Guide%20Qs.doc">Details &amp; Do</a>');
                $('.view-chapter_three .links').attr('rowspan', '4');
                $('.view-chapter_four .links').remove();
                $('.view-chapter_eight .links').remove();
                $('.view-chapter_eleven .links').remove();

                $('.view-question_chapter_two .links').html('<a target="_self" href="/resources/6119/CHW%202730%20Book%20Journeys%20Assignment%20Calendar%20092515.pdf">Document</a>');
                $('.view-question_chapter_two .links').attr('rowspan', '4');
                $('.view-question_chapter_five .links').remove();
                $('.view-question_chapter_seven .links').remove();
                $('.view-question_chapter_eight .links').remove();

                $('.view-question_chapter_eleven .links').html('<a target="_self" href="/resources/6119/CHW%202730%20Book%20Journeys%20Assignment%20Calendar%20092515.pdf">Document</a>');
                $('.view-question_chapter_eleven .links').attr('rowspan', '3');
                $('.view-question_chapter_twelve .links').remove();
                $('.view-question_chapter_fourteen .links').remove();

                $('.view-report_one .links').html('<a target="_self" href="/resources/6002/CHW%202750%20Practicum%20assignment-reports.pdf">Document</a>');
                $('.view-report_one .links').attr('rowspan', '6');
                $('.view-report_two .links').remove();
                $('.view-report_three .links').remove();
                $('.view-report_four .links').remove();
                $('.view-report_five .links').remove();
                $('.view-report_six .links').remove();

                $('.view-crp_card').after('<tr style="text-align: center;"><td>Extra Credit - Confirm/Update Contact Info</td><td></td><td><?php echo $updateInfoStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td><?php echo $updateInfoStatus->getPoints() ?></td><td class="status"><img src="<?php echo $updateInfoStatus->getLight() ?>" class="light" alt=""></td><td><a href="/my_account/updateAll?redirect=/compliance_programs">Enter/Update Info</a></td></tr>');
                $('.view-crp_card').after('<tr style="text-align: center;"><td>Extra Credit - Register on CHW Site</td><td></td><td><?php echo $registerCHWSiteStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td><?php echo $registerCHWSiteStatus->getPoints() ?></td><td class="status"><img src="<?php echo $registerCHWSiteStatus->getLight() ?>" class="light" alt=""></td><td></td></tr>');
                $('.view-crp_card').after('<tr style="text-align: center;"><td>Total & Status as of <?php echo date('m/d/Y') ?> = </td><td></td><td><?php echo $courseCHWStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $courseCHWStatus->getPoints() ?></td><td colspan="2"></td></tr>');

                $('.view-investigation').after('<tr style="text-align: center;"><td>Extra Credit - Complete the Wheel of Life (can help with insights)</td><td></td><td><?php echo $completeWheelStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td><?php echo $completeWheelStatus->getPoints() ?></td><td class="status"><img src="<?php echo $completeWheelStatus->getLight() ?>" class="light" alt=""></td><td><a href="/content/13047?action=takeNewWOL&user_id=<?php echo $user->getId() ?>">Click to Do</a></td></tr>');
                $('.view-investigation').after('<tr style="text-align: center;"><td>Total & Status as of <?php echo date('m/d/Y') ?> = </td><td></td><td><?php echo $courseHealthCareStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $courseHealthCareStatus->getPoints() ?></td><td colspan="2"></td></tr>');

                $('.view-instructor_evaluation').after('<tr style="text-align: center;"><td>Extra Credit - Complete the HPA (can help with goals)</td><td></td><td><?php echo $completeHRAStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td><?php echo $completeHRAStatus->getPoints() ?></td><td class="status"><img src="<?php echo $completeHRAStatus->getLight() ?>" class="light" alt=""></td><td><a href="/content/989">Click to Do</a></td></tr>');
                $('.view-instructor_evaluation').after('<tr style="text-align: center;"><td>Total & Status as of <?php echo date('m/d/Y') ?> = </td><td></td><td><?php echo $courseCHWPracticumStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></td><td><?php echo $courseCHWPracticumStatus->getPoints() ?></td><td colspan="2"></td></tr>');
                $('.phipTable').children(':eq(2)').hide();

                $('.headerRow-extra').hide();
                $('.view-register_chw_site').hide();
                $('.view-update_info').hide();
                $('.view-complete_hra').hide();
                $('.view-complete_wheel').hide();
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

        <div id="altPageHeading">Community Health Workers 2015 Program</div>

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
