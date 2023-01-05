<?php

use hpn\steel\query\SelectQuery;



class SecondNatureBrands2022ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SecondNatureBrands2022WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $hraScrStart = '2022-01-20';
        $hraScrEnd = '2022-05-01';

        $coreGroup = new ComplianceViewGroup('Core Group');

        $hra = new CompleteHRAComplianceView($hraScrStart, $hraScrEnd);
        $hra->setReportName('Health Risk Assessment');
        $hra->setName('hra');
        $coreGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($hraScrStart, $hraScrEnd);
        $scr->setReportName('Screening');
        $scr->setName('screening');
        $coreGroup->addComplianceView($scr);

        $consultationView = new AttendAppointmentComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Private Phone Consult');
        $consultationView->setName('consultation');
        $consultationView->bindTypeIds(array(11, 21));
        $coreGroup->addComplianceView($consultationView);

        $this->addComplianceViewGroup($coreGroup);


        $wellnessGroup = new ComplianceViewGroup('Wellness');
        $wellnessGroup->setPointsRequiredForCompliance(5);

        $eye = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144520, 1);
        $eye->setMaximumNumberOfPoints(1);
        $eye->setReportName('1. Eye Exam');
        $eye->setName('eye_exam');
        $eye->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($eye);

        $flu = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144521, 1);
        $flu->setMaximumNumberOfPoints(1);
        $flu->setReportName('2. Flu Vaccination');
        $flu->setName('flu_vaccination');
        $flu->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($flu);

        $covid = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144522, 1);
        $covid->setMaximumNumberOfPoints(1);
        $covid->setReportName('3. COVID Vaccination and/or booster');
        $covid->setName('covid');
        $covid->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($covid);

        $dental = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144523, 1);
        $dental->setMaximumNumberOfPoints(1);
        $dental->setReportName('4. Dental Exams (2/year)');
        $dental->setName('dental');
        $dental->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($dental);

        $mammogram = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144524, 1);
        $mammogram->setMaximumNumberOfPoints(1);
        $mammogram->setReportName('5. Mammogram');
        $mammogram->setName('mammogram');
        $mammogram->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($mammogram);

        $pelvic = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144525, 1);
        $pelvic->setMaximumNumberOfPoints(1);
        $pelvic->setReportName('6. Pelvic Exam');
        $pelvic->setName('pelvic');
        $pelvic->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($pelvic);

        $colonoscopy = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144526, 1);
        $colonoscopy->setMaximumNumberOfPoints(1);
        $colonoscopy->setReportName('7. Colonoscopy');
        $colonoscopy->setName('colonoscopy');
        $colonoscopy->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($colonoscopy);

        $prostate = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144527, 1);
        $prostate->setMaximumNumberOfPoints(1);
        $prostate->setReportName('8. Prostate Exam');
        $prostate->setName('prostate');
        $prostate->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($prostate);

        $coaching = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $coaching->setMaximumNumberOfPoints(1);
        $coaching->setReportName('9. High Risk Coaching (1 session if invited by Circle Wellness)');
        $coaching->setName('coaching');
        $coaching->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($coaching);

        $smoking = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144529, 1);
        $smoking->setMaximumNumberOfPoints(1);
        $smoking->setReportName('10. Smoking Cessation Program (Any program eligible - <a href="https://www.bcbsm.com/index/health-insurance-help/faqs/topics/getting-care/how-do-i-get-help-quitting-tobacco.html" target="_blank">Resources</a>)');
        $smoking->setName('smoking_cessation');
        $smoking->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($smoking);

        $fiveK = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144530, 1);
        $fiveK->setMaximumNumberOfPoints(1);
        $fiveK->setReportName('11. Participate in a 5k or similar');
        $fiveK->setName('5k');
        $fiveK->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($fiveK);

        $weightWatchers = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 144531, 1);
        $weightWatchers->setMaximumNumberOfPoints(1);
        $weightWatchers->setReportName('12. Participate in Weight Watchers or similar');
        $weightWatchers->setName('weight_watchers');
        $weightWatchers->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $wellnessGroup->addComplianceView($weightWatchers);


        $this->addComplianceViewGroup($wellnessGroup);
    }


    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreStatus = $status->getComplianceViewGroupStatus('Core Group');
        $wellnessStatus = $status->getComplianceViewGroupStatus('Wellness');


        foreach($wellnessStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $view = $viewStatus->getComplianceView();
                $viewStatus->setPoints($view->getMaximumNumberOfPoints());
            }
        }

        if($coreStatus->getStatus() == ComplianceStatus::COMPLIANT && $wellnessStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }

    public function useParallelReport()
    {
        return false;
    }


}


class SecondNatureBrands2022WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'waist_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
            );
        } elseif($view->getName() == 'tobacco') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        } else {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                2 => ComplianceStatus::PARTIALLY_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getClass($points, $max)
    {
        if ($points < $max/2) {
            return "danger";
        } else if ($points >= $max) {
            return "success";
        } else {
            return "warning";
        }
    }


    public function printReport(ComplianceProgramStatus $status)
    {
        $escaper = new hpn\common\text\Escaper;

        $wellnessGroup = $status->getComplianceViewGroupStatus('Wellness');

        $hraCompletion = $status->getComplianceViewStatus('hra');
        $hraClass = ($hraCompletion->getStatus() == ComplianceStatus::COMPLIANT)? "fa fa-check green" : "fa fa-times red";
        $screeningCompletion = $status->getComplianceViewStatus('screening');
        $screeningClass = ($screeningCompletion->getStatus() == ComplianceStatus::COMPLIANT)? "fa fa-check green" : "fa fa-times red";
        $consultationCompletion = $status->getComplianceViewStatus('consultation');
        $consultationClass = ($consultationCompletion->getStatus() == ComplianceStatus::COMPLIANT)? "fa fa-check green" : "fa fa-times red";


        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
                overflow: hidden;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 436px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1em;
            }


            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .total-status td, .spouse-status td {
                text-align: center;
            }

            #wms1 h3[toggle] {
                font-size: 20px;
                color: #333D46;
                background: #ECEFF1;
                cursor: pointer;
                padding: 10px 20px;
                border-radius: 2px;
            }

            #wms1 h3[toggle]:hover {
                color: #48C7E8;
            }

            #wms1 h3[toggle] i {
                margin-right: 10px;
            }

            .date-input {
                width: 100%;
                height: 39px;
                font-size: 1.7rem;
                text-align: center;
                cursor: pointer;
            }

            .date-input:hover {
                background: #ECEFF1;
                outline: none !important;
                border: 1px solid;
            }

            .shadow {
                box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
                height: 100px;
            }

            .grand-total-container {
                box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
            }

            .quarter-indicator {
                width: 50px;
                height: 50px;
            }

            .quarter-even {
                background-color: #48c7e8;
                display: inline-block;

            }

            .quarter-odd {
                background-color: #0cc2ab;
                display: inline-block;
            }

            .quarter {
                text-align: center;
                color: #fff;
                vertical-align: middle;
                line-height: 50px;
                font-size: 20px;
                margin-bottom: 15px;
            }

            .quarter-rectangles {
                margin-top: 20px;
            }

            .quarter-rectangles .col-md-5 {
                padding: 0 !important;
            }

            .grand-total {
                height: 220px;
                background: #333d46;
            }

            .scores-container {
                display: inline-block;
                width: 70%;
            }

            .grand-total-container {
                width: 29%;
                display: inline-block;
            }

            .quarter-points {
                text-align: right;
                padding-right: 20px;
                width: auto;
                float: right;
                font-size: 40px;
                font-weight: bold;
                position: relative;
                top: -14px;
            }

            .quarter-points-odd {
                color: #0cc2ab;
            }

            .quarter-points-even {
                color: #48c7e8;;
            }

            .quarter-range {
                float: left;
                width: 100%;
            }

            .quarter-year {
                color: #333d47;
                float: left;
                margin-left: 15px;
            }

            .quarter-months {
                color: #9c9c9c;
                float: right;
                text-align: right;
                margin-right: 15px;
                text-transform: uppercase;
                font-size: 10px;
                padding-top: 4px;
            }

            .grand-total-header {
                text-align: center;
                color: #fff;
                padding-top: 20px;
                letter-spacing: .3em;
                font-weight: bold;
            }

            .blue-circle {
                height: 150px;
                width: 150px;
                border-radius: 50%;
                background: -webkit-linear-gradient(#81e3fe, #00afda);
                background: -o-linear-gradient(#81e3fe, #00afda);
                background: linear-gradient(#81e3fe, #00afda);
                padding: 3px;
                margin: 0 auto;
                position: relative;
                top: 10px;
            }

            .grand-total-points {
                padding: 2rem;
                background: #333d46;
                border-radius: 50%;
                width: 100%;
                height: 100%;
                text-align: center;
                color: #ced5dd;
            }

            .total-points {
                font-size: 35px;
                font-weight: bold;
                position: relative;
                top: 7px;
            }

            .total-points-text {
                position: relative;
                bottom: 12px;
            }

            .red {
                color: #F44336;
            }

            .green {
                color: #66BB6A;
            }

            .fa-times, .fa-check {
                margin-right: 10px;
                width: 15px;
                display: inline-block;
                text-align: center;
            }

            @media (max-width: 500px) {
                .collapsible-points-report-card {
                    max-width: 500px;
                    min-width: 320px;
                }

                .triangle {
                    display: none;
                }

                .scores-container {
                    width: 100%;
                }

                .quarter-rectangles .col-md-5 {
                    display: inline-block;
                    width: 45%;
                    margin-left: 12px;
                }

                .quarter-points {
                    font-size: 30px;
                    padding-top: 10px;
                }

                .grand-total-container {
                    width: 100%;
                    margin-top: 10px;

                }

                .grand-total-container .row .col-md-12 {
                    padding: 0 12px;
                }

                .grand-total-header {
                    width: 61%;
                    padding: 0 10px;
                    height: 100%;
                    float: left;
                    position: relative;
                    top: 35px;
                    font-size: 17px;
                }

                .grand-total {
                    height: auto;
                    float: left;
                    width: 100%;
                    padding: 20px 0px;
                }

                .blue-circle {
                    float: right;
                    width: 100px;
                    height: 100px;
                    padding: 7px;
                    top: 0;
                    margin-right: 40px;

                }

                .grand-total-points {
                    padding: 0;
                }

                .total-points {
                    font-size: 30px;
                }

                .collapsible-points-report-card tr.details > td {
                    padding: 0;
                }
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>2022 Incentive Report Card</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>



        <div class="row" style="padding: 20px; font-weight: bolder;">
            <p><i class="<?= $hraClass ?>"></i> Health Risk Assessment (HRA)</p>
            <p><i class="<?= $screeningClass ?>"></i> Health Screening</p>
            <p><i class="<?= $consultationClass ?>"></i> Private Phone Consultation</p>
        </div>

        <h3 toggle="program_overview"><i class="fa fa-chevron-right"></i> More Info</h3>

        <div id="program_overview" style="display:none;">
          <p>
            Second Nature Brands invests over two million dollars
            each year to provide health care to our employees and
            their eligible dependents. Our objective is to not only
            provide care when you are sick, but to invest in keeping
            you healthy. We have established a Wellness Program
            designed for just this purpose.
          </p>
          <p>
            All full-time employees are eligible and encouraged to
            participate in the Wellness Program. Below is your
            Report Card for tracking your Wellness Program
            activities. You will have the opportunity to earn points
            through the Wellness Program by completing activities
            from October 1, 2021, through October 1, 2022. There are
            twelve (12) possible points.
          </p>
          <p>
            We encourage you to invest in your own health by
            participating in the Wellness Program.
          </p>
          <p>
            The Health Screenings will take place on-site in
            February-March. When you complete your wellness
            screening, your status will be updated below. The Health
            Risk Assessment (HRA) should be taken prior to your
            health screening, but within 30 days of your screening
            so that both HRA and screening can be paired for your
            Personalized Screening Report. More details will be
            communicated closer to the time of the screenings.
            Private Phone Consultations will follow HRA and Health
            Screening completion and more details will follow.
          </p>
          <p>
            You can click the “Update” links to the right of each
            wellness activity to log those activities for the dates
            they occurred. If you are eligible for a High-Risk
            Coaching session your status will be updated by Circle
            Wellness upon completing your coaching session. You may
            log back to the beginning of the program (10/01/2021)
            for completed activities. The Health Screening, HRA and
            Private Phone Consultation status will be populated
            automatically in the Circle Wellness portal as those
            activities are completed.
          </p>
          <p>
            If you have any questions or need assistance with your
            logging, please contact Customer Service at
            866-682-3020 x204.
          </p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="points">Annual Maximum</th>
                            <th class="points">Points Earned</th>
                            <th class="text-center">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="picker open">
                            <td class="name"><?= $wellnessGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">None</td>
                            <td class="points actual <?= $this->getClass($wellnessGroup->getPoints(), 45) ?>"><?= $wellnessGroup->getPoints() ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($wellnessGroup->getPoints(), 45) ?>" style="width: <?= ($wellnessGroup->getPoints() / 45) * 100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                    <tr style="font-size: 20px; text-align: center;"><td colspan="5"><a href="/resources/10739/Recommended_Screenings202111.pdf" target="_blank">Preventative Screening Recommendations</a></td></tr>

                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($wellnessGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">None</td>
                                                <td class="points actual <?= $viewStatus->getPoints() ? "success" : "danger" ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $viewStatus->getPoints() ? "success" : "danger" ?>" style="width: <?= $viewStatus->getPoints() ? 100 : 0 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                    <div><?php echo $link->getHTML() ?></div>
                                                    <?php endforeach ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script type="text/javascript">
                $(function() {

                    $('[toggle]').on('click', function(){
                        let value = $(this).attr('toggle');
                        let icon = $(this).find('i');
                        $('#'+value).toggle();
                        if (icon.hasClass('fa-chevron-right')) {
                            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        } else {
                            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        }
                    });

                    $.each($('#activities .picker'), function() {
                        $(this).click(function(e) {
                            if ($(this).hasClass('closed')) {
                                $(this).removeClass('closed');
                                $(this).addClass('open');
                                $(this).nextAll('tr.details').first().removeClass('closed');
                                $(this).nextAll('tr.details').first().addClass('open');
                            } else {
                                $(this).addClass('closed');
                                $(this).removeClass('open');
                                $(this).nextAll('tr.details').first().addClass('closed');
                                $(this).nextAll('tr.details').first().removeClass('open');
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }
