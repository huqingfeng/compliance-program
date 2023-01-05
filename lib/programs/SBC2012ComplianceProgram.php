<?php

class SBC2012ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);

        $coaching = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);

        $coaching->setEvaluateCallback(function (User $user) {
            require_once sfConfig::get('sf_root_dir').
                '/apps/frontend/modules/legacy/legacy_lib/lib/functions/getExtendedRiskForUser2010.php';

            $risk = getExtendedRiskForUser($user);

            return $risk['number_of_risks'] >= 4;
        });

        $required->addComplianceView($coaching);

        $this->addComplianceViewGroup($required);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SBC2012ComplianceProgramReportPrinter();
    }
}

class SBC2012ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->showNa = false;
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

    <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

    <p>Welcome to Statewide Benefit Cooperative Wellness Website!
    </p>

    <p><strong>Step 1</strong>- Complete your Health Risk Appraisal (HRA) at least one month prior
        to your health screening.</p>

    <p><strong>Step 2</strong>- Complete your on-site or on-demand health screening and
        consultation. </p>


    <p>The current requirements and your current status for each are summarized below.</p>


    <?php
    }
}