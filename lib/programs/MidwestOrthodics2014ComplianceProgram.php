<?php

class MidwestOrthodics2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MidwestOrthodics2014ComplianceProgramReportPrinter;
    }

    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $scrView = new CompleteScreeningComplianceView($start, $end);
        $scrView->setReportName('Wellness Screening');
        $scrView->emptyLinks();
        $group->addComplianceView($scrView);

        $hraView = new CompleteHRAComplianceView($start, $end);
        $hraView->setReportName('Health Risk Assessment');
        $group->addComplianceView($hraView);


        $this->addComplianceViewGroup($group);
    }
}

class MidwestOrthodics2014ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Report Card';
    }
    
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

    <p>Welcome to your Wellness Website! This site was developed not only to track your wellness completion, but also to be used as a great resource for health related topics and questions. We encourage you to explore the site. </p>

    <p><strong>Step 1</strong>- Complete your health screening.</span></p>

    <p><strong>Step 2</strong>- Complete your Health Risk Assessment (HRA) Questionnaire by June 30, 2015. <a href="/content/1006">Click here </a> to complete your HRA if you have not done so already. Your current status for each are summarized below.</span></p>

    <p></p>
    <?php
    }
}