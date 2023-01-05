<?php

class ComplyWithMultipleHraQuestionsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, array $questionIds)
    {
        $this->setDateRange($startDate, $endDate);

        foreach($questionIds as $id) {
            $this->views[$id] = new ComplyWithArbitraryHraQuestionComplianceView(
                    $startDate, $endDate, $id
            );
        }
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'comply_with_multiple_hra_questions_'.implode('_', array_keys($this->views));
    }

    public function getDefaultReportName()
    {
        return 'Comply With Multiple HRA - ' . implode(', ', array_keys($this->views));
    }

    public function getStatus(User $user)
    {
        $numberCompliant = 0;

        foreach($this->views as $view) {
            if($view->getStatus($user)->isCompliant()) {
                $numberCompliant++;
            }
        }

        return new ComplianceViewStatus(
            $this,
            $numberCompliant == count($this->views) ? ComplianceStatus::COMPLIANT :
                ($numberCompliant > 0 ? ComplianceStatus::PARTIALLY_COMPLIANT : null));
    }

    private $views = array();
}