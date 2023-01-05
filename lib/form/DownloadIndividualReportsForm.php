<?php

class DownloadIndividualReports extends BaseForm
{
    public function configure()
    {
        $this->setWidgets(array(
            'compliance_program_record_id' => new sfWidgetFormDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'query' => $this->getOption('query'))),
            'disable_layout'               => new sfWidgetFormInputCheckbox(),
            'user_ids'                     => new sfWidgetFormTextarea()
        ));

        $this->setValidators(array(
            'disable_layout'               => new sfValidatorBoolean(),
            'user_ids'                     => new sfValidatorString(array('required' => false)),
            'compliance_program_record_id' => new sfValidatorDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'query' => $this->getOption('query'))),
        ));

        $this->getWidgetSchema()->setHelps(array(
            'disable_layout'               => 'If checked, the site layout will not be included in the PDFs',
            'user_ids'                     => 'If left blank, defaults to all of the users for the specified program. Separated by new lines or commas, e.g. 46258,46259',
            'compliance_program_record_id' => 'The program to render'
        ));
    }
}