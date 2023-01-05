<?php

class ShapePrinter extends CHPShapeComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
    <p style="font-weight:bold">
        The 2013-2014 Shape Family Wellness Requirements will be available to
        complete beginning on March 1, 2013.The HRA must be completed by May 1, 2013.
        After this time, you may continue to
        complete an HRA but it will not count toward your wellness incentive.
    </p>
    <?php
        parent::printHeader($status);
    }

    public function  printFooter(ComplianceProgramStatus $status)
    {
        ?>
    <p>
        * If your physician completes your BMI card, you are responsible to
        turn the card into Fitness Factory on time.
    </p>
    <p>
        ** This site will be updated first, by mid-March (to include historical
        information through 3/1), then updated again every two weeks thereafter.
    </p>
    <p>
        *** This requirement will be determined when the 2012-2013 program ends on April 30, 2013 and will be updated to reflect your status on May 1, 2013..
    </p>
    <!--<p>&#8224;This light indicator will change to reflect your individual personal status once the BMI/Tobacco Card is turned in.</p> -->

    <?php
    }
}

class Shape2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ShapePrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        // Full SSN over last 4
        $printer->setShowUserFields(null, null, null, false, true);
        $printer->setShowUserContactFields(true, null, null);

        $printer->addCallbackField('covered_social_security_number', function (User $user) {
            return $user->covered_social_security_number;
        });

        $printer->setShowTotals(false);

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('division', function (User $user) {
            return $user->division;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Done', '/images/shape/lights/done.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Done', '/images/shape/lights/incomplete.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/shape/lights/notdoneyet.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/shape/lights/notrequired.jpg')
        )));

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        $group = new ComplianceViewGroup('Requirements');

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('HRA (Health Risk Appraisal)<br />');
        $hra->setName('hra');
        $hra->setAttribute('more_info_link', '/content/11358');
        $hra->setAttribute('about', '');
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, '(Complete annually between March 1 and May 1)');
        $hra->setAttribute('did_this_link', 'mailto:ffactory@shape.com');
        $group->addComplianceView($hra);

        $tobacco = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('Tobacco Card / BMI');
        $tobacco->setName('tobacco_bmi');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, '(Complete annually between March 1 and May 1)');
        $tobacco->setAttribute('about', 'Sign tobacco form in presence of Fitness Factory staff or HR representative.<br/>BMI measured at the Fitness Factory, or with your Primary Care Physician.');
        $tobacco->setAttribute('more_info_link', '/content/11358');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $group->addComplianceView($tobacco);

        $phye = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $phye->setName('physical');
        $phye->setReportName('Preventive Physical **');
        $phye->setAttribute('about', 'Turn in the PHYSICIAN FORM, completed by your doctor to show physical completed.');
        $phye->setAttribute('more_info_link', '/content/11358');
        $phye->setAttribute('did_this_link', ' /resources/3770/Shape Verification Form-2013.pdf');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, '
        Employee Year. Show physical was completed sometime between May 1, 2011 and May 1, 2013.<br/>
        ');
        $phye->setEvaluateCallback(array($this, 'physicalIsRequired'));
        $group->addComplianceView($phye);

        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Disease Management');
        $disease->setName('disease_management');
        $disease->setAttribute('about', 'Only required if contacted directly by Priority Health.');
        $disease->setAttribute('more_info_link', '/content/11358');
        $disease->setAttribute('link_add', 'Contact Priority Health Shape Dedicated Customer Team 1.800.956.1954');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'Must comply with Priority Health guidelines only if contacted.');
        $group->addComplianceView($disease);

        $completeProgram = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $completeProgram->setReportName('Complete 2012-2013 Health Improvement Program ***');
        $completeProgram->setName('completed_program');
        $completeProgram->setAttribute('about', 'Only required if currently enrolled in a Health Improvement Program.');
        $completeProgram->setAttribute('more_info_link', '/content/11358');
        $completeProgram->setAttribute('did_this_link', 'mailto:ffactory@shape.com');
        $completeProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you qualified for a Health Improvement Program in 2012, it must be completed by May 1, 2013.');
        $group->addComplianceView($completeProgram);

        $enrollProgram = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2013 - 2014 Health Improvement Program');
        $enrollProgram->setName('enroll_program');
        // Remove the next line when Valerie says okay to go back to overrides for compliance status - RS 050812
        //$enrollProgram->setName('enroll_programFAKE');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater or you use tobacco products and wish to quit. ');
        $enrollProgram->setAttribute('more_info_link', '/content/11358');
        $enrollProgram->setAttribute('did_this_link', '/content/i_did_this');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'This is a Reminder Light ONLY. Lights will not change to reflect enrollment status until June 1, 2014');
        $group->addComplianceView($enrollProgram);

        $this->addComplianceViewGroup($group);
    }

    public function physicalIsRequired(User $user)
    {
        return $this->isRequired($user) && $user->relationship_type == Relationship::EMPLOYEE;
    }

    public function isRequired(User $user)
    {
        return $user->hiredate === null || $user->getDateTimeObject('hiredate')->format('U') < strtotime('2013-01-01');
    }
}