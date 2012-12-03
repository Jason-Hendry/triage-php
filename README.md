triage-php
==========

Triage client for PHP

    include vendor/autoload.php

    function myError($errno, $errstr, $errfile, $errline, $errcontext) {
        call_user_func_array('JasonHendry\TriagePHP\TriageClient::error', array('localhost:5001','myproject',$errno, $errstr, $errfile, $errline, $errcontext));
    }
