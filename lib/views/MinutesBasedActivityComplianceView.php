<?php

class MinutesBasedActivityComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $minutesId = ActivityTrackerQuestion::MINUTES)
    {
        $this->activityId = $activityId;
        $this->minutesId = $minutesId;

        parent::__construct($startDate, $endDate);
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }
    
    public function setPointsMultiplier($multiplier)
    {
        $this->pointsMultiplier = $multiplier;
    }    

    public function setMinutesDivisorForPoints($value)
    {
        $this->pointDivisor = $value;

        return $this;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
        $totalMinutes = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->minutesId])) {
                $totalMinutes += $answers[$this->minutesId]->getAnswer();
            }
        }

        $pointsDivisor = $this->pointDivisor;
        $points = floor($totalMinutes / $this->pointDivisor) * $this->pointsMultiplier;

        return new ComplianceViewStatus($this, null, $points);
    }    

    private $minutesId;
    private $activityId;
    private $pointDivisor = 1;
    private $pointsMultiplier = 1;
}

