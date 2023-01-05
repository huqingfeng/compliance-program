<?php

class medpro2011ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2011';
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
    </style>
    <p>Hi <?php echo $_user->getFirstName() ?>,</p>
    <p>
        <!- To qualify for the preferred medical rate, you are required to complete
        the screening and health risk assessment. Green lights will appear when
        that requirement has been fulfilled. -->
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

class medpro2011ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new medpro2011ComplianceProgramReportPrinter();

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

        $requiredGroup = new ComplianceViewGroup('required', 'Required in order to receive $50.00 incentive');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        $hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($requiredGroup);


    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}