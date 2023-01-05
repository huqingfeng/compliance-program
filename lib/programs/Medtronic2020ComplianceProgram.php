<?php

class Medtronic2020CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('glucose')
            )
        );

        if(count($data) > 0) $this->setAttribute('has_screening', true);

        return $data;
    }
}



class Medtronic2020ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addStatusFieldCallback('# of Goals Met', function(ComplianceProgramStatus $status) {
            $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

            return $healthyGroupStatus->getAttribute('number_compliant');
        });

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Medtronic2020ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'Program');
//        $coreGroup->setPointsRequiredForCompliance(50);

        $screeningView = new Medtronic2020CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('1. Complete the wellness screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->setAttribute('goal', '12/07/2020');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2000, 0, 0, 0));
        $coreGroup->addComplianceView($screeningView);


        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. Body Mass Index');
        $bmiView->overrideTestRowData(null, null, 27.5, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '≤27.5');
        $bmiView->setAttribute('screening_view', true);
        $bmiView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($bmiView);

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setReportName('2. Waist');
        $waistView->setName('waist');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $waistView->overrideTestRowData(null, null, 40, null, 'M');
        $waistView->overrideTestRowData(null, null, 35, null, 'F');
        $waistView->setAttribute('goal', 'F≤35  M≤40');
        $waistView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($waistView);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->setReportName('3. Blood Pressure');
        $bpView->setName('blood_pressure');
        $bpView->overrideSystolicTestRowData(null, null, 139.999, null);
        $bpView->overrideDiastolicTestRowData(null, null, 89.999, null);
        $bpView->emptyLinks();
        $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpView->setAttribute('goal', '<140 / <90');
        $bpView->setAttribute('screening_view', true);
        $bpView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($bpView);

        $hdlRatioView = new ComplyWithNonHDLCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('4. Non-HDL Cholesterol');
        $hdlRatioView->overrideTestRowData(null, null, 129.999, null);
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->setAttribute('goal', '<130');
        $hdlRatioView->setAttribute('screening_view', true);
        $hdlRatioView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($hdlRatioView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('5. Glucose');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $gluView->setAttribute('goal', '≤99');
        $gluView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($gluView);

        $cotinineView = new ComplyWithCigaretteSmokingScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('6. Tobacco Use Status');
        $cotinineView->setName('tobacco');
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Non-User');
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->setPreMapCallback($this->validateScreening());
        $group->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($group);

        $additionalGroup = new ComplianceViewGroup('additional', 'Additional');
        $additionalGroup->setPointsRequiredForCompliance(0);

        $qualificationForm = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $qualificationForm->setReportName('Alternate Qualification Form');
        $qualificationForm->setName('alternate_qualification_form');
        $qualificationForm->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1000, 0, 0, 0));
        $qualificationForm->setAttribute('goal', 'Complete');
        $additionalGroup->addComplianceView($qualificationForm);

        $this->addComplianceViewGroup($additionalGroup);
    }

    protected function validateScreening()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        return function($status, $user) use ($programStart, $programEnd) {
            $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
                $user,
                new DateTime('@'.$programStart),
                new DateTime('@'.$programEnd),
                array(
                    'require_online'   => false,
                    'merge'            => true,
                    'require_complete' => false,
                    'required_fields'  => array('glucose')
                )
            );

            if($status->getComplianceView()->getName() == 'blood_pressure') {
                if(strpos(strtolower($status->getAttribute('systolic_real_result')), 'decline') !== false) {
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    $status->setComment($status->getAttribute('systolic_real_result'));
                } elseif(strpos(strtolower($status->getAttribute('diastolic_real_result')), 'decline') !== false) {
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    $status->setComment($status->getAttribute('diastolic_real_result'));
                }
            } else {
                if(strpos(strtolower($status->getAttribute('real_result')), 'decline') !== false) {
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    $status->setComment($status->getAttribute('real_result'));
                }
            }

            if(!isset($data['glucose']) || empty($data['glucose'])) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $status->setComment('');
            }

            return $data;
        };
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $numCompliant = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {

            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numCompliant++;
        }

        if($numCompliant >= 5) {
            $healthGroupStatus->setStatus(ComplianceViewStatus::COMPLIANT);
            $healthGroupStatus->setPoints(2000);
        } elseif ($numCompliant >= 4) {
            $healthGroupStatus->setPoints(1500);
        } elseif ($numCompliant >= 3) {
            $healthGroupStatus->setPoints(1000);
        } else {
            $healthGroupStatus->setPoints(0);
        }

        if($numCompliant >= 3) {
            $healthGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $healthGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $healthGroupStatus->setAttribute('number_compliant', $numCompliant);

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $additionalGroupStatus = $status->getComplianceViewGroupStatus('additional');

        $status->setPoints($coreStatus->getPoints() + $healthyGroupStatus->getPoints() + $additionalGroupStatus->getPoints());

    }
}

class Medtronic2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#88b2f6;
                font-weight:bold;
                font-size:10pt;
                height:46px;
            }

            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:7.6in;
                margin:0 0.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
                padding: 1px;
            }


            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#DEDEDE;
            }

            #bottom_notes{
                width:7.6in;
                margin:0 0.5in;
            }

            #bottom_notes p{
                margin: 3px 0;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">
            <p style="clear: both;">
            <div style="float: left;">
                <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                4205 Westbrook Drive<br />
                Aurora, IL 60504
            </div>

            <div style="float: right;">

            </div>
            </p>

            <p style="margin-left:0.75in; padding-top:.56in; clear: both;">
                <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>
                Congratulations for participating in the 2020 Wellness Screening program. Below is a chart that outlines
                your Healthy Measures results and reward points. Your reward points will be visible on
                <a href="http://healthiertogether.medtronic.com">healthiertogether.medtronic.com</a> within six weeks.
                Please note that your test results are not shared with Medtronic and remain confidential.
            </p>

            <p style="text-align: center; font-size: 13pt;">Your Healthy Measures Results for 2020</p>


            <?php echo $this->getTable($status) ?>

            <div id="bottom_notes">
                <p>Visit <a href="http://medtronic.hpn.com">medtronic.hpn.com</a> to view your screening
                    results, links in the report, and to access powerful tools and resources for optimizing your wellbeing such as:</p>

                <ul>
                    <li>Healthwise® Knowledgebase for decisions about medical tests, medications, other treatments and risks</li>
                    <li>Over 500 videos and 1,000 e-lessons on health and wellbeing</li>
                    <li>Decision Tools for over 170 elective care decisions</li>
                    <li>Cholesterol, body metrics, blood sugars, women’s and men’s health and over 40 other learning centers</li>
                </ul>

                <p>Thank you for participating in the Wellness Screening program.</p><br />

                <p>
                <div style="width: 80%; float: left">
                    Best Regards,<br />
                    Empower Health Services
                </div>
                <div style="width:20%; float: right">
                    <img src="/images/empower/medtronic_logo.jpg" style="height:60px;"  />
                </div>
                </p>
            </div>

            <p>&nbsp;</p>
        </div>

        <?php if($coreStatus->getStatus() == ComplianceStatus::COMPLIANT && $healthyGroupStatus->getAttribute('number_compliant') <= 2) : ?>
        <p>
            <img src="/resources/10554/2020_Medtronic_AQF_121019.jpg" />
        </p>
    <?php endif ?>

        <?php
    }

    private function getTable($status)
    {

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $additionalGroupStatus = $status->getComplianceViewGroupStatus('additional');

        $qualificationFormStatus = $status->getComplianceViewStatus('alternate_qualification_form');

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">A. Incentive Actions</th>
                        <th>Date Done</th>
                        <th>Goal Deadline</th>
                        <th>Goal Met</th>
                        <th>Points Possible</th>
                        <th>My Points</th>
                    </tr>

                    <?php foreach($coreStatus->getComplianceViewStatuses() as $viewStatus) : ?>
        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
            <td><?php echo $viewStatus->getComment() ?></td>
            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
            <td><?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
            <td><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints()  ?></td>
            <td><?php echo $viewStatus->getPoints()  ?></td>
        </tr>
    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">B. Incentive Measures</th>
                        <th>My Result</th>
                        <th>Goal Range</th>
                        <th>Goal Met</th>
                        <th></th>
                        <th></th>
                    </tr>

                    <?php $count = 0 ?>
        <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
            <td><?php echo $viewStatus->getComment() ?></td>
            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
            <td>
                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
            </td>

            <?php if($count == 0) : ?>
                <td rowspan="7">
                    <div style="width: 160px;">
                        0-2 met = 0 pts.<br/>
                        3 = 1,000 pts.<br/>
                        4 = 1,500 pts.<br/>
                        5+ = 2,000 pts.
                    </div>
                </td>
                <td rowspan="7">
                    <?php echo $healthyGroupStatus->getPoints() ?>
                </td>
                <?php $count++ ?>
            <?php endif ?>
        </tr>
    <?php endforeach ?>

                    <tr class="status-<?php echo $healthyGroupStatus->getStatus() ?>">
                        <td># of Goals Met</td>
                        <td><?php echo $healthyGroupStatus->getAttribute('number_compliant') ?></td>
                        <td>3 or more</td>
                        <td><?php echo $healthyGroupStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                    </tr>

                    <tr class="status-<?php echo $qualificationFormStatus->getStatus() ?>" style="<?php echo $qualificationFormStatus->getStatus() != ComplianceStatus::COMPLIANT ? 'display:none' : ''?>">
                        <td style="text-align: left;"><?php echo $qualificationFormStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $qualificationFormStatus->getComment() ?></td>
                        <td><?php echo $qualificationFormStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $qualificationFormStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td><?php echo $qualificationFormStatus->getComplianceView()->getMaximumNumberOfPoints()  ?></td>
                        <td><?php echo $qualificationFormStatus->getPoints()  ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th colspan="5" style="text-align: left;">Point Total <span style="padding-left: 66%;">Total Reward Points</span></th>
                        <td><?php echo $coreStatus->getPoints() + $healthyGroupStatus->getPoints() + $additionalGroupStatus->getPoints() ?></td>
                    </tr>

                    <tr>
                        <td colspan="7">
                            <div>
                            <?php if($healthyGroupStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT && $coreStatus->getComplianceViewStatus('complete_screening')->getComplianceView()->getAttribute('has_screening')) : ?>
                                <p style="color: red;">
                                    You may complete and return the <a href="/resources/10549/2020_Medtronic_AQF_121019.pdf">Alternate Qualification Form</a>
                                    to earn 1,000 points for Healthy Measures, for a total of 3,000 points for
                                    completing the screening program and the form.
                                </p>
                            <?php endif ?>

                                <p>
                                    Visit <a href="http://healthiertogether.medtronic.com">healthiertogether.medtronic.com</a> to view all your options to earn reward points.
                                </p>
                            </div>


                         </td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }


    /**
     * To show the pass table, a user has to be compliant for the program, and be compliant
     * without considering elearning lessons.
     */
    private function getNumCompliant($status)
    {
        $numberCompliant = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();

                if($view->getAttribute('screening_view')) {
                    if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        // Alternative wasn't executed, so original_status is null. View still compliant

                        $numberCompliant++;
                    }
                }
            }
        }

        return $numberCompliant;
    }

}