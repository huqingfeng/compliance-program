<?php

use hpn\steel\query\SelectQuery;

class ShapeCoaching2018FitnessLogComplianceView extends ComplianceView
{
    public function __construct($month)
    {
        $this->month = $month;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord(ShapeCoaching2018ComplianceProgram::SHAPE_2018_FITNESS_RECORD_TYPE, true);

        $requiredMinutes = 500;

        $dates = ShapeCoaching2018ComplianceProgram::getFitnessDates();

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

class ShapeCoaching2018FitnessMinutesForm extends BaseForm
{
    public function configure()
    {
        $user = $this->getOption('user');
        $months = ShapeCoaching2018ComplianceProgram::getFitnessMonths();
        $dates = ShapeCoaching2018ComplianceProgram::getFitnessDates();
        $lastMonth = date('F', strtotime("first day of last month"));
        $thisMonth = date('F');

        $limitedMonths = array('');
        foreach($months as $month) {
            if(in_array($month, array($lastMonth, $thisMonth))
                && date('Y-m-d') >= '2018-06-01') {
                $limitedMonths[] = $month;
            }
        }

        if(count($limitedMonths) == 1) {
            $limitedMonths[] = reset($months);
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

class ShapeCoaching2018ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        return $user->hasAttribute(Attribute::COACHING_END_USER);
    }
}


class ShapeCoaching2018ComplianceProgram extends ComplianceProgram
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
        $printer = new ShapeCoaching2018ComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);
        $months = ShapeCoaching2018ComplianceProgram::getFitnessMonths();

        $printer->addStatusFieldCallback('coach_name', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('coach_name');
        });

        $printer->addStatusFieldCallback('requirement_exempt', function(ComplianceProgramStatus $status) {
            if($status->getComplianceViewStatus('requirement_exempt')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                return 'Yes';
            }

            return null;
        });

        foreach($months as $month) {
            $printer->addCallbackField('total_minutes_cardio_'.$month, function(User $user) use ($month) {
                $dates = ShapeCoaching2018ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord(self::SHAPE_2018_FITNESS_RECORD_TYPE);

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
                $dates = ShapeCoaching2018ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord(self::SHAPE_2018_FITNESS_RECORD_TYPE);

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


        $printer->setShowTotals(false);

        return $printer;
    }

    public function getHardcodedId()
    {
        return 1354;
    }

    public function getPoints($status)
    {
        $points = $status->getPoints();
        $total_points = $points >= 20 ? 100 : round(($points / 20) * 100);
        $bonus_points = 0;
        if($points >= 20) {
            $total_bonus_points = $points - 20;
            $bonus_points = $total_bonus_points >= 20 ? 100 : round(($total_bonus_points / 20) * 100);
        }
        return $total_points;
    }

    public function getBonusPoints($status)
    {
        $points = $status->getPoints();
        $total_points = $points >= 20 ? 100 : round(($points / 20) * 100);
        $bonus_points = 0;
        if($points >= 20) {
            $total_bonus_points = $points - 20;
            $bonus_points = $total_bonus_points >= 20 ? 100 : round(($total_bonus_points / 20) * 100);
        }
        return $bonus_points;
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
        return new ShapeCoaching2018WMS2Printer();
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
        $consultationGroup->setPointsRequiredForCompliance(1);

        $consultationOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationOneView->setReportName('Wellness Consultation 01');
        $consultationOneView->setName('consultation_01');
        $consultationOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationOneView);

        $consultationTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $consultationTwoView->setName('consultation_02');
        $consultationTwoView->setReportName('Wellness Consultation 02');
        $consultationTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $consultationGroup->addComplianceView($consultationTwoView);

        $this->addComplianceViewGroup($consultationGroup);


        $classesGroup = new ComplianceViewGroup('classes', 'Wellness Classes');
        $classesGroup->setPointsRequiredForCompliance(4);

        for($i = 1; $i < 19; $i++) {
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

        $elearningEndDate = '2018-07-01';

        $countedLessons = array();
        for($itemNumber = 1; $itemNumber < 11; $itemNumber++) {
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
            $fitnessView = new ShapeCoaching2018FitnessLogComplianceView($month);
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
        $bookClubGroup->setPointsRequiredForCompliance(6);


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

        $bookClubThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubThreeView->setName('book_club_03');
        $bookClubThreeView->setReportName('Book Club 03');
        $bookClubThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubThreeView);

        $bookClubFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubFourView->setName('book_club_04');
        $bookClubFourView->setReportName('Book Club 04');
        $bookClubFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubFourView);

        $bookClubFiveView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubFiveView->setName('book_club_05');
        $bookClubFiveView->setReportName('Book Club 05');
        $bookClubFiveView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubFiveView);

        $bookClubSixView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bookClubSixView->setName('book_club_06');
        $bookClubSixView->setReportName('Book Club 06');
        $bookClubSixView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));
        $bookClubGroup->addComplianceView($bookClubSixView);

        $this->addComplianceViewGroup($bookClubGroup);


        $munchMovieGroup = new ComplianceViewGroup('munch_movie', 'Munch \'n\' Movie');
        $munchMovieGroup->setPointsRequiredForCompliance(3);

        $munchMovieOneView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $munchMovieOneView->setName('munch_movie_01');
        $munchMovieOneView->setReportName('Munch Movie 01');
        $munchMovieOneView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $munchMovieGroup->addComplianceView($munchMovieOneView);

        $munchMovieTwoView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $munchMovieTwoView->setName('munch_movie_02');
        $munchMovieTwoView->setReportName('Munch Movie 02');
        $munchMovieTwoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $munchMovieGroup->addComplianceView($munchMovieTwoView);

        $munchMovieThreeView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $munchMovieThreeView->setName('munch_movie_03');
        $munchMovieThreeView->setReportName('Munch Movie 03');
        $munchMovieThreeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $munchMovieGroup->addComplianceView($munchMovieThreeView);

        $munchMovieFourView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $munchMovieFourView->setName('munch_movie_04');
        $munchMovieFourView->setReportName('Munch Movie 04');
        $munchMovieFourView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 0, 0, 0));
        $munchMovieGroup->addComplianceView($munchMovieFourView);

        $this->addComplianceViewGroup($munchMovieGroup);


        $extraGroup = new ComplianceViewGroup('extra_wellness_items', 'Extra Wellness Items');
        $extraGroup->setPointsRequiredForCompliance(5);

        for($i = 1; $i < 16; $i++) {
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
        if(is_object($mostRecentSession) && $mostRecentSession->getDate('Y-m-d') >= '2018-01-01') {
            $status->setAttribute('coach_name', $mostRecentSession->getCoachUser()->getFullName());
        }

        $elearningStartDate = '2018-07-01';
        $elearningEndDate = '2019-02-28';

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

        $totalPoints = $status->getPoints();

        if ($totalPoints >= 20) {
            $programStatus = ComplianceStatus::COMPLIANT;
        }
        
        $status->setStatus($programStatus);
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

        $fitnessForm = new ShapeCoaching2018FitnessMinutesForm(array(), array('user' => $user));
        $dates = ShapeCoaching2018ComplianceProgram::getFitnessDates();
        $dates = array_reverse($dates);
        $months = ShapeCoaching2018ComplianceProgram::getFitnessMonths();
        $months = array_reverse($months);

        if($fitnessForm->isValidForRequest($actions->getRequest())) {
            $record = $user->getNewestDataRecord(self::SHAPE_2018_FITNESS_RECORD_TYPE, true);
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


        $record = $user->getNewestDataRecord(self::SHAPE_2018_FITNESS_RECORD_TYPE);
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
                only log the current month and the month prior. In order to receive credit, you must complete 500
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
                if(date('Y-m-d', strtotime($session->created_at)) < self::SHAPE_2018_COACHING_START_DATE
                    || date('Y-m-d', strtotime($session->created_at)) > self::SHAPE_2018_COACHING_END_DATE) {
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

    const SHAPE_2018_COACHING_START_DATE = '2018-01-01';
    const SHAPE_2018_COACHING_END_DATE = '2019-05-14';

    const SHAPE_2018_FITNESS_RECORD_TYPE = 'shape_coaching_2018_new';
}


class ShapeCoaching2018WMS2Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $classForStatus = function($status, $points = 0) {
            if ($status == ComplianceStatus::COMPLIANT || $points == 40) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($status, $text, $points = 0) use ($classForStatus) {
            $class = $status === 'shape' ? 'shape' : $classForStatus($status, $points);
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

            if($target == 0) {
                $pct = 1;
            } else {
                $pct = $points / $target;
            }

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
                        <a href="/compliance_programs/localAction?id=1354&local_action=fitness_logs" target="_blank">Log Activity Minutes</a>
                    <?php elseif($name == 'E-learning') : ?>
                        <a href="/content/9420?action=lessonManager&tab_alias=nutrition_shape" target="_blank">View/Do Lessons</a>
                    <?php elseif($name == 'Wellness Challenges') : ?>
                        <a href="/wms2/resources/wellness-challenges" target="_blank">View Details</a>
                    <?php elseif($name == 'Wellness Workshops' || $name == "Munch 'n' Movie") : ?>
                        <a href="https://static.hpn.com/wms2/documents/clients/shape/Cheat_Sheet_Point_Options_2018.pdf" target="_blank">View Details</a>
                    <?php elseif($name == 'Book Club') : ?>
                        <a href="#" class="book-club" target="_blank">View Details</a>
                    <?php elseif($name == 'Wellness Classes') : ?>
                        <a href="https://static.hpn.com/wms2/documents/clients/shape/FF_BR_July_Oct_2018.pdf" target="_blank">View Details</a>
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
            /****************************************************************
 *
 * CSS Percentage Circle
 * Author: Andre Firchow
 *
*****************************************************************/
.rect-auto, .c100.p51 .slice, .c100.p52 .slice, .c100.p53 .slice, .c100.p54 .slice, .c100.p55 .slice, .c100.p56 .slice, .c100.p57 .slice, .c100.p58 .slice, .c100.p59 .slice, .c100.p60 .slice, .c100.p61 .slice, .c100.p62 .slice, .c100.p63 .slice, .c100.p64 .slice, .c100.p65 .slice, .c100.p66 .slice, .c100.p67 .slice, .c100.p68 .slice, .c100.p69 .slice, .c100.p70 .slice, .c100.p71 .slice, .c100.p72 .slice, .c100.p73 .slice, .c100.p74 .slice, .c100.p75 .slice, .c100.p76 .slice, .c100.p77 .slice, .c100.p78 .slice, .c100.p79 .slice, .c100.p80 .slice, .c100.p81 .slice, .c100.p82 .slice, .c100.p83 .slice, .c100.p84 .slice, .c100.p85 .slice, .c100.p86 .slice, .c100.p87 .slice, .c100.p88 .slice, .c100.p89 .slice, .c100.p90 .slice, .c100.p91 .slice, .c100.p92 .slice, .c100.p93 .slice, .c100.p94 .slice, .c100.p95 .slice, .c100.p96 .slice, .c100.p97 .slice, .c100.p98 .slice, .c100.p99 .slice, .c100.p100 .slice {
  clip: rect(auto, auto, auto, auto);
}

.pie, .c100 .bar, .c100.p51 .fill, .c100.p52 .fill, .c100.p53 .fill, .c100.p54 .fill, .c100.p55 .fill, .c100.p56 .fill, .c100.p57 .fill, .c100.p58 .fill, .c100.p59 .fill, .c100.p60 .fill, .c100.p61 .fill, .c100.p62 .fill, .c100.p63 .fill, .c100.p64 .fill, .c100.p65 .fill, .c100.p66 .fill, .c100.p67 .fill, .c100.p68 .fill, .c100.p69 .fill, .c100.p70 .fill, .c100.p71 .fill, .c100.p72 .fill, .c100.p73 .fill, .c100.p74 .fill, .c100.p75 .fill, .c100.p76 .fill, .c100.p77 .fill, .c100.p78 .fill, .c100.p79 .fill, .c100.p80 .fill, .c100.p81 .fill, .c100.p82 .fill, .c100.p83 .fill, .c100.p84 .fill, .c100.p85 .fill, .c100.p86 .fill, .c100.p87 .fill, .c100.p88 .fill, .c100.p89 .fill, .c100.p90 .fill, .c100.p91 .fill, .c100.p92 .fill, .c100.p93 .fill, .c100.p94 .fill, .c100.p95 .fill, .c100.p96 .fill, .c100.p97 .fill, .c100.p98 .fill, .c100.p99 .fill, .c100.p100 .fill {
  position: absolute;
  border: 0.08em solid #307bbb;
  width: 0.84em;
  height: 0.84em;
  clip: rect(0em, 0.5em, 1em, 0em);
  -webkit-border-radius: 50%;
  -moz-border-radius: 50%;
  -ms-border-radius: 50%;
  -o-border-radius: 50%;
  border-radius: 50%;
  -webkit-transform: rotate(0deg);
  -moz-transform: rotate(0deg);
  -ms-transform: rotate(0deg);
  -o-transform: rotate(0deg);
  transform: rotate(0deg);
}

.c100.p75 .fill, .c100.p75 .bar, .c100.p76 .fill, .c100.p76 .bar, .c100.p77 .fill, .c100.p77 .bar, .c100.p78 .fill, .c100.p78 .bar, .c100.p79 .fill, .c100.p79 .bar, .c100.p80 .fill, .c100.p80 .bar, .c100.p81 .fill, .c100.p81 .bar, .c100.p82 .fill, .c100.p82 .bar, .c100.p83 .fill, .c100.p83 .bar, .c100.p84 .fill, .c100.p84 .bar, .c100.p85 .fill, .c100.p85 .bar, .c100.p86 .fill, .c100.p86 .bar, .c100.p87 .fill, .c100.p87 .bar, .c100.p88 .fill, .c100.p88 .bar, .c100.p89 .fill, .c100.p89 .bar, .c100.p90 .fill, .c100.p90 .bar, .c100.p91 .fill, .c100.p91 .bar, .c100.p92 .fill, .c100.p92 .bar, .c100.p93 .fill, .c100.p93 .bar, .c100.p94 .fill, .c100.p94 .bar, .c100.p95 .fill, .c100.p95 .bar, .c100.p96 .fill, .c100.p96 .bar, .c100.p97 .fill, .c100.p97 .bar, .c100.p98 .fill, .c100.p98 .bar, .c100.p99 .fill, .c100.p99 .bar, .c100.p100 .fill, .c100.p100 .bar{
    border: 0.08em solid #74c36e !important;
}

.c100.p50 .fill, .c100.p50 .bar, .c100.p51 .fill, .c100.p51 .bar, .c100.p52 .fill, .c100.p52 .bar, .c100.p53 .fill, .c100.p53 .bar, .c100.p54 .fill, .c100.p54 .bar, .c100.p55 .fill, .c100.p55 .bar, .c100.p56 .fill, .c100.p56 .bar, .c100.p57 .fill, .c100.p57 .bar, .c100.p58 .fill, .c100.p58 .bar, .c100.p59 .fill, .c100.p59 .bar, .c100.p60 .fill, .c100.p60 .bar, .c100.p61 .fill, .c100.p61 .bar, .c100.p62 .fill, .c100.p62 .bar, .c100.p63 .fill, .c100.p63 .bar, .c100.p64 .fill, .c100.p64 .bar, .c100.p65 .fill, .c100.p65 .bar, .c100.p66 .fill, .c100.p66 .bar, .c100.p67 .fill, .c100.p67 .bar, .c100.p68 .fill, .c100.p68 .bar, .c100.p69 .fill, .c100.p69 .bar, .c100.p70 .fill, .c100.p70 .bar, .c100.p71 .fill, .c100.p71 .bar, .c100.p72 .fill, .c100.p72 .bar, .c100.p73 .fill, .c100.p73 .bar, .c100.p74 .fill, .c100.p74 .bar, .c100.p25 .fill, .c100.p25 .bar {
    border-color: #74c36e !important;
}

.c100.p0 .fill, .c100.p0 .bar, .c100.p1 .fill, .c100.p2 .bar, .c100.p3 .fill, .c100.p3 .bar, .c100.p4 .fill, .c100.p4 .bar, .c100.p5 .fill, .c100.p5 .bar, .c100.p6 .fill, .c100.p6 .bar, .c100.p7 .fill, .c100.p7 .bar, .c100.p8 .fill, .c100.p8 .bar, .c100.p9 .fill, .c100.p9 .bar, .c100.p10 .fill, .c100.p10 .bar, .c100.p11 .fill, .c100.p11 .bar, .c100.p12 .fill, .c100.p12 .bar, .c100.p13 .fill, .c100.p13 .bar, .c100.p14 .fill, .c100.p14 .bar, .c100.p15 .fill, .c100.p15 .bar, .c100.p16 .fill, .c100.p16 .bar, .c100.p17 .fill, .c100.p17 .bar, .c100.p18 .fill, .c100.p18 .bar, .c100.p19 .fill, .c100.p19 .bar, .c100.p20 .fill, .c100.p20 .bar, .c100.p21 .fill, .c100.p21 .bar, .c100.p22 .fill, .c100.p22 .bar, .c100.p23 .fill, .c100.p23 .bar, .c100.p24 .fill, .c100.p24 .bar, .c100.p25 .fill, .c100.p25 .bar, .c100.p26 .fill, .c100.p26 .bar, .c100.p27 .fill, .c100.p27 .bar, .c100.p28 .fill, .c100.p28 .bar, .c100.p29 .fill, .c100.p29 .bar, .c100.p30 .fill, .c100.p30 .bar, .c100.p31 .fill, .c100.p31 .bar, .c100.p32 .fill, .c100.p32 .bar, .c100.p33 .fill, .c100.p33 .bar, .c100.p34 .fill, .c100.p34 .bar, .c100.p35 .fill, .c100.p35 .bar, .c100.p36 .fill, .c100.p36 .bar, .c100.p37 .fill, .c100.p37 .bar, .c100.p38 .fill, .c100.p38 .bar, .c100.p39 .fill, .c100.p39 .bar, .c100.p40 .fill, .c100.p40 .bar, .c100.p41 .fill, .c100.p41 .bar, .c100.p42 .fill, .c100.p42 .bar, .c100.p43 .fill, .c100.p43 .bar, .c100.p44 .fill, .c100.p44 .bar, .c100.p45 .fill, .c100.p45 .bar, .c100.p46 .fill, .c100.p46 .bar, .c100.p47 .fill, .c100.p47 .bar, .c100.p48 .fill, .c100.p48 .bar, .c100.p49 .fill, .c100.p49 .bar {
    border-color: #74c36e !important;
}

.pie-fill, .c100.p51 .bar:after, .c100.p51 .fill, .c100.p52 .bar:after, .c100.p52 .fill, .c100.p53 .bar:after, .c100.p53 .fill, .c100.p54 .bar:after, .c100.p54 .fill, .c100.p55 .bar:after, .c100.p55 .fill, .c100.p56 .bar:after, .c100.p56 .fill, .c100.p57 .bar:after, .c100.p57 .fill, .c100.p58 .bar:after, .c100.p58 .fill, .c100.p59 .bar:after, .c100.p59 .fill, .c100.p60 .bar:after, .c100.p60 .fill, .c100.p61 .bar:after, .c100.p61 .fill, .c100.p62 .bar:after, .c100.p62 .fill, .c100.p63 .bar:after, .c100.p63 .fill, .c100.p64 .bar:after, .c100.p64 .fill, .c100.p65 .bar:after, .c100.p65 .fill, .c100.p66 .bar:after, .c100.p66 .fill, .c100.p67 .bar:after, .c100.p67 .fill, .c100.p68 .bar:after, .c100.p68 .fill, .c100.p69 .bar:after, .c100.p69 .fill, .c100.p70 .bar:after, .c100.p70 .fill, .c100.p71 .bar:after, .c100.p71 .fill, .c100.p72 .bar:after, .c100.p72 .fill, .c100.p73 .bar:after, .c100.p73 .fill, .c100.p74 .bar:after, .c100.p74 .fill, .c100.p75 .bar:after, .c100.p75 .fill, .c100.p76 .bar:after, .c100.p76 .fill, .c100.p77 .bar:after, .c100.p77 .fill, .c100.p78 .bar:after, .c100.p78 .fill, .c100.p79 .bar:after, .c100.p79 .fill, .c100.p80 .bar:after, .c100.p80 .fill, .c100.p81 .bar:after, .c100.p81 .fill, .c100.p82 .bar:after, .c100.p82 .fill, .c100.p83 .bar:after, .c100.p83 .fill, .c100.p84 .bar:after, .c100.p84 .fill, .c100.p85 .bar:after, .c100.p85 .fill, .c100.p86 .bar:after, .c100.p86 .fill, .c100.p87 .bar:after, .c100.p87 .fill, .c100.p88 .bar:after, .c100.p88 .fill, .c100.p89 .bar:after, .c100.p89 .fill, .c100.p90 .bar:after, .c100.p90 .fill, .c100.p91 .bar:after, .c100.p91 .fill, .c100.p92 .bar:after, .c100.p92 .fill, .c100.p93 .bar:after, .c100.p93 .fill, .c100.p94 .bar:after, .c100.p94 .fill, .c100.p95 .bar:after, .c100.p95 .fill, .c100.p96 .bar:after, .c100.p96 .fill, .c100.p97 .bar:after, .c100.p97 .fill, .c100.p98 .bar:after, .c100.p98 .fill, .c100.p99 .bar:after, .c100.p99 .fill, .c100.p100 .bar:after, .c100.p100 .fill {
  -webkit-transform: rotate(180deg);
  -moz-transform: rotate(180deg);
  -ms-transform: rotate(180deg);
  -o-transform: rotate(180deg);
  transform: rotate(180deg);
}

.c100 {
  position: relative;
  font-size: 120px;
  width: 1em;
  height: 1em;
  -webkit-border-radius: 50%;
  -moz-border-radius: 50%;
  -ms-border-radius: 50%;
  -o-border-radius: 50%;
  border-radius: 50%;
  float: left;
  margin: 0 0.1em 0.1em 0;
  background-color: #cccccc;
}
.c100 *, .c100 *:before, .c100 *:after {
  -webkit-box-sizing: content-box;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
}
.c100.center {
  float: none;
  margin: 0 auto;
}
.c100.big {
  font-size: 240px;
}
.c100.small {
  font-size: 80px;
}
.c100 > span {
  position: absolute;
  width: 100%;
  z-index: 1;
  left: 0;
  top: 0;
  width: 5em;
  line-height: 5em;
  font-size: 0.2em;
  color: #cccccc;
  display: block;
  text-align: center;
  white-space: nowrap;
  -webkit-transition-property: all;
  -moz-transition-property: all;
  -o-transition-property: all;
  transition-property: all;
  -webkit-transition-duration: 0.2s;
  -moz-transition-duration: 0.2s;
  -o-transition-duration: 0.2s;
  transition-duration: 0.2s;
  -webkit-transition-timing-function: ease-out;
  -moz-transition-timing-function: ease-out;
  -o-transition-timing-function: ease-out;
  transition-timing-function: ease-out;
}
.c100:after {
  position: absolute;
  top: 0.08em;
  left: 0.08em;
  display: block;
  content: " ";
  -webkit-border-radius: 50%;
  -moz-border-radius: 50%;
  -ms-border-radius: 50%;
  -o-border-radius: 50%;
  border-radius: 50%;
  background-color: whitesmoke;
  width: 0.84em;
  height: 0.84em;
  -webkit-transition-property: all;
  -moz-transition-property: all;
  -o-transition-property: all;
  transition-property: all;
  -webkit-transition-duration: 0.2s;
  -moz-transition-duration: 0.2s;
  -o-transition-duration: 0.2s;
  transition-duration: 0.2s;
  -webkit-transition-timing-function: ease-in;
  -moz-transition-timing-function: ease-in;
  -o-transition-timing-function: ease-in;
  transition-timing-function: ease-in;
}
.c100 .slice {
  position: absolute;
  width: 1em;
  height: 1em;
  clip: rect(0em, 1em, 1em, 0.5em);
}
.c100.p1 .bar {
  -webkit-transform: rotate(3.6deg);
  -moz-transform: rotate(3.6deg);
  -ms-transform: rotate(3.6deg);
  -o-transform: rotate(3.6deg);
  transform: rotate(3.6deg);
}
.c100.p2 .bar {
  -webkit-transform: rotate(7.2deg);
  -moz-transform: rotate(7.2deg);
  -ms-transform: rotate(7.2deg);
  -o-transform: rotate(7.2deg);
  transform: rotate(7.2deg);
}
.c100.p3 .bar {
  -webkit-transform: rotate(10.8deg);
  -moz-transform: rotate(10.8deg);
  -ms-transform: rotate(10.8deg);
  -o-transform: rotate(10.8deg);
  transform: rotate(10.8deg);
}
.c100.p4 .bar {
  -webkit-transform: rotate(14.4deg);
  -moz-transform: rotate(14.4deg);
  -ms-transform: rotate(14.4deg);
  -o-transform: rotate(14.4deg);
  transform: rotate(14.4deg);
}
.c100.p5 .bar {
  -webkit-transform: rotate(18deg);
  -moz-transform: rotate(18deg);
  -ms-transform: rotate(18deg);
  -o-transform: rotate(18deg);
  transform: rotate(18deg);
}
.c100.p6 .bar {
  -webkit-transform: rotate(21.6deg);
  -moz-transform: rotate(21.6deg);
  -ms-transform: rotate(21.6deg);
  -o-transform: rotate(21.6deg);
  transform: rotate(21.6deg);
}
.c100.p7 .bar {
  -webkit-transform: rotate(25.2deg);
  -moz-transform: rotate(25.2deg);
  -ms-transform: rotate(25.2deg);
  -o-transform: rotate(25.2deg);
  transform: rotate(25.2deg);
}
.c100.p8 .bar {
  -webkit-transform: rotate(28.8deg);
  -moz-transform: rotate(28.8deg);
  -ms-transform: rotate(28.8deg);
  -o-transform: rotate(28.8deg);
  transform: rotate(28.8deg);
}
.c100.p9 .bar {
  -webkit-transform: rotate(32.4deg);
  -moz-transform: rotate(32.4deg);
  -ms-transform: rotate(32.4deg);
  -o-transform: rotate(32.4deg);
  transform: rotate(32.4deg);
}
.c100.p10 .bar {
  -webkit-transform: rotate(36deg);
  -moz-transform: rotate(36deg);
  -ms-transform: rotate(36deg);
  -o-transform: rotate(36deg);
  transform: rotate(36deg);
}
.c100.p11 .bar {
  -webkit-transform: rotate(39.6deg);
  -moz-transform: rotate(39.6deg);
  -ms-transform: rotate(39.6deg);
  -o-transform: rotate(39.6deg);
  transform: rotate(39.6deg);
}
.c100.p12 .bar {
  -webkit-transform: rotate(43.2deg);
  -moz-transform: rotate(43.2deg);
  -ms-transform: rotate(43.2deg);
  -o-transform: rotate(43.2deg);
  transform: rotate(43.2deg);
}
.c100.p13 .bar {
  -webkit-transform: rotate(46.8deg);
  -moz-transform: rotate(46.8deg);
  -ms-transform: rotate(46.8deg);
  -o-transform: rotate(46.8deg);
  transform: rotate(46.8deg);
}
.c100.p14 .bar {
  -webkit-transform: rotate(50.4deg);
  -moz-transform: rotate(50.4deg);
  -ms-transform: rotate(50.4deg);
  -o-transform: rotate(50.4deg);
  transform: rotate(50.4deg);
}
.c100.p15 .bar {
  -webkit-transform: rotate(54deg);
  -moz-transform: rotate(54deg);
  -ms-transform: rotate(54deg);
  -o-transform: rotate(54deg);
  transform: rotate(54deg);
}
.c100.p16 .bar {
  -webkit-transform: rotate(57.6deg);
  -moz-transform: rotate(57.6deg);
  -ms-transform: rotate(57.6deg);
  -o-transform: rotate(57.6deg);
  transform: rotate(57.6deg);
}
.c100.p17 .bar {
  -webkit-transform: rotate(61.2deg);
  -moz-transform: rotate(61.2deg);
  -ms-transform: rotate(61.2deg);
  -o-transform: rotate(61.2deg);
  transform: rotate(61.2deg);
}
.c100.p18 .bar {
  -webkit-transform: rotate(64.8deg);
  -moz-transform: rotate(64.8deg);
  -ms-transform: rotate(64.8deg);
  -o-transform: rotate(64.8deg);
  transform: rotate(64.8deg);
}
.c100.p19 .bar {
  -webkit-transform: rotate(68.4deg);
  -moz-transform: rotate(68.4deg);
  -ms-transform: rotate(68.4deg);
  -o-transform: rotate(68.4deg);
  transform: rotate(68.4deg);
}
.c100.p20 .bar {
  -webkit-transform: rotate(72deg);
  -moz-transform: rotate(72deg);
  -ms-transform: rotate(72deg);
  -o-transform: rotate(72deg);
  transform: rotate(72deg);
}
.c100.p21 .bar {
  -webkit-transform: rotate(75.6deg);
  -moz-transform: rotate(75.6deg);
  -ms-transform: rotate(75.6deg);
  -o-transform: rotate(75.6deg);
  transform: rotate(75.6deg);
}
.c100.p22 .bar {
  -webkit-transform: rotate(79.2deg);
  -moz-transform: rotate(79.2deg);
  -ms-transform: rotate(79.2deg);
  -o-transform: rotate(79.2deg);
  transform: rotate(79.2deg);
}
.c100.p23 .bar {
  -webkit-transform: rotate(82.8deg);
  -moz-transform: rotate(82.8deg);
  -ms-transform: rotate(82.8deg);
  -o-transform: rotate(82.8deg);
  transform: rotate(82.8deg);
}
.c100.p24 .bar {
  -webkit-transform: rotate(86.4deg);
  -moz-transform: rotate(86.4deg);
  -ms-transform: rotate(86.4deg);
  -o-transform: rotate(86.4deg);
  transform: rotate(86.4deg);
}
.c100.p25 .bar {
  -webkit-transform: rotate(90deg);
  -moz-transform: rotate(90deg);
  -ms-transform: rotate(90deg);
  -o-transform: rotate(90deg);
  transform: rotate(90deg);
}
.c100.p26 .bar {
  -webkit-transform: rotate(93.6deg);
  -moz-transform: rotate(93.6deg);
  -ms-transform: rotate(93.6deg);
  -o-transform: rotate(93.6deg);
  transform: rotate(93.6deg);
}
.c100.p27 .bar {
  -webkit-transform: rotate(97.2deg);
  -moz-transform: rotate(97.2deg);
  -ms-transform: rotate(97.2deg);
  -o-transform: rotate(97.2deg);
  transform: rotate(97.2deg);
}
.c100.p28 .bar {
  -webkit-transform: rotate(100.8deg);
  -moz-transform: rotate(100.8deg);
  -ms-transform: rotate(100.8deg);
  -o-transform: rotate(100.8deg);
  transform: rotate(100.8deg);
}
.c100.p29 .bar {
  -webkit-transform: rotate(104.4deg);
  -moz-transform: rotate(104.4deg);
  -ms-transform: rotate(104.4deg);
  -o-transform: rotate(104.4deg);
  transform: rotate(104.4deg);
}
.c100.p30 .bar {
  -webkit-transform: rotate(108deg);
  -moz-transform: rotate(108deg);
  -ms-transform: rotate(108deg);
  -o-transform: rotate(108deg);
  transform: rotate(108deg);
}
.c100.p31 .bar {
  -webkit-transform: rotate(111.6deg);
  -moz-transform: rotate(111.6deg);
  -ms-transform: rotate(111.6deg);
  -o-transform: rotate(111.6deg);
  transform: rotate(111.6deg);
}
.c100.p32 .bar {
  -webkit-transform: rotate(115.2deg);
  -moz-transform: rotate(115.2deg);
  -ms-transform: rotate(115.2deg);
  -o-transform: rotate(115.2deg);
  transform: rotate(115.2deg);
}
.c100.p33 .bar {
  -webkit-transform: rotate(118.8deg);
  -moz-transform: rotate(118.8deg);
  -ms-transform: rotate(118.8deg);
  -o-transform: rotate(118.8deg);
  transform: rotate(118.8deg);
}
.c100.p34 .bar {
  -webkit-transform: rotate(122.4deg);
  -moz-transform: rotate(122.4deg);
  -ms-transform: rotate(122.4deg);
  -o-transform: rotate(122.4deg);
  transform: rotate(122.4deg);
}
.c100.p35 .bar {
  -webkit-transform: rotate(126deg);
  -moz-transform: rotate(126deg);
  -ms-transform: rotate(126deg);
  -o-transform: rotate(126deg);
  transform: rotate(126deg);
}
.c100.p36 .bar {
  -webkit-transform: rotate(129.6deg);
  -moz-transform: rotate(129.6deg);
  -ms-transform: rotate(129.6deg);
  -o-transform: rotate(129.6deg);
  transform: rotate(129.6deg);
}
.c100.p37 .bar {
  -webkit-transform: rotate(133.2deg);
  -moz-transform: rotate(133.2deg);
  -ms-transform: rotate(133.2deg);
  -o-transform: rotate(133.2deg);
  transform: rotate(133.2deg);
}
.c100.p38 .bar {
  -webkit-transform: rotate(136.8deg);
  -moz-transform: rotate(136.8deg);
  -ms-transform: rotate(136.8deg);
  -o-transform: rotate(136.8deg);
  transform: rotate(136.8deg);
}
.c100.p39 .bar {
  -webkit-transform: rotate(140.4deg);
  -moz-transform: rotate(140.4deg);
  -ms-transform: rotate(140.4deg);
  -o-transform: rotate(140.4deg);
  transform: rotate(140.4deg);
}
.c100.p40 .bar {
  -webkit-transform: rotate(144deg);
  -moz-transform: rotate(144deg);
  -ms-transform: rotate(144deg);
  -o-transform: rotate(144deg);
  transform: rotate(144deg);
}
.c100.p41 .bar {
  -webkit-transform: rotate(147.6deg);
  -moz-transform: rotate(147.6deg);
  -ms-transform: rotate(147.6deg);
  -o-transform: rotate(147.6deg);
  transform: rotate(147.6deg);
}
.c100.p42 .bar {
  -webkit-transform: rotate(151.2deg);
  -moz-transform: rotate(151.2deg);
  -ms-transform: rotate(151.2deg);
  -o-transform: rotate(151.2deg);
  transform: rotate(151.2deg);
}
.c100.p43 .bar {
  -webkit-transform: rotate(154.8deg);
  -moz-transform: rotate(154.8deg);
  -ms-transform: rotate(154.8deg);
  -o-transform: rotate(154.8deg);
  transform: rotate(154.8deg);
}
.c100.p44 .bar {
  -webkit-transform: rotate(158.4deg);
  -moz-transform: rotate(158.4deg);
  -ms-transform: rotate(158.4deg);
  -o-transform: rotate(158.4deg);
  transform: rotate(158.4deg);
}
.c100.p45 .bar {
  -webkit-transform: rotate(162deg);
  -moz-transform: rotate(162deg);
  -ms-transform: rotate(162deg);
  -o-transform: rotate(162deg);
  transform: rotate(162deg);
}
.c100.p46 .bar {
  -webkit-transform: rotate(165.6deg);
  -moz-transform: rotate(165.6deg);
  -ms-transform: rotate(165.6deg);
  -o-transform: rotate(165.6deg);
  transform: rotate(165.6deg);
}
.c100.p47 .bar {
  -webkit-transform: rotate(169.2deg);
  -moz-transform: rotate(169.2deg);
  -ms-transform: rotate(169.2deg);
  -o-transform: rotate(169.2deg);
  transform: rotate(169.2deg);
}
.c100.p48 .bar {
  -webkit-transform: rotate(172.8deg);
  -moz-transform: rotate(172.8deg);
  -ms-transform: rotate(172.8deg);
  -o-transform: rotate(172.8deg);
  transform: rotate(172.8deg);
}
.c100.p49 .bar {
  -webkit-transform: rotate(176.4deg);
  -moz-transform: rotate(176.4deg);
  -ms-transform: rotate(176.4deg);
  -o-transform: rotate(176.4deg);
  transform: rotate(176.4deg);
}
.c100.p50 .bar {
  -webkit-transform: rotate(180deg);
  -moz-transform: rotate(180deg);
  -ms-transform: rotate(180deg);
  -o-transform: rotate(180deg);
  transform: rotate(180deg);
}
.c100.p51 .bar {
  -webkit-transform: rotate(183.6deg);
  -moz-transform: rotate(183.6deg);
  -ms-transform: rotate(183.6deg);
  -o-transform: rotate(183.6deg);
  transform: rotate(183.6deg);
}
.c100.p52 .bar {
  -webkit-transform: rotate(187.2deg);
  -moz-transform: rotate(187.2deg);
  -ms-transform: rotate(187.2deg);
  -o-transform: rotate(187.2deg);
  transform: rotate(187.2deg);
}
.c100.p53 .bar {
  -webkit-transform: rotate(190.8deg);
  -moz-transform: rotate(190.8deg);
  -ms-transform: rotate(190.8deg);
  -o-transform: rotate(190.8deg);
  transform: rotate(190.8deg);
}
.c100.p54 .bar {
  -webkit-transform: rotate(194.4deg);
  -moz-transform: rotate(194.4deg);
  -ms-transform: rotate(194.4deg);
  -o-transform: rotate(194.4deg);
  transform: rotate(194.4deg);
}
.c100.p55 .bar {
  -webkit-transform: rotate(198deg);
  -moz-transform: rotate(198deg);
  -ms-transform: rotate(198deg);
  -o-transform: rotate(198deg);
  transform: rotate(198deg);
}
.c100.p56 .bar {
  -webkit-transform: rotate(201.6deg);
  -moz-transform: rotate(201.6deg);
  -ms-transform: rotate(201.6deg);
  -o-transform: rotate(201.6deg);
  transform: rotate(201.6deg);
}
.c100.p57 .bar {
  -webkit-transform: rotate(205.2deg);
  -moz-transform: rotate(205.2deg);
  -ms-transform: rotate(205.2deg);
  -o-transform: rotate(205.2deg);
  transform: rotate(205.2deg);
}
.c100.p58 .bar {
  -webkit-transform: rotate(208.8deg);
  -moz-transform: rotate(208.8deg);
  -ms-transform: rotate(208.8deg);
  -o-transform: rotate(208.8deg);
  transform: rotate(208.8deg);
}
.c100.p59 .bar {
  -webkit-transform: rotate(212.4deg);
  -moz-transform: rotate(212.4deg);
  -ms-transform: rotate(212.4deg);
  -o-transform: rotate(212.4deg);
  transform: rotate(212.4deg);
}
.c100.p60 .bar {
  -webkit-transform: rotate(216deg);
  -moz-transform: rotate(216deg);
  -ms-transform: rotate(216deg);
  -o-transform: rotate(216deg);
  transform: rotate(216deg);
}
.c100.p61 .bar {
  -webkit-transform: rotate(219.6deg);
  -moz-transform: rotate(219.6deg);
  -ms-transform: rotate(219.6deg);
  -o-transform: rotate(219.6deg);
  transform: rotate(219.6deg);
}
.c100.p62 .bar {
  -webkit-transform: rotate(223.2deg);
  -moz-transform: rotate(223.2deg);
  -ms-transform: rotate(223.2deg);
  -o-transform: rotate(223.2deg);
  transform: rotate(223.2deg);
}
.c100.p63 .bar {
  -webkit-transform: rotate(226.8deg);
  -moz-transform: rotate(226.8deg);
  -ms-transform: rotate(226.8deg);
  -o-transform: rotate(226.8deg);
  transform: rotate(226.8deg);
}
.c100.p64 .bar {
  -webkit-transform: rotate(230.4deg);
  -moz-transform: rotate(230.4deg);
  -ms-transform: rotate(230.4deg);
  -o-transform: rotate(230.4deg);
  transform: rotate(230.4deg);
}
.c100.p65 .bar {
  -webkit-transform: rotate(234deg);
  -moz-transform: rotate(234deg);
  -ms-transform: rotate(234deg);
  -o-transform: rotate(234deg);
  transform: rotate(234deg);
}
.c100.p66 .bar {
  -webkit-transform: rotate(237.6deg);
  -moz-transform: rotate(237.6deg);
  -ms-transform: rotate(237.6deg);
  -o-transform: rotate(237.6deg);
  transform: rotate(237.6deg);
}
.c100.p67 .bar {
  -webkit-transform: rotate(241.2deg);
  -moz-transform: rotate(241.2deg);
  -ms-transform: rotate(241.2deg);
  -o-transform: rotate(241.2deg);
  transform: rotate(241.2deg);
}
.c100.p68 .bar {
  -webkit-transform: rotate(244.8deg);
  -moz-transform: rotate(244.8deg);
  -ms-transform: rotate(244.8deg);
  -o-transform: rotate(244.8deg);
  transform: rotate(244.8deg);
}
.c100.p69 .bar {
  -webkit-transform: rotate(248.4deg);
  -moz-transform: rotate(248.4deg);
  -ms-transform: rotate(248.4deg);
  -o-transform: rotate(248.4deg);
  transform: rotate(248.4deg);
}
.c100.p70 .bar {
  -webkit-transform: rotate(252deg);
  -moz-transform: rotate(252deg);
  -ms-transform: rotate(252deg);
  -o-transform: rotate(252deg);
  transform: rotate(252deg);
}
.c100.p71 .bar {
  -webkit-transform: rotate(255.6deg);
  -moz-transform: rotate(255.6deg);
  -ms-transform: rotate(255.6deg);
  -o-transform: rotate(255.6deg);
  transform: rotate(255.6deg);
}
.c100.p72 .bar {
  -webkit-transform: rotate(259.2deg);
  -moz-transform: rotate(259.2deg);
  -ms-transform: rotate(259.2deg);
  -o-transform: rotate(259.2deg);
  transform: rotate(259.2deg);
}
.c100.p73 .bar {
  -webkit-transform: rotate(262.8deg);
  -moz-transform: rotate(262.8deg);
  -ms-transform: rotate(262.8deg);
  -o-transform: rotate(262.8deg);
  transform: rotate(262.8deg);
}
.c100.p74 .bar {
  -webkit-transform: rotate(266.4deg);
  -moz-transform: rotate(266.4deg);
  -ms-transform: rotate(266.4deg);
  -o-transform: rotate(266.4deg);
  transform: rotate(266.4deg);
}
.c100.p75 .bar {
  -webkit-transform: rotate(270deg);
  -moz-transform: rotate(270deg);
  -ms-transform: rotate(270deg);
  -o-transform: rotate(270deg);
  transform: rotate(270deg);
}
.c100.p76 .bar {
  -webkit-transform: rotate(273.6deg);
  -moz-transform: rotate(273.6deg);
  -ms-transform: rotate(273.6deg);
  -o-transform: rotate(273.6deg);
  transform: rotate(273.6deg);
}
.c100.p77 .bar {
  -webkit-transform: rotate(277.2deg);
  -moz-transform: rotate(277.2deg);
  -ms-transform: rotate(277.2deg);
  -o-transform: rotate(277.2deg);
  transform: rotate(277.2deg);
}
.c100.p78 .bar {
  -webkit-transform: rotate(280.8deg);
  -moz-transform: rotate(280.8deg);
  -ms-transform: rotate(280.8deg);
  -o-transform: rotate(280.8deg);
  transform: rotate(280.8deg);
}
.c100.p79 .bar {
  -webkit-transform: rotate(284.4deg);
  -moz-transform: rotate(284.4deg);
  -ms-transform: rotate(284.4deg);
  -o-transform: rotate(284.4deg);
  transform: rotate(284.4deg);
}
.c100.p80 .bar {
  -webkit-transform: rotate(288deg);
  -moz-transform: rotate(288deg);
  -ms-transform: rotate(288deg);
  -o-transform: rotate(288deg);
  transform: rotate(288deg);
}
.c100.p81 .bar {
  -webkit-transform: rotate(291.6deg);
  -moz-transform: rotate(291.6deg);
  -ms-transform: rotate(291.6deg);
  -o-transform: rotate(291.6deg);
  transform: rotate(291.6deg);
}
.c100.p82 .bar {
  -webkit-transform: rotate(295.2deg);
  -moz-transform: rotate(295.2deg);
  -ms-transform: rotate(295.2deg);
  -o-transform: rotate(295.2deg);
  transform: rotate(295.2deg);
}
.c100.p83 .bar {
  -webkit-transform: rotate(298.8deg);
  -moz-transform: rotate(298.8deg);
  -ms-transform: rotate(298.8deg);
  -o-transform: rotate(298.8deg);
  transform: rotate(298.8deg);
}
.c100.p84 .bar {
  -webkit-transform: rotate(302.4deg);
  -moz-transform: rotate(302.4deg);
  -ms-transform: rotate(302.4deg);
  -o-transform: rotate(302.4deg);
  transform: rotate(302.4deg);
}
.c100.p85 .bar {
  -webkit-transform: rotate(306deg);
  -moz-transform: rotate(306deg);
  -ms-transform: rotate(306deg);
  -o-transform: rotate(306deg);
  transform: rotate(306deg);
}
.c100.p86 .bar {
  -webkit-transform: rotate(309.6deg);
  -moz-transform: rotate(309.6deg);
  -ms-transform: rotate(309.6deg);
  -o-transform: rotate(309.6deg);
  transform: rotate(309.6deg);
}
.c100.p87 .bar {
  -webkit-transform: rotate(313.2deg);
  -moz-transform: rotate(313.2deg);
  -ms-transform: rotate(313.2deg);
  -o-transform: rotate(313.2deg);
  transform: rotate(313.2deg);
}
.c100.p88 .bar {
  -webkit-transform: rotate(316.8deg);
  -moz-transform: rotate(316.8deg);
  -ms-transform: rotate(316.8deg);
  -o-transform: rotate(316.8deg);
  transform: rotate(316.8deg);
}
.c100.p89 .bar {
  -webkit-transform: rotate(320.4deg);
  -moz-transform: rotate(320.4deg);
  -ms-transform: rotate(320.4deg);
  -o-transform: rotate(320.4deg);
  transform: rotate(320.4deg);
}
.c100.p90 .bar {
  -webkit-transform: rotate(324deg);
  -moz-transform: rotate(324deg);
  -ms-transform: rotate(324deg);
  -o-transform: rotate(324deg);
  transform: rotate(324deg);
}
.c100.p91 .bar {
  -webkit-transform: rotate(327.6deg);
  -moz-transform: rotate(327.6deg);
  -ms-transform: rotate(327.6deg);
  -o-transform: rotate(327.6deg);
  transform: rotate(327.6deg);
}
.c100.p92 .bar {
  -webkit-transform: rotate(331.2deg);
  -moz-transform: rotate(331.2deg);
  -ms-transform: rotate(331.2deg);
  -o-transform: rotate(331.2deg);
  transform: rotate(331.2deg);
}
.c100.p93 .bar {
  -webkit-transform: rotate(334.8deg);
  -moz-transform: rotate(334.8deg);
  -ms-transform: rotate(334.8deg);
  -o-transform: rotate(334.8deg);
  transform: rotate(334.8deg);
}
.c100.p94 .bar {
  -webkit-transform: rotate(338.4deg);
  -moz-transform: rotate(338.4deg);
  -ms-transform: rotate(338.4deg);
  -o-transform: rotate(338.4deg);
  transform: rotate(338.4deg);
}
.c100.p95 .bar {
  -webkit-transform: rotate(342deg);
  -moz-transform: rotate(342deg);
  -ms-transform: rotate(342deg);
  -o-transform: rotate(342deg);
  transform: rotate(342deg);
}
.c100.p96 .bar {
  -webkit-transform: rotate(345.6deg);
  -moz-transform: rotate(345.6deg);
  -ms-transform: rotate(345.6deg);
  -o-transform: rotate(345.6deg);
  transform: rotate(345.6deg);
}
.c100.p97 .bar {
  -webkit-transform: rotate(349.2deg);
  -moz-transform: rotate(349.2deg);
  -ms-transform: rotate(349.2deg);
  -o-transform: rotate(349.2deg);
  transform: rotate(349.2deg);
}
.c100.p98 .bar {
  -webkit-transform: rotate(352.8deg);
  -moz-transform: rotate(352.8deg);
  -ms-transform: rotate(352.8deg);
  -o-transform: rotate(352.8deg);
  transform: rotate(352.8deg);
}
.c100.p99 .bar {
  -webkit-transform: rotate(356.4deg);
  -moz-transform: rotate(356.4deg);
  -ms-transform: rotate(356.4deg);
  -o-transform: rotate(356.4deg);
  transform: rotate(356.4deg);
}
.c100.p100 .bar {
  -webkit-transform: rotate(360deg);
  -moz-transform: rotate(360deg);
  -ms-transform: rotate(360deg);
  -o-transform: rotate(360deg);
  transform: rotate(360deg);
}
.c100:hover {
  cursor: default;
}
.c100 > span {
    background: green;
}
/*.c100:hover > span {
  width: 3.33em;
  line-height: 3.33em;
  font-size: 0.3em;
  color: #4caf50;
}*/
/*.c100:hover:after {
  top: 0.04em;
  left: 0.04em;
  width: 0.92em;
  height: 0.92em;
}*/
.c100.dark {
  background-color: #777777;
}
.c100.dark .bar,
.c100.dark .fill {
  border-color: #c6ff00 !important;
}
.c100.dark > span {
  color: #777777;
}
.c100.dark:after {
  background-color: #666666;
}
.c100.dark:hover > span {
  color: #c6ff00;
}
.c100.green .bar, .c100.green .fill {
  border-color: #4db53c !important;
}
.c100.green:hover > span {
  color: #4db53c;
}
.c100.green.dark .bar, .c100.green.dark .fill {
  border-color: #5fd400 !important;
}
.c100.green.dark:hover > span {
  color: #5fd400;
}
.c100.orange .bar, .c100.orange .fill {
  border-color: #dd9d22 !important;
}
.c100.orange:hover > span {
  color: #dd9d22;
}
.c100.orange.dark .bar, .c100.orange.dark .fill {
  border-color: #e08833 !important;
}
.c100.orange.dark:hover > span {
  color: #e08833;
}

.c100.p100 {
    display: none;
}


.div-100 {
    display: block !important;
}

.hide-at-100 {
    display: none !important;
}

.c100 {
    width: 150px;
    height: 150px;
}
 <?php 
    $points = $status->getPoints(); 
    $total_points = $points >= 20 ? 100 : round(($points / 20) * 100);
    $bonus_points = 0;
    if($points >= 20) {
        $total_bonus_points = $points - 20;
        $bonus_points = $total_bonus_points >= 20 ? 100 : round(($total_bonus_points / 20) * 100);    
    }
?>
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
                    <p>You must complete at least 20 points between July 1, 2018 and February 28, 2019 in order to stay compliant.</p>

                    <?php echo $status->getAttribute('coach_name') ? "<p>My coach is {$status->getAttribute('coach_name')}</p>" : '' ?>

                    <p><a href="https://static.hpn.com/wms2/documents/clients/shape/Cheat_Sheet_How_to_Earn_Your_Points_2018.pdf" target="_blank">How do I earn my points?</a></p>
                </div>
            </div>
            <div class="row">
            
                <div class="col-md-12" <?php echo !sfConfig::get('app_wms2') ? 'style="margin-top: 250px;"' : '' ?>>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-3 text-center">
                            <div class="row">
                                <div class="col-md-2"></div>
                                <div class="col-md-4">
                                    <div class="row hide div-<?php echo $total_points; ?>">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <div class="circle-range">
                                                <div class="circle-range-inner circle-range-inner-<?php echo $classForStatus($status->getStatus()); ?>">
                                                    <div style="font-size: 1.3em; line-height: 1.2em;">
                                                        SFW<br/>Wellness<br/>Program<br/>
                                                        <div style="margin-top: 10px; font-size:1.5em; ">
                                                            <?php echo $status->getPoints(); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 10px;">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <div class="clearfix" style="position: relative">
                                                <div style="position: absolute; z-index: 5000; left:40px; top: 35px; font-size: 1.3em; line-height: 1.2em;" class="hide-at-<?php echo $total_points; ?>">
                                                    SFW<br>Wellness<br>Program<br/>
                                                    <div style="margin-top: 10px; font-size:1.5em; ">
                                                        <?php echo $status->getPoints(); ?>
                                                    </div>
                                                </div>
                                                <div style="font-size: 150px;" class="c100 p<?php echo $total_points; ?>">
                                                    <span></span>
                                                    <div class="slice">
                                                        <div class="bar"></div>
                                                        <div class="fill"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2"></div>
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
                    <?php echo $tableRow("Munch 'n' Movie", $status->getComplianceViewGroupStatus('munch_movie')) ?>
                    <?php echo $tableRow('Extra Wellness Items', $status->getComplianceViewGroupStatus('extra_wellness_items')) ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
                var resources = 'https://' + window.location.hostname + '/resources/paper-documents';
                var resource = resources.replace('/compliance/shape-2018/coaching-report-card', '');

                $('.book-club').attr({href: resource});
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

