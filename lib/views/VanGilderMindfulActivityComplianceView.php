<?php
class VanGilderMindfulActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::MINDFUL_ACTIVITY);
    }

    public function getStatus(User $user)
    {

        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $monthDaysCompliant = array();
        $monthMinutes = array();
        foreach($records as $record) {
            $month = date('Ym', $record->getDate('U'));

            if(!isset($monthDaysCompliant[$month])) {
                $monthDaysCompliant[$month] = 0;
            }

            if(!isset($monthMinutes[$month])) {
                $monthMinutes[$month] = 0;
            }

            $answers = $record->getQuestionAnswers();

            if(isset($answers[ActivityTrackerQuestion::MINUTES])) {
                $numberOfMinutes = $answers[ActivityTrackerQuestion::MINUTES]->getAnswer();

                if($numberOfMinutes >= 30) {
                    $monthDaysCompliant[$month]++;
                }

                $monthMinutes[$month] += $numberOfMinutes;
            }
        }

        // calculate total points
        $totalPoints = 0;
        $numberOfMonthsGoalMet = 0;
        foreach($monthMinutes as $month => $minutes) {
            $pointsForThisMonth = (int) ($minutes / 30);

            if($pointsForThisMonth > 15) {
                $pointsForThisMonth = 15;
            }

            $totalPoints += $pointsForThisMonth;
        }

        // calculate total compliance
        $numberOfMonthsCompliant = 0;
        foreach($monthDaysCompliant as $month => $daysCompliant) {
            if($daysCompliant >= 12) {
                $numberOfMonthsCompliant++;
            }
        }

        if($numberOfMonthsCompliant >= 2) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Done.');
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, 'Not Done.');
        }
    }
}

