<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCMC2013CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        $tests = array(
            'cholesterol',
            'hdl',
            'triglycerides',
            'glucose',
            'ha1c',
            'height',
            'weight',
            'systolic',
            'diastolic'
        );

        $testsCompleted = 0;

        foreach($tests as $test) {
            if(isset($array[$test]) && trim($array[$test])) {
                $testsCompleted++;
            }
        }

        if(count($tests) === $testsCompleted) {
            return ComplianceStatus::COMPLIANT;
        } elseif($testsCompleted > 0) {
            return ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            return ComplianceStatus::NOT_COMPLIANT;
        }
    }
}

class UCMC2013ComplianceProgram extends ComplianceProgram
{
    const DISPLAY_DAYS = 60;
    const CALCULATE_DAYS = 90;
    const SCREENING_START_DATE = '2013-01-01';
    const NEW_HIRE_DATE = '2013-07-01';
    const OLD_HIRE_DISPLAY_DATE = '2013-07-19';
    const OLD_HIRE_CALCULATE_DATE = '2013-07-31';
    
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, null, false, true,  null, null, true, true);
        $printer->setShowText(false, false, true);

        $additionalFieldsToAdd = array(
            'employee-interest-survey-participation-date' => 'Employee Interest Survey participation date',
            'thc-2013-participation-date'                 => 'THC 2013 participation date',
            'setting-the-pace-participation-date'         => 'Setting the Pace participation date'
        );

        foreach($additionalFieldsToAdd as $additionalFieldSlug => $additionalField) {
            $printer->addCallbackField($additionalField, function(User $user) use($additionalFieldSlug) {
                $additionalFieldObject = $user->getUserAdditionalFieldBySlug($additionalFieldSlug);

                return $additionalFieldObject ?
                    $additionalFieldObject->value : '';
            });
        }

        $printer->addCallbackField('Hire Date', function(User $user) {
            return $user->hiredate;
        });

        $printer->addCallbackField('Payroll Schedule', function(User $user) {
            return $user->getNewestDataRecord('payroll_schedule', true)->rule;
        });

        $printer->addCallbackField('Step It Up 2013 Registration Date', function(User $user) {
                return SelectQuery::create()
                    ->hydrateSingleScalar()
                    ->select('f.creation_date')
                    ->from('user_data_fields f')
                    ->innerJoin('user_data_records r')
                    ->on('r.id = f.user_data_record_id')
                    ->where('r.user_id = ?', array($user->id))
                    ->andWhere('r.type = ?', array('ucmc_step_it_up'))
                    ->andWhere('f.field_name = ?', array('registered'))
                    ->andWhere('f.field_value = 1')
                    ->limit(1)
                    ->execute();
        });

        $printer->addCallbackField('THC 2014 Pre-Eval Date', function(User $user) {
           return SelectQuery::create()
               ->hydrateSingleScalar(true)
               ->select('date')
               ->from('screening')
               ->where('user_id = ?', array($user->id))
               ->andWhere('date BETWEEN ? AND ?', array('2014-01-01', '2014-03-18'))
               ->andWhere('body_fat_method IS NOT NULL')
               ->limit(1)
               ->execute();
        });

        $printer->addCallbackField('THC 2014 Registration Date', function(User $user) {
            $thcRegistrationDate = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('sr.shadow_timestamp')
                ->from('compliance_program_record_user_registrations r')
                ->innerJoin('shadow_compliance_program_record_user_registrations sr')
                ->on('sr.id = r.id')
                ->where('r.user_id = ?', array($user->id))
                ->orderBy('sr.shadow_timestamp desc')
                ->limit(1)
                ->execute();

            if($thcRegistrationDate) {
                $stamp = strtotime($thcRegistrationDate.' UTC');

                return date('Y-m-d H:i:s', $stamp);
            } else {
                return '';
            }
        });

        $printer->addStatusFieldCallback('Blood Work Date', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('comply_with_total_cholesterol_screening_test')->getAttribute('date');
        });

        $printer->addStatusFieldCallback('Biometric Screening Date', function(ComplianceProgramStatus $status) {
            $bmiDate = $status->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test')->getAttribute('bmi_date');

            return $bmiDate;
        });

        $printer->addStatusFieldCallback('Full Compliance Effective Date', function(ComplianceProgramStatus $status) {
            if($status->isCompliant()) {
                $date = null;

                foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if(($viewStatusDate = $viewStatus->getAttribute('date')) && ($viewStatusDateStamp = strtotime($viewStatusDate))) {
                            if($date === null || $viewStatusDateStamp > $date) {
                                $date = $viewStatusDateStamp;
                            }
                        }
                    }
                }

                if($date !== null) {
                    return date('m/d/Y', $date);
                }
            }

            return '';
        });

        return $printer;
    }    
    
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new UCMC2013ScreeningTestPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(false);

            return $printer;

            $scrStartDate = isset($_GET['screening_start_date']) ?
                $_GET['screening_start_date'] : self::SCREENING_TEST_START;

            $scrEndDate = isset($_GET['screening_end_date']) ?
                $_GET['screening_end_date'] : self::SCREENING_TEST_END;

            $screening = ScreeningTable::getInstance()->findCompletedForUserBetweenDates(
                $this->getActiveUser(),
                new DateTime($scrStartDate),
                new DateTime($scrEndDate),
                array('execute' => false)
            )->limit(1)->fetchOne();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new UCMC2013ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function showGroup($group)
    {
        if($group->getName() == 'fitness') {
            $this->tableHeaders['completed'] = 'Updated';
        } else {
            $this->tableHeaders['completed'] = 'Completed';
        }

        return true;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Criteria Met', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Working On It', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Started', '/images/lights/redlight.gif'),
           // ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Received Yet', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $coreEndDate = array($this, 'getCalculatedEndDate');

        $core = new ComplianceViewGroup('core', 'Core actions required by %s');

        $scrView = new UCMC2013CompleteScreeningComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('report_name_link', '/content/1094#annScreen1a');
        $scrView->emptyLinks();
        //scrView->addLink(new Link('Schedule Screening', '/content/1051'));
        $scrView->addLink(new Link('Results', '/content/1006'));
        $core->addComplianceView($scrView);

        $hpaView = new CompleteHRAComplianceView($startDate, $coreEndDate);
        $hpaView->setReportName('Health Risk Assessment (HRA)');
        $hpaView->setAttribute('report_name_link', '/content/1094#hra1b');
        $hpaView->emptyLinks();
        $hpaView->addLink(new Link('Complete HRA', '/content/1006'));
        $hpaView->addLink(new Link('Results', '/content/1006'));
        $core->addComplianceView($hpaView);

        $updateView = new UpdateContactInformationComplianceView($startDate, $coreEndDate);
        $updateView->setReportName('Enter/Update Key Contact Information (Email, Address, etc.)');
        $updateView->setAttribute('report_name_link', '/content/1094#persContact1c');
        $core->addComplianceView($updateView);

        $this->addComplianceViewGroup($core);

        $numbers = new ComplianceViewGroup('numbers', 'Know your numbers and learn more. Optional actions not required for the incentive.');
        $numbers->setNumberOfViewsRequired(0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 9);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('elearning_alias', 'cholesterol');
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_alias', 'blood_sugars');
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);
        
        $ha1cView = new ComplyWithHa1cScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $ha1cView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $ha1cView->setReportName('Hemoglobin A1C');
        $ha1cView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($ha1cView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_alias', 'blood_pressure');
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView(self::SCREENING_START_DATE, $endDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $bodyFatBMIView->setAttribute('elearning_alias', 'body_fat');
        $bodyFatBMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView(self::SCREENING_START_DATE, $endDate);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Non-User of Tobacco');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');        
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $numbers->addComplianceView($nonSmokerView);

        $this->addComplianceViewGroup($numbers);
    }
    
    public function getCalculatedEndDate($format, User $user)
    {
        return $this->getHireEndDate($format, $user, false);
    }
    
    public function getDisplayableEndDate($format, User $user)
    {
        return $this->getHireEndDate($format, $user, true);
    }

    public function isNewHire(User $user)
    {
        return $user->hiredate && $user->hiredate >= self::NEW_HIRE_DATE;
    }
    
    private function getHireEndDate($format, User $user, $forDisplay)
    {
        if($this->isNewHire($user)) {
            $days = $forDisplay ? self::DISPLAY_DAYS : self::CALCULATE_DAYS;

            $hireDate = $user->getDateTimeObject('hiredate')->format('U');

            return date($format, strtotime(sprintf('+%s days', $days), $hireDate));
        } else {
            $date = $forDisplay ? self::OLD_HIRE_DISPLAY_DATE : self::OLD_HIRE_CALCULATE_DATE;

            return date($format, strtotime($date));
        }     
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

class UCMC2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $group = $status->getComplianceViewGroupStatus('core')->getComplianceViewGroup();

        $group->setReportName(sprintf(
            $group->getReportName(),
            $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser())
        ));

        parent::printReport($status);
    }    
    
    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Are these measures in the healthy green zone?  If not, consider taking some e-lessons to learn more about it and what you can do to improve your result.', '/content/1094#biometrics2a'));

        $this->pageHeading = 'UCMC 2013-2014 Employee Wellness Benefit: 1st 60-days Well Reward Requirements';

        $this->numberScreeningCategory = false;
        $this->showName = true;
        $this->setShowTotal(false);
        $this->showCompleted = false;

        $this->screeningAllResultsArea = '';
        $this->screeningLinkText = '';

        $this->addStatusCallbackColumn('Context', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            switch($view->getComplianceViewGroup()->getName()) {
                case 'core':
                    $default = $view instanceof DateBasedComplianceView ?
                        $view->getEndDate('m/d/Y') : '';

                    return $view->getAttribute('deadline', $default);

                case 'numbers':
                    return $status->getComment();

                    break;
            }
        });

        $this->addStatusCallbackColumn('Context2', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            switch($view->getComplianceViewGroup()->getName()) {
                case 'core':
                    return $status->getComment();

                case 'numbers':
                    return $status->getAttribute('elearning_lessons_completed');

                    break;
            }
        });

        $this->screeningLinkArea = '';

        $this->tableHeaders['links'] = 'Action Links';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $escaper = new Escaper();

        ?>
        <script type="text/javascript">
            $(function() {
                // Context header lines
                $('.headerRow-core').find('td').first().html('Deadline');
                $('.headerRow-numbers').find('td').first().html('Result');

                $('.headerRow-core').find('td').next().first().html('Date Completed');
                $('.headerRow-numbers').find('td').next().first().html('# Lessons Completed');

                // Rework Action Links to line up eLearning links with appropriate views

                var $tc = $('td.links[rowspan="9"]').parent('tr');

                $tc.find('td.links').remove();

                var $topLinks = $tc.prev('tr').find('.links');

                var $glucose = $tc.next('tr').next('tr').next('tr').next('tr');
                
                var $ha1c = $glucose.next('tr');

                var $bp = $ha1c.next('tr');

                var $bmi = $bp.next('tr');

                var $tobacco = $bmi.next('tr');

                //$topLinks.append('<a href="?preferredPrinter=ScreeningProgramReportPrinter&id=239">View All 8 Results after screening results are received.</a>');

                $tc.append('<td class="links" rowspan="4"><a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Review Blood Fat Lessons</a></td>');

                $glucose.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review Blood Sugar Lessons</a></td>');
                
                $ha1c.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review Blood Sugar Lessons</a></td>');

                $bp.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Review BP Lessons</a></td>');

                $bmi.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Review Body Metrics Lessons</a></td>');

                $tobacco.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=tobacco">Review Tobacco Lessons</a></td>');

               // Swap status and Context2

                $.each($('.phipTable tr'), function() {
                    $(this).children(':eq(3)').after($(this).children(':eq(2)'));
                });
                
                $('.view-complete_screening').children(':eq(1)').html('<?php echo $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser()) ?>');
                $('.view-complete_hra').children(':eq(1)').html('<?php echo $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser()) ?>');
                $('.view-update_contact_information').children(':eq(1)').html('<?php echo $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser()) ?>');
                
                $('.phipTable tbody').children(':eq(5)').children(':eq(2), :eq(3), :eq(4)').remove();
                $('.phipTable tbody').children(':eq(5)').find(':eq(2)').after('<td colspan="3" style="text-align:center;"><span style="color:#FF0000">Allow 3-4 weeks for screening results and status change to show.</span><br/><br/><a href="<?php echo sprintf('/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=%s', $status->getComplianceProgram()->getID()) ?>">View All 9 Results</a></td>');
                $('.phipTable tbody tr.view-complete_screening td').first().append('&nbsp;&nbsp;&nbsp;&nbsp;Allow 3-4 weeks for results and status change to show.');
            });
        </script>
        <style type="text/css">
            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            #legend {
                text-align:center;
            }

            .legendEntry {
                display:inline;
                padding:10px;
                float:none;
                width:auto;
            }

            .phipTable .headerRow, #legendText {
                background-color:#547698;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .status img {
                width:25px;
            }
        </style>

        <?php if($status->getComplianceProgram()->isNewHire($status->getUser())) : ?>

        <?php else : ?>

        <?php endif ?>

        <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>
        <p>Welcome to your summary page for the UCMC 2013-2014 Well Rewards benefit.
            To receive your applicable incentive, you MUST take action and complete all of the following criteria
            within 60 days of your start date. The sooner you complete all requirements, the sooner the per-pay-period
            incentive credit can begin (if applicable).</p>
        <p>Employees meeting all of the requirements below who enrolled in a 2013-2014 UCMC Medical Insurance
            Plan will receive the following per-pay-period credit on their health insurance premium for the applicable
            pay periods of the current plan year (07/01/13-06/30/14):</p>

         <ul>
             <li>Bi-weekly: $5.72 per-pay-period reduction</li>
             <li>Monthly: $10.91 per-pay-period reduction</li><p></p>
         </ul>

          <p>&nbsp;&nbsp;&nbsp;<strong>Note: </strong>Actions completed below will also fulfill part of the requirements of your 2014-2015 Well Rewards
              requirements<br /> &nbsp;&nbsp;&nbsp;(see 2014-2015 Well Rewards To-Do’s button located on your home page).</p>

          <p>Employees meeting the criteria that have elected not to enroll in a UCMC medical plan, or are ineligible for benefits,
                 will receive a free gift. <a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">(Click here to view choices)</a></p>

          <p><strong>Status Updates:</strong> To complete actions click on the links below. If the status did not change for an item
           you are working on, you may need to go back and enter missing information or entries for it to change. The status for HRA and wellness
            screenings will not change until after your report is processed. Thank you for your actions and patience!
          </p>

             <div class="pageHeading">
                 <a href="/content/1094">Click here for more details about the Well Rewards program</a>
             </div>



        <?php
    }
}

class UCMC2013ScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function setShowTobacco($bool)
    {
        $this->showTobacco = $bool;
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
        <p>
            <a href="/compliance_programs?id=<?php echo $status->getComplianceProgram()->getID() ?>">Back to Wellness Rewards</a>
        </p>

        <?php parent::printReport($status); ?>

        <style type="text/css">
            .phipTable .headerRow {
                background-color:#547698;
            }
        </style>

        <br/>
        <br/>

        <table width="100%"
            border="1"
            cellpadding="3"
            cellspacing="0"
            class="tableCollapse"
            id="table3">
            <tr>
              <td width="42%">Risk ratings & colors =</td>
              <td width="22%">
                <div align="center"><strong>OK/Good</strong></div>
                </td>
              <td width="17%">
                <div align="center"><strong>Borderline</strong></div>
                </td>
              <td width="19%">
                <div align="center"><strong>At-Risk</strong></div>
                </td>
              </tr>

            <tr>
                <td><p><u>Key measures, ranges &amp; related points:</u></p></td>
                <td bgcolor="#CCFFCC" style="text-align:center">10 points</td>
                <td bgcolor="#FFFF00" style="text-align:center">5 points</td>
                <td bgcolor="#FF909A" style="text-align:center">0 points</td>
            </tr>
            <tr>
              <td valign="top">
                <ol>
                  <li><strong>Total cholesterol</strong></li>
                  </ol>
                </td>
              <td valign="top" bgcolor="#CCFFCC">
                <div align="center">100 - < 200</div>
                </td>
              <td valign="top" bgcolor="#FFFF00">
                <div align="center">200 - 240<br />
                  90 - &lt;100 </div>
                </td>
              <td valign="top" bgcolor="#FF909A">
                <div align="center">> 240<br />
                  &lt; 90 </div>
                </td>
              </tr>
            <tr>
              <td>
                <ol start="2">
                  <li><strong>HDL cholesterol</strong> ^<br />
                  • Men<br />
                  • Women</li>
                  </ol>
                </td>
              <td bgcolor="#CCFFCC">
                <div align="center">≥ 40<br />
                  ≥ 50 </div>
                </td>
              <td bgcolor="#FFFF00">
                <div align="center">25 - &lt;40<br />
                  25 - &lt;50 </div>
                </td>
              <td bgcolor="#FF909A">
                <div align="center">< 25<br />
                  &lt; 25 </div>
                </td>
              </tr>
            <tr>
              <td>
                <ol start="3">
                  <li><strong>LDL cholesterol</strong> ^</li>
                  </ol>
                </td>
              <td bgcolor="#CCFFCC">
                <div align="center">≤ 129</div>
                </td>
              <td bgcolor="#FFFF00">
                <div align="center">130 - &lt;159</div>
                </td>
              <td bgcolor="#FF909A">
                <div align="center">≥159</div>
                </td>
            </tr>
            <tr>
              <td><ol start="4">
                <li><strong>Triglycerides</strong></li>
              </ol></td>
              <td bgcolor="#CCFFCC"><div align="center">&lt; 150</div></td>
              <td bgcolor="#FFFF00"><div align="center">150 - &lt;200</div></td>
              <td bgcolor="#FF909A"><div align="center">≥ 200</div></td>
            </tr>
            <tr>
              <td valign="top">
                <ol start="5">
                  <li><strong>Glucose</strong> (Fasting)<br />
                  • Men<br />
                  <br />
                  • Women</li>
                  </ol>
                </td>
              <td valign="top" bgcolor="#CCFFCC">
                <div align="center"><br />
                  70 - &lt;100<br />
                  <br />
        <br />
                  70 - &lt;100 </div>
                </td>
              <td valign="top" bgcolor="#FFFF00">
                <div align="center"><br />
                  100 - &lt;126<br />
                  50 - &lt;70<br />
                  <br />
                  100 - &lt;126<br />
                  40 - &lt;70 <br />
                </div>
                </td>
              <td valign="top" bgcolor="#FF909A">
                <div align="center"><br />
                  ≥ 126<br />
                  &lt; 50
                  <br />
                  <br />
                  ≥ 126 <br />
                  &lt; 40 </div>
                </td>
            </tr>
            <tr>
              <td><ol start="6">
                <li><strong>Hemoglobin A1C</strong></li>
                </ol></td>



              <td bgcolor="#CCFFCC"><div align="center">< 5.7</div></td>
              <td bgcolor="#FFFF00"><div align="center">5.7 - &lt;6.5</div></td>
              <td bgcolor="#FF909A"><div align="center">≥ 6.5</div></td>
            </tr>
            <tr>
              <td valign="bottom"><ol start="7">
                <li><strong>Blood pressure</strong>*<br />
                  <br />
                  Systolic<br />
                  Diastolic </li>
              </ol></td>
              <td bgcolor="#CCFFCC"><div align="center"><br />
                &lt; 120<br />
                &lt; 80 </div></td>
              <td bgcolor="#FFFF00"><div align="center"><br />
                120 - &lt;140<br />
                80 - &lt;90 </div></td>
              <td bgcolor="#FF909A"><div align="center"><br />
                ≥ 140<br />
                ≥ 90 </div></td>
            </tr>
            <tr>
              <td valign="bottom">
                <ol start="8">
                  <li>The better of:<br />
                    <strong>Body Mass Index<br />
                      </strong>• men & women<br />
                    -- OR --<br />
                    <strong>% Body Fat:<br />
                      </strong>• Men<br />
                    • Women
                    </li>
                  </ol>
                </td>
              <td bgcolor="#CCFFCC">
                <div align="center">
                  <p><br />
                    18.5 - <25
                    <br />
                    <br />
                  </p>
                  <p>&nbsp;</p>
                  <p>6 - &lt;18%<br />
                    14 - &lt;25%</p>
                </div>
                </td>
              <td bgcolor="#FFFF00">
                <div align="center">
                  <p><br />
                    25 - <30 <br />
                    <br />
                  </p>
                  <p>&nbsp;</p>
                  <p>18 - &lt;25<br />
                    25 - &lt;32%</p>
                </div>
                </td>
              <td bgcolor="#FF909A">
                <div align="center">
                  <p><br />
                    ≥ 30; < 18.5<br />
                    <br />
                  </p>
                  <p>&nbsp;</p>
                  <p>            ≥ 25; &lt; 6%<br />
                    ≥ 32; &lt; 14%</p>
                </div>
                </td>
            </tr>
            <?php if($this->showTobacco) : ?>
                <tr>
                  <td><ol start="9">
                    <li><strong>Tobacco</strong></li>
                  </ol></td>
                  <td bgcolor="#CCFFCC"><div align="center">Non-user</div></td>
                  <td bgcolor="#FFFF00"><div align="center">User</div></td>
                  <td bgcolor="#FF909A"><div align="center">User</div></td>
                </tr>
            <?php endif ?>
          </table>
          <br>
          <span style="font-size:10px">^ An HDL of ≥60 and LDL of <100 are optimal and offer even greater protection against heart disease and stroke.</span>
         <?php
    }

    private $showTobacco = true;
}