<?php

class Medpro2016ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2016';
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

        <p><a href="compliance_programs?id=100">Click here</a> for the 2011 report card.</p>
        <p><a href="compliance_programs?id=218">Click here</a> for the 2012 report card.</p>
        <p><a href="compliance_programs?id=273">Click here</a> for the 2013 report card.</p>
        <p><a href="compliance_programs?id=372">Click here</a> for the 2014 report card.</p>
        <p><a href="compliance_programs?id=512">Click here</a> for the 2015 report card.</p>

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

class Medpro2016ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Medpro2016ComplianceProgramReportPrinter();
    }

    public function getScreeningData(User $user)
    {
        $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
            $user,
            new DateTime($this->getStartDate('Y-m-d')),
            new DateTime($this->getEndDate('Y-m-d')),
            array('require_complete' => false, 'merge' => true, 'fields' => array('cholesterol', 'cotinine'))
        );

        return $screening;
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

        $cotinine2011View = new ComplyWithCotinineScreeningTestComplianceView('2011-01-01', '2011-12-31');
        $cotinine2011View->setReportName('Cotinine 2011');
        $cotinine2011View->setName('cotinine_2011');
        $requiredGroup->addComplianceView($cotinine2011View);

        $cotinine2012View = new ComplyWithCotinineScreeningTestComplianceView('2012-01-01', '2012-12-31');
        $cotinine2012View->setReportName('Cotinine 2012');
        $cotinine2012View->setName('cotinine_2012');
        $cotinine2012View->setPostEvaluateCallback(
            $this->setNaIfCompliant(array($cotinine2011View))
        );

        $requiredGroup->addComplianceView($cotinine2012View);

        $cotinine2013View = new ComplyWithCotinineScreeningTestComplianceView('2013-01-01', '2013-12-31');
        $cotinine2013View->setReportName('Cotinine 2013');
        $cotinine2013View->setName('cotinine_2013');
        $cotinine2013View->setPostEvaluateCallback(
            $this->setNaIfCompliant(array($cotinine2011View, $cotinine2012View))
        );
        $requiredGroup->addComplianceView($cotinine2013View);

        $cotinine2014View = new ComplyWithCotinineScreeningTestComplianceView('2014-01-01', '2014-12-31');
        $cotinine2014View->setReportName('Cotinine 2014');
        $cotinine2014View->setName('cotinine_2014');
        $cotinine2014View->setPostEvaluateCallback(
            $this->setNaIfCompliant(array($cotinine2012View, $cotinine2013View))
        );
        $requiredGroup->addComplianceView($cotinine2014View);

        $cotinine2015View = new ComplyWithCotinineScreeningTestComplianceView('2015-01-01', '2015-12-31');
        $cotinine2015View->setReportName('Cotinine 2015');
        $cotinine2015View->setName('cotinine_2015');
        $cotinine2015View->setPostEvaluateCallback(
            $this->setNaIfCompliant(array($cotinine2013View, $cotinine2014View))
        );
        $requiredGroup->addComplianceView($cotinine2015View);

        $newCotinineView = new ComplyWithCotinineScreeningTestComplianceView('2016-01-01', '2016-12-31');
        $newCotinineView->setReportName('Cotinine 2016');
        $newCotinineView->setName('cotinine_2016');
        $newCotinineView->setPostEvaluateCallback(
            $this->setNaIfCompliant(array($cotinine2011View, $cotinine2012View, $cotinine2013View, $cotinine2014View, $cotinine2015View))
        );

        $requiredGroup->addComplianceView($newCotinineView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function setNaIfCompliant(array $views)
    {
        return function($status, User $user) use($views) {
            $compliant = false;

            foreach($views as $view) {
                if($view->getStatus($user)->isCompliant()) {
                    $compliant = true;

                    break;
                }
            }

            if($compliant) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        };
    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}
