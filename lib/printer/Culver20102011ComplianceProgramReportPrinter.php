<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class Culver20102011ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $_user = Piranha::getInstance()->getUser();
        $_cr = Piranha::getInstance()->getContentReferencer();
        // print CSS
        $_cr->printContent('110284');


        $totalOneStatus = null;
        $totalTwoStatus = null;
        $totalOneComment = null;
        $totalTwoComment = null;

        $groupOne = $status->getComplianceViewGroupStatus('one');
        $groupTwo = $status->getComplianceViewGroupStatus('two');

        if($groupOne->getStatus() == ComplianceStatus::COMPLIANT) {
            $totalOneStatus = 'YES';
            $totalOneComment = 'Actions DONE for Premium Credit';
        } else if($groupOne->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
            $totalOneStatus = 'Not Yet';
            $totalOneComment = 'Actions NOT DONE for Premium Credit';
        } else {
            $totalOneStatus = 'No';
            $totalOneComment = 'Actions NOT DONE for Premium Credit';
        }

        if($groupTwo->getStatus() == ComplianceStatus::COMPLIANT) {
            $totalTwoStatus = 'YES';
            $totalTwoComment = 'HAS ENOUGH points for the $200 Flex Benefit';
        } else if($groupTwo->getPoints() > 0) {
            $totalTwoStatus = 'Not Yet';
            $totalTwoComment = '
        <div>NEEDS MORE points for the $200 Flex Benefit</div>
        <div style="font-style:italic;text-align:center;">If needed, use 2HIJ to earn enough points</div>
      ';
        } else {
            $totalTwoStatus = 'No';
            $totalTwoComment = '
        <div>NEEDS MORE points for the $200 Flex Benefit</div>
        <div style="font-style:italic;text-align:center;">If needed, use 2HIJ to earn enough points</div>
      ';
        }


        ?>
    <style type="text/css">
        .comply_with_smoking_hra_question td.links,
        .comply_with_blood_pressure_screening_test td.links,
        .comply_with_triglycerides_screening_test td.links,
        .comply_with_glucose_screening_test td.links,
        .comply_with_total_cholesterol_screening_test td.links,
        .comply_with_total_hdl_cholesterol_ratio_screening_test td.links,
        .comply_with_body_fat_bmi_screening_test td.links {
            border-left:0px;
            border-top:0px;
            border-bottom:0px;
        }

        .headerRow th, .headerRow td {
            font-size:10pt;
        }

    </style>
    <div class="pageHeading">Rewards/To-Do Summary Page</div>

    <p>Hello <?php echo $_user->getFullName(); ?>,</p>
    <p></p>
    <p>Welcome to your summary page for the 2010-2011 Culver Academies Wellness Incentives.

    <p>To receive the incentives, eligible employees and spouses MUST EACH take certain actions and meet the criteria
        specified below:</p>

    <ol>
        <li><u>Premium contribution credit</u>: Complete <strong><u>ALL</u></strong> core required actions by November
            30, 2010. By getting #1 done, those with medical benefits through Culver get the applicable lower Wellness
            Rate for premium contributions saving you over $300-$2800 this year depending on the benefits you elected.
            If not, your premium contribution doubles, costing you that much more.
        </li>
        <li><u>Flex benefit credit</u>: Earn at least 100 points by July 31, 2010. By getting #2 done, employees with
            medical benefits through Culver will receive a $200 contribution to their Flexible Benefit Account.
        </li>
    </ol>
    <p>Important:</p>
    <ul>
        <li>Number 1 (above) applies to each eligible employee and spouse with medical benefits through Culver. With
            employee/spouse coverage or employee/full family coverage, both the employee AND spouse must meet the
            requirements for #1.
        </li>
        <li>Number 2 only applies to each eligible employee, but not to a spouse who does not work at Culver.</li>
    </ul>
    <p></p>
    <p></p>
    <p style="text-align:center;">
        <a href="content/1175">Details about the 2010-2011 Wellness Rewards benefit and requirements</a>
    </p>
    <p></p>
    <p><strong>Update Notice</strong>: To get actions done and earn extra points click on the links below. If the points
        or status did not change for an item you are working on, you may need to go back and enter missing information
        or entries to earn more points. The status for wellness screening will not change until after your report is
        mailed. Thanks for your actions and patience!</p>

    <table class="phipTable">
        <tbody>
            <tr id="legend">
                <td colspan="6">
                    <div id="legendText">Legend</div>
                    <?php foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                                      ->getMappings() as $sstatus => $mapping) {
                    ?>
                    <?php if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT || $status->getComplianceProgram()
                        ->hasPartiallyCompliantStatus()
                    ) {
                        ?>
                        <div class="legendEntry">
                            <img src="<?php echo $mapping->getLight(); ?>" class="light"/>
                            =
                            <?php echo $mapping->getText(); ?>
                        </div>
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>

            <?php $groupNumber = 0; foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $groupNumber++; ?>
            <?php $group = $groupStatus->getComplianceViewGroup(); ?>

            <tr class="headerRow">
                <th>
                    <?php echo "<strong>{$groupNumber}</strong>".'. '.$group->getReportName(); ?>
                </th>

                <?php if(!$group->pointBased()) { ?>
                <td>Date Done</td>
                <td colspan="4">Links</td>
                <?php } else { ?>
                <td>Result</td>
                <td>Status</td>
                <td># Points Earned</td>
                <td># Points Possible</td>

                <td>Links</td>

                <?php } ?>

            </tr>

            <?php $number = 0; ?>
            <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) { ?>
                <?php $view = $viewStatus->getComplianceView(); ?>
                <tr class="<?php echo $view->getName(); ?>">
                    <td>
                        <strong><?php echo getLetterFromNumber($number++); ?></strong>. <?php echo $view->getReportName(); ?>
                    </td>
                    <td class="result"><?php echo $viewStatus->getComment(); ?></td>
                    <?php if(!$group->pointBased()) { ?>
                    <td class="links" colspan="4">
                        <?php foreach($view->getLinks() as $link) { ?>
                        <?php echo $link->getHTML(); ?>
                        <?php } ?>
                    </td>
                    <?php } else { ?>
                    <td class="status">
                        <?php
                        if(
                            $view instanceof CompleteRequiredELearningLessonsComplianceView ||
                            $view instanceof CulverCompleteAerobicExerciseComplianceView ||
                            $view instanceof CompletePreventionPhysicalExamComplianceView
                        ) {
                            ?><img src="/images/lights/whitelight.gif" class="light"/><?php
                        } else {
                            ?><img src="<?php echo $viewStatus->getLight(); ?>" alt="" class="light"/><?php
                        }
                        ?>
                    </td>
                    <td class="points"><?php echo $viewStatus->getPoints(); ?></td>
                    <td class="points"><?php echo $view->getMaximumNumberOfPoints(); ?></td>
                    <td class="links">
                        <?php foreach($view->getLinks() as $link) { ?>
                        <?php echo $link->getHTML(); ?>
                        <?php } ?>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>

            <?php } ?>

            <tr>
                <td style="text-align:right;">Total Points as of <?php echo date('m/d/Y'); ?> =</td>
                <td class="points"><?php echo $status->getPoints(); ?></td>
                <td class="points"><img src="/images/lights/whitelight.gif" class="light"/></td>
                <td></td>
                <td class="points"><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints(); ?></td>
                <td></td>
            </tr>
            <tr class="headerRow">
                <th><strong>3</strong>. Requirements, Deadlines & Status</th>
                <td>Status</td>
                <td colspan="4">Comments</td>
            </tr>
            <tr>
                <td style="text-align:right;">Premium Reward Status: 1AB by 11/30/<strong>2010</strong> ?</td>
                <td class="points"><?php echo $totalOneStatus; ?></td>
                <td colspan="4" class="points"><?php echo $totalOneComment; ?></td>
            </tr>
            <tr>
                <td style="text-align:right;">Flex Reward Status: ≥ 7 points by 7/31/<strong>2011</strong> ?</td>
                <td class="points"><?php echo $totalTwoStatus; ?></td>
                <td colspan="4" class="points"><?php echo $totalTwoComment; ?></td>
            </tr>
        </tbody>
    </table>

    <?php
    }
}

?>
