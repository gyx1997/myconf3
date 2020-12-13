<?php


namespace myConf\Methods\Conference\PaperSubmit;


use myConf\BaseMethod;
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Services;
use myConf\Utils\Arguments;

class PaperSubmit extends BaseMethod
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Load paper data (including suggested sessions, or topics) from service layer.
     *
     */
    protected static function loadDataForPaperForm()
    {
        // Get and validate the paper's identifier.
        $id = Arguments::getHttpArg('id');
        $version = Arguments::getHttpArg('ver');
        if (is_null($id) || is_null($version))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return false;
        }
        $id = intval($id);
        $version = intval($version);
        // Get paper's data for presentation.
        $paper = Services::conferences()
                         ->paper()
                         ->get($id, $version);
        // Check the paper's existence.
        if (empty($paper))
        {
            self::retError(404, -1, 'PAPER_NOT_FOUND', 'The paper you are requested is not found.');
            return false;
        }
        // Check whether the current user is not the paper's author.
        $currentUserId = self::getGlobal('user_id');
        if (intval($paper['user_id']) !== $currentUserId)
        {
            self::retError(403, -1, 'NOT_PAPER_AUTHOR', 'Cannot edit or submit a revision of this paper because you are not the author.');
            return false;
        }
        // Get conference's suggested sessions (aka. topics).
        $conferenceId = self::getGlobal('conference_id');
        $sessions = Services::conferences()
                            ->session()
                            ->getAll($conferenceId);
        return array('paper' => $paper, 'sessions' => $sessions);
    }

    /**
     * Submit paper form including save and submit.
     *
     * @param string $action Including 'update', 'submit', 'revision' and 'save'.
     * @param bool   $isDraft
     *
     */
    protected static function submitPaperForm(string $action,
                                       bool $isDraft = false)
    {
        // Get operation from global variable list.
        $userId = self::getGlobal('user_id');
        $conferenceId = self::getGlobal('conference_id');
        $conferenceData = self::getGlobal('conference_data');
        // Initialize the paper's identifier as 0-0 which is actually invalid.
        $id = $version = 0;
        // When updating a paper or submitting a revision of a paper, get paper's identifier and do validation.
        if ($action === 'update' || $action === 'revision')
        {
            $id = Arguments::getHttpArg('id');
            $version = Arguments::getHttpArg('ver');
            if (is_null($id) || is_null($version))
            {
                self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
                return;
            }
            $id = intval($id);
            $version = intval($version);
        }
        // Get paper data and author data from POST form and
        // do validation on the paper's field in $_POST data.
        $paper = self::getPaperForm();
        $authors = self::getAuthors();
        if (is_null($paper) || is_null($authors))
        {
            self::retError(400, -1, 'REQUEST_FORM_INVALID', 'Necessary field(s) in Form missing.');
            return;
        }
        // Final submission of a paper must include authors.
        // However, authors are not necessary for drafts.
        if ($isDraft === false)
        {
            if (empty($authors))
            {
                self::retError(200,
                               -1,
                               'AUTHOR_EMPTY',
                               'Paper\'s author list is empty.');
            }
        }
        // Check action type.
        if ($action === 'revision')
        {
            // Revision can be submitted before the conference starts.
            if (time() >= $conferenceData['conference_start_time'] + 24 * 3600)
            {
                self::retError(200, -1, 'OUT_OF_DATE', 'Cannot submit after deadline.');
                return;
            }
            // Submit a revision of the given paper.
            /** @noinspection PhpUnhandledExceptionInspection */
            Services::conferences()
                    ->paper()
                    ->newRevision($id,
                                  $version,
                                  $userId,
                                  $conferenceId,
                                  $paper['paper_abstract'],
                                  $paper['paper_title'],
                                  $authors,
                                  $paper['paper_content_aid'],
                                  $paper['paper_copyright_aid'],
                                  $paper['paper_type'],
                                  $paper['paper_suggested_session'],
                                  $paper['paper_suggested_session_custom'],
                                  $isDraft);
        }
        else
        {
            // Submitting a new paper or updating the draft is not allowed after deadline.
            if (time() >= $conferenceData['conference_paper_submit_end'] + 24 * 3600)
            {
                self::retError(200, -1, 'OUT_OF_DATE', 'Cannot submit after deadline.');
                return;
            }
            if ($action === 'update')
            {
                // Update the paper (draft).
                Services::conferences()
                        ->paper()
                        ->update($id,
                                 $version,
                                 $paper['paper_content_aid'],
                                 $paper['paper_copyright_aid'],
                                 $authors,
                                 $paper['paper_type'],
                                 $paper['paper_title'],
                                 $paper['paper_abstract'],
                                 $paper['paper_suggested_session'],
                                 $paper['paper_suggested_session_custom'],
                                 $isDraft);
            }
            else if ($action === 'new')
            {
                // Make a new paper. If $isDraft is true, make a new draft. Otherwise, make a new submission directly.
                Services::conferences()
                        ->paper()
                        ->new($userId,
                              $conferenceId,
                              $paper['paper_title'],
                              $paper['paper_abstract'],
                              $authors,
                              $paper['paper_content_aid'],
                              $paper['paper_copyright_aid'],
                              $paper['paper_type'],
                              $paper['paper_suggested_session'],
                              $paper['paper_suggested_session_custom'],
                              $isDraft
                        );
            }
            else
            {
                trigger_error('Unknown parameter $action in \\myConf\\Methods\\Conferences\\PaperSubmit\\PaperSubmit::submitPaperForm',
                              E_USER_WARNING
                );
            }
        }
        // Error handler.
        self::return();
    }
    
    
    /**
     * @return array|bool If the form is valid, returns the array-represented Paper Form. Otherwise, if the form lack
     * fields, which means the form is invalid, returns false.
     */
    private static function getPaperForm()
    {
        $paper = array();
        /* Paper content and copyright. */
        $paperContentAid = Arguments::getHttpArg('paper_pdf_aid', true);
        $paperCopyrightAid = Arguments::getHttpArg('paper_copyright_aid', true);
        $paperTitle = Arguments::getHttpArg('paper_title_text', true);
        $paperAbstract = Arguments::getHttpArg('paper_abstract_text', true);
        $paperType = Arguments::getHttpArg('paper_type_text', true);
        $paperSession = Arguments::getHttpArg('paper_suggested_session', true);
        $paperSessionCustom = Arguments::getHttpArg('paper_suggested_session', true);
        if (is_null($paperContentAid) || is_null($paperCopyrightAid) || is_null($paperTitle) || is_null($paperAbstract))
        {
            return false;
        }
        $paper['paper_content_aid'] = intval($paperContentAid);
        $paper['paper_copyright_aid'] = intval($paperCopyrightAid);
        $paper['paper_title'] = trim($paperTitle);
        $paper['paper_abstract'] = trim($paperAbstract);
        $paper['paper_type'] = trim($paperType);
        $paper['paper_suggested_session'] = trim($paperSession);
        $paper['paper_suggested_session_custom'] = trim($paperSessionCustom);
        return $paper;
    }
    
    /**
     * Get authors of a paper from paper submission request.
     *
     * @return null|mixed
     */
    private static function getAuthors()
    {
        $authorsRaw = Arguments::getHttpArg('authors', true);
        if (is_null($authorsRaw))
        {
            return null;
        }
        $authors = json_decode($authorsRaw, true);
        if (empty($authors))
        {
            return array();
        }
        foreach ($authors as &$author)
        {
            /* Field chn_full_name has not been used yet, while it has been in database schema. */
            !isset($author['chn_full_name']) && $author['chn_full_name'] = '';
        }
        return $authors;
    }
}