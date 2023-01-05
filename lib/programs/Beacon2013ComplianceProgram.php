<?php

class Beacon2013ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowStatus(false, false, false);
        $printer->setShowText(true, true, true);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Met Standard', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partial Credit', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('No Points/Not Complete', '/images/ministryhealth/redblock1.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('No Result', 'images/ministryhealth/whiteblock.jpg')
        )));
        
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);


        $this->addComplianceViewGroup($required);


    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Beacon2013ComplianceProgramReportPrinter();
    }
}

class Beacon2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status) {
        $this->pageHeading = 'My Health Compass';
        parent::printReport($status);
    }
    
    public function printHeader(ComplianceProgramStatus $status)
    {
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


    <p>Welcome: <strong><?php echo $status->getUser()->getFullName(); ?></strong></p>

    <p><i>Using a shared computer?</i>
     <strong>If you are not <?php echo $status->getUser()->getFullName(); ?>, please click <a
                        href="/logout">here</a>.</strong></p>


    <p> <a href="<?php echo url_for('/compliance_programs?id=268') ?>">2014 Incentive Status</a></p>

    <p>Welcome to Beacon Memorial Health System Wellness Website! This site was developed not only to track your wellness requirements, but also to be used as a great resource for health related topics and questions. We encourage you to explore the site while also fulfilling your requirements. By completing the following steps in 2013 you will fulfill your requirements to participate in the Health Plan.
    </p>

    <p><strong>Step 1</strong>- Complete your Virtual Wellness Screening. Screenings are scheduled November 1 - January 31, 2014.</p>

    <p><strong>Step 2</strong>- Complete your Health Risk Assessment. <a href='/content/1006'>Click here </a>to complete your Questionnaire if you haven’t already done so.</p>
    <p>The current requirements and your current status for each are summarized below.</p>


    <?php
    }
}