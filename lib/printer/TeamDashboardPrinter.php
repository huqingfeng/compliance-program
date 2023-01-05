<?php

interface TeamDashboardPrinter
{
    public function printReport($teamName, array $programStatuses);
}