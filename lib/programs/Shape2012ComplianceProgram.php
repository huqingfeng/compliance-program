<?php

class ShapePrinter extends CHPShapeComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
    <p style="font-weight:bold">
        The 2012-2013 Shape Family Wellness Requirements will be available to
        complete beginning on March 1, 2012.The HRA must be completed before May 1, 2012.
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
        *** This requirement will be determined when the 2011-2012 program ends
        on April 30, 2012 and will be updated to reflect your status on May 1, 2012.
    </p>
    <!--<p>&#8224;This light indicator will change to reflect your individual personal status once the BMI/Tobacco Card is turned in.</p> -->

    <?php
    }
}

class ShapePhysicalExamComplianceView extends ComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultReportName()
    {
        return 'Physical Exam';
    }

    public function getDefaultName()
    {
        return 'physical_exam';
    }

    public function getStatus(User $user)
    {
        $dataQuery = '
      SELECT prevention_data.id,prevention_data.date
      FROM prevention_data
      LEFT JOIN prevention_codes ON prevention_codes.code = prevention_data.code
      WHERE date BETWEEN ? AND ?
      AND prevention_codes.type = 4
      AND user_id = ?
      ORDER BY date DESC
      LIMIT 1
    ';

        $_db = Database::getDatabase();

        $_db->executeSelect(
            $dataQuery,
            '2010-04-30',
            '2012-04-30',
            $user->getID()
        );

        $numberOfRecords = $_db->getNumberOfRows();

        if($numberOfRecords) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else if($user->getRelationshipType() == Relationship::EMPLOYEE) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            // If their hire date is after 12/02 (inclusive), they are NA.
            // Also, if they are not 19 at start of program, they are NA.

            $latestHireDateAllowedUnix = strtotime('2010-12-02');
            $usersHireDateField = $user->getField('hiredate');
            $usersDOBField = $user->getField('date_of_birth');

            if(!empty($usersHireDateField) && strtotime($usersHireDateField) >= $latestHireDateAllowedUnix) {
                // We have their hire date, if it is after the latest hiredate allowed return NA COMPLIANT
                return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
            } else if(!empty($usersDOBField)) {
                // We have their DOB, check if they are not 19 at start of program
                return new ComplianceViewStatus($this,
                    $user->getAge($this->getComplianceViewGroup()->getComplianceProgram()
                        ->getStartDate(), true) < 19 ? ComplianceStatus::NA_COMPLIANT : ComplianceStatus::NOT_COMPLIANT
                );
            } else {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
            }
        }
    }
}

class Shape2012ComplianceProgram extends ComplianceProgram
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

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->setShowTotals(false);

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
        $hra->setAttribute('about', '(formerly called HQ)');
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, '(Complete annually beginning March 1, but before May 1)');
        $hra->setEvaluateCallback(array($this, 'isRequired'));
        $hra->setAttribute('did_this_link', '/content/i_did_this');
        $group->addComplianceView($hra);

        $tobacco = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('Tobacco Card');
        $tobacco->setName('tobacco');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, '(Complete annually beginning March 1, but before May 1)');
        $tobacco->setAttribute('about', 'Sign form in presence of Fitness Factory staff or HR representative');
        $tobacco->setAttribute('more_info_link', '/content/11358');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $tobacco->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($tobacco);

        $bmi = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bmi->setReportName('BMI Card *');
        $bmi->setName('bmi');
        $bmi->setStatusSummary(ComplianceStatus::COMPLIANT, '(Complete annually beginning March 1, but before May 1)');
        $bmi->setAttribute('about', 'BMI measured at the Fitness Factory, or with your Primary Care Physician');
        $bmi->setAttribute('more_info_link', '/content/11358');
        $bmi->setAttribute('did_this_link', '/content/i_did_this');
        $bmi->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($bmi);

        $phye = new ShapePhysicalExamComplianceView();
        $phye->setName('physical');
        $phye->setReportName('Preventive Physical **');
        $phye->setAttribute('about', 'Receive physical from your Primary Care Physician.');
        $phye->setAttribute('more_info_link', '/content/11358');
        $phye->setAttribute('did_this_link', ' /resources/3770/Shape Verification Form-2012.pdf');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, '
      Every other year.<br/>
      <strong>Employees - Due before May 1 of 2013.</strong>
      <strong>Spouses - Due before May 1, 2012.</strong>
    ');
        $phye->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($phye);

        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Disease Management');
        $disease->setName('disease_management');
        $disease->setAttribute('about', 'Only required if contacted directly by Priority Health.');
        $disease->setAttribute('more_info_link', '/content/11358');
        $disease->setAttribute('link_add', 'Call Priority Health Customer Service at 616.956.1954 or 800.956.1954');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'Must comply with Priority Health guidelines only if contacted.');
        $disease->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($disease);

        $completeProgram = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $completeProgram->setReportName('Completed 2011-2012 Health Improvement Program ***');
        $completeProgram->setName('completed_program');
        $completeProgram->setAttribute('about', 'Only required if currently enrolled in a Health Improvement Program.');
        $completeProgram->setAttribute('more_info_link', '/content/11358');
        $completeProgram->setAttribute('did_this_link', '/content/i_did_this');
        $completeProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you qualified for a Health Improvement Program in 2011, it must be completed before May 1, 2012.');
        $completeProgram->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($completeProgram);

        $enrollProgram = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2012 - 2013 Health Improvement Program');
        $enrollProgram->setName('enroll_program');
        // Remove the next line when Valerie says okay to go back to overrides for compliance status - RS 050812
        //$enrollProgram->setName('enroll_programFAKE');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater or you use tobacco products and wish to quit. ');
        $enrollProgram->setAttribute('more_info_link', '/content/11358');
        $enrollProgram->setAttribute('did_this_link', '/content/i_did_this');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'This is a Reminder Light ONLY. Lights will not change to reflect enrollment status until June 1, 2012');
        $enrollProgram->setEvaluateCallback(array($this, 'isRequired'));
        $group->addComplianceView($enrollProgram);

        $this->addComplianceViewGroup($group);
    }

    public function isRequired(User $user)
    {
        return $user->hiredate === null || $user->getDateTimeObject('hiredate')->format('U') < strtotime('2012-01-27');
    }
}