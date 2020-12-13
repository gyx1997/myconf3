<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

/**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 16:52
     */

    namespace myConf\Controllers;

    use myConf\Errors\Services\Services as E_SERVICE;
    use myConf\Errors;
    use myConf\Exceptions\HttpStatusException;
    use myConf\Services;
    use myConf\Utils\Arguments;
    use myConf\Utils\Session;
    use myConf\Utils\Env;
    use myConf\Utils\Logger;

    /* Imports of methods. */
    use myConf\Methods\Account\Login as methodLogin;
    use myConf\Methods\Account\Logout as methodLogout;
    use myConf\Methods\Account\ResetPassword as methodResetPwd;
    use myConf\Methods\Account\Register as methodRegister;
    use myConf\Methods\Account\MySettings as methodSettings;
    /* Imports of authenticators. */
    use myConf\Authenticators\Account\Login as authLogin;
    use myConf\Authenticators\Account\Logout as authLogout;
    use myConf\Authenticators\Account\Register as authRegister;
    use myConf\Authenticators\Account\Settings as authSettings;

    /**
     * Class Account
     *
     * @package myConf\Controllers
     */
    class Account extends \myConf\BaseController {

        /**
         * Account constructor.
         *
         * @throws \Exception
         */
        public function __construct() {
            parent::__construct();
            self::setGlobal('redirect_url', $this->urlRedirect);
        }

        /**
         * @requestUrl /account/login/
         */
        public function login()
        {
            /* Login method has no action parameter, so give 'default' to it. */
            if (strlen($this->actionName) > 0)
            {
                $this->actionName = 'Default';
            }
            /* Parse Url. */
            switch ($this->do) {
                case 'submit':
                    {
                        /* Authentication. */
                        authLogin::authLogin();

                        if (authLogin::authSuccess() === true)
                        {
                            /* If authentication succeeds, call submit method. */
                            methodLogin::submit();
                        }
                        break;
                    }
                default:
                    {
                        /* Authentication. */
                        authLogin::authLogin();
                        if (authLogin::authSuccess() === true)
                        {
                            methodLogin::showLoginPage();
                        }
                        else
                        {
                            /** @noinspection PhpUnhandledExceptionInspection */
                            $this->selfRedirect();
                        }
                        break;
                    }
            }
            return;
        }

        /**
         * @requestUrl /account/logout/
         */
        public function logout() {
            authLogout::authLogout();
            if (authLogout::authSuccess() === true)
            {
                methodLogout::logout();
            }
            /* Anyway, it will redirect to the previous page. */
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->selfRedirect();
        }

        /**
         * @requestUrl /account/reset-password/
         * @noinspection PhpUnused
         */
        public function reset_password() {
            switch ($this->do) {
                case 'verifyKey':
                    {
                        methodResetPwd::sendVerifyKey();
                        break;
                    }
                case 'submitNewPwd':
                    {
                        methodResetPwd::setNewPassword();
                        break;
                    }
                default:
                    {
                        throw new HttpStatusException(400, 'WRONG_DO_FLAG', 'Wrong request parameters (do) given.');
                    }
            }
            return;
        }

        /**
         * @requestUrl /account/register/
         */
        public function register() {
            /* First check whether the current user has logged in. */
            authRegister::authRegister();
            if (authRegister::authSuccess() === false)
            {
                /* If he/she has logged in, he/she cannot register a new account.
                   All requests with method register should not be done.
                   Just redirect to his/her personal page.
                   Note that authSuccess() returns true if last authentication succeeds,
                   which means the user has not logged in.
                   In this branch, we need to redirect if the user has logged in. */
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->RedirectTo('/account/');
                return;
            }
            /* Parse request parameter. */
            switch ($this->do) {
                case 'checkEmail':
                    {
                        methodRegister::getVerificationEmail();
                        break;
                    }
                case 'submit':
                    {
                        methodRegister::submitRegister();
                        break;
                    }
                default:
                    {
                        methodRegister::showRegisterPage();
                        break;
                    }
            }
            return;
        }

        /**
         * @throws \myConf\Exceptions\SendRedirectInstructionException
         */
        public function index() : void {
            $this->RedirectTo('/account/my-settings/');
        }

        /**
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DirectoryException
         * @throws \myConf\Exceptions\FileUploadException
         * @throws \myConf\Exceptions\SendRedirectInstructionException
         * @throws \myConf\Exceptions\UserNotExistsException
         */
        public function my_settings() {
            authSettings::authMySettings();
            if (authSettings::authSuccess() === false) {
                /* The user has not logged in. Redirect to login page. */
                $this->RedirectTo('/account/login/');
            }
            if ($this->do == 'submit') {
                switch ($this->actionName) {
                    case 'general':
                        {
                            break;
                        }
                    case 'avatar':
                        {
                            try {
                                $this->Services->Account->changeAvatar($this->userId, 'avatar_image');
                            } catch (\myConf\Exceptions\AvatarNotSelectedException $e) {
                                //TODO 返回未选择头像
                            }
                            break;
                        }
                    case 'scholar':
                        {
                            methodSettings::updateScholarData();
                        }
                }
                $this->RedirectTo('/account/my-settings/?ret=ok');
                return;
            } else {
                methodSettings::showSettings();
            }
        }

        public function my_conferences() {
            $this->_login_redirect();
        }

        public function my_messages() {
            switch ($this->do) {
                case '':
                    {
                        //$this->_render('account/messages', 'My Account', array());
                        break;
                    }
                case 'submit':
                    {
                        break;
                    }
            }
        }
    }