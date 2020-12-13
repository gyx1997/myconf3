<?php


namespace myConf\Methods\Account;

use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors as E;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods for user account login.
 *
 * @package Methods\Account
 */
class Login extends \myConf\BaseMethod
{
    /**
     * Login constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * requestUrl : /account/login/?do=submit
     */
    public static function submit() {
        /* Initialize input variables. */
        $loginEntry = Arguments::getHttpArg('login_entry', true);
        $loginPassword = Arguments::getHttpArg('login_password', true);
        $captchaStr = Arguments::getHttpArg('login_captcha', true);
        /* Check the form's correctness. */
        if (is_null($loginEntry) || is_null($loginPassword) || is_null($captchaStr))
        {
            self::retError(400, -1, 'REQUEST_FORM_INVALID', 'Necessary form field(s) missing.');
            return;
        }
        /* Do login. */
        Services::Accounts()->login($loginEntry, $loginPassword, $captchaStr);
        /* Error handler. */
        $errNo = E::getLastError();
        $errStr = isset (E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        self::retArray(200, $errNo, $errStr);
        return;
    }

    /**
     * requestUrl : /account/login/
     */
    public static function showLoginPage() {
        $urlRedirect = self::getGlobal('url_redirect');
        self::retSuccess(array('redirect' => base64_encode($urlRedirect)));
    }
}