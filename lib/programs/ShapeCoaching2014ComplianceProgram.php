<?php


class FitnessLogComplianceView extends ComplianceView
{
    public function __construct($month)
    {
        $this->month = $month;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('shape_coaching_2015', true);

        switch(ShapeCoaching2014ComplianceProgram::getLevel($user)) {
            case 3:
                $cardio = 480;
                $strength = 360;
                break;

            case 2:
                $cardio = 360;
                $strength = 240;
                break;

            case 1:
            default:
                $cardio = 240;
                $strength = 0;
                break;
        }
        
        $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
        
        if($record->getDataFieldValue("{$this->month}_cardio")) {
            $userCardio = (int) $record->getDataFieldValue("{$this->month}_cardio");
        } else {
            $userCardio = 0;
            foreach($dates as $date) {
                $userCardio += (int) $record->getDataFieldValue("{$this->month}_{$date}_cardio");
            }
        }

        if($record->getDataFieldValue("{$this->month}_strength")) {
            $userStrength = (int) $record->getDataFieldValue("{$this->month}_strength");
        } else {
            $userStrength = 0;
            foreach($dates as $date) {
                $userStrength += (int) $record->getDataFieldValue("{$this->month}_{$date}_strength");
            }
        }
        
        $compliant = $userCardio >= $cardio && $userStrength >= $strength;
        
        $dateSubmitted = null;
        if($record->getDataFieldValue("{$this->month}_date_entered")) {
            $dateSubmitted = $record->getDataFieldValue("{$this->month}_date_entered");
        } else {
            foreach($dates as $date) {
                $dateSubmitted = $record->getDataFieldValue("{$this->month}_{$date}_date_entered");
            }
        }

        $status = new ComplianceViewStatus($this, $compliant ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
        $status->setAttribute('strength_minutes', $userStrength);
        $status->setAttribute('cardio_minutes', $userCardio);
        $status->setAttribute('date_submitted', $dateSubmitted);

        return $status;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "fitness_log_{$this->month}";
    }


    public function getDefaultReportName()
    {
        return $this->month;
    }

    private $month;
}

class FitnessMinutesForm extends BaseForm
{


    public function configure()
    {
        $user = $this->getOption('user');
        $months = ShapeCoaching2014ComplianceProgram::getFitnessMonths();
        $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
        $lastMonth = date('F', strtotime("-30 days"));
        $thisMonth = date('F');
        
        $limitedMonths = array('');
        foreach($months as $month) {
            if(in_array($month, array($lastMonth, $thisMonth))) {
                $limitedMonths[] = $month;
            }
        }
        
        $dayChoices = array_merge(array('0' => ''), $dates);
        
        $this->setWidgets(array(
            'month' => new sfWidgetFormChoice(array('choices' => array_combine($limitedMonths, $limitedMonths))),
            'date' => new sfWidgetFormChoice(array('choices' => $dayChoices)),
            'cardio' => new sfWidgetFormInputText(),
            'strength' => new sfWidgetFormInputText(),
            'user_id'  => new sfWidgetFormInputHidden(),
        ));

        $this->setValidators(array(
            'month' => new sfValidatorChoice(array('choices' => $limitedMonths)),
            'date' => new sfValidatorChoice(array('choices' => array_keys($dayChoices), 'required' => false)),
            'cardio' => new sfValidatorInteger(array('min' => 0)),
            'strength' => new sfValidatorInteger(array('min' => 0)),
            'user_id'  => new sfValidatorString(array('required' => false))
        ));
        
        $this->widgetSchema->setHelp('date', 'If you would like to enter your minutes for the whole month, don’t enter a date in this field. Select month only.');
        $this->widgetSchema->setHelp('strength', 'Strength Training is not required for Level One of the program. If that’s you, please type the number zero in this field.');
        
        $defaults['user_id'] = $user->getId();
        
        $this->setDefaults($defaults);
    }
}

class ShapeCoaching2014ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public $headerText = 'The first entry of Fitness Minutes for the 2014-2015 program year will be for the month of July, 2014.';

    public function printReport(ComplianceProgramStatus $status)
    {
        $id = $status->getComplianceProgram()->getHardcodedId();

        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness');
        $fitnessGroupStatus = $status->getComplianceViewGroupStatus('fitness');
        $stressGroupStatus = $status->getComplianceViewGroupStatus('stress');
        $extraWellnessGroupStatus = $status->getComplianceViewGroupStatus('extra_wellness');

        $coachData = $status->getComplianceProgram()->getCoachingData($status->getUser());
        
        $coachNotes = isset($coachData['type_communication_current_note']) ?
            $coachData['type_communication_current_note'] : '';

        ?>
        <style type="text/css">
            #report {
                width:100%;
            }

            #report th, #report td {
                padding:6px;
            }

            #report .header th {
                background-color:#0066cc;
                font-weight:normal;
                color:#FFF;
            }

            #report .light, #legend .light {
                width:25px;
            }

            #report .compliant,
            #report .date_submitted,
            #report .minutes_logged,
            #report th.additional_info {
                text-align:center;
            }
        </style>

        <div class="page-header">
            <h4 style="display:inline">
                <?php echo $status->getUser() ?>
            </h4>
            <div>
                <h5><?php echo $this->headerText ?></h5>
                <br />
                <br />
<!--                <a class="btn btn-primary" href="/compliance_programs/localAction?id=--><?php //echo $id ?><!--&local_action=fitness_logs">Log Activity Minutes</a>-->
            </div>

            <div style="clear:both"></div>
        </div>

        <br/>

        <div id="legend" style="text-align:center">
            <?php
            foreach($status->getComplianceProgram()->getComplianceStatusMapper()->getMappings() as $sstatus => $mapping) :
                if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT || $status->getComplianceProgram()
                    ->hasPartiallyCompliantStatus()
                ) :
                    ?>
                        <img src="<?php echo $mapping->getLight() ?>" class="light" alt=""/>
                        = <?php echo $mapping->getText() ?>
                    <?php
                endif;
            endforeach
            ?>
        </div>

        <br/>

        <table id="report">
            <tr class="header">
                <th><?php echo $wellnessGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <th colspan="3"></th>
                <th class="compliant">Completed</th>
            </tr>

            <?php $i = 1 ?>
            <?php foreach($wellnessGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <tr>
                    <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                    <?php  if($viewStatus->getComplianceView()->getName() == 'initial') : 
                                $noteName = 'wellness_consultation_initial1_text';
                            else :
                                $noteName = 'wellness_consultation_session'.$i.'_text';
                                $i++;
                            endif;
                    ?>
                    <td class="additional_info" style="vertical-align:top" colspan="3">
                        <?php echo isset($coachData[$noteName]) ? $coachData[$noteName] : '' ?>
                    </td>
                    <td class="compliant">
                        <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                    </td>
                </tr>
            <?php endforeach ?>

            <tr class="header">
                <th><?php echo $requiredGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <th class="additional_info" colspan="3">Additional Info</th>
                <th class="compliant">Completed</th>
            </tr>

            <?php $i = 1 ?>
            <?php foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <tr>
                    <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                    <?php $noteName = 'nutrition_nicotine_session'.$i.'_text' ?>
                    <td class="additional_info" style="vertical-align:top" colspan="3">
                        <?php echo isset($coachData[$noteName]) ? $coachData[$noteName] : '' ?>
                    </td>
                    <td class="compliant">
                        <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                    </td>
                </tr>
                <?php $i++ ?>
            <?php endforeach ?>

            <tr class="header">
                <th><?php echo $stressGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <th class="additional_info" colspan="3">Additional Info</th>
                <th class="compliant">Completed</th>
            </tr>

            <?php foreach($stressGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <tr>
                    <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                    <?php $noteName = 'stress_management_session_text' ?>
                    <td class="additional_info" style="vertical-align:top" colspan="3">
                        <?php echo isset($coachData[$noteName]) ? $coachData[$noteName] : '' ?>
                    </td>
                    <td class="compliant">
                        <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                    </td>
                </tr>
            <?php endforeach ?>

            <tr class="header">
                <th colspan="4"><?php echo $extraWellnessGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <th class="compliant">Completed</th>
            </tr>

            <?php $i = 1 ?>
            <?php foreach($extraWellnessGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <tr>
                    <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                    <?php $noteName = 'extra_wellness_element_activity'.$i.'_text' ?>
                    <td class="additional_info" style="vertical-align:top" colspan="3">
                        <?php echo isset($coachData[$noteName]) ? $coachData[$noteName] : '' ?>
                    </td>
                    <td class="compliant">
                        <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                    </td>
                </tr>
                <?php $i++ ?>
            <?php endforeach ?>

            <tr class="header">
                <th><?php echo $fitnessGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <th class="date_submitted">Date Submitted</th>
                <th class="minutes_logged">Cardio Minutes</th>
                <th class="minutes_logged">Strength Minutes</th>
                <th class="compliant">Completed</th>
            </tr>

            <?php foreach($fitnessGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <tr>
                    <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                    <td class="date_submitted">
                        <?php if($dateSubmitted = $viewStatus->getAttribute('date_submitted')) : ?>
                            <?php echo date('m/d/Y', strtotime($dateSubmitted)) ?>
                        <?php endif ?>
                    </td>
                    <td class="minutes_logged"><?php echo $viewStatus->getAttribute('cardio_minutes') ?></td>
                    <td class="minutes_logged"><?php echo $viewStatus->getAttribute('strength_minutes') ?></td>
                    <td class="compliant">
                        <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                    </td>
                </tr>
            <?php endforeach ?>
        </table>

        <br/>

        <p>If you have questions about any of your requirements for the Weight Mgt. or
            Nicotine program, please contact your coach directly.</p>
        <?php
    }
}

class ShapeCoaching2014ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        return $user->hasAttribute(Attribute::COACHING_END_USER);
    }
}

class ShapeCoaching2014ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new ShapeCoaching2014ComplianceProgramAdminReportPrinter();
        $months = ShapeCoaching2014ComplianceProgram::getFitnessMonths();
        $that = $this;

        $printer->addCallbackField('coach_name', function(User $user) {
            $mostRecentSession = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
            if(is_object($mostRecentSession)) {
                return $mostRecentSession->getCoachUser()->getFullName();
            } else {
                return null;
            }
        });

        $printer->addCallbackField('level', function(User $user) {
            return ShapeCoaching2014ComplianceProgram::getLevel($user);
        });

        $printer->addStatusFieldCallback('total_points_without_extra_welness', function(ComplianceProgramStatus $status) {
            return ShapeCoaching2014ComplianceProgram::getTotalPoints($status);
        });

        $printer->addStatusFieldCallback('total_points_with_extra_welness', function(ComplianceProgramStatus $status) {
            return ShapeCoaching2014ComplianceProgram::getTotalPoints($status, true);
        });

        $printer->addEndCallbackField('program_type', function(User $user) use($that) {
            return $that->getProgramType($user);
        });

        $printer->addCallbackField('requirement_exempt', function(User $user) {
            $coachingData = ShapeCoaching2014ComplianceProgram::getCoachingData($user);
            if(isset($coachingData['requirement_exempt']) && $coachingData['requirement_exempt']) {
                return 'Yes';
            }

            return null;
        });

        $printer->addStatusFieldCallback('wellness_bonus', function(ComplianceProgramStatus $status) {
            $isWellnessBonus = true;
            $extraWellnessCompleted = false;

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                if($groupStatus->getComplianceViewGroup()->getName() == 'wellness') {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if($viewStatus->getStatus() != ComplianceViewStatus::COMPLIANT) $isWellnessBonus = false;
                    }
                } elseif ($groupStatus->getComplianceViewGroup()->getName() == 'required') {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if($viewStatus->getStatus() != ComplianceViewStatus::COMPLIANT) $isWellnessBonus = false;
                    }
                } elseif($groupStatus->getComplianceViewGroup()->getName() == 'extra_wellness') {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $extraWellnessCompleted = true;
                    }
                } elseif($groupStatus->getComplianceViewGroup()->getName() == 'fitness') {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if($viewStatus->getStatus() != ComplianceViewStatus::COMPLIANT) $isWellnessBonus = false;
                    }
                }
            }

            if($isWellnessBonus && $extraWellnessCompleted) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        foreach($months as $month) {
            $printer->addCallbackField('total_minutes_cardio_'.$month, function(User $user) use ($month) {
                $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord('shape_coaching_2015');

                $userCardio = 0;
                if($record->exists()) {
                    if($record->getDataFieldValue("{$month}_cardio")) {
                        $userCardio = (int) $record->getDataFieldValue("{$month}_cardio");
                    } else {
                        foreach($dates as $date) {
                            $userCardio += (int) $record->getDataFieldValue("{$month}_{$date}_cardio");
                        }
                    }
                }

                return $userCardio;
            });

            $printer->addCallbackField('total_minutes_strength_'.$month, function(User $user) use ($month) {
                $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
                $record = $user->getNewestDataRecord('shape_coaching_2015');

                $userStrength = 0;
                if($record->exists()) {
                    if($record->getDataFieldValue("{$month}_strength")) {
                        $userStrength = (int) $record->getDataFieldValue("{$month}_strength");
                    } else {
                        foreach($dates as $date) {
                            $userStrength += (int) $record->getDataFieldValue("{$month}_{$date}_strength");
                        }
                    }
                }

                return $userStrength;
            });
        }

        $printer->setShowTotals(false);

        return $printer;
    }

    public function getHardcodedId()
    {
        return 322;
    }

    public static function getFitnessMonths()
    {
        return array(
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
            'January',
            'February',
            'March',
            'April'
        );
    }
    
    public static function getFitnessDates()
    {
        $dates = array();
        for($i=1; $i<=31; $i++) {
            $dates[$i] = $i;
        }
        
        return $dates;
    }

    public static function getLevel(User $user)
    {
        $coachingData = self::_getCoachingData($user);

        $selectedLevel = isset($coachingData['fitness_level']) ?
            $coachingData['fitness_level'] : 'level_1';

        switch($selectedLevel) {
            case 'level_3': return 3;
            case 'level_2': return 2;
            case 'level_1': return 1;

            default:        return 1;
        }
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ShapeCoaching2014ComplianceProgramReportPrinter();
    }

    protected function getCoachingFieldEvaluator($field, $requiredFunction)
    {
        $program = $this;

        return function(ComplianceViewStatus $status, User $user) use($program, $field, $requiredFunction) {
            $coachingData = $program->getCoachingData($user);

            if(!call_user_func($requiredFunction, $user)) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            } elseif(isset($coachingData[$field]) && $coachingData[$field]) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        };
    }
    
    protected function getCoachingExtraWellnessFieldEvaluator($field, $notRequiredField)
    {
        $program = $this;
        return function(ComplianceViewStatus $status, User $user) use($program, $field, $notRequiredField) {
            $coachingData = $program->getCoachingData($user);
            
            if(isset($coachingData[$notRequiredField]) && $coachingData[$notRequiredField]) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            } elseif(isset($coachingData[$field]) && $coachingData[$field]) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        };
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Completed', '/images/lights/greenlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Completed', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Required', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $nicotineRequired = array($this, 'nicotineIsRequired');
        $wellnessRequired = array($this, 'wellnessIsRequired');
        $stressManagementRequired = array($this, 'stressManagementIsRequired');

        $wellness = new ComplianceViewGroup('wellness', 'Wellness Consultations');

        $initialView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $initialView->setName('initial');
        $initialView->setReportName('Initial');
        $initialView->setPostEvaluateCallback($this->getCoachingFieldEvaluator('wellness_consultation_initial1_checkbox', $wellnessRequired));
        $wellness->addComplianceView($initialView);

        $sessionOne = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionOne->setName('session_1');
        $sessionOne->setReportName('Session 1 (July-Sept 30)');
        $sessionOne->setPostEvaluateCallback($this->getCoachingFieldEvaluator('wellness_consultation_session1_checkbox', $wellnessRequired));
        $wellness->addComplianceView($sessionOne);

        $sessionTwo = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionTwo->setName('session_2');
        $sessionTwo->setReportName('Session 2 (Oct-Dec 30)');
        $sessionTwo->setPostEvaluateCallback($this->getCoachingFieldEvaluator('wellness_consultation_session2_checkbox', $wellnessRequired));
        $wellness->addComplianceView($sessionTwo);

        $sessionThree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionThree->setName('session_3');
        $sessionThree->setReportName('Session 3 (Jan-Apr 30)');
        $sessionThree->setPostEvaluateCallback($this->getCoachingFieldEvaluator('wellness_consultation_session3_checkbox', $wellnessRequired));
        $wellness->addComplianceView($sessionThree);

        $this->addComplianceViewGroup($wellness);

        $required = new ComplianceViewGroup('required', 'Nutrition/Nicotine Elements');

        $sessionOne = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionOne->setName('req_session_1');
        $sessionOne->setReportName('Session 1 (July-Sept 30)');
        $sessionOne->setPostEvaluateCallback($this->getCoachingFieldEvaluator('nutrition_nicotine_session1_checkbox', $nicotineRequired));
        $required->addComplianceView($sessionOne);

        $sessionTwo = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionTwo->setName('req_session_2');
        $sessionTwo->setReportName('Session 2 (Oct-Dec 30)');
        $sessionTwo->setPostEvaluateCallback($this->getCoachingFieldEvaluator('nutrition_nicotine_session2_checkbox', $nicotineRequired));
        $required->addComplianceView($sessionTwo);

        $sessionThree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sessionThree->setName('req_session_3');
        $sessionThree->setReportName('Session 3 (Jan-Apr 30)');
        $sessionThree->setPostEvaluateCallback($this->getCoachingFieldEvaluator('nutrition_nicotine_session3_checkbox', $nicotineRequired));
        $required->addComplianceView($sessionThree);

        $this->addComplianceViewGroup($required);

        $stress = new ComplianceViewGroup('stress', 'Stress Management');

        $training = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $training->setName('training');
        $training->setReportName('1 per year');
        $training->setPostEvaluateCallback($this->getCoachingFieldEvaluator('stress_management_session_checkbox', $stressManagementRequired));
        $stress->addComplianceView($training);

        $this->addComplianceViewGroup($stress);

        $extraWellness = new ComplianceViewGroup('extra_wellness', 'Extra Wellness Items');

        for($itemNumber = 1; $itemNumber <= 10; $itemNumber++) {
            $view = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
            $view->setName('extra_wellness_'.$itemNumber);
            $view->setReportName('Activity '.$itemNumber);
            $view->setPostEvaluateCallback($this->getCoachingExtraWellnessFieldEvaluator('extra_wellness_element_activity'.$itemNumber.'_checkbox', 'extra_wellness_element_activity'.$itemNumber.'_not_required'));

            $extraWellness->addComplianceView($view);
        }

        $this->addComplianceViewGroup($extraWellness);

        $fitness = new ComplianceViewGroup('fitness', 'Fitness Logs');

        foreach(self::getFitnessMonths() as $month) {
            $view = new FitnessLogComplianceView($month);

            $fitness->addComplianceView($view);
        }

        $this->addComplianceViewGroup($fitness);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $coachData = $this->getCoachingData($user);

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            if($groupStatus->getComplianceViewGroup()->getName() == 'wellness') {
                $i = 1;
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    if($viewStatus->getComplianceView()->getName() == 'initial') {
                        $noteName = 'wellness_consultation_initial1_text';
                    } else {
                        $noteName = 'wellness_consultation_session'.$i.'_text';
                        $i++;
                    }
                    if(isset($coachData[$noteName])) {
                        $viewStatus->setComment($coachData[$noteName]);
                    }
                }
            } elseif ($groupStatus->getComplianceViewGroup()->getName() == 'required') {
                $i = 1;
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $noteName = 'nutrition_nicotine_session'.$i.'_text';
                    if(isset($coachData[$noteName])) {
                        $viewStatus->setComment($coachData[$noteName]);
                    }
                    $i++;
                }
            } elseif ($groupStatus->getComplianceViewGroup()->getName() == 'stress') {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $noteName = 'stress_management_session_text';
                    if(isset($coachData[$noteName])) {
                        $viewStatus->setComment($coachData[$noteName]);
                    }
                }
            } elseif($groupStatus->getComplianceViewGroup()->getName() == 'extra_wellness') {
                $i = 1;
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $noteName = 'extra_wellness_element_activity'.$i.'_text';
                    if(isset($coachData[$noteName])) {
                        $viewStatus->setComment($coachData[$noteName]);
                    }
                    $i++;
                }
            }
        }

        if($this->getProgramType($user) == 'Nicotine program' && $this->getTotalPoints($status) >= 8) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($this->getProgramType($user) == 'Weight Management' && $this->getTotalPoints($status) >= 21) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }
    }

    public function getLocalActions()
    {
        return array(
            'fitness_logs' => array($this, 'executeFitnessLogs'), 
            'delete_fitness_minutes' => array($this, 'executeDeleteFitnessMinutes')
            );
    }
    
    public function executeDeleteFitnessMinutes(sfActions $actions)
    {
        $user = $this->findRequestUser($actions);
        
        $recordID = $actions->getRequestParameter('record_id');
        $month = $actions->getRequestParameter('month');
        $date = $actions->getRequestParameter('date');
        
        $record = new UserDataRecord($recordID);
        
        if($record->exists()) {
            if($date && !empty($date)) {
                $record->getDataField("{$month}_{$date}_cardio")->delete();
                $record->getDataField("{$month}_{$date}_strength")->delete();
                $record->getDataField("{$month}_{$date}_date_entered")->delete();
            } else {
                $record->getDataField("{$month}_cardio")->delete();
                $record->getDataField("{$month}_strength")->delete();
                $record->getDataField("{$month}_date_entered")->delete();
            }
            $record->save();
            
            $actions->getUser()->setNoticeFlash('Your activity for '.$month.' '.$date.' has been deleted.');

            $actions->redirect(sprintf(
                '/compliance_programs/localAction?id=%s&local_action=fitness_logs&user_id=%s',
                $this->getHardcodedId(),
                $user->getId()
            ));
        }
        
        
    }

    public function executeFitnessLogs(sfActions $actions)
    {
        $user = $this->findRequestUser($actions);

        $fitnessForm = new FitnessMinutesForm(array(), array('user' => $user));
        $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
        
        if($fitnessForm->isValidForRequest($actions->getRequest())) {
            $record = $user->getNewestDataRecord('shape_coaching_2015', true);
            if($fitnessForm->getValue('date')) {
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_date_entered", date('Y-m-d'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_cardio", $fitnessForm->getValue('cardio'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_{$fitnessForm->getValue('date')}_strength", $fitnessForm->getValue('strength'));
                
                $record->getDataField("{$fitnessForm->getValue('month')}_cardio")->delete();
                $record->getDataField("{$fitnessForm->getValue('month')}_strength")->delete();
                $record->getDataField("{$fitnessForm->getValue('month')}_date_entered")->delete();
                
            } else {
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_date_entered", date('Y-m-d'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_cardio", $fitnessForm->getValue('cardio'));
                $record->setDataFieldValue("{$fitnessForm->getValue('month')}_strength", $fitnessForm->getValue('strength')); 
                
                foreach($dates as $date) {
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_cardio")->delete();
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_strength")->delete();
                    $record->getDataField("{$fitnessForm->getValue('month')}_{$date}_date_entered")->delete();
                }
            }

            $record->save();

            $actions->getUser()->setNoticeFlash(
                    sprintf('Your activity for '.$fitnessForm->getValue('month').' %s was saved.', 
                            $fitnessForm->getValue('date') != 0 ? $fitnessForm->getValue('date') : '')
                );

            if($user->getId() != $actions->getSessionUser()->getId()) {
                $actions->redirect(sprintf(
                    '/compliance_programs/localAction?id=%s&local_action=fitness_logs&user_id=%s',
                    $this->getHardcodedId(),
                    $user->getId()
                ));
            }

            $actions->redirect('/compliance_programs?id='.$this->getHardcodedId());
        }
        
        
        $record = $user->getNewestDataRecord('shape_coaching_2015');
        $lastMonth = date('F', strtotime("-32 days"));
        $thisMonth = date('F');
        
        ?>
        <style type="text/css">
            #fitness_log_levels {
                text-align:center;
            }
            
            #previous_entries_table tr th, #previous_entries_table tr td {
                padding: 10px 10px 10px 10px;
                text-align: center;
            }
        </style>

        <div id="fitness_logs">
            <p><?php echo $user->getFullName() ?></p>
            <p>You have committed to: Level <?php echo ShapeCoaching2014ComplianceProgram::getLevel($user) ?></p>

            <hr/>

            <div class="row" id="fitness_log_levels">
                <div class="span4">
                    <h4>LEVEL 1</h4>
                    <br/><br/>
                    <strong>240 Minutes/Month</strong><br/>
                    Cardio only<br/>
                    Ex: 3 x 20 min/week
                </div>
                <div class="span4">
                    <h4>LEVEL 2</h4>
                    <br/><br/>
                    <strong>600 Minutes/Month</strong><br/>
                    360 minutes cardio<br/>
                    240 minutes strength <br/>
                    Ex: 3 x 30 min cardio <br/>
                    2 x 30 min strength
                </div>
                <div class="span4">
                    <h4>LEVEL 3</h4>
                    <br/><br/>
                    <strong>840 Minutes/Month</strong><br/>
                    480 minutes cardio<br/>
                    360 minutes strength <br/>
                    Ex: 4 x 30 min cardio <br/>
                    2 x 45 min strength
                </div>
            </div>

            <hr/>

            <p>To log your fitness minutes, fill out the form below and select SUBMIT. Please be aware that you can only log current month and the month prior.</p>

            <br/>

            <?php echo $fitnessForm->renderFormTag("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=fitness_logs") ?>
                <ul>
                    <?php echo $fitnessForm ?>
                </ul>
                <div class="form-actions">
                    <p>By selecting SUBMIT, I attest that the information I am submitting is accurate to the
                        best of my knowledge.
                    </p>

                    <input type="submit" class="btn btn-primary" value="SUBMIT" />
                </div>
            </form>
            
            <?php if($record->exists() && count($record->getAllDataFieldValues()) > 0) : ?>
            <table id="previous_entries_table">
                <tr><th>Month</th><th>Date</th><th>Cardio Minutes</th><th>Strength Minutes</th><th>Date Entered</th><th></th></tr>
            <?php $lastMonthTotal = true; ?>
            <?php foreach($dates as $date) : ?>
            <?php if($record->getDataFieldValue("{$lastMonth}_{$date}_cardio") && $record->getDataFieldValue("{$lastMonth}_{$date}_strength")) : ?>
                <tr>
                    <td><?php echo $lastMonth ?></td>
                    <td><?php echo $date ?></td>
                    <td><?php echo $record->getDataFieldValue("{$lastMonth}_{$date}_cardio"); ?></td>
                    <td><?php echo $record->getDataFieldValue("{$lastMonth}_{$date}_strength"); ?></td>
                    <td><?php echo date('m/d/Y', strtotime($record->getDataFieldValue("{$lastMonth}_{$date}_date_entered"))); ?></td>
                    <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                        <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                        <input type="hidden" name="month" value="<?php echo $lastMonth ?>" />
                        <input type="hidden" name="date" value="<?php echo $date ?>" />
                        <button type="submit" class="btn" id="search-submit"> Delete</button>
                    </form></td>
                </tr>
            <?php $lastMonthTotal = false ?>
            <?php endif ?>
            <?php endforeach; ?>
            <?php if($lastMonthTotal
                    && $record->getDataFieldValue("{$lastMonth}_cardio") 
                    && $record->getDataFieldValue("{$lastMonth}_strength")) : ?>
                <tr>
                    <td><?php echo $lastMonth ?></td>
                    <td><?php echo 'For the whole month' ?></td>
                    <td><?php echo $record->getDataFieldValue("{$lastMonth}_cardio"); ?></td>
                    <td><?php echo $record->getDataFieldValue("{$lastMonth}_strength"); ?></td>
                    <td><?php echo  date('m/d/Y', strtotime($record->getDataFieldValue("{$lastMonth}_date_entered"))); ?></td>
                    <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                        <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                        <input type="hidden" name="month" value="<?php echo $lastMonth ?>" />
                        <button type="submit" class="btn" id="search-submit"> Delete</button>
                    </form></td>
                </tr>
            <?php endif ?>
                
                
            <?php $thisMonthTotal = true; ?>
            <?php foreach($dates as $date) : ?>
            <?php if($record->getDataFieldValue("{$thisMonth}_{$date}_cardio") && $record->getDataFieldValue("{$thisMonth}_{$date}_strength")) : ?>
                <tr>
                    <td><?php echo $thisMonth ?></td>
                    <td><?php echo $date ?></td>
                    <td><?php echo $record->getDataFieldValue("{$thisMonth}_{$date}_cardio"); ?></td>
                    <td><?php echo $record->getDataFieldValue("{$thisMonth}_{$date}_strength"); ?></td>
                    <td><?php echo date('m/d/Y', strtotime($record->getDataFieldValue("{$thisMonth}_{$date}_date_entered"))); ?></td>
                    <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                        <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                        <input type="hidden" name="month" value="<?php echo $thisMonth ?>" />
                        <input type="hidden" name="date" value="<?php echo $date ?>" />
                        <button type="submit" class="btn" id="search-submit"> Delete</button>
                    </form></td>
                </tr>
            <?php $thisMonthTotal = false ?>
            <?php endif ?>
            <?php endforeach; ?>
            <?php if($thisMonthTotal
                    && $record->getDataFieldValue("{$thisMonth}_cardio") 
                    && $record->getDataFieldValue("{$thisMonth}_strength")) : ?>
                <tr>
                    <td><?php echo $thisMonth ?></td>
                    <td><?php echo 'For the whole month' ?></td>
                    <td><?php echo $record->getDataFieldValue("{$thisMonth}_cardio"); ?></td>
                    <td><?php echo $record->getDataFieldValue("{$thisMonth}_strength"); ?></td>
                    <td><?php echo  date('m/d/Y', strtotime($record->getDataFieldValue("{$thisMonth}_date_entered"))); ?></td>
                    <td><form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getHardcodedId()."&local_action=delete_fitness_minutes") ?>">
                        <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user->getId() ?>" />
                        <input type="hidden" name="month" value="<?php echo $thisMonth ?>" />
                        <button type="submit" class="btn" id="search-submit"> Delete</button>
                    </form></td>
                </tr>
            <?php endif ?>
            </table>
            <?php endif ?>
        </div>
        <?php
    }
    
    protected function findRequestUser(sfActions $actions)
    {
        $user = ($userId = $actions->getRequestParameter('user_id')) ? UserTable::getInstance()->find($userId) : $actions->getSessionUser();

        if (!$user) {
          $actions->forward404();
        }

        if (!$actions->getSessionUser()->canViewUser($user)) {
          $actions->forward401();
        }

        return $user;
    }

    public function nicotineIsRequired(User $user)
    {
        $coachingData = $this->getCoachingData($user);

        if(isset($coachingData['nutrition_nicotine_not_required']) && $coachingData['nutrition_nicotine_not_required']) {
            return false;
        } else {
            return true;
        }
    }

    public function wellnessIsRequired(User $user)
    {
        $coachingData = $this->getCoachingData($user);

        if(isset($coachingData['wellness_consultation_not_required']) && $coachingData['wellness_consultation_not_required']) {
            return false;
        } else {
            return true;
        }
    }

    public function extraWellnessIsRequired(User $user)
    {
        $coachingData = $this->getCoachingData($user);

        if(isset($coachingData['extra_wellness_element_not_required']) && $coachingData['extra_wellness_element_not_required']) {
            return false;
        } else {
            return true;
        }
    }
    
    public function stressManagementIsRequired(User $user)
    {
        $coachingData = $this->getCoachingData($user);
        
        if(isset($coachingData['stress_management_not_required']) && $coachingData['stress_management_not_required']) {
            return false;
        } else {
            return true;
        }
    }

    public static function getCoachingData(User $user)
    {
        return self::_getCoachingData($user);
    }

    public function getProgramType(User $user)
    {
        if($this->stressManagementIsRequired($user)) {
            return 'Nicotine program';
        } else {
            return 'Weight Management';
        }
    }

    public static function getTotalPoints(ComplianceProgramStatus $status, $addExtraWellnessPoints = false)
    {
        $user = $status->getUser();
        $coachingData = ShapeCoaching2014ComplianceProgram::_getCoachingData($user);
        $months = ShapeCoaching2014ComplianceProgram::getFitnessMonths();
        $dates = ShapeCoaching2014ComplianceProgram::getFitnessDates();
        $elearningAliasMapper = ShapeCoaching2015ComplianceProgram::getElearningAliasMapper();
        $elearningData = ShapeCoachesCoaching2015ComplianceProgram::_getElearningData($user);

        foreach($elearningAliasMapper as $alias => $elementName){
            if (isset($elearningData[$alias]) && $elearningData[$alias] !== false) {
                $coachingData[$elementName] = true;
            }
        }

        $points = 0;
        for ($i = 1; $i < 11; $i++) {
            if (isset($coachingData['extra_wellness_element_activity' . $i . '_checkbox'])
                && !isset($coachingData['extra_wellness_element_activity' . $i . '_not_required'])
                && $addExtraWellnessPoints) {
                $points += 2;
            }

            if (isset($coachingData['wellness_consultation_session' . $i . '_checkbox'])
                && !isset($coachingData['wellness_consultation_not_required'])) {
                $points += 1;
            }

            if (isset($coachingData['nutrition_nicotine_session' . $i . '_checkbox'])
                && !isset($coachingData['nutrition_nicotine_not_required'])) {
                $points += 2;
            }
        }

        if (isset($coachingData['stress_management_session_checkbox'])
            && !isset($coachingData['stress_management_not_required'])) {
            $points += 2;
        }

        $record = $user->getNewestDataRecord('shape_coaching_2015', true);


        foreach($months as $month) {
            if($record->getDataFieldValue("{$month}_cardio")) {
                $userCardio = (int) $record->getDataFieldValue("{$month}_cardio");
            } else {
                $userCardio = 0;
                foreach($dates as $date) {
                    $userCardio += (int) $record->getDataFieldValue("{$month}_{$date}_cardio");
                }
            }

            $fitnessViewComplianceStatus = $status->getComplianceViewStatus("fitness_log_{$month}");

            if($userCardio >= 240 || $fitnessViewComplianceStatus->getStatus() == ComplianceStatus::COMPLIANT) $points += 2;
        }

        return $points;
    }

    protected static function _getCoachingData(User $user)
    {
        if(self::$_coachingData['user_id'] == $user->id) {
            return self::$_coachingData['data'];
        } else {
            $defaults = array();

            $sessions = CoachingSessionTable::getInstance()->findActiveSessionsForUser($user);
            foreach($sessions as $session) {
                if(date('Y-m-d', strtotime($session->created_at)) < self::SHAPE_2014_COACHING_START_DATE
                    || date('Y-m-d', strtotime($session->created_at)) > self::SHAPE_2014_COACHING_END_DATE) {
                    continue;
                }

                $reports = CoachingReportTable::getInstance()->findActiveReports($session);

                foreach($reports as $report) {
                    if(!is_object($report)) continue;

                    $edit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);
                    if(is_object($edit)) {
                        $recordedDocument = $edit->getRecordedDocument();
                        $recordedFields = $recordedDocument->getRecordedDocumentFields();

                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();

                            if(!empty($value) && empty($defaults[$name])) {
                                    $defaults[$name] = $value;                  
                            }                        
                        }                
                    }
                }            
            }
            
            self::$_coachingData['user_id'] = $user->id;
            self::$_coachingData['data'] = $defaults;

            return $defaults;
        }
    }

    private static $_coachingData = array('user_id' => null, 'data' => array());

    const SHAPE_2014_COACHING_START_DATE = '2014-01-01';
    const SHAPE_2014_COACHING_END_DATE = '2015-05-31';
}
