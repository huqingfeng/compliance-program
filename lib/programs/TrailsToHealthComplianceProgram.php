<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class AttendCommunityEducationProgram extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(30);
    }

    public function __construct($startDate, $endDate)
    {
        parent::__construct($startDate, $endDate);

        $this->pointsPerRecord = 20;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $totalPoints = $this->pointsPerRecord === null ? null : $this->pointsPerRecord * count($records);

        return new ComplianceViewStatus($this, null, $totalPoints);
    }

    private $pointsPerRecord = null;
}

class TrailsPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->tableHeaders['links'] = 'Action Links';
        ?>
    <style type="text/css">
        .phipTable .headerRow th, .phipTable .headerRow td, #legendText {
            background-color:#018bb0 !important;
        }

        .phipTable th, .phipTable td {
            font-size:12px;
            padding:8px;
        }
    </style>
    <?php
        parent::printReport($status);
    }
}

class TrailsToHealthComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new TrailsPrinter();

        $printer->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            if($view instanceof DateBasedComplianceView) {
                return $view->getEndDate('m/d/Y');
            } else {
                return ' ';
            }
        });

        return $printer;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $noPoints = new ComplianceStatusPointMapper(0, 0, 0, 0);

        $end180 = $this->endDateGetter(180);
        $end300 = $this->endDateGetter(300);

        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('core', 'All Core Actions Required by the Deadlines Noted.')
                ->addComplianceView(
                CompleteHRAComplianceView::create($startDate, $end180)
                    ->setName('hra')
                    ->setReportName('Complete Annual Health Risk Assessment')
                    ->setComplianceStatusPointMapper($noPoints)
            )
                ->addComplianceView(
                GraduateFromCoachingSessionComplianceView::create($startDate, $end180)
                    ->setReportName('Complete Annual Health Screening & Coaching Visit')
                    ->setComplianceStatusPointMapper($noPoints)
            )
                ->addComplianceView(
                DoctorVisitComplianceView::create($startDate, $end180)
                    ->setReportName('Annual Visit With Primary Care Doctor')
                    ->setComplianceStatusPointMapper($noPoints)
            )
                ->addComplianceView(
                ViewHRAComplianceView::create($startDate, $end180)
                    ->setName('view_wellness_screening')
                    ->setReportName('Review My Annual Screening Results (From 1B)')
                    ->emptyLinks()
                    ->addLink(new Link('Results', '/content/989'))
                    ->setComplianceStatusPointMapper($noPoints)
            )
        );

        $screeningTestStart = '2009-01-01';
        $screeningTestEnd = '2009-12-31';
        $screeningMapper = new ComplianceStatusPointMapper(15, 5, 0, 0);

        $pointsGroup = new ComplianceViewGroup('extra', 'Earn 100 or more points from the options below.');

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $end300);
        $elearningView->setName('additional_elearning');
        $elearningView->setReportName('Complete recommended & extra e-learning lessons');
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(25);

        $pointsGroup
            ->setPointsRequiredForCompliance(100)
            ->addComplianceView($elearningView)
            ->addComplianceView(
            AttendCommunityEducationProgram::create($startDate, $end300)
                ->setReportName('Complete community education programs')
                ->setMaximumNumberOfPoints(40)
        );

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $end300);
        $physicalActivityView->setReportName('Get regular physical activity');
        $physicalActivityView->setMaximumNumberOfPoints(60);
        $physicalActivityView->setMonthlyPointLimit(60);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(5);
        $physicalActivityView->setFractionalDivisorForPoints(4);
        $physicalActivityView->setName('physical_activity');

        $pointsGroup->addComplianceView($physicalActivityView);

        $this->addComplianceViewGroup($pointsGroup);
    }

    private function endDateGetter($days)
    {
        $end = $this->getEndDate();

        $days = (int) $days;

        return function ($format, User $user = null) use ($days, $end) {
            if($user && $user->planenrolldate && ($p = strtotime($user->planenrolldate)) !== false) {
                $timeStamp = strtotime("+$days days", $p);
            } else {
                $timeStamp = $end;
            }

            return date($format, $timeStamp);
        };
    }
}