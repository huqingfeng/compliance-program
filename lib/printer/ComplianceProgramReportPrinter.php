<?php

interface ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status);
}