<?php

class LifestyleComplianceView extends PlaceHolderComplianceView
{
    public function __construct($name)
    {
        parent::__construct(ComplianceStatus::NOT_COMPLIANT);

        $this->setName($name);
        $this->setReportName($name);
        $this->addLink(new Link('View', '/content/12088'));
        $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(self::POINTS, 0, 0, 0));
    }

    const POINTS = 25;
}

class CompleteOneActivityComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $activity, $pts = 5)
    {
        $this->activity = $activity;
        parent::__construct($startDate, $endDate);
        $link = current($this->getLinks());
        $this->emptyLinks();
        $this->addLink(new Link('I did this', $link->getLink().'&redirect=/compliance_programs'));
        $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper($pts, 0, 0, 0));
    }

    public function getActivity()
    {
        return $this->activity;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        return new ComplianceViewStatus($this, count($records) ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
    }

    private $activity;
}

class VolunteeringPCComplianceView extends VolunteeringComplianceView
{
    public function configure()
    {
        $this->setMaximumNumberOfPoints(5);
        $link = current($this->getLinks());
        $this->emptyLinks();
        $this->addLink(new Link('I did this', $link->getLink().'&redirect=/compliance_programs'));
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity(41);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $hours = 0;
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();
            if(isset($answers[60])) {
                $hours += $answers[60]->getAnswer();
            }
        }

        return new ComplianceViewStatus($this, null, $hours, 'Done.');
    }

}

//class VolunteerComplianceView extends CompleteActivityComplianceView {

//}

class DependentPrevention extends ComplianceView
{
    public function __construct($type, $startDate, $endDate)
    {
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultName()
    {
        return 'prevention_dependent_'.$this->type;
    }

    public function getDefaultReportName()
    {
        return 'Prevention Dependent '.$this->type;
    }

    public function getStatus(User $user)
    {
        $db = Database::getDatabase();

        $query = '
      SELECT COUNT(*) AS count
      FROM prevention_data
      INNER JOIN prevention_codes on prevention_codes.code = prevention_data.code
      WHERE prevention_codes.type = ?
      AND prevention_data.user_id != ?
      AND prevention_data.covered_social_security_number = ?
      AND prevention_data.date BETWEEN ? AND ?
    ';

        $data = current($db->getResultsForQuery(
            $query,
            $this->type,
            $user->getID(),
            $user->getSocialSecurityNumber(),
            date('Y-m-d', strtotime($this->startDate)),
            date('Y-m-d', strtotime($this->endDate))
        ));

        return new ComplianceViewStatus($this, $data['count'] ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    private $type;
    private $startDate;
    private $endDate;
}

/**
 * Defines pilot chemical's 2010 compliance program.
 */
class PilotChemical2010ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $p = new CHPComplianceProgramReportPrinter();
        $p->requirements = false;
        $p->hide_status_when_point_based = true;

        return $p;
    }

    public function setComplianceProgramRecord(ComplianceProgramRecord $record = null)
    {
        parent::setComplianceProgramRecord($record);

        if($record) {
            $lessonsGroup = $this->getComplianceViewGroup('elearning');
            $lessons = ELearningLesson_v2::getRequiredLessons($this->getComplianceProgramRecord()->getClient());
            foreach($lessons as $lesson) {
                $lessonsGroup->addComplianceView(CompleteELearningLessonComplianceView::create($this->getStartDate(), $this->getEndDate(), $lesson));
            }
            $lessonsGroup->setPointsRequiredForCompliance(count($lessons));
        }
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $requirementsGroup = new ComplianceViewGroup('Requirements<br/><i></i>');

        $hraView = new CompleteHRAComplianceView($startDate, '04/01/2011');
        $hraView->setReportName('Complete HRA');
        $requirementsGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requirementsGroup);

        $lifestyleGroup = new ComplianceViewGroup('Lifestyle Management Programs');
        $lifestyleGroup
            ->addComplianceView(LifestyleComplianceView::create('LivingFree'))
            ->addComplianceView(LifestyleComplianceView::create('LivingLean'))
            ->addComplianceView(LifestyleComplianceView::create('LivingSmart'))
            ->addComplianceView(LifestyleComplianceView::create('LivingEasy'))
            ->addComplianceView(LifestyleComplianceView::create('LivingFit'))
            ->setPointsRequiredForCompliance(count($lifestyleGroup->getComplianceViews()) * LifestyleComplianceView::POINTS);

        $this->addComplianceViewGroup($lifestyleGroup);


        $lessonsGroup = new ComplianceViewGroup('E-Learning Programs');
        $lessonsGroup->setName('elearning');
        // See setComplianceProgramRecords
        $this->addComplianceViewGroup($lessonsGroup);

        $volGroup = new ComplianceViewGroup('Volunteering for a charitable organization');
        $volGroup->setPointsRequiredForCompliance(5);
        $volGroup->addComplianceView(VolunteeringPCComplianceView::create($startDate, $endDate)
            ->setReportName('Employee Volunteering'));
        $this->addComplianceViewGroup($volGroup);


        $runGroup = new ComplianceViewGroup('Participating in 5K/10K');
        $runGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(33))
            ->setReportName('Employee Participation'))
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(34))
            ->setReportName('Dependent Participation'));
        $this->addComplianceViewGroup($runGroup);


        $preventionMapper = new ComplianceStatusPointMapper(5, 0, 0, 0);

        $phyStartDate = '2009-12-01';
        $phyEndDate = '2011-03-31';

        $phySDText = date('m/d/y', strtotime($phyStartDate));
        $phyEDText = date('m/d/y', strtotime($phyEndDate));


        $phyGroup = new ComplianceViewGroup("Annual Physical<br/>($phySDText - $phyEDText)");
        $phyGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompletePreventionPhysicalExamComplianceView::create($phyStartDate, $phyEndDate)
            ->setReportName('Employee Physical')->setComplianceStatusPointMapper($preventionMapper)
            ->addLink(new Link('I did this', '/resources/2400/pilot_chemical_physical_exam_verification_form.pdf')))
            ->addComplianceView(DependentPrevention::create(PreventionType::PHYSICAL, $phyStartDate, $phyEndDate)
            ->setReportName('Dependent Physical')->setComplianceStatusPointMapper($preventionMapper)
            ->addLink(new Link('I did this', '/resources/2400/pilot_chemical_physical_exam_verification_form.pdf')));

        $this->addComplianceViewGroup($phyGroup);

        $denGroup = new ComplianceViewGroup("Routine Dental Exam<br/>($phySDText - $phyEDText)");
        $denGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompletePreventionDentalExamComplianceView::create($phyStartDate, $phyEndDate)
            ->setReportName('Employee Dental Visit')->setComplianceStatusPointMapper($preventionMapper)
            ->addLink(new Link('I did this', '/resources/2403/pilot_chemical_dental_exam_verification_form.pdf')))
            ->addComplianceView(DependentPrevention::create(PreventionType::DENTAL, $phyStartDate, $phyEndDate)
            ->setReportName('Dependent Dental Visit')->setComplianceStatusPointMapper($preventionMapper)
            ->addLink(new Link('I did this', '/resources/2403/pilot_chemical_dental_exam_verification_form.pdf')));


        $this->addComplianceViewGroup($denGroup);

        $drivingGroup = new ComplianceViewGroup('Safe Driving Pledge');
        $drivingGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(35))
            ->setReportName('Employee Pledge'))
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(36), 1)
            ->setReportName('Dependent Pledge'));
        $this->addComplianceViewGroup($drivingGroup);

        $defenseGroup = new ComplianceViewGroup('Self-Defense Course');
        $defenseGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(37))
            ->setReportName('Employee Course'))
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(38), 1)
            ->setReportName('Dependent Course'));
        $this->addComplianceViewGroup($defenseGroup);

        $flushotGroup = new ComplianceViewGroup('Flu Shot');
        $flushotGroup
            ->setPointsRequiredForCompliance(5)
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(39))
            ->setReportName('Employee Flu Shot'))
            ->addComplianceView(CompleteOneActivityComplianceView::create($startDate, $endDate, new ActivityTrackerActivity(40), 1)
            ->setReportName('Dependent Flu Shot'));
        $this->addComplianceViewGroup($flushotGroup);
    }
}