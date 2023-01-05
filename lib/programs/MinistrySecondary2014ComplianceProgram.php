<?php

/**
 * Contains all classes for Ministry's secondary compliance program. This is
 * a custom table format that is mostly override-driven but does integrate
 * with activity tracker and elearning among a few other views.
 *
 * This uses some views from the 2013 program because the code is unchanged.
 */

class MinistrySecondary2014FileAffidavitComplianceView extends MinistrySecondary2013FileAffidavitComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(329);
    }
}

class MinistrySecondary2014ComplianceProgram extends MinistrySecondary2013ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('date_of_birth', function (User $user) {
            return $user->date_of_birth;
        });

        return $printer;
    }
    
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MinistrySecondary2014Printer();
    }

    protected function configureViews()
    {
        parent::configureViews();

        // To change a description, find the view key in the 2013 program and add
        // it to the array below with a new description as the value.

        $miscInfo = array(

        );

        foreach($miscInfo as $viewName => $helpText) {
            $this->getComplianceView($viewName)->setAttribute('help_text', $helpText);
        }
    }

    protected function getFitnessGroup()
    {
        $group = new ComplianceViewGroup('fitness', ' Fitness Activities – 25 Points');
        $group->setPointsRequiredForCompliance(25);

        $group->addComplianceView($this->getOverrideView('fitness_non_pedo', 'Non-pedometer<br/> 360 or more minutes/month', 'Four', 5, 20, new Link('Update Your File', '/content/chp-document-uploader')));
        $group->addComplianceView($this->getStepsView('fitness_pedo', 'Pedometer<br/> 200,000 steps/month', 'Five', 5, 25, 328, 102, 200000));
        $group->addComplianceView($this->getOverrideView('fitness_community', 'Community Fitness Event', 'Twice', 5, 10, new Link('Update Your File', '/content/chp-document-uploader')));

        return $group;
    }

    protected function getLifeStyleGroup()
    {
        $group = parent::getLifeStyleGroup();

        $group->removeComplianceView('style_tobacco_program');

        return $group;
    }

    protected function getFileAffidavitView($name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $questionId)
    {
        $view = new MinistrySecondary2014FileAffidavitComplianceView($this->getStartDate(), $this->getEndDate(), $questionId, $pointsValue);

        $this->configureView($view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints);

        return $view;
    }
}

class MinistrySecondary2014Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $fitness = $status->getComplianceViewGroupStatus('fitness');
        $wep = $status->getComplianceViewGroupStatus('wep');
        $style = $status->getComplianceViewGroupStatus('style');
        ?>
    <style type="text/css">
        table.group tr th, table.group tr td {
            text-align:left;
            vertical-align:middle;
        }

        h3.title {
            color:#345A92;
            text-align:center !important;
            margin-top:10px;
            margin-bottom:5px;
        }

        table.group tr .subtitle {
            width:50px;
            text-align:center;
        }

        table.group tr td.answer {
            text-align:center;
        }

        table.table-really-condensed th, table.table-really-condensed td {
            padding:0px 2px;
        }

        table.group tr td.program {
            padding-left:32px;
            height:30px;
            width:300px;
        }

        table.group tr .action_links {
            text-align:center;
            width:200px;
        }

        table.group tr .sub-heading {
            padding-left:16px;
            text-align:left;
        }

        table.group {
            margin-bottom:10px;
            width:100%;
        }

        a.more-info-anchor {
            text-decoration:none;
            display:block;
            float:left;
            padding-right:20px;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::COMPLIANT) ?> {
            background-color:#E1FFDB;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::PARTIALLY_COMPLIANT) ?> {
            background-color:#FDFFDB;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::NOT_COMPLIANT) ?> {
            background-color:#FFDBDB;
        }
    </style>
       <p><a href="/resources/4624/Wellness-Activity-Rewards-FAQ-NCW-2014.pdf">Frequently Asked Questions</a></p>


        <p>
            <a href="/compliance_programs?id=241">
                View your 2014 Program
            </a>
        </p>


        <h3>Wellness Activity Rewards for 2015</h3>
    <!--<p>The maximum points available to be earned is 25 in each category and 75 total per program year. This program is available to benefits eligible employees only; spouses are not eligible to participate in this additional incentive program.</p>-->
    <p> When you earn 25 points in one of the categories shown
        (Fitness/Education/Lifestyle), you will receive $25 to purchase health
        and wellness products on the<em>A Healthier You</em> e-commerce site.</p>
    <ul>
        <li>Earn up to $75 per program year by accumulating 25 points in all 3
            wellness activity reward categories.
        </li>
        <li>All activities must be submitted no later then September 30, 2014.
        </li>
        <li>Rewards will be credited to your e-commerce account in January
            2015.
        </li>
        <li>Please note that per IRS regulations, cash awards such as these are
            taxable.
        </li>
        <li>These Wellness Activity Rewards are available to benefit eligible associates
            only; spouses are not eligible to participate in this reward
            program.
        </li>
        <li><span style="font-weight:bold;"></span>The
            <em>A Healthier You</em> store can
            be found at <a href="http://www.co-store.com/ahealthieryou">www.co-store.com/ahealthieryou</a>.
        </li>

    </ul>

    <h3 class="title"><?php echo $fitness->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <tr>

        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_non_pedo')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_pedo')) ?>
        <?php //$this->printViewStatus($status->getComplianceViewStatus('fitness_pedo_bonus')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_community')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $fitness->getComplianceViewGroup()
                ->getPointsRequiredForCompliance(); ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $fitness->getStatus()) ?>">
                <?php echo $fitness->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <h3 class="title"><?php echo $wep->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_wep')) ?>
        <tr>
            <th colspan="6" class="sub-heading">Online Learning</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_tobacco')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_alcohol_use')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_substance_abuse')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_healthy_weight')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_depression')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_nutrition')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_exercise')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_stress')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $wep->getComplianceViewGroup()
                ->getPointsRequiredForCompliance() ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $wep->getStatus()) ?>">
                <?php echo $wep->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <h3 class="title"><?php echo $style->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <tr>
            <th colspan="6" class="sub-heading">LifeStyle Improvement Programs
            </th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_stress')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_exercise')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_weight')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_alcohol')) ?>
        <?php //$this->printViewStatus($status->getComplianceViewStatus('style_tobacco_program')) ?>

        <tr>
            <th colspan="6" class="sub-heading">My Commitments</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_commitment')) ?>
        <tr>
            <th colspan="6" class="sub-heading">Other Initiatives</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_story')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_coach')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_chronic')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_weight_program')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $style->getComplianceViewGroup()
                ->getPointsRequiredForCompliance() ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $style->getStatus()) ?>">
                <?php echo $style->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <br/>
    <br/>

    <p>If you are unable to complete one of the activities listed above due to a
        medical condition, but would still like to participate, please contact
        your local Associate Health Office to discuss alternatives for your
        participation.</p>
    <?php
    }

    private function printViewStatus($status)
    {
        $view = $status->getComplianceView();
        $popoverId = sprintf('%s-popover', $view->getName());
        ?>
    <tr>
        <td class="program">
            <?php if($help = $view->getAttribute('help_text')) : ?>
            <a id="<?php echo $popoverId ?>" href="#" class="more-info-anchor"
                onclick="return false;">
                <i class="icon-info-sign"></i>
                <?php echo $view->getReportName(true) ?>
            </a>
            <script type="text/javascript">
                $(function () {
                    $("#<?php echo $popoverId ?>").popover({
                        title: <?php echo json_encode(preg_replace('|<br[ ]*/?>.*|', '', $view->getReportName())) ?>,
                        content: <?php echo json_encode($help) ?>,
                        trigger:'hover',
                        html:true
                    });
                });
            </script>
            <?php else : ?>
            <?php echo $view->getReportName(true) ?>
            <?php endif ?>
        </td>
        <td class="answer"><?php echo $view->getAttribute('times_per_year') ?></td>
        <td class="answer"><?php echo $view->getAttribute('points_value') ?></td>
        <td class="answer"><?php echo $view->getMaximumNumberOfPoints() ?></td>
        <td class="answer"><?php echo $status->getPoints() ?></td>
        <td class="action_links">
            <?php echo implode(' ', $view->getLinks()) ?>
        </td>
    </tr>
    <?php
    }
}