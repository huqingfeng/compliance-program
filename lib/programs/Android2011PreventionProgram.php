<?php

class Android2011PreventionProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Android2011PreventionProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        $printer->addCallbackField('employee_id', function (User $user) {
            return (string) $user->employeeid;
        });
        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate('U');
        $programEnd = $this->getEndDate('U');
        $currentProgram = $this;

        $requiredGroup = new ComplianceViewGroup('Required');

        $physicalExamStartDate = strtotime('-24 months', $programStart);

        $physicalExam = new CompletePreventionPhysicalExamComplianceView($physicalExamStartDate, $programEnd);
        $physicalExam->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/hw226888'));
        $physicalExam->setReportName('Physical Exam');
        $requiredGroup->addComplianceView($physicalExam);

        $coloRectalScreening = new CompletePreventionColoRectalScreeningComplianceView($programStart, $programEnd);
        $coloRectalScreening->setMinimumAge(50, date('Y-m-d'));
        $coloRectalScreening->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/hw209694'));
        $coloRectalScreening->setReportName('Colo-rectal Screening');
        $requiredGroup->addComplianceView($coloRectalScreening);

        $mammography = new CompletePreventionMammographyComplianceView($programStart, $programEnd);
        $mammography->setMinimumAge(40, date('Y-m-d'));

        $mammography->setStartDate(
            function ($format, User $user) use ($currentProgram) {
                $programStart = $currentProgram->getStartDate('U');
                $age = $user->getAge($programStart, true);

                $monthOffset = $age < 50 ? 24 : 12;

                return date($format, strtotime("-$monthOffset months", $programStart));
            }
        );
        $mammography->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/hw214210'));
        $mammography->setReportName('Mammography');
        $requiredGroup->addComplianceView($mammography);

        $papTestStartDate = strtotime('-24 months', $programStart);
        $papTest = new CompletePreventionPapTestComplianceView($papTestStartDate, $programEnd);
        $papTest->setMinimumAge(19, date('Y-m-d'));
        $papTest->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/hw5266'));
        $papTest->setReportName('Pap Test');
        $requiredGroup->addComplianceView($papTest);


        $visionScreeningStartDate = strtotime('-24 months', $programStart);
        $visionScreening = new CompletePreventionVisionExamComplianceView($visionScreeningStartDate, $programEnd);
        $visionScreening->setMinimumAge(19, date('Y-m-d'));
        $visionScreening->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/center1014'));
        $visionScreening->setReportName('Vision Screening');
        $requiredGroup->addComplianceView($visionScreening);

        $dentalCareStartDate = strtotime('-12 months', $programStart);
        $dentalCare = new CompletePreventionDentalExamComplianceView($dentalCareStartDate, $programEnd);
        $dentalCare->setMinimumAge(19, date('Y-m-d'));
        $dentalCare->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/ug3192'));
        $dentalCare->setReportName('Preventive Dental Care');
        $requiredGroup->addComplianceView($dentalCare);

        $psaView = new CompletePreventionPSAComplianceView($programStart, $programEnd);
        $psaView->setReportName('PSA');
        $psaView->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/aa38144'));
        $psaView->setAlternativeComplianceView(
            new CompleteScreeningTestComplianceView($programStart, $programEnd, 'psa')
        );
        $requiredGroup->addComplianceView($psaView);

        $thyroidView = new CompletePreventionThyroidComplianceView($programStart, $programEnd);
        $thyroidView->setReportName('Thyroid');
        $thyroidView->addLink(new Link('More Info', '/healthwise/knowledge-base/documents/hw235109'));
        $thyroidView->setAlternativeComplianceView(
            new CompleteScreeningTestComplianceView($programStart, $programEnd, 'tsh')
        );
        $requiredGroup->addComplianceView($thyroidView);

        $this->addComplianceViewGroup($requiredGroup);
    }
}

class Android2011PreventionProgramReportPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            ?>
        <tr class="thead">
            <th class="resource">Resource</th>
            <th class="status">Status</th>
            <th class="information">Links</th>
        </tr>
        <?php
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $this->printTableRow($viewStatus);
            }
        }
    }

    private function getViewName(ComplianceView $view)
    {
        $viewName = '<span>'.$view->getReportName().'</span>';

        return $viewName;
    }

    private function printTableRow(ComplianceViewStatus $status)
    {
        $view = $status->getComplianceView();
        $c = $view->getAttribute('comment', 'Result');

        if($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
            return;
        }
        ?>
    <tr>
        <td class="resource"><?php echo $this->getViewName($view); ?></td>
        <td class="phipstatus">
            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            <?php
            if($status->getStatus() == ComplianceStatus::COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                echo "<br/>$c:<br/>", $status->getComment();
            }
            ?>
        </td>
        <td class="moreInfo">
            <?php
            $i = 0;
            foreach($view->getLinks() as $link) {
                echo $i++ > 0 ? ', ' : ' ';
                echo $link;
            }
            ?>
        </td>
    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        use_stylesheet('/css/compliance/Amway20092010ComplianceProgram.css');
        $user = $status->getUser();
        ?>
    <style type="text/css">
        div#phip {
            float:none;
            width:100%;
        }
    </style>
    <p>Hello <?php echo $user; ?></p>

    <p class="subnote">
        <em>Using a shared computer?</em>
        <strong>If you are not <?php echo $user; ?>, <a href="/logout">Please click here</a>.</strong>
    </p>
    <hr/>

    <div style="font-size:0.95em;">

        <p>Preventative Exam Information – As a courtesy to you, preventative exam information will be automatically
            entered into your record from information received from your health plan. Updates are
            scheduled each quarter for claims that are paid through the previous quarter. Example:
            If your appointment was on February 9th and the claim was paid on March 20th, the claim
            will be updated on the website in April or May.</p>

        <p><strong>CONFIDENTIALITY:</strong> No one from Android Industries, including Android medical staff, has access
            to your personal results. The following information is provided as an added benefit to help you understand
            your recommended preventative screenings.</p>


    </div>
    <hr/>
    <div id="phip">
        <div class="pageTitle">Recommended Preventative Screenings Report Card</div>
        (not required for any incentives)

        <table id="legend">
            <tr>
                <td id="firstColumn">LEGEND</td>
                <td id="secondColumn">
                    <table id="secondColumnTable">
                        <tr>
                            <td>
                                <img src="/images/lights/greenlight.gif" class="light" alt=""/> Completed
                            </td>
                            <td>

                            </td>
                            <td>
                                <img src="/images/lights/redlight.gif" class="light" alt=""/> Not Completed
                            </td>
                            <td>
                                <img src="/images/lights/whitelight.gif" class="light" alt=""/> N/A
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table id="phipTable">
            <tbody>
                <?php $this->printTableRows($status); ?>
            </tbody>

        </table>

        <div id="endNote">
            <div>If you have any questions about your report card please call toll free: (866) 682-3020 ext. 204.</div>
            <br/>
        </div>
    </div>
    <div style="clear: both;"></div>
    <?php
    }
}