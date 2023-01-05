<?php
class MinutesBasedMindfulActivityComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::MINDFUL_ACTIVITY);
    }

    public function __construct($startDate, $endDate, $minutesPerPoint)
    {
        parent::__construct($startDate, $endDate);

        $this->minutesPerPoint = $minutesPerPoint;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $minutes = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[ActivityTrackerQuestion::MINUTES])) {
                $minutes += $answers[ActivityTrackerQuestion::MINUTES]->getAnswer();
            }
        }

        $points = floor($minutes / $this->minutesPerPoint);

        return new ComplianceViewStatus($this, null, $points);
    }

    private $minutesPerPoint;
}

