<?php

use hpn\steel\query\SelectQuery;

class XrayTechnologies2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, true, true);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data['Biometric Points'] = $status->getAttribute('total_biometric_points');
            $data['Campaign Points'] = $status->getAttribute('participate_get_up_and_go_points') + $status->getAttribute('participate_five_for_five_points');
            $data['Alternative Points'] = $status->getAttribute('total_alternatives_points');
            $data['Total Points'] = $status->getAttribute('total_points');

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new XrayTechnologies2016Printer ();
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


        $hraView = new CompleteHRAComplianceView('2016-09-01', '2016-11-30');
        $hraView->setReportName('Health Risk Assessment(HRA)');
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView('2016-05-01', '2016-11-30');
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

        //$smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        //$smokingView->setReportName('Tobacco');
        //$smokingView->addLink(new link('Complete Affidavit with HR', '/content/989'));
        //$smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Negative (Cotinine)');
        //$smokingView->setEvaluateCallback(function(User $user) {
        //return false;
        //return $user->relationship_type == \Relationship::EMPLOYEE;
        //});
        //$employeeesGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($employeeesGroup);

        $biometricsGroup = new ComplianceViewGroup('requirements', 'Biometrics');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);

        $this->configureViewForElearningAlternative($bloodPressureView, 'reqd2014_bloodpressure');

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $this->configureViewForElearningAlternative($triglView, 'reqd2014_triglycerides');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 64 or ≥ 100');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');

        $this->configureViewForElearningAlternative($glucoseView, 'reqd2014_bloodsugar');

        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $this->configureViewForElearningAlternative($cholesterolView, 'reqd2014_totalcholesterol');

        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $this->configureViewForElearningAlternative($totalHDLRatioView, 'reqd2014_totalhdlratio');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new XrayTechnologies2015ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new XrayTechnologies2015BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat/BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $bodyFatBMIView->setUseHraFallback(true);

        // We have to reset start/end dates so that overrides work - the overrides are applied to the containing class
        // but won't bve to this bmi/body fat.

        $bodyFatBMIView->setStartDate($programStart);
        $bodyFatBMIView->setEndDate($programEnd);

        $this->configureViewForElearningAlternative($bodyFatBMIView, 'reqd2014_bmi');

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $extraPoints = 0;
        $totalBiometricPoints = 0;
        $getUpAndGoPoints = 0;
        $fiveForFivePoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            if($groupStatus->getComplianceViewGroup()->getName() != 'required_employees') {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $totalBiometricPoints += $viewStatus->getPoints();

                    if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                        $extraPoints += $viewExtraPoints;
                    }
                }
            }
        }

        if($status->getAttribute('total_biometric_points') === null) {
            $status->setAttribute('total_biometric_points', $totalBiometricPoints);
        }

        if($status->getAttribute('total_alternatives_points') === null) {
            $status->setAttribute('total_alternatives_points', $extraPoints);
        }

        if($status->getAttribute('participate_get_up_and_go_points') === null) {
            if($this->participateGetUpAndGo($status->getUser())) {
                $getUpAndGoPoints = 2;
            }

            $status->setAttribute('participate_get_up_and_go_points', $getUpAndGoPoints);
        }

        if($status->getAttribute('participate_five_for_five_points') === null) {

            if($this->participateFiveForFive($status->getUser())) {
                $fiveForFivePoints = 2;
            }

            $status->setAttribute('participate_five_for_five_points', $fiveForFivePoints);
        }

        if($status->getAttribute('total_points') === null) {
            $status->setAttribute('total_points', $totalBiometricPoints + $getUpAndGoPoints + $fiveForFivePoints);
        }

    }

    protected function participateGetUpAndGo(User $user)
    {
        return SelectQuery::create()
            ->select('id')
            ->from('compliance_program_record_team_users')
            ->where('compliance_program_record_id = ?', array(self::XRAYTECHNOLOGIES_GET_UP_AND_GO_2016_ID))
            ->andWhere('user_id = ?', array($user->id))
            ->andWhere('accepted = 1')
            ->hydrateSingleScalar()
            ->execute();
    }

    protected function participateFiveForFive(User $user)
    {
        $record = $user->getNewDataRecord(sfConfig::get('mod_legacy_chp_five_for_five_campaign_data_type', 'five_for_five_campaign_2016'));

        if($record->exists()) {
            return true;
        } else {
            return false;
        }
    }

    private function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
            $view = $status->getComplianceView();
            $viewPoints = $status->getPoints();

            $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();

            if($status->getAttribute('has_result') && $viewPoints < $maxPoints) {
                $startDate = '2016-10-01';
                $endDate = '2016-12-11';

                $limit = 2 * ($maxPoints - $viewPoints);

                $elearningView = new CompleteELearningGroupSet($startDate, $endDate, $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->useAlternateCode(true);

                $status->getComplianceView()->addLink(new Link('Complete ELearning', "/content/9420?action=lessonManager&tab_alias={$alias}&limit={$limit}"));

                $elearningStatus = $elearningView->getStatus($user);

                $numberCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

                $extraPoints = (int)($numberCompleted / 2);

                if($extraPoints > 0) {
                    $actualExtraPoints = min($maxPoints, $viewPoints + $extraPoints) - $viewPoints;

                    $status->setPoints($viewPoints + $extraPoints);
                    $status->setAttribute('extra_points', $actualExtraPoints);
                }


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

    const XRAYTECHNOLOGIES_GET_UP_AND_GO_2016_ID = 737;
}

class XrayTechnologies2016Printer  extends CHPStatusBasedComplianceProgramReportPrinter
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
        <p>
            <a href="/compliance_programs/index?id=265">
                View your 2013 Program
            </a>
        </p>

        <p>
            <a href="/compliance_programs/index?id=374">
                View your 2014 Program
            </a>
        </p>

        <p>
            <a href="/compliance_programs/index?id=530">
                View your 2015 Program
            </a>
        </p>

        <p style="margin-top:20px;font-size:smaller;">*If an onsite screening is not available at your location, you may request a packet from HR to bring to your physician to have your health screening done. You can also call 866-682-3020 ext 204 to request a packet to visit a lab
            location.</p>

        <p style="margin-top:20px;font-size:smaller;">**If contacted for coaching, employees and spouses must complete two coaching calls by May 30, 2016, or will lose incentive effective June 01, 2016.</p>


        <p style="margin-top:20px;font-size:smaller;">Pregnant? Are you pregnant or 6 months postpartum? You may complete a <a href="/resources/6137/2015_X-RAY_Pregnancy_Exception_Form.092815.pdf.pdf">Pregnancy Exception Form </a> and submit it to Circle Wellness to obtain credit for the body measurements (BMI and Body Fat).</p>


        <?php
    }

    public function printClientMessage()
    {
        $biometricPoints = $this->status->getAttribute('total_biometric_points');
        $participateGetAndGoPoints = $this->status->getAttribute('participate_get_up_and_go_points');
        $participateFiveForFivePoints = $this->status->getAttribute('participate_five_for_five_points');
        $totalCampaignPoints = $participateGetAndGoPoints + $participateFiveForFivePoints;
        $alternativesPoints = $this->status->getAttribute('total_alternatives_points');
        $totalPoints = $this->status->getAttribute('total_points');


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

            $('#clientMessage').next().remove();
            $('#clientMessage').next().remove();
            $('#clientMessage').next().remove();

            $('#clientMessage').after('<strong style="margin-top: 20px;">2016 Health Screening</strong>');


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

            $('.headerRow.totalRow th').first().html(
                'Total Biometric Points:<br/><br/>Total Campaign Points:<br/><br/>Total Reasonable Alternative Points:<br/><br/>2016 Point TOTAL:'
            );

            $('.headerRow.totalRow td:eq(2)').first().html(
                '<?php echo "$biometricPoints<br/><br/>$totalCampaignPoints<br/><br/>$alternativesPoints<br/><br/>$totalPoints" ?>'
            );
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
    <p></p>

    <style type="text/css">
        #legendEntry2 {
            display:none;
        }
    </style>

    <p>
        Welcome to the 2016 X-Ray Industries Wellness Program. In order to qualify for the $600 incentive in 2016, <strong>employees</strong> who are on
        the medical plan and are participating in wellness must meet 3 objectives to qualify for the 2016 incentive (unless
        contacted for health coaching**). <strong>Spouses</strong>  on the medical plan will only be required to meet requirements 1 & 2 to qualify
        for the 2016 incentive (unless contacted for health coaching**).
    </p>

    <p>
    <ol>
        <li>
            Complete an online Health Risk Assessment by <strong>November 30, 2016</strong>.
        </li>
        <li>
            Complete an onsite Health Screening by <strong>November 30, 2016.*</strong>
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
            Complete the ELearning lessons through the links on your report card. Once you complete all of the lessons for a particular biometric measure, your report card will populate with the maximum points for that measure. ELearning lessons must be completed by <strong>December 11, 2016</strong>. You must have a total of nine points to qualiy for the incentive. Please contact the Circle Wellness Hotline if you have any questions: 1‐866‐682‐3020.
            <br /><br />&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;OR<br /><br />

        </li>

        <li>
            Bring a <a href="/resources/6134/2015_X-RAY_Physician_Exception_Form.092815.pdf">Physician Exception Form</a> to your physician to complete. The form must be returned by <strong>December 11, 2016.</strong><br />
        </li>



    </ul>
    <p>The report card below outlines your requirements and will keep track of your progress throughout the program.
        If you or your X-Ray Industries insured spouse choose not to complete the requirements, you will not be eligible for the incentive.</p>
    <p>

    <div class="pageHeading">My Incentive Report Card</div>

    <div id="legend">
        <div id="legendText">Legend</div>
            <span class="legendEntry" id="legendEntry4">
                <img src="/images/lights/greenlight.gif" class="light" alt=""> Compliant </span>
            <span class="legendEntry" id="legendEntry2">
                <img src="/images/lights/yellowlight.gif" class="light" alt=""> Partially Done </span>
            <span class="legendEntry" id="legendEntry1">
                <img src="/images/lights/redlight.gif" class="light" alt=""> Not Compliant </span>
            <span class="legendEntry" id="legendEntry3">
                <img src="/images/lights/blacklight.gif" class="light" alt=""> Not Required </span>
        </div>

    <table class="phipTable">
        <tbody>
            <tr class="headerRow">
                <th>Optional Campaigns</th>
                <td></td>
                <td></td>
                <td class="points">Points Possible</td>
                <td>Points Earned</td>
            </tr>
            <tr>
                <td>Get Up and Go!</td>
                <td>Participate as a team member</td>
                <td>June 1-30</td>
                <td class="points">2</td>
                <td class="points"><?php echo $participateGetAndGoPoints ?></td>
            </tr>
            <tr>
                <td>Five For Five</td>
                <td>Participate and score at least 1200 points</td>
                <td>August 1-26</td>
                <td class="points">2</td>
                <td class="points"><?php echo $participateFiveForFivePoints ?></td>
            </tr>
        </tbody>
    </table>


    <?php
    }
}
