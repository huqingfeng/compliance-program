<?php
class Dyson2015TobaccoFormComplianceView extends ComplianceView
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

class Dyson2015ComplianceProgram extends ComplianceProgram
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
        $coreGroup = new ComplianceViewGroup('core', ' Core Actions – Required Actions (4) must be completed by the June 30, 2015 deadline');

        $contactInformationView = new UpdateContactInformationComplianceView($programStart, $programEnd);
        $contactInformationView->setReportName('Register with HMI: <br />Provide current contact information (email and phone) on the health portal');
        $contactInformationView->setAttribute('report_name_link', '/content/1094#1areg');
        $coreGroup->addComplianceView($contactInformationView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Onsite Wellness Screening: Onsite Wellness Screenings will be held in Chicago, Aurora, and the Field in June 2015');
        $screeningView->setAttribute('report_name_link', '/content/1094#1bscreen');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Complete Health Power Assessment: Complete lifestyle questionnaire online at www.myhmihealth.com');
        $hraView->setAttribute('report_name_link', '/content/1094#1chpa');
        $coreGroup->addComplianceView($hraView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Update your HMI Account to Include Main Doctor/Primary Care Physician: Go to bcbsil.com and click on Provider Finder to find an in-network doctor.');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#1dpcp');
        $coreGroup->addComplianceView($doctorInformationView);

        $this->addComplianceViewGroup($coreGroup);


        // Build the extra group

        $numbers = new ComplianceViewGroup('points', 'Health Points – Must earn 100 points. Choose how you earn the points from the options below by the June 30, 2015 deadline.');

        $nonSmokerView = new Dyson2015TobaccoFormComplianceView();
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2enonsmoke');
        $nonSmokerView->setReportName('Non Smoker');
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2etobacco');
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($nonSmokerView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('e-Learning Lessons');
        $elearn->setName('complete_elearning');
        $elearn->setAttribute('report_name_link', '/content/1094#2felearn');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(15);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#2gphysact');
        $physicalActivityView->setMaximumNumberOfPoints(75);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $numbers->addComplianceView($physicalActivityView);

        $dysonWellnessChallengeView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 421, 20);
        $dysonWellnessChallengeView->setName('dyson_wellness_challenge');
        $dysonWellnessChallengeView->setAttribute('report_name_link', '/content/1094#2hwellnesschall');
        $dysonWellnessChallengeView->setReportName('Participate in the Dyson Sponsored Wellness Challenge');
        $dysonWellnessChallengeView->setMaximumNumberOfPoints(20);
        $numbers->addComplianceView($dysonWellnessChallengeView);


        $preventiveScreeningsView = new CompleteArbitraryActivityComplianceView("2014-07-01", $programEnd, 422, 30);
        $preventiveScreeningsView->setReportName('Get an Annual Physical Exam');
        $preventiveScreeningsView->setAttribute('report_name_link', '/content/1094#2jexam');
        $preventiveScreeningsView->setMaximumNumberOfPoints(30);
        $numbers->addComplianceView($preventiveScreeningsView);

        $lunchAndLearnView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 423, 5);
        $lunchAndLearnView->setMaximumNumberOfPoints(10);
        $lunchAndLearnView->setName('lunch_and_learn');
        $lunchAndLearnView->setAttribute('report_name_link', '/content/1094#2jlnl');
        $lunchAndLearnView->setReportName('Participate in Wise and Well Lunch and Learn');
        $numbers->addComplianceView($lunchAndLearnView);

        $numbers->setPointsRequiredForCompliance(100);

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
            $printer = new Dyson2015ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($alias = $status->getComplianceView()->getAttribute('elearning_alias')) {
            $view = $this->getAlternateElearningView($status->getComplianceView()->getComplianceViewGroup(), $alias);

            $status->setAttribute(
                'elearning_lessons_completed',
                count($view->getStatus($user)->getAttribute('lessons_completed'))
            );
        }

        if($status->getComment() == '') {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
    }

    private function getAlternateElearningView($group, $alias)
    {
        $view = new CompleteELearningGroupSet($this->getStartDate(), $this->getEndDate(), $alias);

        $view->useAlternateCode(true);

        // These are "optional" - can't be completed for credit

        $view->setNumberRequired(999);

        $view->setComplianceViewGroup($group);

        return $view;
    }
}


class Dyson2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>

     <script type="text/javascript">     
         $(function() {
             $('.view-complete_screening').children(':eq(0)').html('<strong>B</strong>. <a href="/content/1094#1bscreen">Complete Onsite Wellness Screening</a>: <br />Onsite Wellness Screenings will be held in Chicago, Aurora, and the Field in June 2015');
             $('.view-complete_hra').children(':eq(0)').html('<strong>C</strong>. <a href="/content/1094#1chpa">Complete Health Power Assessment</a>: <br />Complete lifestyle questionnaire online at <a href="http://www.myhmihealth.com" target="_blank">www.myhmihealth.com</a>');
             $('.view-update_doctor_information').children(':eq(0)').html('<strong>D</strong>. <a href="/content/1094#1dpcp">Update your HMI Account to Include Main Doctor/Primary Care Physician</a>: <br />Go to <a href="http://bcbsil.com"  target="_blank">bcbsil.com</a> and click on Provider Finder to find an in-network doctor.');

//             $('.headerRow-points').next().children(':eq(0)').html('<strong>E.</strong> Biometric Results');
             $('.view-non_smoker_view').children(':eq(0)').html('<strong>E.</strong> <a href="/content/1094#2etobacco">Non Smoker</a>');
             $('.view-complete_elearning').children(':eq(0)').html('<strong>F.</strong> <a href="/content/1094#2felearn">e-Learning Lessons</a>');
             $('.view-activity_21').children(':eq(0)').html('<strong>G.</strong> <a href="/content/1094#2gphysact">Regular Physical Activity</a>');
             $('.view-dyson_wellness_challenge').children(':eq(0)').html('<strong>H.</strong> <a href="/content/1094#2hwellnesschall">Participate in the Dyson Sponsored Wellness Challenge</a>');
//             $('.view-activity_24').children(':eq(0)').html('<strong>I.</strong> <a href="/content/1094#2ivol">Regular Volunteering</a>');
             $('.view-activity_422').children(':eq(0)').html('<strong>I.</strong> <a href="/content/1094#2iexams">Get an Annual Physical Exam between 7/1/14 and 6/30/15 by Primary Care Physician</a>');
             $('.view-lunch_and_learn').children(':eq(0)').html('<strong>J.</strong> <a href="content/1094#2jlnl">Participate in Wise and Well Lunch and Learn</a>');
             $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>Welcome to your summary page for the 2015 DYSON Wise and Well program.</p>

     <p>To receive the incentive, you MUST take action and meet all criteria below:</p>

    <ol>
        <li>Complete <strong>ALL</strong> of the core required actions by June 30th, 2015.</li>
        <li>Get 100 or more points from key actions taken for good health.
        </li>
    </ol>

    <p>Employees meeting all criteria will each receive $60 per month. This incentive is only being offered to
        employees (not spouse / family members).</p>

     <div class="pageHeading"><a href="/content/1094">Click here for more details about the 2015 Wellness Rewards
         benefit and requirements</a>.
     </div>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
    }
}