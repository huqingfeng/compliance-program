<?php

class ComplyWithArbitraryHraQuestionComplianceView extends ComplyWithHRAQuestionComplianceView
{
    public function __construct($startDate, $endDate, $questionId)
    {
        parent::__construct($startDate, $endDate);

        $this->questionId = $questionId;
    }

    public function getQuestionID()
    {
        return $this->questionId;
    }

    protected $questionId;
}