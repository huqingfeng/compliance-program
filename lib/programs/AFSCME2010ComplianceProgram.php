<?php
//
class AerobicExerciseComplianceView extends ComplianceView
{
    public function __construct() { }

    public function getDefaultName()
    {
        return 'aerobic_exercise';
    }

    public function getDefaultReportName()
    {
        return 'Aerobic Exercise';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('afscme_aerobic_exercise', false);

        if($record && $record->exists() && $record->compliant) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class SmokerComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start)->setEndDate($end);
        $this->addLink(new Link('Click to document', '/content/12062'));
        $this->addLink(new Link('Tips, tools & support', '/content/4765'));
    }

    public function getDefaultName()
    {
        return 'smoker';
    }

    public function getDefaultReportName()
    {
        return 'Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $view = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($this->getStartDate(), $this->getEndDate());
        $view->setComplianceViewGroup($this->getComplianceViewGroup());

        if($view->getStatus($user)->isCompliant()) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        }

        // Must have checked 2 or more on the form

        $record = UserDataRecord::getNewestRecord($user, 'smoker', true);
        if(!empty($record['smoker'])) {
            $smokerInfo = @unserialize($record['smoker']);

            if($smokerInfo === false) {
                $smokerInfo = array();
            }
        } else {
            $smokerInfo = array();
        }

        // smokerInfo[0] holds date, so if there count > 2, then compliant
        // If either of these are marked, give them compliance
        // - [2] I am enrolled in the Free and Clear program with phone, web and mail-based support at no expense to me  - see #1 below
        // - [4] I am using another qualified support option to quit smoking
        // - [3] I am working with my doctor to quit smoking
        if(isset($smokerInfo[2]) || isset($smokerInfo[3]) || isset($smokerInfo[4])) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class WeightManagementComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start)->setEndDate($end);
    }

    public function getDefaultName()
    {
        return 'weight_management';
    }

    public function getDefaultReportName()
    {
        return 'Weight Management';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        if($user->getDateOfBirth('U') <= strtotime('1946-12-31')) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            $bmi = false;

            $t = ScreeningTable::getInstance();

            $screeningQuery = $t->getScreeningForDates(
                $this->getStartDate(),
                $this->getEndDate(),
                array(
                    'query'   => $t->getScreeningsForUser($user, array('execute' => false)),
                    'execute' => false
                )
            );

            $screening = $screeningQuery->fetchOne();

            if($screening && $screening->getHeight() && $screening->getWeight()) {
                $bmi = (int) $screening->getWeight() * 703 / ((int) $screening->getHeight() * (int) $screening->getHeight());
            } else {
                $db = Database::getDatabase();

                $db->executeSelect(
                    'SELECT bmi_text FROM hra WHERE user_id = ? AND done = 1 AND date BETWEEN ? AND ? ORDER BY date DESC LIMIT 1',
                    $user->getID(),
                    $this->getStartDate('Y-m-d'),
                    $this->getEndDate('Y-m-d')
                );

                $hra = $db->getNextRow();

                if($hra && $hra['bmi_text']) {
                    $bmi = (float) $hra['bmi_text'];
                }
            }

            if($bmi !== false && $bmi < 30) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
            } else {
                $record = $user->getNewestDataRecord('afscme_weight', true);

                if($record->current_weight) {
                    if($record->compliant) {
                        return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
                    } else if($record->yellow) {
                        return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT);
                    } else {
                        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
                    }
                } else {
                    $status = in_array($record->option, array(1, 3, 4, 5)) ?
                        ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

                    return new ComplianceViewStatus($this, $status);
                }
            }
        }
    }
}

class AFSCME2010ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $supportGroup = ComplianceViewGroup::create('support', 'Have & use support as required & needed')
            ->addComplianceView(
            PlaceHolderComplianceView::create(ComplianceStatus::NA_COMPLIANT)
                ->setName('care_counselor')
                ->setReportName('Call the Care Counselor when required; return calls and follow-up as advised')
                ->setAttribute('status', '<a href="/content/5253">How the nurses can help</a>')
                ->setAttribute('end_date', 'All Year')
                ->setAttribute('report_name_link', '/content/1094#1carecouns')
                ->addLink(new Link('When are calls required?', '/content/5317'))
        )
            ->addComplianceView(
            PlaceHolderComplianceView::create(ComplianceStatus::NA_COMPLIANT)
                ->setName('health_coach')
                ->setReportName('Call the Health Coach when required: return calls and follow-up as advised')
                ->setAttribute('status', '<a href="/content/8733">How a coach can help</a>')
                ->setAttribute('end_date', 'All Year')
                ->setAttribute('report_name_link', '/content/1094#2healthcoach')
                ->addLink(new Link('Health coach info', '/content/8733'))
        )
            ->addComplianceView(
            PlaceHolderComplianceView::create(ComplianceStatus::NA_COMPLIANT)
                ->setName('health_tools')
                ->setReportName('Use your health books, doctor visit tools, online tools and other resources.')
                ->setAttribute('status', 'Keep them handy')
                ->setAttribute('end_date', 'All Year')
                ->setAttribute('report_name_link', '/content/1094#3resources')
                ->addLink(new Link('Go to online tools', '/'))
        );

        $this->addComplianceViewGroup($supportGroup);

        $relativeStart = strtotime('2011-01-01');

        $contactEnd = strtotime('2011-07-31');

        $contactGroup = ComplianceViewGroup::create('contact_information', 'Enter key contact info - update yearly and when information changes')
            ->addComplianceView(
            UpdateDoctorInformationComplianceView::create($relativeStart, $contactEnd)
                ->setReportName('My primary care doctor’s CURRENT contact info')
                ->setName('update_doctor')
                ->setAttribute('report_name_link', '/content/1094#4maindoc')
        )
            ->addComplianceView(
            UpdateContactInformationComplianceView::create($relativeStart, $contactEnd)
                ->setReportName('My CURRENT phone numbers, home address and email address')
                ->setName('update_contact')
                ->setAttribute('report_name_link', '/content/1094#5perscontact')
        );

        $this->addComplianceViewGroup($contactGroup);

        $detectionGroup = ComplianceViewGroup::create('early_detection', 'Practice good early detection')
            ->addComplianceView(
            CompleteHRAComplianceView::create('2010-12-15', '2011-07-31')
                ->setReportName('Complete the Health Power Assessment (HPA)')
                ->setName('complete_hra')
                ->setAttribute('report_name_link', '/content/1094#6hpa')
        )
            ->addComplianceView(
            CompleteScreeningComplianceView::create('2010-12-15', '2011-02-15')
                ->setReportName('Complete the wellness screening')
                ->setName('complete_screening')
                ->setAttribute('report_name_link', '/content/1094#7screening')
                ->emptyLinks()
                ->addLink(new Link('Results', '/content/989'))
        );

        $this->addComplianceViewGroup($detectionGroup);

        $skillsEnd = '2011-07-31';
        $skillsGroup = ComplianceViewGroup::create('skills_group', 'Learn, refresh and update core knowledge & skills')
            ->addComplianceView(
            CompleteELearningGroupSet::create('2007-01-01', '2011-11-30', 'catchup_lessons')
                ->setReportName('Pass key catch-up lessons from 2007-2009 (for new HIP members)')
                ->setName('catchup_lessons')
                ->setAttribute('report_name_link', '/content/1094#8catchup')
        )
            ->addComplianceView(
            CompleteRequiredELearningLessonsComplianceView::create($start, $skillsEnd)
                ->setReportName('Complete the e-learning lesson about the Health Improvement Plan')
                ->setName('required_lessons')
                ->setAttribute('report_name_link', '/content/1094#9group')
        )
            ->addComplianceView(
            CompleteELearningGroupSet::create($start, $skillsEnd, 'extra')
                ->setReportName('Complete 5 of the 15 extra lessons')
                ->setName('extra_lessons')
                ->setNumberRequired(5)
                ->setAttribute('report_name_link', '/content/1094#9group')
        )
            ->addComplianceView(
            CompleteAssignedELearningLessonsComplianceView::create($start, $skillsEnd)
                ->setReportName('Pass lessons & smart decision tools as requested by your health coach')
                ->setName('assigned_lessons')
                ->setAttribute('report_name_link', '/content/1094#10extra')
        );

        $this->addComplianceViewGroup($skillsGroup);

        $reviewGroupEnd = '2011-07-31';
        $reviewGroup = ComplianceViewGroup::create('review', 'Review your health, claims & care risk data and take action as needed')
            ->addComplianceView(
            AFSCMEViewWorkbookComplianceView::create($relativeStart, $reviewGroupEnd)
                ->setReportName('View your HealthReach Workbook')
                ->setAttribute('report_name_link', '/content/1094#11healthreach')
        )
            ->addComplianceView(
            WeightManagementComplianceView::create($relativeStart, '2011-11-15')
                ->setReportName('Have weight in a healthy range or commit to working on it')
                ->addLink(new Link('3 Month Follow-Up', '/content/afscme-weight'))
                ->addLink(new Link('Details', '/content/1094#12weight'))
                ->setAttribute('report_name_link', '/content/1094#12weight')
                ->setAttribute('footnote', 'Based on 2011 screening results')
        )
            ->addComplianceView(
            AerobicExerciseComplianceView::create()
                ->setName('aerobic_exercise')
                ->setReportName('Get sufficient aerobic exercise each month')
                ->addLink(new Link('Click to document', '/content/cnt_2665_aerobic_exercise'))
                ->addLink(new Link('Details', '/content/1094#13aerobic'))
                ->setAttribute('end_date', date('m/d/Y', strtotime($reviewGroupEnd)))
                ->setAttribute('report_name_link', '/content/1094#13aerobic')
        )
            ->addComplianceView(
            SmokerComplianceView::create('2010-12-15', $reviewGroupEnd)
                ->setReportName('Be a non-smoker or commit to trying to become one')
                ->setAttribute('report_name_link', '/content/1094#14nonsmoker')
                ->setAttribute('footnote', 'Based on HRA 12/15/10 or later')
        );

        $this->addComplianceViewGroup($reviewGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new BasicComplianceProgramReportPrinter();
        $printer->showCompleted = false;
        $printer->numberViewsOnly = true;

        $printer->addStatusCallbackColumn('By When', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            return $view->getAttribute('end_date', $view instanceof DateBasedComplianceView ? $view->getEndDate('m/d/Y') : null);
        });

        return $printer;
    }
}