<?php


namespace myConf\Methods\Conference\PaperSubmit;

use myConf\Services;
use \myConf\Utils\Arguments;

/**
 * Methods of showing current user's paper list in given conference.
 * Also equals to Overview page.
 *
 * @package myConf\Methods\Conferences\PaperSubmit
 */
class Papers extends \myConf\BaseMethod
{
    /**
     * Papers constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * requestUrl : /conference/{confUrl}/paper-submit/
     */
    public static function showList()
    {
        /* Get input parameters. */
        $userId = Arguments::getFuncArg('user_id');
        $conferenceId = Arguments::getFuncArg('conference_id');
        /* Check whether the current user has joint in the conference. */
        $jointInConference = Services::conferences()
                                     ->userJointIn($userId, $conferenceId);
        if ($jointInConference === true)
        {
            /* If the current user has joint in the conference, get all his papers. */
            $papers = Services::Papers()
                              ->getUserPapers($userId, $conferenceId);
        }
        return self::return(array(
            'has_joint' => $jointInConference,
            'papers'    => isset($papers) ? $papers : array(),
        ));
    }
}