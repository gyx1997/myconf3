<?php


namespace myConf\Methods\Conference\PaperSubmit;

use myConf\Models\Constants\PaperStatus;
use myConf\Utils\Arguments;

/**
 * Methods of editing papers.
 *
 * @package myConf\Methods\Conferences\PaperSubmit
 */
class Edit extends PaperSubmit
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * requestUrl : /conference/{confUrl}/paper-submit/edit/
     *            ; /conference/{confUrl}/paper-submit/edit/?do=edit
     */
    public static function showEditPage()
    {
        // Load paper data.
        $paperData = self::loadDataForPaperForm();
        // Check whether the paper is valid.
        if ($paperData === false)
        {
            return;
        }
        // Check whether the paper's status is Saved.
        if (intval($paperData['paper']['paper_status']) !== PaperStatus::Saved)
        {
            self::retError(403, -1, 'PAPER_STATUS_INVALID', 'Cannot edit paper which status is not saved.');
            return;
        }
        self::return($paperData);
    }

    /**
     * requestUrl : /conference/{confUrl}/paper-submit/edit/?do=save
     *            ; /conference/{confUrl}/paper-submit/edit/?do=submit
     *
     * @return array
     */
    public static function submitPaper()
    {
        // Get parameters $isDraft from controller layer.
        $isDraft = self::getGlobal('do') === 'save';
        // Call PaperSubmit::submitPaperForm to update the paper.
        self::submitPaperForm('update', $isDraft);
    }
}