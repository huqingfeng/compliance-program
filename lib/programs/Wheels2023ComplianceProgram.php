<?php

class Wheels2023ComplianceProgram extends ComplianceProgram
{


    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(true, true, true, null, null, null, null, null, true);
        $printer->setShowUserContactFields(true);


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Wheels2023ComplianceProgramReportPrinter();

        return $printer;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('OK or Done', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Borderline', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Received Yet', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $core = new ComplianceViewGroup('core', 'Core Action Requirements');


        $hraView = new CompleteHRAComplianceView($startDate, $endDate);
        $hraView->setReportName('Complete the Health Risk Questionnaire');
        $hraView->setName('hra');
        $hraView->setAttribute('deadline', '12/01/2023');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/my-health'));
        $hraView->addLink(new Link('<span style="margin-left: 10px;">Results</span>', '/content/my-health'));
        $core->addComplianceView($hraView);


        $eligibleLessonIDs = array(202, 1340, 692);

        $tobacco = new CompleteELearningLessonsComplianceView($startDate, $endDate, $eligibleLessonIDs);
        $tobacco->setName('tobacco');
        $tobacco->setReportName('Submit Tobacco Affidavit');
        $tobacco->setAttribute('deadline',  '12/01/2023');
        $tobacco->setNumberRequired(3);
        $tobacco->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($startDate, $endDate) {
            $view = $status->getComplianceView();

            $record = $user->getNewestDataRecord('wheels_tobacco_2023');

            if($record->exists() && $record->date) {
                $date = str_replace('-', '/', $record->date);
                $date = date('m/d/Y', strtotime($date));

                if(strtotime($date) >= $startDate) {
                    if($record->smoker === "2") {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                        $status->setComment($date);
                    } else {
                        $view->setReportName('
                    <p>Submit Tobacco Affidavit</p>
                    <ol style="list-style-type: lower-alpha; width:300px;">
                        Not Tobacco Free Alternative – Complete 3 additional tobacco related e-learnings
                        <ol style="list-style-type: Lower-roman">
                            <li>Smoking Cessation</li>
                            <li>E-Cigarettes and Vaping</li>
                            <li>Secondhand Smoke</li>
                        </ol>
                    </ol>');

                        $view->addLink(new Link('<br/><br/>Smoking Cessation<br/>', '/content/9420?action=displayQuiz&quiz_id=3935'));
                        $view->addLink(new Link('E-Cigarettes and Vaping<br/>', '/content/9420?action=displayQuiz&quiz_id=5073'));
                        $view->addLink(new Link('Secondhand Smoke', '/content/9420?action=displayQuiz&quiz_id=4298'));

                        $status->setComment(count($status->getAttribute('lessons_completed')));
                    }
                }

            }


        });
        $tobacco->emptyLinks();
        $tobacco->addLink(new Link('Tobacco Affidavit', '/content/wheels-tobacco-affidavit-2023'));
        $core->addComplianceView($tobacco);


        $requiredNumber = 3;
        $elearning = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, $requiredNumber, $eligibleLessonIDs);
        $elearning->setName('elearning');
        $elearning->setReportName('Complete 3 Wellness Activities and/or E-Learnings');
        $elearning->setAttribute('deadline',  '12/01/2023');
        $elearning->setAllowPointsOverride(true);
        $elearning->setPreMapCallback(function(ComplianceViewStatus $status){
            $lessonCompleted = count($status->getAttribute('lessons_completed'));
            $status->setPoints($lessonCompleted);
        });
        $elearning->setPostEvaluateCallback(function(ComplianceViewStatus $status) {
            $status->setComment($status->getPoints());
        });
        $elearning->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $core->addComplianceView($elearning);


        $screeningView = new CompleteScreeningComplianceView('2022-11-01', $endDate);
        $screeningView->setReportName('Complete Biometric Screening Onsite or via Alternative Screening Form');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->setAttribute('deadline', '12/01/2023');
        $core->addComplianceView($screeningView);



        $this->addComplianceViewGroup($core);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $allCompliant = true;
        foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($user->getRelationshipType() != Relationship::EMPLOYEE && $viewStatus->getComplianceView()->getName() == 'complete_screening') continue;

            if($viewStatus->getStatus() != ComplianceViewStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }

        if($allCompliant) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }

    private $lastType = null;

}

class Wheels2023ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $user = $status->getUser();

        ?>

        <style type="text/css">
            html {
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content h1,
            #page #content h1 {
                padding: 0.5rem;
                background-color: #8587b9;
                color: white;
                text-align: center;
                font-family: Roboto;
                font-size: 1.5rem;
                font-weight: bold;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content aside,
            #page #content aside {
                padding: 0 1rem;
                background-color: #f8fafc;
                border: 1px solid #e7eaf2;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content aside p:first-of-type + p,
            #page #content aside p:first-of-type + p {
                position: relative;
            }

            #page #wms3-content aside i,
            #page #content aside i {
                background-color: transparent !important;
                text-align: center;
                margin-top: -0.95rem;
                font-size: 1.25rem;
            }

            #page #wms3-content aside i,
            #page #wms3-content q,
            #page #content aside i,
            #page #content q {
                position: absolute;
                top: 50%;
                left: 0.5rem;
            }

            #page #wms3-content q,
            #page #content q {
                margin-top: -1.2rem;
                background-color: #ffb65e;
                text-align: left;
            }

            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q:before,
            #page #content q:after {
                content: '';
                position: absolute;
                background-color: inherit;
            }

            #page #wms3-content q,
            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q,
            #page #content q:before,
            #page #content q:after {
                display: inline-block;
                width:  1.5rem;
                height: 1.5rem;
                border-radius: 0;
                border-top-right-radius: 30%;
            }

            #page #wms3-content q,
            #page #content q {
                transform: rotate(-60deg) skewX(-30deg) scale(1,.866);
            }

            #page #wms3-content q:before,
            #page #content q:before {
                transform: rotate(-135deg) skewX(-45deg) scale(1.414,.707) translate(0,-50%);
            }

            #page #wms3-content q:after,
            #page #content q:after {
                transform: rotate(135deg) skewY(-45deg) scale(.707,1.414) translate(50%);
            }

            #page #wms3-content table,
            #page #content table {
                border-collapse: separate;
                table-layout: fixed;
                width: 100%;
                line-height: 1.5rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content table + table,
            #page #content table + table {
                margin-top: 1rem;
            }

            #page #wms3-content th,
            #page #content th {
                padding: 1rem;
                background-color: #014265;
                color: white;
                border: 1px solid #014265;
                font-weight: bold;
                text-align: center;
            }

            #page #wms3-content th:first-of-type,
            #page #content th:first-of-type {
                border-top-left-radius: 0.25rem;
                text-align: left;
            }

            #page #wms3-content th:last-of-type,
            #page #content th:last-of-type {
                border-top-right-radius: 0.25rem;
            }

            #page #wms3-content td,
            #page #content td {
                padding: 1rem;
                color: #57636e;
                border-left: 1px solid #e8e8e8;
                border-bottom: 1px solid #e8e8e8;
                text-align: center;
            }

            #page #wms3-content tr:last-of-type td:first-of-type,
            #page #content tr:last-of-type td:first-of-type {
                border-bottom-left-radius: 0.25rem;
            }

            #page #wms3-content td:last-of-type,
            #page #content td:last-of-type {
                border-right: 1px solid #e8e8e8;
            }

            #page #wms3-content tr:last-of-type td:last-of-type,
            #page #content tr:last-of-type td:last-of-type {
                border-bottom-right-radius: 0.25rem;
            }

            #page #wms3-content a,
            #page #content a {
                display: inline-block;
                color: #0085f4 !important;
                font-size: 1rem;
                text-transform: uppercase;
                text-decoration: none !important;
            }

            #page #wms3-content a + a,
            #page #content a + a {
                margin-top: 1rem;
            }

            #page #wms3-content a:hover,
            #page #wms3-content a:focus,
            #page #wms3-content a:active,
            #page #content a:hover,
            #page #content a:focus,
            #page #content a:active {
                color: #0052C1 !important;
                text-decoration: none !important;
            }

            #page #wms3-content i,
            #page #content i {
                width: 1.5rem;
                height: 1.5rem;
                line-height: 1.5rem;
                background-color: #ced2db;
                border-radius: 999px;
                color: white;
                font-size: 1.25rem;
            }

            #page #wms3-content i.fa-check,
            #page #content i.fa-check {
                background-color: #4fd3c2;
            }

            #page #wms3-content i.fa-exclamation,
            #page #content i.fa-exclamation {
                background-color: #ffb65e;
            }

            #page #wms3-content i.fa-times,
            #page #content i.fa-times {
                background-color: #dd7370;
            }

            #legend {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 2rem 0;
            }

            #legend div {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
            }

            #legend h2 {
                margin: 2rem 0;
                color: #23425e;
                font-size: 1.75rem;
                letter-spacing: 0.4rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #legend p {
                position: relative;
                width: 11rem;
                height: 2.5rem;
                line-height: 2.5rem;
                margin: 0.25rem 0.25rem;
                padding-left: 1.25rem;
                background-color: #ebf1fa;
                text-align: center;
                font-size: 1.1rem;
            }

            #legend i {
                position: absolute;
                left: 1rem;
                top: 50%;
                margin-top: -0.75rem;
            }

            @media only screen and (max-width: 1200px) {
                #legend {
                    flex-direction: column;
                    align-items: flex-start;
                }

                #legend > div {
                    align-content: flex-start;
                }
            }

            @media only screen and (max-width: 1060px) {
                #page #wms3-content table,
                #page #content table {
                    table-layout: auto;
                }
            }
        </style>

        <h1>Rewards/To-Do Summary Page</h1>

        <aside>
            <p><?= $status->getUser()->getFullName() ?></p>

            <p>Welcome to your Welcome to the 2023 Wellness Program.</p>

            <p>Use the Action Links in the last column to get things done and learn more.</p>

        </aside>

        <div id="legend">
<!--            <h2>--><?//= $track ?><!--</h2>-->
            <div>
                <div>
                    <p><i class="far fa-check"></i> CRITERIA MET</p>
                    <p><i class="fas fa-exclamation"></i> IN PROGRESS</p>
                </div>
                <div>
                    <p><i class="far fa-times"></i> NOT STARTED</p>
                    <p><i class="far fa-minus"></i> N/A</p>
                </div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th colspan="4">A. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <th>Deadline</th>
                <th>Count/Date Completed</th>
                <th>Status</th>
                <th>Links</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
            <?php if($user->getRelationshipType() != Relationship::EMPLOYEE && $viewStatus->getComplianceView()->getName() == 'complete_screening') continue; ?>
            <tr>
                <td colspan="4" style="text-align: left;">
                    <?= $viewStatus->getComplianceView()->getReportName(true) ?>
                </td>
                <td>
                    <?= $viewStatus->getComplianceView()->getAttribute('deadline', '') ?>
                </td>
                <td>
                    <?= $viewStatus->getComment() ?>
                </td>
                <td>
                    <i class="<?= $this->getFaIcon($viewStatus->getStatus()) ?>"></i>
                </td>
                <td>
                    <?php
                    foreach ($viewStatus->getComplianceView()->getLinks() as $link)
                        echo $link->getHTML();
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>




            </tbody>
        </table>






        <?php
    }

    private function getFaIcon($code) {
        if ($code == 4) return 'far fa-check';
        if ($code == 2) return 'fas fa-exclamation';
        if ($code == 1) return 'far fa-times';
        return 'far fa-minus';
    }
}

