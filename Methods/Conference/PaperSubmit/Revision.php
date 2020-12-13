<?php /** @noinspection DuplicatedCode */

namespace myConf\Methods\Conference\PaperSubmit;


use myConf\BaseMethod;
use myConf\Models\Constants\PaperStatus;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods of submitting revision of a given paper.
 *
 * @package myConf\Methods\Conferences\PaperSubmit
 */
class Revision extends PaperSubmit
{
    /**
     * Revision constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-submit/revision/
     *
     */
    public static function showRevision()
    {
        // Load old paper data.
        $paperData = self::loadDataForPaperForm();
        if ($paperData === false)
        {
            return;
        }
        // Check whether the paper's status is Revision.
        if (intval($paperData['paper']['paper_status']) !== PaperStatus::Revision)
        {
            self::retError(403,
                           -1,
                           'PAPER_STATUS_INVALID',
                           'Cannot submit a revision of this paper because of its invalid status.'
            );
            return;
        }
        self::return($paperData);
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-submit/revision/?do=submit
     *             /conference/{confUrl}/paper-submit/revision/?do=save
     *
     */
    public static function submitRevision()
    {
        $isDraft = self::getGlobal('do') === 'save';
        self::submitPaperForm('revision', $isDraft);
    }
}