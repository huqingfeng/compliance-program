<?php

class MinistryHealth2011Printer extends CHPComplianceProgramReportPrinter
{
    public function  __construct()
    {
        $this->page_heading = 'My 2011 Incentive Report card';
    }

    public function printClientMessage()
    {
        $user = Piranha::getInstance()->getUser();
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Sitemap'));
        ?>
    <p>
        <a href="/content/802"><?php echo get_sitemap_title('You have %USER_UNREAD_MESSAGES_TEXT%') ?></a>
    </p>
    <!--<p>
     <a href="<?php //echo url_for('compliance_programs/index?id=97') ?>"> 
        2012 Program
      </a>
    </p> This will be turned on 2/1/11-->
    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <p>
        <strong>2011 Rewards for “A Healthier You” Wellness Program Participation</strong>
    </p>
    <p>
        Employees enrolled in the medical plan who participated in at least one
        quarter of Tangerine Weight Management in 2010 (weighed-in at the beginning
        and end of a quarter), and had their biometric data collected by having
        their cholesterol, glucose and blood pressure measured by Employee Health,
        before October 1, 2010, will receive a $10 per pay period contribution to
        their 2011 medical plan contribution rates.
    </p>
    <br/>
    <p>
        <strong>2011 Rewards for Completing the HRA by February 28, 2011</strong>
    </p>
    <p>
        Employees enrolled in a Ministry medical plan who complete their baseline
        HRA between January 1, 2011 and February 28, 2011, will receive a $5 per
        paycheck contribution to their medical plan rates starting in April 2011
        through the remainder of the calendar year.
    </p>
    <div>
        <ul>
            <li>
                Employees who participated in both the Tangerine program and
                biometric screening program before October 1, 2010, and complete the
                baseline HRA during the first two months of 2011, will receive an
                additional $5 contribution to their medical plan rates for a total of
                $15 per paycheck premium discount.
            </li>

            <li>
                If you did not participate in the 2010 wellness program
                (Tangerine and biometrics), this is your opportunity to receive a $5
                per paycheck contribution toward your medical plan rates beginning in
                April 2011 and continuing through the end of the year.
            </li>
            <li>
                If you will be paying the tobacco surcharge rates, this gives you the
                opportunity to receive a $5 per pay period contribution to your medical
                plan rates to reduce that surcharge beginning in April 2011 through the
                end of the year.
            </li>
        </ul>
    </div>
    <p>
        Though there are currently no financial rewards for non-medical plan
        participants, we encourage all benefit eligible employees to take advantage
        of the new Circle Wellness HRA tool.
    </p>
    <?php
    }
}

class MinistryHealthComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('job_title', function (User $user) {
            return $user->getJobTitle();
        });

        $printer->addCallbackField('employee-health-homebase', function (User $user) {
            return (string) $user->getUserAdditionalFieldBySlug('employee-health-homebase');
        });

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MinistryHealth2011Printer();
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $bio = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bio->setReportName('2010 Wellness Program');
        $bio->setStatusSummary(ComplianceStatus::COMPLIANT, 'Biometric & Tangerine Participation');

        $bio->setName('screening');
        $requiredGroup->addComplianceView($bio);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Complete', '/content/989'));
        $hraView->setReportName('Baseline HRA');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Health Risk Assessment (HPA)');
        $hraView->setName('hra');
        $requiredGroup->addComplianceView($hraView);

        //$tob = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        //$tob->setReportName('Tobacco Certification');
        //$tob->addLink(new Link('Alternative', '/content/12088'));
        //$tob->setName('tobacco');
        //$requiredGroup->addComplianceView($tob);

        $this->addComplianceViewGroup($requiredGroup);
    }
}