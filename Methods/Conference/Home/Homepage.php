<?php


namespace myConf\Methods\Conference\Home;


use myConf\Services;
use myConf\Utils\Arguments;

class Homepage extends \myConf\BaseMethod
{
    public static function loadPage()
    {
        /* Initialize parameters. cid/did are not necessary parameters. */
        $categoryId = Arguments::getHttpArg('cid');
        $categoryId = is_null($categoryId) ? 0 : intval($categoryId);
        $documentId = Arguments::getHttpArg('did');
        $documentId = is_null($documentId) ? 0 : intval($documentId);
        $conferenceId = self::getGlobal('conference_id');
        $data = Services::conferences()->category()->show($conferenceId, $categoryId);
        self::return($data);
    }
}