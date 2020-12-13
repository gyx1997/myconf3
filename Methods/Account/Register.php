<?php


namespace myConf\Methods\Account;

use myConf\Errors as E;
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Utils\Arguments;
use myConf\Services;

/**
 * Methods of register.
 *
 * @package myConf\Methods\Account
 */
class Register extends \myConf\BaseMethod
{
    /**
     * Register constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * requestUrl : /account/register/?do=checkEmail
     */
    public static function getVerificationEmail() {
        /* Get input data from http request. */
        $base64EncodedEmail = Arguments::getHttpArg('email');
        /* Validate input data. */
        if (is_null($base64EncodedEmail))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        /* Decode email string. */
        $email = base64_decode($base64EncodedEmail);
        /* Send verify email for registering an account. */
        Services::Accounts()->sendVerifyEmail($email, 'reg-verify', 'Email Verification');
        // Return data.
        self::return(array('email' => $email));
    }

    /**
     * requestUrl : /account/register/?do=submit
     */
    public static function submitRegister() {
        /* Get global variable(s). */
        $redirect = self::getGlobal('url_redirect');
        /* Get input data from http request. */
        $captchaStr = Arguments::getHttpArg('register_captcha', true);
        $hashKey = Arguments::getHttpArg('register_verification_key', true);
        $email = Arguments::getHttpArg('register_email', true);
        $password = Arguments::getHttpArg('register_password', true);
        /* Validate input data. */
        if (is_null($captchaStr) || is_null($hashKey) || is_null($email) || is_null($password))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        /* Trim $hashKey to make comparison easily. */
        $hashKey = trim($hashKey);
        /* Generate user name from email. */
        $emailPrefix = substr(explode('@', $email)[0], 0, 17);
        $usernameGenerated = $emailPrefix . '-' . substr(md5($email . $password . strval(time())), 0, 32 - strlen
                ($emailPrefix) - 1);
        Services::Accounts()
                ->new($email,
                      $usernameGenerated,
                      $password,
                      $captchaStr,
                      $hashKey);
        // Return data.
        self::return(array('redirect' => $redirect));
    }

    /**
     * requestUrl : /account/register/
     */
    public static function showRegisterPage() {
        $urlRedirect = self::getGlobal('url_redirect');
        self::retSuccess(array('redirect' => base64_encode($urlRedirect)));
    }
}