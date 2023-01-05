<?php

class CompleteELearningLessonComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, ELearningLesson_v2 $lesson, $linkEnabled = true)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
        $this->lesson = $lesson;

        $quizIds = $lesson->getQuizIDs(false);

        $quizId = reset($quizIds);

        if ($linkEnabled){
            $this->addLink(new Link('Lesson', '/content/9420?action=displayQuiz&quiz_id='.$quizId));
        }
    }

    public function getDefaultReportName()
    {
        return $this->lesson->getName();
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceStatus::COMPLIANT ? 'Complete '.$this->lesson->getName() : null;
    }

    public function getDefaultName()
    {
        return 'elearning_lesson_'.$this->lesson->getID();
    }

    public function getStatus(User $user)
    {
        $completedLesson = ELearningLessonCompletion_v2::getLessonCompleted($this->lesson, $user, $this->getStartDate(), $this->getEndDate());
        
        if($completedLesson) {
            return new ComplianceViewStatus($this, $completedLesson ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT, null, $completedLesson->getCreationDate());
        } else {
            return new ComplianceViewStatus($this, $completedLesson ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT, null, null);
        }
    }

    private $lesson;
}