<?php

class FederatedMedia2014ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
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
            Appraisal.</p>

        <p>Incentive Categories:</p>
        <ol>
            <li>Non-tobacco user (complete affidavit)</li>
            <li>BMI:  <30 or Body fat: <26% for men and <32% for women</li>
            <li>Blood pressure:  systolic <140 mmHg and diastolic:  <90 mmHg</li>
            <li>LDL Cholesterol:  <130 or Total Cholesterol to HDL Ratio <4.6</li>
            <li>Fasting glucose:  <126</li>
        </ol>
        <br/>
        <table border=1>
            <tbody>
            <tr>
                <th> # of Categories <br />Achieved </th>
                <th> HSA Dollars <br />Earned</th>
            </tr>
            <tr>
                <td>2</td>
                <td>$60</td>
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
        <p>Federated Media is committed to helping you achieve your best health through the
            wellness program we offer.  Rewards for participating in the program are available to all employees.
            If you think you will be unable to meet the standard for a reward under this program, you might qualify
            for an opportunity to earn the same reward by a different means.  Please contact Tamara McDonald
            (Federated Media) at 574-360-4379 or Fawn Thomas (Truth Publishing) at 574-294-1661 and we will work with
            you (and if you wish, with your doctor) to find an alternative program with the same reward that is right for you.
        </p>
        <?php
    }
}

class FederatedMedia2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new FederatedMedia2014ComplianceProgramReportPrinter();

        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum Points';
        $printer->requirementsHeader = 'Deadline';

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('Requirements');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, $this->getEndDate('m/d/Y'));
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, $this->getEndDate('m/d/Y'));

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
        $bmi->overrideBMITestRowData(null, null, 29.999, null);
        $bmi->overrideBodyFatTestRowData(null, null, 25.999, null, Gender::MALE);
        $bmi->overrideBodyFatTestRowData(null, null, 31.999, null, Gender::FEMALE);
        $bmi->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI: &lt; 30 or<br/> Body Fat: (Male) &lt; 26% <br/> Body Fat: (Female) &lt; 32% ');

        $pointsGroup->addComplianceView($bmi);

        $bp = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bp->setComplianceStatusPointMapper($mapper);
        $bp->setUseHraFallback(true);
        $bp->overrideSystolicTestRowData(null, null, 139.999, null);
        $bp->overrideDiastolicTestRowData(null, null, 89.999, null);
        $bp->setStatusSummary(ComplianceStatus::COMPLIANT, 'Systolic: &lt; 140 <br/> Diastolic: &lt; 90');

        $pointsGroup->addComplianceView($bp);

        $ldl = new ComplyWithLDLTotalHDLCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $ldl->setComplianceStatusPointMapper($mapper);
        $ldl->overrideLDLTestRowData(null, null, 129.999, null);
        $ldl->overrideTotalHDLCholesterolRatioTestRowData(null, null, 4.5999, null);
        $ldl->setStatusSummary(ComplianceStatus::COMPLIANT, 'LDL: &lt; 130 or<br/> TC to HDL Ratio: &lt; 4.6');

        $pointsGroup->addComplianceView($ldl);

        $glucose = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucose->setComplianceStatusPointMapper($mapper);
        $glucose->overrideTestRowData(null, null, 125.999, null);
        $glucose->setStatusSummary(ComplianceStatus::COMPLIANT, '&lt; 126');

        $pointsGroup->addComplianceView($glucose);

        $this->addComplianceViewGroup($pointsGroup);
    }
}