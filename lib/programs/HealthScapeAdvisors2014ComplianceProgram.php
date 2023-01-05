<?php

class HealthScapeAdvisors2014ComplianceProgram extends ComplianceProgram
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

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Assessment');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraView->setAttribute('points_per_activity', '5 points');
        $hraView->setAttribute('requirement', 'Fill out HPA');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do Assessment', '/content/989'));
        $wellnessGroup->addComplianceView($hraView);
        
        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('2014 Wellness Screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $screeningView->setAttribute('points_per_activity', '75 points');
        $screeningView->setAttribute('requirement', '2014 Wellness Screening');
        $wellnessGroup->addComplianceView($screeningView);
        
        $wellnessKickOff = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $wellnessKickOff->setReportName('Wellness Kick-Off');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the Wellness Kick-Off meeting. Time and date will be announced via email.');
        $wellnessKickOff->setAttribute('points_per_activity', '10 points');
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign-In Sheet at event and admin will enter', '#'));
        $wellnessGroup->addComplianceView($wellnessKickOff);

        $physicianInfo = new PlaceHolderComplianceView(null, 0);
        $physicianInfo->setName('physician_info');
        $physicianInfo->setReportName('Provide Physician Information at Wellness Screening ');
        $physicianInfo->setAttribute('requirement', 'A copy of your lab results will be forwarded to your designated physician following the event to facilitate continuum of care.');
        $physicianInfo->setMaximumNumberOfPoints(5);
        $physicianInfo->setAttribute('points_per_activity', 5);
        $physicianInfo->addLink(new FakeLink('Provide Physician information at time of screening', '#'));
        $wellnessGroup->addComplianceView($physicianInfo);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('eLearning');
        $elearn->setAttribute('requirement', 'Complete an online eLearning course');
        $elearn->setAttribute('points_per_activity', '3 points');
        $elearn->setPointsPerLesson(3);
        $elearn->setMaximumNumberOfPoints(300);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $wellnessGroup->addComplianceView($elearn);

        $smokingView = new PlaceHolderComplianceView(null, 0);
        $smokingView->setName('smoking_view');
        $smokingView->setReportName('Smoking Cessation');
        $smokingView->setAttribute('requirement', 'Complete a Smoking Cessation course offered by IL Tobacco Quitline. <a href="http://www.quityes.org">www.Quityes.org</a>');
        $smokingView->setAttribute('points_per_activity', '25 points');
        $smokingView->setMaximumNumberOfPoints(25);
        $smokingView->addLink(new Link('Verification Form', '/resources/5016/Quitline Process Instructions HSAd.pdf'));
        $smokingView->addLink(new FakeLink('Submit to Axion RMS for approval', '#'));
        $wellnessGroup->addComplianceView($smokingView);

        $smokingView = new PlaceHolderComplianceView(null, 0);
        $smokingView->setName('mytime_view');
        $smokingView->setReportName('OnMyTime Courses');
        $smokingView->setAttribute('requirement', 'Complete BCBS Online Program via Well On Target');
        $smokingView->setAttribute('points_per_activity', '15 points');
        $smokingView->setMaximumNumberOfPoints(15);
        $smokingView->addLink(new Link('Verification Form', '/resources/5017/BCBS OnMyTime Nutrition HSAd.pdf'));
        $smokingView->addLink(new FakeLink('Submit to Axion RMS for approval', '#'));
        $wellnessGroup->addComplianceView($smokingView);

        $weightView = new PlaceHolderComplianceView(null, 0);
        $weightView->setName('weight_view');
        $weightView->setReportName('Individual Weight Loss Program');
        $weightView->setAttribute('requirement', 'Participate in a supervised weight loss program such as Weight Watchers for a minimum of 8 weeks');
        $weightView->setAttribute('points_per_activity', '15 points');
        $weightView->setMaximumNumberOfPoints(15);
        $weightView->addLink(new Link('Verification Form', '/resources/5018/WellnessRelated-Event-Cert-HSAd.pdf'));
        $weightView->addLink(new FakeLink('Submit to Axion RMS for approval', '#'));
        $wellnessGroup->addComplianceView($weightView);
        
        $regularFitnessTrainingView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $regularFitnessTrainingView->setReportName('Regular Fitness Training');
        $regularFitnessTrainingView->setMaximumNumberOfPoints(180);
        $regularFitnessTrainingView->setMinutesDivisorForPoints(10);
        $regularFitnessTrainingView->setMonthlyPointLimit(60);
        $regularFitnessTrainingView->setPointsMultiplier(1);
        $regularFitnessTrainingView->setFractionalDivisorForPoints(1);
        $regularFitnessTrainingView->setAttribute('requirement', 'Log minutes of activity on HMI Website – 15 points for 150 minutes of activity per week for 4 consecutive weeks.');
        $regularFitnessTrainingView->setAttribute('points_per_activity', '15 points per week for achieving average of 150 minutes per week.  ');
        $wellnessGroup->addComplianceView($regularFitnessTrainingView);

        $wellView = new PlaceHolderComplianceView(null, 0);
        $wellView->setName('wellness_view');
        $wellView->setReportName('Participate in a Wellness-Related Activity');
        $wellView->setAttribute('requirement', 'Participate in a wellness-related activity to receive credit.  Proof of participation required. Approved Activities include: preventative care (i.e. flu shot, dental exam, mammogram), a healthy lunch excursion, Wellness Lunch & Learn');
        $wellView->setAttribute('points_per_activity', '5 points');
        $wellView->setMaximumNumberOfPoints(60);
        $wellView->addLink(new Link('Verification Form', '/resources/5015/WellnessRelated Event Cert HSAd.pdf'));
        $wellView->addLink(new FakeLink('For onsite events, sign in at event and admin will enter', '#'));
        $wellnessGroup->addComplianceView($wellView);

        $fiveKView = new PlaceHolderComplianceView(null, 0);
        $fiveKView->setName('5k_view');
        $fiveKView->setReportName('Participate in a 5K');
        $fiveKView->setAttribute('requirement', 'Proof of participation in race is required (registration #, race result or bib number)');
        $fiveKView->setAttribute('points_per_activity', '10 points');
        $fiveKView->setMaximumNumberOfPoints(40);
        $fiveKView->addLink(new Link('Verification Form', '/resources/5018/WellnessRelated-Event-Cert-HSAd.pdf'));
        $fiveKView->addLink(new FakeLink('Submit to Axion RMS for approval', '#'));
        $wellnessGroup->addComplianceView($fiveKView);

        $tenKView = new PlaceHolderComplianceView(null, 0);
        $tenKView->setName('10k_view');
        $tenKView->setReportName('Participate in a 10K');
        $tenKView->setAttribute('requirement', 'Proof of participation in race is required (registration #, race result or bib number)');
        $tenKView->setAttribute('points_per_activity', '15 points');
        $tenKView->setMaximumNumberOfPoints(60);
        $tenKView->addLink(new Link('Verification Form', '/resources/5018/WellnessRelated-Event-Cert-HSAd.pdf'));
        $tenKView->addLink(new FakeLink('Submit to Axion RMS for approval', '#'));
        $wellnessGroup->addComplianceView($tenKView);

        $this->addComplianceViewGroup($wellnessGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new HealthScapeAdvisors2014ComplianceProgramReportPrinter();

        return $printer;
    }
}


class HealthScapeAdvisors2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->tableHeaders['points_possible'] = 'Maximum Possible Points';
        
        $this->addStatusCallbackColumn('Requirement', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            
            return $view->getAttribute('requirement');
        });
        
        $this->addStatusCallbackColumn('Points Per Activity', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('points_per_activity');
        });
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        ?>
        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
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

            #programTable {
                border-collapse: collapse;
                margin:0 auto;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #0063dc;
            }

            .bold-center {
                font-weight: bolder;
                font-size: 12pt;
                text-align: center
            }
        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned

            $(function() {
               $('.phipTable .headerRow.headerRow-wellness_programs').before(
                   "<tr><th colspan='6' style='text-align:center; color: white; background-color:#436EEE;'>HealthScape Advisors WELLNESS PROGRAM<br/>Incentive period ends June 30, 2015<br/>Note:  A&B are required for participation in program</th></tr>"
               );

               $('.phipTable tr td.points').each(function() {
                   $(this).html($(this).html() + ' points');
               });

               $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(1)').html('Requirement');
               $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(2)').html('Points Per Activity');

               $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(1)').html('Requirement');
               $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(2)').html('Points Per Activity');

               $('.view-participate_in_walking_challenge td.links').attr('rowspan', 2);
               $('.view-challenge_winner td.links').remove();

                $('.view-step_one td.links').attr('rowspan', 4);
                $('.view-step_two td.links').remove();
                $('.view-step_three td.links').remove();
                $('.view-beat_cfo td.links').remove();
            });
        </script>

        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">
            The HealthScape Advisors Wellness Incentive Program
        </p>

        <br/>

        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>
        <p>HealthScape Advisors cares about your health!  We have partnered with
            HMI and Axion RMS to implement our Wellness Incentive Program.
            This Wellness Program is your way to better, healthier living.</p>

        <p class="bold-center">HOW DOES THE PROGRAM WORK?</p>

        <p class="bold-center">To participate in the program you need to complete the Health Power
            Assessment and the Wellness Screening and earn 100 points in the first
            quarter to enter raffle.  All other quarters you will need 60 points.</p>

        <p class="bold-center">The Raffle Prizes are three $100 Visa Gift Cards per quarter.
            ($1200 per year) Program requirements and available points are listed
            in the table below. </p>
        <?php
    }

    public $showUserNameInLegend = true;
}
