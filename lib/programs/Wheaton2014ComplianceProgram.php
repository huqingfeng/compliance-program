<?php

class Wheaton2014TobaccoFormComplianceView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'non_smoker_view';
    }

    public function getDefaultReportName()
    {
        return 'Non Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

        if($record->exists() && $record->agree) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class Wheaton2014ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroupEndDate = strtotime('2014-04-30');
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $coreGroupEndDate));

        $screeningView = new CompleteScreeningComplianceView($programStart, $coreGroupEndDate);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreGroupEndDate);
        $hraView->setReportName('Complete Health Power Assessment');
        $hraView->setAttribute('report_name_link', '/content/1094#2bdoc');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $extraGroup = new ComplianceViewGroup('points', 'Additional Actions: You must earn <em>at least</em> 100 points from the below actions by '.date('F d, Y', $programEnd));
        $extraGroup->setPointsRequiredForCompliance(100);

        $screeningTestMapper = new ComplianceStatusPointMapper(20, 10, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $totalCholesterolView->overrideTestRowData(null, null, 199, 239);

        $extraGroup->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $hdlCholesterolView->overrideTestRowData(null, 41, null, null);
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $ldlCholesterolView->overrideTestRowData(null, null, 99, 129);
        $extraGroup->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $trigView->overrideTestRowData(null, null, 149, 199);
        $extraGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $glucoseView->overrideTestRowData(null, null, 99, 125);
        $extraGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 119, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 79, 89);
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $extraGroup->addComplianceView($bloodPressureView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setComplianceStatusPointMapper($screeningTestMapper);
        $bmiView->setAttribute('report_name_link', '/content/1094#2abiometric');
        $bmiView->setReportName('BMI');
        $bmiView->overrideTestRowData(null, null, 24.999, 29.999);
        $extraGroup->addComplianceView($bmiView);

        $nonSmokerView = new Wheaton2014TobaccoFormComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Be a Non Smoker');
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2atobacco');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($nonSmokerView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main Doctor/Primary Care Provider');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#2cphysact');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $extraGroup->addComplianceView($doctorInformationView);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094');
        $physicalActivityView->setMaximumNumberOfPoints(30);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $extraGroup->addComplianceView($physicalActivityView);

        $elearningView = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, null, null, 10);
        $elearningView->setReportName('Complete e-Learning Lessons');
        $elearningView->setAttribute('report_name_link', '/content/1094#2delearn');
        $elearningView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($elearningView);

        $haveConsultation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $haveConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $haveConsultation->setName('have_consultation');
        $haveConsultation->setReportName('Have an individual consultation following a wellness screening');
        $haveConsultation->addLink(new Link('Sign Up', '/content/1051'));
        $haveConsultation->setAttribute('report_name_link', '/content/1094#2econsult');
        $extraGroup->addComplianceView($haveConsultation);

        $attendSeminar = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $attendSeminar->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $attendSeminar->setName('attend_seminar');
        $attendSeminar->setReportName('Attend an onsite Seminar provided by a Registered Dietitian/Nutritionist');
        $attendSeminar->addLink(new Link('Sign Up', '/content/1051'));
        $attendSeminar->setAttribute('report_name_link', '/content/1094#1felearn');
        $extraGroup->addComplianceView($attendSeminar);

        $extraGroup->setPointsRequiredForCompliance(100);

        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();

            $query = ScreeningTable::getInstance()
                ->getScreeningsForUser($this->getActiveUser(), array('execute' => false));
            $screenings = ScreeningTable::getInstance()
                ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
            $screening = $screenings->getFirst();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new Wheaton2014ComplianceProgramReportPrinter();
        }

        return $printer;
    }

}


class Wheaton2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status)
    {
        $this->pageHeading = '2014 Incentive Program Summary Page';
        $this->screeningLinkArea = '
            <br/>
            <br/>
            <br/>
            <a href="/content/83525">Tobacco Affidavit</a>
        ';

        parent::printReport($status);
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            .phipTable {
                font-size:0.9em;
            }

            .phipTable .headerRow, #legendText {
                background-color:#1D3779;
            }
        </style>

        <p>Welcome to the 2014 <strong>Get Well with Wheaton</strong> Incentive Program
            Summary page. The City of Wheaton has partnered with HMI to provide this
            program that focuses on "healthy measures" you will need to know in order
            to understand your risks for heart disease, stroke, diabetes, cancer and
            other diseases, <em>so that you can improve your health</em> and well-being
            and live a healthier life. This Wellness program rewards employees/spouses
            for controlling specific "healthy measures" which are indicative of good
            health and for participating in opportunities to improve health-related
            knowledge.</p>

        <p>Beginning July 1, 2014, non-union eligible employees and spouses covered by
            the City of Wheaton Health Insurance Plan have an opportunity to receive
            a discount on health insurance premiums.</p>

        <p><strong>To achieve the discount</strong>, two Core Actions (Section 1 below)
            must be completed by April 30, 2014 and 100 points from Additional Actions
            (Section 2 below) must be earned by June 15, 2014.</p>

        <p><strong>If you do not complete the required Core Actions plus earn 100 points
            from the Additional Actions Section by the required deadlines, you will not
            receive the health insurance premium discount.</strong></p>

        <p>Here are some tips about the table below and using it:</p>

        <ul>
            <li>In the first column, click on the text in blue to learn why the action is important.</li>
            <li>Use the Action Links in the right column to get things done or view more information.</li>
        </ul>

        <p style="text-align:center">
            <a href="/content/1094">Click here for more details about requirements and rewards</a>
        </p>
        <?php
    }
}
