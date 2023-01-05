<?php

class Wheels2020ComplianceProgram extends ComplianceProgram
{
    public function getType(User $user)
    {
        if($this->lastType && $this->lastType['user_id'] == $user->id) {
            return $this->lastType['type'];
        } else {
            $type = $user->getGroupValueFromTypeName('Wheels Program Type 2020', 'Program B');

            $this->lastType = array('user_id' => $user->id, 'type' => $type);

            return $type;
        }
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(true, true, true, null, null, null, null, null, true);
        $printer->setShowUserContactFields(true);

        $printer->addCallbackField('program_type', function (User $user) {
            return $this->getType($user);
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Wheels2020ComplianceProgramReportPrinter();

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

        $screeningModel = new ComplianceScreeningModel();

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $core = new ComplianceViewGroup('core', 'Core Action Requirements');


        $hraView = new CompleteHRAComplianceView($startDate, $endDate);
        $hraView->setReportName('Complete the Health Risk Questionnaire');
        $hraView->setName('hra');
        $hraView->setAttribute('deadline', '12/31/2021');
        $core->addComplianceView($hraView);


        $eligibleLessonIDs = array(202, 1340, 692);

        $tobacco = new CompleteELearningLessonsComplianceView($startDate, $endDate, $eligibleLessonIDs);
        $tobacco->setName('tobacco');
        $tobacco->setReportName('Submit Tobacco Affidavit');
        $tobacco->setAttribute('deadline',  '12/31/2021');
        $tobacco->setNumberRequired(3);
        $tobacco->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();

            $record = $user->getNewestDataRecord('wheels_tobacco_2020');

            if($record->exists() && $record->date) {
                if($record->smoker === "2") {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment($record->date);
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

            if($status->getStatus() != ComplianceStatus::COMPLIANT) {

            }
        });
        $tobacco->emptyLinks();
        $tobacco->addLink(new Link('Tobacco Affidavit', '/content/wheels-tobacco-affidavit'));
        $core->addComplianceView($tobacco);


        $elearning = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $elearning->setName('elearning');
        $elearning->setReportName('Complete E-Learning');
        $elearning->setAttribute('deadline',  '12/31/2021');
        $elearning->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($startDate, $endDate, $eligibleLessonIDs) {
            $view = $status->getComplianceView();
            $type = $this->getType($user);
            if($type == 'Program B') {
                $requiredNumber = 3;
                $view->setReportName('Complete 3 E-Learnings');
            } else {
                $requiredNumber = 1;
                $view->setReportName('Complete 1 E-Learnings');
            }


            $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, $requiredNumber, $eligibleLessonIDs);
            $alternative->setUseOverrideCreatedDate(true);
            $alternativeStatus = $alternative->getStatus($user);

            if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setComment(count($alternativeStatus->getAttribute('lessons_completed')));

        });
        $elearning->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $core->addComplianceView($elearning);


        $wellnessActivity = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wellnessActivity->setName('wellness');
        $wellnessActivity->setReportName('Complete Wellness Activity');
        $wellnessActivity->setAttribute('deadline',  '12/31/2021');
        $wellnessActivity->setCompliancePointStatusMapper(new CompliancePointStatusMapper(1, 0, 0, 0));
        $wellnessActivity->setAllowPointsOverride(true);
        $wellnessActivity->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($startDate, $endDate, $eligibleLessonIDs) {
            $view = $status->getComplianceView();
            $points = $status->getPoints();
            $type = $this->getType($user);
            if($type == 'Program B') {
                $view->setReportName('Complete 2 Wellness Activity');
                if($points >= 2) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } elseif($points > 0) {
                    $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                }

            } else {
                $view->setReportName('Complete 1 Wellness Activity');

                if($points >= 1) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
            $status->setComment($status->getPoints());

            if($user->getRelationshipType() == Relationship::SPOUSE) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        });
        $core->addComplianceView($wellnessActivity);

        $this->addComplianceViewGroup($core);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

    }

    private $lastType = null;

}

class Wheels2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $hraStatus = $coreGroupStatus->getComplianceViewStatus('hra');
        $tobaccoStatus = $coreGroupStatus->getComplianceViewStatus('tobacco');
        $elearningStatus = $coreGroupStatus->getComplianceViewStatus('elearning');
        $wellnessStatus = $coreGroupStatus->getComplianceViewStatus('wellness');


        $user = $status->getUser();

        $type = $status->getComplianceProgram()->getType($user);
        ?>
        <style type="text/css">
            .phipTable ul, .phipTable li {
                margin-top:0px;
                margin-bottom:0px;
                padding-top:0px;
                padding-bottom:0px;
            }

            .pageHeading {
                font-weight:bold;
                text-align:center;
                margin-bottom:20px;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable .headerRow {
                background-color:#2e75b3;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .phipTable .headerRow th {
                text-align:left;
                font-weight:normal;
            }

            .phipTable .headerRow td {
                text-align:center;
            }

            .phipTable .links {
                text-align:center;
            }

            .phipTable .left {
                /*padding-left:20px*/
            }

            .center {
                text-align:center;
            }

            .white {
                background-color:#FFFFFF;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .right {
                text-align:right;
            }

            #legend, #legend tr, #legend td {
                padding:0px;
                margin:0px;
            }

            #legend td {

                padding-bottom:5px;
            }

            #legendText {
                text-align:center;
                background-color:#2e75b3;
                font-weight:normal;
                color:#FFFFFF;
                font-size:12pt;
                margin-bottom:5px;
            }

            .legendEntry {
                width:160px;
                float:left;
                text-align:center;
                padding-left:2px;
            }

            .number {
                width: 20px;
            }
        </style>

        <script type="text/javascript">
            $(function() {


            });
        </script>
        <div class="pageHeading">Rewards/To-Do Summary Page</div>
        <p>Hello <?php echo $status->getUser() ?>,</p>
        <p></p>
        <p>Welcome to your Wellness On Wheels Incentive/To-Do summary page.</p>

        <p>Use the Action Links in the last column to get things done and learn more.</p>



        <div style="font-weight: bold; text-align: center; margin:10px;"><?php echo $type ?></div>
        <table class="phipTable" border="1">
            <tbody>
            <tr class="headerRow" style="height: 50px;">
                <th colspan="3">A. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <td>Deadline</td>
                <td>Count/Date Completed</td>
                <td colspan="2">Goal Met</td>
                <td>Links</td>
            </tr>
            <tr>
                <td class="center number">1</td>
                <td colspan="2"><?php echo $hraStatus->getComplianceView()
                            ->getReportName() ?></td>
                <td class="center">
                    <?php echo $hraStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $hraStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <img src="<?php echo $hraStatus->getLight() ?>" class="light" />
                </td>

                <td class="links">
                    <?php
                    foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="center">2</td>
                <td colspan="2">
                    <?php echo $tobaccoStatus->getComplianceView()
                        ->getReportName() ?>
                </td>
                <td class="center">
                    <?php echo $tobaccoStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $tobaccoStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <img src="<?php echo $tobaccoStatus->getLight() ?>" class="light" />
                </td>

                <td class="links">
                    <?php
                    foreach($tobaccoStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="center">3</td>
                <td colspan="2"><?php echo $elearningStatus->getComplianceView()
                        ->getReportName() ?></td>
                <td class="center">
                    <?php echo $elearningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $elearningStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <img src="<?php echo $elearningStatus->getLight() ?>" class="light" />
                </td>
                <td class="links">
                    <?php
                    foreach($elearningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <?php if($user->getRelationshipType() != Relationship::SPOUSE) : ?>
                <tr>
                    <td class="center">4</td>
                    <td colspan="2"><?php echo $wellnessStatus->getComplianceView()
                            ->getReportName() ?></td>
                    <td class="center">
                        <?php echo $wellnessStatus->getComplianceView()->getAttribute('deadline') ?>
                    </td>
                    <td class="center">
                        <?php echo $wellnessStatus->getComment(); ?>
                    </td>

                    <td class="center" colspan="2">
                        <img src="<?php echo $wellnessStatus->getLight() ?>" class="light" />
                    </td>
                    <td class="links">
                        Admin will enter
                    </td>
                </tr>
            <?php endif ?>


            </tbody>
        </table>

        <?php
    }
}

