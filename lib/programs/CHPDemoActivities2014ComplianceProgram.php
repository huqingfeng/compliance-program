<?php

class CHPDemoActivities2014ComplianceProgram extends ComplianceProgram
{
    protected function getActivityView($name, $activityId, $points, $reportName = null)
    {
        $view = new CompleteArbitraryActivityComplianceView(
            $this->getStartDate(),
            $this->getEndDate(),
            $activityId,
            $points
        );

        $view->setMaximumNumberOfPoints($points);

        $view->setName($name);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        return $view;
    }

    protected function getPlaceHolderView($name, $points, $reportName = null)
    {
        $view = new PlaceHolderComplianceView(null, 0);
        $view->setName($name);
        $view->setMaximumNumberOfPoints($points);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        return $view;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new CHPDemoActivities2014ComplianceProgramPrinter();
        $printer->hide_status_when_point_based = true;
        $printer->requirements = false;
        $printer->page_heading = 'My Activities (<a href="/compliance_programs?id=335">View Report Card</a>)';
        $printer->show_group_totals = true;

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $prevention = new ComplianceViewGroup('Preventative Screening Tests- Prevention');
        $prevention->setPointsRequiredForCompliance(0);
        $prevention->setMaximumNumberOfPoints(50);
        $prevention->setAttribute('available_points', 60);

        $confirmationView = new PlaceHolderComplianceView(null, 0);
        $confirmationView->setName('screening_confirmation');
        $confirmationView->setReportName('Confirmation of physician reviewed Wellness Screening');
        $confirmationView->setMaximumNumberOfPoints(10);
        $confirmationView->setPostEvaluateCallback(function($status, $user) use($startDate, $endDate) {
           if(!$user->insurancetype) {
               $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 373, 10);

               if($alternative->getStatus($user)->getPoints() > 0) {
                   $status->setPoints(10);
                   $status->setStatus(ComplianceStatus::COMPLIANT);
               }
           }
        });
        $prevention->addComplianceView($confirmationView);

        $examsView = new PlaceHolderComplianceView(null, 0);
        $examsView->setMaximumNumberOfPoints(30);
        $examsView->setName('exams');
        $examsView->setReportName(
            'Complete age-appropriate tests/exams <br/>
             <div style="padding-left:30px;">
                Complete a minimum of 3 of the following: <br/>
                <div style="padding-left:15px;">
                    Pelvic exam/Pap (annually) <br/>
                    Prostate exam (annually age 50+) <br/>
                    PSA test (annually age 50+) <br/>
                    Mammogram (annually age 40+) <br/>
                    Colonoscopy (every 10 years age 50+) <br/>
                    Physical Exam (annually) <br/>
                    Dental Exam (annually)
                </div>
            </div>
            '
        );
        $examsView->addLink(new Link('I did this', '/content/chp-document-uploader'));
        $prevention->addComplianceView($examsView);

        $fluTetView = new ComplyWithMultipleHraQuestionsComplianceView($startDate, $endDate, array(30, 31));
        $fluTetView->setReportName('Tetanus &amp; Flu Vaccinations');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $prevention->addComplianceView($fluTetView);
        $prevention->addComplianceView($this->getPlaceHolderView('prevention_lnl', 5, 'Attend a Lunch & Learn'));
        $prevention->addComplianceView($this->getPlaceHolderView('rewards_counselor', 10, 'Meet with a Total Rewards Counselor'));

        $community = new ComplianceViewGroup('Community');
        $community->setPointsRequiredForCompliance(0);
        $community->addComplianceView($this->getActivityView('donate_blood', 346, 10));
        $community->addComplianceView($this->getActivityView('mentor', 347, 10));
        $community->addComplianceView($this->getActivityView('donate_non_profit', 348, 10));
        $community->addComplianceView($this->getActivityView('church', 349, 20));
        $community->addComplianceView($this->getPlaceHolderView('community_lnl', 5, 'Attend a Lunch & Learn'));
        $community->setMaximumNumberOfPoints(50);
        $community->setAttribute('available_points', 55);

        $brain = new ComplianceViewGroup('Brain Health- Mind');
        $brain->setPointsRequiredForCompliance(0);
        $brain->addComplianceView($this->getActivityView('crossword', 351, 10));
        $brain->addComplianceView($this->getActivityView('puzzle', 352, 10));
        $brain->addComplianceView($this->getActivityView('language', 353, 15));
        $brain->addComplianceView($this->getActivityView('instrument', 354, 15));
        $brain->addComplianceView($this->getActivityView('cognitive_program', 355, 10));
        $brain->addComplianceView($this->getActivityView('education_class', 356, 5));
        $brain->addComplianceView($this->getActivityView('meditation', 357, 10));
        $brain->addComplianceView($this->getPlaceHolderView('brain_lnl', 5, 'Attend a Lunch & Learn'));
        $brain->setMaximumNumberOfPoints(50);
        $brain->setAttribute('available_points', 80);

        $financial = new ComplianceViewGroup('Financial Fitness- Financial');
        $financial->setPointsRequiredForCompliance(0);
        $financial->setAttribute('available_points', 70);

        $fairView = $this->getActivityView('retirement_fair', 359, 5);
        $fairView->setReportName('Attend Retirement Fair or Retirement Education Workshop');
        $fairViewLinks = $fairView->getLinks();
        $fairViewLink = reset($fairViewLinks);
        $fairViewLink->setLinkText('Update Fair');

        $financial->addComplianceView($fairView);
        $financial->addComplianceView($this->getPlaceHolderView('retirement_rep', 5, 'Meet with the Transamerica Retirement Representative'));
        $financial->addComplianceView($this->getPlaceHolderView('plan_contribute', 15, 'Contribute 10% to your Retirement Savings Plan'));
        $financial->addComplianceView($this->getPlaceHolderView('plan_beneficiary', 5, 'Beneficiary on file for Life Insurance and Retirement Plans'));
        $financial->addComplianceView($this->getActivityView('budget', 363, 15));
        $financial->addComplianceView($this->getActivityView('pay_loan', 364, 5));
        $financial->addComplianceView($this->getActivityView('emergency_fund', 365, 15));
        $financial->addComplianceView($this->getPlaceHolderView('financial_lnl', 5, 'Attend a Lunch & Learn'));
        $financial->setMaximumNumberOfPoints(50);

        $fitness = new ComplianceViewGroup('Physical Fitness and Exercise- Exercise');
        $fitness->setPointsRequiredForCompliance(0);
        $fitness->addComplianceView($this->getPlaceHolderView('cardio', 30, 'Cardio Exercise (Daily)'));
        $fitness->addComplianceView($this->getPlaceHolderView('strength', 30, 'Strength Training (Daily)'));
        $fitness->addComplianceView($this->getPlaceHolderView('fitness_lnl', 5, 'Attend a Lunch & Learn'));
        $fitness->setMaximumNumberOfPoints(50);
        $fitness->setAttribute('available_points', 65);

        $nutrition = new ComplianceViewGroup('Nutrition');
        $nutrition->setPointsRequiredForCompliance(0);
        $nutrition->addComplianceView($this->getActivityView('nutritionist', 368, 5));
        $nutrition->addComplianceView($this->getPlaceHolderView('water', 30, 'Drink Enough Water (Daily)'));
        $nutrition->addComplianceView($this->getPlaceHolderView('fruit', 30, 'Eat Enough Fruit & Vegetables (Daily)'));

        $learningView = new CompleteELearningGroupSet($startDate, $endDate, 'light_activities');
        $learningView->setReportName('E-learning lessons (complete one from the link to the right)');
        $learningView->setNumberRequired(1);
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

        $nutrition->addComplianceView($learningView);
        $nutrition->addComplianceView($this->getPlaceHolderView('nutrition_lnl', 5, 'Attend a Lunch & Learn'));
        $nutrition->setMaximumNumberOfPoints(50);
        $nutrition->setAttribute('available_points', 75);

        $stress = new ComplianceViewGroup('Stress Control- De-stress');
        $stress->setPointsRequiredForCompliance(0);
        $stress->addComplianceView($this->getActivityView('one_vacation', 370, 5));
        $stress->addComplianceView($this->getActivityView('two_vacation', 371, 10));
        $stress->addComplianceView($this->getPlaceHolderView('relax', 30, 'Relax / Take Time for Yourself (Daily)'));
        $stress->addComplianceView($this->getPlaceHolderView('sleep', 30, 'Sleep (Daily)'));
        $stress->addComplianceView($this->getPlaceHolderView('stress_lnl', 5, 'Attend a Lunch & Learn'));
        $stress->setMaximumNumberOfPoints(50);
        $stress->setAttribute('available_points', 80);

        $this->addComplianceViewGroup($prevention);
        $this->addComplianceViewGroup($community);
        $this->addComplianceViewGroup($brain);
        $this->addComplianceViewGroup($financial);
        $this->addComplianceViewGroup($fitness);
        $this->addComplianceViewGroup($nutrition);
        $this->addComplianceViewGroup($stress);

        foreach(array('cardio', 'strength', 'water', 'fruit', 'relax', 'sleep') as $dailyViewName) {
            $this->getComplianceView($dailyViewName)->addLink(new Link('Daily Log', '/content/12048?action=showActivity&activityidentifier=343'));
        }

        foreach($this->getComplianceViews() as $view) {
            foreach($view->getLinks() as $link) {
                if($link->getLinkText() == 'Enter/Update Info') {
                    $link->setLinkText('Update');
                }
            }
        }
    }
}



class CHPDemoActivities2014ComplianceProgramPrinter extends CHPComplianceProgramReportPrinter
{
    public function printReport(\ComplianceProgramStatus $status)
    {
        if(!$status->getUser()->insurancetype) {
            $status->getComplianceViewStatus('screening_confirmation')->getComplianceView()->addLink(
                new Link('Update', '/content/12048?action=showActivity&activityidentifier=373')
            );
        }

        ?>
        <style type="text/css">
            .phipTable {
                font-size:0.9em;
            }

            #legend {
                display:none;
            }
        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=335', function(fullPage) {
                    var $page = $(fullPage);

                    $('#combined_points').html(
                        '' + (parseInt($page.find('#spectrum_points').html(), 10) + <?php echo $status->getPoints() ?>)
                    );
                });
            });
        </script>
        <?php
        parent::printReport($status);
    }

    public function printClientMessage()
    {

    }

    public function printClientNote()
    {

    }

    protected function printMaximumNumberOfGroupPoints(ComplianceViewGroup $group)
    {
        $maxPoints = $group->getMaximumNumberOfPoints();
        $availablePoints = $group->getAttribute('available_points');
        ?>
        <td class="maxpoints">
            <?php echo $this->getFormattedPoints($maxPoints); ?> Maximum Points Possible <br/>
            (<?php echo $this->getFormattedPoints($availablePoints); ?> Available Points)
        </td>
        <?php
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow">
            <th colspan="2">My Total Activity Points (350 possible)-</th>
            <td id="activity_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My Total Activity &amp; Report Card Points (1,000 possible)-</th>
            <td id="combined_points"></td>
            <td></td>
            <td></td>
        </tr>
        <?php
    }
}