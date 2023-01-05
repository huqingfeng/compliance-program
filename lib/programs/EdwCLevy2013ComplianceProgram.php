<?php

class EdwCLevy2013ComplianceProgram extends ComplianceProgram
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
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching->setName('coaching');
        $coaching->setReportName('Graduate From Coaching Session');
        $required->addComplianceView($coaching);

        $this->addComplianceViewGroup($required);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new EdwCLevy2013ComplianceProgramReportPrinter();
    }
}

class EdwCLevy2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
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

    <p> <a href="<?php echo url_for('/compliance_programs/?id=194') ?>">2012 Incentive Status</a></p>

    <p>Welcome to The Edw. C. Levy Co. Wellness Website! This site was developed not only to track
        your wellness participation requirements, but also to be used as a great resource for health-related
        topics and questions. We encourage you to explore the site while also fulfilling your
        requirements. Employees and Spouses must participate in the following steps if you are enrolled
        in the Levy Medical Plan or beginning January 1, 2014 you and family will be enrolled in the Standard Plan
        (this plan has higher deductibles, higher co-pays, higher out-of-pocket costs and higher prescription co-pays).
    </p>

    <p><strong>Step 1</strong>- Complete your Health Risk Appraisal (HRA) at least one week prior to your
        health screening.</p>

    <p><strong>Step 2</strong>- Complete your on-site or on-demand health screening and
        consultation. Screenings are scheduled April through June, see your Wellness
        Coordinator for your location's date(s) and details.</p>

    <p><strong>Step 3</strong>- Based on your risk factors, you may be enrolled in the Personal
        Coaching Sessions. You will be notified at the time of your on-site
        screening, or after your on-demand screening, if you are enrolled for
        coaching. If enrolled you MUST participate in the 4 quarterly coaching
        sessions to be compliant.</p>

    <p>The current requirements and your current status for each are summarized below.</p>


    <?php
    }
}