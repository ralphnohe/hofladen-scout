<?php

if (!defined('WPINC')) {
    die;
}

abstract class LoginAsUser_Integration_Abstract
{
    /** @var w357LoginAsUser */
    protected $main;

    /** @var LoginAsUserButton_Generator */
    protected $loginAsUserButtonGenerator;

    public function __construct($main)
    {
        $this->main = $main;
        $this->loginAsUserButtonGenerator = new LoginAsUserButton_Generator($main);
    }

}