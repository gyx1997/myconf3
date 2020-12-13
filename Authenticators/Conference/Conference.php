<?php


namespace myConf\Authenticators\Conference;


use myConf\Errors as Err;
use myConf\Errors\Authentications as E_AUTH;
use myConf\Exceptions\HttpStatusException;
use myConf\Models;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Class Conferences
 *
 * @package myConf\Authenticators\Conferences
 */
class Conference extends \myConf\BaseAuthenticator
{
    public static function authenticate()
    {
        // Privileges definition for all actors with different role.
        // Privilege table is defined as an array. It includes all roles
        // defined in the conference use case. $privilegeTable[$role] can
        // be used to get a list of methods the user with $role can access to.
        // This list ($privilegeTable[$role]) is also defined as an array,
        // which has keys 'method', 'actions' and 'special_check'.
        // $privilegeTable[$role]['method'] is a string, represents the method
        // name.
        // $privilegeTable[$role]['action'] is either an array of string or
        // boolean literal true.  If it is set to true, all actions belong to
        // the method are  available for this role.  Otherwise, if it is set
        // as an array, it equals to the whitelist, which means all actions
        // in this array can be accessed to by the user with current role.
        // $privilegeTable[$role]['special_check'] is an anonymous function
        // which will do extra check based on certain resource like papers.
        $privilegeTable = array(
            // Privileges for guests, aka the users who are logged in.
            'guest' => array(
                array(
                    'method' => 'index',
                    'actions' => array('default'),
                ),
            ),
            // Privileges for normal users.
            'user' => array(
                array(
                    'method' => 'paper-submit',
                    'actions' => array('default'),
                ),
                array(
                    'method' => 'member',
                    'actions' => true,
                ),
            ),
            // Privileges for scholars, aka users registered in the current
            // conference.
            'scholar' => array(
                array(
                    'method' => 'paper-submit',
                    'actions' => array(
                        'new',
                        'author'
                    ),
                ),
                // The scholar cant edit or delete the paper which does not
                // belong to him/her, so special check function is set here.
                array(
                    'method' => 'paper-submit',
                    'actions' => array(
                        'edit',
                        'delete',
                        'preview',
                        'revision',
                    ),
                    'special_check' => function() : bool
                    {
                        $id = intval(Arguments::getHttpArg('id'));
                        $ver = intval(Arguments::getHttpArg('ver'));
                        $userId = self::getGlobal('user_id');
                        $paper = Services::Papers()->get($id, $ver);
                        if (empty($paper))
                        {
                            throw new HttpStatusException(404, 'PAPER_NOT_FOUND', 'The paper you requested was not found.');
                        }
                        return intval($paper['user_id']) === $userId;
                    },
                ),
            ),
            // Privileges for reviewers.
            'reviewer' => array(
                array(
                    'method' => 'paper-review',
                    'actions' => array('show-review'),
                    'special_check' => function() : bool
                    {
                        // Reviewers can only review the paper of his tasks.
                        $paper_id = intval(Arguments::getHttpArg('id'));
                        $paper_ver = intval(Arguments::getHttpArg('ver'));
                        $userId = self::getGlobal('user_id');
                        $userEmail = self::models()
                                         ->users()
                                         ->get_by_id($userId)['user_email'];
                        return Services::conferences()
                                       ->paper()
                                       ->review()
                                       ->reviewer_exists_in_paper($userEmail,
                                                                  $paper_id,
                                                                  $paper_ver);
                    },
                ),
                array(
                    'method' => 'paper-review',
                    'actions' => array(
                        'reviewer-tasks',
                        'default',
                    ),
                ),
            ),
            // Privileges for editors.
            'editor' => array(
                array(
                    'method' => 'paper-submit',
                    'actions' => array('preview'),
                ),
                array(
                    'method' => 'paper-review',
                    'actions' => true,
                ),
            ),
            /* Privileges for administrators. */
            'admin' => array(
                array(
                    'method' => 'paper-submit',
                    'actions' => array('preview'),
                ),
                array(
                    'method' => 'paper-review',
                    'actions' => true,
                ),
                array(
                    'method' => 'management',
                    'actions' => true,
                ),
            ),
        );
        // The table above describes the basic rules of privileges. In fact,
        // user has all the privileges which the guests have. Then add them to
        // the user's privilege table. Similarly, add all user's privileges
        // to the scholar's.
        $privilegeTable['user'] = array_merge($privilegeTable['user'],
                                              $privilegeTable['guest']);
        $privilegeTable['scholar'] = array_merge($privilegeTable['scholar'],
                                                 $privilegeTable['user']);
        // First check the login status.
        $currentUserId = self::getGlobal('user_id');
        $conferenceId = self::getGlobal('conference_id');
        if ($currentUserId > 0) {
            // If logged in, try to get roles.
            if (self::models()
                    ->conference()
                    ->member()
                    ->jointIn($conferenceId,
                              $currentUserId))
            {
                /** @noinspection PhpUnhandledExceptionInspection */
                $roles = self::models()
                             ->conference()
                             ->member()
                             ->getRoles($conferenceId,
                                        $currentUserId);
            }
            else
            {
                // The user has not registered in this conference.
                $roles = ['user'];
            }
        } else {
            // The current user has not logged in.
            $roles = ['guest'];
        }
        // Set auth globals for compatibility.
        self::setGlobal('auth_reviewer',
                        in_array('reviewer', $roles));
        self::setGlobal('auth_editor',
                        in_array('editor', $roles));
        self::setGlobal('auth_creator',
                        in_array('creator', $roles));
        self::setGlobal('auth_admin',
                        in_array('admin', $roles) || self::getGlobal('auth_creator'));
        /* Now check the privileges. */
        if (self::getGlobal('auth_admin')) {
            /* Founder has all the privileges. */
            return;
        }
        /* Get method name and action name from global variables. */
        $currentMethodName = self::getGlobal('method_name');
        $currentActionName = self::getGlobal('action_name');
        /* Check each role of the user. */
        foreach ($roles as $role)
        {
            /* Check all the privileges of current role. */
            foreach ($privilegeTable[$role] as $privilege)
            {
                /* If method and actions all set, which means this privilege
                   definition is valid, check whether the current role has
                   this privilege. */
                if (isset($privilege['method']) && isset($privilege['actions']))
                {
                    /* If special function is set, use it for checking. */
                    $check_func = isset($privilege['special_check']) ? $privilege['special_check'] : function() : bool {
                        return true;
                    };
                    if ($privilege['method'] === $currentMethodName)
                    {
                        /* Method name from http request matches the method name in current iteration. */
                        if ($privilege['actions'] === true)
                        {
                            /* If there are no restrictions of actions, authentication succeed.*/
                            return;
                        }
                        /* Otherwise, check all actions to see whether there is
                           an action matches the request's action and check_func()
                           returns true. */
                        foreach($privilege['actions'] as $action)
                        {
                            if ($action === $currentActionName && $check_func())
                            {
                                return;
                            }
                        }
                    }
                }
            }
        }
        Err::setError(E_AUTH::E_USER_NO_PERMISSION, 403, E_AUTH::errorMessage[E_AUTH::E_USER_NO_PERMISSION], 'The current user do not have the permission to do this action.');
        return;
    }
}