<?php

class ScreeningProgramReportPrinterMidland implements ComplianceProgramReportPrinter
{
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    public function blacklistClass($className)
    {
        $this->blackList[] = $className;
    }

    public function setShowPoints($boolean)
    {
        $this->showPoints = $boolean;

        return $this;
    }

    public function setShowLight($boolean)
    {
        $this->showLight = $boolean;
    }

    public function bindGroup($name)
    {
        $this->groupName = $name;
    }

    public function returnDataJson($viewStatuses) {
        $points = [];
        foreach($viewStatuses as $viewStatus) {
            $report = [];
            $view = $viewStatus->getComplianceView();
            $report['test_name'] = $view->getReportName();
            $report['points'] =  $viewStatus->getPoints();
            array_push($points, $report);
        }

        return json_encode($points); 
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $_pp = Piranha::getInstance()->getPagePrinter();

        $this->setShowLight(true);

        

        $whiteListedClassNames = array(
            'ComplyWithBloodPressureScreeningTestComplianceView',
            'ComplyWithBodyFatBMIWaistRatioScreeningTestComplianceView',
            'ComplyWithSmokingHRAQuestionComplianceView',
            'ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView',
            'ComplyWithBodyFatBMIScreeningTestComplianceView'
        );
        $viewStatuses = array();

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $group = $groupStatus->getComplianceViewGroup();

            if($this->groupName === null || $this->groupName == $group->getName()) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $view = $viewStatus->getComplianceView();
                    $subStatuses = array();
                    if($view instanceof ComplyWithCoreBiometricsComplianceView) {
                        $subStatuses = $view->getComplianceViewStatuses($status->getUser());
                    } else {
                        $subStatuses = array($viewStatus);
                    }
                    foreach($subStatuses as $viewStatus) {
                        $showView = false;
                        $view = $viewStatus->getComplianceView();
                        if(in_array(get_class($view), $whiteListedClassNames) && !in_array(get_class($view), $this->blackList)) {
                            $showView = true;
                        } else if($view instanceof ComplyWithScreeningTestComplianceView) {
                            $showView = true;
                        }

                        if($showView) {
                            $viewStatuses[] = $viewStatus;
                        }
                    }
                }
            }
        }
        if(isset($_GET['type']) && $_GET['type'] == 'json') {
            
            echo $this->returnDataJson($viewStatuses);
            die;
        }
        if(count($viewStatuses) < 1) {
            $_pp->addStatusMessage('Your program does not have any screening results.');
        } else {
            ?>
        <style type="text/css">
            .phipTable {
                width:100%;
                border-collapse:collapse;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable .headerRow {
                background-color:#365f91;
                font-weight:normal;
                color:#FFFFFF;
                font-size:12pt;
            }

            .phipTable .headerRow th {
                text-align:center;
                font-weight:normal;
            }

            .phipTable .points, .phipTable .result {
                text-align:center;
            }

            .pageTitle {
                color:#158E4C;
                font-size:14pt;
                margin-bottom:10px;
            }

            .light {
                text-align:center;
            }

            .light img {
                width:24px;
            }

            .left_container {
                width: 30%;
                display: inline-block;
                background-color: #fde9d9;
                padding: 5px;
            }

            .left_container p {
                line-height: 16px;
                margin-bottom: 0;
            }

            .right_container {
                width: 65%;
                margin-left: 20px;
                display: inline-block;
                vertical-align: top;
            }

            .left_container ul {
                margin-left: 0px;
                padding-left: 20px;
                line-height: 16px;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .success {
                background: #ccffcc;
            }

            .warning {
                background: #ffff00;
            }

            .fail {
                background: #ff909a;
            }

            .no-decorate {
                list-style-type: none;
                margin: 0;
                padding: 0;
            }

            .no-decorate li {
                text-align: center;
            }
            .full table tr td {
                padding: 10px;
                border: 1px solid #000;
            }

            .pad-it {
                margin: 0; padding: 0 0 0 20px;
            }

            .full {
                margin-top: 30px;
            }
        </style>

        <?php
            $report_name_map = [
                'Total/HDL Cholesterol Ratio' => 'HDL Cholesterol',
                'Total/LDL Cholesterol Ratio' => 'LDL Cholesterol',
                'Body Mass Index (BMI) < 30'  => 'Body Mass Index (BMI)'
            ];
        ?>

        <?php if($this->pageTitle !== null) { ?>
            <div class="pageTitle"><?php echo $this->pageTitle; ?></div>
            <?php } ?>
        <div class="body">
            <div class="left_container">
                <p>These key measures and not using tobacco are strongly connected with your ability to prevent and avoid one or more of the following:
                <ul>
                    <li>Clogged arteries, heart attacks and strokes;</li>
                    <li>Diabetes, loss of vision, amputations &amp; other complications;</li>
                    <li>Certain cancers – lung, gum, lip, tongue, throat, breast...</li>
                    <li>Back pain, hip and knee replacements;</li>
                    <li>Loss of mobility and quality of life at a young age; </li>
                    <li>and Loss of life at a young age.</li>
                </ul>
            </div>
            <div class="right_container">
                <table class="phipTable">
                    <tbody>
                        <tr class="headerRow">
                            <th>Screening Test</th>
                            <th>Result</th>
                            <?php if($this->showPoints) : ?>
                            <th>Points Earned</th>
                            <th>Points Possible</th>
                            <?php endif ?>
                            <?php if($this->showLight) : ?>
                            <th>Status</th>
                            <?php endif ?>
                        </tr>

                        <?php foreach($viewStatuses as $viewStatus) { ?>
                        <?php $view = $viewStatus->getComplianceView(); ?>
                        <tr>
                            <td class="name"><?php echo isset($report_name_map[$view->getReportName()]) ? $report_name_map[$view->getReportName()] : $view->getReportName(); ?></td>
                            <td class="result"><?php echo $viewStatus->getComment(); ?></td>
                            <?php if($this->showPoints) : ?>
                            <td class="points"><?php echo $viewStatus->getPoints(); ?></td>
                            <td class="points"><?php echo $view->getMaximumNumberOfPoints(); ?></td>
                            <?php endif ?>
                            <?php if($this->showLight) : ?>
                            <td class="light"><img src="<?php echo $viewStatus->getLight() ?>" alt=""/></td>
                            <?php endif ?>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="full">
                <table width="100%" cellpadding="100">
                    <tr>
                        <td width="20%" class="text-right" style="border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">Risk ratings & colors =</td>
                        <td width="15%" class="text-center">OK/Good</td>
                        <td width="15%" class="text-center">Borderline</td>
                        <td width="15%" class="text-center">At-Risk</td>
                        <td width="25%" rowspan="9" style="vertical-align: top; padding-top: 50px;">
                            <p>Earn up to <strong>50 points</strong> for each of your results based on the risk rating.</p>

                            <p>Interpreting the ranges and colors:</p>
                            <ul>
                                <li>At-Risk = Call or visit your doctor and share this result.  Ask if a follow-up visit is recommended.</li>
                                <li>Borderline = Share and discuss this result on your next call or visit.</li>
                                <li>OK/Good = Share these results on your next visit.</li>
                                <li>See your report and related links for more information</li>
                            </ul>
                            <p>See the website for tips, tools and resources to get and keep these and other measures in the green!</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center"  style="border-top: 1px solid #ccc; border-left: 1px solid #ccc;">Key measures, ranges <br>&amp; related points</td>
                        <td class="text-center success">50 Points</td>
                        <td class="text-center warning">25 Points</td>
                        <td class="text-center fail">0 Points</td>
                        
                    </tr>
                    <tr>
                        <td>1. Total cholesterol</td>
                        <td class="text-center success">100 - &lt; 200</td>
                        <td class="text-center warning">
                            <ul class="no-decorate">
                                <li>200 - 240</li>
                                <li>90 - &lt;100</li>
                            </ul> 
                        </td>
                        <td class="fail">
                            <ul class="no-decorate">
                                <li>&gt; 240</li>
                                <li>&lt; 90</li>
                            </ul>
                        </td>
                        
                    </tr>
                    <tr>
                        
                        <td>2. HDL cholesterol ^
                            <ul class="text-left">
                                <li>Men</li>
                                <li>Women</li>
                            </ul>
                        </td>
                        <td class="success">
                            <ul class="no-decorate">
                                <li>&#8805; 40</li>
                                <li>&#8805; 50 </li>
                            </ul>
                        </td>
                        <td class="warning">
                            <ul class="no-decorate">
                                <li>25 - &lt;40</li>
                                <li>25 - &lt;50</li>
                            </ul>
                        </td>
                        <td class="fail">
                            <ul class="no-decorate">
                                <li>&lt; 25</li>
                                <li>&lt; 25 </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        
                        <td>3. LDL cholesterol ^</td>
                        <td class="text-center success">&#8804;129</td>
                        <td class="text-center warning">130 - &lt; 159</td>
                        <td class="text-center fail">&#8805;159</td>
                    </tr>
                    <tr>
                        <td>4. Triglycerides</td>
                        <td class="text-center success">&lt; 150</td>
                        <td class="text-center warning">150 - &lt;200</td>
                        <td class="text-center fail">&#8805; 200</td>
                    </tr>
                    <tr>
                        <td>5. Glucose (Fasting)</td>
                        <td class="text-center success">70 - &lt;100</td>
                        <td class="warning">
                            <ul class="no-decorate">
                                <li>100 - &lt;126</li>
                                <li>50 - &lt;70</li>
                            </ul>
                        </td>
                        <td class="fail">
                            <ul class="no-decorate">
                                <li>&#8805; 126</li>
                                <li>&lt; 50</li>
                            </ul> 
                        </td>
                    </tr>
                    <tr>
                        <td>6. Blood pressure*
                            <ul class="no-decorate">
                                <li>Systolic</li>
                                <li>Diastolic </li>
                            </ul>
                        </td>
                        <td class="success">
                            <ul class="no-decorate">
                                <li>&lt; 120</li>
                                <li>&lt; 80</li>
                            </ul>
                        </td>
                        <td class="warning">
                            <ul class="no-decorate">
                                <li>120 - &lt;140</li>
                                <li>80 - &lt;90</li>
                            </ul>
                        </td>
                        <td class="fail">
                            <ul class="no-decorate">
                                <li>&#8805; 140</li>
                                <li>&#8805; 90</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td>7. The better of:<br>
                            <strong>Body Mass Index</strong>
                            <ul class="text-left pad-it">
                                <li>men & women</li>
                            </ul>
                            <div class="pad-it">-- OR --</div>
                            <strong>% Body Fat:</strong>
                            <ul class="text-left pad-it">
                                <li>Men</li>
                                <li>Women</li>
                            </ul>
                        </td>
                        <td class="success">
                            <ul class="no-decorate">
                                <li>18.5 - &lt;25</li>
                                <li>&nbsp;</li>
                                <li>6 - &lt;18%</li>
                                <li>14 - &lt;25%</li>
                            </ul>
                        </td>
                        <td class="warning">
                            <ul class="no-decorate">
                                <li>25 - &lt;30</li> 
                                <li></li>
                                <li>18 - &lt;25</li>
                                <li>25 - &lt;32%</li>
                            </ul>
                        </td>
                        <td class="fail">
                            <ul class="no-decorate">
                                <li>&#8805; 30; &lt; 18.5</li>
                                <li>&nbsp;</li>
                                <li>&#8805; 25; &lt; 6%</li>
                                <li>&#8805; 32; &lt; 14%</li>
                            </ul>
                        </td>

                    </tr>
                    <tr>
                        <td colspan="5">
                            <p>Note:   ^ An HDL of ≥60 and LDL of &lt;100 are optimal with each offering even greater protection against heart disease and strokes.</p>
<p class="center">*  Both systolic and diastolic blood pressure results need to be in the better range for the higher points.</p></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
        }
    }

    private $groupName = null;
    private $pageTitle = null;
    private $blackList = array();
    private $showPoints = true;
    private $showLight = false;
}