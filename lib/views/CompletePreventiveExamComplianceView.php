<?php
/**
 * This class implements an immunization view with start date logic that
 * changes depending upon a question's answer.
 */
class CompletePreventiveExamComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerRecord)
    {
        parent::__construct($startDate, $endDate);

        $this->pointsPerRecord = $pointsPerRecord;
    }

    public function configureActivity($id, $typeId, array $types)
    {
        $this->id = $id;
        $this->typeId = $typeId;
        $this->types = $types;

        $this->emptyLinks();

        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));
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

        $types = $this->getTypes();

        foreach($records as $rec) {
            $answers = $rec->getQuestionAnswers();

            $type = trim(isset($answers[$this->typeId]) ? $answers[$this->typeId]->getAnswer() : '');

            $date = strtotime($rec->getDate());

            $startDate = $this->getStartDate();

            if($date <= $endDate &&
                isset($types[$type]) &&
                $date >= $startDate
            ) {

                $numberDone++;
            }
        }

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $this->pointsPerRecord * $numberDone),
            $records
        );
    }

    private function getTypes()
    {
        return $this->types;
    }

    private $typeId = 42;

    private $id = 26;

    private $types = array(
        'Bone Density'                   => 60,
        'Blood pressure'                 => 12,
        'Cholesterol and Glucose levels' => 12,
        'Clinical Breast Exam'           => 24,
        'Clinical Testicular Exam'       => 12,
        'Colonoscopy'                    => 60,
        'Dental Exam'                    => 12,
        'Digital Exam'                   => 60,
        'HA1C'                           => 12,
        'Mammogram'                      => 24,
        'Pap Test'                       => 12,
        'Physical Exam'                  => 12,
        'PSA Test'                       => 12,
        'Vision Exam'                    => 12,
        'General Preventive Exam'        => 12
    );

    private $pointsPerRecord;
}