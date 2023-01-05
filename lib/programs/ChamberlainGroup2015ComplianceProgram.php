<?php
class ChamberlainGroup2015WeighInComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($startDate, $endDate, $week)
    {
        $this->setDateRange('2015-01-01', $endDate);

        $this->id = 434;
        $this->week = $week;
        $this->weekQuestionId = 153;
        $this->weightQuestionId = 154;
        $this->heightQuestionId = 155;
        $this->weightInWeek = 'Week1: 07/27/2015 through 08/02/2015';
        $this->weightOutWeek = 'Week12: 10/12/2015 through 10/18/2015';
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $week = null;
        $weight = null;
        $height = null;
        $date = null;
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->weekQuestionId])) {
                $week = $answers[$this->weekQuestionId]->getAnswer();
            }

            if(empty($week) || $week != $this->week) {
                continue;
            }

            if (isset($answers[$this->weightQuestionId])) {
                $weight = $answers[$this->weightQuestionId]->getAnswer();
            }

            $date = $record->getDate();
        }

        if(!empty($weight)) {
            $status = new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
            $status->setAttribute('date', $date);
            $status->setAttribute('weight', $weight);

            $weighInData = $this->getWeighInData($user);
            $weighOutData = $this->getWeighOutData($user);
            $programGoal = $this->getProgramGoal($weighInData['height'], $weighInData['weight'], $weighInData['bmi']);
            $goalWeight = $this->getGoalWeight($weighInData['height'], $weighInData['weight'], $weighInData['bmi']);

            if($this->isWeightInWeek($this->week)) {
                $status->setAttribute('height', $weighInData['height']);
                $status->setAttribute('bmi', $weighInData['bmi']);
                $status->setAttribute('program_goal', $programGoal);
                $status->setAttribute('goal_weight', $goalWeight);
            } elseif ($this->isWeightOutWeek($this->week)) {
                $status->setAttribute('height', $weighInData['height']);
                $status->setAttribute('bmi', $weighOutData['bmi']);
                $goalStatus = 'Not Met';

                if($weighInData['bmi'] > 24.9 && $weighOutData['weight'] <= $goalWeight) {
                    $goalStatus = "Goal Met";
                } elseif ($weighInData['bmi'] < 18.5 && $weighOutData['weight'] >= $goalWeight) {
                    $goalStatus = "Goal Met";
                } else {
                    $goalStatus = "Goal Met";
                }

                $status->setAttribute('goal_status', $goalStatus);
            } else {
                $difference = $weight -  $weighInData['weight'];
                $goalStatus = 'Not Met';

                if($weighInData['bmi'] > 24.9 && $weight <= $goalWeight) {
                    $goalStatus = "Goal Met";
                } elseif ($weighInData['bmi'] < 18.5 && $weight >= $goalWeight) {
                    $goalStatus = "Goal Met";
                } else {
                    $goalStatus = "Goal Met";
                }

                $status->setAttribute('difference', $difference);
                $status->setAttribute('goal_status', $goalStatus);
            }


        } else {
            $status = new ComplianceViewStatus($this, null);
        }


        return $status;
    }

    protected function getProgramGoal($height, $weight, $bmi)
    {
        $programGoal = null;
        if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
            $programGoal = 'MAINTAIN';
        } elseif (isset($bmi) && $bmi > 24.9) {
            $idealBMI = 24.9;

            $idealBMIWeight = ($idealBMI * $height * $height) / 703.0;
            $idealDecreasedWeight = $weight - ($weight * 0.03);

            $programGoal = $idealBMIWeight >= $idealDecreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 3%';
        } elseif (isset($bmi) && $bmi < 18.5) {
            $idealBMI = 18.5;

            $idealBMIWeight = ($idealBMI * $height * $height) / 703.0;
            $idealIncreasedWeight = $weight + ($weight * 0.03);

            $programGoal = $idealBMIWeight <= $idealIncreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 3%';
        }

        return $programGoal;
    }

    protected function getGoalWeight($height, $weight, $bmi)
    {
        $goalWeight = null;
        if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
            $goalWeight = $weight;
        } elseif (isset($bmi) && $bmi > 24.9) {
            $idealBMI = 24.9;

            $idealBMIWeight = ($idealBMI * $height * $height) / 703.0;
            $idealDecreasedWeight = $weight - ($weight * 0.03);

            $goalWeight = round($idealBMIWeight >= $idealDecreasedWeight ? $idealBMIWeight : $idealDecreasedWeight, 2);
        } elseif (isset($bmi) && $bmi < 18.5) {
            $idealBMI = 18.5;

            $idealBMIWeight = ($idealBMI * $height * $height) / 703.0;
            $idealIncreasedWeight = $weight + ($weight * 0.03);

            $goalWeight = round($idealBMIWeight <= $idealIncreasedWeight ? $idealBMIWeight : $idealIncreasedWeight, 2);
        }

        return $goalWeight;
    }

    protected function getWeighInData(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $weighIn = array(
            'weight' => null,
            'height' => null,
            'bmi'    => null
        );
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->weekQuestionId])) {
                $week = $answers[$this->weekQuestionId]->getAnswer();
                if($week == $this->weightInWeek) {
                    if (isset($answers[$this->weightQuestionId])) {
                        $weighIn['weight'] = $answers[$this->weightQuestionId]->getAnswer();
                    }

                    if (isset($answers[$this->heightQuestionId])) {
                        $weighIn['height'] = $answers[$this->heightQuestionId]->getAnswer();
                    }

                    if(isset($weighIn['weight']) && isset($weighIn['height']) && $weighIn['weight'] > 0 && $weighIn['height'] > 0) {
                        $weighIn['bmi'] = ($weighIn['weight'] * 703.0) / ($weighIn['height'] * $weighIn['height']);
                        $weighIn['bmi'] = round($weighIn['bmi'], 2);
                    }

                }
            }
        }

        return $weighIn;
    }

    protected function getWeighOutData(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $weighOut = array(
            'weight' => null,
            'height' => null,
            'bmi'    => null
        );

        $weighInData = $this->getWeighInData($user);

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->weekQuestionId])) {
                $week = $answers[$this->weekQuestionId]->getAnswer();
                if($week == $this->weightOutWeek) {
                    if (isset($answers[$this->weightQuestionId])) {
                        $weighOut['weight'] = $answers[$this->weightQuestionId]->getAnswer();
                    }

                    if(isset($weighOut['weight']) && isset($weighInData['height']) && $weighOut['weight'] > 0 && $weighInData['height'] > 0) {
                        $weighOut['bmi'] = ($weighOut['weight'] * 703.0) / ($weighInData['height'] * $weighInData['height']);
                        $weighOut['bmi'] = round($weighOut['bmi'], 2);
                    }
                }
            }
        }

        return $weighOut;
    }

    private function isWeightInWeek($week)
    {
        return $week == $this->weightInWeek ? true : false;
    }

    private function isWeightOutWeek($week)
    {
        return $week == $this->weightOutWeek ? true : false;
    }

    private $id;
    private $week;
    private $weekQuestionId;
    private $weightQuestionId;
    private $heightQuestionId;
    private $weightInWeek;
    private $weightOutWeek;
}

class ChamberlainGroup2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required Actions to Participate &amp; Earn Incentive');

        $week1 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week1: 07/27/2015 through 08/02/2015');
        $week1->setReportName('Week 1');
        $week1->setName('week1');
        $week1->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week1'));
        $requiredGroup->addComplianceView($week1);

        $week2 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week2: 08/03/2015 through 08/09/2015');
        $week2->setReportName('Week 2');
        $week2->setName('week2');
        $week2->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week2'));
        $requiredGroup->addComplianceView($week2);

        $week3 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week3: 08/10/2015 through 08/16/2015');
        $week3->setReportName('Week 3');
        $week3->setName('week3');
        $week3->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week3'));
        $requiredGroup->addComplianceView($week3);

        $week4 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week4: 08/17/2015 through 08/23/2015');
        $week4->setReportName('Week 4');
        $week4->setName('week4');
        $week4->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week4'));
        $requiredGroup->addComplianceView($week4);

        $week5 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week5: 08/24/2015 through 08/30/2015');
        $week5->setReportName('Week 5');
        $week5->setName('week5');
        $week5->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week5'));
        $requiredGroup->addComplianceView($week5);

        $week6 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week6: 08/31/2015 through 09/06/2015');
        $week6->setReportName('Week 6');
        $week6->setName('week6');
        $week6->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week6'));
        $requiredGroup->addComplianceView($week6);

        $week7 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week7: 09/07/2015 through 09/13/2015');
        $week7->setReportName('Week 7');
        $week7->setName('week7');
        $week7->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week7'));
        $requiredGroup->addComplianceView($week7);

        $week8 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week8: 09/14/2015 through 09/20/2015');
        $week8->setReportName('Week 8');
        $week8->setName('week8');
        $week8->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week8'));
        $requiredGroup->addComplianceView($week8);

        $week9 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week9: 09/21/2015 through 09/27/2015');
        $week9->setReportName('Week 9');
        $week9->setName('week9');
        $week9->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week9'));
        $requiredGroup->addComplianceView($week9);

        $week10 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week10: 09/28/2015 through 10/04/2015');
        $week10->setReportName('Week 10');
        $week10->setName('week10');
        $week10->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week10'));
        $requiredGroup->addComplianceView($week10);

        $week11 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week11: 10/05/2015 through 10/11/2015');
        $week11->setReportName('Week 11');
        $week11->setName('week11');
        $week11->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week11'));
        $requiredGroup->addComplianceView($week11);

        $week12 = new ChamberlainGroup2015WeighInComplianceView($startDate, $endDate, 'Week12: 10/12/2015 through 10/18/2015');
        $week12->setReportName('Week 12');
        $week12->setName('week12');
        $week12->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=434#week12'));
        $requiredGroup->addComplianceView($week12);

        $this->addComplianceViewGroup($requiredGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $weighInStatus = $status->getComplianceViewStatus('week1');
        $weightOutStatus = $status->getComplianceViewStatus('week2');

        if($weighInStatus->getStatus() == ComplianceViewStatus::COMPLIANT && $weightOutStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        parent::evaluateAndStoreOverallStatus($status);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data = array();
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($viewStatus->getComplianceView()->getName() == 'week1') {
                        $data['Week #1 Date'] = $viewStatus->getAttribute('date');
                        $data['Week #1 Height'] = $viewStatus->getAttribute('height');
                        $data['Week #1 Weight'] = $viewStatus->getAttribute('weight');
                        $data['Week #1 BMI'] = $viewStatus->getAttribute('bmi');
                        $data['Week #1 Program Goal'] = $viewStatus->getAttribute('program_goal');
                        $data['Week #1 Goal Weight'] = $viewStatus->getAttribute('goal_weight');
                    } else {
                        $data[sprintf('%s Weight', $viewName)] = $viewStatus->getAttribute('weight');
                    }
                }
            }

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ChamberlainGroup2015ComplianceProgramReportPrinter();
    }
}

class ChamberlainGroup2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $week1Status = $status->getComplianceViewStatus('week1');
        $week2Status = $status->getComplianceViewStatus('week2');
        $week3Status = $status->getComplianceViewStatus('week3');
        $week4Status = $status->getComplianceViewStatus('week4');
        $week5Status = $status->getComplianceViewStatus('week5');
        $week6Status = $status->getComplianceViewStatus('week6');
        $week7Status = $status->getComplianceViewStatus('week7');
        $week8Status = $status->getComplianceViewStatus('week8');
        $week9Status = $status->getComplianceViewStatus('week9');
        $week10Status = $status->getComplianceViewStatus('week10');
        $week11Status = $status->getComplianceViewStatus('week11');
        $week12Status = $status->getComplianceViewStatus('week12');

        ?>
        <style type="text/css">
            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:46px;
            }

            #legend td {
                padding:8px !important;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable td {
                text-align: center;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .section {
                height:16px;
                color: white;
                background-color:#436EEE;
            }

            .requirement {
                width: 350px;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
            }

        </style>

        <table class="phipTable">
            <tbody>

            <tr>
                <th colspan="6" class="section">
                    <span style="font-weight:bolder; font-size: 12pt;">1. Healthy Weight Challenge Weigh-In Week #1</span>
                </th>
            </tr>
            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Week #1</td>
                <td class="center">Date</td>
                <td class="center">Height</td>
                <td class="center">Weight</td>
                <td class="center">BMI</td>
                <td class="links">Links</td>
            </tr>

            <tr>
                <td class="week">Weigh-In</td>
                <td class="date"><?php echo $week1Status->getAttribute('date') ?></td>
                <td class="height"><?php echo $week1Status->getAttribute('height') ?></td>
                <td class="weight"><?php echo $week1Status->getAttribute('weight') ?></td>
                <td class="bmi"><?php echo $week1Status->getAttribute('bmi') ?></td>
                <td class="links"><?php foreach($week1Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                 }?></td>
            </tr>

            <tr>
                <td>Program Goal</td>
                <td colspan="5"><?php echo $week1Status->getAttribute('program_goal') ?></td>
            </tr>

            <tr>
                <td>Goal Weight</td>
                <td colspan="5"><?php echo $week1Status->getAttribute('goal_weight') ?> lbs</td>
            </tr>

            <tr>
                <th colspan="6" class="section">
                    <span style="font-weight:bolder; font-size: 12pt;">2. Healthy Weight Challenge Measuring Progess Weeks #2-11</span>
                </th>
            </tr>
            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Week</td>
                <td class="center">Date</td>
                <td class="center">Weight</td>
                <td class="center">Difference</td>
                <td class="center">Status</td>
                <td class="links">Links</td>
            </tr>
            <tr>
                <td class="week">Weigh #2</td>
                <td class="date"><?php echo $week2Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week2Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week2Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week2Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week2Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #3</td>
                <td class="date"><?php echo $week3Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week3Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week3Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week3Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week3Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #4</td>
                <td class="date"><?php echo $week4Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week4Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week4Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week4Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week4Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #5</td>
                <td class="date"><?php echo $week5Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week5Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week5Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week5Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week5Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>

            <tr>
                <td class="week">Weigh #6</td>
                <td class="date"><?php echo $week6Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week6Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week6Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week6Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week6Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #7</td>
                <td class="date"><?php echo $week7Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week7Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week7Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week7Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week7Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #8</td>
                <td class="date"><?php echo $week8Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week8Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week8Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week8Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week8Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #9</td>
                <td class="date"><?php echo $week9Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week9Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week9Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week9Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week9Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #10</td>
                <td class="date"><?php echo $week10Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week10Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week10Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week10Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week10Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>
            <tr>
                <td class="week">Weigh #11</td>
                <td class="date"><?php echo $week11Status->getAttribute('date') ?></td>
                <td class="weight"><?php echo $week11Status->getAttribute('weight') ?></td>
                <td class="difference"><?php echo $week11Status->getAttribute('difference') ?></td>
                <td class="status"><?php echo $week11Status->getAttribute('goal_status') ?></td>
                <td class="links"><?php foreach($week11Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>

            <tr>
                <th colspan="6" class="section">
                    <span style="font-weight:bolder; font-size: 12pt;">3. Healthy Weight Challenge Weigh-Out Week #12</span>
                </th>
            </tr>
            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Week #12</td>
                <td class="center">Date</td>
                <td class="center">Height</td>
                <td class="center">Weight</td>
                <td class="center">BMI</td>
                <td class="links">Links</td>
            </tr>
            <tr>
                <td class="week">Weigh-Out</td>
                <td class="date"><?php echo $week12Status->getAttribute('date') ?></td>
                <td class="height"><?php echo $week12Status->getAttribute('height') ?></td>
                <td class="weight"><?php echo $week12Status->getAttribute('weight') ?></td>
                <td class="bmi"><?php echo $week12Status->getAttribute('bmi') ?></td>
                <td class="links"><?php foreach($week12Status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
            </tr>

            <tr>
                <td class="week">Goal Status</td>
                <td colspan="5"><?php echo $week12Status->getAttribute('goal_status') ?></td>
            </tr>

            <tr>
                <td>
                    <strong>Program Compliance</strong>
                </td>
                <td colspan="5"><img src="<?php echo $status->getLight() ?>" class="light" /></td>
            </tr>
            </tbody>
        </table>
        <?php
    }

}