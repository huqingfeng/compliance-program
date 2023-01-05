<?php

class BatchAdminReportForm extends BaseForm
{
    public function configure()
    {
        $this->setWidgets(array(
            'parent_client_id' => new sfWidgetFormDoctrineChoice(array('model' => 'Client')),
            'active_only'      => new sfWidgetFormInputCheckbox()
        ));

        $this->setValidators(array(
            'parent_client_id' => new sfValidatorDoctrineChoice(array('model' => 'Client')),
            'active_only'      => new sfValidatorBoolean()
        ));

        $this->widgetSchema->setHelps(array(
            'parent_client_id' => 'This report will include compliance records that belong to all descendants of this client',
            'active_only'      => 'If checked, only programs with the active flag enabled will be included.'
        ));

        $this->setDefaults(array(
            'active_only'      => true
        ));
    }
}