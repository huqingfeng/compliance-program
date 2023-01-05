<?php
// This is basically a copy/paste opf RJF's printer :\
class MEI2010ComplianceProgram extends RJF2010ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->getComplianceView('complete_hra')->setEndDate('2011-06-05');

        $this->getComplianceViewGroup('points')
            ->removeComplianceView('work_with_health_coach')
            ->setReportName('Earn 100 or more points from A-J below by '.$this->getEndDate('m/d/Y'))
            ->getComplianceView('work_with_doctor')
            ->addLink(new Link('I did this', '/resources/2407/mei incentive dcm note from doctor 081210.pdf'));

        $this->getComplianceViewGroup('core')
            ->getComplianceView('safety_workshop')
            ->setReportName('Complete the Self Care Workshop');
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if($preferredPrinter) return parent::getProgramReportPrinter($preferredPrinter);

        return new MEIComplianceProgramReportPrinter();
    }
}


class MEIComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $safetyWorkshopStatus = $coreGroupStatus->getComplianceViewStatus('safety_workshop');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bloodPressureStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test');
        $nonSmokingStatus = $pointGroupStatus->getComplianceViewStatus('non_smoker_view');

        $haveDoctorStatus = $pointGroupStatus->getComplianceViewStatus('have_doctor');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluVaccineStatus = $pointGroupStatus->getComplianceViewStatus('flu_vaccine');
        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('physical_activity');
        $healthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach');
        $workWithDoctorStatus = $pointGroupStatus->getComplianceViewStatus('work_with_doctor');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('attend_workshop');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('volunteering');
        $mindfulActivityStatus = $pointGroupStatus->getComplianceViewStatus('mindful_activity');
        ?>
    <style type="text/css">
        .phipTable ul, .phipTable li {
            margin-top:0px;
            margin-bottom:0px;
            padding-top:0px;
            padding-bottom:0px;
        }

        .pageHeading {
            font-weight:bold;
            text-align:center;
            margin-bottom:20px;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable th, .phipTable td {
            border:1px solid #000000;
            padding:2px;
        }

        .phipTable .headerRow {
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
        }

        .phipTable .headerRow th {
            text-align:left;
            font-weight:normal;
        }

        .phipTable .headerRow td {
            text-align:center;
        }

        .phipTable .links {
            text-align:center;
        }

        .center {
            text-align:center;
        }

        .white {
            background-color:#FFFFFF;
        }

        .light {
            width:25px;
        }

        .center {
            text-align:center;
        }

        .right {
            text-align:right;
        }

        #legend, #legend tr, #legend td {
            padding:0px;
            margin:0px;
        }

        #legend td {

            padding-bottom:5px;
        }

        #legendText {
            text-align:center;
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:12pt;
            margin-bottom:5px;
        }

        .legendEntry {
            width:160px;
            float:left;
            text-align:center;
            padding-left:2px;
        }
    </style>


    <div class="pageHeading">Rewards/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>
        Welcome to your summary page for the 2010 Wellness Rewards Program. To
        receive the incentives, eligible employees MUST take action and meet all
        criteria below by <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>:
    </p>

    <ol>
        <li>
            Complete <strong>ALL</strong> of the core required actions; And
        </li>
        <li>
            Earn 100 or more points from key screening results and key actions taken
            for good health.
        </li>
    </ol>

    <p>
        Employees meeting all criteria will each receive a reward of $300 (paid after 6/30/2011).
    </p>

    <div class="pageHeading">
        <a href="/content/1094">
            Click here for the 2010 Wellness Rewards Criteria Details
        </a>.
    </div>

    <p>
        <strong>Update Notice:</strong>
        To get actions done and earn extra points click on the links below. If the
        center is not yet Green for any option you did or are working on, you may
        need to go back and enter missing information, or additional entries to
        earn more points. The center for wellness screening will not change until
        after your report is mailed. <i>Thanks for your actions and patience!</i>
    </p>

    <table class="phipTable" border="1">
    <thead id="legend">
        <tr>
            <td colspan="4">
                <div id="legendText">Legend</div>
                <?php
                foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                            ->getMappings() as $sstatus => $mapping) {
                    $printLegendEntry = false;
                    if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $printLegendEntry = true;
                    } else if($status->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                        $printLegendEntry = true;
                    }

                    if($printLegendEntry) {
                        echo '<div class="legendEntry">';
                        echo '<img src="'.$mapping->getLight().'" class="light" />';
                        echo " = {$mapping->getText()}";
                        echo '</div>';
                    }
                }
                ?>
            </td>
        </tr>
    </thead>
    <tbody>
    <tr class="headerRow">
        <th>1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
        <td>Date Done</td>
        <td>Status</td>
        <td>Links</td>
    </tr>
    <tr>
        <td><a href="/content/1094#1aHA">A. <?php echo $completeScreeningStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $completeScreeningStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#1bHS">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center">
            <?php echo $completeHRAStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $completeHRAStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($completeHRAStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#1cHCSW">C. <?php echo $safetyWorkshopStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $safetyWorkshopStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $safetyWorkshopStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($safetyWorkshopStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="right" style="font-size: 7pt;">
            All Core Actions Done on or before
            <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>
        </td>
        <td></td>
        <td class="center">
            <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No' ?>
        </td>
        <td class="center">
            <?php echo $coreGroupStatus->isCompliant() ? 'Great – Core Actions Done!' : 'NOT Done Yet'; ?>
        </td>
    </tr>
    <tr class="headerRow">
        <th>2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
        <td># Points Earned</td>
        <td># Points Possible</td>
        <td>Links</td>
    </tr>
    <tr>
        <td><a href="/content/1094#2aKBHM">A. Have these screening results in the ideal zone:</a></td>
        <td colspan="3">

        </td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
        <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td rowspan="8" class="links">
            <a href="?preferredPrinter=ScreeningProgramReportPrinter">Click here for the 8 results</a><br/><br/>
            <a href="/content/989">Click for all screening results</a><br/><br/>
            Click on any measure for more info & to improve
        </td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $hdlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
        <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
        <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>

    <tr>
        <td>
            <ul>
                <li><?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bloodPressureStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li><?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li><?php echo $nonSmokingStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $nonSmokingStatus->getPoints(); ?></td>
        <td class="center"><?php echo $nonSmokingStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td><a href="/content/1094#2bMD">B. <?php echo $haveDoctorStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $haveDoctorStatus->getPoints(); ?></td>
        <td class="center"><?php echo $haveDoctorStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($haveDoctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2cRPS">C. <?php echo $preventiveScreeningsStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $preventiveScreeningsStatus->getPoints(); ?></td>
        <td class="center"><?php echo $preventiveScreeningsStatus->getComplianceView()
            ->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($preventiveScreeningsStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2dAFV">D. <?php echo $fluVaccineStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $fluVaccineStatus->getPoints(); ?></td>
        <td class="center"><?php echo $fluVaccineStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($fluVaccineStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2eRPA">E. <?php echo $physicalActivityStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $physicalActivityStatus->getPoints(); ?></td>
        <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($physicalActivityStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2fDoc">F. <?php echo $workWithDoctorStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $workWithDoctorStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workWithDoctorStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workWithDoctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML().' ';
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2gKHE">G. <?php echo $workshopStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $workshopStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workshopStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workshopStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2hEL">H. <?php echo $extraELearningStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $extraELearningStatus->getPoints(); ?></td>
        <td class="center"><?php echo $extraELearningStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($extraELearningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2iVT">I. <?php echo $volunteeringStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $volunteeringStatus->getPoints(); ?></td>
        <td class="center"><?php echo $volunteeringStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($volunteeringStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2jMA">J. <?php echo $mindfulActivityStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $mindfulActivityStatus->getPoints(); ?></td>
        <td class="center"><?php echo $mindfulActivityStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($mindfulActivityStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="right">Total number as of <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
        <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
        <td></td>
    </tr>
    <tr class="headerRow">
        <th>3. Deadlines, Requirements & Status</th>
        <td># Points</td>
        <td>Status</td>
        <td>Needed By <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?></td>
    </tr>

    <tr>
        <td style="text-align: right;">Total & Credit Status: Through <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center">100 or more points + 1ABC done</td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}