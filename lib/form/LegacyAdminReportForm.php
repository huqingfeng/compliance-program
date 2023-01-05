<?php

class LegacyAdminReportForm extends BaseForm
{
    public function configure()
    {
        $this->setWidgets(array(
            'compliance_program_record_id' => new sfWidgetFormDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'query' => $this->getOption('query'))),
            'download'                     => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'include_expired'              => new sfWidgetFormInputCheckbox()
        ));

        $this->setValidators(array(
            'compliance_program_record_id' => new sfValidatorDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'query' => $this->getOption('query'))),
            'download'                     => new sfValidatorBoolean(),
            'include_expired'              => new sfValidatorBoolean()
        ));

        $this->widgetSchema->setHelps(array(
            'include_expired'              => 'If checked, expired users will be included in the result.'
        ));
    }

    public function getComplianceProgramRecord()
    {
        return ComplianceProgramRecordTable::getInstance()->find($this->getValue('compliance_program_record_id'));
    }
}