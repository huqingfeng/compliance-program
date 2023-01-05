<?php

class VictorEnvelope2022ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new VictorEnvelope2022ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $wellnessGroup = new ComplianceViewGroup('wellness', 'Wellness');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('1) Complete the Health Power Assessment (HPA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Complete HPA / View Results', '/content/989'));
        $wellnessGroup->addComplianceView($hraView);


        $cardioElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'cardiovascular_health');
        $cardioElearningView->setReportName('1) Complete 1 or more e-lessons related to Cardiovascular Health');
        $cardioElearningView->setName('cardiovascular_health_elearning');
        $cardioElearningView->setNumberRequired(1);
        $cardioElearningView->useAlternateCode(true);
        $cardioElearningView->emptyLinks();
        $cardioElearningView->addLink(new Link('View / Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=cardiovascular_health'));
        $wellnessGroup->addComplianceView($cardioElearningView);

        $bloodPressureElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'blood_pressure');
        $bloodPressureElearningView->setReportName('2) Complete 1 or more e-lessons related to Blood Pressure');
        $bloodPressureElearningView->setName('blood_pressure_elearning');
        $bloodPressureElearningView->setNumberRequired(1);
        $bloodPressureElearningView->useAlternateCode(true);
        $bloodPressureElearningView->emptyLinks();
        $bloodPressureElearningView->addLink(new Link('View / Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=blood_pressure'));
        $wellnessGroup->addComplianceView($bloodPressureElearningView);

        $mentalHealthElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'mental_health');
        $mentalHealthElearningView->setReportName('3) Complete 1 or more e-lessons related to Mental Health');
        $mentalHealthElearningView->setName('mental_health_elearning');
        $mentalHealthElearningView->setNumberRequired(1);
        $mentalHealthElearningView->emptyLinks();
        $mentalHealthElearningView->useAlternateCode(true);
        $mentalHealthElearningView->addLink(new Link('View / Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=mental_health'));
        $wellnessGroup->addComplianceView($mentalHealthElearningView);

        $foodsNutritionElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'foods_nutrition');
        $foodsNutritionElearningView->setReportName('4) Complete 1 or more e-lessons related to Foods & Nutrition');
        $foodsNutritionElearningView->setName('foods_nutrition_elearning');
        $foodsNutritionElearningView->setNumberRequired(1);
        $foodsNutritionElearningView->useAlternateCode(true);
        $foodsNutritionElearningView->emptyLinks();
        $foodsNutritionElearningView->addLink(new Link('View / Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=foods_nutrition'));
        $wellnessGroup->addComplianceView($foodsNutritionElearningView);

        $this->addComplianceViewGroup($wellnessGroup);


        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco');

        $affidavitView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $affidavitView->setName('tobacco_affidavit');
        $affidavitView->setReportName('A. Verify that you do not use any form of Tobacco');
        $affidavitView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $affidavit_record = UserDataRecord::getNewestRecord($user, 'victor_envelope_tobacco_2022', true);
            $accepted = $affidavit_record->getDataFieldValue("smoker") === "1";
            $denied = $affidavit_record->getDataFieldValue("smoker") === "0";

            if ($denied) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment($affidavit_record->getDataFieldValue('date'));
            } else if ($accepted) {
                $status->setComment($affidavit_record->getDataFieldValue('date'));
            } else {
                $status->setComment($affidavit_record->getDataFieldValue('date'));
            }
        });
        $affidavitView->addLink(new Link('Click to Verify being Tobacco Free', '/content/victorenvelope-tobacco-affidavit'));

        $tobaccoGroup->addComplianceView($affidavitView);

        $tobaccoElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $tobaccoElearningView->setReportName('B. Or - If you are a tobacco user and would like to earn the Tobacco Free Discount, complete 8 or more e-lessons (1 per day max) related to Tobacco that may be helpful for your goals');
        $tobaccoElearningView->setName('tobacco_elearning');
        $tobaccoElearningView->setNumberRequired(8);
        $tobaccoElearningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $status->setComment(count($status->getAttribute('lessons_completed')));
        });
        $tobaccoElearningView->emptyLinks();
        $tobaccoElearningView->addLink(new Link('View / Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=tobacco'));
        $tobaccoGroup->addComplianceView($tobaccoElearningView);

        $this->addComplianceViewGroup($tobaccoGroup);
    }



    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco');

        foreach($tobaccoGroupStatus->getComplianceViewStatuses() as $status) {
            if($status->getStatus() == ComplianceStatus::COMPLIANT) $tobaccoGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class VictorEnvelope2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $client = $user->getClient();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#b7dde8;
                font-weight:bold;
                font-size:9pt;
                height:20px;
            }

            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:8.4in;
                font-size: 9pt;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
                padding: 1px;
            }


            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                /*background-color:#DEDEDE;*/
            }

            #text-area-top div{
                font-size: 10pt;
            }

            #text-area-bottom div{
                font-size: 10px;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.2in;
                padding:0;
            }
        </style>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/resources/10624/victor_envelope_final_logo_010721.png" style="height:100px;"  />
                </div>
            </p>

            <p style="margin-left:3in; clear: both;">
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <div id="text-area-top">
                <div>
                    <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                    <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
                </div><br />


                <p>
                    We are committed to supporting the health & wellbeing of our employees and your families. The first
                    step in promoting good health is awareness. That is why we offer the annual Wellness Program through
                    Empower Health Services. Due to COVID-19, it was necessary to re-design our Wellness Program. We have
                    designed a comprehensive wellness program that encompasses a variety of educational activities to
                    enhance your overall well-being during this challenging time.
                </p>


                <p style="font-weight: bold; font-size: 9pt; text-decoration: underline;">
                    For Employees and Dependent Spouses Currently Enrolled in Our Medical Plans as of 12/31/2022:
                </p>
                <p>
                    You are eligible to participate in the annual Wellness Program that will run from January
                    15, 2022 – April 15, 2022.  Two incentives associated with the 2022 Wellness Program
                    - One for completing wellness activities and one for being tobacco free.
                </p>


                <p style="font-weight: bold; font-size: 9pt; text-decoration: underline;">
                    How Do I Earn the Wellness/Tobacco Discounts for plan year 6/1/2022 – 5/31/2023:
                </p>

                <p>
                    The <span style="font-weight: bold;">first incentive</span> is the Wellness Discount. <span style="text-decoration: underline;">First step is to complete the Health Power Assessment</span>.
                    Then complete 4 designated e-learning courses by April 15, 2022 to earn the wellness discount.
                    See below for the "action links" which will direct you to all steps to earn the wellness discount.
                </p>

                <p>
                    The <span style="font-weight: bold;">second incentive</span> is the Tobacco Free Discount. Please complete the Tobacco Affidavit. By
                    indicating non-tobacco use you will automatically receive the Tobacco Free Discount. If you
                    indicate on the affidavit that you are a tobacco user, you have the alternative option to complete
                    8 Tobacco Cessation e-Learning lessons (1 per day max). See below for the "action links" which
                    will direct you to all steps to earn the Tobacco Free discount.
                </p>

                <p>
                    The deadline to earn these incentives is April 15, 2022 for your discounts beginning on June 1, 2022.
                </p>

            </div>
            
            <?php echo $this->getTable($status) ?>

            <div id="text-area-bottom">
                <div style="width: 56%; float: left">
                    <div>
                        Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                        <ul>
                            <li>View all of your results and links in the report; AND</li>
                            <li>Access powerful online tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <p>
                             Your employer is committed to helping you achieve your best health.
                        </p>

                        <p>
                            If you have additional questions regarding the details of this program, please contact Empower
                             Health Services at 866.367.6974.
                        </p>

                        <p>
                            Thank you for getting these things done this year. These and many of your other actions reflect
                             how you value your own wellbeing and the wellbeing of others at home and work.
                        </p>

                        Best Regards,<br />
                        Empower Health Services
                    </div>

                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 1px;">Some of these online tools include:</div>
                    <div>
                        <ul>
                            <li>Over 1,400 e-lessons</li>
                            <li>
                                The Healthwise® Knowledgebase for decisions about medical tests, medicines, other
                                 treatments, risks and other topics
                            </li>
                            <li>Over 500 videos</li>
                            <li>Decision tools for over 170 elective care decisions</li>
                            <li>Cholesterol, body metrics, blood sugars, women’s health, men’s health and over 40 other learning centers.</li>
                        </ul>
                    </div>
                </div>
            </div>


        </div>

        <?php
    }

    private function getTable($status)
    {
        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness');

        $hraStatus = $status->getComplianceViewStatus('hra');
        $cardioStatus = $status->getComplianceViewStatus('cardiovascular_health_elearning');
        $bloodPressureStatus = $status->getComplianceViewStatus('blood_pressure_elearning');
        $mentalHealthStatus = $status->getComplianceViewStatus('mental_health_elearning');
        $foodsStatus = $status->getComplianceViewStatus('foods_nutrition_elearning');

        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco');
        $tobaccoAffidavitStatus = $status->getComplianceViewStatus('tobacco_affidavit');
        $tobaccoElearningStatus = $status->getComplianceViewStatus('tobacco_elearning');


        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th colspan="5" style="text-align: left; ">1. WELLNESS DISCOUNT – Steps to get done by April 15, 2022</th>
                    </tr>

                    <tr class="headerRow">
                        <th style="width:400px; text-align: left;">A. FIRST STEP:  AWARENESS</th>
                        <th style="width:100px;">Date / # Lessons Completed</th>
                        <th>Met Goal</th>
                        <th style="width:100px;">Wellness Discount Goals Met</th>
                        <th>Action Links</th>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $hraStatus->getComment() ?></td>
                        <td class="status-<?php echo $hraStatus->getStatus() ?>">
                            <?php if($hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td rowspan="6" class="status-<?php echo $wellnessGroupStatus->getStatus() ?>">
                            <?php echo $wellnessGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                        <td class="links">
                             <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;">B. SECOND STEP:  E-LEARNING</th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $cardioStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo count($cardioStatus->getAttribute('lessons_completed')) ?></td>
                        <td class="status-<?php echo $cardioStatus->getStatus() ?>">
                            <?php if($cardioStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td class="links">
                             <?php foreach($cardioStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></td>
                         <td><?php echo count($bloodPressureStatus->getAttribute('lessons_completed')) ?></td>
                        <td class="status-<?php echo $bloodPressureStatus->getStatus() ?>">
                            <?php if($bloodPressureStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td class="links">
                             <?php foreach($bloodPressureStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $mentalHealthStatus->getComplianceView()->getReportName() ?></td>
                         <td><?php echo count($mentalHealthStatus->getAttribute('lessons_completed')) ?></td>
                        <td class="status-<?php echo $mentalHealthStatus->getStatus() ?>">
                            <?php if($mentalHealthStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td class="links">
                             <?php foreach($mentalHealthStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $foodsStatus->getComplianceView()->getReportName() ?></td>
                         <td><?php echo count($foodsStatus->getAttribute('lessons_completed')) ?></td>
                        <td class="status-<?php echo $foodsStatus->getStatus() ?>">
                            <?php if($foodsStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td class="links">
                             <?php foreach($foodsStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="headerRow" style="background-color: #fce9da">
                        <th style="text-align: left; width: 320px;">2. TOBACCO FREE DISCOUNT <br />–Steps to get done by April 15, 2022</th>
                        <th>Date / # Lessons Done</th>
                        <th>Met Goal</th>
                        <th>Tobacco Free Discount Goal Met</th>
                        <th></th>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $tobaccoAffidavitStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $tobaccoAffidavitStatus->getComment() ?></td>
                        <td style="background-color:<?php echo $tobaccoAffidavitStatus->getComment() != '' ? ($tobaccoAffidavitStatus->getStatus() == ComplianceStatus::COMPLIANT ? '#90FF8C;' : '#FF6347;') : '' ?>">
                            <?php if($tobaccoAffidavitStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td rowspan="2" class="status-<?php echo $tobaccoGroupStatus->getStatus() ?>">
                            <?php echo $tobaccoGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                        <td class="links">
                             <?php foreach($tobaccoAffidavitStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;"><?php echo $tobaccoElearningStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $tobaccoElearningStatus->getComment() ?></td>
                        <td class="status-<?php echo $tobaccoElearningStatus->getStatus() ?>">
                            <?php if($tobaccoElearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td class="links">
                             <?php foreach($tobaccoElearningStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}