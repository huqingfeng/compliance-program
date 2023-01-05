<?php
class Ministry2013TCOrHDLComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'tc_or_hdl';
    }

    public function getDefaultReportName()
    {
        return 'TC or HDL';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $totalCholesterol = new ComplyWithTotalCholesterolScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $totalCholesterol->setComplianceViewGroup($this->getComplianceViewGroup());
        $totalCholesterol->overrideTestRowData(null, null, null, null, 'E');
        $totalCholesterol->setMergeScreenings(true);

        $hdl = new ComplyWithHDLScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $hdl->setComplianceViewGroup($this->getComplianceViewGroup());
        $hdl->overrideTestRowData(null, null, null, null, 'E');
        $hdl->setMergeScreenings(true);

        $tcStatus = $totalCholesterol->getStatus($user);
        $hdlStatus = $hdl->getStatus($user);

        $tc = is_numeric($tcStatus->getComment()) && $tcStatus->getComment() ?
            $tcStatus->getComment() : null;

        $hdl = is_numeric($hdlStatus->getComment()) && $hdlStatus->getComment() ?
            $hdlStatus->getComment() : null;

        $hdlGood = $hdl !== null && $hdl > 60;


        $comment = $hdl === null && $tc === null ? 'No Screening' : ($hdlGood || $tc === null ? 'HDL: '.$hdl : 'TC: '.$tc);

        if(($tc !== null && $tc < 201) || $hdlGood) {
            $status = ComplianceStatus::COMPLIANT;
        } else if($tc !== null && ($tc >= 201 && $tc <= 240)) {
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
        }

        return new ComplianceViewStatus($this, $status, null, $comment);
    }
}

class Ministry2013BodyCompositionComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function between($number, $low, $high)
    {
        return $number >= $low && $number <= $high;
    }

    public function getDefaultName()
    {
        return 'body_composition';
    }

    public function getDefaultReportName()
    {
        return 'Body Composition';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatusSummary($status)
    {
        $activeUser = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        $userAge = $activeUser->getAge($this->getStartDate(), true);
        $userGender = $activeUser->getGender();

        if($userGender == Gender::MALE) {
            $whRatio2 = '<.9';
            $wa2 = '<31.5-39';
            $wa1 = '39.1-43';
            $wa0 = '>43';

            if($this->between($userAge, 0, 39)) {
                $bf2 = '7.1%-19%';
            } else if($this->between($userAge, 40, 59)) {
                $bf2 = '11.6%-22.7%';
            } else {
                $bf2 = '15.3%-23.5%';
            }
        } else {
            $whRatio2 = '<.8';
            $wa2 = '<28.5-35';
            $wa1 = '35.1-43';
            $wa0 = '>43';

            if($this->between($userAge, 0, 39)) {
                $bf2 = '14.5%-23.1%';
            } else if($this->between($userAge, 40, 59)) {
                $bf2 = '18.5%-30.1%';
            } else {
                $bf2 = '21.1%-30.9%';
            }
        }

        if($status == ComplianceStatus::COMPLIANT) {
            return "W/H Ratio: $whRatio2<br/>BF%: $bf2<br/>W/A Girth: $wa2";
        } else if($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return "W/A Girth: $wa1";
        } else if($status == ComplianceStatus::NOT_COMPLIANT) {
            return "W/A Girth: $wa0";
        }
    }

    public function getStatus(User $user)
    {
        $userAge = $user->getAge($this->getStartDate(), true);
        $userGender = $user->getGender();

        $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
            $user,
            new DateTime($this->getStartDate('Y-m-d')),
            new DateTime($this->getEndDate('Y-m-d')),
            array('require_complete' => false, 'merge' => true, 'fields' => array('waist_hip', 'waist', 'hips', 'bodyfat'))
        );

        if($screening && $screening['waist_hip']) {
            $whValue = (double) $screening['waist_hip'];
        } else if($screening && $screening['waist'] && $screening['hips']) {
            $whValue = (double) $screening['waist'] / (double) $screening['hips'];
        } else {
            $whValue = null;
        }

        $waValue = $screening && $screening['waist'] ? $screening['waist'] : null;

        $bfValue = $screening && $screening['bodyfat'] ? $screening['bodyfat'] : null;

        if($userGender == Gender::MALE) {
            if($whValue && $whValue < .9) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/H: '.$whValue);
            } else if($waValue && ($waValue <= 39)) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/A: '.$waValue);
            } else if($this->between($userAge, 0, 39) && $bfValue && ($this->between($bfValue, 7.1, 19))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($this->between($userAge, 40, 59) && $bfValue && ($this->between($bfValue, 11.6, 22.7))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($userAge >= 60 && $bfValue && ($this->between($bfValue, 15.3, 23.5))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($waValue && $this->between($waValue, 39.1, 43)) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'W/A: '.$waValue);
            } else {
                return new ComplianceViewStatus(
                    $this,
                    ComplianceStatus::NOT_COMPLIANT,
                    null,
                    $waValue ? 'W/A: '.$waValue : ($bfValue ? 'BF: '.$bfValue : ($whValue ? 'W/H: '.$whValue : 'No Screening'))
                );
            }
        } else {
            if($whValue && $whValue < .8) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/H: '.$whValue);
            } else if($waValue && ($waValue <= 35)) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/A: '.$waValue);
            } else if($this->between($userAge, 0, 39) && $bfValue && ($this->between($bfValue, 14.5, 23.1))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($this->between($userAge, 40, 59) && $bfValue && ($this->between($bfValue, 18.5, 30.1))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($userAge >= 60 && $bfValue && ($this->between($bfValue, 21.1, 30.9))) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'BF: '.$bfValue);
            } else if($waValue && $this->between($waValue, 35.1, 43)) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'W/A: '.$waValue);
            } else {
                return new ComplianceViewStatus(
                    $this,
                    ComplianceStatus::NOT_COMPLIANT,
                    null,
                    $waValue ? 'W/A: '.$waValue : ($bfValue ? 'BF: '.$bfValue : ($whValue ? 'W/H: '.$whValue : 'No Screening'))
                );
            }
        }
    }
}

class MinistryHealth2013Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function  __construct()
    {
        $this->setShowNA(false);
        $this->setPageHeading('My Incentive Report Card for 2013 Premium Rewards');
        $this->setShowLegend(true);
        $this->setShowMaxPoints(false);
        $this->setDoColor(false);
    }

    public function printClientMessage()
    {
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Sitemap'));
        $user = sfContext::getInstance()->getUser()->getUser();
        ?>
    <p>
        <a href="/content/802"><?php echo get_sitemap_title('You have %USER_UNREAD_MESSAGES_TEXT%') ?></a>
    </p>

    <p>
    <a href="/compliance_programs?id=97">
        View your 2012 Program
    </a>
    </p>
    <p>
    <a href="/resources/4212/2013AHealthierYouProgramFAQsJan2013.021413.pdf">
        Frequently Asked Questions
    </a>
    </p>



    </p>
    <?php
    }

    public function printCSS()
    {
        parent::printCSS();

        $status = $this->status;

        $hraStatus = $status->getComplianceViewGroupStatus('required')->getComplianceViewStatus('hra');

        if(!$hraStatus->isCompliant()) {
            $status->getComplianceViewGroupStatus('required')->setComment('Until HRA is done, no points count');
        }
        ?>
    <style type="text/css">
        .status-1 .your_points, .status-3 .your_points {
            background-color:red;
            color:#FFF;
        }

        .status-2 .your_points {
            background-color:yellow;
            color:#000;
        }

        .status-4 .your_points {
            background-color:green;
            color:#FFF;
        }

            <?php if(!$hraStatus->isCompliant()) : ?>
        .total_points {
            background-color:red;
            color:#FFF;
        }
            <?php endif ?>

        .phipTable tr.newViewRow, .phipTable tr.totalRow {
            border-top:8px solid #D7D7D7 !important;
        }

        #legendEntry3 {
            display:none;
        }
    </style>
    <?php
    }

    public function printClientNote()
    {
        $user = sfContext::getInstance()->getUser()->getUser();

        ?>

    <?php if($user->relationship_type == Relationship::SPOUSE) : ?>
    <!-- Spouse Text follows -->
    <br/>
    <p><strong>What Do I Need to Do in 2012 to Earn Program Rewards in 2013?</strong></p>
    <ol>
        <li>Complete a Health Risk Assessment (HRA) between July 1, 2012 and September 30, 2012.</li>
        <li>
            Complete a biometric screening between October 1, 2011 and September 30, 2012:
            <ul>
                <li>Submit the biometric screenings completed by your health care provider's office directly to Circle
                    Wellness. (For a biometric form to take to the doctor,
                    <a href="/resources/3632/SpouseBiometricForm112311.pdf">click here);</a> and,
                </li>
            </ul>
        </li>
        <li>Meet at least 75% of the health standards outlined in the table above, for a total of 12 out of a potential
            16 points.
        </li>
    </ol>
    <br/>
    <p><strong>What are the 2013 "A Healthier You" Rewards?</strong></p>
    <ul>
        <li>
            <strong>Employee Reward</strong> - Ministry Medical Plan participants, who complete the HRA and meet a
            minimum points requirement of 12 as outlined in the points table above, will
            receive a contribution toward their 2013 medical plan premium contribution.
        </li>
        <li>
            <strong>Spouse Reward</strong> - If a spouse enrolled in a Ministry Medical plan, completes the HRA and
            meets
            a minimum points requirement of 12 as outlined in the points table above, the employee will receive a
            separate/ additional
            contribution towards their 2013 medical plan premium contribution.
        </li>
    </ul>
    <br/>

    <?php else : ?>
    <br/>
    <p><strong>What Do I Need to Do in 2012 to Earn Program Rewards in 2013?</strong></p>
    <ol>
        <li>Complete a Health Risk Assessment (HRA) between July 1, 2012 and September 30, 2012.</li>
        <li>
            Complete a biometric screening between October 1, 2011 and September 30, 2012:
            <ul>
                <li>During your birth month at your annual Employee Health (EH) assessment, or</li>
                <li>At your health care providers office (results submitted to EH), and</li>
            </ul>
        </li>
        <li>Meet at least 75% of the health standards outlined in the table above, for a total of 12 out of a potential
            16 points.
        </li>
    </ol>
    <br/>
    <p><strong>What are the 2013 "A Healthier You" Rewards?</strong></p>
    <ul>
        <li>
            <strong>Employee Reward</strong> - Ministry Medical Plan participants, who complete the HRA and meet a
            minimum points requirement of 12 as outlined in the points table above, will
            receive a contribution toward their 2013 medical plan premium contribution.
        </li>
        <li>
            <strong>Spouse Reward</strong> - If your spouse, enrolled in a Ministry Medical plan:
            <ol>
                <li>completes the Circle Wellness HRA available between July 1, 2012 and September 30, 2012;</li>
                <li>submits their biometric form (completed by their health care provider's office); and,</li>
                <li>achieves at least 12 of the 16 points outlined above, you will receive a separate contribution
                    towards your 2013 medical plan premium contribution. For a biometric form to take to the doctor,
                    <a href="/resources/3632/SpouseBiometricForm112311.pdf">click here.</a>
                </li>
            </ol>
        </li>
    </ul>
    <br/>
    <p>
        * Non Health Plan Participants - There are no financial
        rewards for non-medical plan participants, however we encourage all benefit
        eligible employees to take advantage of the <i>A Healthier You</i> HRA web tool
        and have their biometric screenings completed free of charge at their
        annual Employee Health assessment.
    </p>

    <?php endif ?>
    <?php
    }
}

class MinistryHealth2013ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('job_title', function (User $user) {
            return $user->getJobTitle();
        });

        $printer->addCallbackField('employee-health-homebase', function (User $user) {
            return (string) $user->getUserAdditionalFieldBySlug('employee-health-homebase');
        });

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        $printer->addCallbackField('newest_screening_date', function (User $user) {
            $screening = ScreeningQuery::createQuery('s')
                ->forUser($user)
                ->orderBy('s.date DESC')
                ->limit(1)
                ->fetchOne();

            if($screening) {
                return $screening->getDateTimeObject('date')->format('m/d/Y');
            } else {
                return '';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new MinistryHealth2013Printer();
        $printer->setShowNA(true);
        $printer->showResult(true);
        $printer->setTargetHeader('Requirements / Results');

        return $printer;
    }

    public function preMapEvaluate(ComplianceViewStatus $status, User $user)
    {
        $view = $status->getComplianceView();

        if($user->relationship_type == Relationship::SPOUSE &&
            $view->getName() == 'any_prevention'
        ) {

            $view->addLink(new Link('Instructions', '/content/preventive_exam'));
        }

        $hasComment = ($comment = $status->getComment()) !== null &&
            trim($comment) &&
            $comment != ComplyWithScreeningTestComplianceView::NO_SCREENING_TEXT &&
            $comment != ComplyWithScreeningTestComplianceView::TEST_NOT_TAKEN_TEXT &&
            $comment != 'HRA Not Taken';

        if($user->relationship_type == Relationship::SPOUSE &&
            ($alias = $view->getAttribute('assigned_alias'))
        ) {

            $learn = new CompleteELearningGroupSet(
                $this->getStartDate(),
                $this->getEndDate(),
                $alias
            );

            if($linkUrl = $view->getAttribute('spouse_url')) {
                $learnLink = new Link('Alternative', $linkUrl);

                $learn->emptyLinks();
                $learn->addLink($learnLink);
            } else {
                $learnLink = current($learn->getLinks());
                $learnLink->setLinkText('Alternative');
            }

            if($hasComment && !$status->isCompliant()) {
                $view->setAlternativeComplianceView($learn, false, array($this, 'alternativeViewUsed'));
            } else {
                $view->setAlternativeComplianceView(null);
            }
        } else if($user->relationship_type == Relationship::EMPLOYEE &&
            ($alias = $view->getAttribute('ee_assigned_alias'))
        ) {

            if($hasComment && !$status->isCompliant()) {
            //    $view->addLink(new Link('Alternative', '/content/alternativestandard'));
            }

            $elearn = new CompleteAssignedELearningLessonsComplianceView(
                $this->getStartDate(),
                $this->getEndDate()
            );

            $learnLink = current($elearn->getLinks());
            $learnLink->setLinkText('Alternatives Assigned');

            $elearn->bindAlias($alias);

            $assignedLessons = $elearn->getEligibleLessonIDs($user);

            if(count($assignedLessons) && !$status->isCompliant()) {
                $view->setAlternativeComplianceView($elearn, false, array($this, 'alternativeViewUsed'));
            } else {
                $view->setAlternativeComplianceView(null);
            }
        }
    }

    public function alternativeViewUsed(ComplianceViewStatus $status)
    {
        $status->setComment(sprintf('(Alternative Used) %s', $status->getComment()));
    }

    public function loadGroups()
    {

        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Met Standard', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partial Credit', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('No Points/Not Complete', '/images/ministryhealth/redblock1.jpg')
        )));


        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        $mapper = new ComplianceStatusPointMapper(2, 1, 0, 0);

        $screeningStart = '2011-10-01';
        $screeningEnd = '2012-09-30';

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView('2012-07-01', '2012-09-30');
        $hraView->emptyLinks();
        $hraView->setShowDateTaken(false);
        $hraView->addLink(new Link('Complete', '/content/989'));
        $hraView->setReportName('Health Risk Assessment');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, sprintf('Complete between %s - %s', '07/01/2012', $this->getEndDate('m/d/Y')));
        $hraView->setName('hra');
        $hraView->setDefaultComment('HRA Not Taken');

        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $pointsGroup = new ComplianceViewGroup('points', 'Points');
        $pointsGroup->setPointsRequiredForCompliance(12);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView('2012-07-01', '2012-09-30');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $smokingView->setReportName('Tobacco Use');
        $smokingView->setName('smoking');
        $smokingView->setAttribute('ee_assigned_alias', 'tobacco');
        $smokingView->setAttribute('spouse_url', '/content/12088?print_course_id[]=2740');
        $smokingView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $physicalView = new CompleteAnyPreventionComplianceView($programStart, $programEnd);
        $physicalView->setReportName('Preventive Care Visit');
        $physicalView->setName('any_prevention');
        $physicalView->bindAlias('ministry_2013');
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $physicalView->setPreMapCallback(array($this, 'preMapEvaluate'));


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bloodPressureView->setComplianceStatusPointMapper($mapper);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 119, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 79, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '<120/80');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120-140/80-90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>140/>90');
        // $bloodPressureView->addLink($altStandardLink);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setAttribute('ee_assigned_alias', 'blood_pressure');
        $bloodPressureView->setAttribute('assigned_alias', 'auto_bp');
        $bloodPressureView->setAttribute('spouse_url', '/content/bp_elearning_alternative');
        $bloodPressureView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStart, $screeningEnd);
        $glucoseView->setComplianceStatusPointMapper($mapper);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->overrideTestRowData(null, null, 99, 125);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100');
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>125');
        // $glucoseView->addLink($altStandardLink);
        $glucoseView->setMergeScreenings(true);
        $glucoseView->setAttribute('ee_assigned_alias', 'glucose');
        $glucoseView->setAttribute('assigned_alias', 'auto_diabetes');
        $glucoseView->setAttribute('spouse_url', '/content/diabetes_elearning_alternative');
        $glucoseView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $tcOrHdlView = new Ministry2013TCOrHDLComplianceView($screeningStart, $screeningEnd);
        $tcOrHdlView->setComplianceStatusPointMapper($mapper);
        $tcOrHdlView->setName('tc_or_hdl');
        $tcOrHdlView->setReportName('Total Cholesterol <u>OR</u> HDL');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'TC: <201, HDL: > 60');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'TC: 201-240');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'TC: >240');
        //$tcOrHdlView->addLink($altStandardLink);
        $tcOrHdlView->setAttribute('ee_assigned_alias', 'tc_hdl');
        $tcOrHdlView->setAttribute('assigned_alias', 'auto_cholesterol');
        $tcOrHdlView->setAttribute('spouse_url', '/content/chol_elearning_alternative');
        $tcOrHdlView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStart, $screeningEnd);
        $trigView->setComplianceStatusPointMapper($mapper);
        $trigView->setReportName('Triglycerides');
        $trigView->setName('triglycerides');
        $trigView->overrideTestRowData(null, null, 150, 200);
        $trigView->setStatusSummary(ComplianceStatus::COMPLIANT, '<151');
        $trigView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '151-200');
        $trigView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>200');
        // $trigView->addLink($altStandardLink);
        $trigView->setMergeScreenings(true);
        $trigView->setAttribute('ee_assigned_alias', 'trig');
        $trigView->setAttribute('assigned_alias', 'auto_trig');
        $trigView->setAttribute('spouse_url', '/content/trig_elearning_alternative');
        $trigView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $hdlView->setName('hdl');
        $hdlView->setReportName('HDL');
        $hdlView->overrideTestRowData(null, 40, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50, null, null, 'F');
        //$hdlView->addLink($altStandardLink);
        $hdlView->setMergeScreenings(true);
        $hdlView->setAttribute('ee_assigned_alias', 'hdl');
        $hdlView->setAttribute('assigned_alias', 'auto_hdl');
        $hdlView->setAttribute('spouse_url', '/content/hdl_elearning_alternative');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $ldlView->setComplianceStatusPointMapper($mapper);
        $ldlView->setName('ldl');
        $ldlView->setReportName('LDL');
        $ldlView->overrideTestRowData(null, null, 129, 159);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100-129');
        $ldlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '130-159');
        $ldlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '&ge; 160');
        // $ldlView->addLink($altStandardLink);
        $ldlView->setMergeScreenings(true);
        $ldlView->setAttribute('ee_assigned_alias', 'ldl');
        $ldlView->setAttribute('assigned_alias', 'auto_ldl');
        $ldlView->setAttribute('spouse_url', '/content/ldl_elearning_alternative');
        $ldlView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $bodyComposition = new Ministry2013BodyCompositionComplianceView($screeningStart, $screeningEnd);
        $bodyComposition->setComplianceStatusPointMapper($mapper);
        $bodyComposition->setName('body_composition');
        $bodyComposition->setReportName('Body Composition<div style="text-align:center"><br/><br/>Waist/Hip Ratio<br/><u>OR</u><br/>Body Fat %<br/><u>OR</u><br/>Waist/Abdominal Girth</div>');
        $bodyComposition->setStatusSummary(ComplianceStatus::COMPLIANT, 'W/H Ratio: <0.8, Body Fat: *, W/A Girth: <28.5-35');
        $bodyComposition->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'W/A Girth: 35.1-43');
        $bodyComposition->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'W/A: >43');
        // $bodyComposition->addLink($altStandardLink);
        $bodyComposition->setAttribute('ee_assigned_alias', 'body_composition');
        $bodyComposition->setAttribute('assigned_alias', 'auto_body_comp');
        $bodyComposition->setAttribute('spouse_url', '/content/bc_elearning_alternative');
        $bodyComposition->setPreMapCallback(array($this, 'preMapEvaluate'));

        $pointsGroup
            ->addComplianceView($physicalView)
            ->addComplianceView($smokingView)
            ->addComplianceView($bloodPressureView)
            ->addComplianceView($glucoseView)
            ->addComplianceView($tcOrHdlView)
            ->addComplianceView($trigView)
            ->addComplianceView($hdlView)
            ->addComplianceView($ldlView)
            ->addComplianceView($bodyComposition);

        $this->addComplianceViewGroup($pointsGroup);
    }
}