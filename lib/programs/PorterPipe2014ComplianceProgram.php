<?php

class PorterPipeLearningAlternativeComplianceView extends ComplianceView
{
    public function __construct($programStart, $programEnd, $alias)
    {
        $this->start = $programStart;
        $this->end = $programEnd;
        $this->alias = $alias;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'porterpipe_alt_'.$this->alias;
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning '.$this->alias;
    }

    public function getStatus(User $user)
    {
        $screeningView = new CompleteScreeningComplianceView($this->start, $this->end);
        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        if($screeningView->getStatus($user)->isCompliant()) {
            $elearningView = new CompleteELearningGroupSet($this->start, $this->end, $this->alias);
            $elearningView->setComplianceViewGroup($this->getComplianceViewGroup());
            $elearningView->setNumberRequired(1);

            if($elearningView->getStatus($user)->isCompliant()) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Elearning Lesson Completed');
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    protected $alias;
    protected $start;
    protected $end;
}

class PorterPipe2014ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new PorterPipe2014ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('healthy_measures', 'Healthy Measures');
        $group->setPointsRequiredForCompliance(4);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->overrideSystolicTestRowData(null, null, 130, null);
        $bpView->overrideDiastolicTestRowData(null, null, 90, null);
        $bpView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'blood_pressure'));
        $bpView->emptyLinks();
        $bpView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_pressure'));
        $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpView->setAttribute('screening_view', true);
        $group->addComplianceView($bpView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, '<200');
        $tcView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'cholesterol'));
        $tcView->emptyLinks();
        $tcView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tcView->setAttribute('screening_view', true);
        $group->addComplianceView($tcView);

        $tcHdlView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $tcHdlView->overrideTestRowData(null, null, 4.96999, null);
        $tcHdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '<4.97');
        $tcHdlView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'cholesterol'));
        $tcHdlView->emptyLinks();
        $tcHdlView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $tcHdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tcHdlView->setAttribute('screening_view', true);
        $group->addComplianceView($tcHdlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->overrideTestRowData(null, null, 149.999, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '<150');
        $triView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'cholesterol'));
        $triView->emptyLinks();
        $triView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triView->setAttribute('screening_view', true);
        $group->addComplianceView($triView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'blood_sugars'));
        $gluView->emptyLinks();
        $gluView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $group->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->overrideTestRowData(null, null, 29.999, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '<30');
        $bmiView->setAlternativeComplianceView(new PorterPipeLearningAlternativeComplianceView($programStart, $programEnd, 'bmi'));
        $bmiView->emptyLinks();
        $bmiView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $group->addComplianceView($bmiView);
        
        $healthCoaching = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthCoaching->setReportName('Met Requirement for Health Coaching');
        $healthCoaching->setName('health_coaching');
        $healthCoaching->setStatusSummary(ComplianceStatus::COMPLIANT, '2 Sessions');
        $healthCoaching->addLink(new Link('2 Health Coaching Session', '/content/83526'));
        $healthCoaching->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $group->addComplianceView($healthCoaching);

        $this->addComplianceViewGroup($group);
    }
}

class PorterPipe2014ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:7.6in;
                margin:0 0.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
            }

            #results th {
                background-color:#FFFFFF;
            }

            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#DEDEDE;
            }

            #results td.your-result {
                text-align:left;
            }
        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">

            <p style="text-align:center;font-size:18pt;font-weight:bold;">Healthy Measures</p>

            <p style="margin-top:0.5in;margin-left:0.75in;">
                <br/> <br/> <br/> <br/> <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <?php if($this->shouldShowPassTable($status)) : ?>
                <p>Thank you for participating in the 2014 Wellness Screening. Porter Pipe & Supply is committed to helping you
                    achieve your best health. In partnership with Health Maintenance Institute, Inc., Porter Pipe has selected six "Healthy
                Measures" for you to achieve.</p>

                <p>As communicated during open enrollment, rewards for participating in the wellness program are available to all employees.
                Participants who complete a verified Non-Tobacco User Affidavit and fall within the acceptable range for at least
                <span class="bund">four</span> out of the six "Healthy Measures" shown below will be eligible for a wellness discount for the 2014 plan year.</p>

                <?php echo $this->getPassTable($status) ?>

                <p>CONGRATULATIONS! Based on your results above, you fall within the acceptable range for at least four "Healthy Measures".
                    The amount of the wellness discount that you are eligible for is indicated on the Program
                    Outline form included in the results packet mailed to your home.</p>
                
                <p>If you have any questions, please contact Kayla Roeske at Health Maintenance Institute: (847)635-6580 or 
                    <a href="mailto:kayla@hmihealth.com">kayla@hmihealth.com</a></p>

            <?php else : ?>
                <p>Thank you for participating in the 2014 Wellness Screening. Porter Pipe & Supply is committed to helping you
                    achieve your best health. In partnership with Health Maintenance Institute, Inc., Porter Pipe has selected six "Healthy
                Measures" for you to achieve.</p>

                <p>As communicated during open enrollment, rewards for participating in the wellness program are available to all employees.
                Participants who complete a verified Non-Tobacco User Affidavit and fall within the acceptable range for at least
                <span class="bund">four</span> out of the six "Healthy Measures" shown below will be eligible for a wellness discount for the 2014 plan year.</p>

                <p>If you do not fall within the acceptable range for at least four measures, or are medically unable to meet a standard for
                    reward under this wellness program, you may qualify for an opportunity to earn the same reward by a different means.</p>

                <?php echo $this->getFailTable($status) ?>

                <p>Based on your results above, you <span class="bund">DO NOT</span> fall within the acceptable
                    range for at least four "Healthy Measures". Although you do not immediately qualify for the wellness discount, you may still
                    earn the reward by completing 1 elearning lesson that correspond with each "healthy measure" that is out of range AND 2 Heath Coaching
                    Sessions total. This is a Reasonable Alternative Standard that allows you an opportunity to earn the same reward by a different means.</p>

                <p>Instructions for completing the Alternative Stardard are included in the results packet mailed to your home. You may also
                    contact Kayla Roeske at Health Maintenance Institute (<a href="mailto:kayla@hmihealth.com">kayla@hmihealth.com</a> or (847)635-6580
                    and we will work with you (and if you wish, your doctor) to find a program with the same reward that is right for you 
                    in light of your health status.</p>
            <?php endif ?>

            <p>&nbsp;</p>


        </div>

        <?php
    }

    private function getPassTable($status)
    {
        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <thead>
                    <tr>
                        <th>"Healthy Measure"</th>
                        <th>Acceptable Range</th>
                        <th>Your Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php if($viewStatus->getComplianceView()->getName() == 'health_coaching') continue ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?></td>
                            <td class="your-result">
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
    
    private function getFailTable($status)
    {
        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <thead>
                    <tr>
                        <th>"Healthy Measure"</th>
                        <th>Acceptable Range</th>
                        <th>Your Result</th>
                        <th>Alternative</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?></td>
                            <td class="your-result">
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) { 
                                    echo $link;
                                } ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }    

    /**
     * To show the pass table, a user has to be compliant for the program, and be compliant
     * without considering elearning lessons.
     */
    private function shouldShowPassTable($status)
    {        
        if(!$status->isCompliant()) {
            return false;
        }

        $compliantStatuses = array(ComplianceStatus::COMPLIANT, ComplianceStatus::NA_COMPLIANT);

        $numberOriginallyCompliant = 0;
        
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();

                if($view->getAttribute('screening_view')) {
                    if($viewStatus->getAttribute('original_status') === null && $viewStatus->isCompliant()) {
                        // Alternative wasn't executed, so original_status is null. View still compliant

                        $numberOriginallyCompliant++;
                    }
                }
            }
        }

        return $numberOriginallyCompliant >= 4;
    }
    
}