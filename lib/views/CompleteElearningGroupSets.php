<?php
/**
 * This view is used to combine multiple aliases. It should be used for point-
 * based usages with different points per alias.
 */
class CompleteELearningGroupSets extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function addGroup($alias, $pointsPerLesson)
    {
        $this->aliases[$alias] = $pointsPerLesson;

        asort($this->aliases, SORT_NUMERIC);

        if(isset($this->links[$pointsPerLesson])) {
            $this->links[$pointsPerLesson]->setLink(sprintf(
                '%s&tab_alias[]=%s&start_date=%s&end_date=%s',
                $this->links[$pointsPerLesson]->getLink(),
                $alias,
                $this->getStartDate('Y-m-d'),
                $this->getEndDate('Y-m-d')
            ));
        } else {
            $this->links[$pointsPerLesson] = new Link(sprintf(
                    'Complete Lessons (%s %s)',
                    $pointsPerLesson, $pointsPerLesson == 1 ? 'point' : 'points'
                ),
                sprintf(
                    '/content/9420?action=lessonManager&tab_alias[]=%s&start_date=%s&end_date=%s',
                    $alias,
                    $this->getStartDate('Y-m-d'),
                    $this->getEndDate('Y-m-d')
                )
            );

            $this->addLink($this->links[$pointsPerLesson]);
        }
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'elearning_group_sets';
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning Group Sets';
    }

    public function getMaximumNumberOfPoints()
    {
        if($this->hasMaximumNumberOfPoints) {
            return parent::getMaximumNumberOfPoints();
        } else {
            return array_sum($this->getLessons());
        }
    }

    public function getStatus(User $user)
    {
        $complete = array_keys(ELearningLessonCompletion_v2::getAllCompletedLessons($user, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'))); // as $lessonID => $record

        $points = 0;

        $lessons = $this->getLessons($user);

        foreach($complete as $lessonId) {
            if(isset($lessons[$lessonId])) {
                $points += $lessons[$lessonId];
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    public function setMaximumNumberOfPoints($value)
    {
        parent::setMaximumNumberOfPoints($value);

        $this->hasMaximumNumberOfPoints = $value !== null;
    }

    protected function getLessons(User $user = null)
    {
        if(!$user) {
            $user = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        }

        $lessons = array();

        $lessonSets = ELearningCategorySet::getApplicableLessonSets($user->client, array_keys($this->aliases));

        foreach($this->aliases as $alias => $points) {
            if(isset($lessonSets[$alias])) {
                foreach($lessonSets[$alias] as $l) {
                    // Since the aliases are sorted by points, the higher number comes last.
                    // So we don't have to worry about overwriting. The user will always get
                    // the most possible points if a lesson is in >= 2 groups.
                    $lessons[$l[0]->getLessonID()] = $points;
                }
            }
        }

        return $lessons;
    }

    protected $hasMaximumNumberOfPoints = false;
    protected $links = array();
    protected $aliases = array();
}