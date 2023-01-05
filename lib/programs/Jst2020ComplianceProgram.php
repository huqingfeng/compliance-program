<?php

use hpn\steel\query\SelectQuery;


class Jst2020ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'E-Learning Lesson');
        $required->setPointsRequiredForCompliance(6);


        $livingHealthyElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(176));
        $livingHealthyElearning->setReportName('Living Healthy');
        $livingHealthyElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $livingHealthyElearning->setName('living_healthy');
        $livingHealthyElearning->emptyLinks();
        $livingHealthyElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3704'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3705'));
        });
        $required->addComplianceView($livingHealthyElearning);

        $eatingHealthyElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(178));
        $eatingHealthyElearning->setReportName('Eating Healthy');
        $eatingHealthyElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $eatingHealthyElearning->setName('eating_healthy');
        $eatingHealthyElearning->emptyLinks();
        $eatingHealthyElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3556'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3557'));
        });
        $required->addComplianceView($eatingHealthyElearning);

        $healthyLifeElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(180));
        $healthyLifeElearning->setReportName('Exercising for a healthy life');
        $healthyLifeElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $healthyLifeElearning->setName('healthy_life');
        $healthyLifeElearning->emptyLinks();
        $healthyLifeElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3574'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3575'));
        });
        $required->addComplianceView($healthyLifeElearning);

        $managingStressElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(181));
        $managingStressElearning->setReportName('Managing Stress');
        $managingStressElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $managingStressElearning->setName('managing_stress');
        $managingStressElearning->emptyLinks();
        $managingStressElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3736'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3737'));
        });
        $required->addComplianceView($managingStressElearning);

        $weightManagementElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(1118));
        $weightManagementElearning->setReportName('Weight Management');
        $weightManagementElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $weightManagementElearning->setName('weight_management');
        $weightManagementElearning->emptyLinks();
        $weightManagementElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=4769'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=4770'));
        });
        $required->addComplianceView($weightManagementElearning);

        $heartDiseaseElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(99));
        $heartDiseaseElearning->setReportName('How to prevent heart disease');
        $heartDiseaseElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $heartDiseaseElearning->setName('heart_disease');
        $heartDiseaseElearning->emptyLinks();
        $heartDiseaseElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3638'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3639'));
        });
        $required->addComplianceView($heartDiseaseElearning);

        $managingCholesterolElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(184));
        $managingCholesterolElearning->setReportName('Managing cholesterol');
        $managingCholesterolElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $managingCholesterolElearning->setName('managing_cholesterol');
        $managingCholesterolElearning->emptyLinks();
        $managingCholesterolElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3734'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3735'));
        });
        $required->addComplianceView($managingCholesterolElearning);

        $essentialHypertensionElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(177));
        $essentialHypertensionElearning->setReportName('Essential Hypertension');
        $essentialHypertensionElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $essentialHypertensionElearning->setName('essential_hypertension');
        $essentialHypertensionElearning->emptyLinks();
        $essentialHypertensionElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3572'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3573'));
        });
        $required->addComplianceView($essentialHypertensionElearning);

        $diabetesIntroductionElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(218));
        $diabetesIntroductionElearning->setReportName('Diabetes Introduction');
        $diabetesIntroductionElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $diabetesIntroductionElearning->setName('diabetes_introduction');
        $diabetesIntroductionElearning->emptyLinks();
        $diabetesIntroductionElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3535'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3536'));
        });
        $required->addComplianceView($diabetesIntroductionElearning);

        $diabetesMealPlanningElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(106));
        $diabetesMealPlanningElearning->setReportName('Diabetes Meal Planning');
        $diabetesMealPlanningElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $diabetesMealPlanningElearning->setName('diabetes_meal_planning');
        $diabetesMealPlanningElearning->emptyLinks();
        $diabetesMealPlanningElearning->setPostEvaluateCallback(function($status, $user) {
            $status->getComplianceView()->addLink(new Link('Lesson (English)', '/content/9420?action=displayQuiz&quiz_id=3537'));
            $status->getComplianceView()->addLink(new Link('Lesson (Spanish)', '/content/9420?action=displayQuiz&quiz_id=3538'));
        });
        $required->addComplianceView($diabetesMealPlanningElearning);


        $this->addComplianceViewGroup($required);
    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true, true, true);
        $printer->setShowUserFields(true, true, true, true, true, true, null, null, true);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, true);
        $printer->setShowComment(false, false, false);

        $printer->addEndStatusFieldCallBack('Most Recently Completed e-Learning Lesson', function(ComplianceProgramStatus $status) {
            $eligibleLessons = array(99, 106, 176, 177, 178, 180, 181, 184, 218, 1118);
            $completedLessons = ELearningLessonCompletion_v2::getAllCompletedLessons($status->getUser(), '2020-02-01', '2020-05-31', true);

            foreach($completedLessons as $completedLesson) {
                if(!in_array($completedLesson->getLesson()->getId(), $eligibleLessons)) continue;

                return $completedLesson->getLesson()->getName(). ' ('. $completedLesson->getCreationDate().')';
            }

            return '';
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Jst2020ComplianceProgramReportPrinter();

        return $printer;
    }


}

class Jst2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status) {
        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');

        $viewNumber = 0;
        ?>

        <style type="text/css">
            .pageHeading {
                font-weight: bold;
                text-align: center;
                margin-bottom: 20px;
            }

            .page {
                font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
            }

            .phipTable {
                width: 100%;
                border-collapse: collapse;


            }

            .phipTable th, .phipTable td {
                border: 1px solid #000000;
                padding: 2px;
            }

            .phipTable .headerRow {
                background-color: #158E4C;
                font-weight: normal;
                color: #FFFFFF;
                font-size: 12pt;
            }

            .phipTable .headerRow th {
                text-align: left;
                font-weight: normal;
            }

            .phipTable .headerRow td {
                text-align: center;
            }

            .phipTable .links {
                text-align: center; width:10em;
            }

            .center, .points, .result {
                text-align: center;
            }

            .white {
                background-color: #FFFFFF;
            }

            .light {
                width: 25px;
            }

            .status {
                text-align: center;
            }

            #legend, #legend tr, #legend td {
                padding: 0px;
                margin: 0px;
                text-align:center;
            }

            #legend td {

                padding-bottom: 5px;
            }


            #legendText {
                text-align: center;
                background-color: #158E4C;
                font-weight: normal;
                color: #FFFFFF;
                font-size: 12pt;
                margin-bottom: 5px;
            }

            .legendEntry {
                width: 160px;
                float: left;
                text-align: center;
                padding-left: 2px;
            }
        </style>

        <div class="page">
            <h4 style="text-align: center">2020 JST Wellness Program</h4>

            <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

            <p>
                JST cares about your health and overall well-being! That is why we are bringing you this incentive program
                which contains 10 lessons geared towards specific areas of your health that may need improvement. Employees
                who watch any 6 videos and complete the quizzes that follow, will be eligible to be in monthly drawings
                for some prizes. The program will run from March - May.
            </p>

            <p>
                Your status will turn green when you watch the video. You do NOT need to print any certificate of
                completion. It is all tracked in within the portal.
            </p>

            <p>Take action and commit to a healthier, happier life with this wellness program</p>


            <table class="phipTable">
                <tbody>
                <tr class="headerRow headerRow-required">
                    <th><strong>1</strong>. E-Learning Lesson</th>
                    <td style="width: 150px;">Count Completed</td>
                    <td>Status</td>
                    <td>Links</td>
                </tr>

                <?php foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <tr class="view-complete_hra">
                        <td>
                            <strong><?php echo getLetterFromNumber($viewNumber++) ?></strong>. <?php echo $view->getReportName() ?></td>
                        <td class="points"><?php echo $viewStatus->getPoints() ?></td>
                        <td class="points"><img src="<?php echo $viewStatus->getLight() ?>" style="width: 30px;" /> </td>
                        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
                    </tr>

                <?php endforeach ?>

                <tr>
                    <td class="center">
                        <strong>Total Lessons Complete = </strong>
                    </td>
                    <td class="requirement center"><?php echo $status->getPoints() ?></td>
                    <td class="center"><img src="<?php echo $status->getLight() ?>" style="width: 30px;" /> </td>
                    <td class="center"></td>
                </tr>
                </tbody>
            </table>
        </div>




        <?php
    }
}

