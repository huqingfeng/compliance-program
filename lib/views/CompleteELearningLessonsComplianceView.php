<?php
require_once sfConfig::get('sf_root_dir').
    '/apps/frontend/modules/legacy/legacy_lib/content/flash_loading/flashQuiz.lib.php';


class CompleteELearningLessonsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate = null, $endDate = null, $eligibleLessonIDs = null, $numberRequired = null, $ineligibleLessonIDs = null, $pointsPerLesson = null)
    {
        $this->ineligibleLessonIDs = $ineligibleLessonIDs;
        $this->eligibleLessonIDs = $eligibleLessonIDs;

        $this->setAllowOptionalDates(true);

        if($startDate !== null) $this->setStartDate($startDate);
        if($endDate !== null) $this->setEndDate($endDate);

        $this->numberRequired = $numberRequired;
        $this->pointsPerLesson = $pointsPerLesson;

        $this->addLink(new Link('e-Learning Center', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $this->defaultName = $this->numberRequired.'';
        $this->defaultName .= $this->eligibleLessonIDs === null || is_callable($this->eligibleLessonIDs) ? '*' : implode('|', $this->eligibleLessonIDs);
    }

    public function getDefaultName()
    {
        return 'complete_elearning_lessons_'.$this->defaultName;
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning Lessons';
    }

    public function setPointsPerLesson($v)
    {
        $this->pointsPerLesson = $v;

        return $this;
    }

    public function setForceAllowPointsOverride()
    {
        $this->forceAllowPointOverride = true;
    }

    public function allowPointsOverride()
    {
        return $this->forceAllowPointOverride || parent::allowPointsOverride();
    }

    public function setAllowPastCompleted($b)
    {
        $this->allowPastCompleted = $b;

        return $this;
    }

    public function getPointsPerLesson()
    {
        return $this->pointsPerLesson;
    }

    public function getMaximumNumberOfPoints()
    {
        return !$this->maxConfigured && $this->pointsPerLesson !== null && $this->getEligibleLessonIDs() !== null ?
            count($this->getEligibleLessonIDs()) * $this->pointsPerLesson : parent::getMaximumNumberOfPoints();
    }

    public function getEligibleLessonIDs(User $user = null)
    {
        if(is_callable($this->eligibleLessonIDs)) {
            $this->eligibleLessonIDs = call_user_func($this->eligibleLessonIDs, $user);
        }

        return $this->eligibleLessonIDs;
    }

    protected function getNumberToComplete()
    {
        return $this->numberRequired === null ?
            count($this->getEligibleLessonIDs()) : $this->numberRequired;
    }

    public function setMaximumNumberOfPoints($value)
    {
        parent::setMaximumNumberOfPoints($value);

        $this->maxConfigured = true;

        return $this;
    }

    public function setNumberRequired($number)
    {
        $this->numberRequired = $number;

        return $this;
    }

    /**
     * The lessons that do not count towards the total.
     *
     * @return array an array of lesson ids or null if all should count.
     */
    public function getIneligibleLessonIDs(User $user = null)
    {
        return $this->ineligibleLessonIDs;
    }

    public function getMaximumNumberOfIneligibleLessonIDs(User $user = null)
    {
        return null;
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                return sprintf(
                    'Complete %s eligible eLearning %s',
                    $this->getNumberToComplete(), $this->getNumberToComplete() == 1 ? 'lesson' : 'lessons'
                );
            case ComplianceViewStatus::PARTIALLY_COMPLIANT:
                return
                    'Complete 1 eligible eLearning lesson';
        }

        return null;
    }

    public function getStatus(User $user)
    {
        $eligibleLessonIDs = $this->getEligibleLessonIDs($user);
        $numberRequired = $this->getNumberToComplete();
        $nocredit = $this->getIneligibleLessonIDs($user);
        $maxNoCredit = $this->getMaximumNumberOfIneligibleLessonIDs($user);
        if(!is_array($nocredit)) $nocredit = array();
        $noCreditLessonsUsed = array();

        $startDate = $this->getStartDate('U');
        $endDate = $this->getEndDate('U');

        $dateCompleted = null;
        $lessonsCompleted = array();
        $duplicates = [];
        // If the earliest completion of a lesson is not eligible in the current
        // range, and they've since taken it again, some clients do not want to give
        // credit.
        $pastCompleted = array();

        if($this->pointsPerLesson !== null) {
            $points = 0;
        } else {
            $points = null;
        }

        if (self::$userCache['user_id'] != $user->id) {
            self::$userCache['user_id'] = $user->id;
            self::$userCache['data'] = ELearningLessonCompletion_v2::getAllCompletedLessons($user, '2000-01-01', '2025-01-01', true);
        }

        foreach(self::$userCache['data'] as $record) {
            $lessonID = $record->getLesson()->getID();

            if($this->allowPastCompleted || !isset($pastCompleted[$lessonID])) {
                // ignore seconds obtained by casting to a date first
                $elearningDate = strtotime(date('Y-m-d', strtotime($record->getCreationDate())));

                if(
                    (!$startDate || $elearningDate >= $startDate) &&
                    (!$endDate || $elearningDate <= $endDate) &&
                    (!$eligibleLessonIDs || in_array($lessonID, $eligibleLessonIDs)) &&
                    !in_array($lessonID, array_keys($lessonsCompleted)) &&
                    (!in_array($lessonID, $nocredit) || ($maxNoCredit !== null && count($noCreditLessonsUsed) >= $maxNoCredit))
                ) {
                    if(in_array($lessonID, $duplicates)) {
                        continue;
                    }
                    $duplicates[] = $lessonID;

                    $lessonsCompleted[$lessonID] = array('id' => $lessonID, 'date' => $elearningDate);

                    if($pointsForLesson = $this->getPointsForLesson($lessonID)) {
                        $points = ($points === null) ? $pointsForLesson : $points + $pointsForLesson;
                    }
                } else if(
                    (!$startDate || $elearningDate >= $startDate) &&
                    (!$endDate || $elearningDate <= $endDate) &&
                    in_array($lessonID, $nocredit)
                ) {
                    $noCreditLessonsUsed[$lessonID] = true;
                }

                $pastCompleted[$lessonID] = true;
            }
        }

        if(count($lessonsCompleted) >= $numberRequired) {
            $sortedCompleted = $lessonsCompleted;

            usort($sortedCompleted, function($a, $b) {
               return $a['date'] > $b['date'];
            });

            $sortedSlice = array_slice($sortedCompleted, $numberRequired - 1, 1);

            $compliantOn = reset($sortedSlice);

            $dateCompleted = $compliantOn['date'];
        }

        if($numberRequired) {
            $compliant = $dateCompleted ? ComplianceStatus::COMPLIANT :
                (count($lessonsCompleted) ? ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
        } else {
            $compliant = ComplianceStatus::NA_COMPLIANT;
        }

        $comment = $dateCompleted === null ? null : date('m/d/Y', $dateCompleted);

        $statusObject = new ComplianceViewStatus($this, $compliant, $points, $comment);
        $statusObject->setAttribute('lessons_completed', array_keys($lessonsCompleted));
        $statusObject->setAttribute('lessons_completed_dates', $lessonsCompleted);

        return $statusObject;
    }

    protected function getPointsForLesson($lessonId)
    {
        return $this->pointsPerLesson ? $this->pointsPerLesson : null;
    }

    private $allowPastCompleted = true;
    private $ineligibleLessonIDs;
    private $eligibleLessonIDs;
    protected $numberRequired;
    private $defaultName;
    private $pointsPerLesson;
    private $maxConfigured = false;
    private $forceAllowPointOverride = false;
    private static $userCache = array('user_id' => false, 'data' => array());
}