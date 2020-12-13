<?php


namespace myConf\Methods\Account;

use \myConf\Services;
use myConf\Utils\Arguments;
use myConf\Errors as E;
use myConf\Errors\Services\Services as E_SERVICE;

class ResetPassword extends \myConf\BaseMethod
{
    /**
     * requestUrl : /account/reset-password/?do=verifyKey
     */
    public static function sendVerifyKey()
    {
        /* Get input data from http request. */
        $emailEncoded = Arguments::getHttpArg('email');
        /* Validate input data. */
        if (is_null($emailEncoded)) {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        $email = base64_decode($emailEncoded);
        Services::Accounts()->sendVerifyEmail($email, 'reset-pwd', 'myConf Reset Password');
        /* Error handler. */
        $errHttpCode = E::getLastErrorHttpCode();
        $errNo = E::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        self::return(array('email' => $email));
    }

    public static function setNewPassword()
    {
        /* Get input data from http request. */
        $userEmail = Arguments::getHttpArg('user_email', true);
        $userNewPassword = Arguments::getHttpArg('user_password', true);
        $hashKey = Arguments::getHttpArg('verification_key', true);
        $captchaStr = Arguments::getHttpArg('reset_pwd_captcha', true);
        /* Validate input data. */
        if (is_null($userEmail) || is_null($userNewPassword) || is_null($hashKey) || is_null($captchaStr))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) in form missing.');
            return;
        }
        $hashKey = trim($hashKey);
        Services::Accounts()->resetPasswordWithVerificationKey($userEmail, $userNewPassword, $hashKey, $captchaStr);
        /* Error handler. */
        self::return();
        //$errHttpCode = E::getLastErrorHttpCode();
        //$errNo = E::getLastError();
        //$errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        //self::retArray($errHttpCode, $errNo, $errStr);
    }
}