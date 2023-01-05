<?php
class Ministry2015TCOrHDLComplianceView extends DateBasedComplianceView
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

class Ministry2015BodyCompositionComplianceView extends DateBasedComplianceView
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
        } else {
            $whRatio2 = '<.8';
            $wa2 = '<28.5-35';
            $wa1 = '35.1-43';
            $wa0 = '>43';
        }

        if($status == ComplianceStatus::COMPLIANT) {
            return "W/H Ratio: $whRatio2<br/>W/A Girth: $wa2";
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

        if($userGender == Gender::MALE) {
            if($whValue && $whValue < .9) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/H: '.$whValue);
            } else if($waValue && ($waValue <= 39)) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/A: '.$waValue);
            } else if($waValue && $this->between($waValue, 39.1, 43)) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'W/A: '.$waValue);
            } else {
                return new ComplianceViewStatus(
                    $this,
                    ComplianceStatus::NOT_COMPLIANT,
                    null,
                    $waValue ? 'W/A: '.$waValue : ($whValue ? 'W/H: '.$whValue : 'No Screening')
                );
            }
        } else {
            if($whValue && $whValue < .8) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/H: '.$whValue);
            } else if($waValue && ($waValue <= 35)) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'W/A: '.$waValue);
            } else if($waValue && $this->between($waValue, 35.1, 43)) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, 'W/A: '.$waValue);
            } else {
                return new ComplianceViewStatus(
                    $this,
                    ComplianceStatus::NOT_COMPLIANT,
                    null,
                    $waValue ? 'W/A: '.$waValue : ($whValue ? 'W/H: '.$whValue : 'No Screening')
                );
            }
        }
    }
}

class MinistryHealth2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function  __construct()
    {
        $this->setShowNA(false);
        $this->setPageHeading('My Incentive Report Card for 2015 Medical Plan Discount');
        $this->setShowLegend(true);
        $this->setShowMaxPoints(false);
        $this->setDoColor(false);
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        ob_start();

        parent::printReport($status);

        $report = ob_get_clean();

        $replacements = array(
            '%HRA_END_DATE%' => $status->getComplianceViewStatus('hra')->getComplianceView()->getEndDate('m/d/Y'),
            '%HRA_END_DATE_LONG%' => $status->getComplianceViewStatus('hra')->getComplianceView()->getEndDate('F d, Y'),
            '%SCREENING_END_DATE%' => $status->getComplianceViewStatus('blood_pressure')->getComplianceView()->getEndDate('m/d/Y'),
            '%SCREENING_END_DATE_LONG%' => $status->getComplianceViewStatus('blood_pressure')->getComplianceView()->getEndDate('F d, Y'),
        );

        echo str_replace(array_keys($replacements), array_values($replacements), $report);
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
        <a href="/request_archive_collections/show?id=16">
            View your 2014 Program
        </a>
    </p>
    <p>
        <a href="/resources/4621/Medical-Plan-Discount-FAQ-NCW-2014.pdf">
            Frequently Asked Questions
        </a>
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

        $userIsSpouse = $this->status->getComplianceProgram()->userIsSpouse($user);

        ?>

    <?php if($userIsSpouse) : ?>
    <!-- Spouse Text follows -->
    <br/>
    <p><strong>What Do I Need to Do in 2014 to Earn the Medical Plan Discount in 2015?</strong></p>
    <ol>
        <li>Complete a Health Risk Assessment (HRA) between March 1, 2014 and %HRA_END_DATE_LONG%.</li>
        <li>
            Complete a <em>A Healthier You</em> screening between October 1, 2013 and %SCREENING_END_DATE_LONG%:
            <ul>
                <li>Submit the screenings completed by your health care provider's office directly to Circle Wellness by the 9/30/2014 deadline by <a href="/content/chp-document-uploader">clicking here.</a>.
                    For a biometric form to take to the doctor, <a href="/resources/4622/Office-Visit-Screening-Checklist-NCW-2014.pdf">click here)</a>; and,
                </li>

            </ul>
        </li>
        <li>Meet at least 75% of the health standards outlined in the table above, for a total of 12 out of a potential 16 points.
        </li>
    </ol>
    <br/>
    <p><strong>What are the 2015 Medical Plan Discounts?</strong></p>
    <ul>
        <li>
            <strong>Associates</strong> - Ministry Medical Plan participants, who complete the HRA and meet a
            minimum points requirement of 12 as outlined in the points table above, will receive a contribution toward their 2015 medical plan premium contribution. For a provider biometric screening checklist to take to the doctor,
        <a href="/resources/4927/Office-Visit-Screening-Checklist061914.pdf">click here.</a>(Prior year discount was $1020 annual)

        </li>
        <li>
            <strong>Spouse</strong> - If a spouse enrolled in a Ministry Medical plan, completes the HRA and meets a minimum point’s requirement of 12 as outlined in the points table above, the employee will receive a separate/ additional contribution towards their 2015 medical plan premium contribution. (Spouse prior year discount was $504 annual)
        </li>
    </ul>



    <br/>

    <?php else : ?>
    <br/>
    <p><strong>What Do I Need to Do in 2014 to Earn the Medical Plan Discount in 2015?</strong></p>
    <ol>
        <li>Complete the Health Risk Assessment (HRA) between March 1, 2014 and %HRA_END_DATE_LONG%.</li>
        <li>
            Complete <em>A Healthier You</em> screening between October 1, 2013 and %SCREENING_END_DATE_LONG%:
            <ul>
                <li>At your health care provider's office, when you submit your results to your Associate Health Office by the deadline</li>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;OR
                <li>During the designated on-site screening sessions (March 1, 2014 - June 30, 2014)</li>
            </ul>
        </li>
        <li>Meet at least 75% of the health standards outlined in the table above, for a total of 12 out of a potential 16 points.
        </li>
    </ol>
    <br/>
    <p><strong>What are the 2015 Medical Plan Discounts?</strong></p>
    <ul>
        <li>
            <strong>Associate</strong> - Ministry Medical Plan participants,
            who complete the HRA and meet a minimum points requirement of 12 as outlined
            in the points table above, will receive a contribution toward their 2015 medical plan premium. For a provider biometric screening checklist to take to the doctor,
            <a href="/resources/4927/Office-Visit-Screening-Checklist061914.pdf"> click here.</a>(Prior year discount was $1020 annual)
        </li>
        <li>
            <strong>Spouse</strong> - If your spouse, who is enrolled in a Ministry Medical plan, completes the three steps below, you will receive a separate contribution towards your 2015 medical plan premium. (Prior year spouse discount was $504 annual)
            <ol>
                <li>Completes the Circle Wellness HRA between March 1, 2014 and %HRA_END_DATE_LONG%;</li>
                <li>Submits their screenings (completed by their health care provider's office) to Circle Wellness by the deadline. For a provider biometric screening checklist to take to the doctor, <a href="/resources/4622/Office-Visit-Screening-Checklist-NCW-2014.pdf">click here.</a> You can submit
                your documentation directly, by <a href="/content/chp-document-uploader">clicking here.</a>)</li>
                <li>Achieves at least 12 of the 16 points outlined above

                </li>
            </ol>
        </li>
    </ul>
    <br/>
    <p>
        * Non Medical Plan Participants – We encourage all benefit eligible employees to take advantage of the
        <em>A Healthier You</em> HRA web tool and have their screenings completed free of charge at one of the
        designated onsite screening sessions.  Also, see the Wellness Activity button for activities that all
        benefit eligible employees can participate in to earn rewards.
    </p>

    <?php endif ?>
    <?php
    }
}

class MinistryHealth2015ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('date_of_birth', function (User $user) {
            return $user->date_of_birth;
        });
        
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
        $printer = new MinistryHealth2015Printer();
        $printer->setShowNA(true);
        $printer->showResult(true);
        $printer->setTargetHeader('Requirements / Results');

        return $printer;
    }

    public function userIsSpouse(User $user)
    {
        if($user->relationship_type == Relationship::SPOUSE) {
            return true;
        }

        if( ($group = $user->getGroupValueFromTypeName('Ministry ee to treat as spouses'))
            && $group == 'Ministry ee to treat as spouses') {
            return true;
        }

        return false;
    }

    public function preMapEvaluate(ComplianceViewStatus $status, User $user)
    {
        $view = $status->getComplianceView();

        if($view->getName() == 'any_prevention'
        ) {

            $view->addLink(new Link('Instructions', '/content/preventive_exam'));
        }

        $hasComment = ($comment = $status->getComment()) !== null &&
            trim($comment) &&
            $comment != ComplyWithScreeningTestComplianceView::NO_SCREENING_TEXT &&
            $comment != ComplyWithScreeningTestComplianceView::TEST_NOT_TAKEN_TEXT &&
            $comment != 'HRA Not Taken';

        $alternativeEndDate = $this->getHireDateEndDateFunction('2014-09-30', '2014-10-31');

        $userIsSpouse = $this->userIsSpouse($user);

        if($userIsSpouse && ($alias = $view->getAttribute('assigned_alias'))) {
            $learn = new CompleteELearningGroupSet(
                $this->getStartDate(),
                $alternativeEndDate,
                $alias
            );

            $learn->setAllowPastCompleted(false);

            $learn->setNumberRequired(1);

            if($linkUrl = $view->getAttribute('spouse_url')) {
                $learnLink = new Link('Alternative', $linkUrl);

                $learn->emptyLinks();
                $learn->addLink($learnLink);
            } else {
                $learnLink = current($learn->getLinks());
                $learnLink->setLinkText('Alternative');
            }

            if($hasComment && !$status->isCompliant()) {
                $view->setAlternativeComplianceView($learn, true, array($this, 'alternativeViewUsed'));
            } else {
                $view->setAlternativeComplianceView(null);
            }
        } else if(!$userIsSpouse && ($alias = $view->getAttribute('ee_assigned_alias'))) {

            if($hasComment && !$status->isCompliant()) {
                $view->addLink(new Link('Alternative', '/content/alternativestandard'));
            }

            $elearn = new CompleteAssignedELearningLessonsComplianceView(
                $this->getStartDate(),
                $alternativeEndDate
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

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $query->andWhere(
            sprintf('%s.hiredate IS NULL OR %s.hiredate < ?', $query->getRootAlias(), $query->getRootAlias()), '2014-09-30'
        );

        parent::preQuery($query, $withViews);
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

        $hraStart = '2014-03-01';
        $hraEnd = $this->getHireDateEndDateFunction('2014-06-30', '2014-09-30');

        $screeningStart = '2013-10-01';
        $screeningEnd = $programEnd;

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView('2014-03-01', $hraEnd);
        $hraView->emptyLinks();
        $hraView->setShowDateTaken(false);
        $hraView->addLink(new Link('Complete', '/content/989'));
        $hraView->setReportName('Health Risk Assessment');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete between 03/01/2014 - %HRA_END_DATE%');
        $hraView->setName('hra');
        $hraView->setDefaultComment('HRA Not Taken');

        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $pointsGroup = new ComplianceViewGroup('points', 'Points');
        $pointsGroup->setPointsRequiredForCompliance(12);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($hraStart, $hraEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $smokingView->setReportName('Tobacco Use (As answered in the HRA)');
        $smokingView->setName('smoking');
        $smokingView->setAttribute('ee_assigned_alias', '2014_tobacco');
        $smokingView->setAttribute('spouse_url', '/content/12088?print_course_id[]=2740');
        $smokingView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $physicalView = new CompleteAnyPreventionComplianceView($programStart, $programEnd);
        $physicalView->setReportName('Preventive Care Visit (per SAS Ministry plan claims)');
        $physicalView->setName('any_prevention');
        $physicalView->bindAlias('ministry_2013');
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $physicalView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bloodPressureView->setComplianceStatusPointMapper($mapper);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 120, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 80, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤120/≤80');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '121-140/81-90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '>140/>90');
        // $bloodPressureView->addLink($altStandardLink);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setAttribute('ee_assigned_alias', '2014_blood_pressure');
        $bloodPressureView->setAttribute('assigned_alias', 'min_2014_bp_assigned');
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
        $glucoseView->setAttribute('ee_assigned_alias', '2014_glucose');
        $glucoseView->setAttribute('assigned_alias', 'min_2014_diabetes_assigned');
        $glucoseView->setAttribute('spouse_url', '/content/diabetes_elearning_alternative');
        $glucoseView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $tcOrHdlView = new Ministry2015TCOrHDLComplianceView($screeningStart, $screeningEnd);
        $tcOrHdlView->setComplianceStatusPointMapper($mapper);
        $tcOrHdlView->setName('tc_or_hdl');
        $tcOrHdlView->setReportName('Total Cholesterol <u>OR</u> HDL');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'TC: <201, HDL: > 60');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'TC: 201-240');
        $tcOrHdlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'TC: >240');
        //$tcOrHdlView->addLink($altStandardLink);
        $tcOrHdlView->setAttribute('ee_assigned_alias', '2014_tc_hdl');
        $tcOrHdlView->setAttribute('assigned_alias', 'min_2014_cholesterol_assigned');
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
        $trigView->setAttribute('ee_assigned_alias', '2014_trig');
        $trigView->setAttribute('assigned_alias', 'min_2014_triglycerides_assigned');
        $trigView->setAttribute('spouse_url', '/content/trig_elearning_alternative');
        $trigView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $hdlView->setName('hdl');
        $hdlView->setReportName('HDL');
        $hdlView->overrideTestRowData(null, 40, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50, null, null, 'F');
        //$hdlView->addLink($altStandardLink);
        $hdlView->setMergeScreenings(true);
        $hdlView->setAttribute('ee_assigned_alias', '2014_hdl');
        $hdlView->setAttribute('assigned_alias', 'min_2014_hdl_assigned');
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
        $ldlView->setAttribute('ee_assigned_alias', '2014_ldl');
        $ldlView->setAttribute('assigned_alias', 'min_2014_ldl_assigned');
        $ldlView->setAttribute('spouse_url', '/content/ldl_elearning_alternative');
        $ldlView->setPreMapCallback(array($this, 'preMapEvaluate'));

        $bodyComposition = new Ministry2015BodyCompositionComplianceView($screeningStart, $screeningEnd);
        $bodyComposition->setComplianceStatusPointMapper($mapper);
        $bodyComposition->setName('body_composition');
        $bodyComposition->setReportName('Body Composition<div style="text-align:center"><br/><br/>Waist/Hip Ratio<br/><u>OR</u><br/>Waist/Abdominal Girth</div>');
        $bodyComposition->setStatusSummary(ComplianceStatus::COMPLIANT, 'W/H Ratio: <0.8, W/A Girth: <28.5-35');
        $bodyComposition->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, 'W/A Girth: 35.1-43');
        $bodyComposition->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'W/A: >43');
        // $bodyComposition->addLink($altStandardLink);
        $bodyComposition->setAttribute('ee_assigned_alias', '2014_body_composition');
        $bodyComposition->setAttribute('assigned_alias', 'min_2014_body_comp_assigned');
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

    protected function getHireDateEndDateFunction($normalEndDate, $extendedEndDate)
    {
        return function($format, User $user) use($normalEndDate, $extendedEndDate) {
            if($user->relationship_type != Relationship::EMPLOYEE && $user->relationshipUser && $user->relationshipUser->exists()) {
                $hireDate = $user->relationshipUser->hiredate;
            } else {
                $hireDate = $user->hiredate;
            }

            if(strtotime($hireDate) >= strtotime('2014-06-01')) {
                return date($format, strtotime($extendedEndDate));
            } else {
                return date($format, strtotime($normalEndDate));
            }
        };
    }
}