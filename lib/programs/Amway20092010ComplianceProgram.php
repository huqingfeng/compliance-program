<?php
//
class Amway20092010ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Amway20092010ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        $printer->addCallbackField('employee_id', function (User $user) {
            return (string) $user->getUserUniqueIdentifier('amway_employee_id');
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        $programStart = $this->getStartDate('U');
        $programEnd = $this->getEndDate('U');
        $programClose = '2010-12-15 23:59:59';
        $currentProgram = $this;

        $optionalGroup = new ComplianceViewGroup('Optional');

        $diseaseManagementView = new DiseaseManagementComplianceView();
        $diseaseManagementView->setOptional(true);
        $diseaseManagementView->setReportName('Disease Mgmt. / Wellness Coaching');
        $diseaseManagementView->addLink(new Link('More Info', '/content/1057#58405'));
        $optionalGroup->addComplianceView($diseaseManagementView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->emptyLinks();
        $screeningView->setOptional(true);
        $screeningView->setReportName('Optional Screening');
        $optionalGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($optionalGroup);

        $requiredGroup = new ComplianceViewGroup('Required');

        $hraView = new CompleteHRAComplianceView($programStart, '2010-03-31');
        $hraView->setReportName('HRA');
        $requiredGroup->addComplianceView($hraView);

        $physicalExamStartDate = strtotime('-24 months', $programStart);
        $physicalExam = new CompletePreventionPhysicalExamComplianceView($physicalExamStartDate, $programEnd);
        $physicalExam->addLink(new Link('More Info', '/content/1057#58398'));
        $physicalExam->setReportName('Physical Exam');
        $physicalExam->setClosingDate($programClose);
        $requiredGroup->addComplianceView($physicalExam);

        $coloRectalScreening = new CompletePreventionColoRectalScreeningComplianceView($programStart, $programEnd);
        $coloRectalScreening->setMinimumAge(50);
        $coloRectalScreening->addLink(new Link('More Info', '/content/1057#58399'));
        $coloRectalScreening->setReportName('Colo-rectal Screening');
        $coloRectalScreening->setClosingDate($programClose);
        $requiredGroup->addComplianceView($coloRectalScreening);

        $mammography = new CompletePreventionMammographyComplianceView($programStart, $programEnd);
        $mammography->setClosingDate($programClose);
        $mammography->setMinimumAge(40);

        $mammography->setStartDate(
            function ($format, User $user) use ($currentProgram) {
                $programStart = $currentProgram->getStartDate('U');
                $age = $user->getAge($programStart, true);

                $monthOffset = $age < 50 ? 24 : 12;

                return date($format, strtotime("-$monthOffset months", $programStart));
            }
        );
        $mammography->addLink(new Link('More Info', '/content/1057#58401'));
        $mammography->setReportName('Mammography');
        $requiredGroup->addComplianceView($mammography);

        $papTestStartDate = strtotime('-24 months', $programStart);
        $papTest = new CompletePreventionPapTestComplianceView($papTestStartDate, $programEnd);
        $papTest->setMinimumAge(19);
        $papTest->addLink(new Link('More Info', '/content/1057#58402'));
        $papTest->setReportName('Pap Test');
        $papTest->setClosingDate($programClose);
        $requiredGroup->addComplianceView($papTest);

        $visionScreeningStartDate = strtotime('-24 months', $programStart);
        $visionScreening = new CompletePreventionVisionExamComplianceView($visionScreeningStartDate, $programEnd);
        $visionScreening->setMinimumAge(19);
        $visionScreening->addLink(new Link('More Info', '/content/1057#58403'));
        $visionScreening->setReportName('Vision Screening');
        $visionScreening->setClosingDate($programClose);
        $requiredGroup->addComplianceView($visionScreening);

        $dentalCareStartDate = strtotime('-12 months', $programStart);
        $dentalCare = new CompletePreventionDentalExamComplianceView($dentalCareStartDate, $programEnd);
        $dentalCare->setMinimumAge(19);
        $dentalCare->addLink(new Link('More Info', '/content/1057#58404'));
        $dentalCare->setReportName('Preventitive Dental Care');
        $dentalCare->setClosingDate($programClose);
        $requiredGroup->addComplianceView($dentalCare);

        $this->addComplianceViewGroup($requiredGroup);
    }
}

class Amway20092010ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $this->printTableRow($viewStatus);
            }
        }
    }

    private function getViewName(ComplianceView $view)
    {
        $viewName = '<span>'.$view->getReportName().'</span>';

        if($view->getOptional()) {
            $viewName .= '<span class="notRequired">(Not Required)</span>';
        }

        return $viewName;
    }

    private function printTableRow(ComplianceViewStatus $status)
    {
        $view = $status->getComplianceView();
        ?>
    <tr>
        <td class="resource"><?php echo $this->getViewName($view); ?></td>
        <td class="phipstatus">
            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            <?php
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                echo "<br/>Date Completed:<br/>".$status->getComment();
            } else if($status->getStatus() == ComplianceViewStatus::NA_COMPLIANT) {
                echo "<br/>N/A";
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
        <td class="exemption">
            <?php if(!$status->isCompliant() && !$view instanceof CompleteHRAComplianceView) { ?>
            <a href="/resources/1708/Verification Form-2010.pdf">I did this</a> /
            <a href="/resources/1713/Exception-Form-2010.pdf">I am exempt</a>
            <?php } ?>
        </td>
    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        use_stylesheet('/css/compliance/Amway20092010ComplianceProgram.css');
        $user = $status->getUser();
        ?>
    <p>Hello <?php echo $user; ?></p>
    <p class="subnote">
        <em>Using a shared computer?</em>
        <strong>If you are not <?php echo $user; ?>, <a href="/logout">Please click here</a>.</strong>
    </p>
    <hr/>
    <div style="font-size:0.95em;">
        <p>To be eligible for the 2009/2010 program and savings, employees must complete two steps.</p>

        <p>The program runs November 02, 2009 to July 31, 2010</p>
        <ul>
            <li>Complete Step 1 by March 31, 2010</li>
            <li>Complete Step 2 by July 31, 2010</li>
        </ul>
        <p>
            <strong><u>Step1</u></strong> <a href="/content/989">Complete HRA questionnaire</a>
        </p>

        <p>Requirement: Complete this step between 11/02/09 and 3/31/10.</p>

        <p><strong><u>Step 2</u></strong> Preventive Screenings</p>

        <p>
            Requirement: Preventive screening activity will be automatically entered into your record from information
            received from your Amway health plan. Updates are scheduled each quarter for claims that paid through the
            previous quarter. Example: If your appointment was on September 9th and the claim was paid on November 20th,
            the claim will be updated on the website in February or March of the following year. See below for screening
            details.
        </p>
    </div>
    <hr/>
    <div id="phipSubPages">
        My Incentive Report Card Links
        <div id="subpagelinks">
            <a href="/resources/1713/Exception-Form-2010.pdf">2010 Exemption Form</a>
            <a href="/resources/1708/Verification Form-2010.pdf">2010 Verification Form</a>
            <a href="/content/1057">FAQ</a>
            <a href="/support">Need Assistance</a>
            <a href="/request_archive_collections/show?id=12">2008/2009 Incentive
                Status</a>
            <a href="/request_archive_collections/show?id=10">2007/2008 Incentive
                Status</a>
        </div>
    </div>
    <div id="sectionBorder"></div>
    <div id="phip">
        <div class="pageTitle">My Incentive Report Card</div>
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
                                <img src="/images/lights/yellowlight.gif" class="light" alt=""/> Partially Completed
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
            <thead>
                <tr>
                    <th class="resource">Resource</th>
                    <th class="status">Status</th>
                    <th class="information">More Info</th>
                    <th class="links">Links</th>
                </tr>
            </thead>
            <tbody>
                <tr id="totalComplianceRow">
                    <td class="resource">Overall Compliance</td>
                    <td class="status">
                        <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
                    </td>
                    <td class="information"></td>
                    <td class="links"></td>
                </tr>
                <?php $this->printTableRows($status); ?>
            </tbody>

        </table>

        <div id="endNote">
            <div>If you have any questions about your Optimal You report card please call toll free: (866) 682-3020 ext.
                207.
            </div>
            <br/>

            <div>If you feel that you are exempt from a requirement or have already completed it, please click the
                related link, complete the form and have it signed by your Physician. The form outlines your options for
                submitting the form.
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <?php
    }
}

?>