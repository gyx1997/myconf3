<?php


namespace myConf\Methods\Conference\Management;

use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Class Members
 *
 * @package myConf\Methods\Conferences\Management
 */
class Members extends \myConf\BaseMethod
{
    /**
     * Members constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/participant/?do=getAll
     */
    public static function getAllMembers()
    {
        /* Get and preprocess the input. */
        $emailRestriction = Arguments::getHttpArg('email');
        $emailRestriction = is_null($emailRestriction) ? '' : trim($emailRestriction);
        $roleRestriction = array();
        $allRoles = array('scholar', 'reviewer', 'editor', 'admin', 'creator');
        foreach ($allRoles as $role)
        {
            if (Arguments::getHttpArg($role) === 'yes')
            {
                /* If a role's restriction is set, add it to the restriction array. */
                $roleRestriction []= $role;
            }
        }
        $conferenceId = self::getGlobal('conference_id');
        /* Get all members. */
        $members = Services::conferences()->member()->getAll($conferenceId, $roleRestriction, $emailRestriction);
        /* Return data. */
        self::return(array('data' => $members, 'count' => count($members)));
    }

    /**
     * @requestUrl /conference/{confUrl}/management/participant/?do=toggleRole
     */
    public static function toggleRole()
    {
        /* Get input and check. */
        $userId = Arguments::getHttpArg('uid');
        $role = Arguments::getHttpArg('role');
        if (is_null($userId) || is_null($role))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        $conferenceId = self::getGlobal('conference_id');
        /* Toggle the target user's role. */
        if (Services::conferences()->member()->isRole($conferenceId, $userId, $role)) {
            /* If the target user is the given role, remove it. */
            Services::conferences()->member()->removeRole($conferenceId, $userId, $role);
        } else {
            /* Otherwise, add the role to the target user. */
            Services::conferences()->member()->addRole($conferenceId, $userId, $role);
        }
        /* Return success. */
        self::retSuccess();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/participant/?do=remove
     */
    public static function removeMember()
    {
        /* Get input and do validation . */
        $userId = Arguments::getHttpArg('uid');
        if (is_null($userId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        $conferenceId = self::getGlobal('conference_id');
        /* Remove the given user. */
        Services::conferences()->member()->remove($conferenceId, $userId);
        /* Return success. */
        self::retSuccess();
    }
}