<?php

class medpro2013ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2013';
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

    <p><a href="compliance_programs?id=100">Click here</a> for the 2011 report card.
    <p>
        <p><a href="compliance_programs?id=218">Click here</a> for the 2012 report card.


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

class medpro2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new medpro2013ComplianceProgramReportPrinter();
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

        $twentyelevencotinineView = new ComplyWithCotinineScreeningTestComplianceView('2011-01-01', '2011-12-31');
        $twentyelevencotinineView->setReportName('Cotinine 2011');
        $twentyelevencotinineView->setName('cotinine_2011');
        
        $requiredGroup->addComplianceView($twentyelevencotinineView);

        $twentytwelvecotinineView = new ComplyWithCotinineScreeningTestComplianceView('2012-01-01', '2012-12-31');
        $twentytwelvecotinineView->setReportName('Cotinine 2012');
        $twentytwelvecotinineView->setName('cotinine_2012');
        $twentytwelvecotinineView->setPostEvaluateCallback(array($this, 'resetNewCotinineFor2012'));
        $requiredGroup->addComplianceView($twentytwelvecotinineView);

        $newCotinineView = new ComplyWithCotinineScreeningTestComplianceView('2013-01-01', '2013-12-31');
        $newCotinineView->setReportName('Cotinine 2013');
        $newCotinineView->setName('cotinine_2013');
        $newCotinineView->setPostEvaluateCallback(array($this, 'resetNewCotinineFor2013'));

        $requiredGroup->addComplianceView($newCotinineView);

        $this->addComplianceViewGroup($requiredGroup);
    }
    
    public function resetNewCotinineFor2012($status, User $user)
    {
        $twentyelevencotinineView = new ComplyWithCotinineScreeningTestComplianceView('2011-01-01', '2011-12-31');
        $twentyelevencotinineViewStatus = $twentyelevencotinineView->getStatus($user);
        
        if($twentyelevencotinineViewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
    }    
    
    public function resetNewCotinineFor2013($status, User $user)
    {
        $twentyelevencotinineView = new ComplyWithCotinineScreeningTestComplianceView('2011-01-01', '2011-12-31');
        $twentyelevencotinineViewStatus = $twentyelevencotinineView->getStatus($user);
        
        $twentytwelvecotinineView = new ComplyWithCotinineScreeningTestComplianceView('2012-01-01', '2012-12-31');
        $twentytwelvecotinineViewStatus = $twentytwelvecotinineView->getStatus($user);
        
        if($twentyelevencotinineViewStatus->getStatus() == ComplianceStatus::COMPLIANT || $twentytwelvecotinineViewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}