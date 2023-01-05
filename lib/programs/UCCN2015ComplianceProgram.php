<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCCN2015ComplianceProgram extends ComplianceProgram
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
            $printer = new UCCN2015ComplianceProgramReportPrinter();
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
        $scrView->setAttribute('report_name_link', '/content/1094nh#annScreen1a');
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
        $hpaView->setAttribute('report_name_link', '/content/1094nh#hra1b');
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
        $updateView->setAttribute('report_name_link', '/content/1094nh#persContact1e');
        $fixLink($updateView->getLinks(), 'Confirm/Update Info');
        $core->addComplianceView($updateView);

        $tobFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobFree->setName('tobacco');
        $tobFree->setReportName('Be Tobacco Free or Complete Cessation Counseling');
        $tobFree->setAttribute('report_name_link', '/content/1094nh#tobacco1c');
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


        $reqLessons = new CompleteELearningLessonsComplianceView('2015-01-01', $coreEndDate, function(User $user) use($program) {
            if($program->isNewHire($user)) {
                return array(1283, 1339, 1341, 1360);
            } else {
                return array(1283, 1339, 1341);
            }
        });
        $reqLessons->setNumberRequired(3);
        $reqLessons->setName('required_elearning');
        $reqLessons->setReportName('Complete 3 Required e-Learning Lessons');
        $reqLessons->setAttribute('report_name_link', '/content/1094nh#eLearn1d');
        $reqLessons->emptyLinks();
        $reqLessons->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=ucmc_cn_2015_2016'));
        $core->addComplianceView($reqLessons);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $coreEndDate);
        $doctorView->setName('doctor');
        $doctorView->setReportName('Confirm Having a Primary Care Provider');
        $fixLink($doctorView->getLinks(), 'Confirm/Update Info');
        $doctorView->setAttribute('report_name_link', '/content/1094nh#pcp1f');
        $core->addComplianceView($doctorView);

        $this->addComplianceViewGroup($core);
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

class UCCN2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        parent::printReport($status);
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Have your screening results in the healthy zone:', '/content/1094new2015#biometrics2a'));

        $this->pageHeading = 'UCMC Employee Wellness Benefit: <br />2015-2016 Well Rewards Requirements (To-Do’s)';

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


            <?php if(!in_array($status->getUser()->insurancetype, UCMC2015ComplianceProgram::$ppoType)) :  ?>
            .view-nav {
                display:none;
            }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
            });
        </script>

        <div>
            <div style="width: 70%; float: left;">
                <p>Hello <?php echo $escaper->escapeHtml($status->getUser()->getFullName()) ?>.</p>

                <p>Welcome to your summary page for the UCMC 2015-2016 Well Rewards benefit.</p>

                <p>To receive the incentives, eligible employees MUST take action and meet <strong>ALL</strong> requirements
                    below by within 90 days of your start date.</p>

                <p>Employees meeting all of the requirements below who enrolled in a 2015-2016 UCM Care Network
                    medical plan will receive a per-pay-period credit on their health insurance premium
                    (up to $300 annually). </p>

                <p>Employees meeting the criteria that have elected not to enroll in a UCM Care
                    Network medical plan, plan, or are ineligible for benefits, will receive a free gift.
                    (<a href="http://www.eawardcenter.com/rb/0000226868/28en_US/">Click here to view choices</a>)</p>
            </div>
            <div style="width: 30%; float: left; background-color: #CCFFCC;">
                <div style="padding: 5px;">
                    <p><strong>Important:</strong>  This will be your ONLY opportunity to qualify of the
                        2015-16 medical plan premium credit or gift.</p>

                    <p>If for any reason you think you may enroll in a UCM Care Network medical benefit plan
                        (life event, change in benefit status, etc.)
                        <strong>please be sure to complete the these requirements within 90 days of your start date.</strong>
                    </p>
                </div>
            </div>
        </div>

        <div style="clear:both"></div>

        <div class="pageHeading">
            <a href="/content/1094nh">Click here for all details about the 2015-2016 Well Rewards benefit</a>
            <a href="/content/1094nh#ucmc_faqs">FAQ Page</a>
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
