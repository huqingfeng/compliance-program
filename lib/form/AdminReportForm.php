<?php

class AdminReportForm extends BaseForm
{
    public function configure()
    {
        $this->setWidgets(array(
            'id'                                      => new sfWidgetFormDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'table_method' => 'findActive')),
            'show_user_first_name'                    => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_last_name'                     => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_date_of_birth'                 => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_social_security_number_suffix' => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_social_security_number'        => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_address'                       => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_phone_numbers'                 => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_email_addresses'               => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_relationship_text'             => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_related_user_first_name'       => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_user_related_user_last_name'        => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_compliant_program'                  => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_compliant_group'                    => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_compliant_view'                     => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_status_program'                     => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_status_group'                       => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_status_view'                        => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_points_program'                     => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_points_group'                       => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_points_view'                        => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_comment_program'                    => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_comment_group'                      => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked')),
            'show_comment_view'                       => new sfWidgetFormInputCheckbox(array(), array('checked' => 'checked'))
        ));

        $this->setValidators(array(
            'id'                                      => new sfValidatorDoctrineChoice(array('model' => 'ComplianceProgramRecord', 'query' => ComplianceProgramRecordTable::getInstance()
                ->findActive())),
            'show_user_first_name'                    => new sfValidatorBoolean(),
            'show_user_last_name'                     => new sfValidatorBoolean(),
            'show_user_date_of_birth'                 => new sfValidatorBoolean(),
            'show_user_social_security_number_suffix' => new sfValidatorBoolean(),
            'show_user_social_security_number'        => new sfValidatorBoolean(),
            'show_user_address'                       => new sfValidatorBoolean(),
            'show_user_phone_numbers'                 => new sfValidatorBoolean(),
            'show_user_email_addresses'               => new sfValidatorBoolean(),
            'show_user_relationship_text'             => new sfValidatorBoolean(),
            'show_user_related_user_first_name'       => new sfValidatorBoolean(),
            'show_user_related_user_last_name'        => new sfValidatorBoolean(),
            'show_compliant_program'                  => new sfValidatorBoolean(),
            'show_compliant_group'                    => new sfValidatorBoolean(),
            'show_compliant_view'                     => new sfValidatorBoolean(),
            'show_status_program'                     => new sfValidatorBoolean(),
            'show_status_group'                       => new sfValidatorBoolean(),
            'show_status_view'                        => new sfValidatorBoolean(),
            'show_points_program'                     => new sfValidatorBoolean(),
            'show_points_group'                       => new sfValidatorBoolean(),
            'show_points_view'                        => new sfValidatorBoolean(),
            'show_comment_program'                    => new sfValidatorBoolean(),
            'show_comment_group'                      => new sfValidatorBoolean(),
            'show_comment_view'                       => new sfValidatorBoolean()
        ));
    }

    public function configurePrinter(BasicComplianceProgramAdminReportPrinter $printer)
    {
        if(!$this->isBound() || !$this->isValid()) {
            throw new MenagerieException('Form must be bound and valid.');
        }

        $printer->setShowComment(
            $this->getValue('show_comment_program'),
            $this->getValue('show_comment_group'),
            $this->getValue('show_comment_view')
        );

        $printer->setShowCompliant(
            $this->getValue('show_compliant_program'),
            $this->getValue('show_compliant_group'),
            $this->getValue('show_compliant_view')
        );

        $printer->setShowPoints(
            $this->getValue('show_points_program'),
            $this->getValue('show_points_group'),
            $this->getValue('show_points_view')
        );

        $printer->setShowStatus(
            $this->getValue('show_status_program'),
            $this->getValue('show_status_group'),
            $this->getValue('show_status_view')
        );

        $printer->setShowUserFields(
            $this->getValue('show_user_first_name'),
            $this->getValue('show_user_last_name'),
            $this->getValue('show_user_date_of_birth'),
            $this->getValue('show_user_social_security_number_suffix'),
            $this->getValue('show_user_social_security_number'),
            $this->getValue('show_user_relationship_text')
        );

        $printer->setShowUserContactFields(
            $this->getValue('show_user_address'),
            $this->getValue('show_user_phone_numbers'),
            $this->getValue('show_user_email_addresses')
        );

        $printer->setShowShowRelatedUserFields(
            $this->getValue('show_user_related_user_first_name'),
            $this->getValue('show_user_related_user_last_name')
        );
    }
}