<?php

class FTEAutomotiveComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new FTEAutomotiveComplianceProgramReportPrinter;
    }

    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $scrView = new CompleteScreeningComplianceView($start, $end);
        $scrView->setReportName('Wellness Screening');
        $group->addComplianceView($scrView);

        $hraView = new CompleteHRAComplianceView($start, $end);
        $hraView->setReportName('Health Risk Assessment');
        $group->addComplianceView($hraView);

        $cot = new ComplyWithCotinineScreeningTestComplianceView($start, $end);
        $cot->setReportName('Tobacco User (Cotinine)');
        $group->addComplianceView($cot);

        $this->addComplianceViewGroup($group);
    }
}

class FTEAutomotiveComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $user = sfContext::getInstance()->getUser()->getUser();

        ?>
    <style type="text/css">
        #overviewCriteria {
            width:100%;
            border-collapse:collapse;
        }

        #overviewCriteria th {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
            font-size:11pt;
            padding:5px;
        }

        #overviewCriteria td {
            width:33.3%;
            vertical-align:top;
        }
    </style>

    <p><?php echo sprintf('Hello %s,', $user->getFullName()) ?></p>

    <p>Welcome to The FTE automotive Wellness Website! This site was developed
        not only to track your wellness requirements, but also to be used as a
        great resource for health related topics and questions. We encourage you
        to explore the site while also fulfilling your requirements. By
        completing the following steps in 2011 you will receive a $30.00 per month credit ($360 per year)
        beginning 1/1/2012.</p>

    <p><strong>Step 1 - </strong>Complete your on-site health screening or
        on-demand screening and HRA questionnaire. Screenings are scheduled in
        October.</p>

    <p><strong>Step 2 - </strong>Receive additional credit for being a non-tobacco user. Cotinine testing
        will be included in your lab tests to determine tobacco status.</p>



    <p>The current requirements and your current status for each are summarized below.</p>
    <?php
    }
}