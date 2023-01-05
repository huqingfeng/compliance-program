<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCCN2016CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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
            'diastolic',
            'waist'
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


class UCCN2016ComplianceProgram extends ComplianceProgram
{
    const DISPLAY_DAYS = 90;
    const CALCULATE_DAYS = 120;
    const NEW_HIRE_DATE = '2016-05-01';
    const OLD_HIRE_DISPLAY_DATE = '2016-06-15';
    const OLD_HIRE_CALCULATE_DATE = '2016-06-22';
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
            $printer = new UCMC2016ScreeningTestPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(true);
            $printer->setShowTobacco(false);

            return $printer;
        } else {
            $printer = new UCCN2016ComplianceProgramReportPrinter();
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

        if($scrDate >= '2016-01-01' && $scrDate <= '2016-03-14') {
            $ret = !((bool) $bodyFatMethod);

            return $ret;
        } else {
            return true;
        }
    }

    public function loadSessionParameters()
    {
        $_SESSION['manua_override_fitbit_parameters'] = array(
            'activity_id' => '509',
            'question_id' => '110',
            'start_date' => '2016-03-14',
            'end_date' => '2016-06-12',
            'product_name'  => 'Total Steps',
            'header_text'  => '<p><a href="/compliance_programs?id=716">Back to My Dashboard</a></p>',
            'override' => 0
        );
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');

        $this->loadSessionParameters();

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
                return date($format, strtotime('2016-03-14'));
            }
        };

        $coreStartDate = function($format, User $user) use ($program) {
            if($program->isNewHire($user)) {
                return date($format, strtotime('2015-05-01'));
            } else {
                return date($format, strtotime('2016-01-01'));
            }
        };

        $coreEndDate = array($this, 'getCalculatedEndDate');

        $core = new ComplianceViewGroup('core', 'Core actions required by %s');

        $scrFilter = array($this, 'filterScreening');

        $scrView = new UCCN2016CompleteScreeningComplianceView($coreStartDate, $coreEndDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('report_name_link', '/content/1094carenet2016#annScreen1a');
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
                return date($format, strtotime('2016-01-01'));
            }
        }, $coreEndDate);
        $hpaView->setReportName('Complete Health Risk Assessment (HRA)');
        $hpaView->setAttribute('report_name_link', '/content/1094carenet2016#hra1b');
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
        $updateView->setReportName('Enter/Confirm Key Contact Information (Email, Address, etc.)');
        $updateView->setAttribute('report_name_link', '/content/1094carenet2016#persContact1c');
        $updateView->setAttribute('report_name_link_new_hire', '/content/1094nh#persContact1e');
        $fixLink($updateView->getLinks(), 'Update/Confirm Info');
        $updateView->emptyLinks();
        $updateView->addLink(new Link('Enter/Update Info', '/wms2/profile/contact?redirect='.urlencode('/compliance/ucmc-2016-2017/well-rewards/compliance_programs')));
        $core->addComplianceView($updateView);

        $tobFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobFree->setName('tobacco');
        $tobFree->setReportName('Be Tobacco Free or Complete Cessation Counseling');
        $tobFree->setAttribute('report_name_link', '/content/1094carenet2016#tobacco1d');
        $tobFree->setAttribute('report_name_link_new_hire', '/content/1094nh#tobacco1c');
        $tobFree->addLink(new Link('Complete Certificate', '/content/ucmc-tobacco-2016'));
        $tobFree->setPreMapCallback(function($status, User $user) use ($program) {
            $record = $user->getNewestDataRecord('ucmc_tobacco_2016');

            if($program->isNewHire($user)) {
                $startDate = date('Y-m-d', strtotime($user->hiredate));

                $hireDate = $user->getDateTimeObject('hiredate')->format('U');
                $endDate = date('Y-m-d', strtotime(sprintf('+%s days', 120), $hireDate));
            } else {
                $startDate = '2016-03-14';
                $endDate = '2016-06-22';
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


//        $reqLessons = new CompleteELearningGroupSet('2016-03-24', $coreEndDate, 'required_16-17');
        $reqLessons = new CompleteELearningLessonsComplianceView('2016-03-24', $coreEndDate, function(User $user) use($program) {
            return array(1481, 1430, 1421);
        });
        $reqLessons->setNumberRequired(3);
        $reqLessons->setName('required_elearning');
        $reqLessons->setReportName('Complete 3 Required e-Learning Lessons');
        $reqLessons->setAttribute('report_name_link', '/content/1094carenet2016#eLearn1e');
        $reqLessons->setAttribute('report_name_link_new_hire', '/content/1094nh#eLearn1d');
        $reqLessons->emptyLinks();
        $reqLessons->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=required_16-17'));
        $core->addComplianceView($reqLessons);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $coreEndDate);
        $doctorView->setName('doctor');
        $doctorView->setReportName('Have a Primary Care Provider');
        $fixLink($doctorView->getLinks(), 'Update/Confirm Info');
        $doctorView->setAttribute('report_name_link', '/content/1094carenet2016#pcp1f');
        $doctorView->setAttribute('report_name_link_new_hire', '/content/1094nh#pcp1f');
        $core->addComplianceView($doctorView);

        $this->addComplianceViewGroup($core);


        $numbers = new ComplianceViewGroup('numbers', 'And, earn 100 or more points from options below by %s');
        $numbers->setPointsRequiredForCompliance(function(User $user) use($program) {
            return $program->isNewHire($user) ? 0 : 100;
        });

        $screeningTestMapper = new ComplianceStatusPointMapper(15, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($coreStartDate, $coreEndDate);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setFields(array('cholesterol', 'body_fat_method', 'labid', 'date'));
        $totalCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setFilter($scrFilter);
        $totalCholesterolView->overrideTestRowData(90, 100, 199.999, 240);
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $hdlCholesterolView->setFields(array('hdl', 'body_fat_method', 'labid', 'date'));
        $hdlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->setFilter($scrFilter);
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $ldlCholesterolView->setFields(array('ldl', 'body_fat_method', 'labid', 'date'));
        $ldlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->setFilter($scrFilter);
        $ldlCholesterolView->overrideTestRowData(0, 0, 129, 158.999);
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $trigView->setFields(array('triglycerides', 'body_fat_method', 'labid', 'date'));
        $trigView->setAttribute('elearning_alias', 'cholesterol');
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->setFilter($scrFilter);
        $trigView->overrideTestRowData(0, 0, 149.999, 199.999);
        $numbers->addComplianceView($trigView, false);

        $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $ha1cView->setFields(array('ha1c', 'body_fat_method', 'labid', 'date'));
        $ha1cView->setReportName('Hemoglobin A1C');
        $ha1cView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ha1cView->setComplianceStatusPointMapper($screeningTestMapper);
        $ha1cView->setFilter($scrFilter);
        $numbers->addComplianceView($ha1cView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $glucoseView->setFields(array('glucose', 'body_fat_method', 'labid', 'date'));
        $glucoseView->setReportName('Fasting Glucose');
        $glucoseView->setAttribute('elearning_alias', 'blood_sugars');
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $glucoseView->setFilter($scrFilter);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(50, 70, 99.999, 125.999, 'M');
        $glucoseView->overrideTestRowData(40, 70, 99.999, 125.999, 'F');
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $bloodPressureView->setSystolicTestFields(array('systolic', 'body_fat_method', 'labid', 'date'));
        $bloodPressureView->setDiastolicTestFields(array('diastolic', 'body_fat_method', 'labid', 'date'));
        $bloodPressureView->setAttribute('elearning_alias', 'blood_pressure');
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->setFilter($scrFilter);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 119.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 79.999, 89.999);
        $numbers->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($coreStartDate, $coreEndDate);
        $bodyFatBMIView->setBMITestFields(array('bmi', 'body_fat_method', 'labid', 'date'));
        $bodyFatBMIView->setBodyfatTestFields(array('bodyfat', 'body_fat_method', 'labid', 'date'));
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setAttribute('elearning_alias', 'body_fat');
        $bodyFatBMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setFilter($scrFilter);
        $bodyFatBMIView->overrideBMITestRowData(18.5, 18.5, 24.999, 29.999);
        $bodyFatBMIView->overrideBodyFatTestRowData(8, 8, 19.999, 24.999, 'M');
        $bodyFatBMIView->overrideBodyFatTestRowData(17, 17, 28.999, 34.999, 'F');
        $numbers->addComplianceView($bodyFatBMIView);

        $prev = new CompleteArbitraryActivityComplianceView('2015-07-01', $coreEndDate, 26, 10);
        $prev->setMaximumNumberOfPoints(50);
        $prev->setReportName('Get Recommended Preventive Screenings/Exams');
        $numbers->addComplianceView($prev);

        $imm = new CompleteArbitraryActivityComplianceView('2015-07-01', $coreEndDate, 242, 10);
        $imm->setMaximumNumberOfPoints(30);
        $imm->setReportName('Get Flu Shot & Other Recommended Immunizations');
        $numbers->addComplianceView($imm);

        $physicalActivityView = new PhysicalActivityComplianceView('2016-01-01', $coreEndDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(50);
        $physicalActivityView->setMonthlyPointLimit(24);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(1);
        $physicalActivityView->setFractionalDivisorForPoints(1);
        $physicalActivityView->setName('physical_activity');
        $numbers->addComplianceView($physicalActivityView);

        $stress = new CompleteELearningLessonComplianceView($programStartDate, $coreEndDate, new ELearningLesson_v2(1313));
        $stress->setName('stress');
        $stress->setReportName('Complete the Adapting to Stress Skill Builder');
        $stress->emptyLinks();
        $stress->addLink(new Link('Review/Complete', '/sitemaps/adapting_stress'));
        $stress->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $numbers->addComplianceView($stress);

        $stepItUp = new PlaceHolderComplianceView(null, 0);
        $stepItUp->setName('step_it_up');
        $stepItUp->setReportName('Participate in 2015 Step It Up and meet Step Goal');
        $stepItUp->setMaximumNumberOfPoints(50);
        $numbers->addComplianceView($stepItUp);

        $thc = new PlaceHolderComplianceView(null, 0);
        $thc->setName('thc');
        $thc->setReportName('Complete the 2016 Total Health Challenge (January-March)');
        $thc->setMaximumNumberOfPoints(75);
        $thc->addLink(new FakeLink('Updated from THC Program', '#'));
        $numbers->addComplianceView($thc);

        $weight = new PlaceHolderComplianceView(null, 0);
        $weight->setName('weight');
        $weight->setReportName('Verify Qualified Actions Taken to Achieve a Healthy Weight');
        $weight->setMaximumNumberOfPoints(50);
        $weight->addLink(new Link('Review Options', '/content/1094carenet2016#weight2h'));
        $numbers->addComplianceView($weight);

        $additionalLearn = new CompleteAdditionalELearningLessonsComplianceView($programStartDate, $coreEndDate, 5);
        $additionalLearn->setMaximumNumberOfPoints(50);
        $additionalLearn->setReportName('Additional e-Learning Lessons');
        $additionalLearn->addIgnorableGroup('mindful_eating');
        $additionalLearn->addIgnorableGroup('stress_toolbox');
        $additionalLearn->addIgnorableGroup('required_14-15');
        $additionalLearn->addIgnorableGroup('required_15-16');
        $additionalLearn->addIgnorableGroup('required_16-17');
        $additionalLearn->addIgnorableGroup('required_thc_2015');
        $additionalLearn->addIgnorableLesson('1313');
        $numbers->addComplianceView($additionalLearn);

        $eap = new PlaceHolderComplianceView(null, 0);
        $eap->setName('eap');
        $eap->setMaximumNumberOfPoints(50);
        $eap->setReportName('Attend EAP or UCMC Wellness class');
        $eap->addLink(new Link('Class Schedule', '/content/ucmc_classCal'));
        $numbers->addComplianceView($eap);

        $donateBlood = new CompleteArbitraryActivityComplianceView('2015-07-01', $coreEndDate, 503, 30);
        $donateBlood->setMaximumNumberOfPoints(30);
        $donateBlood->setReportName('Donate Blood');
        $numbers->addComplianceView($donateBlood);

        $aha = new PlaceHolderComplianceView(null, 0);
        $aha->setName('aha');
        $aha->setMaximumNumberOfPoints(20);
        $aha->setReportName('AHA National Walking Day');
        $numbers->addComplianceView($aha);

        $fitness = new CompleteArbitraryActivityComplianceView('2015-07-01', $coreEndDate, 506, 20);
        $fitness->setMaximumNumberOfPoints(60);
        $fitness->setReportName('Self-Directed Fitness Activity');
        $numbers->addComplianceView($fitness);

        $cubicle = new PlaceHolderComplianceView(null, 0);
        $cubicle->setName('cubicle');
        $cubicle->setMaximumNumberOfPoints(30);
        $cubicle->setReportName('Cubicle to 5K');
        $numbers->addComplianceView($cubicle);

        $fitbitView = new UCMCWalkingCampaignFitbitComplianceView($programStartDate, '2016-06-12', 70000, 5);
        $fitbitView->setReportName('Log 70,000 steps a week');
        $fitbitView->setMaximumNumberOfPoints(50);
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Sync Fitbit/View Steps <br />', '/content/ucan-fitbit-individual'));
        $fitbitView->addLink(new Link('Enter Steps Manually', '/content/12048?action=showActivity&activityidentifier=509'));
        $numbers->addComplianceView($fitbitView);

        $volunteer = new VolunteeringComplianceView('2015-07-01', $coreEndDate);
        $volunteer->setMinutesDivisorForPoints(60);
        $volunteer->setPointsMultiplier(1);
        $volunteer->setReportName('Volunteer Time to Help Others - Type &amp; Time');
        $volunteer->setMaximumNumberOfPoints(30);
        $numbers->addComplianceView($volunteer);

        $bicycle = new PlaceHolderComplianceView(null, 0);
        $bicycle->setName('bicycle');
        $bicycle->setMaximumNumberOfPoints(30);
        $bicycle->setReportName('Bicycle Commuter Program');
        $bicycle->addLink(new FakeLink('Updated from Participation Lists', '#'));
        $numbers->addComplianceView($bicycle);

        $nav = new AFSCMEViewWorkbookComplianceView($programStartDate, $coreEndDate);
        $nav->setReportName('Check My HealthNavigator online health record (PPO only)');
        $nav->setName('nav');
        $nav->setMaximumNumberOfPoints(30);
        $nav->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStartDate, $programEndDate) {
            $status->setStatus(null);

            $shadowInsurancePlanTypes = SelectQuery::create()
                ->select('insurance_plan_type')
                ->from('shadow_users')
                ->where('id = ?', array($user->id))
                ->andWhere('shadow_timestamp BETWEEN ? AND ?', array(date('Y-m-d H:i:s', $programStartDate), date('Y-m-d H:i:s', $programEndDate)))
                ->andWhere('insurance_plan_type IS NOT NULL')
                ->orderBy('shadow_timestamp desc')
                ->hydrateScalar()
                ->execute()
                ->toArray();

            $qualified = false;
            foreach($shadowInsurancePlanTypes as $shadowInsurancePlanType) {
                if(in_array($shadowInsurancePlanType, UCMC2016ComplianceProgram::$historicalPpoType)) {
                    $qualified = true;
                }
            }

            if($qualified) {
                $status->setPoints(min(30, count($status->getAttribute('months_viewed')) * 10));
            } else {
                $status->setPoints(0);
            }
        });
        $numbers->addComplianceView($nav);



        $care = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $care->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $care->setName('care');
        $care->setReportName('Work with HealthReach Care Counselor/Coach or Graduate');
        $care->addLink(new Link('Contact Counselor/Coach', '#'));
        $care->addLink(new Link('More Info', '#'));
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
        'PPO Premier',
        'PPO Standard',
        'PPO Advantage'
    );
}

class UCCN2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td>Minimum Needed for 2016 Incentive</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Well Rewards Incentive Status: Deadline:
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

        <tr class="headerRow headerRow-totals">
            <th>Well Rewards Points Advantage</th>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td style="text-align:right">
                Earn 150-300 Points
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant() && $status->getPoints() >= 150 && $status->getPoints() <= 300) : ?>
                    <img src="/images/lights/greenlight.gif" class="light" alt="">
                <?php endif ?>
            </td>
            <td>Entry into $250 Gift Card Drawing</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Earn 301-400 Points
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant() && $status->getPoints() >= 301 && $status->getPoints() <= 400) : ?>
                    <img src="/images/lights/greenlight.gif" class="light" alt="">
                <?php endif ?>
            </td>
            <td>$25 Gift Card + entry into $500 Gift Card Drawing</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Earn 401-500 Points
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant() && $status->getPoints() >= 401 && $status->getPoints() <= 500) : ?>
                    <img src="/images/lights/greenlight.gif" class="light" alt="">
                <?php endif ?>
            </td>
            <td>$35 Gift Card + entry into $1,000 Gift Card Drawing</td>
        </tr>
        <tr>
            <td style="text-align:right">
                Earn 501 or more points
            </td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td style="text-align:center">
                <?php if($status->isCompliant() && $status->getPoints() >= 501) : ?>
                    <img src="/images/lights/greenlight.gif" class="light" alt="">
                <?php endif ?>
            </td>
            <td>$50 Gift Card + entry into $2,000 Gift Card  Drawing</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center">
                <a href="http://www.hallmarkbusinessconnections.com/merchant-list">Click here</a> to view Merchant List
            </td>
        </tr>
        <?php
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have your screening results in the healthy zone:', '/content/1094carenet2016#biometrics2a'));

        $this->pageHeading = 'UCMC Employee Wellness Benefit: <br />2016-2017 Well Rewards Requirements (To-Do’s)';

        $this->showName = true;
        $this->setShowTotal(false);
        $this->showCompleted = true;

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

            .view-comply_with_ha1c_screening_test .links{
                line-height: 15px;
            }

            .view-comply_with_blood_pressure_screening_test .links{
                line-height: 12px;
            }

            .view-comply_with_body_fat_bmi_screening_test .links{
                line-height: 12px;
            }

            <?php if($status->getComplianceViewStatus('care')->getStatus() == ComplianceStatus::NA_COMPLIANT) : ?>
            .view-care {
                display:none;
            }
            <?php endif ?>

            <?php if(!in_array($status->getUser()->insurance_plan_type, UCCN2016ComplianceProgram::$ppoType)) :  ?>
            .view-nav {
                display:none;
            }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
                $('.headerRow-numbers').after(
                    '<tr><td><strong>A</strong>. <a target="_self" href="/content/1094carenet2016#biometrics2a">Have your screening results in the healthy zone:</a>' +
                    '<div style="color: #FF0000">Points in this section are based on your most recent 2016 screening results.</div></td>' +
                    '<td class="points"></td><td class="points"></td><td class="links" rowspan="9">' +
                    '<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=716">View All 8 Results after screening results are received.</a>' +
                    '</td></tr>');


                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Total Cholesterol');
                $('.view-comply_with_hdl_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• HDL Cholesterol');
                $('.view-comply_with_ldl_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• LDL Cholesterol');
                $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Triglycerides');
                $('.view-comply_with_ha1c_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Hemoglobin A1C');
                $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Fasting Glucose');
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Blood Pressure');
                $('.view-comply_with_body_fat_bmi_screening_test').children(':eq(0)').html('&nbsp;&nbsp;&nbsp;• Better of body mass index or % body fat');

                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').remove();
                $('.view-comply_with_hdl_screening_test').children(':eq(3)').remove();
                $('.view-comply_with_ldl_screening_test').children(':eq(3)').remove();
                $('.view-comply_with_triglycerides_screening_test').children(':eq(3)').remove();

                $('.view-comply_with_ha1c_screening_test').children(':eq(3)').remove();
                $('.view-comply_with_glucose_screening_test').children(':eq(3)').remove();

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(3)').remove();
                $('.view-comply_with_body_fat_bmi_screening_test').children(':eq(3)').remove();

                $('.view-activity_26').children(':eq(0)').html('<strong>B</strong>. <a href="/content/1094carenet2016#prevScreen2b">Get Recommended Preventive Screenings/Exams</a>');
                $('.view-activity_242').children(':eq(0)').html('<strong>C</strong>. <a href="/content/1094carenet2016#immun2c">Get Flu Shot & Other Recommended Immunizations</a>');
                $('.view-physical_activity').children(':eq(0)').html('<strong>D</strong>. <a href="/content/1094carenet2016#physAct2d">Get Regular Physical Activity</a>');
                $('.view-stress').children(':eq(0)').html('<strong>E</strong>. <a href="/content/1094carenet2016#stress2e">Complete the Adapting to Stress Skill Builder</a>');
                $('.view-step_it_up').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094carenet2016#siu2f">Participate in 2015 Step It Up and meet Step Goal</a>');
                $('.view-thc').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094carenet2016#thc2g">Complete the 2016 Total Health Challenge (January-March)</a>');
                $('.view-weight').children(':eq(0)').html('<strong>H</strong>. <a href="/content/1094carenet2016#weight2h">Verify Qualified Actions Taken to Achieve a Healthy Weight</a>');
                $('.view-complete_elearning_additonal').children(':eq(0)').html('<strong>I</strong>. <a href="/content/1094carenet2016#addelearn2i">Additional e-Learning Lessons</a>');
                $('.view-eap').children(':eq(0)').html('<strong>J</strong>. <a href="/content/1094carenet2016#class2j">Attend EAP or UCMC Wellness class</a>');
                $('.view-activity_503').children(':eq(0)').html('<strong>K</strong>. <a href="/content/1094carenet2016#donate2k">Donate Blood</a>');
                $('.view-aha').children(':eq(0)').html('<strong>L</strong>. <a href="/content/1094carenet2016#walkingDay2l">AHA National Walking Day</a>');
                $('.view-aha').children('.links').html('<a target="_blank" href="/resources/7577/WalkingDayFlyer.pdf">National Walking Day Flyer</a>');

                $('.view-activity_506').children(':eq(0)').html('<strong>M</strong>. <a href="/content/1094carenet2016#fitness2m">Self-Directed Fitness Activity</a>');
                $('.view-cubicle').children(':eq(0)').html('<strong>N</strong>. <a href="/content/1094carenet2016#cubicle2n">Cubicle to 5K</a>');
                $('.view-fitbit').children(':eq(0)').html('<strong>O</strong>. <a href="/content/1094carenet2016#70kSteps2o">Log 70,000 steps a week</a>');
                $('.view-activity_24').children(':eq(0)').html('<strong>P</strong>. <a href="/content/1094carenet2016#vol2p">Volunteer Time to Help Others - Type & Time</a>');
                $('.view-bicycle').children(':eq(0)').html('<strong>Q</strong>. <a href="/content/1094carenet2016#bicycle2q">Bicycle Commuter Program</a>');
                $('.view-nav').children(':eq(0)').html('<strong>R</strong>. <a href="/content/1094carenet2016#healthNav2r">Check My HealthNavigator online health record (PPO only)</a>');
                $('.view-care').children(':eq(0)').html('<strong>S</strong>. <a href="/content/1094carenet2016#hcs2s">Work with HealthReach Care Counselor/Coach or Graduate</a>');

                $('.view-nav').children(':eq(3)').html('<a target="_blank" href="/content/12056">View Health Navigator</a>');

                <?php if($isNewHire && !$isFullReport) : ?>
                $('tr.headerRow-numbers').nextAll('tr').css('display', 'none');
                $('tr.headerRow-numbers').css('display', 'none');
                $('tr.view-complete_screening .links').html('<a target="_self" href="/content/1094nh#annScreen1a">Schedule/Options</a> <a target="_self" href="/content/1006">Results</a>');
                <?php else: ?>
                $('tr.view-complete_screening .links').html('<a href="/content/1051">Screening Sign-Ups</a>  <br /><a target="_self" href="/content/1006">Results</a>');
                <?php endif ?>

            });
        </script>

        <div>
            <div>
                <?php if($isNewHire) : ?>
                    <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>,</p>

                    <p>Welcome to your summary page for the UCMC 2016-2017 Well Rewards benefit. To
                        receive the incentives, eligible employees MUST take action and meet <strong>ALL</strong> requirements
                        below by within 90 days of your start date:</p>

                    <ol>
                        <li>Complete All of the core required activities</li>
                    </ol>

                    <p>Employees meeting all of the requirements below who enrolled in a 2016-2017 UCM Care
                        Network medical plan will receive a per-pay-period credit on their health insurance
                        premium (up to $300 annually). Employees meeting the criteria that have elected not to
                        enroll in a UCM Care Network medical plan, plan, or are ineligible for benefits, will receive
                        a free gift.
                        (<a href="http://www.hallmarkbusinessconnections.com/merchant-list">Click here to view choices</a>)
                    </p>

                <?php else : ?>

                    <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>,</p>

                    <p>Welcome to your summary page for the UCMC 2016-2017 Well Rewards benefit. To receive the
                        incentives, eligible employees MUST take action and meet <strong>ALL</strong> requirements
                        below by June 15, 2016:</p>

                    <ol>
                        <li>Complete ALL of the core required;  AND</li>
                        <li>Earn 100 wellness points from key screening results and health actions taken.</li>
                    </ol>

                    <p>
                        Employees meeting all of the requirements below who enrolled in any 2016-2017 UCM Care
                        Network medical plan will receive a per-pay-period credit on their health insurance premium
                        (up to $400 annually). Employees meeting the criteria that have elected not to enroll
                        in a 2016 UCM Care Network medical plan, or are ineligible for benefits, will receive a $50* gift card
                        to a merchant of your choice. (Click here to
                        <a href="http://www.hallmarkbusinessconnections.com/merchant-list">view choices</a>)
                    </p>

                    <p>
                        <strong>Points Advantage</strong> is an opportunity to earn more by participating in healthy activities.
                        Click link below for more details.
                    </p>

                <?php endif ?>
            </div>
        </div>

        <div style="clear:both"></div>

        <div class="pageHeading">
            <a href="/content/<?php echo $isNewHire ? '1094nh' : '1094carenet2016' ?>">Click here for all details about the 2016-2017 Well Rewards benefit.</a>
            <a href="/content/<?php echo $isNewHire ? '1094nh' : '1094carenet2016' ?>#faqs">FAQ Page</a>
        </div>
        <div style="color:#FF0000;text-align:center">
            Click on any item below for more details
        </div>

        <div>
            <strong>Status Updates:</strong> To complete actions click on the links below. If the status did not
            change for an item you are working on, you may need to go back and enter missing information or entries
            to earn more points. The status for HRA and wellness screenings will not change until after your
            report is processed. Thanks you for your actions and patience!
        </div><br/>

        <div style="font-size: 9pt;">
            *All gift cards are subject to all federal and state taxes. The total value will appear on the recipients 2016 W2 as income.
        </div>

        <?php
    }
}
