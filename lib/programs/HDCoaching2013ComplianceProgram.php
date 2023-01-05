<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class HDCoaching2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();
            $printer->blacklistClass('ComplyWithSmokingHRAQuestionComplianceView');

            $screening = ScreeningTable::getInstance()->findCompletedForUserBetweenDates(
                $this->getActiveUser(),
                new DateTime('@'.$this->getStartDate()),
                new DateTime('@'.$this->getEndDate()),
                array('execute' => false)
            )->limit(1)->fetchOne();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new HDCoaching2013ComplianceProgramReportPrinter();
            $printer->setShowTotal(false);
        }

        return $printer;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required');

        $hraView = new CompleteHRAComplianceView($startDate, $endDate);
        $hraView->setName('hra');
        $hraView->setReportName('Annual Health Risk Assessment');
        $hraView->setAttribute('report_name_link', '/content/under_construction');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('', '/content/coming_soon'));
        $hraView->addLink(new Link('Do HRA / Results', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($startDate, $endDate);
        $screeningView->setName('screening');
        $screeningView->setReportName(' Annual Health Dynamics Exam');
        $screeningView->setAttribute('report_name_link', '/content/1035');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/1028'));
        $screeningView->addLink(new Link('Find Site', '/content/1028'));
        //$screeningView->addLink(new Link('Prior Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $tobaccoFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobaccoFree->setName('smoking');
        $tobaccoFree->setReportName('Review Exam Results with Health Coach - by phone');
        $tobaccoFree->setAttribute('report_name_link', '/content/under_construction');
        $tobaccoFree->addLink(new Link('Schedule Time', '/content/8733'));
        $coreGroup->addComplianceView($tobaccoFree);

        $elearningView = new CompleteELearningGroupSet($startDate, $endDate, 'required_2012');
        $elearningView->setName('required_elearning');
        $elearningView->setReportName('Complete these key eLearning lessons');
        //$elearningView->setAttribute('report_name_link', '/content/under_construction');
        $elearningView->addLink(new Link('View / Do Lessons', '/content/9420?action=lessonManager&tab_alias=assigned'));

      $coreGroup->addComplianceView($elearningView);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Primary Physician');
        $doctorView->setAttribute('report_name_link', '/content/under_construction');
        $doctorView->setName('doctor');
        $doctorView->emptyLinks();
        $doctorView->addLink(new Link('Enter/Update Info', '/my_account/updateDoctor?redirect=/compliance_programs'));
        $coreGroup->addComplianceView($doctorView);

        $this->addComplianceViewGroup($coreGroup);

        $extraGroup = new ComplianceViewGroup('extra', 'And, earn points from options below.');
        $extraGroup->setPointsRequiredForCompliance(50);

        $screeningMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $tc = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $tc->setAttribute('report_name_link', '/content/11008');
        $tc->setAttribute('report_name_link_popup', true);
        $tc->setAttribute('_screening_printer_hack', 8);
        $tc->setName('total_cholesterol');
        $tc->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($tc);

        $hdl = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdl->setAttribute('report_name_link', '/content/11012');
        $hdl->setAttribute('report_name_link_popup', true);
        $hdl->setName('hdl');
        $hdl->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($hdl);

        $ldl = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldl->setAttribute('report_name_link', '/content/11016');
        $ldl->setAttribute('report_name_link_popup', true);
        $ldl->setName('ldl');
        $ldl->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($ldl);

        $triglycerides = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $triglycerides->setAttribute('report_name_link', '/content/11020');
        $triglycerides->setAttribute('report_name_link_popup', true);
        $triglycerides->setName('triglycerides');
        $triglycerides->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($triglycerides);

        $glucose = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucose->setAttribute('report_name_link', '/content/11024');
        $glucose->setAttribute('report_name_link_popup', true);
        $glucose->setName('glucose');
        $glucose->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($glucose);

        $bp = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bp->setAttribute('report_name_link', '/content/11004');
        $bp->setAttribute('report_name_link_popup', true);
        $bp->setName('blood_pressure');
        $bp->setReportName('Blood pressure');
        $bp->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($bp);

        $bf = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $endDate);
        $bf->setAttribute('report_name_link', '/content/112335');
        $bf->setAttribute('report_name_link_popup', true);
        $bf->setName('body_fat_bmi');
        $bf->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($bf);

        $wst = new ComplyWithWaistScreeningTestComplianceView($startDate, $endDate);
        $wst->setReportName('Waist Circumference');
        $wst->setName('waist');
        $wst->setComplianceStatusPointMapper($screeningMapper);
        $extraGroup->addComplianceView($wst);

        $elearnAdditional = new CompleteAdditionalELearningLessonsComplianceView($startDate, $endDate);
        $elearnAdditional->setAttribute('report_name_link', '/content/under_construction');
        $elearnAdditional->setName('additional_elearning');
        $elearnAdditional->setReportName('Additional e-Learning Lessons');
        $elearnAdditional->setMaximumNumberOfPoints(20);
        $elearnAdditional->setPointsPerLesson(5);
        $extraGroup->addComplianceView($elearnAdditional);

        $coach = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $coach->setMaximumNumberOfPoints(20);
        $coach->setReportName('Work with a Health Coach on Health Goals');
        $coach->emptyLinks();
        $coach->addLink(new Link('View / Do Lessons', '/content/9420?action=lessonManager&tab_alias=assigned'));
        $extraGroup->addComplianceView($coach);

        $this->addComplianceViewGroup($extraGroup);
    }
}

class HDCoaching2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
    <style type="text/css">
        .phipTable .headerRow, #legendText {
            background-color:#800000;
            color:#FFFFFF;
            font-size:10pt;
            font-weight:normal;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
        }
    </style>
    <p>Text about incentives, requirements in tips for table below goes here.</p>
    <?php
    }

    public function printCustomRows($status)
    {
        $endDate = $status->getComplianceProgram()->getEndDate('m/d/Y');
        ?>
    <tr class="headerRow">
        <th>3. Deadlines, Requirements & Status</th>
        <td># Earned</td>
        <td>Status</td>
        <td>Minimum Needed for Monthly Raffle</td>
    </tr>
    <tr>
        <td style="text-align: right;"> Total & Status as of: <strong>Deadline: <?php echo $endDate ?></strong> =
        </td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center">1ABCD done + 40 points</td>
    </tr>
        <?php
    }
}