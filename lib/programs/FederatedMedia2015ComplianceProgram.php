<?php

class FederatedMedia2015ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
        <style type="text/css">
            .requirements {
                font-size:10px;
            }

            td.requirements {
                text-align:center;
            }

            .phipTable tr td {
                padding:12px 5px;
            }
        </style>
        <p>Hi <?php echo $_user ?>,</p>
        <p>Federated Media has partnered with Circle Wellness, a
            member of Circle Health Partners, Inc. to provide yearlong wellness
            benefits including the new Circle Wellness Web Tool Suite integrated
            with your onsite screenings. This program is confidential and no one
            will see your personal results except for you. These new benefits
            are designed to help you become more aware of your health, evaluate
            your risks, and track individual goals and efforts toward staying
            healthy.</p>

        <p>In order to receive your HSA incentive, you will be required to
            participate in the on-site health screening provided by On Site
            Health Solutions, LLC as well as complete an online Health Risk
            Appraisal. You will have until January 31, 2015 to complete your
            Health Risk Appraisal (HRA), the HRA will be available beginning December 15, 2014</p>

        <p>Incentive Categories:</p>
        <ol>
            <li>Non-tobacco user (complete affidavit certifying no tobacco use)</li>
            <li>BMI:  <25 or Body fat: <22% for men and <30% for women
                or 5% improvement in weight over previous year</li>
            <li>Blood pressure:  systolic <140 mmHg and diastolic:  <90 mmHg
                or 5% improvement over previous year</li>
            <li>LDL Cholesterol:  <130
                or 5% improvement over previous year
                or Total Cholesterol to HDL Ratio <5.2</li>
            <li>Fasting glucose:  <100 or 5% improvement over previous year</li>
        </ol>
        <br/>
        <table border=1>
            <tbody>
            <tr>
                <th> # of Categories <br />Achieved </th>
                <th> HSA Dollars <br />Earned</th>
            </tr>

            <tr>
                <td>3</td>
                <td>$180</td>
            </tr>
            <tr>
                <td>4</td>
                <td>$420</td>
            </tr>
            <tr>
                <td>5</td>
                <td>$720</td>
            </tr>
            </tbody>
        </table>
        <br />
        <p>All guidelines were established using the national standards established by the National Heart,
            Lung and Blood Institute, the American Heart Association and the American Diabetes Association.
            Your results – personal metrics are completely confidential; only you and your wellness consultant
            will have access to them. A report showing group aggregate results are shared with the company.</p>

        <p>You must meet 3 out of 5 categories or show an improvement of 5% improvement in 3 out of 5
            categories over previous year’s results.</p>

        <p>Rewards for participating in the program are available to all employees.  If you think you
            will be unable to meet a standard for a reward under this program, you might qualify for an
            opportunity to earn the same reward by a different means.  Please contact Tamara McDonald
            (Federated Media) at 574-360-4379 or Fawn Thomas (Truth Publishing) at 574-361-3894 and we
            will work with you (and if you wish, with your doctor) to find an alternative program
            with the same reward that is right for you.
        </p>
        <?php
    }
}

class FederatedMedia2015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new FederatedMedia2015ComplianceProgramReportPrinter();

        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum Points';
        $printer->requirementsHeader = '';

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('Requirements');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, '');
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, '2015-03-07');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, '');

        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $pointsGroup = new ComplianceViewGroup('Points');
        $pointsGroup->setPointsRequiredForCompliance(0);

        $mapper = new ComplianceStatusPointMapper(1, 0, 0, 0);

        $tobacco = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $tobacco->setReportName('Non-Tobacco user');
        $tobacco->setName('tobacco');
        $tobacco->setComplianceStatusPointMapper($mapper);
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, 'Tobacco Free');
        $pointsGroup->addComplianceView($tobacco);

        $bmi = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmi->setComplianceStatusPointMapper($mapper);
        $bmi->setUseHraFallback(true);
        $bmi->overrideBMITestRowData(null, null, 24.999, null);
        $bmi->overrideBodyFatTestRowData(null, null, 21.999, null, Gender::MALE);
        $bmi->overrideBodyFatTestRowData(null, null, 29.999, null, Gender::FEMALE);
        $bmi->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI: &lt; 25 or<br/> Body Fat: (Male) &lt; 22% <br/> Body Fat: (Female) &lt; 30% <br/> Or 5% improvement in weight over previous year');
        $bmi->setPostEvaluateCallback($this->checkReduction(array('weight')));

        $pointsGroup->addComplianceView($bmi);

        $bp = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bp->setComplianceStatusPointMapper($mapper);
        $bp->setUseHraFallback(true);
        $bp->overrideSystolicTestRowData(null, null, 139.999, null);
        $bp->overrideDiastolicTestRowData(null, null, 89.999, null);
        $bp->setStatusSummary(ComplianceStatus::COMPLIANT, 'Systolic: &lt; 140 <br/> Diastolic: &lt; 90 <br/> Or 5% improvement over previous year');
        $bp->setPostEvaluateCallback($this->checkReduction(array('systolic', 'diastolic')));

        $pointsGroup->addComplianceView($bp);

        $ldl = new ComplyWithLDLTotalHDLCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $ldl->setComplianceStatusPointMapper($mapper);
        $ldl->overrideLDLTestRowData(null, null, 129.999, null);
        $ldl->overrideTotalHDLCholesterolRatioTestRowData(null, null, 5.1999, null);
        $ldl->setStatusSummary(ComplianceStatus::COMPLIANT, 'LDL: &lt; 130 <br/> Or 5% improvement over previous year <br/> Or TC to HDL Ratio: &lt; 5.2');
        $ldl->setPostEvaluateCallback($this->checkReduction(array('ldl')));

        $pointsGroup->addComplianceView($ldl);

        $glucose = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucose->setComplianceStatusPointMapper($mapper);
        $glucose->overrideTestRowData(null, null, 99.999, null);
        $glucose->setStatusSummary(ComplianceStatus::COMPLIANT, '&lt; 100 or 5% improvement over previous year');
        $glucose->setPostEvaluateCallback($this->checkReduction(array('glucose')));

        $pointsGroup->addComplianceView($glucose);

        $this->addComplianceViewGroup($pointsGroup);
    }

    protected function checkReduction(array $tests) {
        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2014-01-01');
        $lastEnd = new \DateTime('2014-04-15');

        return function(ComplianceViewStatus $status, User $user) use ($tests, $programStart, $programEnd, $lastStart, $lastEnd) {
            static $cache = null;

            if ($cache === null || $cache['user_id'] != $user->id) {
                $cache = array(
                    'user_id' => $user->id,
                    'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd),
                    'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd)
                );
            }

            if (count($tests) > 0 && $cache['this'] && $cache['last']) {
                $isReduced = true;

                foreach($tests as $test) {
                    $lastVal = isset($cache['last'][0][$test]) ? (float) $cache['last'][0][$test] : null;
                    $thisVal = isset($cache['this'][0][$test]) ? (float) $cache['this'][0][$test] : null;

                    if (!$thisVal || !$lastVal || $lastVal * 0.95 < $thisVal) {
                        $isReduced = false;

                        break;
                    }
                }

                if ($isReduced) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        };
    }
}