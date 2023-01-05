<?php

use hpn\steel\query\SelectQuery;

class ShapeCoaching2016FitnessLogComplianceView extends ComplianceView
{
    public function __construct($month)
    {
        $this->month = $month;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('shape_coaching_2016_new', true);

        $requiredMinutes = 240;

        $dates = ShapeCoaching2016ComplianceProgram::getFitnessDates();

        if($record->getDataFieldValue("{$this->month}_cardio")) {
            $userCardio = (int) $record->getDataFieldValue("{$this->month}_cardio");
        } else {
            $userCardio = 0;
            foreach($dates as $date) {
                $userCardio += (int) $record->getDataFieldValue("{$this->month}_{$date}_cardio");
            }
        }

        if($record->getDataFieldValue("{$this->month}_strength")) {
            $userStrength = (int) $record->getDataFieldValue("{$this->month}_strength");
        } else {
            $userStrength = 0;
            foreach($dates as $date) {
                $userStrength += (int) $record->getDataFieldValue("{$this->month}_{$date}_strength");
            }
        }

        $compliant = ($userCardio + $userStrength) >= $requiredMinutes;

        $dateSubmitted = null;
        if($record->getDataFieldValue("{$this->month}_date_entered")) {
            $dateSubmitted = $record->getDataFieldValue("{$this->month}_date_entered");
        } else {
            foreach($dates as $date) {
                if($record->getDataFieldValue("{$this->month}_{$date}_date_entered")) {
                    $dateSubmitted =  $record->getDataFieldValue("{$this->month}_{$date}_date_entered");
                }
            }
        }

        $status = new ComplianceViewStatus($this, $compliant ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
        $status->setAttribute('strength_minutes', $userStrength);
        $status->setAttribute('cardio_minutes', $userCardio);
        $status->setAttribute('date_submitted', $dateSubmitted);

        return $status;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "fitness_log_{$this->month}";
    }


    public function getDefaultReportName()
    {
        return $this->month;
    }

    private $month;
}

class ShapeCoaching2016FitnessMinutesForm extends BaseForm
{
    public function configure()
    {
        $user = $this->getOption('user');
        $months = ShapeCoaching2016ComplianceProgram::getFitnessMonths();
        $dates = ShapeCoaching2016ComplianceProgram::getFitnessDates();
        $lastMonth = date('F', strtotime("first day of last month"));
        $thisMonth = date('F');

        $limitedMonths = array('');
        foreach($months as $month) {
            if(in_array($month, array($lastMonth, $thisMonth))
                && date('Y-m-d') >= '2016-07-01') {
                $limitedMonths[] = $month;
            }
        }

        $dayChoices = array_merge(array('0' => ''), $dates);

        $this->setWidgets(array(
            'month' => new sfWidgetFormChoice(array('choices' => array_combine($limitedMonths, $limitedMonths))),
            'date' => new sfWidgetFormChoice(array('choices' => $dayChoices)),
            'cardio' => new sfWidgetFormInputText(),
            'strength' => new sfWidgetFormInputText(),
            'user_id'  => new sfWidgetFormInputHidden(),
        ));

        $this->setValidators(array(
            'month' => new sfValidatorChoice(array('choices' => $limitedMonths)),
            'date' => new sfValidatorChoice(array('choices' => array_keys($dayChoices), 'required' => false)),
            'cardio' => new sfValidatorInteger(array('min' => 0)),
            'strength' => new sfValidatorInteger(array('min' => 0)),
            'user_id'  => new sfValidatorString(array('required' => false))
        ));

        $this->widgetSchema->setHelp('date', 'If you would like to enter your minutes for the whole month, do not enter a date in this field. Select month only.');

        $defaults['user_id'] = $user->getId();
        $defaults['cardio'] = 0;
        $defaults['strength'] = 0;

        $this->setDefaults($defaults);
    }
}

class ShapeCoaching2016ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        return $user->hasAttribute(Attribute::COACHING_END_USER);
    }
}


class ShapeCoaching2016ComplianceProgram extends ComplianceProgram
{
    public function handleInvalidUser(sfActions $actions)
    {
        $actions->getUser()->setNoticeFlash('Sorry, you have not enrolled in the program.');

        $actions->redirect('/');
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $query->andWhere(
            sprintf('%s.attributes & ? = ?', $query->getRootAlias()), array(Attribute::COACHING_END_USER, Attribute::COACHING_END_USER)
        );

        parent::preQuery($query, $withViews);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new ShapeCoaching2016ComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);
        $months = ShapeCoaching2016ComplianceProgram::getFitnessMonths();

        $printer->addCallbackField('coach_name', function(User $user) {
            $mostRecentSession = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
            if(is_object($mostRecentSession) && $mostRecentSession->getDate('Y-m-d') >= '2016-01-01') {
                return $mostRecentSession->getCoachUser()->getFullName();
            } else {
                return null;
            }
        });

        $printer->addStatusFieldCallback('requirement_exempt', function(ComplianceProgramStatus $status) {
            if($status->getComplianceViewStatus('requirement_exempt')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                return 'Yes';
            }

            return null;
        });

        foreach($months as $month) {
            $printer->addCallbackField('total_minutes_cardio_'.$month, function(User $user) use ($month) {
                $dates = ShapeCoaching2016ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord('shape_coaching_2016_new');

                $userCardio = 0;
                if($record->exists()) {
                    if($record->getDataFieldValue("{$month}_cardio")) {
                        $userCardio = (int) $record->getDataFieldValue("{$month}_cardio");
                    } else {
                        foreach($dates as $date) {
                            $userCardio += (int) $record->getDataFieldValue("{$month}_{$date}_cardio");
                        }
                    }
                }

                return $userCardio;
            });

            $printer->addCallbackField('total_minutes_strength_'.$month, function(User $user) use ($month) {
                $dates = ShapeCoaching2016ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord('shape_coaching_2016_new');

                $userStrength = 0;
                if($record->exists()) {
                    if($record->getDataFieldValue("{$month}_strength")) {
                        $userStrength = (int) $record->getDataFieldValue("{$month}_strength");
                    } else {
                        foreach($dates as $date) {
                            $userStrength += (int) $record->getDataFieldValue("{$month}_{$date}_strength");
                        }
                    }
                }

                return $userStrength;
            });
        }

        $printer->addEndStatusFieldCallBack('$50 Wellness Bonus', function(ComplianceProgramStatus $status) {
            if($status->getAttribute('bonus_status') == ComplianceStatus::COMPLIANT) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        $printer->setShowTotals(false);

        return $printer;
    }

    public function getHardcodedId()
    {
        return 774;
    }

    public static function getFitnessMonths()
    {
        return array(
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
            'January',
            'February'
        );
    }

    public static function getFitnessDates()
    {
        $dates = array();
        for($i=1; $i<=31; $i++) {
            $dates[$i] = $i;
        }

        return $dates;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ShapeCoaching2016WMS2Printer();
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Completed', '/images/lights/greenlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Completed', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Required', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $overrideGroup = new ComplianceViewGroup('Compliance Overrides');
        $requirementExempt = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $requirementExempt->setName('requirement_exempt');
        $requirementExempt->setReportName('Requirement Exempt');
        $overrideGroup->addComplianceView($requirementExempt);

        $this->addComplianceViewGroup($overrideGroup);


        $consultationGroup = new ComplianceViewGroup('consultation', 'Wellness Consultations');
        $consultationGroup->setPointsRequiredForCompliance(8);

        $consultationOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationOneView->setReportName('Wellness Consultation 01');
        $consultationOneView->setName('consultation_01');
        $consultationOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationOneView);

        $consultationTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationTwoView->setName('consultation_02');
        $consultationTwoView->setReportName('Wellness Consultation 02');
        $consultationTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationTwoView);

        $consultationThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationThreeView->setName('consultation_03');
        $consultationThreeView->setReportName('Wellness Consultation 03');
        $consultationThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationThreeView);

        $consultationFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationFourView->setName('consultation_04');
        $consultationFourView->setReportName('Wellness Consultation 04');
        $consultationFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationFourView);

        $consultationOnsiteView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationOnsiteView->setName('consultation_onsite');
        $consultationOnsiteView->setReportName('Wellness Consultation Onsite');
        $consultationOnsiteView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationOnsiteView);

        $this->addComplianceViewGroup($consultationGroup);


        $classesGroup = new ComplianceViewGroup('classes', 'Wellness Classes');
        $classesGroup->setPointsRequiredForCompliance(4);

        for($i = 1; $i < 17; $i++) {
            $classView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
            $classView->setName(sprintf("classes_%02d", $i));
            $classView->setReportName(sprintf("Wellness Classes %02d", $i));
            $classView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
            $classesGroup->addComplianceView($classView);
        }

        $this->addComplianceViewGroup($classesGroup);


        $challengesGroup = new ComplianceViewGroup('challenges', 'Wellness Challenges');
        $challengesGroup->setPointsRequiredForCompliance(4);

        for($i = 1; $i < 15; $i++) {
            $challengeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
            $challengeView->setName(sprintf("challenges_%02d", $i));
            $challengeView->setReportName(sprintf("Wellness Challenges %02d", $i));
            $challengeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
            $challengesGroup->addComplianceView($challengeView);
        }

        $this->addComplianceViewGroup($challengesGroup);


        $elearningGroup = new ComplianceViewGroup('elearning', 'E-learning');
        $elearningGroup->setPointsRequiredForCompliance(3);

        $elearningEndDate = '2017-03-01';

        $countedLessons = array();
        for($itemNumber = 1; $itemNumber < 21; $itemNumber++) {
            $elearningView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
            $elearningView->setName('elearning_lesson_'.$itemNumber);
            $elearningView->setReportName(sprintf('Elearning %s', $itemNumber));
            $elearningView->setMaximumNumberOfPoints(1);
            $elearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
//            $elearningView->setPostEvaluateCallback(function($status, $user) use($programStart, $elearningEndDate, &$countedLessons) {
//                $completedLessons = array_reverse(
//                    ELearningLessonCompletion_v2::getAllCompletedLessons($user, $programStart, $elearningEndDate)
//                );
//
//                foreach($completedLessons as $completedLesson) {
//                    if(!in_array($completedLesson->lesson_id, $countedLessons)) {
//
//                        $status->getComplianceView()->setReportName($completedLesson->getLesson()->getName());
//                        $status->setPoints(1);
//                        $status->setStatus(ComplianceStatus::COMPLIANT);
//
//
//                        $countedLessons[] = $completedLesson->lesson_id;
//
//                        break;
//                    }
//                }
//
//            });

            $elearningGroup->addComplianceView($elearningView);
        }

        $this->addComplianceViewGroup($elearningGroup);


        $exerciseGroup = new ComplianceViewGroup('exercise', 'Activity Minutes');
        $exerciseGroup->setPointsRequiredForCompliance(3);

        foreach(self::getFitnessMonths() as $month) {
            $fitnessView = new ShapeCoaching2016FitnessLogComplianceView($month);
            $fitnessView->setReportName($month);
            $fitnessView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
            $fitnessView->addLink(new Link('Log Activity Minutes', sprintf('/compliance_programs/localAction?id=%s&local_action=fitness_logs"', $this->getHardcodedId())));

            $exerciseGroup->addComplianceView($fitnessView);
        }


        $this->addComplianceViewGroup($exerciseGroup);


        $workshopsGroup = new ComplianceViewGroup('workshops', 'Wellness Workshops');
        $workshopsGroup->setPointsRequiredForCompliance(20);

        $workshopOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $workshopOneView->setName('workshops_01');
        $workshopOneView->setReportName('Wellness Workshops 01');
        $workshopOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $workshopsGroup->addComplianceView($workshopOneView);

        $workshopTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $workshopTwoView->setName('workshops_02');
        $workshopTwoView->setReportName('Wellness Workshops 02');
        $workshopTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $workshopsGroup->addComplianceView($workshopTwoView);

        $workshopThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $workshopThreeView->setName('workshops_03');
        $workshopThreeView->setReportName('Wellness Workshops 03');
        $workshopThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $workshopsGroup->addComplianceView($workshopThreeView);

        $workshopFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $workshopFourView->setName('workshops_04');
        $workshopFourView->setReportName('Wellness Workshops 04');
        $workshopFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $workshopsGroup->addComplianceView($workshopFourView);

        $this->addComplianceViewGroup($workshopsGroup);


        $bookClubGroup = new ComplianceViewGroup('book_club', 'Book Club');
        $bookClubGroup->setPointsRequiredForCompliance(4);


        $bookClubOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubOneView->setName('book_club_01');
        $bookClubOneView->setReportName('Book Club 01');
        $bookClubOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubOneView);

        $bookClubTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubTwoView->setName('book_club_02');
        $bookClubTwoView->setReportName('Book Club 02');
        $bookClubTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubTwoView);

        $this->addComplianceViewGroup($bookClubGroup);


        $documentaryGroup = new ComplianceViewGroup('documentary', 'Documentary');
        $documentaryGroup->setPointsRequiredForCompliance(4);

        $documentaryOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $documentaryOneView->setName('documentary_01');
        $documentaryOneView->setReportName('Documentary 01');
        $documentaryOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $documentaryGroup->addComplianceView($documentaryOneView);

        $documentaryTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $documentaryTwoView->setName('documentary_02');
        $documentaryTwoView->setReportName('Documentary 02');
        $documentaryTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $documentaryGroup->addComplianceView($documentaryTwoView);

        $documentaryThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $documentaryThreeView->setName('documentary_03');
        $documentaryThreeView->setReportName('Documentary 03');
        $documentaryThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $documentaryGroup->addComplianceView($documentaryThreeView);

        $documentaryFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $documentaryFourView->setName('documentary_04');
        $documentaryFourView->setReportName('Documentary 04');
        $documentaryFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $documentaryGroup->addComplianceView($documentaryFourView);

        $this->addComplianceViewGroup($documentaryGroup);


        $extraGroup = new ComplianceViewGroup('extra_wellness_items', 'Extra Wellness Items');
        $extraGroup->setPointsRequiredForCompliance(5);

        for($i = 1; $i < 11; $i++) {
            $extraView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
            $extraView->setName(sprintf("extra_%02d", $i));
            $extraView->setReportName(sprintf("Extra Wellness Items %02d", $i));
            $extraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
            $extraGroup->addComplianceView($extraView);
        }

        $this->addComplianceViewGroup($extraGroup);
    }

    protected function composeViewCallbacks(array $callbacks)
    {
        return function(ComplianceViewStatus $status, User $user) use($callbacks) {
            foreach($callbacks as $cb) {
                call_user_func($cb, $status, $user);
            }
        };
    }

    public function useParallelReport()
    {
        return false;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $mostRecentSession = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
        if(is_object($mostRecentSession) && $mostRecentSession->getDate('Y-m-d') >= '2016-01-01') {
            $status->setAttribute('coach_name', $mostRecentSession->getCoachUser()->getFullName());
        }

        $elearningStartDate = '2016-07-01';
        $elearningEndDate = '2017-03-01';

        $completedLessons = array_reverse(
            ELearningLessonCompletion_v2::getAllCompletedLessons($user, $elearningStartDate, $elearningEndDate)
        );

        $itemNumber = 1;
        $elearningCompliant = 0;
        $elearningGroupStatus = $status->getComplianceViewGroupStatus('elearning');
        foreach($completedLessons as $completedLesson) {
            $elearningStatus = $elearningGroupStatus->getComplianceViewStatus('elearning_lesson_'.$itemNumber);
            if($elearningStatus) {
                $elearningStatus->setPoints(1);
                $elearningStatus->setStatus(ComplianceStatus::COMPLIANT);
                $elearningStatus->setAttribute('completed_lesson', $completedLesson->getLesson()->getName());

                $elearningCompliant++;
            }

            $itemNumber++;
        }


        $elearningGroupStatus->setPoints($elearningCompliant);
        if($elearningGroupStatus->getPoints() >= $elearningGroupStatus->getComplianceViewGroup()->getPointsRequiredForCompliance()) {
            $elearningGroupStatus->getStatus(ComplianceStatus::COMPLIANT);
        } elseif($elearningGroupStatus->getPoints() > 0) {
            $elearningGroupStatus->getStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $elearningGroupStatus->getStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        parent::evaluateAndStoreOverallStatus($status);


        $programStatus = ComplianceStatus::NOT_COMPLIANT;
        $bonusStatus = ComplianceStatus::NOT_COMPLIANT;

        $totalPoints = $status->getPoints();

        $consultationGroupStatus = $status->getComplianceViewGroupStatus('consultation');
        $onsiteStatus = $status->getComplianceViewStatus('consultation_onsite');

        if ($onsiteStatus->getStatus() == ComplianceViewStatus::COMPLIANT
            && $consultationGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $totalPoints >= 30) {
            $programStatus = ComplianceStatus::COMPLIANT;
            $bonusStatus = ComplianceStatus::COMPLIANT;
        } else if ($onsiteStatus->getStatus() == ComplianceViewStatus::COMPLIANT
            && $consultationGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $totalPoints >= 20) {
            $programStatus = ComplianceStatus::COMPLIANT;
        }

        $status->setStatus($programStatus);
        $status->setAttribute('bonus_status', $bonusStatus);
    }

    public function getLocalActions()
    {
        return array(
            'fitness_logs' => array($this, 'executeFitnessLogs'),
            'delete_fitness_minutes' => array($this, 'executeDeleteFitnessMinutes')
        );
    }

    public function executeDeleteFitnessMinutes(sfActions $actions)
    {
        $user = $this->findRequestUser($actions);

        $recordID = $actions->getRequestParameter('record_id');
        $month = $actions->getRequestParameter('month');
        $date = $actions->getRequestParameter('date');

        $record = new UserDataRecord($recordID);

        if($record->exists()) {
            if($date && !empty($date)) {
                $record->getDataField("{$month}_{$date}_cardio")->delete();
                $record->getDataField("{$month}_{$date}_strength")->delete();
                $record->getDataField("{$month}_{$date}_date_entered")->delete();
            } else {
                $record->getDataField("{$month}_cardio")->delete();
                $record->getDataField("{$month}_strength")->delete();
                $record->getDataField("{$month}_date_entered")->delete();
            }
            $record->save();

            $actions->getUser()->setNoticeFlash('Your activity for '.$month.' '.$date.' has been deleted.');

            $actions->redirect(sprintf(
                '/compliance_programs/localAction?id=%s&local_action=fitness_logs&user_id=%s',
                $this->getHardcodedId(),
                $user->getId()
            ));
        }


    }

    public function executeFitnessLogs(sfActions $actions)
    {
        $user = $this->findRequestUser($actions);

        $fitnessForm = new ShapeCoaching2016FitnessMinutesForm(array(), array('user' => $user));
        $dates = ShapeCoaching2016ComplianceProgram::getFitnessDates();
        $dates = array_reverse($dates);
        $months = ShapeCoaching2016ComplianceProgram::getFitnessMonths();
        $months = array_reverse($months);

        if($fitnessForm->isValidForRequest($actions->getRequest())) {
            $record = $user->getNewestDataRecord(self::SHAPE_2016_FITNESS_RECORD_TYPE, true);
            if($fitnessForm->getValue('date')) {
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_date_entered", date('Y-m-d'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_cardio", $fitnessForm->getValue('cardio'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_strength", $fitnessForm->getValue('strength'));

                $record->getDataField("{$fitnessForm->getValue('month')}_cardio")->delete();
                $record->getDataField("{$fitnessForm->getValue('month')}_strength")->delete();
                $record->getDataField("{$fitnessForm->getValue('month')}_date_entered")->delete();

            } else {
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_date_entered", date('Y-m-d'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_cardio", $fitnessForm->getValue('cardio'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_strength", $fitnessForm->getValue('strength'));

                foreach($dates as $date) {
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_cardio")->delete();
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_strength")->delete();
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_date_entered")->delete();
                }
            }

            $record->save();

            $actions->getUser()->setNoticeFlash(
                sprintf('Your activity for '.$fitnessForm->getValue('month').' %s was saved.',
                    $fitnessForm->getValue('date') != 0 ? $fitnessForm->getValue('date') : '')
            );

            if($user->getId() != $actions->getSessionUser()->getId()) {
                $actions->redirect(sprintf(
                    '/compliance_programs/localAction?id=%s&local_action=fitness_logs&user_id=%s',
                    $this->getHardcodedId(),
                    $user->getId()
                ));
            }

            $actions->redirect('/compliance_programs?id='.$this->getHardcodedId());
        }


        $record = $user->getNewestDataRecord(self::SHAPE_2016_FITNESS_RECORD_TYPE);
        $lastMonth = date('F', strtotime("first day of last month"));
        $thisMonth = date('F');

        ?>
        <style type="text/css">
            #previous_entries_table tr th, #previous_entries_table tr td {
                padding: 10px 10px 10px 10px;
                text-align: center;
            }
        </style>

        <div id="fitness_logs">
            <p><?php echo $user->getFullName() ?></p>
            <hr/>


            <p>To log your fitness minutes, fill out the form below and select SUBMIT. Please be aware that you can
                only log the current month and the month prior. In order to receive credit, you must complete 240
                minutes of activity each month.</p>

            <br/>

            <?php echo $fitnessForm->renderFormTag("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=fitness_logs") ?>
            <ul>
                <?php echo $fitnessForm ?>
            </ul>
            <div class="form-actions">
                <p>By selecting SUBMIT, I attest that the information I am submitting is accurate to the
                    best of my knowledge.
                </p>

                <input type="submit" class="btn btn-primary" value="SUBMIT" />
            </div>
            </form>

            <?php if($record->exists() && count($record->getAllDataFieldValues()) > 0) : ?>
                <table id="previous_entries_table">
                    <tr><th>Month</th><th>Date</th><th>Cardio Minutes</th><th>Strength Minutes</th><th>Date Entered</th><th></th></tr>
                    <?php foreach($months as $month) : ?>
                        <?php $monthTotal = true; ?>
                        <?php foreach($dates as $date) : ?>
                            <?php if($record->getDataFieldValue("{$month}_{$date}_cardio") || $record->getDataFieldValue("{$month}_{$date}_strength")) : ?>
                                <tr>
                                    <td><?php echo $month ?></td>
                                    <td><?php echo $date ?></td>
                                    <td><?php echo $record->getDataFieldValue("{$month}_{$date}_cardio"); ?></td>
                                    <td><?php echo $record->getDataFieldValue("{$month}_{$date}_strength"); ?></td>
                                    <td><?php echo date('m/d/Y', strtotime($record->getDataFieldValue("{$month}_{$date}_date_entered"))); ?></td>
                                    <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                                            <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                                            <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                                            <input type="hidden" name="month" value="<?php echo $month ?>" />
                                            <input type="hidden" name="date" value="<?php echo $date ?>" />
                                            <button type="submit" class="btn" id="search-submit"> Delete</button>
                                        </form></td>
                                </tr>
                                <?php $monthTotal = false ?>
                            <?php endif ?>
                        <?php endforeach; ?>
                        <?php if($monthTotal
                            && ($record->getDataFieldValue("{$month}_cardio")
                            || $record->getDataFieldValue("{$month}_strength"))) : ?>
                            <tr>
                                <td><?php echo $month ?></td>
                                <td><?php echo 'For the whole month' ?></td>
                                <td><?php echo $record->getDataFieldValue("{$month}_cardio"); ?></td>
                                <td><?php echo $record->getDataFieldValue("{$month}_strength"); ?></td>
                                <td><?php echo  date('m/d/Y', strtotime($record->getDataFieldValue("{$month}_date_entered"))); ?></td>
                                <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                                        <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                                        <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                                        <input type="hidden" name="month" value="<?php echo $month ?>" />
                                        <button type="submit" class="btn" id="search-submit"> Delete</button>
                                    </form></td>
                            </tr>
                        <?php endif ?>
                    <?php endforeach ?>

                </table>
            <?php endif ?>
        </div>
        <?php
    }

    protected function findRequestUser(sfActions $actions)
    {
        $user = ($userId = $actions->getRequestParameter('user_id')) ? UserTable::getInstance()->find($userId) : $actions->getSessionUser();

        if (!$user) {
            $actions->forward404();
        }

        if (!$actions->getSessionUser()->canViewUser($user)) {
            $actions->forward401();
        }

        return $user;
    }


    public static function getCoachingData(User $user)
    {
        return self::_getCoachingData($user);
    }

    protected static function _getCoachingData(User $user)
    {
        if(self::$_coachingData['user_id'] == $user->id) {
            return self::$_coachingData['data'];
        } else {
            $defaults = array();

            $sessions = CoachingSessionTable::getInstance()->findActiveSessionsForUser($user);
            foreach($sessions as $session) {
                if(date('Y-m-d', strtotime($session->created_at)) < self::SHAPE_2016_COACHING_START_DATE
                    || date('Y-m-d', strtotime($session->created_at)) > self::SHAPE_2016_COACHING_END_DATE) {
                    continue;
                }

                $reports = CoachingReportTable::getInstance()->findActiveReports($session);

                foreach($reports as $report) {
                    if(!is_object($report)) continue;

                    $edit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);
                    if(is_object($edit)) {
                        $recordedDocument = $edit->getRecordedDocument();
                        $recordedFields = $recordedDocument->getRecordedDocumentFields();

                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();

                            if(!empty($value) && empty($defaults[$name])) {
                                $defaults[$name] = $value;
                            }
                        }
                    }
                }
            }

            self::$_coachingData['user_id'] = $user->id;
            self::$_coachingData['data'] = $defaults;

            return $defaults;
        }
    }

    private static $_coachingData = array('user_id' => null, 'data' => array());

    const SHAPE_2016_COACHING_START_DATE = '2015-06-01';
    const SHAPE_2016_COACHING_END_DATE = '2016-05-14';

    const SHAPE_2016_FITNESS_RECORD_TYPE = 'shape_coaching_2016_new';
}


class ShapeCoaching2016WMS2Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($status, $text) use ($classForStatus) {
            $class = $status === 'shape' ? 'shape' : $classForStatus($status);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
                    <div style="font-size: 1.3em; line-height: 1.2em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $circle2 = function($color) {
            ob_start();
            ?>
            <div style="width:30px; height: 30px; border-radius: 15px; background-color: <?php echo $color ?>;"></div>
            <?php

            return ob_get_clean();
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();
                ?>

            <table class="details-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <?php if($group->getComplianceViewGroup()->getName() == 'exercise') : ?>
                        <th class="minutes">Cardio Minutes</th>
                        <th class="minutes">Strength Minutes</th>
                    <?php else : ?>
                        <th class="comment">Notes</th>
                    <?php endif ?>
                    <th class="points">Points</th>
                    <th class="text-center">Links</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1 ?>
                <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                    <?php $class = $classFor($pct) ?>
                    <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?> <?php echo $viewStatus->getStatus() != ComplianceViewStatus::COMPLIANT ? 'hidden' : ''?>">
                        <td>
                            <?php if($group->getComplianceViewGroup()->getName() == 'elearning') : ?>
                                <?php echo $viewStatus->getAttribute('completed_lesson') ?>
                            <?php else : ?>
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            <?php endif ?>
                        </td>
                        <?php if($group->getComplianceViewGroup()->getName() == 'exercise') : ?>
                            <td class="minutes">
                                <?php echo $viewStatus->getAttribute('cardio_minutes') ?>
                            </td>
                            <td class="minutes">
                                <?php echo $viewStatus->getAttribute('strength_minutes') ?>
                            </td>
                        <?php else : ?>
                            <td class="comment">
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                        <?php endif ?>
                        <td class="points <?php echo $class ?>">
                            <?php echo $viewStatus->getPoints() ?>
                        </td>
                        <td class="links text-center">
                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                <div><?php echo $link->getHTML() ?></div>
                            <?php endforeach ?>
                        </td>
                    </tr>
                    <?php $i++ ?>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();

            $points = $group->getPoints();
            $target = $group->getComplianceViewGroup()->getPointsRequiredForCompliance();

            $pct = $points / $target;

            $class = $classFor($pct);
            ?>
            <tr class="picker closed">
                <td class="name" colspan="2">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points <?php echo $class ?>">
                    <strong><?php echo $points ?></strong><br/>
                    points
                </td>
                <td class="pct">
                    <?php if($name == 'Activity Minutes') : ?>
                        <a href="/compliance_programs/localAction?id=774&local_action=fitness_logs" target="_blank">Log Activity Minutes</a>
                    <?php elseif($name == 'E-learning') : ?>
                        <a href="/content/9420?action=lessonManager&tab_alias=nutrition_shape" target="_blank">View/Do Lessons</a>
                    <?php elseif($name == 'Wellness Challenges') : ?>
                        <a href="/wms2/resources/wellness-challenges" target="_blank">View Details</a>
                    <?php elseif($name == 'Wellness Workshops' || $name == "Documentary") : ?>
                        <a href="/resources/7712/Cheat%20Sheet%20-%20New%20Point%20Options%20.pdf" target="_blank">View Details</a>
                    <?php elseif($name == 'Book Club') : ?>
                        <a href="/content/shape-paper-documents" target="_blank">View Details</a>
                    <?php elseif($name == 'Wellness Classes') : ?>
                        <a href="/resources/7934/FF_BR July-Oct 2016.pdf" target="_blank">View Details</a>
                    <?php endif ?>
                </td>
            </tr>
            <tr class="details closed">
                <td colspan="5">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };

        ?>

        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
                text-align: center;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 150px;
            }

            .details-table .comment {
                width: 160px;
            }

            .details-table .minutes {
                padding-left: 30px;
                text-align: center;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1.4em;
            }

            #total_points {
                display: inline-block;
                height: 100%;
                margin-top: 46%;
                font-size: 1.3em;
            }

            #header-text {
                font-weight: bold;
                font-size: 1.1em;
            }

            <?php if($status->getUser()->insurancetype) : ?>
            .screening-link { display:none; }
            .view-hra .links a { display:none; }
            <?php endif ?>
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>SFW WELLNESS <small>PROGRAM</small></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">

                <style type="text/css">
                    .view-screening { display:none; }
                </style>

                <div id="header-text">
                    <p>You must complete at least 20 points between July 1, 2016 and February 28, 2017 in order to stay compliant.</p>

                    <p>You must have 2 consultations with your Wellness Coach - one between July 1, 2016 and October 31, 2016,
                        and another between November 1, 2016 and February 28, 2017.</p>

                    <p>At least one consultation must be done in person.</p>

                    <p>10 additional points are required to earn the $50.00 Wellness Bonus.</p>

                    <?php echo $status->getAttribute('coach_name') ? "<p>My coach is {$status->getAttribute('coach_name')}</p>" : '' ?>

                    <p><a href="/resources/7715/Cheat%20Sheet%20-%20How%20to%20Earn%20Your%20Points.pdf" target="_blank">How do I earn my points?</a></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" <?php echo !sfConfig::get('app_wms2') ? 'style="margin-top: 250px;"' : '' ?>>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <div id="total_points">
                                                Total Points <br />
                                                <?php echo $status->getPoints(); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                $status->getStatus(),
                                                'SFW<br/>Wellness<br/>Program'
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                $status->getAttribute('bonus_status'),
                                                '$50<br/>Wellness<br/>Bonus'
                                            ) ?>
                                            <br/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th class="points">Points</th>
                        <th class="text-center"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Wellness Consultations', $status->getComplianceViewGroupStatus('consultation')) ?>
                    <?php echo $tableRow('Wellness Classes', $status->getComplianceViewGroupStatus('classes')) ?>
                    <?php echo $tableRow('Wellness Challenges', $status->getComplianceViewGroupStatus('challenges')) ?>
                    <?php echo $tableRow('E-learning', $status->getComplianceViewGroupStatus('elearning')) ?>
                    <?php echo $tableRow('Activity Minutes', $status->getComplianceViewGroupStatus('exercise')) ?>
                    <?php echo $tableRow('Wellness Workshops', $status->getComplianceViewGroupStatus('workshops')) ?>
                    <?php echo $tableRow('Book Club', $status->getComplianceViewGroupStatus('book_club')) ?>
                    <?php echo $tableRow("Documentary", $status->getComplianceViewGroupStatus('documentary')) ?>
                    <?php echo $tableRow('Extra Wellness Items', $status->getComplianceViewGroupStatus('extra_wellness_items')) ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());
            });
        </script>
        <?php
    }
}

