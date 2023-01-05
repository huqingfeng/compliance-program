<?php

use hpn\steel\query\SelectQuery;


class Harris2017CompleteScreeningView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        if(!parent::evaluateStatus($user, $array)) {
            return false;
        }

        $requiredFields = array(
            'cholesterol'   => trim((string) $array['cholesterol']),
            'ldl'            => trim((string) $array['ldl']),
            'hdl'            => trim((string) $array['hdl']),
            'triglycerides'=> trim((string) $array['triglycerides']),
            'glucose'       => trim((string) $array['glucose']),
            'height'        => trim((string) $array['height']),
            'weight'        => trim((string) $array['weight']),
            'waist'         => trim((string) $array['waist']),
            'bmi'           => trim((string) $array['bmi']),
            'systolic'     => trim((string) $array['systolic']),
            'diastolic'    => trim((string) $array['diastolic'])
        );

        foreach($requiredFields as $requiredField) {
            if($requiredField == '' || $requiredField == '0') {
                return ComplianceStatus::PARTIALLY_COMPLIANT;
            }
        }

        return ComplianceStatus::COMPLIANT;
    }

}

class Harris2017HomePageComplianceProgram extends ComplianceProgram
{
    public function loadEvaluators()
    {

    }

    public function loadGroups()
    {
        $group = new ComplianceViewGroup('Procedure');

        $screeningFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $screeningFormView->setReportName('Primary Care Physician Screening Form');
        $screeningFormView->setName('primary_care_physician_screening_form');
        $screeningFormView->emptyLinks();
        $screeningFormView->setAttribute('always_show_links', true);
        $screeningFormView->setAttribute('always_show_links_when_current', true);
        $screeningFormView->addLink(new Link('Download Form', '/resources/9480/Harris Final PCP Form 061217.pdf', false, '_blank'));
        $screeningFormView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));

        $screeningFormView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($screeningFormView) {
            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();


            if($activeProgramRecord->id == 1117) {
                if($programStatus->getComplianceViewStatus('screening')->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });

        $group->addComplianceView($screeningFormView);

        $healthLinc2018View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthLinc2018View->setAttribute('always_show_links_when_current', true);
        $healthLinc2018View->setName('healthLinc_2018');
        $healthLinc2018View->setReportName('Track Harris 2018 Wellness Program Status');
        $healthLinc2018View->addLink(new Link('View Status', '/compliance_programs?id=1117'));
        $healthLinc2018View->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()->find(1114);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();

            if($programStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });

        $group->addComplianceView($healthLinc2018View);
        $this->addComplianceViewGroup($group);
    }
}


class Harris2017ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'Document the points you earn from any of these action areas by using the action links');
        $required->setPointsRequiredForCompliance(350);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Online Health Risk Assessment');
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/989'));
        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setName('screening');
        $screening->setReportName('Complete Annual Physical');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(200, 0, 0, 0));
        $screening->emptyLinks();
        $screening->addLink(new Link('Download Form', ' /resources/9480/Harris Final PCP Form 061217.pdf '));
        $screening->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $required->addComplianceView($screening);

        $tobacco = new PlaceHolderComplianceView(null, 0);
        $tobacco->setMaximumNumberOfPoints(50);
        $tobacco->setReportName('Non Use of Tobacco');
        $tobacco->setName('tobacco');
        $tobacco->addLink(new Link('Download Form', '/resources/9483/Harris Final TOBACCO Form 061217.pdf'));
        $tobacco->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $required->addComplianceView($tobacco);

        $dental = new PlaceHolderComplianceView(null, 0);
        $dental->setMaximumNumberOfPoints(50);
        $dental->setReportName('Dental Exam (25 points per exam, 50 points max)');
        $dental->setName('dental_exam');
        $dental->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $required->addComplianceView($dental);

        $vision = new PlaceHolderComplianceView(null, 0);
        $vision->setMaximumNumberOfPoints(25);
        $vision->setReportName('Vision Exam');
        $vision->setName('vision_exam');
        $vision->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $required->addComplianceView($vision);

        $flushot = new PlaceHolderComplianceView(null, 0);
        $flushot->setMaximumNumberOfPoints(25);
        $flushot->setReportName('Flu Shot');
        $flushot->setName('flu_shot');
        $flushot->addLink(new FakeLink('Admin will enter', '#'));
        $required->addComplianceView($flushot);

        $bodyCompositionElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(1118));
        $bodyCompositionElearning->setReportName('Body Composition Lesson');
        $bodyCompositionElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $bodyCompositionElearning->setName('body_composition');
        $bodyCompositionElearning->emptyLinks();
        $bodyCompositionElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4769'));
        $required->addComplianceView($bodyCompositionElearning);

        $bloodPressureElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(177));
        $bloodPressureElearning->setReportName('Blood Pressure Lesson');
        $bloodPressureElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $bloodPressureElearning->setName('blood_pressure');
        $bloodPressureElearning->emptyLinks();
        $bloodPressureElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3572'));
        $required->addComplianceView($bloodPressureElearning);

        $bloodSugarElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(115));
        $bloodSugarElearning->setReportName('Blood Sugar Lesson');
        $bloodSugarElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $bloodSugarElearning->setName('blood_sugar');
        $bloodSugarElearning->emptyLinks();
        $bloodSugarElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3856'));
        $required->addComplianceView($bloodSugarElearning);

        $totalCholesterolElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(184));
        $totalCholesterolElearning->setReportName('Total Cholesterol Lesson');
        $totalCholesterolElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $totalCholesterolElearning->setName('total_cholesterol');
        $totalCholesterolElearning->emptyLinks();
        $totalCholesterolElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3734'));
        $required->addComplianceView($totalCholesterolElearning);

        $tobaccoElearning = new CompleteELearningLessonComplianceView($startDate, $endDate, new ELearningLesson_v2(186));
        $tobaccoElearning->setReportName('Tobacco Lesson');
        $tobaccoElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $tobaccoElearning->setName('tobacco_lesson');
        $tobaccoElearning->emptyLinks();
        $tobaccoElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3931'));
        $required->addComplianceView($tobaccoElearning);

        $ineligibleLessonIDs = array(1118, 177, 115, 184, 186);
        $additionalElearning = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, $ineligibleLessonIDs);
        $additionalElearning->setReportName('Additional e-Learning');
        $additionalElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $additionalElearning->setName('additional_elearning');
        $additionalElearning->emptyLinks();
        $additionalElearning->setPointsPerLesson(25);
        $additionalElearning->setMaximumNumberOfPoints(25);
        $additionalElearning->addLink(new Link('Complete e-Learning Lesson', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $required->addComplianceView($additionalElearning);

        $lho = new PlaceHolderComplianceView(null, 0);
        $lho->setMaximumNumberOfPoints(25);
        $lho->setReportName('Register for LHO');
        $lho->setName('lho');
        $lho->addLink(new FakeLink('Admin will enter', '#'));
        $required->addComplianceView($lho);

        $openEnrollment = new PlaceHolderComplianceView(null, 0);
        $openEnrollment->setMaximumNumberOfPoints(25);
        $openEnrollment->setReportName('Open Enrollment');
        $openEnrollment->setName('open_enrollment');
        $openEnrollment->addLink(new FakeLink('Admin will enter', '#'));
        $required->addComplianceView($openEnrollment);

        $facebook = new PlaceHolderComplianceView(null, 0);
        $facebook->setMaximumNumberOfPoints(25);
        $facebook->setReportName('Signed up for Facebook, Twitter, or Text Messaging');
        $facebook->setName('facebook');
        $facebook->addLink(new FakeLink('Admin will enter', '#'));
        $required->addComplianceView($facebook);

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

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Harris2017ComplianceProgramReportPrinter();

        return $printer;
    }


}

class Harris2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <h4 style="text-align: center">2017 Harris Wellness Program</h4>

            <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

            <p>
                Welcome to your summary page for the Harris Wellness Program. This program is designed to promote health
                awareness, encourage healthy habits, and bring our workforce together by fostering a culture that cares for
                each individual’s wellbeing. You are eligible to participate in this program. The program does not apply to
                spouses or dependents. Anyone that earns 350 points by the program deadline of 10/31/2017 will receive the
                medical premium incentive.
            </p>

            <p>
                Thank you for your participation this year. Harris would encourage you to continue to participate in future
                wellness initiatives!
            </p>

            <?php if($status->getUser()->hasAttribute(Attribute::VIEW_PHI)) : ?>
                <p>Click <a href="/content/chp-document-uploader?admin=1">here</a> to view uploaded files</p>
            <?php endif ?>

            <table class="phipTable">
                <tbody>
                <tr class="headerRow headerRow-required">
                    <th><strong>1</strong>. Earn points from the activities below by the program deadline 10/31/2017</th>
                    <td style="width: 150px;">Point Value</td>
                    <td>Points Earned</td>
                    <td>Links</td>
                </tr>

                <?php foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <tr class="view-complete_hra">
                        <td>
                            <strong><?php echo getLetterFromNumber($viewNumber++) ?></strong>. <?php echo $view->getReportName() ?></td>
                        <td class="points"><?php echo $view->getMaximumNumberOfPoints() ?></td>
                        <td class="points"><?php echo $viewStatus->getPoints() ?></td>
                        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
                    </tr>

                <?php endforeach ?>

                <tr>
                    <td class="center">
                        <strong>Have You Earned Program Incentive?</strong>
                    </td>
                    <td class="requirement center">350 Points Needed</td>
                    <td class="center"><?php echo $status->getPoints() ?></td>
                    <td class="center"><img src="<?php echo $status->getLight() ?>" style="width: 30px;" /> </td>
                </tr>
                </tbody>
            </table>
        </div>




        <?php
    }
}

