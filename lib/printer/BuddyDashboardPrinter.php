<?php

interface BuddyDashboardPrinter
{
    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus);
}