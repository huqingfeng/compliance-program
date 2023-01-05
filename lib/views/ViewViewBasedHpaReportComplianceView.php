<?php


class ViewViewBasedHpaReportComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, array $views)
    {
        if(!count($views)) {
            throw new \InvalidArgumentException('You must pass at least one view.');
        }

        $this->setDateRange($startDate, $endDate);
        $this->views = $views;
    }

    public function getStatus(User $user)
    {
        $this->emptyLinks();

        $screeningIds = null;
        $hraIds = null;
        $compliant = true;

        $hraRequired = false;
        $screeningRequired = false;

        foreach($this->views as $view) {
            $status = $view->getStatus($user);

            if(!$status->isCompliant()) {
                $compliant = false;
            } elseif($view instanceof CompleteScreeningComplianceView) {
                $screeningIds = $status->getAttribute('range_ids');
                $screeningRequired = true;
            } elseif($view instanceof CompleteHRAComplianceView) {
                $hraIds = $status->getAttribute('range_ids');
                $hraRequired = true;
            } elseif($view instanceof CompleteHRAAndScreeningComplianceView) {
                $hraIds = $status->getAttribute('hra_range_ids');

                $screeningIds = $status->getAttribute('screening_range_ids');

                $hraRequired = true;
                $screeningRequired = true;
            }

            if(!$compliant) {
                break;
            }
        }

        if($compliant) {
            $linkParameters = array(
                'nopeergroup' => 1,
                'user_id'     => $user->id,
                'report_type' => 'hpa'
            );

            if($hraIds !== null && count($hraIds)) {
                $linkParameters['hraid'] = reset($hraIds);
            }

            if($screeningIds !== null && count($screeningIds)) {
                $linkParameters['screeningid'] = reset($screeningIds);
            }

            $this->addLink(new \Link('View Report', sprintf('/' . sfConfig::get('wms3_hpa_report_type', 'content/751') . '?%s', http_build_query($linkParameters))));
        }

        $eventLogs = EventLogTable::getInstance()->findForUserBetween(
            $user,
            'view_hpa',
            $this->getStartDateTime(),
            $this->getEndDateTime('Y-m-d 23:59:59')
        );

        foreach($eventLogs as $log) {
            $params = $log->getParameters(false);

            $hraDone = $hraRequired && isset($params['hra_id']) && $hraIds && in_array($params['hra_id'], $hraIds);

            $screeningDone = $screeningRequired && isset($params['screening_id']) && $screeningIds && (in_array($params['screening_id'], $screeningIds) || (isset($params['screening_merge_ids']) && count(array_intersect($screeningIds, $params['screening_merge_ids'])) > 0));

            if((!$hraRequired || $hraDone) && (!$screeningRequired || $screeningDone)) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, date('m/d/Y', strtotime($log->created_at)));
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'view_hpa_report';
    }

    public function getDefaultReportName()
    {
        return 'View HPA Report';
    }

    private $views;
}
