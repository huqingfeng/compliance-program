<?php

class Taubman2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Taubman2013ComplianceProgramReportPrinter;
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

        $consultation = new CompletePrivateConsultationComplianceView($start, $end);
        $consultation->setReportName('Private Consultation');
        $group->addComplianceView($consultation);

        $this->addComplianceViewGroup($group);
    }
}

class Taubman2013ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
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

    <p>Welcome to The Taubman Company Wellness Website! This site was developed not only to track your wellness requirements,
        but also to be used as a great resource for health related topics and questions. We encourage you to explore the site
        while also fulfilling your requirements. By completing the following steps in 2013 you will receive a $250.00
        wellness credit in January 2014.</p>

    <p><strong>Step 1</strong>- Complete your health screening and HRA questionnaire. Screenings are scheduled at BHO in September & October. On-demand
        & Physician Screenings are available beginning in June.  <span style="font-weight:bold; color:#FF0000">Packet requests for on-demand & physician screenings must be made by calling the
        Wellness Hotline at 866-682-3020 x204 by 10/25/2013. The deadline of completing your on-demand or physician screening and HRA is 11/1/2013.</span></p>

    <p><strong>Step 2</strong>- Complete your on-site or web/telephone consultation. Consultations will be scheduled in November at BHO. Web/Phone consultation
        appointments can be made once you have received your report in the mail at home. <span style="font-weight:bold; color:#FF0000">Sign up for a web/phone consultation by 11/15/2013 by calling the Wellness
        Hotline at 866-682-3020 x204. Consultations must be completed by 11/29/2013.</span></p>

    <p>The current requirements and your current status for each are summarized below.</p>
    <?php
    }
}