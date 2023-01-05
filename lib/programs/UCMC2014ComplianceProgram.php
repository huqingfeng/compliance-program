<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCMC2014CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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

        if($this->waistRequired($user)) {
            $tests[] = 'waist';
        }

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

    protected function waistRequired(User $user)
    {
        if(!$user->hiredate) {
            return true;
        }

        $hireDate = date('Y-m-d', strtotime($user->hiredate));

        return $hireDate < '2014-01-01' || $hireDate > '2014-04-30';
    }
}

class UCMC2014ComplianceProgram extends ComplianceProgram
{
    const DISPLAY_DAYS = 90;
    const CALCULATE_DAYS = 90;
    const HRA_START_DATE = '2014-01-01';
    const SCREENING_START_DATE = '2014-01-01';
    const NEW_HIRE_DATE = '2014-05-01';
    const OLD_HIRE_DISPLAY_DATE = '2014-07-07';
    const OLD_HIRE_CALCULATE_DATE = '2014-07-15';
    const PPO_TYPE = 'BCBS PPO Plan';
    
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
            $printer = new UCMC2014ScreeningTestPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(true);
            $printer->setShowTobacco(false);

            return $printer;
        } else {
            $printer = new UCMC2014ComplianceProgramReportPrinter();
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

    public function filterScreening(array $screening)
    {
        $bodyFatMethod = isset($screening['body_fat_method']) ?
            trim($screening['body_fat_method']) : false;

        $scrDate = date('Y-m-d', strtotime($screening['date']));

        if($scrDate >= '2014-01-14' && $scrDate <= '2014-03-18') {
            $ret = !((bool) $bodyFatMethod);

            return $ret;
        } else {
            return true;
        }
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');

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

        $scrFilter = array($this, 'filterScreening');

        $scrView = new UCMC2014CompleteScreeningComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('report_name_link', '/content/1094#annScreen1a');
        $scrView->setAttribute('report_name_link_new_hire', '/content/1094nh20142015#annScreen1a');
        $scrView->emptyLinks();
        $scrView->addLink(new Link('Schedule/Options', '/content/1051ucmc'));
        $scrView->addLink(new Link('Results', '/content/1006'));
        $scrView->setFilter($scrFilter);
        $core->addComplianceView($scrView);

        $hpaView = new CompleteHRAComplianceView(strtotime(self::HRA_START_DATE), $coreEndDate);
        $hpaView->setReportName('Health Risk Assessment (HRA)');
        $hpaView->setAttribute('report_name_link', '/content/1094#hra1b');
        $hpaView->setAttribute('report_name_link_new_hire', '/content/1094nh20142015#hra1b');
        $hpaView->emptyLinks();
        $hpaView->addLink(new Link('Complete HRA', '/content/1006'));
        $hpaView->addLink(new Link('Results', '/content/1006'));
        $core->addComplianceView($hpaView);

        $updateView = new UpdateContactInformationComplianceView($startDate, $coreEndDate);
        $updateView->setReportName('Enter/Update Key Contact Information (Email, Address, etc.)');
        $updateView->setAttribute('report_name_link', '/content/1094#persContact1c');
        $updateView->setAttribute('report_name_link_new_hire', '/content/1094nh20142015#persContact1c');
        $core->addComplianceView($updateView);

        $tobFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobFree->setName('tobacco');
        $tobFree->setReportName('Be Tobacco Free or Complete Cessation Counseling');
        $tobFree->setAttribute('report_name_link', '/content/1094#tobacco1d');
        $tobFree->setAttribute('report_name_link_new_hire', '/content/1094nh20142015#tobacco1d');
        $tobFree->addLink(new Link('Complete Certificate', '/content/ucmc-tobacco'));
        $tobFree->setPreMapCallback(function($status, User $user) {
            $record = $user->getNewestDataRecord('ucmc_tobacco');

            if($record && $record->compliant) {
                $status->setStatus(ComplianceStatus::COMPLIANT);

                if($record->today) {
                    $status->setComment($record->today);
                }
            } elseif($record && $record->partial) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $core->addComplianceView($tobFree);

        $reqLessons = new CompleteELearningGroupSet($startDate, $coreEndDate, 'required_14-15');
        $reqLessons->setNumberRequired(3);
        $reqLessons->setReportName('Complete 3 Required e-Learning Lessons (3rd lesson is now available)');
        $reqLessons->setAttribute('report_name_link', '/content/1094#eLearn1e');
        $reqLessons->setAttribute('report_name_link_new_hire', '/content/1094nh20142015#eLearn1e');


        $core->addComplianceView($reqLessons);

        $this->addComplianceViewGroup($core);

        $program = $this;

        $numbers = new ComplianceViewGroup('numbers', 'And, earn 100 or more points from options below by %s');
        $numbers->setPointsRequiredForCompliance(function(User $user) use($program) {
            return $program->isNewHire($user) ? 0 : 100;
        });

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('elearning_alias', 'cholesterol');
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->setFilter($scrFilter);
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_alias', 'blood_sugars');
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $glucoseView->setFilter($scrFilter);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $numbers->addComplianceView($glucoseView);
        
        $ha1cView = new ComplyWithHa1cScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $ha1cView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $ha1cView->setReportName('Hemoglobin A1C');
        $ha1cView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ha1cView->setComplianceStatusPointMapper($screeningTestMapper);
        $ha1cView->setFilter($scrFilter);
        $numbers->addComplianceView($ha1cView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_alias', 'blood_pressure');
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->setFilter($scrFilter);
        $numbers->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView(self::SCREENING_START_DATE, $coreEndDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $bodyFatBMIView->setAttribute('elearning_alias', 'body_fat');
        $bodyFatBMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setFilter($scrFilter);
        $numbers->addComplianceView($bodyFatBMIView);

        $prev = new CompleteArbitraryActivityComplianceView('2013-07-01', $coreEndDate, 26, 10);
        $prev->setMaximumNumberOfPoints(30);
        $prev->setReportName('Complete Recommended Preventative Screenings/Exams');
        $prev->setAttribute('report_name_link', '/content/1094#prevScreen2b');
        $numbers->addComplianceView($prev);

        $imm = new CompleteArbitraryActivityComplianceView('2013-07-01', $coreEndDate, 242, 10);
        $imm->setMaximumNumberOfPoints(30);
        $imm->setReportName('Record Immunizations obtained');
        $imm->setAttribute('report_name_link', '/content/1094#immun2c');
        $numbers->addComplianceView($imm);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $coreEndDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(50);
        $physicalActivityView->setMonthlyPointLimit(15);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(1);
        $physicalActivityView->setFractionalDivisorForPoints(1);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#physAct2d');
        $numbers->addComplianceView($physicalActivityView);

        $stressToolbox = new CompleteELearningGroupSet($startDate, $coreEndDate, 'stress_toolbox');
        $stressToolbox->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $stressToolbox->setReportName('Complete the Stress Toolbox');
        $stressToolbox->setName('stress_toolbox');
        $stressToolbox->setAttribute('report_name_link', '/content/1094#stress2e');
        $numbers->addComplianceView($stressToolbox);

        $mindfulEating = new CompleteELearningGroupSet($startDate, $coreEndDate, 'mindful_eating');
        $mindfulEating->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $mindfulEating->setReportName('Complete Mindful Eating');
        $mindfulEating->setName('mindful_eating');
        $mindfulEating->setAttribute('report_name_link', '/content/1094#mindful2f');
        $numbers->addComplianceView($mindfulEating);

        $additionalLearn = new CompleteAdditionalELearningLessonsComplianceView($startDate, $coreEndDate, 5);
        $additionalLearn->setMaximumNumberOfPoints(50);
        $additionalLearn->addIgnorableGroup('mindful_eating');
        $additionalLearn->addIgnorableGroup('stress_toolbox');
        $additionalLearn->addIgnorableGroup('required_14-15');
        $additionalLearn->setAttribute('report_name_link', '/content/1094/1094#addelearn2g');
        $numbers->addComplianceView($additionalLearn);

        $eap = new PlaceHolderComplianceView(null, 0);
        $eap->setName('eap');
        $eap->setMaximumNumberOfPoints(40);
        $eap->setReportName('Attend EAP or UCMC Wellness class');
        $eap->addLink(new Link('See Topic Details', '/content/ucmc_classCal'));
        $eap->setAttribute('report_name_link', '/content/1094#class2h');
        $numbers->addComplianceView($eap);

        $nav = new AFSCMEViewWorkbookComplianceView($startDate, $coreEndDate);
        $nav->setReportName('Check My HealthNavigator online health record (PPO only)');
        $nav->setName('nav');
        $nav->setAttribute('report_name_link', '/content/1094#healthNav2i');
        $nav->setMaximumNumberOfPoints(30);
        $nav->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setStatus(null);

            if($user->insurancetype == UCMC2014ComplianceProgram::PPO_TYPE) {
                $status->setPoints(min(30, count($status->getAttribute('months_viewed')) * 10));
            } else {
                $status->setPoints(0);
            }
        });
        $numbers->addComplianceView($nav);

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

class UCMC2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $fixGroupName = function($name) use($status) {
            $group = $status->getComplianceViewGroupStatus($name)->getComplianceViewGroup();

            $group->setReportName(sprintf(
                $group->getReportName(),
                $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser())
            ));
        };

        $fixGroupName('core');
        $fixGroupName('numbers');

        parent::printReport($status);
    }

    protected function printCustomRows($status)
    {
        ?>
        <tr class="headerRow headerRow-totals">
            <th><strong>3.</strong> Deadlines, Requirements & Status</th>
            <td># Earned</td>
            <td>Status</td>
            <td>Minimum Needed for 2014 Incentive</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Total Points & Incentive Status: Deadline:
                <?php echo $status->getComplianceProgram()
                                  ->getDisplayableEndDate('m/d/Y', $status->getUser()) ?> =
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant()) : ?>
                    Done!
                <?php else : ?>
                    Not Done!
                <?php endif ?>
            </td>
            <td>100 or more points + 1ABCDE complete</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Total & Bonus Status* as of <?php echo date('m/d/Y') ?> =
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant() && $status->getPoints() >= 150) : ?>
                    Done!
                <?php else : ?>
                    Not Done!
                <?php endif ?>
            </td>
            <td>150 or more points + 1ABCDE complete</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center">
                * Become eligible for BONUS gift with 150 or more points.
                (Prize $50 value click <a href="#">here</a> to view prizes)
            </td>
        </tr>
        <?php
    }
    
    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have your screening results in the healthy zone:', '/content/1094#biometrics2a'));

        $this->pageHeading = 'UCMC 2014-2015 Employee Wellness Benefit';

        $this->showName = true;
        $this->setShowTotal(false);
        $this->showCompleted = true;

        $this->screeningAllResultsArea = '<p style="color:#FF0000">Points in this section are based on your
            most recent 2014 screening results. </p>';
        $this->screeningLinkText = 'View all 8 results';



        $this->screeningLinkArea = '';

        $this->tableHeaders['links'] = 'Action Links';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $isNewHire = $status->getComplianceProgram()->isNewHire($status->getUser());

        if($isNewHire) {
            foreach($status->getComplianceProgram()->getComplianceViews() as $view) {
                $view->setAttribute(
                    'report_name_link',
                    $view->getAttribute('report_name_link_new_hire')
                );
            }
        }

        $escaper = new Escaper();

        ?>
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
                background-color:#8B0020;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .status img {
                width:25px;
            }

            <?php if($status->getUser()->insurancetype != UCMC2014ComplianceProgram::PPO_TYPE) :  ?>
                .view-nav {
                    display:none;
                }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
            <?php if($isNewHire) : ?>
                $('tr.headerRow-numbers').nextAll('tr').css('display', 'none');
                $('tr.headerRow-numbers').css('display', 'none');
            <?php endif ?>
            });
        </script>

        <?php if($isNewHire) : ?>
            <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>

            <p>Welcome to your summary page for the UCMC 2014-2015 Well Rewards
                benefit.  To receive your applicable incentive, you MUST take action
                and complete all of the following criteria within 90 days of
                your start date. The sooner you complete all requirements, the
                sooner the per-pay-period incentive credit can begin
                (if applicable)</p>

            <p>Employees meeting all of the below requirements who enrolled in
                2014-2015 UCMC Medical Insurance Plan will receive the following
                per-pay-period credit on their health insurance premium for the
                applicable pay periods of the current plan year (07/01/14-06/30/15):</p>

            <ul style="margin-left:150px;">
                <li>Bi-weekly: $11.36 per-pay-period reduction</li>
            </ul>

            <p>Employees meeting the criteria that have elected not to enroll
                in a UCMC Medical Plan, or are ineligible for benefits, will
                receive a free gift. (<a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">Click here to view choices</a>)</p>

            <p style="text-align:center;"><a href="/content/1094nh20142015">Click here for more details about the Well Rewards Program.</a></p>

        <?php else : ?>

            <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>

            <p>Welcome to your summary page for the UCMC 2014-2015 Well Rewards benefit.
                To receive the incentives, eligible employees MUST take action
                and meet <strong>ALL</strong> requirements below by June 30, 2014.</p>

            <ol>
                <li>Complete ALL of the core required; AND</li>
                <li>Earn 100 or more wellness points from key screening results and
                    health actions taken</li>
            </ol>

            <p>Employees meeting all of the requirements below who enrolled in a
                2014-2015 UCMC Medical Insurance Plan will receive a per-pay-period
                credit on their health insurance premium (up to $250 annually).
                Employees meeting the criteria that have elected not to enroll in
                a 2014 medical plan, or are ineligible for benefits, will receive a
                free gift. (<a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">View choices</a>)</p>

            <div class="pageHeading">
                 <a href="/content/1094">Click here for all details about the 2014-2015 Well Rewards benefit</a>
                 <a href="#">FAQ Page</a>
            </div>

        <?php endif ?>

        <div style="color:#FF0000;text-align:center">
            Click on any item below for more details
        </div>

        <div>
            <strong>Status Updates:</strong> To complete actions click
            on the links below. If the status did not change for an item you
            are working on, you may need to go back and enter missing
            information or entries to earn more points. The status for HRA and
            wellness screenings will not change until after your report is
            processed. Thanks you for your actions and patience!
        </div>

        <br/>
        <?php
    }
}

class UCMC2014ScreeningTestPrinter extends ScreeningProgramReportPrinter
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
                <div align="center">120 - < 200</div>
                </td>
              <td valign="top" bgcolor="#FFFF00">
                <div align="center">200 - 240<br />
                    100 - &lt;120 </div>
                </td>
              <td valign="top" bgcolor="#FF909A">
                <div align="center">> 240<br />
                    &lt;100 </div>
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
                <div align="center">60 - 129</div>
                </td>
              <td bgcolor="#FFFF00">
                <div align="center">130 - &lt;159<br />
                    &lt;60 </div>
                </td>
              <td bgcolor="#FF909A">
                <div align="center">≥159</div>
                </td>
            </tr>
            <tr>
              <td><ol start="4">
                <li><strong>Triglycerides</strong></li>
              </ol></td>
              <td bgcolor="#CCFFCC"><div align="center">30 - &lt; 150</div></td>
              <td bgcolor="#FFFF00"><div align="center">150 - &lt;200<br />
                      <30 </div></td>
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



              <td bgcolor="#CCFFCC"><div align="center">3.9 - < 5.7</div></td>
              <td bgcolor="#FFFF00"><div align="center">5.7 - &lt;6.5<br />
                      &lt;3.9 </div>
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
          <span style="font-size:10px">^ An HDL of ≥60 and LDL of 60-100 are optimal and offer even greater protection against heart disease and stroke.</span>
         <?php
    }

    private $showTobacco = true;
}