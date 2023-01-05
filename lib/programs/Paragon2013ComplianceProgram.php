<?php

class Paragon2013ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2013';
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

    <p>Welcome to your Wellness Website - sponsored by PARAGON.  This secure and personalized website provides
        you with free access to a variety of interactive programs, resources, and information – all designed
        to help you reach your personal wellness goals.  Additionally, this site helps you track your annual
        wellness requirements via the “My Wellness Report Card” below.</p>

    <p>As a member of the St. Joseph County Health Plan, you have the opportunity to receive a significant discount
        on your 2014 health insurance deductible.  In order to qualify for the discount, any adult member (non-child)
        insured on the plan is required to complete the following:</p>



        <p>
        <strong>Step 1 - On-line HRA Questionnaire:</strong>NEW this year. Beginning October 21, 2013, you can access this site, log in
            securely, and complete the on-line HRA questionnaire.  The on-line HRA questionnaire takes
            about 15 – 20 minutes and needs completed by December 31st,  2013 in order to qualify for the deductible reduction.
        </p>

    <p>
        <strong>Step 2 - PHA Blood Draw:</strong>Schedule and participate in the annual Personal Health Assessment
        (PHA) at one of several county facility locations.   The PHA is comprised of bio-screens and a fasting blood draw.
        PHA’s begin on Monday, October 28th.  You must schedule and complete the PHA by Friday, November 15th 2013.
        After completing the blood draw, your lab results will be available on this site within 48 hours.

    </p>
        <p>After you have completed both steps, a comprehensive Health Profile Report will be mailed securely
            to you at your home address.  You are encouraged to review the results of your profile with a health
            care professional.  Keep in mind that you will have access to your results and report within this
            secure and personal Wellness Website all year.</p>

    <p>
        When it comes to your health, knowledge is power.  Thank you for participating and good luck on your wellness journey.



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

class Paragon2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Paragon2013ComplianceProgramReportPrinter();

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

        $hraView = new CompleteHRAComplianceView($programStart, '2013-12-19');
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