<?php

class ViewLinkComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, Link $link)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

        $this->url = url_for('redirect', array('url' => $link->getLink(), 'confirm' => 0));

        $redirectLink = new Link(
            $link->getLinkText(),
            $this->url
        );

        $redirectLink->setTarget('_blank');

        $this->addLink($redirectLink);
        $this->link = $redirectLink;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'view_link_'.$this->link->getLinkText();
    }

    public function getDefaultReportName()
    {
        return 'View Link';
    }

    public function getStatus(User $user)
    {
        $log = RequestLogQuery::createQuery('r')
            ->where('r.user_id = ?', $user->id)
            ->andWhere('r.created_at BETWEEN ? AND ?', array(
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d 23:59:59')
        ))->andWhere('r.request_uri LIKE ?', '%'.$this->url)
            ->andWhere('r.for_services = 0')
            ->orderBy('r.created_at DESC')
            ->limit(1)
            ->fetchOne();

        return new ComplianceViewStatus(
            $this,
            $log ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT,
            null,
            $log ? $log->getDateTimeObject('created_at')->format('m/d/Y') : null
        );
    }

    protected $url;
    protected $link;
}