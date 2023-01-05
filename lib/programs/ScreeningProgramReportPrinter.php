<?php

class ScreeningProgramReportPrinter implements ComplianceProgramReportPrinter
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

    public function printReport(ComplianceProgramStatus $status)
    {
        $_pp = Piranha::getInstance()->getPagePrinter();

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
                background-color:#158E4C;
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
        </style>

        <?php if($this->pageTitle !== null) { ?>
            <div class="pageTitle"><?php echo $this->pageTitle; ?></div>
            <?php } ?>

        <table class="phipTable">
            <tbody>
                <tr class="headerRow">
                    <th>Screening Test</th>
                    <th>Result</th>
                    <?php if($this->showPoints) : ?>
                    <th>Points Earned</th>
                    <th>Maximum Points</th>
                    <?php endif ?>
                    <?php if($this->showLight) : ?>
                    <th>Status</th>
                    <?php endif ?>
                </tr>

                <?php foreach($viewStatuses as $viewStatus) { ?>
                <?php $view = $viewStatus->getComplianceView(); ?>
                <tr>
                    <td class="name"><?php echo $view->getReportName(); ?></td>
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
        <?php
        }
    }

    private $groupName = null;
    private $pageTitle = null;
    private $blackList = array();
    private $showPoints = true;
    private $showLight = false;
}