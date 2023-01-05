<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class LoyolaUniversity2017ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Quarter 1 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('Quarter 2 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('Quarter 3 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('Quarter 4 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new LoyolaUniversity2017ComplianceReportPrinter();
        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'A & B are required by November 30, 2016');

        $hraScreeningStartDate = '2016-01-01';
        $hraScreeningEndDate = '2016-12-12';

        $quarterlyDateRange = $this->getQuerterlyRanges();

        $screening = new CompleteScreeningComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $screening->setReportName('Annual Wellness Screening');
        $screening->setAttribute('report_name_link', '/content/1094#ascreen');
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $hra->setReportName('Complete the Health Power Assessment ');
        $hra->setAttribute('report_name_link', '/content/1094#bhpa');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($hra);

        $this->addComplianceViewGroup($required);

        $pointsGroup = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas C - I below by the Dec 18, 2017 deadline in order to earn the quarterly $75 reward.');

        $challengeView = new PlaceHolderComplianceView(null, 0);
        $challengeView->setMaximumNumberOfPoints(50);
        $challengeView->setReportName('Participate in a Wellness Challenge - 50 pts per challenge');
        $challengeView->setName('wellness_challenge');
        $challengeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 553, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $challengeView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=553'));
        $pointsGroup->addComplianceView($challengeView);

        $welnessView = new PlaceHolderComplianceView(null, 0);
        $welnessView->setMaximumNumberOfPoints(40);
        $welnessView->setReportName('Participate in a Wellness Event - 20 pts per event');
        $welnessView->setName('wellness_event');
        $welnessView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 556, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $welnessView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=556'));
        $pointsGroup->addComplianceView($welnessView);

        $communityView = new PlaceHolderComplianceView(null, 0);
        $communityView->setMaximumNumberOfPoints(20);
        $communityView->setReportName('Participate in a Community Event - 20 pts per event');
        $communityView->setName('community_event');
        $communityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 559, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $communityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=559'));
        $pointsGroup->addComplianceView($communityView);

        $preventiveExamView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamView->setMaximumNumberOfPoints(50);
        $preventiveExamView->setReportName('Receive a Preventive Exam - 10 pts per exam');
        $preventiveExamView->setName('preventive_exam');
        $preventiveExamView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new  CompletePreventiveExamWithRollingStartDateLogicComplianceView($startDate, $endDate, 10);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setRollingStartDate(false);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }

                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $preventiveExamView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=26'));
        $pointsGroup->addComplianceView($preventiveExamView);

        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete e-Learning Lessons - 10 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $pointsGroup->addComplianceView($elearn);

        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Healthy Activity - 5 pts per hour of exercise');
        $physicalActivityView->setName('healthy_activity');
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(12);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $pointsGroup->addComplianceView($physicalActivityView);

        $financialView = new PlaceHolderComplianceView(null, 0);
        $financialView->setMaximumNumberOfPoints(20);
        $financialView->setReportName('Participate in a Financial Wellness program through Emerge - 20 pts each');
        $financialView->setName('financial');
        $financialView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 562, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $financialView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=562'));
        $pointsGroup->addComplianceView($financialView);


        $pointsGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($pointsGroup);
    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2017-01-01', '2017-03-31'),
            'quarter2' => array('2017-04-01', '2017-06-30'),
            'quarter3' => array('2017-07-01', '2017-09-30'),
            'quarter4' => array('2017-10-01', '2017-12-18')
        );

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($pointStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('quarter1')) {
                $quarter1Points += $viewStatus->getAttribute('quarter1');
            }

            if($viewStatus->getAttribute('quarter2')) {
                $quarter2Points += $viewStatus->getAttribute('quarter2');
            }

            if($viewStatus->getAttribute('quarter3')) {
                $quarter3Points += $viewStatus->getAttribute('quarter3');
            }

            if($viewStatus->getAttribute('quarter4')) {
                $quarter4Points += $viewStatus->getAttribute('quarter4');
            }
        }

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
    }

}



class LoyolaUniversity2017ComplianceReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->pageHeading = '<img src="/images/hmii/loyola/loyola_university_logo.png" style="width:320px;" /><br/ >
                                <br/ >LOYOLA UNIVERSITY CHICAGO <br /><br />2017 WELLNESS REWARDS PROGRAM';
        $this->setScreeningResultsLink(new FakeLink('Complete eLearning Lessons', '#'));
    }

    public function printHeader(ComplianceProgramStatus $status)
    {


        ?>

        <script type="text/javascript">
            $(function(){
                $('#legend tr td').html('<div id="legendText">Legend</div>' +
                    '<div class="legendEntry"><img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/greenlight.gif" class="light" alt=""> = Criteria Met </div>' +
                    '<div class="legendEntry"> <img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/redlight.gif" class="light" alt=""> = Not Started </div>');

                $('.headerRow.headerRow-points').children(':eq(2)').html('Max Points Possible per Quarter');

                $('.view-healthy_activity').children(':eq(2)').html('Unlimited');

                $('.view-wellness_challenge').children(':eq(0)').html('<strong>C</strong>. Participate in a Wellness Challenge - 50 pts per challenge');
                $('.view-wellness_event').children(':eq(0)').html('<strong>D</strong>. Participate in a Wellness Event - 20 pts per event');
                $('.view-community_event').children(':eq(0)').html('<strong>E</strong>. Participate in a Community Event - 20 pts per event');
                $('.view-preventive_exam').children(':eq(0)').html('<strong>F</strong>. Receive a Preventive Exam - 10 pts per exam');
                $('.view-elearning').children(':eq(0)').html('<strong>G</strong>. Complete e-Learning Lessons - 10 pts for each lesson done');
                $('.view-healthy_activity').children(':eq(0)').html('<strong>H</strong>. Healthy Activity - 5 pts per hour of exercise');
                $('.view-financial').children(':eq(0)').html('<strong>I</strong>. Participate in a Financial Wellness program through Emerge - 20 pts each');



            });
        </script>

        <style type="text/css">
            .status img {
                width:25px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2017 Wellness Rewards benefit at Loyola University.</p>

        <p>This year, employees on Loyola’s health insurance have the opportunity to earn a premium discount.</p>

        <p>
            To receive the reduced premium You MUST register with HMI, complete the annual wellness screening and complete the
            Health Power Assessment by November 30, 2016 (Section 1 below).
        </p>

        <p>
            To receive the additional $75 quarterly wellness rewards, you MUST earn 100 points each quarter from actions
            taken for good health. Points must be recorded by the following dates (section 2 below).
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <p style="margin-top: 20px; margin-left: 200px;">
            <strong>Quarter 1:</strong> Earn at least 100 points by <strong>March 31, 2017</strong> <br/>
            <strong>Quarter 2:</strong> Earn at least 100 points by <strong>June 30, 2017</strong> <br/>
            <strong>Quarter 3:</strong> Earn at least 100 points by <strong>September 30, 2017</strong> <br/>
            <strong>Quarter 4:</strong> Earn at least 100 points by <strong>December 18, 2017</strong> <br />
        </p>
        <?php
    }
}