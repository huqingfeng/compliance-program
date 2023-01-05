<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class UCMC2017ReportCardVisitHealthUComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }


    public function getDefaultName()
    {
        return;
    }


    public function getDefaultReportName()
    {
        return;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('ucmc_click_health_u');

        if($record->exists()) {
            if($record->clicked && date('Y-m-d', $this->startDate) <= $record->date && $record->date <= date('Y-m-d', $this->endDate)) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
    }
}

class UCMC2017ReportCardCompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {

    }

}


class UCMC2017ReportCardComplianceProgram extends ComplianceProgram
{
    const DISPLAY_DAYS = 90;
    const CALCULATE_DAYS = 120;
    const NEW_HIRE_DATE = '2016-05-01';
    const OLD_HIRE_DISPLAY_DATE = '2016-06-15';
    const OLD_HIRE_CALCULATE_DATE = '2016-06-22';
    const PPO_TYPE = 'BCBS PPO Plan';

    public function getAdminProgramReportPrinter()
    {
        return ;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $wms1Id = $_GET['wms1Id'];
        $wms2Id = $_GET['wms2Id'];
        $wms2Account = $_GET['wms2AccountId'];
        $relationship = function($relationship){
            if (substr($relationship, 0, 6) == "spouse") {
                return "spouse";
            } else {
                if ($_GET['view'] == "admin") {
                    return $_GET['relationship'];
                } else {
                    return "employee";
                }

            }
        };
        $token = "7bc9b94d-8a55-410c-bec8-4787d02c0e7b";

        $domain = $this->getDomain();
        
        $dir = isset($_GET['year']) && $_GET['year'] == 2018 ? 'ucmc2018' : 'ucmc';
        if ($_GET['view'] == "ajax") {
            $url = $domain."wms3/public/".$dir."?bypass=true&clientId=ucmc&token=7bc9b94d-8a55-410c-bec8-4787d02c0e7b&view=ajax&wms1Id=$wms1Id&action=".$_GET['action']."&value=".$_GET['value']."&category=".$_GET['category'];
            $url.= isset($_GET['year']) ? '&year='.$_GET['year'] : '';
            if(isset($_GET['dev'])) {
                $url = $domain."wms3/public/ucmc2018?bypass=true&clientId=ucmc&token=7bc9b94d-8a55-410c-bec8-4787d02c0e7b&view=ajax&wms1Id=$wms1Id&action=".$_GET['action']."&value=".$_GET['value']."&category=".$_GET['category'];
            }
        } else {
            if ($_GET['lastUpdated'] != "null") {
                $lastUpdated = substr($_GET['lastUpdated'], 0, 10);
            } else {
                $lastUpdated = 0;
            }

            $url = $domain."wms3/public/".$dir."?bypass=true&clientId=ucmc&token=".$token."&view=".$_GET['view']."&relationship=".$relationship($_GET['relationship'])."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&contactInfoUpdated=".$lastUpdated;
            $url.= isset($_GET['year']) ? '&year='.$_GET['year'] : '';
            $url.= isset($_GET['testing']) ? '&testing='.$_GET['testing'] : '';
            if(isset($_GET['dev'])) {
                $url = $domain."wms3/public/ucmc2018?bypass=true&clientId=ucmc&token=".$token."&view=".$_GET['view']."&relationship=".$relationship($_GET['relationship'])."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&contactInfoUpdated=".$lastUpdated;
            }
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;
        return ;
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
        // get user's data needing for redirect querystring back to wms3 page on wms2
        if (isset($_GET['loopback']) && $_GET['loopback'] == "wms3UCMCReportCard"){
            $url = "/compliance/ucmc-2016-2017/well-rewards/compliance_programs?id=1087";
            $url .= "&wms1Id=".$_GET['wms1Id'];
            $url .= "&wms2Id=".$_GET['wms2Id'];
            $url .= "&wms2AccountId=".$_GET['wms2AccountId'];
            $url .= "&view=".$_GET['view'];
            $url .= "&relationship=".$_GET['relationship'];

        } else {
            $url = "/compliance_programs?id=701";
        }

        $_SESSION['manua_override_fitbit_parameters'] = array(
            'activity_id' => '509',
            'question_id' => '110',
            'start_date' => '2016-03-14',
            'end_date' => '2016-06-12',
            'product_name'  => 'Total Steps',
            'header_text'  => '<p><a href="'.$url.'">Back to My Dashboard</a></p>',
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

        $scrView = new UCMC2017ReportCardCompleteScreeningComplianceView($coreStartDate, $coreEndDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('report_name_link', '/content/1094new2016#annScreen1a');
        $scrView->setAttribute('report_name_link_new_hire', '/content/1094nh#annScreen1a');
        $scrView->emptyLinks();
        $scrView->addLink(new Link('Schedule/Options', '/content/1094nh#annScreen1a'));
        $scrView->addLink(new Link('Results', '/wms2/compliance/ucmc-2016-2017/my-health'));
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
        $hpaView->setAttribute('report_name_link', '/content/1094new2016#hra1b');
        $hpaView->setAttribute('report_name_link_new_hire', '/content/1094nh#hra1b');
        $hpaView->emptyLinks();
        $hpaView->addLink(new Link('Complete HRA', '/wms2/compliance/ucmc-2016-2017/my-health'));
        $hpaView->addLink(new Link('Results', '/wms2/compliance/ucmc-2016-2017/my-health'));
        $core->addComplianceView($hpaView);

        $fixLink = function($links, $text) {
            $link = reset($links);

            $link->setLinktext($text);
        };

        $updateView = new UpdateContactInformationComplianceView($startDate, $coreEndDate);
        $updateView->setReportName('Enter/Confirm Key Contact Information (Email, Address, etc.)');
        $updateView->setAttribute('report_name_link', '/content/1094new2016#persContact1c');
        $updateView->setAttribute('report_name_link_new_hire', '/content/1094nh#persContact1e');
        $fixLink($updateView->getLinks(), 'Update/Confirm Info');
        $updateView->emptyLinks();
        $updateView->addLink(new Link('Enter/Update Info', '/wms2/profile/contact?redirect='.urlencode('/compliance/ucmc-2016-2017/well-rewards/compliance_programs')));
        $core->addComplianceView($updateView);

        $tobFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobFree->setName('tobacco');
        $tobFree->setReportName('Be Tobacco Free or Complete Cessation Counseling');
        $tobFree->setAttribute('report_name_link', '/content/1094new2016#tobacco1d');
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


        $reqLessons = new CompleteELearningGroupSet('2016-03-24', $coreEndDate, 'required_16-17');
//        $reqLessons = new CompleteELearningLessonsComplianceView('2016-03-24', $coreEndDate, function(User $user) use($program) {
//            if($program->isNewHire($user)) {
//                return array(1283, 1339, 1341, 1360);
//            } else {
//                return array(00000);
//            }
//        });
        $reqLessons->setNumberRequired(3);
        $reqLessons->setName('required_elearning');
        $reqLessons->setReportName('Complete 3 Required e-Learning Lessons');
        $reqLessons->setAttribute('report_name_link', '/content/1094new2016#eLearn1e');
        $reqLessons->setAttribute('report_name_link_new_hire', '/content/1094nh#eLearn1d');
        $reqLessons->emptyLinks();
        $reqLessons->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias[]=required_16-17'));
        $core->addComplianceView($reqLessons);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $coreEndDate);
        $doctorView->setName('doctor');
        $doctorView->setReportName('Have a Primary Care Provider');
        $fixLink($doctorView->getLinks(), 'Update/Confirm Info');
        $doctorView->setAttribute('report_name_link', '/content/1094new2016#pcp1f');
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
        $ha1cView->overrideTestRowData(0, 0, 5.699, 6.499);
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
        $bodyFatBMIView->overrideBodyFatTestRowData(6, 6, 17.999, 24.999, 'M');
        $bodyFatBMIView->overrideBodyFatTestRowData(14, 14, 24.999, 31.999, 'F');
        $numbers->addComplianceView($bodyFatBMIView);

        $prev = new CompleteArbitraryActivityComplianceView('2016-07-01', '2017-06-16', 26, 10);
        $prev->setMaximumNumberOfPoints(50);
        $prev->setReportName('Get Recommended Preventive Screenings/Exams');
        $numbers->addComplianceView($prev);

        $imm = new CompleteArbitraryActivityComplianceView('2016-07-01', '2017-06-16', 242, 10);
        $imm->setMaximumNumberOfPoints(30);
        $imm->setReportName('Get Flu Shot & Other Recommended Immunizations');
        $numbers->addComplianceView($imm);

        $physicalActivityView = new PhysicalActivityComplianceView('2017-01-01', '2017-06-16');
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
        $weight->addLink(new Link('Review Options', '/content/1094new2016#2hweight'));
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

        $donateBlood = new CompleteArbitraryActivityComplianceView('2016-07-01', '2017-06-16', 503, 30);
        $donateBlood->setMaximumNumberOfPoints(30);
        $donateBlood->setReportName('Donate Blood');
        $numbers->addComplianceView($donateBlood);

        $aha = new PlaceHolderComplianceView(null, 0);
        $aha->setName('aha');
        $aha->setMaximumNumberOfPoints(20);
        $aha->setReportName('AHA National Walking Day');
        $numbers->addComplianceView($aha);

        $fitness = new CompleteArbitraryActivityComplianceView('2016-07-01', '2017-06-16', 506, 20);
        $fitness->setMaximumNumberOfPoints(60);
        $fitness->setReportName('Self-Directed Fitness Activity');
        $numbers->addComplianceView($fitness);

        $cubicle = new PlaceHolderComplianceView(null, 0);
        $cubicle->setName('cubicle');
        $cubicle->setMaximumNumberOfPoints(30);
        $cubicle->setReportName('Cubicle to 5K');
        $numbers->addComplianceView($cubicle);

        $fitbitView = new UCMCWalkingCampaignFitbitComplianceView('2017-03-20', '2017-06-11', 70000, 5);
        $fitbitView->setReportName('Log 70,000 steps a week');
        $fitbitView->setMaximumNumberOfPoints(50);
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Sync Fitbit/View Steps <br />', '/content/ucan-fitbit-individual'));
        $fitbitView->addLink(new Link('Enter Steps Manually', '/content/12048?action=showActivity&activityidentifier=509'));
        $numbers->addComplianceView($fitbitView);

        $volunteer = new VolunteeringComplianceView('2016-07-01', '2016-06-16');
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
                if(in_array($shadowInsurancePlanType, UCMC2017ReportCardComplianceProgram::$historicalPpoType)) {
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

        $visitHealthU = new UCMC2017ReportCardVisitHealthUComplianceView($programStartDate, $programEndDate);
        $visitHealthU->setReportName('Visit TotalHealthU for benefit details, changes & decision');
        $visitHealthU->setName('visit_health_u');
        $visitHealthU->setMaximumNumberOfPoints(20);
        $visitHealthU->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $visitHealthU->addLink(new Link('Visit TotalHealthU', '/content/visit-health-u'));
        $numbers->addComplianceView($visitHealthU);

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

    public static $historicalPpoType = array(
        'BCBS PPO Plan',
        'BCBS Standard Plan',
        'PPO Premier',
        'PPO Standard',
        'PPO Advantage'
    );
}

class UCMC2017ReportCardComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
    }

    public function __construct()
    {
    }

    public function printHeader(ComplianceProgramStatus $status)
    {

    }
}

class UCMC2017ReportCardScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function setShowTobacco($bool)
    {
    }
    public function printReport(ComplianceProgramStatus $status)
    {
    }
}
