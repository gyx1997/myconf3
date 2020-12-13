<?php
    
    
    namespace myConf\Methods\Admin;

    use myConf\Utils\Arguments;

    /**
     * Class User
     *
     * @package myConf\Methods\Admin
     */
    class User extends \myConf\BaseMethod
    {
        /**
         * @requestUrl /admin/users/list
         */
        public static function listUsers()
        {
            $emailRestriction = Arguments::getHttpArg('email');
            if (is_null($emailRestriction))
            {
                $emailRestriction = '';
            }
            else
            {
                $emailRestriction = trim($emailRestriction);
            }
        }
    
        /**
         * @requestUrl /admin/users/register
         */
        public static function register()
        {
        
        }
    
        /**
         * @requestUrl /admin/users/resetPassword
         */
        public static function resetPassword()
        {
        
        }
    }