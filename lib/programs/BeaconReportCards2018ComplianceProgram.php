<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class BeaconReportCards2018ComplianceView extends ComplianceView
{
    public function __construct() { }

    public function getDefaultStatusSummary($status) { return null; }
    public function getDefaultName() { return; }
    public function getDefaultReportName() { return; }
    public function getStatus(User $user) { return; }
}

class BeaconReportCards2018ComplianceProgram extends ComplianceProgram
{

    public function getAdminProgramReportPrinter() { return ; }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
//        $url = "http://127.0.0.1/wms3/public/beacon?bypass=true&method=";
        $url = "https://master.hpn.com/wms3/public/beacon?bypass=true&method=";
        $url .= isset($_POST['data']) ? $_POST['data']['override'] : $_GET['method'];
        $url .= '&wms1Id=' . $_GET['wms1Id'];
        $url .='&program_id='.$_GET['id'];
        $url.= isset($_GET['editing']) ? '&editing='.$_GET['editing'] : '';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if(isset($_POST['data'])) {
        curl_setopt($curl, CURLOPT_POSTFIELDS,
          http_build_query($_POST['data']));
        }
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;
        return ;
    }

    public function showGroup($group) { return true; }
    public function loadSessionParameters() { return; }
    public function loadGroups() { return; }
    public function getLocalActions() { return; }
}

class BeaconReportCards2018ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status) { return; }

    protected function printCustomRows($status) {}

    public function __construct() { }

    public function printHeader(ComplianceProgramStatus $status) { }
}

class BeaconReportCards2018CompliancePrinter extends ScreeningProgramReportPrinter
{
    public function setShowTobacco($bool) { }
    public function printReport(ComplianceProgramStatus $status) { }
}