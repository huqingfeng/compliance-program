<?php

class CompleteELearningGroupSet extends CompleteELearningLessonsComplianceView
{
    /**
     * @param mixed $startDate
     * @param mixed $endDate
     * @param array|string $groupSetAlias
     */
    public function __construct($startDate, $endDate, $groupSetAlias)
    {
        parent::__construct($startDate, $endDate);

        $this->aliases = (array) $groupSetAlias;
        $this->emptyLinks();

        $aliases = implode('&', array_map(
                function ($item) {
                    return "tab_alias[]=$item";
                },
                $this->aliases)
        );

        $this->addLink(new Link(
            'Complete Lessons',
            "/content/9420?action=lessonManager&$aliases"
        ));
    }

    public function getEligibleLessonIDs(User $user = null)
    {
        return $this->allowAll ? null : $this->_getEligibleLessonIDs($user);
    }

    public function useAlternateCode($use = true)
    {
        $this->useAlternateCode = $use;
    }

    protected function _getEligibleLessonIDs(User $user = null)
    {
        global $_db;

        if(!$user) $user = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        $client = $user->getClient();

        static $ids = array();

        $lessons = array();

        foreach($this->aliases as $alias) {
            if(!isset($ids[$client->getID()][$alias])) {
                $ids[$client->getID()][$alias] = array();

                if($this->useAlternateCode) {
                    $lessonSets = ELearningCategorySet::getApplicableLessonSets($client, array($alias));

                    $ids[$client->getID()][$alias] = array_keys($lessonSets[$alias]);
                } else {
                    if($categorySet = ELearningCategorySet::getCategorySetByAlias($client, $alias)) {
                        $parentsRows = $_db->getResultsForQuery('
                            SELECT *
                            FROM elearning_category_set
                            WHERE parent_category_id = ?
                        ', $categorySet->getID());

                        if(count($parentsRows)) {
                            $newAliases = array();

                            foreach($parentsRows as $parentRow) {
                                $newAliases[] = $parentRow['alias'];
                            }

                            $lessonSets = ELearningCategorySet::getApplicableLessonSets($client, $newAliases);

                            foreach($lessonSets as $lessonSetAlias => $lessonSetLessons) {
                                foreach($lessonSetLessons as $lessonId => $lesson) {
                                    $ids[$client->id][$alias][] = $lessonId;
                                }
                            }
                        } else {
                            $_db->executeSelect('
                                SELECT lesson_id
                                FROM elearning_lesson_set
                                WHERE category_set_id = ?
                                AND removed = 0
                            ', $categorySet->getID());
                        }

                        while($l = $_db->getNextRow()) {
                            $ids[$client->getID()][$alias][] = $l['lesson_id'];
                        }
                    }
                }
            }

            foreach($ids[$client->getID()][$alias] as $id) {
                $lessons[$id] = true;
            }
        }

        return array_keys($lessons);
    }

    protected function getNumberToComplete()
    {
        return $this->numberRequired !== null ? $this->numberRequired : count($this->_getEligibleLessonIDs());
    }

    /**
     * Some clients want to allow any lessons to work for credit, but have a link added
     * to an individual lesson. If so, set this to true. Note that by doing this,
     * past credit is not counted.
     *
     * @param boolean $boolean
     */
    public function setAllowAllLessons($boolean)
    {
        $this->allowAll = $boolean;
        $this->setAllowPastCompleted(!$this->allowAll);

        return $this;
    }

    private $useAlternateCode = false;
    private $allowAll = false;
    private $aliases;
}