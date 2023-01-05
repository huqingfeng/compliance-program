<?php

class Ochnser2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Ochnser2014Printer();
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMapping(
            ComplianceStatus::NA_COMPLIANT, new ComplianceStatusMapping('N/A *', '/images/lights/whitelight.gif')
        );

        $this->setComplianceStatusMapper($mapping);

        $startDate = $this->getStartDate();

        $endDate = $this->getEndDate();

        $start = new ComplianceViewGroup('start', 'All core actions required by August 15, 2014');

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Complete Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094#1aHS');
        $scr->emptyLinks();
        $scr->addLink(new Link('Results','/content/989'));
        $start->addComplianceView($scr);

        $hpa = new CompleteHRAComplianceView($startDate, $endDate);
        $hpa->setReportName('Complete Health Power Assessment');
        $hpa->setAttribute('report_name_link', '/content/1094#1bHPA');
        $start->addComplianceView($hpa);

        $doc = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doc->setReportName('Verify having a main doctor/primary care provider');
        $doc->setAttribute('report_name_link', '/content/1094#2cMD');
        $start->addComplianceView($doc);

        $updateInfo = new UpdateContactInformationComplianceView($startDate, $endDate);
        $updateInfo->setReportName('Verify/Update my current contact information');
        $updateInfo->setAttribute('report_name_link', '/content/1094#1dPCI');
        $start->addComplianceView($updateInfo);

        //$healthCoach = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        //$healthCoach->setName('coach');
        //$healthCoach->setReportName('Return Call(s) of Health Coach - if applicable');
        //$healthCoach->setAttribute('report_name_link', '/content/1094#1eCalls');
        //$start->addComplianceView($healthCoach);

        $elearn = new CompleteCoreELearningLessonsComplianceView($startDate, $endDate);
        $elearn->setReportName('Complete all core e-learning lessons');
        $elearn->setAttribute('report_name_link', '/content/1094#1freqeL');
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=core_lessons'));
        $start->addComplianceView($elearn);



        $this->addComplianceViewGroup($start);

        $scrMapper = new ComplianceStatusPointMapper(10, 0, 0, 0);

//        $key = new ComplianceViewGroup('key', 'See how many points you can earn! by '.$this->getEndDate('m/d/Y'));
        $key = new ComplianceViewGroup('key', 'See how many points you can earn! ');
        $key->setPointsRequiredForCompliance(200);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setUseDateForComment(true);
        $totalCholesterolView->setComplianceStatusPointMapper($scrMapper);
        $key->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdlCholesterolView->setUseDateForComment(true);
        $hdlCholesterolView->setComplianceStatusPointMapper($scrMapper);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $key->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlCholesterolView->setUseDateForComment(true);
        $ldlCholesterolView->setComplianceStatusPointMapper($scrMapper);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $key->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $trigView->setUseDateForComment(true);
        $trigView->setComplianceStatusPointMapper($scrMapper);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $key->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setUseDateForComment(true);
        $glucoseView->setComplianceStatusPointMapper($scrMapper);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $key->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setUseDateForComment(true);
        $bloodPressureView->setComplianceStatusPointMapper($scrMapper);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $key->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $endDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setUseDateForComment(true);
        $bodyFatBMIView->setComplianceStatusPointMapper($scrMapper);
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $key->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($startDate, $endDate);
        $nonSmokerView->setUseDateForComment(true);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setComplianceStatusPointMapper($scrMapper);
        $nonSmokerView->setReportName('Non-Smoker/Non-User of Tobacco');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');
        $key->addComplianceView($nonSmokerView);


        $prev = new CompletePreventiveExamWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $prev->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $prev->setMaximumNumberOfPoints(20);
        $prev->setReportName('Get Recommended Preventive Screenings/Exams');
        $prev->setAttribute('report_name_link', '/content/1094#4eprevScreen');
        $key->addComplianceView($prev);

        $imm = new CompleteImmunizationsWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $imm->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $imm->setMaximumNumberOfPoints(20);
        $imm->setReportName('Get Recommended Immunizations');
        $imm->setAttribute('report_name_link', '/content/1094#2cImm');
        $key->addComplianceView($imm);

        $phy = new PhysicalActivityComplianceView($startDate, $endDate);
        $phy->_setID(241);
        $phy->setReportName('Get Regular Physical Activity');
        $phy->setAttribute('report_name_link', '/content/1094#2dRPA');
        $phy->setMaximumNumberOfPoints(220);
        $phy->setFractionalDivisorForPoints(1);
        $phy->setMinutesDivisorForPoints(60);
        $phy->setCompliancePointStatusMapper(new CompliancePointStatusMapper(60 * 100, 1, 0, 0));
        $key->addComplianceView($phy);

        $doc = new PlaceHolderComplianceView(null, 0);
        $doc->setCompliancePointStatusMapper(new CompliancePointStatusMapper(30, 15, 0, 0));
        $doc->setReportName('Work with a health coach, financial coach or doctor on Wellbeing goals.');
        $doc->setAttribute('report_name_link', '/content/1094#2eHC');
        $doc->setName('doc');
        $doc->setMaximumNumberOfPoints(100);
        $doc->addLink(new Link('Click For Form', '/resources/3774/AFSCME31 DCM note from doctor 022112.pdf'));
        $key->addComplianceView($doc);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($startDate, $endDate, 5);
        $attendWorkshopView->setReportName('Health/Wellbeing Programs/Events Participated In');
        $attendWorkshopView->setAttribute('report_name_link', '/content/1094#2fProg');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(30);
        $key->addComplianceView($attendWorkshopView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Volunteer Time to Help Others – outside of work');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $volunteeringView->setAttribute('report_name_link', '/content/1094#2gVol');
        $key->addComplianceView($volunteeringView);

        $smart = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 336, 30);
        $smart->setReportName('Present MAD/SMART/TED talks @ Ochsner');
        $smart->setAttribute('report_name_link', '/content/1094#2hTalks');
        $smart->setMaximumNumberOfPoints(30);
        $key->addComplianceView($smart);

        $my25 = new PlaceHolderComplianceView(null, 0);
        $my25->setName('my25');
        $my25->setMaximumNumberOfPoints(30);
        $my25->setReportName('Use My25 at least 6 times – really use it !');
        $my25->addLink(new Link('Take Me to My25', 'http://www.my25.com/'));
        $my25->setAttribute('report_name_link', '/content/1094#2iMy25');
        $key->addComplianceView($my25);

        $lessons = new CompleteELearningGroupSet($startDate, $endDate, 'fwc_financial');
        $lessons->useAlternateCode();
        $lessons->setReportName('Complete mini-lessons on Financial Wellness');
        $lessons->emptyLinks();
        $lessons->addLink(new Link('Complete Lesons', '/content/9420?action=lessonManager&tab_alias=fwc_financial'));
        $lessons->setAttribute('report_name_link', '/content/1094#2jFW');
        $lessons->setMaximumNumberOfPoints(30);
        $lessons->setPointsPerLesson(10);
        $key->addComplianceView($lessons);

        $additional = new CompleteAdditionalELearningLessonsComplianceView($startDate, $endDate, 5);
        $additional->addIgnorableGroup('core_lessons');
        $additional->addIgnorableGroup('fwc_financial');
        $additional->setMaximumNumberOfPoints(30);
        $additional->setReportName('Complete Extra e-Learning Lessons');
        $additional->setAttribute('report_name_link', '/content/1094#2keLearn');
        $key->addComplianceView($additional);

        $services = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 335, 1);
        $services->setCompliancePointStatusMapper(new CompliancePointStatusMapper(1, 1));
        $services->setReportName('Share Ideas to Improve TTW, Feedback, or Ways it has Helped');
        $services->setAttribute('report_name_link', '/content/1094#1gideas');
        $key->addComplianceView($services);

        $this->addComplianceViewGroup($key);
    }
}

class Ochnser2014Printer extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
        <p style="font-size:smaller;">* *  The September drawing will include
            gift certificates and cash prizes. To qualify, eligible individuals
            must meet all deadlines and have 300 or more points. </p>
        <?php
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have these screening results in the ideal zone:', '/content/1094#3abio'));

        $this->pageHeading = '2014 Rewards / To-Dos Action Center';

        $this->showName = true;
        $this->setShowTotal(false);
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <script type="text/javascript">
            $(function() {
                // Rework Action Links to line up eLearning links with appropriate views

            });
        </script>
        <style type="text/css">
            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .headerRow, #legendText {
                background-color:#002AAE;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .status img {
                width:25px;
            }
        </style>
        <p>Welcome to your Trails to Wellbeing (TTW) Rewards/To-Dos Action Center</p>

        <p>If you complete ALL core actions listed in the table below, by August 15, 2014:</p>
        <ol>
            <li>You will receive $500 either as cash or added to your MERP account (if applicable);</li>
            <li>And if you also earn 300 or more points by September 15, 2014, you will be entered into a drawing for additional rewards that month.</li>
        </ol>

        <p>Most importantly, your efforts can:</p>
        <ul>
            <li>Help you benefit from improvements in your health, health care and wellbeing – now and throughout life;</li>
            <li>Help you avoid fewer health problems and related expenses each year;  and</li>
            <li>Help other people in many of these same ways through your example, encouragement and support along the way.</li>
        </ul>
        <p>Here are some tips about the table below and using it:</p>
        <ul>
            <li>In the first column, click on the text in blue to learn why the action is important.
            </li>
            <li>Use the Action Links in the right column to get things done or more information.
            </li>
            <li>Click here for more details about the requirements and benefits of each.
            </li>
        </ul>
    <?php
    }

    protected function printCustomRows($status)
    {
        ?>
        <tr class="headerRow headerRow-key">
            <th><strong>3</strong>. Totals</th>
            <td>Stats</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td style="text-align: right">My points earned as of: <?php echo date('m/d/Y') ?></td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td colspan="2">Points - out of 630 possible</td>
        </tr>
        <?php
    }
}