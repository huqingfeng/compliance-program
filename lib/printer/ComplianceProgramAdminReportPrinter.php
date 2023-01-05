<?php

interface ComplianceProgramAdminReportPrinter
{
    public function printAdminReport(ComplianceProgramReport $report, $output);
}