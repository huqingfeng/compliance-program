<?php

class PhysicalActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityID);
    }

    public function _setID($id)
    {
        $this->activityID = $id;
        $this->emptyLinks();
        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));

        return $this;
    }

    public function setFractionalDivisorForPoints($value)
    {
        $this->fractionalPointDivisor = $value;

        return $this;
    }

    public function setMinutesDivisorForPoints($value)
    {
        $this->pointDivisor = $value;

        return $this;
    }

    public function setPointsMultiplier($value)
    {
        $this->pointsMultiplier = $value;
    }

    /**
     * @param int $value
     * @return PhysicalActivityComplianceView
     */
    public function setMonthlyPointLimit($value)
    {
        $this->monthlyPointLimit = $value;

        return $this;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords(
            $user,
            $this->getStartDate(),
            $this->getEndDate()
        );

        $monthMinutes = array();
        $totalPoints = 0;

        foreach($records as $record) {
            $month = date('Ym', $record->getDate('U'));

            if(!isset($monthMinutes[$month])) {
                $monthMinutes[$month] = 0;
            }

            $answers = $record->getQuestionAnswers();

            if(isset($answers[ActivityTrackerQuestion::ACTIVITY], $answers[43]) && $answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer() == 'Other' && $answers[43]->getAnswer() == 'SIM Activity') {
                // This is a straightup hack for H&H to not include SIM Bonus points
                // as part of the monthly. Not sure how to best implement this yet.
                $totalPoints += $this->getPoints($answers[ActivityTrackerQuestion::MINUTES]->getAnswer());
            } else if(isset($answers[ActivityTrackerQuestion::MINUTES])) {
                $monthMinutes[$month] += $answers[ActivityTrackerQuestion::MINUTES]->getAnswer();
            }
        }

        if($this->monthlyPointLimit !== null) {
            // Group points by month, no rollover of minutes between months

            foreach($monthMinutes as $month => $minutes) {
                $totalPoints += min($this->getPoints($minutes), $this->monthlyPointLimit);
            }
        } else {
            // Minutes for entire timeframe are summed and then points are calculated

            $totalPoints += $this->getPoints(array_sum($monthMinutes));
        }

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $totalPoints),
            $records
        );
    }

    /**
     * Translates minutes into a point figure.
     *
     * @param int $minutes
     * @return int
     */
    private function getPoints($minutes)
    {
        return $this->pointsMultiplier * round(((int) ($this->fractionalPointDivisor * $minutes / $this->pointDivisor)) / $this->fractionalPointDivisor, 2);
    }

    private $activityID = ActivityTrackerActivity::PHYSICAL_ACTIVITY;
    private $fractionalPointDivisor = 4;
    private $pointsMultiplier = 1;
    private $pointDivisor = 1;
    private $monthlyPointLimit = null;
}

