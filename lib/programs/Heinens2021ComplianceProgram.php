<?php

class Heinens2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addCallbackField('Incentive Choice', function(User $user) {
            $record = $user->getNewestDataRecord("heinens-2021-selection", true);
            return $record->response;
        });

        $printer->addEndStatusFieldCallBack('Total Premium Earned', function(ComplianceProgramStatus $status) {
            return $status->getPoints() . "%";
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Heinens2021ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('screening_incentive', 'Report Card');
        $group->setPointsRequiredForCompliance(50);

        $affidavitView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $affidavitView->setName('affidavit_response');
        $affidavitView->setReportName('Tobacco Affidavit Response');
        $affidavitView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2021', true);
            $accepted = $affidavit_record->getDataFieldValue("smoker") === "1";
            $denied = $affidavit_record->getDataFieldValue("smoker") === "0";
            $date = $affidavit_record->getDataFieldValue("date");

            $valid_date = ($date >= "2021-01-25" && $date <= "2021-03-15");

            if ($denied && $valid_date) {
                $status->setPoints(20);
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment("Negative");
            } else if ($accepted && $valid_date) {
                $status->setComment("Positive");
            } else {
                $status->setComment("Not Taken");
            }
        });
        $group->addComplianceView($affidavitView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $group->addComplianceView($hraView);

        $custom_start = "2020-01-10";

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($custom_start, $programEnd, true);
        $bmiView->overrideTestRowData(null, null, 26.9, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
                $user,
                new DateTime("2020-01-10"),
                new DateTime("2021-03-15"),
                array(
                    'fields'           => ["waist"],
                    'merge'            => true,
                    'require_complete' => false,
                    'filter'           => null
                )
            );

            if (!empty($screening["waist"] )) {
                if(($screening["waist"] <= 35) && $user->gender == "M") {
                    $status->setPoints(10);
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else if (($screening["waist"] <= 33) && $user->gender == "F") {
                    $status->setPoints(10);
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });

        $group->addComplianceView($bmiView);

        $cholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($custom_start, $programEnd);
        $cholesterolView->setReportName('Cholesterol/HDL');
        $cholesterolView->overrideTestRowData(null, null, 4, null);
        $cholesterolView->emptyLinks();
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $group->addComplianceView($cholesterolView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($custom_start, $programEnd);
        $triglyceridesView->setReportName('Triglycerides/HDL');
        $triglyceridesView->overrideTestRowData(null, null, 2, null);
        $triglyceridesView->emptyLinks();
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $triglyceridesView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
                $user,
                new DateTime("2020-01-10"),
                new DateTime("2021-03-15"),
                array(
                    'fields'           => ["hdl"],
                    'merge'            => true,
                    'require_complete' => false,
                    'filter'           => null
                )
            );

            $hdl =  $screening["hdl"] ?? null;
            $triglycerides = $status->getComment();

            if (is_numeric($triglycerides)) {
                $status->setComment("No Screening");
                $status->setPoints(0);
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $override = false;
            } else {
                $override = true;
            }

            if (!empty($hdl) && !empty($triglycerides) && $triglycerides !== 0 && !$override) {
                if (($triglycerides/$hdl) <= 2) {
                    $status->setPoints(5);
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }

                $status->setComment(number_format($triglycerides/$hdl, 2));
            }
        });

        $group->addComplianceView($triglyceridesView);

        $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($custom_start, $programEnd);
        $ha1cView->overrideTestRowData(null, null, 5.6, null);
        $ha1cView->emptyLinks();
        $ha1cView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $ha1cView->setStatusSummary(ComplianceStatus::COMPLIANT, '');

        $group->addComplianceView($ha1cView);

        $elearningView = new CompleteELearningGroupSet($programStart, "2022-05-31", 'tobacco');
        $elearningView->setReportName('Complete 6 Tobacco Related Elearning Lessons');
        $elearningView->setName('elearning_tobacco_alternative');
        $elearningView->setNumberRequired(6);
        $elearningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $lessons_completed = $status->getComment();

            $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2021', true);
            $accepted = $affidavit_record->getDataFieldValue("smoker") == 1;

            if ($lessons_completed >= 6 && $accepted) {
                $status->setPoints(20);
            } else {
                $status->setPoints(0);
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        });
        $group->addComplianceView($elearningView);


        $this->addComplianceViewGroup($group);
    }
}

class Heinens2021ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function calc_result($value) {
        if ($value) {
            return "Compliant";
        } else {
            return "Not Compliant";
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $selection = null;
        $record = $user->getNewestDataRecord("heinens-2021-selection", true);

        if (isset($_GET['selection_response'])) {
            $record->setDataFieldValue('response', $_GET['selection_response']);
            $record->save();
        } else {
            $selection = $record->response;
        }

        $premium_earned = $status->getPoints();

        // Get Affidavit Record
        $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2021', true);

        $date = $affidavit_record->getDataFieldValue("date");
        $valid_date = ($date >= "2021-01-25" && $date <= "2021-03-15");

        if ($valid_date) {
            $accepted = $affidavit_record->getDataFieldValue("smoker") === "1";
            $denied = $affidavit_record->getDataFieldValue("smoker") === "0";
        } else {
            $accepted = false;
            $denied = false;
        }

        // Get HRA Status
        $hra_status = $status->getComplianceViewStatus("complete_hra")->getStatus() == 4;

        // Get BMI Status and Points
        $bmi_points = $status->getComplianceViewStatus("comply_with_bmi_screening_test")->getPoints();
        $bmi_result = $status->getComplianceViewStatus("comply_with_bmi_screening_test")->getComment();
        $bmi_status = $bmi_points == 10;

        // Get Cholesterol Status and Points
        $cholesterol_points = $status->getComplianceViewStatus("comply_with_total_hdl_cholesterol_ratio_screening_test")->getPoints();
        $cholesterol_result = $status->getComplianceViewStatus("comply_with_total_hdl_cholesterol_ratio_screening_test")->getComment();
        $cholesterol_status = $cholesterol_points == 5;

        // Get Triglycerides Status and Points
        $triglyceride_points = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getPoints();
        $triglyceride_result = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getComment();
        $triglyceride_override = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getAttribute("override");
        $triglyceride_status = $triglyceride_points == 5;

        // Get HA1C Status and Points
        $ha1c_points = $status->getComplianceViewStatus("comply_with_ha1c_screening_test")->getPoints();
        $ha1c_result = $status->getComplianceViewStatus("comply_with_ha1c_screening_test")->getComment();
        $ha1c_status = $ha1c_points == 10;

        // Get Tobacco Elearning Alternative
        $tobacco_points = $status->getComplianceViewStatus("elearning_tobacco_alternative")->getPoints();
        $tobacco_result = $status->getComplianceViewStatus("elearning_tobacco_alternative")->getComment();
        $tobacco_status = $tobacco_points == 20;

        $load_selection = !empty($_GET['selection']);
        ?>
        <style type="text/css">
            #wms1 {
                font-size: 15px;
            }

            .fs13 {
                font-size: 13px !important;
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

            .subtitle {
                padding-left: 20px;
            }

            #results {
                width:7.6in;
                margin:0 0.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border: none;
                padding: 1px;
            }

            .correct {
                color:#4CAF50;
            }

            .incorrect {
                color: #F44336;
            }

            .action_link {
                text-align: center;
            }

            .collapsible-points-report-card i {
                font-size: 20px;
            }

            .collapsible-points-report-card .open .triangle {
                display: none;
            }

            #results th {
                background-color:#FFFFFF;
            }

            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#DEDEDE;
            }

            #not_compliant_notes p{
                margin: 5px 0;
            }
            .large {
                width: 190px;
            }
            .medium {
                width: 100px;
            }

            .pad20 {
                padding-left: 20px;
                padding-right: 20px;
            }

            .border-less td {
                border-top: 0px !important;
            }

            .bold {
                font-weight: 600;
            }

            .selection_item {
                display: flex;
                margin-bottom: 20px;
                align-items: flex-start;
                cursor: pointer;
            }

            .selection_item div {
                height: 30px;
                width: 30px;
                border-radius: 4px;
                border: 2px solid #eee;
                display: flex;
                margin-right: 20px;
                align-items: center;
                justify-content: center;
                color: #4DB6AC;
            }

            .selection_item div i {
                display: none;
            }

            .selection_item.selected {
                color: #000;
            }

            .selection_item.selected div i {
                display: inline;
            }

            .selection_item span {
                display: flex;
                max-width: calc(100% - 50px);
            }

            .selection_item:hover div {
                background: #f3f3f3;
            }

            .ajax_hide {
                display: none;
            }

            #screening_results {
                min-height: 228px;
            }

            .alert.alert-warning {
                background: #ebc45f;
                color: #434C54;
                display: flex;
                align-items: center;
                padding: 10px 20px;
            }

            .alert.alert-warning i {
                margin-right: 15px;
                font-size: 24px;
                /*color: rgba(255,255,255,.9);*/
            }

            #selection_answer {
                display: none;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <script type="text/javascript">
            $(function(){
                $(".selection_item").click(function(){
                    $(".selection_item").removeClass("selected");
                    $(this).toggleClass('selected');
                });

                $('#screening_results').load('/compliance/heinens-2020/screening-incentive/compliance_programs?id=1492 #screening_results');

                $('#submit_selection').click(function(){
                    var selection = $('.selection_item.selected').attr('data-selection');

                    if (selection) {
                        $.ajax({
                            method: "GET",
                            data: {selection_response: selection},
                            url: "/compliance/heinens-2020/screening-incentive/compliance_programs?id=1581",
                            dataType: "json",
                            complete: function(){
                                location.href = "/compliance/heinens-2020/tobacco-affidavit/content/heinens-tobacco-affidavit";
                            }
                        });
                    } else {
                        alert("Please select an option below.");
                    }
                });
            });
        </script>

        <div id="selection_answer"><?= $selection?></div>

        <?php if ($load_selection || empty($selection)): ?>

            <?php if(empty($selection)): ?>
                <div class="alert alert-warning"><i class="fal fa-exclamation-circle"></i> <strong>You must complete this page before proceeding further.</strong></div>
            <?php endif;?>

            <p>Due to the COVID pandemic, Heinen’s will not conduct onsite biometric screenings in 2021. If you participated in biometric screenings in 2020 and are happy with the incentive percentage you earned at that time, there is no need to rescreen in 2021.</p>
            
            <p>If this is your first time enrolling in Heinen's Wellness Program as either an associate or spouse OR you would like to rescreen this year to improve your incentive percentage, you can do so by completing your screening at a Quest Lab or with your own primary care physician.</p>

            <p>Please note that if you completed a screening in 2020 and choose not to rescreen in 2021, you will still need to complete a new Health Risk Assessment and Tobacco/Nicotine Affidavit.</p>

            <p class="bold">Review your 2020 screening results below:</p>

            <div id="screening_results"></div>
            
            <p class="bold">Please confirm the option you choose below:</p>

            <div class="selection_item <?php if ($selection == 1) echo "selected" ?>" data-selection="1"><div><i class="fa fa-check"></i></div><span>1. I am happy with my 2020 results and accept the same results for 2021 for the following metrics: Body Mass Index (BMI), Total Cholesterol/HDL, Triglycerides/HDL, and A1C. I understand that I will need to complete a new Tobacco/Nicotine Affidavit in 2021 which may impact my overall percentage.</span></div>

            <div class="selection_item <?php if ($selection == 2) echo "selected" ?>" data-selection="2"><div><i class="fa fa-check"></i></div><span>2. I participated in 2020 but would like to rescreen to improve my results.</span></div>

            <div class="selection_item <?php if ($selection == 3) echo "selected" ?>" data-selection="3"><div><i class="fa fa-check"></i></div><span>3. I am brand new and need to complete a screening. I did not participate in a 2020 screening.</span></div>

            <div id="submit_selection" class="btn btn-primary">Submit</div>
        <?php else :?>

            <div class="letter">

                <img src="https://master.hpn.com/resources/10514/HeinensLogo.jpg" style="max-width: 200px; display: block; margin: auto;margin-top:20px; margin-bottom: 20px;">

                <div class="pad20">
                    <p>Dear <?php echo $user->first_name ?> <?php echo $user->last_name ?>,</p>

                    <p>You have selected option <strong><?= $selection?>. <?php if ($selection == 1) echo "I am happy with my 2020 results and accept the same results for 2021 for the following metrics: Body Mass Index (BMI), Total Cholesterol/HDL, Triglycerides/HDL, and A1C. I understand that I will need to complete a new Tobacco/Nicotine Affidavit in 2021 which may impact my overall percentage."; elseif ($selection == 2) echo "I participated in 2020 but would like to rescreen to improve my results."; elseif ($selection == 3) echo "I am brand new and need to complete a screening."; ?></strong>
                    </p>

                    <p>If you would like to change your selection you may do so on the <a href="/compliance/heinens-2020/screening-incentive/compliance_programs?id=1581&selection=true">Incentive Program Declaration Page</a></p>

                    <p>Let's get healthy, Heinen's! All associates and spouses can complete the Wellness Biometric Screening to earn up to a 50% contribution rate reduction for medical coverage in the 2021-2022 benefit year.</p>

                    <p>The overall percentage of the premium for both the associate and spouse will be averaged together to determine the couple's total percentage of premium reduction. For example, an associate achieving three (3) healthy ranges for 20% , and a spouse achieving one (1) healthy range for 5%, will receive a 12.5% premium incentive (20% + 5% = 25%/2 = 12.5%).</p>

                    <p>Please see the Program Guide for three (3) appeals options if you did not meet your healthy range goals.</p>

                    <p>Here are your results.</p>
                </div>

                <div>
                    <table id="heinens-table" class="collapsible-points-report-card">
                        <thead>
                        <tr>
                            <th class="name"></th>
                            <th class="points target">Earned</th>
                            <th class="points actual">Possible</th>
                            <th class="group-progress">Progress</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr class="picker open" id="core-actions">
                            <td class="name">
                                Premium Earned
                                <div class="triangle"></div>
                            </td>

                            <td class="points target"><strong><?= $premium_earned?></strong>%</td>

                            <td class="points actual warning"><strong>50</strong>%</td>

                            <td class="group-progress">
                                <div class="pgrs">
                                    <div class="bar success" style="width:<?= $premium_earned*2?>%;"></div>
                                </div>
                            </td>
                        </tr>

                        <tr class="details open" id="core-actions-details">
                            <td colspan="4">
                                <div>
                                    <table class="table table-condensed">
                                        <thead>
                                        <tr>
                                            <th>1. Health Power Assessment</th>
                                            <th class="text-center medium">Completed</th>
                                            <th class="text-center large">Link</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <tr>
                                            <td><span class="item-title">Complete Health Power Assessment Questionnaire (Required)</span></td>
                                            <?php if ($hra_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link">
                                                <a href="/compliance/heinens-2020/my-health/content/my-health">Take HPA</a><br>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <table class="table table-condensed">
                                        <thead>
                                        <tr>
                                            <th>2. Screening Metric</th>
                                            <th class="text-center">Employer Goal</th>
                                            <th class="text-center">Result</th>
                                            <th class="text-center medium">Status</th>
                                            <th class="text-center large">Premium Earned If Met</th>
                                        </tr>
                                        </thead>

                                        <tr>
                                            <td><span class="item-title subtitle">a. Body Mass Index (BMI)*</span></td>
                                            <td class="text-center"><i class="fs13 fal fa-less-than-equal"></i> 26.9</td>
                                            <td class="text-center"><?= $bmi_result?></td>
                                            <?php if ($bmi_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link">10%</td>
                                        </tr>

                                        <tr>
                                            <td><span class="item-title subtitle">b. Total Cholesterol/HDL</span></td>
                                            <td class="text-center"><i class="fs13 fal fa-less-than-equal"></i> 4.0</td>
                                            <td class="text-center"><?= $cholesterol_result?></td>
                                            <?php if ($cholesterol_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link">5%</td>
                                        </tr>

                                        <tr>
                                            <td><span class="item-title subtitle">c. Triglycerides/HDL</span></td>
                                            <td class="text-center"><i class="fs13 fal fa-less-than-equal"></i> 2.0</td>
                                            <td class="text-center"><?= $triglyceride_result?></td>
                                            <?php if ($triglyceride_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link">5%</td>
                                        </tr>

                                        <tr>
                                            <td><span class="item-title subtitle">d. A1C</span></td>
                                            <td class="text-center"><i class="fs13 fal fa-less-than-equal"></i> 5.6</td>
                                            <td class="text-center"><?= $ha1c_result?></td>
                                            <?php if ($ha1c_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link">10%</td>
                                        </tr>

                                        <tr>
                                            <td><span class="item-title subtitle">e. Tobacco/Nicotine</span></td>
                                            <td class="text-center">Negative</td>
                                            <td class="text-center"><?php if ($accepted) echo 'Positive'; elseif ($denied) echo 'Negative';?></td>
                                            <?php if ($accepted) :?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php elseif($denied): ?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center"></td>
                                            <?php endif; ?>
                                            <td class="action_link">20%</td>
                                        </tr>
                                    </table>
                                    <table class="table table-condensed">
                                        <thead>
                                        <tr>
                                            <th>3. Tobacco Affidavit</th>
                                            <th class="text-center medium">Completed</th>
                                            <th class="text-center large">Link</th>
                                        </tr>
                                        </thead>

                                        <tr>
                                            <td><span class="item-title">Complete Tobacco Affidavit</span></td>
                                            <?php if ($accepted || $denied) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link"><a href="/compliance/heinens-2020/tobacco-affidavit/content/heinens-tobacco-affidavit">Tobacco Affidavit</a></td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <?php if ($accepted): ?>
                                    <table class="table table-condensed">
                                        <thead>
                                        <tr>
                                            <th>4. Tobacco/Nicotine Alternative</th>
                                            <th class="text-center medium">Completed</th>
                                            <th class="text-center large">Link</th>
                                        </tr>
                                        </thead>

                                        <tr>
                                            <td><span class="item-title">If a tobacco user & would like the 20% premium incentive you must complete six (6) E-Learning Lessons from the Tobacco & Nicotine section</span></td>
                                            <?php if ($tobacco_status) :?>
                                                <td class="text-center correct"><i class="far fa-check"></i></td>
                                            <?php elseif ($tobacco_result > 0): ?>
                                                <td class="text-center"><?= $tobacco_result?> out of 6</td>
                                            <?php else: ?>
                                                <td class="text-center incorrect"><i class="far fa-times"></i></td>
                                            <?php endif; ?>
                                            <td class="action_link"><a href="/search-learn/elearning/content/9420?action=lessonManager&tab_alias=tobacco">Complete Lessons</a></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <?php endif;?>
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>

                    <div style="padding: 20px; padding-top: 0px;">
                        <p><span class="bund">RESULTS APPEALS PROCESS</span><br>If you would like to participate in the Appeals process, please take <a href="pdf/clients/heinens/Heinens_2021_Appeals_Form.pdf" target="_blank">this form</a> to be completed and signed by your primary care physician. You can fax the completed form or submit an electric copy which you can upload <a href="http://ehsupload.com/">here</a>.</p>
                        <p>*Waist measurement automatically corrects elevated BMI due to lean muscle mass, even if the individual fails the BMI goal. (Female less than or equal to 33 inches. Male less than or equal to 35 inches.)</p>
                    </div>
                </div>

            </div>
        <?php endif ?>

        <?php
    }
}