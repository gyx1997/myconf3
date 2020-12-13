<?php
    
    
    namespace Services\Administration;

    use myConf\Errors as ERR;
    
    use myConf\Errors\Services\Services as ERR_CONSTANTS_SERVICES;
    use myConf\Utils\Misc;

    /**
     * Class Account
     *
     * @package Services\Administration
     */
    class Account extends \myConf\BaseService
    {
        /**
         * Account constructor.
         */
        public function __construct()
        {
            parent::__construct();
        }
    
        /**
         * @param $userEmail
         *
         * @return bool
         */
        public function newAccount($userEmail)
        {
            // Check whether the user has been registered.
            if ($this->models()
                     ->users()
                     ->exist_by_email($userEmail) === true)
            {
                ERR::setError(ERR_CONSTANTS_SERVICES::E_USER_ALREADY_REGISTERED,
                              200,
                              ERR_CONSTANTS_SERVICES::errorMessage[ERR_CONSTANTS_SERVICES::E_USER_ALREADY_REGISTERED],
                              'The specified user already exists.');
                return false;
            }
            // Generate random password.
            $password = $this->generateRandomPassword();
            $userName = Misc::generateUsername($userEmail, $password['raw']);
            $user_id = $this->models()->users()->create_new($userName, $password['md5'], $userEmail);
            /**
             * TODO refactor the callback module below which makes the real reviewers have the review permission to
             * model layer and fix using SQL like SELECT DISTINCT.
             */
            $tasks = $this->models()
                          ->reviewers()
                          ->getTasksForReviewer($userEmail, 0);
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
            return true;
        }
    
        /**
         * Reset a user's password in administration panel.
         * @param $userEmail
         *
         * @return array|bool
         */
        public function resetAccountPassword($userEmail)
        {
            if ($this->models()
                     ->users()
                     ->exist_by_email($userEmail) === false)
            {
                ERR::setError(ERR_CONSTANTS_SERVICES::E_USER_NOT_EXISTS,
                              200,
                              ERR_CONSTANTS_SERVICES::errorMessage[ERR_CONSTANTS_SERVICES::E_USER_NOT_EXISTS],
                              'The specified user does not exist.');
                return false;
            }
            $password = $this->generateRandomPassword();
            $this->models()
                 ->users()
                 ->setPassword($userEmail,
                               $password['md5']);
            return array('new_password' => $password['raw']);
        }

        /**
         * @return array
         */
        private function generateRandomPassword()
        {
            $rawPassword = mt_rand(10000000, 99999999);
            $md5Password = md5($rawPassword);
            return array('raw' => $rawPassword, 'md5' => $md5Password);
        }
    }