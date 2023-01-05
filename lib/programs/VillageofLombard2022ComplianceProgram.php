<?php

use hpn\steel\query\SelectQuery;

class VillageofLombard2022CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        return ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@' . $this->getStartDate()),
            new DateTime('@' . $this->getEndDate()),
            array(
                'require_online' => false,
                'merge' => true,
                'require_complete' => false,
                'filter' => $this->getFilter(),
                'required_fields' => array('cholesterol')
            )
        );
    }
}

class VillageofLombard2022ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if ($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2022', 'SCENARIO C');

            $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

            return $track;
        }
    }

    public function loadGroups()
    {
        global $_user;

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $req = new ComplianceViewGroup('core', 'Requirements');


        $screening = new VillageofLombard2022CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '06/13/2023');
        $screening->setReportName('<b>A.</b> Complete Biometric Screening');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/49'));
        $screening->addLink(new Link('View Results', '/content/my-health'));

        $req->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hra->setReportName('<b>B.</b> Complete Health Risk Assessment');
        $hra->setAttribute('deadline', '06/13/2023');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->addLink(new Link('Take HRA', "/content/my-health"));

        $req->addComplianceView($hra);


        $docVisit = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144545, 1);
        $docVisit->setReportName('<b>C.</b> Complete 1 of the Following: <p style="margin-left:20px;">Dentist Visit <br /><br />OR<br /><br />Physician Visit</p>');
        $docVisit->setName('doctor_visit');
        $docVisit->setAttribute('deadline', '06/13/2023');
        $docVisit->setCompliancePointStatusMapper(new CompliancePointStatusMapper());
        $docVisit->setPostEvaluateCallback(function($status, $user) {
            $view = $status->getComplianceView();

            if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                $lastRecordDate = $view->latestRecordDate($user);

                $records = $view->getRecords($user);

                $record = reset($records);

                $answers = $record->getQuestionAnswers();
                if(isset($answers[225])) {
                    $visitSelected = $answers[225]->getAnswer();

                    $status->setAttribute('date', $lastRecordDate);

                    $status->setComment($visitSelected.' ('.$lastRecordDate.')');
                }
            }

        });
        $req->addComplianceView($docVisit);


        $this->addComplianceViewGroup($req);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new VillageofLombard2022ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $program = $this;

        $printer->setShowUserFields(null, null, true, false, true, null, null, null, true);
        $printer->setShowUserContactFields(true, null, true);

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        $printer->addCallbackField('member_id', function (User $user) {
            return $user->member_id;
        });


        return $printer;
    }




    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();


    }

    private function getTempView($name, $reportName, $deadline, array $links = array(), $defaultStatus = ComplianceStatus::NOT_COMPLIANT)
    {
        $ageAppropriate = new PlaceHolderComplianceView($defaultStatus);
        $ageAppropriate->setName($name);
        $ageAppropriate->setReportName($reportName);
        $ageAppropriate->setAttribute('deadline', $deadline);
        $ageAppropriate->setAllowPointsOverride(true);

        foreach ($links as $link)
            $ageAppropriate->addLink($link);

        return $ageAppropriate;
    }

    private $hideMarker = '<span class="hide-view">hide</span>';
    private $lastTrack = null;
    private $GHIndicator = 'No';
}

class VillageofLombard2022ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {

        $user = $status->getUser();

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');


        ?>

        <style type="text/css">
            html {
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #wms3-content {
                width: 1380px;
            }

            #page #wms3-content h1,
            #page #content h1 {
                padding: 0.5rem;
                background-color: #8587b9;
                color: white;
                text-align: center;
                font-family: Roboto;
                font-size: 1.5rem;
                font-weight: bold;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content aside,
            #page #content aside {
                padding: 0 1rem;
                background-color: #f8fafc;
                border: 1px solid #e7eaf2;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content aside p:first-of-type + p,
            #page #content aside p:first-of-type + p {
                position: relative;
                padding-left: 3rem;
            }

            #page #wms3-content aside i,
            #page #content aside i {
                background-color: transparent !important;
                text-align: center;
                margin-top: -0.95rem;
                font-size: 1.25rem;
            }

            #page #wms3-content aside i,
            #page #wms3-content q,
            #page #content aside i,
            #page #content q {
                position: absolute;
                top: 50%;
                left: 0.5rem;
            }

            #page #wms3-content q,
            #page #content q {
                margin-top: -1.2rem;
                background-color: #ffb65e;
                text-align: left;
            }

            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q:before,
            #page #content q:after {
                content: '';
                position: absolute;
                background-color: inherit;
            }

            #page #wms3-content q,
            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q,
            #page #content q:before,
            #page #content q:after {
                display: inline-block;
                width: 1.5rem;
                height: 1.5rem;
                border-radius: 0;
                border-top-right-radius: 30%;
            }

            #page #wms3-content q,
            #page #content q {
                transform: rotate(-60deg) skewX(-30deg) scale(1, .866);
            }

            #page #wms3-content q:before,
            #page #content q:before {
                transform: rotate(-135deg) skewX(-45deg) scale(1.414, .707) translate(0, -50%);
            }

            #page #wms3-content q:after,
            #page #content q:after {
                transform: rotate(135deg) skewY(-45deg) scale(.707, 1.414) translate(50%);
            }

            #page #wms3-content table,
            #page #content table {
                border-collapse: separate;
                table-layout: fixed;
                width: 100%;
                line-height: 1.5rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content table + table,
            #page #content table + table {
                margin-top: 1rem;
            }

            #page #wms3-content th,
            #page #content th {
                padding: 1rem;
                background-color: #014265;
                color: white;
                border: 1px solid #014265;
                font-weight: bold;
                text-align: center;
            }

            #page #wms3-content th:first-of-type,
            #page #content th:first-of-type {
                border-top-left-radius: 0.25rem;
                text-align: left;
            }

            #page #wms3-content th:last-of-type,
            #page #content th:last-of-type {
                border-top-right-radius: 0.25rem;
            }

            #page #wms3-content td,
            #page #content td {
                padding: 1rem;
                color: #57636e;
                border-left: 1px solid #e8e8e8;
                border-bottom: 1px solid #e8e8e8;
                text-align: center;
            }

            #page #wms3-content tr:last-of-type td:first-of-type,
            #page #content tr:last-of-type td:first-of-type {
                border-bottom-left-radius: 0.25rem;
            }

            #page #wms3-content td:last-of-type,
            #page #content td:last-of-type {
                border-right: 1px solid #e8e8e8;
            }

            #page #wms3-content tr:last-of-type td:last-of-type,
            #page #content tr:last-of-type td:last-of-type {
                border-bottom-right-radius: 0.25rem;
            }

            #page #wms3-content a,
            #page #content a {
                display: inline-block;
                color: #0085f4 !important;
                font-size: 1rem;
                text-transform: uppercase;
                text-decoration: none !important;
            }

            #page #wms3-content a + a,
            #page #content a + a {
                margin-top: 1rem;
            }

            #page #wms3-content a:hover,
            #page #wms3-content a:focus,
            #page #wms3-content a:active,
            #page #content a:hover,
            #page #content a:focus,
            #page #content a:active {
                color: #0052C1 !important;
                text-decoration: none !important;
            }

            #page #wms3-content i,
            #page #content i {
                width: 1.5rem;
                height: 1.5rem;
                line-height: 1.5rem;
                background-color: #ced2db;
                border-radius: 999px;
                color: white;
                font-size: 1.25rem;
            }

            #page #wms3-content i.fa-check,
            #page #content i.fa-check {
                background-color: #4fd3c2;
            }

            #page #wms3-content i.fa-exclamation,
            #page #content i.fa-exclamation {
                background-color: #ffb65e;
            }

            #page #wms3-content i.fa-times,
            #page #content i.fa-times {
                background-color: #dd7370;
            }

            #legend {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 2rem 0;
            }

            #legend div {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
            }

            #legend h2 {
                margin: 2rem 0;
                color: #23425e;
                font-size: 1.75rem;
                letter-spacing: 0.4rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #legend p {
                position: relative;
                width: 11rem;
                height: 2.5rem;
                line-height: 2.5rem;
                margin: 0.25rem 0.25rem;
                padding-left: 1.25rem;
                background-color: #ebf1fa;
                text-align: center;
                font-size: 1.1rem;
            }

            #legend i {
                position: absolute;
                left: 1rem;
                top: 50%;
                margin-top: -0.75rem;
            }

            @media only screen and (max-width: 1200px) {
                #legend {
                    flex-direction: column;
                    align-items: flex-start;
                }

                #legend > div {
                    align-content: flex-start;
                }
            }

            @media only screen and (max-width: 1060px) {
                #page #wms3-content table,
                #page #content table {
                    table-layout: auto;
                }
            }
        </style>

        <h1>Village of Lombard 2022-2023 Wellness Rewards Program</h1>


        <div id="legend">
            <div>
                <div>
                    <p><i class="far fa-check"></i> CRITERIA MET</p>
                    <p><i class="fas fa-exclamation"></i> IN PROGRESS</p>
                </div>
                <div>
                    <p><i class="far fa-times"></i> NOT STARTED</p>
                    <p><i class="far fa-minus"></i> N/A</p>
                </div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th colspan="4">1. Requirements</th>
                <th>Deadline</th>
                <th>Date Done</th>
                <th>Count Completed</th>
                <th>Status</th>
                <th>Links</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) { ?>

                <tr>
                    <td colspan="4" style="text-align: left;">
                        <?= $viewStatus->getComplianceView()->getReportName(true) ?>
                    </td>
                    <td>
                        <?= $viewStatus->getComplianceView()->getAttribute('deadline', '') ?>
                    </td>
                    <td>
                        <?php echo $viewStatus->getComplianceView()->getName() == 'doctor_visit' ? $viewStatus->getAttribute('date') : $viewStatus->getComment() ?>
                    </td>
                    <td>
                        <?= $viewStatus->getPoints() != '' ? $viewStatus->getPoints() : 0 ?>
                    </td>
                    <td>
                        <i class="<?= getStatus($viewStatus->getStatus()) ?>"></i>
                    </td>
                    <td>
                        <?php
                        foreach ($viewStatus->getComplianceView()->getLinks() as $link)
                            echo $link->getHTML();
                        ?>
                    </td>
                </tr>
            <?php } ?>

            </tbody>
        </table>

        <?php
    }
}

function getStatus($code)
{
    if ($code == 4) return 'far fa-check';
    if ($code == 2) return 'fas fa-exclamation';
    if ($code == 1) return 'far fa-times';
    return 'far fa-minus';
}

?>
