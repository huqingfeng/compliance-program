<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class WMS3LightSpectrumAlternatives2017 extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        return ;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');
    }

    public function adminTemplate($value)
    {
        if ($value == "ajax") {
            $output = "ajax&value=" . $_GET['value'] . "&item=" . $_GET['item'] . "&action=". $_GET['action'];
        } elseif ($value == "editor") {
            $output = "editor";
        } else {
            $output = "display";
        }
        return $output;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $wms1Id = $_GET['wms1Id'];
        $wms2Id = $_GET['wms2Id'];
        $wms2Account = $_GET['wms2Account'];
        $view = $this->adminTemplate($_GET['view']);
        $token = "c13084f0-4b89-45a8-870d-ed7c9e46137d";

        //$domain = "http://127.0.0.1/";
        $domain = "https://master.hpn.com/";

        $url = $domain."wms3/public/beacon?bypass=true&token=".$token."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&view=".$view;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;

        return ;
    }
}

class WMS3LightSpectrumAlternatives2017Printer extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $fixGroupName = function($name) use($status) {
            $group = $status->getComplianceViewGroupStatus($name)->getComplianceViewGroup();

            $group->setReportName(sprintf(
                $group->getReportName(),
                $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser())
            ));
        };

        $fixGroupName('core');
        $fixGroupName('numbers');

        parent::printReport($status);
    }

    protected function printCustomRows($status)
    {
    }

    public function __construct()
    {
    }

    public function printHeader(ComplianceProgramStatus $status)
    {

    }
}
