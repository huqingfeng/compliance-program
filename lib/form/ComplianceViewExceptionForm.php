<?php

class ComplianceViewExceptionForm extends ComplianceViewStatusOverrideForm
{
    public function setup()
    {
        parent::setup();

        if(!($view = $this->getOption('compliance_view'))) {
            throw new MenagerieException('Missing option: compliance_view');
        }

        $this->useFields($this->getFields());

        $statuses = array(null => 'Autocalculate') + ComplianceStatus::get(true);

        $this->setWidget('status', new sfWidgetFormChoice(array('choices' => $statuses)));
        $this->setValidator('status', new sfValidatorChoice(array('choices' => array_keys($statuses))));

        foreach($this->getFields() as $field) {
            $this->getValidator($field)->setOption('required', false);
        }

        $this->getWidgetSchema()->setHelps(array(
            'start_date'        => 'Defines the date that this exception will be effective on, relative to the program in question.',
            'end_date'          => 'Defines the date that this exception will be effective through, relative to the program in question.',
            'status'            => 'For the defaults (i.e. auto-calculated), leave blank. This changes the status for this view.',
            'points'            => 'For the defaults (i.e. auto-calculated), leave blank. This changes the points for this view.',
            'add_points'        => 'If checked, the provided points amount will be added to the user\'s points rather than replace it.',
            'comment'           => 'For the defaults (i.e. auto-calculated), leave blank. You may store, for example, the date of exemption or completion here.',
            'ignore_status_na'  => 'If checked, the exception will not count if the user is calculated as a status of NA.',
            'not_required'      => 'If checked, this user does not have to complete this view.',
            'new_start_date'    => 'If entered, this defines new requirement dates that the user can complete this view on or after. For defaults, leave blank.',
            'new_end_date'      => 'If entered, this defines new requirement dates that the user must complete this view by. For defaults, leave blank.'
        ));

        if(!($view instanceof DateBasedComplianceView)) {
            unset($this['new_start_date'], $this['new_end_date']);
        }

        // If the group isn't point based, the points are typically used just to drive
        // a status, so we want the override form to include status but not points
        // in such cases.
        if(!$view->allowPointsOverride()) {
            unset($this['points'], $this['add_points']);
        } else if($view->hasCompliancePointStatusMapper()) {
            unset($this['status']);
        }

    }

    private function getFields()
    {
        return array(
            'id',
            'ignore_status_na',
            'not_required',
            'status',
            'points',
            'add_points',
            'comment',
            'new_start_date',
            'new_end_date'
        );
    }

    public function isEmpty($values)
    {
        foreach($values as $field => $value) {
            if($field != 'id' && $value !== null) {
                // TODO: Rework this a bit. These fields have default values of false. If false isn't the value,
                // form is not empty.
                if(($field != 'ignore_status_na' && $field != 'not_required') || $value) {
                    return false;
                }
            }
        }

        return true;
    }
}