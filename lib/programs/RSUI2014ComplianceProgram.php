<?php

class RSUI2014ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2014';
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <style type="text/css">
        .phipTable .resource {
            width:240px;
        }

        .phipTable .links {
            width:240px;
        }

        .phipTable .requirements {
            display:none;
        }

        .phipTable tr td.status, .phipTable tr td.links {
            vertical-align:top;
        }
    </style>

    <script type="text/javascript">
        $(function() {
            $('.phipTable tr:eq(3)').find('td.resource').html('3. <strong>Complete one of the following</strong><br/><br/><div style="padding-left:24px;">New Year Checkup<br/><br/>Dental Exam<br/><br/>Annual Eye Exam<br/><br/>Annual Physical</div>');
        });
    </script>

    <p>Hi <?php echo $_user->getFirstName() ?>,</p>
    <p>
        To earn the 2015 Preferred Medical Rate, you are required to complete the following during 2014:</p>
    <ul>
        <li>Biometric Screening (employee and covered spouse or domestic partner)</li>
        <li>Completion of Health Risk Appraisal (employee and covered spouse or domestic partner)</li>
        <li>Completion of a Preventative Visit (employee and covered spouse or domestic partner)</li>
    </ul>

           The deadline for completing the criteria is October 15, 2014.</p>

    <p>Green lights will appear when that requirement has been fulfilled. Please note this is your personal report card and will not reflect the status of your spouse or domestic partner.</p>

    </p>

    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <br/>

    <?php
    }
}

class RSUI2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new RSUI2014ComplianceProgramReportPrinter();

        $printer->filterComplianceViews(function (ComplianceViewStatus $status) {
            return $status->getStatus() != ComplianceStatus::NA_COMPLIANT;
        });

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        return new RSUI2013ComplianceProgramAdminReportPrinter();
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('Required for Preferred Medical Rate - To be completed in 2014');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $screeningView->setFilter(function($row) {
           return isset($row['creatinine']) && (bool) trim($row['creatinine']);
        });
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        //$hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);

        $oneView = new AttendAppointmentComplianceView($programStart, $programEnd);
        $oneView->setName('one');
        $oneView->bindTypeIds(array(43));
        $oneView->setReportName('Complete One Prevention');

        $oneView->addLink(new Link('I did this', '/content/chp-document-uploader'));

        $requiredGroup->addComplianceView($oneView);

        $this->addComplianceViewGroup($requiredGroup);
    }
}
