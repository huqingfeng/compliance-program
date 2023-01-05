<?php

class CompleteAssignedELearningLessonsComplianceView extends CompleteELearningLessonsComplianceView
{
    public function __construct($startDate = null, $endDate = null, $eligibleLessonIDs = null, $numberRequired = null, $ineligibleLessonIDs = null, $pointsPerLesson = null)
    {
        parent::__construct($startDate, $endDate, $eligibleLessonIDs, $numberRequired, $ineligibleLessonIDs, $pointsPerLesson);

        $this->link = new Link('View Lessons', '/content/9420?action=lessonManager&tab_alias=assigned&category_title=Assigned%20By%20Health%20Coach');

        $this->emptyLinks();

        $this->addLink($this->link);
    }

    public function getEligibleLessonIDs(User $user = null)
    {
        global $_db;

        if(!$user) {
            $user = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        }

        $query = "
      SELECT elearning_lesson_id
      FROM user_assigned_elearning_lessons
      WHERE user_id = ?
    ";

        $params = array($user->id);

        if($this->alias) {
            $query .= "
        AND alias = ?
      ";

            $params[] = $this->alias;
        }

        $_db->executeSelect($query, $params);

        $lessons = array();

        while($row = $_db->getNextRow()) {
            $lessons[] = $row['elearning_lesson_id'];
        }

        return count($lessons) ? $lessons : null;
    }

    public function bindAlias($alias)
    {
        $this->alias = $alias;

        $this->link->setLink($this->link->getLink().'&assigned_alias='.$this->alias);
    }

    protected $link;
    protected $alias = null;
}