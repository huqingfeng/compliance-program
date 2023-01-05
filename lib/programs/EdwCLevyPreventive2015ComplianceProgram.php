<?php

class EdwCLevyPreventive2015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new EdwCLevyPreventive2015ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        $printer->addCallbackField('employee_id', function (User $user) {
            return (string) $user->getUserUniqueIdentifier('amway_employee_id');
        });
        $printer->addCallbackField('client_executive', function(User $user) {
            return $user->hasAttribute(Attribute::CLIENT_EXECUTIVE_USER) ? 1 : 0;
        });
        $printer->setShowUserFields(null, null, null, null, null, null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        $programStart = $this->getStartDate('U');
        $programEnd = $this->getEndDate('U');
        $currentProgram = $this;

        $requiredGroup = new ComplianceViewGroup('Required');

        $physicalExamStart = function($format, User $user) {
           if ($user->getAge() < 50) {
               return date($format, strtotime('-24 months'));
           } else {
               return date($format, strtotime('-12 months'));
           }
        };

        $physicalExam = new CompletePreventionPhysicalExamComplianceView($physicalExamStart, $programEnd);
        $physicalExam->addLink(new Link('More Info', '/resources/5394/Recommended_Screenings.031115.1120.pdf'));
        $physicalExam->setReportName('Physical Exam');
        $requiredGroup->addComplianceView($physicalExam);

        $coloRectalScreening = new CompletePreventionColoRectalScreeningComplianceView(strtotime('-120 months'), $programEnd);
        $coloRectalScreening->setMinimumAge(50);
        $coloRectalScreening->addLink(new Link('More Info', '/resources/5394/Recommended_Screenings.031115.1120.pdf'));
        $coloRectalScreening->setReportName('Colo-rectal Screening');
        $requiredGroup->addComplianceView($coloRectalScreening);


        $mammographyStart = function($format, User $user) {
           if ($user->getAge() < 40) {
               return date($format, strtotime('-36 months'));
           } else {
               return date($format, strtotime('-12 months'));
           }
        };

        $mammography = new CompletePreventionMammographyComplianceView($mammographyStart, $programEnd);
        $mammography->setMinimumAge(40);
        $mammography->addLink(new Link('More Info', '/resources/5394/Recommended_Screenings.031115.1120.pdf'));
        $mammography->setReportName('Mammography');
        $requiredGroup->addComplianceView($mammography);

        $papTest = new CompletePreventionPapTestComplianceView(strtotime('-24 months'), $programEnd);
        $papTest->setMinimumAge(21);
        $papTest->addLink(new Link('More Info', '/resources/5394/Recommended_Screenings.031115.1120.pdf'));
        $papTest->setReportName('Pap Test');
        $requiredGroup->addComplianceView($papTest);

        $psaScreening = new CompletePreventionPSAComplianceView(strtotime('-120 months'), $programEnd);
        $psaScreening->setMinimumAge(50);
        $psaScreening->addLink(new Link('More Info', '/resources/5394/Recommended_Screenings.031115.1120.pdf'));
        $psaScreening->setReportName('Prostate Specific Antigen (PSA)');
        $requiredGroup->addComplianceView($psaScreening);

        $this->addComplianceViewGroup($requiredGroup);
    }
}

class EdwCLevyPreventive2015ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if ($viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $this->printTableRow($viewStatus);
                }
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

    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        use_stylesheet('/css/compliance/Amway20092010ComplianceProgram.css');
        $user = $status->getUser();
        ?>
    <p>Hello <?php echo $user; ?></p>
    <p>This report card tracks your preventative exams completed 1/1/2012 forward.
        It can take approximately 90 days after your claim has been paid for your report card to display an exam.
        Having your preventative exams completed is an important part of managing your health.
        This is another tool to provide you awareness and to take charge of your health!
    </p>
        <p>
            <a href="/resources/5394/Recommended_Screenings.031115.1120.pdf">What are the recommended preventative tests?</a>
        </p>

    <style typ="text/css">
        div#phip {
            float: none;
            width: 100%;
        }
    </style>



    <em>Using a shared computer?</em>
    <strong>If you are not <?php echo $user; ?>, <a href="/logout">Please click here</a>.</strong>
    </p>
    <hr/>

    <div id="phip">
        <div class="pageTitle">Preventative Test Tracking</div>
        <hr>
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
                </tr>
            </thead>
            <tbody>
                <tr id="totalComplianceRow">
                    <!--<td class="resource">Overall Compliance</td>
                    <td class="status">
                        <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
                    </td>
                    <td class="information"></td>
                    <td class="links"></td>-->
                </tr>
                <?php $this->printTableRows($status); ?>
            </tbody>

        </table>

        <div id="endNote">
            <div>

            </div>
            <br/>

            <div>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <?php
    }
}