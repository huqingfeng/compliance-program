<?php

class CompleteRequiredELearningLessonsComplianceView extends CompleteELearningGroupSet
{
    public function __construct($startDate = null, $endDate = null)
    {
        parent::__construct($startDate, $endDate, 'required');
    }

    public function getDefaultReportName()
    {
        return 'Complete Required eLearning Lessons';
    }

    public function getDefaultName()
    {
        return 'complete_elearning_required';
    }
}