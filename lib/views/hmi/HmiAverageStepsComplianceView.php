<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/jawbone/lib/model/jawboneApi.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

class HmiAverageStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold)
    {
        $this->setDateRange($startDate, $endDate);

        $this->threshold = $threshold;

        $formattedThreshold = number_format($this->threshold);

        $this->setAttribute('requirement', "Walk an average of {$formattedThreshold} steps/day");
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceStatus::COMPLIANT) {
            return $this->getAttribute('requirement');
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return "hmi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $fitbitData = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        if($this->useJawbone) {
            try{
                JawboneApi::refreshJawboneData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $jawboneData = JawboneApi::getJawboneData($user);
        }


        if($this->useMoves) {
            try{
                MovesApi::refreshMovesData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $movesData = MovesApi::getMovesData($user, true);
        }

        $dates = $this->getDatesInRange($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $totalSteps = 0;
        foreach($dates as $date) {
            $steps = 0;

            if(isset($fitbitData['dates'][$date]) && $fitbitData['dates'][$date] > $steps) {
                $steps = $fitbitData['dates'][$date];
            }

            if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $steps) {
                $steps = $jawboneData['dates'][$date];
            }

            if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $steps) {
                $steps = $movesData['dates'][$date];
            }

            $totalSteps += $steps;
        }
        $averageDailySteps = round($totalSteps/count($dates));


        $status = new ComplianceViewStatus(
            $this,
            isset($averageDailySteps) && $averageDailySteps > $this->threshold ?
                ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT
        );

        $status->setAttribute('average_daily_steps', $averageDailySteps);
        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }

    private function getDatesInRange($strDateFrom, $strDateTo)
    {
        $aryRange=array();

        $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),substr($strDateFrom,8,2),substr($strDateFrom,0,4));
        $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),substr($strDateTo,8,2),substr($strDateTo,0,4));

        if ($iDateTo>=$iDateFrom)
        {
            array_push($aryRange,date('Y-m-d',$iDateFrom));
            while ($iDateFrom<$iDateTo)
            {
                $iDateFrom += 86400;
                array_push($aryRange,date('Y-m-d',$iDateFrom));
            }
        }
        return $aryRange;
    }

    public function setUseJawbone($useJawbone)
    {
        $this->useJawbone = $useJawbone;
    }

    public function setUseMoves($useMoves)
    {
        $this->useMoves = $useMoves;
    }

    private $threshold;
    private $useJawbone = false;
    private $useMoves = false;
}
