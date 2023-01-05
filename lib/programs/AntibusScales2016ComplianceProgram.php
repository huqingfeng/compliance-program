<?php

class AntibusScales2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, true, false, true, false, null, null, false);
        $printer->setShowUserLocation(false);
        $printer->setShowShowRelatedUserFields(false, false, false);
        $printer->setShowUserInsuranceTypes(false, false);
        $printer->setShowCompliant(true, false, false);
        $printer->setShowStatus(false,false,false);
        $printer->setShowPoints(false,false,false);
        $printer->setShowComment(false,false,false);


        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $hraStatus = $status->getComplianceViewStatus('hra');
            $screeningStatus  = $status->getComplianceViewStatus('screening');

            return array(
                'Hra Date'          => $hraStatus->getComment(),
                'Screening Date'          => $screeningStatus->getComment(),
            );
        });


        return $printer;
    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new AntibusScales2016ComplianceProgramReportPrinter;
    }

    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $scrView = new CompleteScreeningComplianceView($start, $end);
        $scrView->setName('screening');
        $scrView->setReportName('Wellness Screening');
        $scrView->emptyLinks();
        $group->addComplianceView($scrView);

        $hraView = new CompleteHRAComplianceView($start, $end);
        $hraView->setName('hra');
        $hraView->setReportName('Health Risk Assessment');
        $group->addComplianceView($hraView);


        $this->addComplianceViewGroup($group);
    }
}

class AntibusScales2016ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
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

    <p><strong>Step 2</strong>- Complete your Health Risk Assessment (HRA) Questionnaire by June 30, 2016. <a href="/content/1006">Click here </a> to complete your HRA if you have not done so already. Your current status for each are summarized below.</span></p>

    <p></p>
    <?php
    }
}