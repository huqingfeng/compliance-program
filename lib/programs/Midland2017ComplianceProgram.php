<?php
class Midland2017TobaccoFormComplianceView extends ComplianceView
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
class Midland2017ComplianceProgram extends ComplianceProgram
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
        $coreGroup = new ComplianceViewGroup('core', 'Core Actions to get done by 1/31/18 for your Core Reward and toward your Bonus Reward:');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('Results', '/compliance/midland-paper-2017/my-results'));


        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Complete the Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'And, earn at least 350 points by 1/31/18 through the opportunities below for your Bonus Reward:');

        $screeningTestMapper = new ComplianceStatusPointMapper(50, 25, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('elearning_lesson_id', '789');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(89.999, 99.999, 199.999, 240.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setAttribute('elearning_lesson_id', '595');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
//        $hdlCholesterolView->overrideTestRowData(0, 0, 4.499, 4.499);
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setAttribute('elearning_lesson_id', '595');
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(0, 0, 129.999, 158.999);
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setAttribute('elearning_lesson_id', '99');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 0, 149.999, 199.999);
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('elearning_lesson_id', '105');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(49.999, 69.999, 99.999, 125.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('elearning_lesson_id', '1309');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 119.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 79.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $numbers->addComplianceView($bloodPressureView);

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI) < 30');
        $BMIView->setAttribute('elearning_lesson_id', '1118');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideTestRowData(18.499, 0, 24.999, 29.999);
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($BMIView);

        $doc = new UpdateDoctorInformationComplianceView($programStart,$programEnd);
        $doc->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $doc->setReportName('Have a Primary Care Doctor');
        $numbers->addComplianceView($doc);
        // $numbers->addComplianceView($proviceDoctor);


        // $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        // $fluVaccineView->setReportName('Annual Flu Vaccine');
        // $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        // $fluVaccineView->setName('flu_vaccine');
        // $numbers->addComplianceView($fluVaccineView);
// here
// and here
        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $numbers->addComplianceView($fluVaccineView);

        $nonSmokerView = new Midland2017TobaccoFormComplianceView($programStart, $programEnd);
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($nonSmokerView);

        $ineligibleLessonIDs = array(789, 595, 99, 105, 1309, 1118);
        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, null, $ineligibleLessonIDs);
        $elearn->setReportName('Complete e-Learning Lessons - 25 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(150);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each hour of physical activity');
        $physicalActivityView->setMaximumNumberOfPoints(250);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $numbers->addComplianceView($physicalActivityView);

        $midlandFitChallengeView = new PlaceHolderComplianceView(null, 0);
        $midlandFitChallengeView->setName('midland_get_fit_challenge');
        $midlandFitChallengeView->setReportName('Participate in Midland Get Fit Challenge');
        $midlandFitChallengeView->addLink(new Link('More Info', '/content/get-fit-challenge'));
        $midlandFitChallengeView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($midlandFitChallengeView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering – 5 pts for each hour of volunteering');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($volunteeringView);

        $preventiveScreeningsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 452, 25);
        $preventiveScreeningsView->setReportName('Get an Annual Physical Exam');
        $preventiveScreeningsView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($preventiveScreeningsView);

        $midlandPaper = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 424, 25);
        $midlandPaper->setMaximumNumberOfPoints(25);
        $midlandPaper->setReportName('Attend a Midland Paper Sponsored Wellness Event');
        $numbers->addComplianceView($midlandPaper);

        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 342, 10);
        $blueCrossBlueShield->setMaximumNumberOfPoints(10);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield’s Blue Access for Members');
        $blueCrossBlueShield->addLink(new Link('BCBS', 'http://www.bcbsil.com/member'));
        $numbers->addComplianceView($blueCrossBlueShield);

        

        $numbers->setPointsRequiredForCompliance(350);

        $this->addComplianceViewGroup($numbers);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinterMidland' && $this->getActiveUser() !== null) {

            $printer = new $preferredPrinter;

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
            $printer = new Midland2017ComplianceProgramReportPrinter();
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


class Midland2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        $coreStatus = $status->getComplianceViewGroupStatus('core');

        ?>

        <script type="text/javascript">
            $(function() {
                $.ajax({
                    url: '/compliance/midland-paper-2017/my-rewards/compliance_programs?preferredPrinter=ScreeningProgramReportPrinterMidland&id=1227&type=json',
                    success: function(reply) {
                        var total_points = 0;
                        var scores = JSON.parse(reply);
                        var points = 0;
                        var point_elements = $('td.points');
                        for(var i = 0; i < (point_elements.length - 1); i++) {
                            if(i == 0 || i % 2 == 0) {
                                points += parseInt(point_elements[i].innerHTML);
                            }
                        }

                        point_elements[(point_elements.length - 1)].innerHTML = points;

                         var statusMap = {
                            '/compliance/midland-paper-2017/my-rewards/images/lights/greenlight.gif' : 'success',
                            '/compliance/midland-paper-2017/my-rewards/images/lights/yellowlight.gif' : 'partially_compliant',
                            '/compliance/midland-paper-2017/my-rewards/images/lights/redlight.gif' : 'not_compliant'
                        };

                        var screening_status = $('.view-complete_screening .status img').attr('src');
                        var hpa_status = $('.view-complete_hra .status img').attr('src');
                

                        var status = "Still some to go!"
                        var bonusStatus = status;
                        var compliance = '/compliance/midland-paper-2017/my-rewards/images/lights/redlight.gif';
                        var bonus = '/compliance/midland-paper-2017/my-rewards/images/lights/redlight.gif';

                        if(statusMap[screening_status] == 'success' && statusMap[hpa_status] == 'success') {
                            compliance = '/compliance/midland-paper-2017/my-rewards/images/lights/greenlight.gif';
                            status = "Congrats!";
                            if(points >= 350) {
                                bonus = compliance;
                                bonusStatus = status;
                            }
                        } else if((statusMap[screening_status] == 'success' || statusMap[hpa_status] == 'partially_compliant') || (statusMap[screening_status] == 'partially_compliant' || statusMap[hpa_status] == 'success')) {
                            compliance = '/compliance/midland-paper-2017/my-rewards/images/lights/yellowlight.gif';
                            status = "Still some to go!"
                        } else if(statusMap[screening_status] == 'not_compliant' && statusMap[hpa_status] == 'not_compliant') {
                            compliance = '/compliance/midland-paper-2017/my-rewards/images/lights/redlight.gif';
                            status = "Still some to go!"
                        }

                        
                        $('tr.headerRow').last().parent().append('<tr><td colspan="2" style="text-align: right;">Core Reward Status: <?php echo $coreStatus->getStatus() == ComplianceStatus::COMPLIANT ? sprintf('Core Actions done as of %s', date('m/d/Y')) : '' ?></td><td style="text-align: center;"><img src="' + compliance + '" class="light"></td><td>' +  status + '</td></tr><tr><td colspan="2" style="text-align: right;">Bonus Reward Status: Core actions done + &#8805; 350 points</td><td style="text-align: center;"><img src="' + bonus + '" class="light"></td><td>' +  bonusStatus + '</td></tr>');

                    }
                });

                var getPoints = function(scores) {
                    return parseInt(scores.points) < 50;
                }
                
                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.headerRow-points').after('<tr><td><strong>A. </strong>Biometric Results: Gain points by having biometrics in the healthier ranges –OR– complete related e-lessons to learn more and improve.</td><td></td><td></td><td style="text-align: center;"><a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinterMidland&id=1227">View Results & point scale</a></td></tr>');

                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">1. Total Cholesterol</span>');
                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').html('<a href="/search-learn/core-strengths/sitemaps/health_centers/15913">Blood Fat Tips & Lessons</a>');
                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').attr('rowspan', 4);

                $('.view-comply_with_hdl_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">2. HDL Cholesterol</span>');
                $('.view-comply_with_hdl_screening_test').children(':eq(3)').remove();
                
                $('.view-comply_with_total_ldl_cholesterol_ratio_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">3. LDL Cholesterol</span>');
                $('.view-comply_with_total_ldl_cholesterol_ratio_screening_test').children(':eq(3)').remove();;

                $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">4. Triglycerides</span>');
                $('.view-comply_with_triglycerides_screening_test').children(':eq(3)').remove();

                $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">5. Glucose</span>');
                $('.view-comply_with_glucose_screening_test').children(':eq(3)').html('<a href="/search-learn/core-strengths/sitemaps/health_centers/15401">Blood Sugar Tips & Lessons</a>');

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">6. Blood Pressure</span>');
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(3)').html('<a href="/search-learn/core-strengths/sitemaps/health_centers/15919">Blood Pressure Tips Lessons</a>');
                
                $('.view-comply_with_bmi_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">7. Body Mass Index (BMI)</span>');
                $('.view-comply_with_bmi_screening_test').children(':eq(3)').html('<a href="/search-learn/core-strengths/sitemaps/health_centers/15932">BMI Tips & Lessons</a>');


                $('td:contains(Status of All Criteria =)').html('<div style="width: 100%; text-align: left;">3. Status of All Criteria</div>');
                $('.view-update_doctor_information').children(':eq(0)').html('<strong>B.</strong> Document that you have a main doctor for primary care');
                $('.view-flu_vaccine').children(':eq(0)').html('<strong>C.</strong> Annual Flu Vaccine');
                $('.view-non_smoker_view').children(':eq(0)').html('<strong>D.</strong> Non-Smoker');
                $('.view-elearning').children(':eq(0)').html('<strong>E.</strong> Complete e-Learning Lessons - 25 pts for each lesson done');
                $('.view-activity_21').children(':eq(0)').html('<strong>F.</strong> Regular Physical Activity - 1 pt for each hour of physical activity');
                $('.view-midland_get_fit_challenge').children(':eq(0)').html('<strong>G.&nbsp;</strong> Participate in Midland Get Fit Challenge');
                $('.view-activity_24').children(':eq(0)').html('<strong>H.</strong> Regular Volunteering – 5 pts for each hour of volunteering');
                $('.view-activity_452').children(':eq(0)').html('<strong>I.</strong> Get an Annual Physical Exam');
                $('.view-activity_424').children(':eq(0)').html('<strong>J.</strong> Attend a Midland Paper Sponsored Wellness Event');
                $('.view-activity_342').children(':eq(0)').html('<strong>K.</strong> Register with Blue Cross Blue Shield’s Blue Access for Members');
                $('.white').html('Status').removeClass('white');
                // var points = <?php echo $status->getPoints(); ?>;


                
                <?php if(sfConfig::get('app_wms2')) : ?>
                $('.view-complete_hra').children(':eq(3)').html('<a href="/compliance/hmi-2016/my-health">Take HPA</a>');
                <?php else : ?>
                $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
                <?php endif ?>
            });
        </script>

        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2017 Midland Paper Wellness Rewards benefit.</p>

        <p><em>Do you have medical benefit coverage through Midland Paper?</em> If yes, you can earn rewards of $480 to $1,080 ($40-$90 per month) in savings toward your 2018 medical plan premium contribution.</p>

        <p>
            Here’s how:
            <ol>
                <li><strong>Core Reward</strong> – complete the Wellness Screening and Health Power Assessment by 1/31/18 and get:
                    <ol type="a">
                        <li>$480 ($40/month) if you have employee-only coverage; <strong>OR</strong></li>
                        <li>$600 ($50/month) if you have employee plus spouse/children/family coverage, and both you and your eligible spouse (if applicable) get both core actions done.</li>
                    </ol>
                </li>
                <li><atrong>And a Bonus Reward</atrong> – get the core actions (above) done <strong>PLUS</strong> earn at least 350 points by 1/31/18 and:
                    <ol type="a">
                        <li>Get an extra $300 ($25/month) if you have employee-only coverage; <strong>OR</strong></li>
                        <li>Get an extra $480 ($40/month) if you have employee plus spouse/children/family coverage, and both you and your eligible spouse (if applicable) get these things done.</li>
                    </ol>
                </li>
            </ol>
        </p>
        <p>
            More importantly, taking these actions can add to your health and wellbeing now and in the future.
        </p>

        <p>
            Login anytime to see your Wellness Rewards To-Do summary below. The action links make it easy to get things done and earn your rewards. <br />
            <a href="/content/1094_midlandpaper_2017">Click here</a> for more details about each action and point-earning option.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
    }
}