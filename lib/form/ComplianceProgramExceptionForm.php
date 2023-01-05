<?php

class ComplianceProgramExceptionForm extends BaseForm
{
    public function setup()
    {
        if(!($program = $this->getOption('compliance_program'))) {
            throw new MenagerieException('Missing option: compliance_program');
        }

        if(!($user = $this->getOption('user'))) {
            throw new MenagerieException('Missing option: user');
        }

        // TODO: Can doctrine collection key itself by a field ?
        $viewStatusOverrides = array();
        foreach($user->getComplianceViewStatusOverridesSortedByIdDesc() as $override) {
            if($override->getComplianceProgramRecordID() == $program->getID()) {
                $viewStatusOverrides[$override->getComplianceViewName()] = $override;
            }
        }

        $program->setActiveUser($user);

        $programStatus = $program->getStatus();

        foreach($program->getComplianceViewGroups() as $group) {
            $groupFields = array();

            foreach($group->getComplianceViews() as $view) {
                $viewStatusOverride = isset($viewStatusOverrides[$view->getName()]) ? $viewStatusOverrides[$view->getName()] : new ComplianceViewStatusOverride();
                $viewStatusOverride->setComplianceProgramRecordID($program->getID());
                $viewStatusOverride->setComplianceViewName($view->getName());
                $viewStatusOverride->setUserID($user->getID());

                $viewForm = new ComplianceViewExceptionForm($viewStatusOverride, array('compliance_view' => $view));
                $this->embedForm($view->getName(), $viewForm);
                $groupFields[] = $view->getName();

                $viewStatus = $programStatus->getComplianceViewStatus($view->getName());

                $name = '<div class="collapse-anchor">'.strip_tags($view->getReportName()).'</div>';

                if(($status = $viewStatus->getStatus()) !== null) {
                    $text = $viewStatus->getText();

                    switch($status) {
                        case ComplianceStatus::COMPLIANT:
                            $statusLabelClass = 'label-success';
                            break;

                        case ComplianceStatus::PARTIALLY_COMPLIANT:
                            $statusLabelClass = 'label-warning';
                            break;

                        case ComplianceStatus::NOT_COMPLIANT:
                            $statusLabelClass = 'label-important';
                            break;

                        case ComplianceStatus::NA_COMPLIANT:
                        default:
                            $statusLabelClass = 'label-inverse';
                    }

                    $name .= ' <span class="label '.$statusLabelClass.'">'.$text.'</span>';
                }

                if(($points = $viewStatus->getPoints()) !== null) {
                    if($maximumPoints = $viewStatus->getComplianceView()->getMaximumNumberOfPoints()) {
                        $pointMax = ' ('.$maximumPoints.')';
                    } else {
                        $pointMax = '';
                    }

                    $name .= ' <span class="label label-info">'.$viewStatus->getPoints().' Points'.$pointMax.'</span>';
                }

                $this->getWidgetSchema()->setLabel($view->getName(), $name);
            }

            $this->setGroup(strip_tags(sfInflector::underscore(str_replace(' ', '', $group->getName()))), $groupFields, $group->getReportName());
        }
    }

    public function save()
    {
        foreach($this->embeddedForms as $name => $form) {
            $values = $this->getValue($name);

            if($form->isEmpty($values)) {
                if($form->getObject()->exists()) {
                    $form->getObject()->delete();
                }
            } else {
                $form->saveEmbeddedForms();
                $form->updateObject($values);
                $form->getObject()->save();
            }
        }
    }
}