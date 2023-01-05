<?php
class VillageOfLibertyville2016TobaccoFormComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

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

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class VillageOfLibertyville2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-C are required by October 15, 2016 in order to earn the $50 gift card.');

        $registerHMIView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $registerHMIView->setReportName('Register with HMI');
        $registerHMIView->setName('register_hmi_site');
        $registerHMIView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd) {
            if($user->created_at >= '2016-01-01'
                && $user->created_at <= '2017-02-28') {
                $status->setStatus(ComplianceViewStatus::COMPLIANT);
            }
        });
        $coreGroup->addComplianceView($registerHMIView);
        
        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView('2016-08-01', $programEnd);
        $hraView->setReportName('Complete the Health Power Assessment');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain 250 points through actions taken in option areas D-L below by the Feb 28, 2017 deadline in order to earn the additional $50 gift card.');

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $numbers->addComplianceView($fluVaccineView);

        $nonSmokerView = new VillageOfLibertyville2016TobaccoFormComplianceView($programStart, $programEnd);
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($nonSmokerView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('Complete e-Learning Lessons - 10 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setPointsPerLesson(10);
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setMaximumNumberOfPoints(150);
        $physicalActivityView->setMinutesDivisorForPoints(30);
//        $physicalActivityView->setMonthlyPointLimit(30);
        $numbers->addComplianceView($physicalActivityView);

        $wellnessRun = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 548, 50);
        $wellnessRun->setMaximumNumberOfPoints(100);
        $wellnessRun->setReportName('Participate in a Wellness Run/Walk - 50 pts per activity *');
        $numbers->addComplianceView($wellnessRun);

        $preventiveExamsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 26, 10);
        $preventiveExamsView->setReportName('Receive a Preventive Exam - 10 points per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(30);
        $preventiveExamsView->setName('do_preventive_exams');
        $numbers->addComplianceView($preventiveExamsView);

        $wellnessEvent = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 424, 50);
        $wellnessEvent->setMaximumNumberOfPoints(150);
        $wellnessEvent->setReportName('Attend a Sponsored Wellness Event - 50 pts per activity ** ');
        $numbers->addComplianceView($wellnessEvent);

        $healthy = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 551, 1);
        $healthy->setMaximumNumberOfPoints(75);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day - 1 pt per day');
        $numbers->addComplianceView($healthy);

        $doc = new UpdateDoctorInformationComplianceView($programStart,$programEnd);
        $doc->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $doc->setReportName('Have a Primary Care Doctor');
        $numbers->addComplianceView($doc);

        $numbers->setPointsRequiredForCompliance(250);

        $this->addComplianceViewGroup($numbers);
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
            $printer = new VillageOfLibertyville2016ComplianceProgramReportPrinter();
            $printer->setShowTotal(false);
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }
}


class VillageOfLibertyville2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>

        <style type="text/css">

        </style>

        <script type="text/javascript">
            $(function() {
                $('#legend tr td').children(':eq(2)').remove();
                $('#legend tr td').children(':eq(2)').remove();

                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.view-flu_vaccine').children(':eq(0)').html('<strong>D.</strong> Annual Flu Vaccine');
                $('.view-non_smoker_view').children(':eq(0)').html('<strong>E.</strong> Non-Smoker');
                $('.view-elearning').children(':eq(0)').html('<strong>F.</strong> Complete e-Learning Lessons - 10 pts for each lesson done');
                $('.view-activity_21').children(':eq(0)').html('<strong>G.</strong> Regular Physical Activity - 1 pt for each 30 minutes of activity');
                $('.view-activity_548').children(':eq(0)').html('<strong>H.</strong> Participate in a Wellness Run/Walk - 50 pts per activity *');
                $('.view-do_preventive_exams').children(':eq(0)').html('<strong>I.</strong> Receive a Preventive Exam - 10 points per exam');
                $('.view-activity_424').children(':eq(0)').html('<strong>J.</strong> Attend a Sponsored Wellness Event - 50 pts per activity **');
                $('.view-healthy').children(':eq(0)').html('<strong>K.</strong> Drink 6-8 glasses of pure water per day - 1 pt per day');
                $('.view-update_doctor_information').children(':eq(0)').html('<strong>L.</strong> Have a Primary Care Doctor');



                $('.view-update_doctor_information').after('<tr class="headerRow headerRow-footer"><td class="center">Status of All Criteria = </td><td></td><td></td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr><td style="text-align: right;">By 02/28/2017</td><td style="text-align: center;"><?php echo $status->getPoints() ?></td><td class="status"><img src="<?php echo $status->getLight(); ?>" class="light" /></td><td style="text-align: center;">250</td></tr>')



            });
        </script>

        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2016-17 Wellness Rewards benefit at Village of Libertyville.</p>

        <p>You have the opportunity to earn two $50 gift cards.<br />
            The deadline to complete all actions is February 28, 2017.
        </p>

        <p>
            To receive the initial $50 gift card, you MUST register with HMI, complete the annual wellness screening
            and complete the Health Power Assessment by October 15, 2016 (Section 1 below).
        </p>


        <p>
            To receive the additional $50 gift card, you MUST earn 250 or more points from key actions taken for good health
            (Section 1 & 2 below).
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <div style="margin-top: 20px;">
            <div style="float: left; margin-right: 160px;">
                * Run/Walk Examples <br /><br />
                - Heart Walk <br />
                - Walk to Cure Diabetes <br />
                - Libertyville Twilight Shuffle <br />
                - Participate in a Wellness Walk/Run of your choice
            </div>

            <div style="float: left;">
                ** Sponsored Wellness Events <br /><br />
                - Wellness & Benefit Fair (10/13/2016) <br />
                - Lunch & Learn (Fall 2016) <br />
                - Lunch & Learn (Winter 2017)
            </div>
        </div>

        <div style="clear: both"></div>

        <?php

    }
}