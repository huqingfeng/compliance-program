<?php
abstract class ComplyWithHRAQuestionComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate)->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'comply_with_hra_question_'.$this->getQuestionID();
    }

    public function getDefaultReportName()
    {
        return 'HRA Question '.$this->getQuestionID();
    }

    public function getDefaultStatusSummary($status)
    {
        static $hraStatuses = array(
            ComplianceStatus::COMPLIANT           => array(NO_RISK, OK),
            ComplianceStatus::NA_COMPLIANT        => array(NA),
            ComplianceStatus::NOT_COMPLIANT       => array(RISK, PANIC),
            ComplianceStatus::PARTIALLY_COMPLIANT => array(BORDERLINE)
        );

        $user = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        $hra = HRA::getNewestHRABetweenDates($user, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));
        if(!$hra->exists()) {
            return null;
        }
        $questionID = $this->getQuestionID();
        $questions = $hra->getQuestions();

        if(isset($questions[$questionID]['responses']) && isset($hraStatuses[$status])) {
            foreach($hraStatuses[$status] as $status) {
                foreach($questions[$questionID]['responses'] as $response) {
                    if($response['risk'] == $status) {
                        return $response['text'];
                    }
                }
            }
        }

        return null;
    }

    public abstract function getQuestionID();

    public function setUseDateForComment($bool)
    {
        $this->useDate = $bool;
    }

    public function getStatus(User $user)
    {
        $startDate = $this->getStartDate('Y-m-d');
        $endDate = $this->getEndDate('Y-m-d');

        if (self::$hraCache['user_id'] != $user->id || self::$hraCache['start_date'] != $startDate || self::$hraCache['end_date'] != $endDate) {
            $hraData = HRA::getNewestHRABetweenDates($user, $startDate, $endDate);

            $risks = $hraData ? $hraData->getRisks() : false;
            $values = $hraData ? $hraData->getValues() : false;
            $responses = $hraData ? $hraData->getResponses() : false;

            self::$hraCache = array(
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $hraData,
                'risks' => $risks,
                'values' => $values,
                'responses' => $responses
            );
        }

        $hra = self::$hraCache['data'];

        if(!$hra->exists()) {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null);
        }

        $risks = self::$hraCache['risks'];
        $values = self::$hraCache['values'];
        $responses = self::$hraCache['responses'];

        static $statusMap = array(
            NO_RISK    => ComplianceStatus::COMPLIANT,
            OK         => ComplianceStatus::COMPLIANT,
            BORDERLINE => ComplianceStatus::PARTIALLY_COMPLIANT,
            RISK       => ComplianceStatus::NOT_COMPLIANT,
            PANIC      => ComplianceStatus::NOT_COMPLIANT
        );

        $questionid = $this->getQuestionID();
        $riskValue = isset($risks[$questionid]) ? $risks[$questionid] : RISK;
        $questionResponse = isset($values[$questionid]) ? $values[$questionid] : null;

        $comment = isset($responses[$questionid]) ? $responses[$questionid] : null;
        $status = isset($statusMap[$riskValue]) ? $statusMap[$riskValue] : ComplianceStatus::NA_COMPLIANT;

        $viewStatus = new ComplianceViewStatus($this, $status, null, $this->useDate ? $hra->getDate() : $comment);
        $viewStatus->setAttribute('date', $hra->getDate('Y-m-d'));
        $viewStatus->setAttribute('has_result', $questionResponse !== null);

        return $viewStatus;
    }

    private static $hraCache = array('user_id' => false, 'start_date' => false, 'end_date' => false, 'data' => false, 'risks' => false, 'values' => false, 'responses' => false);
    private $useDate = false;
}