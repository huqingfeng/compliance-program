<?php

class UserInviteForm extends BaseForm
{
    public function configure()
    {
        $query = $this->getOption('query');

        $this->setWidget('user_id', new sfWidgetFormInputText(array(), array('id' => 'user_id', 'type' => 'hidden')));

        $this->setValidator('user_id', new sfValidatorCallback(array('callback' => function($validator, $value) use ($query) {
                    $users = $query->andWhere('u.id = ?', array($value))->execute();

                    if(!count($users)) {
                        throw new sfValidatorError($validator, 'invalid');
                    }

                    return $value;
                })));
    }
}