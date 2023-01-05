<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class WMSDEMO2020ReportCardComplianceView extends ComplianceView
{
    public function __construct() { }

    public function getDefaultStatusSummary($status) { return null; }
    public function getDefaultName() { return; }
    public function getDefaultReportName() { return; }
    public function getStatus(User $user) { return; }
}

class WMSDEMO2020ReportCardComplianceProgram extends ComplianceProgram
{

    public function getAdminProgramReportPrinter() { return ; }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $user = sfContext::getInstance()->getUser()->getUser();

        $domain = $this->getDomain();

        $map = [
            4069 => 1505
        ];

        if($_GET['method'] == 'custom_points' && !isset($_GET['reportcard_id'])) {

            $cards = [
                21 => 'WMSDEMO Reportcard'
            ];

            echo '
                    <div><select name="reportcard_id" class="select-card">
                        <option value="-1">Select a reportcard to edit</option>';
                        foreach($cards as $id => $card) {
                            echo '<option value="' . $id .'">' . $card . '</option>';
                        }
                echo '</select></div>
                <div><button type="button" class="edit-card">Edit Reportcard</button></div>';

                ?>
                    <script>
                        $(function() {
                            $('.edit-card').click(function() {
                                var card_id = $('.select-card').val();
                                if(card_id != -1) {
                                    window.location = '/compliance/wmsdemo/well-rewards/compliance_programs?<?php echo $_SERVER['QUERY_STRING']; ?>&reportcard_id=' + card_id;
                                }
                            });
                        })
                    </script>

                <?php 
        } else {

            if(isset($_GET['editing'])) {
                $user_editing = UserTable::getInstance()->findOneBy('id', $_GET['editing']);

                $_GET['id'] = $map[$user_editing->getClientId()];
            }
            
            $url = $domain."/wms3/public/wmsdemoreportcard?bypass=true&method=";
            $url .= isset($_POST['data']) ? $_POST['data']['override'] : $_GET['method'];
            $url .= '&wms1Id=' . $user->id;
            $url .='&program_id='.$_GET['id'];
            $url .= isset($_GET['editing']) ? '&editing='.$_GET['editing'] : '';
            $url .= isset($_GET['reportcard_id']) ? '&reportcard_id=' . $_GET['reportcard_id'] : '';
            if(isset($_GET['show'])) {
                $url.='&show='.$_GET['show'];
            }
            

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
    }

    public function showGroup($group) { return true; }
    public function loadSessionParameters() { return; }
    public function loadGroups() { return; }
    public function getLocalActions() { return; }
}

class WMSDEMO2020ReportCardComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status) { return; }

    protected function printCustomRows($status) {}

    public function __construct() { }

    public function printHeader(ComplianceProgramStatus $status) { }
}

class WMSDEMO2020ReportCardCompliancePrinter extends ScreeningProgramReportPrinter
{
    public function setShowTobacco($bool) { }
    public function printReport(ComplianceProgramStatus $status) { }
}