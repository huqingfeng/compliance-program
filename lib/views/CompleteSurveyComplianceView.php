<?php

class CompleteSurveyComplianceView extends ComplianceView
{
    public function __construct($surveyID)
    {
        $this->id = $surveyID;
        $this->addLink(new Link('Do Survey', '/surveys/'.$this->id.'/showIntroduction'));
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return sprintf('complete_survey_%s', $this->id);
    }

    public function getDefaultReportName()
    {
        return sprintf('Complete Survey #%s', $this->id);
    }

    public function getStatus(User $user)
    {
        $status = ComplianceStatus::NOT_COMPLIANT;
        $comment = null;

        $surveyCompletions = array();
        foreach($user->getSurveyCompletions() as $surveyCompletion) {
            if($surveyCompletion->getSurveyID() == $this->id) {
                $surveyCompletions[$surveyCompletion->getId()]['status'] = ComplianceStatus::PARTIALLY_COMPLIANT;
                $surveyCompletions[$surveyCompletion->getId()]['complete'] = false;
                $surveyCompletions[$surveyCompletion->getId()]['comment'] = $surveyCompletion->getDateTimeObject('completed_at')->format('m/d/Y');

                if($surveyCompletion->getComplete()) {
                    $surveyCompletions[$surveyCompletion->getId()]['complete'] = true;
                    $surveyCompletions[$surveyCompletion->getId()]['status'] = ComplianceStatus::COMPLIANT;
                }
            }
        }

        usort($surveyCompletions, function($a, $b) {
            return strtotime($b['comment']) - strtotime($a['comment']);
        });

        foreach($surveyCompletions as $surveyCompletion) {
            $status = $surveyCompletion['status'];

            if($surveyCompletion['complete']) {
                $comment = $surveyCompletion['comment'];
                break;
            }
        }

        return new ComplianceViewStatus($this, $status, null, $comment);
    }

    protected $id;
}