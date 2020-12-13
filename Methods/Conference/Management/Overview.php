<?php


namespace myConf\Methods\Conference\Management;

use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors as Err;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods for general management of conferences.
 *
 * @package myConf\Methods\Conferences\Management
 */
class Overview extends \myConf\BaseMethod
{
    /**
     * Overview constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/overview/?do=submit
     */
    public static function updateConference() {
        // Get conference identifier.
        $conferenceId = Arguments::getFuncArg('conference_id');
        // Get input data.
        $conferenceName = Arguments::getHttpArg('conference_name_text', true);
        $conferenceHost = Arguments::getHttpArg('conference_host_text', true);
        $conferenceDateStr = Arguments::getHttpArg('conference_date_text', true);
        $conferencePaperSubmitEndStr = Arguments::getHttpArg('conference_paper_submit_end', true);
        // Validate the form.
        if (is_null($conferenceName) || is_null($conferenceHost) || is_null($conferenceDateStr) || is_null
            ($conferencePaperSubmitEndStr))
        {
            self::retArray(400, -1, 'REQUEST_FORM_INVALID', array('description' => 'Necessary form field(s) missing.'));
            return;
        }
        // String preprocessing.
        $conferenceName = trim($conferenceName);
        $conferenceHost = trim($conferenceHost);
        $conferenceDateStr = trim($conferenceDateStr);
        $conferencePaperSubmitEndStr = trim($conferencePaperSubmitEndStr);
        /**
         * Inline function to convert a date string which format is 'yyyy-mm-dd' to timestamp(int).
         * @param string $dateString Date string to be converted.
         * @return int|false
         */
        function convertDateStringToTimestamp(string $dateString) {
            // Convert date string to temporary array of length 3.
            $date_ymd = explode('-', $dateString);
            if (count($date_ymd) !== 3) {
                // If the array does not have 3 elements,
                // which means data string is invalid, return false.
                return false;
            }
            // Call mktime() to get the timestamp.
            return mktime(0,
                          0,
                          0,
                          $date_ymd[1],
                          $date_ymd[2],
                          $date_ymd[0]);
        }
        // Date string conversion and validation.
        $conferenceDate = convertDateStringToTimestamp($conferenceDateStr);
        if ($conferenceDate === false)
        {
            self::retArray(200,
                           -1,
                           'CONFERENCE_DATE_INVALID');
            return;
        }
        $conferencePaperSubmitEndDate = convertDateStringToTimestamp($conferencePaperSubmitEndStr);
        if ($conferencePaperSubmitEndDate === false)
        {
            self::retArray(200,
                           -1,
                           'CONFERENCE_SUBMIT_END_DATE_INVALID');
            return;
        }
        // Update conference data.
        Services::conferences()->updateConference($conferenceId, $conferenceName, $conferenceHost,
                                                  $conferenceDate, true, $conferencePaperSubmitEndDate, 'banner_image');
        // Return data.
        self::return();
    }
}