<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCMC2015ComplianceProgram extends ComplianceProgram
{
    const DISPLAY_DAYS = 90;
    const CALCULATE_DAYS = 120;
    const HRA_START_DATE = '2015-01-01';
    const NEW_HIRE_DATE = '2015-05-01';
    const OLD_HIRE_DISPLAY_DATE = '2015-06-15';
    const OLD_HIRE_CALCULATE_DATE = '2015-06-24';
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
            $printer = new UCMC2015ComplianceProgramReportPrinter();
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

        if($scrDate >= '2015-01-01' && $scrDate <= '2015-01-31') {
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

        $programStartDate = $this->getStartDate();
        $programEndDate = $this->getEndDate();

        $program = $this;

        $startDate = function($format, User $user) use($program) {
            if($program->isNewHire($user)) {
                return date($format, strtotime($user->hiredate));
            } else {
                return date($format, strtotime('2015-03-16'));
            }
        };

        $coreStartDate = function($format, User $user) use ($program) {
            if($program->isNewHire($user)) {
                return date($format, strtotime('2015-05-01'));
            } else {
                return $program->getStartDate();
            }
        };

        $coreEndDate = array($this, 'getCalculatedEndDate');

        $core = new ComplianceViewGroup('core', 'Core actions required by %s');

        $scrFilter = array($this, 'filterScreening');

        $scrView = new UCMC2014CompleteScreeningComplianceView($coreStartDate, $coreEndDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('report_name_link', '/content/1094new2015#annScreen1a');
        $scrView->setAttribute('report_name_link_new_hire', '/content/1094nh#annScreen1a');
        $scrView->emptyLinks();
        $scrView->addLink(new Link('Schedule/Options', '/content/1094nh#annScreen1a'));
        $scrView->addLink(new Link('Results', '/content/1006'));
        $scrView->setFilter($scrFilter);
        $core->addComplianceView($scrView);

        $hpaView = new CompleteHRAComplianceView(function($format, User $user) use($program) {
            if($program->isNewHire($user)) {
                return date($format, strtotime($user->hiredate));
            } else {
                return $program->getStartDate();
            }
        }, $coreEndDate);
        $hpaView->setReportName('Health Risk Assessment (HRA)');
        $hpaView->setAttribute('report_name_link', '/content/1094new2015#hra1b');
        $hpaView->setAttribute('report_name_link_new_hire', '/content/1094nh#hra1b');
        $hpaView->emptyLinks();
        $hpaView->addLink(new Link('Complete HRA', '/content/1006'));
        $hpaView->addLink(new Link('Results', '/content/1006'));
        $core->addComplianceView($hpaView);

        $fixLink = function($links, $text) {
            $link = reset($links);

            $link->setLinktext($text);
        };

        $updateView = new UpdateContactInformationComplianceView($startDate, $coreEndDate);
        $updateView->setReportName('Confirm/Update Key Contact Information (Email, Address, etc.)');
        $updateView->setAttribute('report_name_link', '/content/1094new2015#persContact1e');
        $updateView->setAttribute('report_name_link_new_hire', '/content/1094nh#persContact1e');
        $fixLink($updateView->getLinks(), 'Confirm/Update Info');
        $core->addComplianceView($updateView);

        $tobFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobFree->setName('tobacco');
        $tobFree->setReportName('Be Tobacco Free or Complete Cessation Counseling');
        $tobFree->setAttribute('report_name_link', '/content/1094new2015#tobacco1c');
        $tobFree->setAttribute('report_name_link_new_hire', '/content/1094nh#tobacco1c');
        $tobFree->addLink(new Link('Complete Certificate', '/content/ucmc-tobacco-2015'));
        $tobFree->setPreMapCallback(function($status, User $user) use ($program) {
            $record = $user->getNewestDataRecord('ucmc_tobacco_2015');

            if($program->isNewHire($user)) {
                $startDate = date('Y-m-d', strtotime($user->hiredate));

                $hireDate = $user->getDateTimeObject('hiredate')->format('U');
                $endDate = date('Y-m-d', strtotime(sprintf('+%s days', 120), $hireDate));
            } else {
                $startDate = '2015-03-16';
                $endDate = '2015-06-24';
            }

            if($record && $record->compliant && $startDate <= date('Y-m-d', strtotime($record->today)) && date('Y-m-d', strtotime($record->today)) <= $endDate) {
                $status->setStatus(ComplianceStatus::COMPLIANT);

                if($record->today) {
                    $status->setComment($record->today);
                }
            } elseif($record && $record->partial  && $startDate <= date('Y-m-d', strtotime($record->today)) && date('Y-m-d', strtotime($record->today)) <= $endDate) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $core->addComplianceView($tobFree);


        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $coreEndDate);
        $doctorView->setName('doctor');
        $doctorView->setReportName('Confirm Having a Primary Care Provider');
        $fixLink($doctorView->getLinks(), 'Confirm/Update Info');
        $doctorView->setAttribute('report_name_link', '/content/1094new2015#pcp1f');
        $doctorView->setAttribute('report_name_link_new_hire', '/content/1094nh#pcp1f');
        $core->addComplianceView($doctorView);

        $reqLessons = new CompleteELearningLessonsComplianceView('2015-01-01', $coreEndDate, function(User $user) use($program) {
            if($program->isNewHire($user)) {
                return array(1283, 1339, 1341, 1360);
            } else {
                return array(1283, 1339, 1341);
            }
        });
        $reqLessons->setNumberRequired(3);
        $reqLessons->setName('required_elearning');
        $reqLessons->setReportName('Complete 3 Required e-Learning Lessons (1 lesson released each month)');
        $reqLessons->setAttribute('report_name_link', '/content/1094new2015#eLearn1d');
        $reqLessons->setAttribute('report_name_link_new_hire', '/content/1094nh#eLearn1d');
        $reqLessons->emptyLinks();
        $reqLessons->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=required_15-16'));
        $core->addComplianceView($reqLessons);

        $this->addComplianceViewGroup($core);


        $numbers = new ComplianceViewGroup('numbers', 'And, earn 100 or more points from options below by %s');
        $numbers->setPointsRequiredForCompliance(function(User $user) use($program) {
            return $program->isNewHire($user) ? 0 : 100;
        });

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('elearning_alias', 'cholesterol');
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->setFilter($scrFilter);
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_alias', 'blood_sugars');
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $glucoseView->setFilter($scrFilter);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $numbers->addComplianceView($glucoseView);
        
        $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $ha1cView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $ha1cView->setReportName('Hemoglobin A1C');
        $ha1cView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ha1cView->setComplianceStatusPointMapper($screeningTestMapper);
        $ha1cView->setFilter($scrFilter);
        $numbers->addComplianceView($ha1cView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_alias', 'blood_pressure');
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->setFilter($scrFilter);
        $numbers->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $bodyFatBMIView->setAttribute('elearning_alias', 'body_fat');
        $bodyFatBMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setFilter($scrFilter);
        $numbers->addComplianceView($bodyFatBMIView);

        $prev = new CompleteArbitraryActivityComplianceView('2014-07-01', $programEndDate, 26, 10);
        $prev->setMaximumNumberOfPoints(30);
        $prev->setReportName('Get Recommended Preventive Screenings/Exams');
        $prev->setAttribute('report_name_link', '/content/1094new2015#prevScreen2b');
        $numbers->addComplianceView($prev);

        $imm = new CompleteArbitraryActivityComplianceView('2014-07-01', $programEndDate, 242, 10);
        $imm->setMaximumNumberOfPoints(30);
        $imm->setReportName('Get Flu Shot & Other Recommended Immunizations');
        $imm->setAttribute('report_name_link', '/content/1094new2015#immun2c');
        $numbers->addComplianceView($imm);

        $physicalActivityView = new PhysicalActivityComplianceView($programStartDate, $programEndDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(50);
        $physicalActivityView->setMonthlyPointLimit(24);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(1);
        $physicalActivityView->setFractionalDivisorForPoints(1);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094new2015#physAct2d');
        $numbers->addComplianceView($physicalActivityView);

        $stress = new CompleteELearningLessonComplianceView($programStartDate, $programEndDate, new ELearningLesson_v2(1313));
        $stress->setName('stress');
        $stress->setReportName('Complete the Adapting to Stress Skill Builder');
        $stress->emptyLinks();
        $stress->addLink(new Link('Review/Complete', '/sitemaps/adapting_stress'));
        $stress->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $stress->setAttribute('report_name_link', '/content/1094new2015#stress2e');
        $numbers->addComplianceView($stress);


        $thc = new PlaceHolderComplianceView(null, 0);
        $thc->setName('thc');
        $thc->setReportName('Complete the 2015 Total Health Challenge (January-March)');
        $thc->setMaximumNumberOfPoints(50);
        $thc->addLink(new FakeLink('Updated from THC Program', '#'));
        $thc->setAttribute('report_name_link', '/content/1094new2015#thc2f');
        $numbers->addComplianceView($thc);

        $weight = new PlaceHolderComplianceView(null, 0);
        $weight->setName('weight');
        $weight->setReportName('Verify Other Qualified Actions Taken To Get to Healthy Weight');
        $weight->setMaximumNumberOfPoints(25);
        $weight->addLink(new FakeLink('Details', '/content/1094new2015#weight2g'));
        $weight->setAttribute('report_name_link', '/content/1094new2015#weight2g');
        $numbers->addComplianceView($weight);

        $additionalLearn = new CompleteAdditionalELearningLessonsComplianceView($programStartDate, $programEndDate, 5);
        $additionalLearn->setMaximumNumberOfPoints(50);
        $additionalLearn->setReportName('Complete Additional e-Learning Lessons');
        $additionalLearn->addIgnorableGroup('mindful_eating');
        $additionalLearn->addIgnorableGroup('stress_toolbox');
        $additionalLearn->addIgnorableGroup('required_14-15');
        $additionalLearn->addIgnorableGroup('required_15-16');
        $additionalLearn->addIgnorableGroup('required_thc_2015');
        $additionalLearn->addIgnorableLesson('1313');
        $additionalLearn->setAttribute('report_name_link', '/content/1094new2015/1094new2015#addelearn2h');
        $numbers->addComplianceView($additionalLearn);

        $eap = new PlaceHolderComplianceView(null, 0);
        $eap->setName('eap');
        $eap->setMaximumNumberOfPoints(40);
        $eap->setReportName('Attend EAP or UCMC Wellness class');
        $eap->addLink(new FakeLink('Updated from Participation List(s)', '#'));
        $eap->setAttribute('report_name_link', '/content/1094new2015#class2i');
        $numbers->addComplianceView($eap);

        $volunteer = new VolunteeringComplianceView($programStartDate, $programEndDate);
        $volunteer->setMinutesDivisorForPoints(60);
        $volunteer->setPointsMultiplier(1);
        $volunteer->setReportName('Volunteer Time to Help Others - Type &amp; Time');
        $volunteer->setAttribute('report_name_link', '/content/1094new2015#vol2j');
        $volunteer->setMaximumNumberOfPoints(30);
        $numbers->addComplianceView($volunteer);

        $sodium = new EventLogComplianceView($programStartDate, $programEndDate, 'aha_sodium_quiz');
        $sodium->addLink(new Link('Complete Quiz', '/compliance_programs/localAction?id=437&local_action=aha_sodium_quiz', false, '_blank'));
        $sodium->setName('sodium');
        $sodium->setReportName('Take the American Heart Association Sodium Quiz');
        $sodium->setAllowPointsOverride(true);
        $sodium->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $sodium->setMaximumNumberOfPoints(20);
        $sodium->setAttribute('report_name_link', '/content/1094new2015#amaQuiz2k');
        $sodium->addLink(new Link('Take Pledge', 'http://sodiumbreakup.heart.org/pledge/?DDCA_RegSource=8&DDCA_Medium=MWA&DDCA_Term=UCM'));
        $numbers->addComplianceView($sodium);

        $nav = new AFSCMEViewWorkbookComplianceView($programStartDate, $programEndDate);
        $nav->setReportName('Check My HealthNavigator online health record');
        $nav->setName('nav');
        $nav->setAttribute('report_name_link', '/content/1094new2015#healthNav2l');
        $nav->setMaximumNumberOfPoints(30);
        $nav->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setStatus(null);

            if(in_array($user->insurancetype, UCMC2015ComplianceProgram::$ppoType)) {
                $status->setPoints(min(30, count($status->getAttribute('months_viewed')) * 10));
            } else {
                $status->setPoints(0);
            }
        });
        $numbers->addComplianceView($nav);

        $care = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $care->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $care->setName('care');
        $care->setReportName('Work with HealthReach Care Counselor/Coach or Graduate');
        $care->addLink(new Link('Contact Counselor/Coach', '#'));
        $care->addLink(new Link('More Info', '#'));
        $care->setAttribute('report_name_link', '/content/1094new2015#hcs2m');
        $numbers->addComplianceView($care);

        $this->addComplianceViewGroup($numbers);
    }

    public function getLocalActions()
    {
        return array(
            'aha_sodium_quiz' => array($this, 'executeAhaSodiumQuiz'),
        );
    }

    public function executeAhaSodiumQuiz(sfActions $actions)
    {
        $actions->getContext()->getEventDispatcher()->notify(new sfEvent(
            $actions->getUser(),
            'system.event',
            array('type' => 'aha_sodium_quiz')
        ));

        $actions->redirect('http://sodiumbreakup.heart.org/test-your-knowledge/');
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
        if($user->client_id == 2401) return true;

        return $user->hiredate && $user->hiredate >= self::NEW_HIRE_DATE;
    }

    public function isFullReport(User $user)
    {
        if(isset($_REQUEST['full_report'])) return true;

        return false;
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

    public static $ppoType = array(
        'BCBS PPO Plan',
        'BCBS Standard Plan',
        'BCBS HDHP'
    );
}

class UCMC2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td>Minimum Needed for 2015 Incentive</td>
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
            <td>100 or more points + 1ABCDEF complete</td>
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
            <td>150 or more points + 1ABCDEF complete</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center">
                * Become eligible for BONUS gift with 150 or more points.
                (Prize $50 value click <a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">here</a> to view prizes)
            </td>
        </tr>
        <?php
    }
    
    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have your screening results in the healthy zone:', '/content/1094new2015#biometrics2a'));

        $this->pageHeading = 'UCMC 2015-2016 Employee Wellness Benefit';

        $this->showName = true;
        $this->setShowTotal(false);
        $this->showCompleted = true;

        $this->screeningAllResultsArea = '<br/><br/>
            <a href="/sitemaps/health_centers/15913">Blood Fat Center</a><br/><br/>
            <a href="/sitemaps/health_centers/15401">Blood Sugar Center</a><br/><br/>
            <a href="/sitemaps/health_centers/15919">Blood Pressure Center</a><br/><br/>
            <a href="/sitemaps/health_centers/15932">Body Metrics Center</a>

            <script type="text/javascript">
                $(function() {
                    $("a[href=\"/content/1094new2015#biometrics2a\"]").parent().append(
                        "<div style=\"color: #FF0000\">Points in section 2A are based on your most recent 2015 screening results.</div>"
                    );
                });
            </script>
        ';
        $this->screeningLinkText = 'View all 8 results';



        $this->screeningLinkArea = '';

        $this->tableHeaders['links'] = 'Action Links';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $isNewHire = $status->getComplianceProgram()->isNewHire($status->getUser());
        $isFullReport = $status->getComplianceProgram()->isFullReport($status->getUser());

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

            <?php if($status->getComplianceViewStatus('care')->getStatus() == ComplianceStatus::NA_COMPLIANT
                    || !in_array($status->getUser()->insurancetype, UCMC2015ComplianceProgram::$ppoType)) : ?>
                .view-care {
                    display:none;
                }
            <?php endif ?>

            <?php if(!in_array($status->getUser()->insurancetype, UCMC2015ComplianceProgram::$ppoType)) :  ?>
                .view-nav {
                    display:none;
                }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
            <?php if($isNewHire && !$isFullReport) : ?>
                $('tr.headerRow-numbers').nextAll('tr').css('display', 'none');
                $('tr.headerRow-numbers').css('display', 'none');
                $('tr.view-complete_screening .links').html('<a target="_self" href="/content/1094nh#annScreen1a">Schedule/Options</a> <a target="_self" href="/content/1006">Results</a>');
            <?php else: ?>
                $('tr.view-complete_screening .links').html('<a target="_self" href="/content/1006">Results</a>');
            <?php endif ?>

            <?php if($isNewHire) : ?>
                $('tr.view-required_elearning').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094new2015#eLearn1d">Complete 3 Required e-Learning Lessons (First two lessons and the appropriate UCMC New Hire Benefit Enrollment Overview 2015-16)</a>');
            <?php else : ?>
                $('tr.view-required_elearning').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094new2015#eLearn1d">Complete 3 Required e-Learning Lessons (First two lessons and the appropriate UCMC Benefit Enrollment Overview 2015-16)</a>');
            <?php endif ?>
            });
        </script>

        <div>
            <div style="width: 70%; float: left;">
                <?php if($isNewHire) : ?>
                    <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>

                    <p>Welcome to your summary page for the UCMC 2015-2016 Well Rewards benefit.  To receive your applicable
                        incentive, MUST take action and meet <strong>ALL</strong> of the following requirements (actions)
                        below within 90 days of your start date.  The sooner you complete all requirements, the sooner the
                        per-pay-period incentive credit can begin.</p>

                    <p>Employees meeting all of the requirements below who enrolled in a 2015-2016 UCMC Medical Insurance Plan
                        will receive the following per-pay-period credit on their health insurance premium for the applicable
                        pay periods of the current plan year (7/01/15 – 06/30/16):</p>

                    <ul style="margin-left:150px;">
                        <li>Bi-weekly: $12.50 per-pay-period reduction (@ up to 24 pay periods per year)</li>
                        <li>Monthly: $25 per-pay-period reduction.</li>
                    </ul>

                    <p>Employees meeting the criteria who did not elect to enroll in a 2015 medical plan, or are
                        ineligible for benefits, will receive a free gift
                        (<a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">Click here to view choices</a>)</p>


                <?php else : ?>

                    <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>

                    <p>Welcome to your summary page for the UCMC 2015-2016 Well Rewards benefit.
                        To receive the incentives, eligible employees MUST take action
                        and meet <strong>ALL</strong> requirements below by June 15, 2015.</p>

                    <ol>
                        <li>Complete ALL of the core required; AND</li>
                        <li>Earn 100 or more wellness points from key screening results and
                            health actions taken</li>
                    </ol>

                    <p>Employees meeting all of the requirements below who enrolled in a
                        2015-2016 UCMC Medical Insurance Plan will receive a per-pay-period
                        credit on their health insurance premium ($300 annually).
                        Employees meeting the criteria that have elected not to enroll in
                        any 2015 medical plan, or are ineligible for benefits, will receive a
                        free gift. (<a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">View choices</a>)</p>

                <?php endif ?>
            </div>
            <div style="width: 30%; float: left; background-color: #CCFFCC;">
                <div style="padding: 5px;">
                    <p><strong>Important:</strong>  This will be your ONLY opportunity to qualify for the 2015-16 medical
                        plan premium credit or gift.</p>

                    <p>If for any reason you think you may enroll in a UCMC medical benefit plan after
                        July 1, 2015 (life event, change in benefit status, etc.)

                        <?php if($isNewHire) : ?>
                            <strong>please be sure to complete these requirements within the first 90 days of your start date.</strong>
                        <?php else : ?>
                            <strong>please be sure to complete these requirements by June 15, 2015.</strong>
                        <?php endif ?>
                    </p>
                </div>
            </div>
        </div>

        <div style="clear:both"></div>

        <div class="pageHeading">
             <a href="/content/<?php echo $isNewHire ? '1094nh' : '1094new2015' ?>">Click here for all details about the 2015-2016 Well Rewards benefit</a>
             <a href="/content/<?php echo $isNewHire ? '1094nh' : '1094new2015' ?>#ucmc_faqs">FAQ Page</a>
        </div>
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
