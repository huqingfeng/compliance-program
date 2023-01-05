<?php

class CompleteAdditionalELearningLessonsComplianceView extends CompleteELearningLessonsComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerLesson = null)
    {
        parent::__construct($startDate, $endDate);

        $this->setPointsPerLesson($pointsPerLesson);
        $this->numberToComplete = 1;
    }

    public function addIgnorableGroup($alias)
    {
        $this->ignoreGroups[] = $alias;
    }

    public function addIgnorableLesson($lessonId)
    {
        $this->ignoreLessons[] = $lessonId;
    }

    public function getDefaultReportName()
    {
        return 'Complete Additional eLearning Lessons';
    }

    public function getDefaultName()
    {
        return 'complete_elearning_additonal';
    }

    public function getMaximumNumberOfIneligibleLessonIDs(User $user = null)
    {
        return $this->maxInelig;
    }

    public function setMaximumNumberOfIneligibleLessonIDs($number)
    {
        $this->maxInelig = $number;
    }

    public function getIneligibleLessonIDs(User $user = null)
    {
        $requiredView = $this->getRequiredView();

        if(!$user) {
            $user = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        }

        $ids = $requiredView === null ? array() : $requiredView->getEligibleLessonIDs($user);

        foreach($this->ignoreLessons as $ignoreId) {
            $ids[] = $ignoreId;
        }

        foreach($this->ignoreGroups as $groupAlias) {
            $ignoreView = new CompleteELearningGroupSet($this->getStartDate(), $this->getEndDate(), $groupAlias);
            $ignoreView->setComplianceViewGroup($this->getComplianceViewGroup());

            foreach($ignoreView->getEligibleLessonIDs($user) as $lessonId) {
                $ids[] = $lessonId;
            }
        }

        return $ids;
    }

    public function getRequiredView()
    {
        if($this->requiredAlias === null) {
            return null;
        } else {
            $requiredView = new CompleteELearningGroupSet($this->getStartDate(), $this->getEndDate(), $this->requiredAlias);
            $requiredView->setComplianceViewGroup($this->getComplianceViewGroup());

            return $requiredView;
        }
    }

    public function getEligibleLessonIDs(User $user = null)
    {
        return null; // all are eligible
    }

    /**
     * Sets the alias that is used to construct the required elearning view.
     *
     * If you don't want to consider the required view, pass null.
     *
     * @param $requiredAlias
     */
    public function setRequiredAlias($requiredAlias)
    {
        $this->requiredAlias = $requiredAlias;
    }

    public function setNumberToComplete($number)
    {
        $this->numberToComplete = $number;
    }

    public function getNumberToComplete()
    {
        return $this->numberToComplete;
    }

    private $requiredAlias = 'required';
    private $ignoreGroups = array();
    private $ignoreLessons = array();
    private $maxInelig = null;
}