<?php

class NSK2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new NSK2012Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(null, null, null, false, true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program');
        $preventionEventGroup->addComplianceView($hraScreeningView);

        $viewHra = new ViewHpaReportComplianceView($programStart, $programEnd, ViewHpaReportComplianceView::LOGIC_BOTH_AT_ONCE);
        $viewHra->setReportName('View HRA/Screening Reports');
        $viewHra->setName('view_hpa_report');
        $preventionEventGroup->addComplianceView($viewHra);

        $this->addComplianceViewGroup($preventionEventGroup);

        $tobaccoGroup = new ComplianceViewGroup('Tobacco');

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $tobaccoGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $consGroup = new ComplianceViewGroup('Consultations (Optional)');
        $consGroup->setNumberOfViewsRequired(0);

        $consView = new AttendAppointmentComplianceView($programStart, $programEnd);
        $consView->setReportName('Consultations and Extended Risk Coaching');
        $consView->bindTypeIds(array(11));
        $consGroup->addComplianceView($consView);

        $this->addComplianceViewGroup($consGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(6);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');

        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new NSK2012ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setUseHraFallback(true);
        $bodyFatBMIView->setComplianceStatusPointMapper(new NSK2012BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
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

class NSK2012BFMapper extends ComplianceStatusPointMapper
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

class NSK2012ComplyWithBodyFatBMIScreeningTestComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
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

class NSK2012Printer extends CHPStatusBasedComplianceProgramReportPrinter
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

    public function printClientMessage()
    {
        ?>
    <p>
        <style type="text/css">
            .phipTable .links {
                width:145px;
            }

            #legendEntry3, #legendEntry2 {
                display:none;
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
        </style>
    </p>
    <p style="color:red; font-weight:bolder">BELOW IS A PREVIEW OF THE REQUIREMENTS FOR THE 2013 PROGRAM YEAR TO
        DETERMINE YOUR 2014 SAVINGS LEVEL
    </p>
    <p>Welcome to The NSK Americas Wellness Website! </p>
    <p>Use this site to track your wellness requirements. It’s also a great resource for health related topics and
        questions.</p>
    <p>Complete the following steps in 2013 to determine your 2014 savings level.</p>
    <table width="100%" border="1" bordercolor="#999999" cellpadding="5" style="border-collapse:collapse">
        <tr>
            <td width="7%" align="center"><strong style="color:#039; font-size:1.2em">Step 1</strong></td>
            <td width="93%">Complete your on-site health screening and HRA by dates TBA OR on-demand screening and HRA
                on-line by date TBA.<br/>
                <span style="font-size:.8em"></span></td>
        </tr>
        <tr>
            <td align="center"><strong style="color:#039; font-size:1.2em">Step 2</strong></td>
            <td>View BOTH screening and HRA results (results will be viewable 3-5 days after your screening).<br/>
                <br/>
                <span style="font-size:.8em"></span></td>
        </tr>
        <tr>
            <td align="center"><strong style="color:#039; font-size:1.2em">Step 3</strong></td>
            <td>Meet the recommended range requirements for your screening tests -OR- satisfy the alternative standard
                to receive $90 monthly savings (based on the points earned and tracked on your report card)*.
            </td>
        </tr>
    </table>
    <!--<p style="font-size:.8em">*These requirements are for all employees on the medical plan who want to receive the Star 2 or Star 3 level premium differentials.<br />-->
    <br/>
    </p>
    <p><strong style="color:#039; font-size:1.2em">Incentive Levels</strong></p>
    <table width="100%" border="1" bordercolor="#FFF" cellpadding="5" style="border-collapse:collapse">
        <tr>
            <td width="7%" align="center" bgcolor="#003399"><strong style="color:#FFF">Level</strong></td>
            <td width="46%" align="center" bgcolor="#003399"><strong style="color:#FFF">What You Need To Do</strong>
            </td>
            <td width="47%" align="center" bgcolor="#003399"><strong style="color:#FFF">What your Monthly Savings will
                be:</strong></td>
        </tr>
        <tr>
            <td align="center" bgcolor="#EEEEEE"><strong>1</strong></td>
            <td bgcolor="#EEEEEE"><p>Fail to complete Steps 1 & 2 Above</p></td>
            <td bgcolor="#EEEEEE">$0/month</td>
        </tr>
        <tr>
            <td align="center" bgcolor="#CCCCCC"><strong>2</strong></td>
            <td bgcolor="#CCCCCC"><p>Complete Steps 1 & 2 Above <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AND
                <br/>Test positive for Cotinine (tobacco user)

            </p></td>
            <td bgcolor="#CCCCCC">$45/month</td>
        </tr>
        <tr>
            <td align="center" bgcolor="#EEEEEE"><strong>3</strong></td>
            <td bgcolor="#EEEEEE">Complete Steps 1 & 2 Above <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AND
                <br/> Test negative for Cotinine (non-tobacco user) <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AND
                <br/> Fail to meet 11 of the 13 possible points available in your report card below AND fail to
                achieve a year over year improvement of 2 or more points
            </td>
            <td bgcolor="#EEEEEE">$60/month</td>
        </tr>
        <tr>
            <td align="center" bgcolor="#D6D6D6"><strong>4</strong></td>
            <td bgcolor="#D6D6D6">Complete Steps 1 & 2 Above <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AND
                <br/> Test negative for Cotinine (non-tobacco user) <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AND
                <br/> Meet either 11 of the possible 13 points available in your report card below OR achieve a year
                over year improvement of 2 or more points*
            </td>
            <td bgcolor="#D6D6D6">$90/month</td>
        </tr>
        <!--<tr>
          <td align="center" bgcolor="#EEEEEE"><strong>5</strong></td>
          <td bgcolor="#EEEEEE">Complete Level 2 requirements, test negative for Cotinine (non-tobacco user)
            AND meet either 11 of the possible 13 points available in your report card below OR achieve a year
            over year improvement of 2 or more points*</td>
          <td bgcolor="#EEEEEE">$0/month</td>
        </tr>     -->
    </table>
    <p>*If you did not participate in the 2012 program you will need to satisfy a point value of 11 or more to obtain
        Level 4 status above ($90/month savings); as you won’t yet have a baseline of data to measure improvement.</p>


    <p><strong>Earn wellness points by satisfying wellness criteria in up to five areas: </strong></p>
    <ul>

        <li>Triglycerides</li>
        <li>Glucose</li>
        <li>Total Cholesterol</li>
        <li>Total/HDL Cholesterol Ratio</li>
        <li>Better of Body Fat/Body Mass Index (BMI)</li>

    </ul>
    <p>When you satisfy the criteria, your red light will change to a green light in your report card.<br/>
        <strong>Total Possible Points = 13</strong></p>
    <p>If it is unreasonably difficult due to a medical condition for you to satisfy one or more of the wellness
        criteria
        under this program or if it is medically inadvisable for you to attempt to achieve these wellness criteria for
        the reward,
        you may submit a form that will be provided on the website to be completed by your physician and
        sent to Circle Wellness. The form will be reviewed to determine your level of savings.</p>

    <p><strong>Pregnant?  </strong>Are you pregnant or 6 months postpartum? you may complete a pregnancy exception form
        that will be provided on the website and submitted to Circle Wellness to obtain credit for the body
        measurements.</p>
    <p>The current requirements and your current status for each are summarized
        below.</p>

    <?php
    }
}