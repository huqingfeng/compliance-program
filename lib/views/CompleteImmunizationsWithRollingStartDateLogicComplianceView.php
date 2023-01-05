<?php
/**
 * This class implements an immunization view with start date logic that
 * changes depending upon a question's answer.
 */
class CompleteImmunizationsWithRollingStartDateLogicComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerRecord)
    {
        parent::__construct($startDate, $endDate);

        $this->pointsPerRecord = $pointsPerRecord;
    }

    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user);

        $numberDone = 0;

        $endDate = $this->getEndDate();

        foreach($records as $rec) {
            $answers = $rec->getQuestionAnswers();

            $type = trim(isset($answers[63]) ? $answers[63]->getAnswer() : '');

            $date = strtotime($rec->getDate());

            if($date <= $endDate) {
                if($type == 'Tetanus (Td)' || $type == 'Tetanus, diptheria & Pertussis (Tdap)') {
                    if($date >= strtotime('-120 months', $this->getStartDate())) {
                        $numberDone++;
                    }
                } else if($type == 'Flu shot') {
                    if($date >= strtotime('-12 months', $this->getStartDate())) {
                        $numberDone++;
                    }
                } else {
                    $numberDone++;
                }
            }
        }

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $this->pointsPerRecord * $numberDone),
            $records
        );
    }

    private $id = 242;
    private $pointsPerRecord;
}