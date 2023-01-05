<?php

class CompleteCoreELearningLessonsComplianceView extends CompleteELearningGroupSet
{
    public function __construct($startDate = null, $endDate = null)
    {
        parent::__construct($startDate, $endDate, 'core_lessons');
    }

    public function getDefaultReportName()
    {
        return 'Complete Core eLearning Lessons';
    }

    public function getDefaultName()
    {
        return 'complete_core_elearning';
    }
}