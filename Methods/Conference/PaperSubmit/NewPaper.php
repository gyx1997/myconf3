<?php


namespace myConf\Methods\Conference\PaperSubmit;


use myConf\Services;
use myConf\Utils\Arguments;

class NewPaper extends PaperSubmit
{
    /**
     * @requestUrl /conference/{confUrl}/paper-submit/new/
     */
    public static function showNewPage()
    {
        $confId = self::getGlobal('conference_id');
        $sessionData = Services::conferences()->session()->getAll($confId);
        self::return(array('sessions' => $sessionData));
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-submit/new/?do=submit
     * @requestUrl /conference/{confUrl}/paper-submit/new/?do=save
     */
    public static function submit() {
        $isDraft = self::getGlobal('do') === 'save';
        self::submitPaperForm('new', $isDraft);
    }
}