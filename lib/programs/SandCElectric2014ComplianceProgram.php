<?php

class SandCElectric2014ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2014', 'T2 - Lifestyle');

            $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

            return $track;
        }
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $req = new ComplianceViewGroup('core', 'Requirements');

        $ampSignup = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $ampSignup->setName('amp_signup');
        $ampSignup->setReportName('AMP UP! Sign Up! Card');
        $ampSignup->setAttribute('deadline', '7/31/14');
        $ampSignup->setAttribute('report_name_link', '/content/1094#1asignup');
        $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/4919/05-14-SignUp-2014card-FILLABLE.060914.PDF'));
        $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($ampSignup);
        $this->addComplianceViewGroup($req);

        $screening = new CompleteScreeningComplianceView($startDate, '2014-07-31');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '7/31/14');
        $screening->setReportName('Complete Biometric Screening');
        $screening->setAttribute('report_name_link', '/content/1094#1bscreen');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/7'));
        $screening->addLink(new Link('Results', '/content/989'));

        $req->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, '2014-09-30');
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->setAttribute('deadline', 'Before Health Action Call');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->setAttribute('report_name_link', '/content/1094#1chra');
        $hra->addLink(new Link('Take HRA/See Results', '/content/989'));
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($hra);

        $coachView = $this->getTempView(
            'coach',
            'Complete 1 Health Action Call',
            '8/15/14',
            array(new Link('Call to Schedule', '/content/1094#1dhacall'))
        );
        $coachView->setAttribute('report_name_link', '/content/1094#1dhacall');
        $req->addComplianceView($coachView);

        $scrView = $this->getTempView(
            'age_appropriate_screening',
            'Complete 1 Age-Appropriate Screening',
            '9/30/14',
            array(new Link('Get Form', '/content/1094#1eagescreen'))
        );
        $scrView->setAttribute('report_name_link', '/content/1094#1eagescreen');
        $req->addComplianceView($scrView);

        $physView = $this->getTempView(
            'physical',
            'Complete 1 Annual Physical',
            '9/30/14',
            array(new Link('Get Form', '/content/1094#1fannphys'))
        );
        $physView->setAttribute('report_name_link', '/content/1094#1fannphys');
        $req->addComplianceView($physView);

        $altView = $this->getTempView(
            'smart_alternative',
            'Complete Smart Alternative Activities (from 2)',
            '9/30/14',
            array(new Link('See Options 2A-G below', '/content/1094#1gsmartalt'))
        );
        $altView->setAttribute('report_name_link', '/content/1094#1gsmartalt');
        $req->addComplianceView($altView);

        $lifestyleView = $this->getTempView(
            'hap_lifestyle',
            'Health Action Plan: Complete Lifestyle Health Calls (from 2B)',
            '9/30/14',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $lifestyleView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($lifestyleView);

        $nurseView = $this->getTempView(
            'hap_nurse',
            'Health Action Plan: Work With Nurse Care Manager (calls/visits) Make Progress with Goals Over Specified Time',
            '9/30/14',
            array(new Link('Learn More', '#'), new Link('See Goals', 'content/1094#1hhealthplan'))
        );
        $nurseView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($nurseView);

        $altGroup = new ComplianceViewGroup('smart', 'Smart Alternative Activities');

        $smartU = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $smartU->setName('lesson');
        $smartU->setReportName('Smart U - Complete at least 1 eLearning lesson');
        $smartU->setAttribute('deadline', '9/30/14');
        $smartU->setAttribute('report_name_link', '/content/1094#2aelearn');
        $smartU->setPointsPerLesson(1);
        $altGroup->addComplianceView($smartU);

        $callsView = $this->getTempView(
            'calls',
            'Smart U - Participate in Lifestyle Health Calls',
            '9/30/14',
            array(new Link('Call to Schedule', '/content/1094#2bhealthcall'))
        );
        $callsView->setAttribute('report_name_link', '/content/1094#2bhealthcall');
        $altGroup->addComplianceView($callsView);

        $lunchView = $this->getTempView(
            'lunch',
            'Smart U - Attend Lunch \'n\' Learns',
            '9/30/14',
            array(new Link('See Topics &amp; Calendar', '/content/1094#2elnl'))
        );
        $lunchView->setAttribute('report_name_link', '/content/1094#2elnl');
        $altGroup->addComplianceView($lunchView);

        $weightView = $this->getTempView(
            'weight',
            'Smart Health - Join &amp; Participate in onsite Weight Watchers',
            '9/30/14',
            array(new Link('Get Info or Sign Up', '/content/1094#2cww'))
        );
        $weightView->setAttribute('report_name_link', '/content/1094#2cww');
        $altGroup->addComplianceView($weightView);





        $dentalView = $this->getTempView(
            'dental',
            'Smart Health - Complete a Dental Exam or Cleaning',
            '9/30/14',
            array(new Link('Get Form', '/content/1094#2fdental'))
        );
        $dentalView->setAttribute('report_name_link', '/content/1094#2fdental');
        $altGroup->addComplianceView($dentalView);

        $fitnessView = $this->getTempView(
            'fitness',
            'Smart Move - Complete a Fitness Assessment',
            '9/30/14',
            array(new Link('Call to Schedule', '/content/1094#2gfitness'))
        );
        $fitnessView->setAttribute('report_name_link', '/content/1094#2gfitness');
        $altGroup->addComplianceView($fitnessView);

        $moveView = $this->getTempView(
            'move',
            'Participate in 2 Smart Challenges',
            '9/30/14',
            array(new Link('See Topics &amp; Calendar', '/content/1094#2dchallenge'))
        );
        $moveView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($moveView);


        $this->addComplianceViewGroup($altGroup);

        $settingsGroup = new ComplianceViewGroup('settings', 'Settings');

        $asOfDate = new PlaceHolderComplianceView();
        $asOfDate->setName('as_of_date');
        $asOfDate->setReportName('As Of Date');
        $settingsGroup->addComplianceView($asOfDate);

        $this->addComplianceViewGroup($settingsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SandCElectric2014ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter() {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, true, false, true);

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        return $printer;
    }

    public function setActiveUser(User $user = null)
    {
        parent::setActiveUser($user);

        $smartGroupName = 'smart';

        if($smartGroup = $this->getComplianceViewGroup($smartGroupName)) {
            if($user === null) {
                $smartGroup->setNumberOfViewsRequired(null);
            } else {
                $smartGroup->setNumberOfViewsRequired(
                    $this->getMinimumRequired($user, $smartGroupName)
                );
            }
        }

        return $this;
    }

    public function getMinimumRequired(User $user, $groupName)
    {
        return $this->getMinimum($user, "{$groupName}_required");
    }

    public function getMinimum(User $user, $viewName)
    {
        $minimums = array(
            'amp_signup' => 1,
            'screening' => 1,
            'hra' => 1,
            'coach' => 1,
            'age_appropriate_screening' => 1,
            'physical' => 1,
            'smart_alternative' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 2,
                'T2 - Lifestyle' => 2,
                'T3B - Care Management Alternative' => 3,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 4,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0
            ),
            'hap_lifestyle' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2B - Lifestyle Alternative' => $this->hideMarker,
                'T2 - Lifestyle' => 4,
                'T3B - Care Management Alternative' => 6,
                'T3 - Care Management' => $this->hideMarker,
                'T4B - Integrated Care Management Alternative' => 12,
                'T4 - Integrated Care Management Count' => $this->hideMarker,
                'T4 - Integrated Care Management' => $this->hideMarker
            ),
            'hap_nurse' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2B - Lifestyle Alternative' => $this->hideMarker,
                'T2 - Lifestyle' => $this->hideMarker,
                'T3B - Care Management Alternative' => $this->hideMarker,
                'T3 - Care Management' => 1,
                'T4B - Integrated Care Management Alternative' => $this->hideMarker,
                'T4 - Integrated Care Management Count' => 1,
                'T4 - Integrated Care Management' => 1,
            ),
            'lesson' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 1,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 1,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 1,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'calls' => array(
                'T1 - Maintenance' => 6,
                'T2B - Lifestyle Alternative' => 6,
                'T2 - Lifestyle' => 4,
                'T3B - Care Management Alternative' => 6,
                'T3 - Care Management' => $this->hideMarker,
                'T4B - Integrated Care Management Alternative' => 12,
                'T4 - Integrated Care Management Count' => $this->hideMarker,
                'T4 - Integrated Care Management' => $this->hideMarker,
            ),
            'weight' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 1,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 1,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 1,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'move' => array(
                'T1 - Maintenance' => 2,
                'T2B - Lifestyle Alternative' => 2,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 2,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 2,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'lunch' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 1,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 1,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 1,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'dental' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 1,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 1,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 1,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'fitness' => array(
                'T1 - Maintenance' => 1,
                'T2B - Lifestyle Alternative' => 1,
                'T2 - Lifestyle' => 0,
                'T3B - Care Management Alternative' => 1,
                'T3 - Care Management' => 0,
                'T4B - Integrated Care Management Alternative' => 1,
                'T4 - Integrated Care Management Count' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'core_required' => array(
                'T1 - Maintenance' => 'all',
                'T2B - Lifestyle Alternative' => 'all',
                'T2 - Lifestyle' => 'all',
                'T3B - Care Management Alternative' => 'all',
                'T3 - Care Management' => 'all',
                'T4B - Integrated Care Management Alternative' => 'all',
                'T4 - Integrated Care Management Count' => 'all',
                'T4 - Integrated Care Management' => 'all',
            )
        );

        $minimums['smart_required'] = $minimums['smart_alternative'];

        $track = trim($this->getTrack($user));

        if(isset($minimums[$viewName])) {
            if(is_array($minimums[$viewName])) {
                if(isset($minimums[$viewName][$track])) {
                    return $minimums[$viewName][$track];
                } else {
                    return '';
                }
            } else {
                return $minimums[$viewName];
            }
        } else {
            return '';
        }
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $firstPass = true;

        beginPass:

        $user = $status->getUser();
        $track = trim($this->getTrack($user));
        
        $lifeStyleStatus = $status->getComplianceViewStatus('hap_lifestyle');
        $callsView = $status->getComplianceViewStatus('calls');
        $smartAlternativeStatus = $status->getComplianceViewStatus('smart_alternative');
        
        $lifeStyleStatus->setPoints($callsView->getPoints());

        if($track == 'T2 - Lifestyle') {            
            if($smartAlternativeStatus->isCompliant()
                    && $callsView->getPoints() >= 1) {
                $lifeStyleStatus->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        } 
        
        $groupsCompliant = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            if($groupStatus
                    ->getComplianceViewGroup()
                    ->getName() == 'settings') {

                continue;
            }

            $numberDone = 0;

            $allCompliant = true;

            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($firstPass) {
                    $viewStatus->setPoints((int)$viewStatus->getPoints());
                }

                $view = $viewStatus->getComplianceView();

                $minimumRequired = $this->getMinimum($user, $view->getName());
                
                if($view->getName() == 'lunch' 
                        || $view->getName() == 'move'
                        || $view->getName() == 'lesson') {
                    $numberDone += $viewStatus->getPoints();
                }

                if((string)$minimumRequired != $this->hideMarker) {
                    if($viewStatus->getPoints() >= $minimumRequired) {
                        $light = $minimumRequired > 0 || $viewStatus->getPoints() > 0 ?
                            ComplianceStatus::COMPLIANT :
                            ComplianceStatus::NA_COMPLIANT;

                        if($firstPass) {
                            $viewStatus->setStatus($light);
                        }

                        if($light == ComplianceStatus::COMPLIANT 
                                && $view->getName() != 'lunch'
                                && $view->getName() != 'move'
                                && $view->getName() != 'lesson') {
                            $numberDone++;
                        }

                    } elseif($firstPass && $viewStatus->getPoints() > 0) {
                        $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                    } elseif($firstPass) {
                        $viewStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    }
                } elseif($firstPass) {
                    $viewStatus->setStatus(ComplianceStatus::NA_COMPLIANT);
                }

                if(!$viewStatus->isCompliant()) {
                    $track = trim($this->getTrack($user));
                    if($track == 'T2 - Lifestyle') {
                        if($view->getName() != 'smart_alternative' && $view->getName() != 'hap_lifestyle') {                            
                            $allCompliant = false;
                        }
                    } else {
                        $allCompliant = false;
                    }
                }
            }
            
            if($track == 'T2 - Lifestyle') {
                if(!$status->getComplianceViewStatus('smart_alternative')->isCompliant()
                        && !$status->getComplianceViewStatus ('hap_lifestyle')->isCompliant()) {
                    $allCompliant = false;
                }
            }
            
            $groupStatus->setAttribute('number_done', $numberDone);

            $groupMinimumRequired = $this->getMinimumRequired(
                $user, $groupStatus->getComplianceViewGroup()->getName()
            );

            if($groupMinimumRequired == 'all') {
                if($allCompliant) {
                    $groupStatus->setStatus(ComplianceStatus::COMPLIANT);
                } elseif($numberDone > 0) {
                    $groupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                } else {
                    $groupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                }
            } elseif($numberDone >= $groupMinimumRequired) {
                $light = $groupMinimumRequired > 0 || $numberDone > 0 ?
                    ComplianceStatus::COMPLIANT :
                    ComplianceStatus::NA_COMPLIANT;

                $groupStatus->setStatus($light);

                $groupsCompliant++;
            } elseif($numberDone > 0) {
                $groupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            } else {
                $groupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        }

        if($groupsCompliant >= 2) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
                
        // We're supposed to copy 2 total over to 1G. But we don't know 2's
        // status until now, and 1G affects 1's status. So we copy it over and
        // call again if there's a change.

        $oneGStatus = $status->getComplianceViewStatus('smart_alternative');
        $twoStatus = $status->getComplianceViewGroupStatus('smart');

        if($twoStatus->isCompliant() && $firstPass) {
            $firstPass = false;

            $oneGStatus->setStatus($twoStatus->getStatus());

            goto beginPass;
        }
    }

    private function getTempView($name, $reportName, $deadline, array $links = array())
    {
        $ageAppropriate = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $ageAppropriate->setName($name);
        $ageAppropriate->setReportName($reportName);
        $ageAppropriate->setAttribute('deadline', $deadline);
        $ageAppropriate->setAllowPointsOverride(true);

        foreach($links as $link) {
            $ageAppropriate->addLink($link);
        }

        return $ageAppropriate;
    }

    private $hideMarker = '<span class="hide-view">hide</span>';
    private $lastTrack = null;
}

class SandCElectric2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function showGroup($group)
    {
        $groupName = $group->getName();

        if($groupName == 'smart') {
            $this->tableHeaders['completed'] = 'Count Completed';
        } else {
            $this->tableHeaders['completed'] = 'Date Done';
        }

        return $groupName != 'settings';
    }

    protected function getCompleted(ComplianceViewGroup $group,
                                    ComplianceViewStatus $viewStatus)
    {
        if($group->getName() == 'smart') {
            return $viewStatus->getPoints();
        } else {
            return $viewStatus->getComment();
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $program = $status->getComplianceProgram();

        $user = $status->getUser();

        $this->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            $default = $view instanceof DateBasedComplianceView ?
                $view->getEndDate('m/d/Y') : '';

            return $view->getAttribute('deadline', $default);
        });

        $this->addStatusCallbackColumn('Minimum Required', function (ComplianceViewStatus $status) use($program, $user) {
            $view = $status->getComplianceView();

            return $program->getMinimum($user, $view->getName());
        });

        $startDate = $status->getComplianceProgram()->getEndDate('F d, Y');

        $this->setShowLegend(true);
        $this->setShowTotal(false);
        $this->pageHeading = '2014 Wellness Initiative Program';

        $this->tableHeaders['links'] = 'Action Links';


        parent::printReport($status);
        ?>
        <br/>

        <p></p>
        <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $program = $status->getComplianceProgram();
        $user = $status->getUser();
        $asOfDate = $status->getComplianceViewStatus('as_of_date')->getComment();

        if(!$asOfDate) {
            $asOfDate = date('m/d/Y', strtotime(sfConfig::get('app_compliance_programs_sandc_as_of_date', date('Y-m-d'))));
        }

        ?>
        <style type="text/css">
            #overviewCriteria {
                width:100%;
                border-collapse:collapse;
            }

            #legendText {
                background-color:#90C4DE;
            }

            #overviewCriteria th {
                background-color:#42669A;
                color:#FFFFFF;
                font-weight:normal;
                font-size:11pt;
                padding:5px;
            }

            #overviewCriteria td {
                width:33.3%;
                vertical-align:top;
            }

            .phipTable .headerRow {
                background-color:#90C4DE;
                font-size:16px;
            }

            .phipTable {
                font-size:12px;
                margin-bottom:10px;
            }

            .phipTable th, .phipTable td {
                padding:1px 2px;
                border:1px solid #a0a0a0;
            }

            .phipTable .points {
                width:80px;
            }

            .phipTable .links {
                width:190px;
            }
        </style>

        <script type="text/javascript">
            $(function() {
               $('.headerRow-Smart td:eq(2)').html('Current Count');

                $('.view-hap_nurse').after(
                        '<tr>' +
                        '<td colspan="4" style="text-align:right"><strong>Your incentive requirements status as of <?php echo $asOfDate ?></strong>*</td>' +
                        '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('core')->getLight() ?>" class="light" /></td>' +
                        '<td id="section_complete_message"></td>' +
                        '</tr>'
                );

                <?php if($status->getComplianceViewGroupStatus('core')->isCompliant()) : ?>
                    $('#section_complete_message').html('You are eligible to receive the 2014 participation rewards beginning in 2015.');
                <?php endif ?>

                $('.view-move').after(
                    '<tr>' +
                    '<td colspan="2" style="text-align:right"><strong>Number of greens earned as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td style="text-align:center"><?php echo $program->getMinimumRequired($user, 'smart') ?></td>' +
                    '<td style="text-align:center"><?php echo $status->getComplianceViewGroupStatus('smart')->getAttribute('number_done') ?></td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('smart')->getLight() ?>" class="light" />' +
                    '<td></td>' +
                    '</tr>'
                );

                $('.phipTable tr span.hide-view').parent('td').parent('tr').hide();
            });
        </script>

        <div class="row">
            <div class="span6">
                <?php echo $status->getUser()->getFullName() ?>
            </div>
            <div class="span6">
                Track = <?php echo $status->getComplianceProgram()->getTrack($status->getUser()) ?>
            </div>
        </div>
    <p></p>
    <p style="color:red;margin-left:24px;">Note: Some actions you took within the past 30-60 days may not show
        until next month. Please allow 30-60 days for updates relying on
        claims (1E and 1F) and/or any required forms you have submitted.</p>
    <p>If you have any questions/concerns about your report card please contact the AMP UP! Help Desk 800-761-5856  (M-F from 8am-8pm CST)</p>

        <br/>
    <?php
    }
}
