<?php

class ComplianceStatusMapping
{
    public function __construct($text, $light)
    {
        $this->text = $text;
        $this->light = $light;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getLight()
    {
        return $this->light;
    }

    private $text;
    private $light;
}
