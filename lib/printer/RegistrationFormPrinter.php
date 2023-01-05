<?php

interface RegistrationFormPrinter
{
    public function printForm(BaseForm $form, $url, User $user);
}