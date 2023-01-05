<?php
/**
 * An activity based on mindful activity entries that uses (currently) hardcoded
 * logic based on minutes.
 */
class TimeBasedMindfulActivityComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::MINDFUL_ACTIVITY);
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

        $points = floor($minutes / 15);

        return new ComplianceViewStatus($this, null, $points);
    }
}

