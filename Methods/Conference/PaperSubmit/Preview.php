<?php


namespace myConf\Methods\Conference\PaperSubmit;


use myConf\BaseMethod;
use myConf\Models\Constants\PaperStatus;
use myConf\Utils\Arguments;

/**
 * Methods of preview a paper.
 *
 * @package myConf\Methods\Conferences\PaperSubmit
 */
class Preview extends PaperSubmit
{
    /**
     * Preview constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-submit/preview
     */
    public static function previewPaper() {
        // Load paper data and return.
        self::return(self::loadDataForPaperForm());
    }
}