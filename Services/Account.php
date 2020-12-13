<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 17:05
 */

namespace myConf\Services;

use myConf\BaseService;
use myConf\Exceptions\AvatarNotSelectedException;
use myConf\Exceptions\CacheDriverException;
use myConf\Exceptions\DbException;
use myConf\Exceptions\DbTransactionException;
use myConf\Exceptions\DirectoryException;
use myConf\Exceptions\EmailExistsException;
use myConf\Exceptions\EmailVerifyFailedException;
use myConf\Exceptions\FileUploadException;
use myConf\Exceptions\UsernameExistsException;
use myConf\Exceptions\UserNotExistsException;
use myConf\Services;
use myConf\Utils\Arguments;
use myConf\Utils\Avatar;
use myConf\Utils\Email;
use myConf\Utils\Env;
use \myConf\Utils\Logger;
use myConf\Utils\Session;
/* Import for error processing. */
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors as ERR;

class Account extends BaseService
{
    /**
     * Account constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }



    /**
     * 用户登录逻辑
     *
     * @param string $entry
     * @param string $password
     * @param string $captcha
     *
     * @return array|bool
     */
    public function login(string $entry,
                          string $password,
                          string $captcha)
    {
        // First check whether the captcha is correct.
        if ($this->checkCaptcha($captcha) === false)
        {
            ERR::setError(E_SERVICE::E_MISC_CAPTCHA_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_MISC_CAPTCHA_ERROR],
                          'Wrong captcha.');
            return false;
        }
        // Determine whether the user exists.
        if ($this->Models->User->exist_by_email($entry) === false)
        {
            ERR::setError(E_SERVICE::E_USER_NOT_EXISTS,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_USER_NOT_EXISTS],
                          'The current user does not exist.');
            return false;
        }
        // Check whether the current account is temporary frozen.
        if ($this->checkLoginTimes() === false)
        {
            ERR::setError(E_SERVICE::E_USER_LOGIN_FROZEN,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_USER_LOGIN_FROZEN],
                          'Login frozen due to too many failure attempts.');
            return false;
        }
        // Get the user's detail.
        $user = $this->models()
                     ->users()
                     ->get_by_email($entry);
        // Compare the passwords between submitted and saved.
        if ($user['user_password'] !== md5($password . $user['password_salt']))
        {
            // Increase the login failure counter.
            $this->increaseLoginTimes($entry);
            // Log the login failure as a sensitive operation.
            Logger::log_sensitive_operation(Logger::operation_login, Env::get_ip(), 'User "' . $entry . '" login password error.');
            ERR::setError(E_SERVICE::E_USER_PASSWORD_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_USER_PASSWORD_ERROR],
                          'User password error.');
            return false;
        }
        // Set login status using session.
        Session::set_user_data('user_id', $user['user_id']);
        Session::set_user_data('user_data', $user);
        Session::set_user_data('login_time', time());
        return $user;
    }

    /**
     * Check the correctness of captcha in services of account.
     *
     * @param string $captchaStr The captcha string which is submitted by the user.
     *
     * @return bool Returns true if the input is correct. Otherwise, returns false.
     */
    private function checkCaptcha(string $captchaStr)
    {
        /* Get captcha from session. */
        $captcha = Session::get_temp_data('captcha');
        if (isset($captcha) === false)
        {
            /* If the captcha does not exist, it must be incorrect. */
            return false;
        }
        /* Compare the submitted and the saved captcha. */
        return $captcha === $captchaStr;
    }

    /**
     * Check whether the login failures reach to the limit. (set to 5)
     *
     * @return bool
     */
    private function checkLoginTimes(): bool
    {
        // Get login times from session.
        $times = Session::get_user_data('login_times');
        // If it is unset, which means it is the first time to login.
        if (!isset($times))
        {
            return true;
        }
        // If failures reach to the limit and the time is not expired,
        // return false which means the account is temporary frozen.
        return !($times['expires'] > time() && $times['times'] > 5);
    }

    /**
     * Increase the counter of login failures.
     * @param string $email The target account's email.
     */
    private function increaseLoginTimes(string $email): void
    {
        $times = Session::get_user_data('login_times');
        Session::set_user_data('login_times', isset($times) ? array(
            'expires' => $times['expires'],
            'times'   => $times['times'] + 1,
            'account' => $email,
        ) : array(
            'expires' => time() + 900,
            'times'   => 1,
            'account' => $email,
        ));
    }

    /**
     *
     *
     * @param string $email
     * @param string $username
     * @param string $password
     * @param string $captcha
     * @param string $hashKey
     *
     * @return array|false
     */
    public function new(string $email,
                        string $username,
                        string $password,
                        string $captcha,
                        string $hashKey)
    {
        // Check captcha first.
        if ($this->checkCaptcha($captcha) === false)
        {
            ERR::setError(E_SERVICE::E_MISC_CAPTCHA_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_MISC_CAPTCHA_ERROR],
                          'Captcha error.');
            return false;
        }
        // Get stored hash key from session.
        $hashKeyStored = Session::get_temp_data('reg-verify' . '-hash-key');
        // Compare (check) hash keys.
        if (isset($hashKeyStored) === false || $hashKeyStored !== $hashKey)
        {
            ERR::setError(E_SERVICE::E_MISC_HASH_KEY_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_MISC_HASH_KEY_ERROR],
                          'Verification hash key error.');
            return false;
        }
        // Check whether the email has already been registered.
        if ($this->Models->User->exist_by_email($email))
        {
            ERR::setError(E_SERVICE::E_USER_ALREADY_REGISTERED,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_USER_ALREADY_REGISTERED],
                          'User email exists.');
            return false;
        }
        // Since user name is generated from the email and hash of time(),
        // it should not exist. So checks are ignored here.
        // Create a new account for the registration.
        /** @noinspection PhpUnhandledExceptionInspection */
        $user_id = $this->models()
                        ->users()
                        ->create_new($username, $password, $email);
        // Extra works need to be done here such as adding reviewer.
        $tasks = $this->models()
                      ->reviewers()
                      ->getTasksForReviewer($email,
                                            0);
        if (!empty($tasks))
        {
            // If the user has been added as a reviewer of a conference, change his/her role.
            $task = array_shift($tasks);
            $this->models()
                 ->conference()
                 ->member()
                 ->add($task['conference_id'], $user_id);
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->models()
                 ->conference()
                 ->member()
                 ->setRoles($task['conference_id'], $user_id, array(
                     'scholar',
                     'reviewer',
                 ));
        }
        // Get user data.
        /** @noinspection PhpUnhandledExceptionInspection */
        $userData = $this->models()
                         ->users()
                         ->get_by_id($user_id);
        // Set user login data.
        Session::set_user_data('user_id', $userData['user_id']);
        Session::set_user_data('user_data', $userData);
        Session::set_user_data('login_time', time());
        return $userData;
    }

    /**
     * @param string $email
     * @param string $username
     * @param string $password
     *
     * @throws CacheDriverException
     * @throws DbException
     * @throws DbTransactionException
     */
    public function InitSuperAdministrator(string $email,
                                           string $username,
                                           string $password)
    {
        $user_id = $this->Models->User->create_new($username, $password, $email, 'sadmin');
    }

    /**
     * 发送验证邮件业务逻辑
     *
     * @param string $email
     * @param string $type
     * @param string $title
     */
    public function sendVerifyEmail(string $email,
                                    string $type,
                                    string $title): void
    {
        if ($type === 'reset-pwd')
        {
            // Email must exist when trying to reset password.
            if ($this->Models->User->exist_by_email($email) === false)
            {
                ERR::setError(E_SERVICE::E_USER_NOT_EXISTS,
                              200,
                              E_SERVICE::errorMessage[E_SERVICE::E_USER_NOT_EXISTS],
                              'User does not exist.');
                return;
            }
        }
        else if ($type === 'reg-verify')
        {
            // Email must not exist when register an new account.
            if ($this->Models->User->exist_by_email($email) === true)
            {
                ERR::setError(E_SERVICE::E_USER_ALREADY_REGISTERED,
                              200,
                              E_SERVICE::errorMessage[E_SERVICE::E_USER_ALREADY_REGISTERED],
                              'This email(user) has already been registered.');
                return;
            }
        }
        // Generate the verification hash key and save it in session.
        $hash_key = md5(uniqid());
        Session::set_temp_data($type . '-hash-key', $hash_key, 1800);
        // Send verification email.
        $content = '
            <h1>Email Verification From myConf.cn</h1>
            <p>Copy the text below to enter in the form popped from myConf.cn .</p>
            <p style="font-family: Consolas; font-size: 14px; background-color: #D0D0D0">' . $hash_key . '</p>
            <p>You should finish to submit the form in 30 minutes since you have submit this request.</p>
            <p>If you have not registered an account at myConf.cn, Please ignore this email.</p>
            ';
        Email::send_mail('AccountVerification@mail.myconf.cn', 'myConf Account Verification', $email, $title, $content);
        return;
    }

    /**
     * Reset password with a verification key.
     * @param string $email
     * @param string $newPassword
     * @param string $hashKey
     * @param string $captcha
     */
    public function resetPasswordWithVerificationKey(string $email, string $newPassword, string $hashKey, string
    $captcha)
    {
        // First check whether the captcha is correct.
        if ($this->checkCaptcha($captcha) === false)
        {
            ERR::setError(E_SERVICE::E_MISC_CAPTCHA_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_MISC_CAPTCHA_ERROR],
                          'Wrong captcha.');
            return;
        }
        // Get stored hash key from session.
        $hashKeyStored = Session::get_temp_data('reset-pwd' . '-hash-key');
        // Compare hash keys.
        if (isset($hashKeyStored) && $hashKeyStored !== $hashKey)
        {
            ERR::setError(E_SERVICE::E_MISC_HASH_KEY_ERROR,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_MISC_HASH_KEY_ERROR],
                          'Verification key error.');
            return;
        }
        // Check the correctness of email.
        if ($this->Models->User->exist_by_email($email) === false) {
            ERR::setError(E_SERVICE::E_USER_NOT_EXISTS,
                          200,
                          E_SERVICE::errorMessage[E_SERVICE::E_USER_NOT_EXISTS],
                          'The current user does not exist.');
            return;
        }
        // Set new password.
        $this->models()
             ->users()
             ->setPassword($email, $newPassword);
        return;
    }

    /**
     * 更改密码用户逻辑
     *
     * @param string $email
     * @param string $new_password
     *
     * @throws CacheDriverException
     * @throws UserNotExistsException
     */
    private function resetPassword(string $email,
                                   string $new_password): void
    {
        if (!$this->Models->User->exist_by_email($email))
        {
            throw new UserNotExistsException();
        }
        $user_id = $this->Models->User->get_by_email($email)['user_id'];
        $this->Models->User->set_password($user_id, $new_password);
        return;
    }

    /**
     * 修改头像业务逻辑
     *
     * @param int    $user_id
     * @param string $avatar_field
     *
     * @throws AvatarNotSelectedException
     * @throws CacheDriverException
     * @throws DirectoryException
     * @throws FileUploadException
     */
    public function changeAvatar(int $user_id,
                                 string $avatar_field): void
    {
        try
        {
            /* Parse uploaded file. */
            $new_file = Avatar::ParseAvatar($user_id, $avatar_field);
            /* Update data. */
            $this->Models->User->SetAvatar($user_id, $new_file);
        }
        catch (FileUploadException $e)
        {
            if ($e->getShortMessage() === 'NO_SUCH_FILE')
            {
                ERR::setError(E_SERVICE::E_UPLOAD_FILE_NOT_SELECTED);
                return;
            }
            ERR::setError(E_SERVICE::E_UPLOAD_FILE_UPLOAD_ERROR);
        }
        return;
    }

    /**
     * @param int $user_id
     *
     * @return array|false
     */
    public function getSettings(int $user_id)
    {
        /* Get the user's basic information. */
        $basicData = $this->Models->User->get_by_id($user_id);
        if (empty($basicData))
        {
            Err::setError(E_SERVICE::E_USER_NOT_EXISTS);
            return false;
        }
        /* Get the user's scholar information. */
        $scholarData = $this->Models->Scholar->getByEmail($basicData['user_email']);
        return array(
            'user_id'           => $user_id,
            'user_name'         => $basicData['user_name'],
            'user_email'        => $basicData['user_email'],
            'user_phone'        => $basicData['user_phone'],
            'user_avatar'       => $basicData['user_avatar'],
            'user_scholar_data' => $scholarData,
        );
    }

    /**
     * 更新账户的Scholar信息
     *
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $institution
     * @param string $department
     * @param string $address
     * @param string $prefix
     * @param string $chn_full_name
     */
    public function update_scholar_info(string $email,
                                        string $first_name,
                                        string $last_name,
                                        string $institution,
                                        string $department,
                                        string $address,
                                        string $prefix = '',
                                        string $chn_full_name = ''): void
    {
        $this->Models->Scholar->set_by_email($email, $first_name, $last_name, $institution, $department, $address, $prefix, $chn_full_name);
        return;
    }

    /**
     * 获取用户的账户信息
     *
     * @param int $user_id
     *
     * @return array
     * @throws UserNotExistsException
     */
    public function user_account_info(int $user_id): array
    {
        $user_data = $this->Models->User->get_by_id(strval($user_id));
        if (empty($user_data))
        {
            throw new UserNotExistsException('USER_NOT_EXISTS', 'The user which has the user_id ' . $user_id . ' does not exist.');
        }
        return $user_data;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function email_exists(string $email): bool
    {
        return $this->Models->User->exist_by_email($email);
    }

    /**
     * 清除登录次数的记录
     */
    private function clearLoginTimes(): void
    {
        Session::unset_user_data('login_times');
    }
}