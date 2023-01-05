<?php

class IntegrityAerospaceGroup2014BFMapper extends ComplianceStatusPointMapper
{
    public function __construct()
    {

    }

    public function getMaximumNumberOfPoints()
    {
        return 4;
    }

    public function getPoints($status)
    {
        return $status;
    }

    private $mapping;
}

class IntegrityAerospaceGroup2014ComplyWithBodyFatBMIScreeningTestComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
{
    public function getStatusSummary($status)
    {
        return sprintf(
            'Body Fat: %s, BMI: %s',
            $this->bodyFatView->getStatusSummary($status),
            $this->bmiView->getStatusSummary($status)
        );
    }
}

class IntegrityAerospaceGroup2014ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, true, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new IntegrityAerospaceGroup2014Printer();
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);

        return $printer;
    }

    public function getSpouseView()
    {
        return new RelatedUserCompleteComplianceViewsComplianceView(
            $this,
            array('complete_hra', 'complete_screening'),
            Relationship::get()
        );
    }

    public function loadEvaluators()
    {
        $employeeOnlyGroup = $this->getComplianceViewGroup('required_employees');

        $employeeOnlyCallback = function(User $user) {
            return $user->relationship_type == \Relationship::EMPLOYEE;
        };

        $points = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), $this->getEndDate()), array('requirements'), 9);
        $points->setName('points');
        $points->setReportName('Biometric Points');
        $points->setStatusSummary(ComplianceStatus::COMPLIANT, 'Earn 9 or more biometric points');
        $points->setEvaluateCallback($employeeOnlyCallback);

        $employeeOnlyGroup->addComplianceView($points);

        $spouseView = $this->getSpouseView();
        $spouseView->setEvaluateCallback($employeeOnlyCallback);

        $employeeOnlyGroup->addComplianceView($spouseView);
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT     => new ComplianceStatusMapping('Compliant', '/images/lights/greenlight.gif'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT  => new ComplianceStatusMapping('Not Required', '/images/lights/blacklight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $preventionEventGroup = new ComplianceViewGroup('Required');


        $hraView = new CompleteHRAComplianceView($programStart, '2013-11-30');
        $hraView->setReportName('Health Risk Assessment(HRA)');
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, '2013-11-30');
        $screeningView->setReportName('On-site or On-demand Health screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new link('Results', '/content/989'));
        $preventionEventGroup->addComplianceView($screeningView);

        $coachingView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);       
        $coachingView->setName('coaching');
        $coachingView->setReportName('Health Coaching');
        $preventionEventGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $employeeesGroup = new ComplianceViewGroup('required_employees', 'Required for Employees');

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $smokingView->addLink(new link('Complete Affidavit with HR', '/content/989'));
        $smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Negative (Cotinine)');
        $smokingView->setEvaluateCallback(function(User $user) {
            return false;
            //return $user->relationship_type == \Relationship::EMPLOYEE;
        });
        $employeeesGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($employeeesGroup);

        $biometricsGroup = new ComplianceViewGroup('requirements', 'Biometrics');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);

        $this->configureViewForElearningAlternative($bloodPressureView, 'required_bloodpressure');

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $this->configureViewForElearningAlternative($triglView, 'required_triglycerides');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 64 or ≥ 100');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');

        $this->configureViewForElearningAlternative($glucoseView, 'required_bloodsugar');

        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $this->configureViewForElearningAlternative($cholesterolView, 'required_totalcholesterol');

        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $this->configureViewForElearningAlternative($totalHDLRatioView, 'required_totalhdlratio');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new IntegrityAerospaceGroup2014ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new IntegrityAerospaceGroup2014BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat/BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $bodyFatBMIView->setUseHraFallback(true);

        // We have to reset start/end dates so that overrides work - the overrides are applied to the containing class
        // but won't bve to this bmi/body fat.

        $bodyFatBMIView->setStartDate($programStart);
        $bodyFatBMIView->setEndDate($programEnd);

        $this->configureViewForElearningAlternative($bodyFatBMIView, 'required_bmi');

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    private function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
            $view = $status->getComplianceView();
            $viewPoints = $status->getPoints();

            $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();

            if($status->getAttribute('has_result') && $viewPoints < $maxPoints) { 
                $endDate = strtotime($user->hiredate) >= strtotime('2014-01-01') ? '2014-09-30' : '2013-12-31';
                                
                $limit = 2 * ($maxPoints - $viewPoints);

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $endDate, $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->useAlternateCode(true);

                $status->getComplianceView()->addLink(new Link('Complete ELearning', "/content/9420?action=lessonManager&tab_alias={$alias}&limit={$limit}"));

                $elearningStatus = $elearningView->getStatus($user);

                $numberCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

                $extraPoints = (int)($numberCompleted / 2);

                $status->setPoints($viewPoints + $extraPoints);
            }
        });
    }

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');

        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBodyFatScreeningTestComplianceView'
        );

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');


        return $view;
    }
}

class IntegrityAerospaceGroup2014Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function pointBasedViewStatusMatchesMapping($viewStatus, $mapping)
    {
        if($viewStatus->getComplianceView()->getName() == 'bf_bmi') {
            return $viewStatus->getPoints() == $mapping;
        } else {
            return parent::pointBasedViewStatusMatchesMapping($viewStatus, $mapping);
        }
    }

    protected function getStatusMappings(ComplianceView $view)
    {
        $mappings = parent::getStatusMappings($view);

        if($view->getName() == 'bf_bmi') {
            return array(
                4 => $mappings[ComplianceStatus::COMPLIANT],
                3 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                2 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                1 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                0 => $mappings[ComplianceStatus::NOT_COMPLIANT]
            );
        } else {
            return $mappings;
        }
    }

    public function printClientNote()
    {
        ?>
        <p style="margin-top:20px;font-size:smaller;">*If an onsite screening is not available at your location, you will receive a packet from HR to bring to your physician to have your labs done.</p>

        <p style="margin-top:20px;font-size:smaller;">**If contacted for coaching, employees and spouses must complete two coaching calls by May 30, 2014.</p>


        <p style="margin-top:20px;font-size:smaller;">Pregnant? Are you pregnant or 6 months postpartum? You may complete a <a href="/resources/4302/IAGPregnException 051913.pdf">Pregnancy Exception Form </a> and submit it to Circle Wellness to obtain credit for the body measurements (BMI and Body Fat).</p>


    <?php
    }

public function printClientMessage()
{
    $program = $this->status->getComplianceProgram();

    if($this->status->getComplianceViewGroupStatus('requirements')->getPoints() >= 9) {
        foreach($program->getComplianceViewGroup('requirements')->getComplianceViews() as $view) {
            $view->emptyLinks();
        }
    }

    $spouseStatus = $program->getSpouseView()->getMappedStatus($this->status->getUser());

    $spouseViewStatuses = $spouseStatus->getAttribute('compliance_view_statuses', array());

    $spouseHraLight = isset($spouseViewStatuses['complete_hra']) ? $spouseViewStatuses['complete_hra']->getLight() : '/images/lights/blacklight.gif';
    $spouseScrLight = isset($spouseViewStatuses['complete_screening']) ? $spouseViewStatuses['complete_screening']->getLight() : '/images/lights/blacklight.gif';
    $coachingLight = isset($spouseViewStatuses['coaching']) ? $spouseViewStatuses['coaching']->getLight() : '/images/lights/blacklight.gif';

    ?>
    <script type="text/javascript">
        $(function() {
            $('.totalRow.group-requirements').nextAll().each(function() {
                $(this).find(':nth-child(1)').attr('colspan', 2);
                $(this).find(':nth-child(2)').remove();
                $(this).find(':nth-child(2)').html('');
                $(this).find(':nth-child(4)').attr('colspan', 2);
                $(this).find(':nth-child(5)').remove();
            });

            $('.headerRow.group-prevention-event td:nth-child(2)').html('');
            $('.view-complete_hra .requirements').html('');
            $('.view-complete_screening .requirements').html('');
            $('.view-comply_with_cotinine_screening_test .links').html('Complete Affidavit with HR');
            $('.view-comply_with_cotinine_screening_test .status').html('To be populated later in the year');

            // Add Spouse column after Your Status for the first group

            $('tr.group-required td:eq(2)').html("Spouse's Status");
            $('tr.view-complete_hra td:eq(3)').addClass('status').html('<img class="light" alt="" src="<?php echo $spouseHraLight ?>" />');
            $('tr.view-complete_screening td:eq(3)').addClass('status').html('<img class="light" alt="" src="<?php echo $spouseScrLight ?>" />');
            $('tr.view-coaching td:eq(3)').addClass('status').html('<img class="light" alt="" src="<?php echo $coachingLight ?>" />');

            <?php if($this->status->getComplianceViewStatus('coaching')->getStatus() == ComplianceStatus::NA_COMPLIANT) : ?>
                $('tr.view-coaching .requirements').html('Not Required');
            <?php else : ?>
                $('tr.view-coaching .requirements').html('Required');
            <?php endif ?>

            <?php if($this->status->getUser()->relationship_type == \Relationship::SPOUSE) : ?>
                $('.group-required-employees').hide();
                $('.view-comply_with_cotinine_screening_test').hide();
                $('.view-points').hide();
            <?php endif ?>
        });
    </script>
    <style type="text/css">
        .totalRow.group-campaigns {
            display:none;
        }

        .headerRow.group-campaigns {
            border-top: 2px solid #D7D7D7;
        }

        .view-bf_bmi.statusRow4 {
            background-color:#BEE3FE;
        }

        .view-bf_bmi.statusRow3 {
            background-color:#DDEBF4;
        }

        .view-bf_bmi.statusRow2 {
            background-color:#FFFDBD;
        }

        .view-bf_bmi.statusRow1 {
            background-color:#FFDC40;
        }

        .view-bf_bmi.statusRow0 {
            background-color:#FF6040;
        }

        .view-comply_with_cotinine_screening_test .requirements, .requirements{
            text-align: center;
        }
    </style>
    <p>
        <style type="text/css">
            #legendEntry2 {
                display:none;
            }
        </style>
    </p>


    <p>
        <a href="/compliance_programs/index?id=225">
            View your 2013 Program
        </a>
    </p>
    <p>
        In an effort to facilitate improvement in our health and well‐being as a company, Integrity Aerospace Group is making some changes to our wellness program. In order to qualify for the $600 incentive, <strong>Employees</strong> who are on the medical plan and are participating in wellness must meet 3 objectives to qualify for the 2014 incentive (unless contacted for health coaching**). <strong>Spouses</strong> on the medical plan will only be required to meet requirements 1 & 2 to qualify for the 2014 incentive (unless contacted for health coaching**).
    </p>

    <p>
    <ol>
        <li>
            Complete an online Health Risk Assessment by <strong></strong>November 30, 2013</strong>.
        </li>
        <li>
            Complete an onsite Health Screening by <strong>November 30, 2013.*</strong>.
        </li>
        <li>
            <strong>Employees only:</strong> Earn a total of 9 or more points on the report card or satisfy the reasonable alternative.</strong>
        </li>




    </ol>
    </p>
    <p>
        Once your lab values are showing on your report card, a reasonable alternative will be offered if you weren’t able to achieve these requirements. You will have a choice of two reasonable alternatives:</p>
    <p>
    <ul>
        <li>
            Bring a <a href="/resources/4301/IAGPhysicianExceptionForm051613.pdf">Physician Exception Form</a> to your physician to complete. The form must be returned by December 13, 2013.<br />
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;OR<br />
        </li>
        <li>
            Complete the ELearning lessons through the links on your report card. Once you complete all of the lessons for a particular biometric measure, your report card will populate with the maximum points for that measure. ELearning lessons must be completed by Dec. 13, 2013. You must have a total of nine points to qualify for the incentive. Please contact the Circle Wellness Hotline if you have any questions: 1‐866‐682‐3020.
        </li>


    </ul>
    <p>The report card below outlines your requirements and will keep track of your progress throughout the program.
        If you or your IAG insured spouse choose not to complete the requirements, you will not be eligible for the incentive.</p>

    <p>If you participated in wellness during the 2013 plan year, <a href="/compliance_programs?id=266">CLICK HERE</a> to view the new report card populated with those screening results. This is only a sample . The report card below will populate following the upcoming screenings and will determine your 2014 incentive.</p>


    <p>

<?php
}
}