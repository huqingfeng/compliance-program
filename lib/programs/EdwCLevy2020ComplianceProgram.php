<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class EdwCLevy2020ReportcardComplianceView extends ComplianceView
{
    public function __construct() { }

    public function getDefaultStatusSummary($status) { return null; }
    public function getDefaultName() { return; }
    public function getDefaultReportName() { return; }
    public function getStatus(User $user) { return; }
}

class EdwCLevy2020ReportcardComplianceProgram extends ComplianceProgram
{

    public function getAdminProgramReportPrinter() { return ; }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        global $_user;
        $user_id = $_user->getId();

        $domain = $this->getDomain();
        $url = $domain."wms3/public/edwclevy?bypass=true&method=";
        $url .= isset($_POST['data']) ? $_POST['data']['override'] : $_GET['method'];
        $url .= '&wms1Id=' . $_GET['wms1Id'];
        $url .='&program_id='.$_GET['id'];
        if (isset($_GET['item'])) $url .='&item='.$_GET['item'];
        if (isset($_GET['value'])) $url .='&value='.$_GET['value'];
        $url.= isset($_GET['admin_id']) ? '&admin_id='.$_GET['admin_id'] : '';
        $url.= isset($_GET['sync']) ? '&sync='.$_GET['sync'] : '';
        $url.= isset($_GET['tobacco_user']) ? '&tobacco_user='.$_GET['tobacco_user'] : '';
        $url.= isset($_GET['signature']) ? '&signature='.$_GET['signature'] : '';
        $url.= isset($_GET['date']) ? '&date='.$_GET['date'] : '';
        $url.= isset($_GET['response']) ? '&response='.$_GET['response'] : '';
        $url.='&year=2020';
        if(isset($_GET['page'])) {
            $url.='&page='.$_GET['page'];
        }

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

class EdwCLevy2020ReportcardComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status) { return; }

    protected function printCustomRows($status) {}

    public function __construct() { }

    public function printHeader(ComplianceProgramStatus $status) { }
}

class EdwCLevy2020ReportcardCompliancePrinter extends ScreeningProgramReportPrinter
{
    public function setShowTobacco($bool) { }
    public function printReport(ComplianceProgramStatus $status) { }
}
