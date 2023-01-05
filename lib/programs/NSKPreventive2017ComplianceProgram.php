<?php

class NSKPreventive2017ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if(sfConfig::get('app_wms2')) {
            return new NSKPreventive2017WMS2Printer();
        } else {
            return new NSKPreventive2017ComplianceProgramReportPrinter();
        }

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
        $physicalExam->setReportName('Physical Exam');
        $requiredGroup->addComplianceView($physicalExam);

        $coloRectalScreening = new CompletePreventionColoRectalScreeningComplianceView(strtotime('-120 months'), $programEnd);
        $coloRectalScreening->setMinimumAge(50);
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
        $mammography->setReportName('Mammography');
        $requiredGroup->addComplianceView($mammography);

        $papTest = new CompletePreventionPapTestComplianceView(strtotime('-24 months'), $programEnd);
        $papTest->setMinimumAge(21);
        $papTest->setReportName('Pap Test');
        $requiredGroup->addComplianceView($papTest);

        $psaScreening = new CompletePreventionPSAComplianceView(strtotime('-120 months'), $programEnd);
        $psaScreening->setMinimumAge(50);
        $psaScreening->setReportName('Prostate Specific Antigen (PSA)');
        $requiredGroup->addComplianceView($psaScreening);

        $this->addComplianceViewGroup($requiredGroup);
    }
}

class NSKPreventive2017ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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

class NSKPreventive2017WMS2Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();

            ?>


            <table class="details-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th class="points">Status</th>
                    <th class="text-center">Date Completed</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1 ?>
                <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php
                    if ($viewStatus->isCompliant()) {
                        $pct = 1;
                    } else if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $pct = 0.5;
                    } else {
                        $pct = 0;
                    }
                    ?>
                    <?php $class = $classFor($pct) ?>
                    <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                        <td class="name">
                            <?php echo $i ?>.
                            <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="points <?php echo $class ?>">
                            <?php echo $viewStatus->getText() ?>
                        </td>
                        <td class="links text-center">
                            <div><?php echo $viewStatus->getComment() ?></div>
                        </td>
                    </tr>
                    <?php $i++ ?>
                <?php endforeach ?>
                </tbody>
            </table>

            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();


            if ($group->getComplianceViewGroup()->getMaximumNumberOfPoints() === null) {
                if ($group->isCompliant()) {
                    $pct = 1;
                } else if ($group->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                    $pct = 0.5;
                } else {
                    $pct = 0;
                }

                $actual = $group->getText();
                $target = $group->getComplianceViewGroup()->getName() == 'Tobacco' ? 'Negative Result' : 'Done';
            } else {
                $points = $group->getPoints();
                $target = '<strong>'.$group->getComplianceViewGroup()->getMaximumNumberOfPoints().'</strong><br/>points';
                $actual = '<strong>'.$points.'</strong><br/>points';
                $pct = $points / $group->getComplianceViewGroup()->getMaximumNumberOfPoints();
            }

            $class = $classFor($pct);
            ?>
            <tr class="picker open">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <?php echo $target ?>
                </td>
                <td class="points <?php echo $class ?>">
                    <?php echo $actual ?>
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details open">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };

        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 300px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1.4em;
            }

            .view-force_compliant {
                display: none;
            }

            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .total-status td, .spouse-status td {
                text-align: center;
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>Preventative Test Tracking</h1>
                <br/>
                <p>
                    Thank you for visiting your Preventative Test Tracking.
                    Your recommended preventive care screenings for your age and gender will be updated quarterly.
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Resource', $status->getComplianceViewGroupStatus('Required')) ?>

                    </tbody>
                </table>
                <br/>

            </div>
        </div>
        <div>
            <a href="https://static.hpn.com/pdf/clients/chp/Recommended_Screenings_2018.pdf">Recommended Preventative Tests</a>
        </div>
        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());

                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'More...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('More...');
                    }
                });
            });
        </script>
        <?php
    }
}