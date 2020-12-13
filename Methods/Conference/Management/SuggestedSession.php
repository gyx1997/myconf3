<?php


namespace myConf\Methods\Conference\Management;

use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods for conference's suggested sessions(topics).
 *
 * @package myConf\Methods\Conferences\Management
 */
class SuggestedSession extends \myConf\BaseMethod
{
    /**
     * SuggestedSession constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/?do=add
     */
    public static function add() {
        // Get input from $_POST form.
        $sessionText = Arguments::getHttpArg('session_text',
                                             true);
        $sessionType = Arguments::getHttpArg('session_type',
                                             true);
        if (is_null($sessionText) || is_null($sessionText))
        {
            self::retError(
                400,
                -1,
                'REQUEST_PARAM_INVALID',
                'Necessary parameter(s) missing.'
            );
        }
        $sessionType = intval($sessionType);
        // Get the current conference id from global variable list.
        $conferenceId = self::getGlobal('conference_id');
        // Add the specified session to the conference.
        Services::conferences()
                ->session()
                ->add($conferenceId,
                      $sessionText,
                      $sessionType);
        self::return();
    }
    
    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/?do=up
     */
    public static function moveUp()
    {
        // Get the input from $_GET request.
        $categoryId = Arguments::getHttpArg('id');
        if (is_null($categoryId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        Services::conferences()
                ->session()
                ->moveUp($categoryId);
        self::return();
    }
    
    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/?do=down
     */
    public static function moveDown()
    {
        // Get the input from $_GET request.
        $categoryId = Arguments::getHttpArg('id');
        if (is_null($categoryId))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        Services::conferences()->session()->moveDown($categoryId);
        self::return();
    }
    
    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/
     */
    public static function getAll()
    {
        // Get the current conference id from global variable list.
        $conferenceId = self::getGlobal('conference_id');
        // Get the dispatched session data.
        $sessions = Services::conferences()
                            ->session()
                            ->getAllUndispatched($conferenceId);
        // Return data.
        self::return(array('sessions' => $sessions));
    }
    
    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/?do=edit
     */
    public static function update()
    {
        $sessionId = Arguments::getHttpArg('session_id', true);
        $sessionText = Arguments::getHttpArg('session_text', true);
        $sessionType = Arguments::getHttpArg('session_type', true);
        if (is_null($sessionId) || is_null($sessionText) || is_null($sessionType))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary field(s) in post form missing.');
            return;
        }
        Services::conferences()
                ->session()
                ->update($sessionId, $sessionText, $sessionType);
        self::return();
    }
    
    /**
     * @requestUrl /conference/{confUrl}/management/suggested-session/?do=delete
     */
    public static function delete()
    {
        $sessionId = Arguments::getHttpArg('id');
        if (is_null($sessionId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        Services::conferences()
                ->session()
                ->delete($sessionId);
        self::return();
    }
}