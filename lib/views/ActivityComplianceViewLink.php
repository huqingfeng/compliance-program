<?php

class ActivityComplianceViewLink extends Link
{
    public function __construct(ActivityTrackerActivity $a)
    {
        $this->linktext = 'Enter/Update Info';
        $this->link = '/content/12048?action=showActivity&activityidentifier='.$a->getID();
    }
}