<?php

class Paragon2014ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2014';
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <script type="text/javascript">
        $(function(){
           $('#pageMessage div:eq(1) i').remove(); 
        });
    </script>
    
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
    </style>
    <p>Hi <?php echo $_user->getFirstName() ?>,</p>

    <p>Welcome to your Wellness Website - sponsored by PARAGON. This secure and personalized website provides you with free
        access to a variety of interactive programs, resources, and information – all designed to help you reach your personal
        wellness goals. Additionally, this site helps you track your annual wellness requirements via the “My Wellness Report Card” below.</p>

    <p>As a member of the St. Joseph County Health Plan, you have the opportunity to receive a
        significant discount on your 2015 health insurance deductible.</p>



        <p>PHA Blood Draw:   Schedule and participate in the annual Personal Health Assessment
            (PHA) at one of several county facility locations. The PHA is comprised of bio-screens
            and a fasting blood draw. PHA’s begin on Monday, December 1st.  After completing the blood
            draw, your lab results will be available on this site within 48 hours.  The blood draw is
            required in order to receive the deductible discount.
        </p>

    <p>
        On-line HRA Questionnaire:  Beginning December 1, 2014, securly log in to this site and complete the on-line
        Health Risk Assessment (HRA) questionnaire. The on-line HRA questionnaire takes about 15 – 20 minutes to complete.
        The HRA is optional, but highly encouraged.
    </p>

        <p>After you have completed both steps above, a comprehensive Health Profile Report will be mailed securely
            to you at your home address. You are encouraged to review the results of your profile with a health care
            professional. Keep in mind that you will have access to your results and report within this secure and personal
            Wellness Website all year.</p>

    <p>
        When it comes to your health, knowledge is power. Thank you for participating and good luck on your wellness journey.



    </p>





    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <br/>
    <p>
    </p>
    <?php
    }
}

class Paragon2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Paragon2014ComplianceProgramReportPrinter();

        $printer->filterComplianceViews(function (ComplianceViewStatus $status) {
            return $status->getStatus() != ComplianceStatus::NA_COMPLIANT;
        });

        return $printer;
    }

    public function getScreeningData(User $user)
    {
        $query = '
      SELECT cholesterol, cotinine
      FROM screening
      WHERE user_id = ?
      AND date BETWEEN ? AND ?
      ORDER BY date DESC
      LIMIT 1
    ';

        $db = Database::getDatabase();

        $db->executeSelect($query, array(
            $user->id,
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d')
        ));

        $row = $db->getNextRow();

        return $row;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $program = $this;

        $printer->addCallbackField('newest_cotinine_result', function (User $user) use ($program) {
            if($row = $program->getScreeningData($user)) {
                return (string) $row['cotinine'];
            } else {
                return '';
            }
        });

        $printer->addCallbackField('newest_screening_has_full_results', function (User $user) use ($program) {
            if($row = $program->getScreeningData($user)) {
                return trim($row['cholesterol']) ? 'Full' : 'Cotinine Only';
            } else {
                return 'No Screening';
            }
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', '');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, '2014-12-19');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        $hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);

        //$cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        //$cotinineView->setReportName('Cotinine');
        //$requiredGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($requiredGroup);


    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}