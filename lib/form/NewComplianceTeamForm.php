<?php

class NewComplianceTeamForm extends BaseForm
{
    public function configure()
    {
        $this->setWidget('name', new sfWidgetFormInput());
        $this->setValidator('name', new sfValidatorString(array('min_length' => 3, 'max_length' => 32)));

        $this->widgetSchema->setLabel('name', 'Team Name');
    }
}