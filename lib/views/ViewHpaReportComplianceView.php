<?php

class ViewHpaReportComplianceView extends DateBasedComplianceView
{
    const LOGIC_SCREENING = 1;
    const LOGIC_REGULAR = 2;
    const LOGIC_ANY = 3;
    const LOGIC_BOTH = 4;
    const LOGIC_BOTH_AT_ONCE = 5;

    public function __construct($startDate, $endDate, $logic)
    {
        $this->setDateRange($startDate, $endDate);
        $this->logic = $logic;

        if($logic != self::LOGIC_SCREENING) {
            $this->addLink(new Link('View HPA Report', '/' . sfConfig::get('wms3_hpa_report_type', 'content/751')));
        }

        if($logic != self::LOGIC_REGULAR && $logic != self::LOGIC_BOTH_AT_ONCE) {
            $this->addLink(new Link('View Screening Report', '/' . sfConfig::get('wms3_hpa_report_type', 'content/751') . '?hraid=-1'));
        }
    }

    public function getStatus(User $user)
    {
        $eventLogs = EventLogTable::getInstance()->findForUserBetween(
            $user,
            'view_hpa',
            $this->getStartDateTime(),
            new DateTime(sprintf('%s 23:59:59', $this->getEndDate('Y-m-d')))
        );

        $hasRegular = false;
        $hasScreeningOnly = false;
        $hasBothAtOnce = false;
        $hasBothComment = null;
        $comment = null;

        foreach($eventLogs as $log) {
            $params = $log->getParameters(false);

            $parsedDate = isset($params['report_date']) ?
                strtotime($params['report_date']) : false;

            if($this->getStartDate() <= $parsedDate && $this->getEndDate() >= $parsedDate) {
                if(isset($params['screening_only']) && $params['screening_only']) {
                    if(!$hasScreeningOnly) {
                        $comment = date('m/d/Y', strtotime($log->created_at));
                    }

                    $hasScreeningOnly = true;
                } else {
                    if(!$hasRegular) {
                        $comment = date('m/d/Y', strtotime($log->created_at));
                    }

                    $hasRegular = true;

                    if(isset($params['screening_id'], $params['hra_id'])) {
                        if(!$hasBothAtOnce) {
                            $hasBothComment = date('m/d/Y', strtotime($log->created_at));
                        }

                        $hasBothAtOnce = true;
                    }
                }

                if($hasScreeningOnly && $hasRegular && $hasBothAtOnce) {
                    break;
                }
            }
        }

        switch($this->logic) {
            case self::LOGIC_SCREENING:
                $status = $hasScreeningOnly ?
                    ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

                break;

            case self::LOGIC_ANY:
                $status = $hasRegular || $hasScreeningOnly ?
                    ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

                break;

            case self::LOGIC_BOTH:
                if($hasRegular && $hasScreeningOnly) {
                    $status = ComplianceStatus::COMPLIANT;
                } elseif($hasRegular || $hasScreeningOnly) {
                    $status = ComplianceStatus::PARTIALLY_COMPLIANT;
                } else {
                    $status = ComplianceStatus::NOT_COMPLIANT;
                }

                break;

            case self::LOGIC_REGULAR:
                $status = $hasRegular ?
                    ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

                break;

            case self::LOGIC_BOTH_AT_ONCE:
                $status = $hasBothAtOnce ?
                    ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

                $comment = $hasBothComment;

                break;

            default:
                throw new \RuntimeException("Invalid logic constant: {$this->logic}");
        }

        return new ComplianceViewStatus(
            $this,
            $status,
            null,
            $status == ComplianceStatus::COMPLIANT ? $comment : null
        );
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

    private $logic = self::LOGIC_REGULAR;
}
