<?php

/**
 * A view that is organized around numeric values entered per day for a given question, and a threshold that must
 * be reached to earn points.
 */
class CompleteDailySummableActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($startDate, $endDate, $id, $questionId, $pointsPer, $threshold)
    {
        $this->id = $id;

        parent::__construct($startDate, $endDate);

        $this->questionId = $questionId;
        $this->pointsPer = $pointsPer;
        $this->threshold = $threshold;
    }

    public function getStatus(User $user)
    {
        $sum = 0;
        
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $entriesByDay = array();

        foreach($records as $record) {
            $recordDate = $record->getDate('Y-m-d');
              
            if (!isset($entriesByDay[$recordDate])) {
                $entriesByDay[$recordDate] = 0;
            }

            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->questionId])) {
                $entriesByDay[$recordDate] += $answers[$this->questionId]->getAnswer();
            }
        }

        foreach($entriesByDay as $numericVal) {
            if ($numericVal >= $this->threshold) {
                $sum += $this->pointsPer;
            }
        }

        return new ComplianceViewStatus($this, null, round($sum, 2));
    }

    private $id;
    private $questionId;
    private $pointsPer;
    private $threshold;
}

