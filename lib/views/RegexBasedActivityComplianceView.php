<?php

class RegexBasedActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $questionId, $multiSelect = false)
    {
        $this->activityId = $activityId;
        $this->questionId = $questionId;
        $this->multiSelect = $multiSelect;

        parent::__construct($startDate, $endDate);

        $pointsMap = array();

        foreach($this->getActivity()->getQuestions() as $question) {
            if($question->getID() == $this->questionId) {
                $parameters = $question->getParameters();

                foreach($parameters as $item => $answers) {
                    if($this->multiSelect) {
                        // Points are part of the category name

                        preg_match('/([0-9]+) Points?/', $item, $matches);

                        if(isset($matches[1]) && is_numeric($matches[1])) {
                            $points = (int) $matches[1];

                            foreach($answers as $answer) {
                                $pointsMap[$answer] = $points;
                            }
                        }
                    } else {
                        // Points are part of the answer directly

                        preg_match('/([0-9]+) Points?/i', $answers, $matches);

                        if(isset($matches[1]) && is_numeric($matches[1])) {
                            $pointsMap[$answers] = (int) $matches[1];
                        }
                    }
                }
            }
        }


        $this->answerPoints = $pointsMap;
    }

    public function setDefaultPoints($points)
    {
        $this->defaultPoints = $points;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $points = 0;

        $recorded = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId]) && ($answerText = $answers[$this->questionId]->getAnswer())) {
                if(!isset($recorded[$answerText])) {
                    $recorded[$answerText] = true;

                    $points += $this->getPointsForAnswer($answerText);
                }
            } else {
                $points += $this->defaultPoints;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    protected function getPointsForAnswer($item)
    {
        return isset($this->answerPoints[$item]) ?
            $this->answerPoints[$item] : $this->defaultPoints;
    }

    protected $answerPoints = array();
    protected $defaultPoints = 0;
    protected $questionId;
    protected $activityId;
    protected $multiSelect;
}