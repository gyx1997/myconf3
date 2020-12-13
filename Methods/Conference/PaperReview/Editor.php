<?php


namespace myConf\Methods\Conference\PaperReview;


use myConf\BaseMethod;
use myConf\Models\Constants\PaperStatus;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods of editors, including arrange reviewers, listing papers, etc.
 *
 * @package myConf\Methods\Conferences\PaperReview
 */
class Editor extends BaseMethod
{
    /**
     * Editor constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    
    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/?do=getDetails
     */
    public static function viewPaper()
    {
        // Get the current conference.
        $conferenceId = self::getGlobal('conference_id');
        // Get the input and do validation.
        $paperLogicId = Arguments::getHttpArg('id');
        $paperVersion = Arguments::getHttpArg('ver');
        if (is_null($paperLogicId) || is_null($paperVersion))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing');
            return;
        }
        // Get the preview data.
        $data = Services::conferences()
                        ->paper()
                        ->review()
                        ->editor()
                        ->preview($paperLogicId, $paperVersion);
        self::return(array('paper' => $data));
    }
    
    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/?do=getReviewers
     */
    public static function getReviewers()
    {
        // Initialize input and do validation.
        $id = Arguments::getHttpArg('id');
        $version = Arguments::getHttpArg('ver');
        if (is_null($id) || is_null($version))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        $id = intval($id);
        $version = intval($version);
        $reviewers = Services::conferences()
                             ->paper()
                             ->review()
                             ->editor()
                             ->getReviewers($id,
                                            $version);
        // Return data
        self::return(
            array(
                'count' => count($reviewers),
                'reviewers' => $reviewers
            )
        );
    }
    
    public static function removeReviewer()
    {
    
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/?do=delete
     */
    public static function deletePaper()
    {
        // Initialize input and do validation.
        $id = Arguments::getHttpArg('id');
        $version = Arguments::getHttpArg('ver');
        if (is_null($id) || is_null($version))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        $id = intval($id);
        $version = intval($version);
        // Delete the specified paper and return.
        Services::conferences()
                ->paper()
                ->delete($id, $version);
        self::return();
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/?do=addReviewer
     *
     */
    public static function addReviewer()
    {
        // Preprocess parameters and do validation.
        $emailEncoded = Arguments::getHttpArg('email');
        $paper_id = Arguments::getHttpArg('id');
        $paper_version = Arguments::getHttpArg('ver');
        if (is_null($emailEncoded) || is_null($paper_id) || is_null($paper_version))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        // Trim the white spaces and validate the format of email.
        $email = base64_decode($emailEncoded);
        $email = trim($email);
        if (strpos($email, '@') === false)
        {
            self::retError(200,
                           -1,
                           'EMAIL_INVALID',
                           'Format of email invalid.');
            return;
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        Services::conferences()
                ->paper()
                ->review()
                ->editor()
                ->addReviewer($paper_id,
                              $paper_version,
                              $email);
        self::return();
    }
    
    public static function showDecisionPage()
    {
        // Input data preparation and validation.
        $paperId = Arguments::getHttpArg('id');
        $paperVer = Arguments::getHttpArg('ver');
        $targetAction = Arguments::getHttpArg('action');
        if (is_null($paperId) || is_null($paperVer) || is_null($targetAction))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        $paperId = intval($paperId);
        $paperVer = intval($paperVer);
        $targetAction = trim($targetAction);
        if ($targetAction !== 'accept' && $targetAction !== 'reject' && $targetAction !== 'revision')
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Parameter(s) invalid.');
            return;
        }
        // Get the preview data.
        $data = Services::conferences()
                        ->paper()
                        ->review()
                        ->editor()
                        ->loadDecisionPage($paperId,
                                           $paperVer);
        // Return data.
        self::return(
            array(
                'paper' => $data,
                'target_action' => $targetAction
            )
        );
    }

    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/finishReview
     */
    public static function finishReview()
    {
        // Editor can only choose one of those status as review result.
        $acceptedStatus = array(
            'reject'   => PaperStatus::Rejected,
            'accept'   => PaperStatus::Accepted,
            'revision' => PaperStatus::Revision,
        );
        // Input data preparation and validation.
        $paperId = Arguments::getHttpArg('id');
        $paperVer = Arguments::getHttpArg('ver');
        $reviewResult = Arguments::getHttpArg('result');
        $comments = Arguments::getHttpArg('comments', true);
        $emailNotification = Arguments::getHttpArg('send_mail', true);
        if (is_null($paperId)
            || is_null($paperVer)
            || is_null($reviewResult)
            || is_null($comments)
            || is_null($emailNotification))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        $paperId = intval($paperId);
        $paperVer = intval($paperVer);
        // Check whether parameter result is valid.
        $reviewResult = trim($reviewResult);
        if (array_key_exists($reviewResult, $acceptedStatus) === false)
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Parameter(s) value invalid.');
            return;
        }
        // Get the review status id.
        $reviewStatusId = $acceptedStatus[$reviewResult];
        // Trim the comments.
        $comments = trim($comments);
        // Trim the email notification field and do comparision.
        $emailNotification = (trim($emailNotification) === 'true');
        // Call service to finish the review.
        Services::conferences()
                ->paper()
                ->review()
                ->editor()
                ->finishReview($paperId,
                               $paperVer,
                               $reviewStatusId,
                               $comments,
                               $emailNotification);
        self::return();
    }
    
    /**
     * @requestUrl /conference/{confUrl}/paper-review/editor-list/view
     */
    public static function viewStatusFromPaperList()
    {
        // Get the current conference id.
        $conferenceId = self::getGlobal('conference_id');
        // Input data initialization. Note that null arguments are
        // allowed here which means default value would be set instead.
        $page = Arguments::getHttpArg('page');
        $page = is_null($page) ? 1 : intval($page);
        $paperStatus = Arguments::getHttpArg('status');
        $paperStatus = is_null($paperStatus) ? -1 : intval($paperStatus);
        $paperSessionId = Arguments::getHttpArg('topicId');
        $paperSessionId = is_null($paperSessionId) ? -1 : $paperSessionId;
        $paperPerPage = Arguments::getHttpArg('ppPage');
        $paperPerPage = is_null($paperPerPage) ? 10 : intval($paperPerPage);
        $paperPerPage = $paperPerPage <= 0 ? 10 : $paperPerPage;
        // Get paper and session data.
        $data = Services::conferences()
                             ->paper()
                             ->review()
                             ->editor()
                             ->getReviewStatus($conferenceId,
                                               $paperStatus,
                                               $paperSessionId,
                                               $page,
                                               $paperPerPage);
        self::return( $data);
    }
    
    public static function viewStatusFromReviewerList()
    {
        // Get conference id.
        $conferenceId = self::getGlobal('conference_id');
        // Get input and do validation.
        $countPerPage = Arguments::getHttpArg('cpPage');
        $countPerPage = is_null($countPerPage) ? 10 : intval($countPerPage);
        $countPerPage = $countPerPage <= 0 ? 10 : $countPerPage;
        $page = Arguments::getHttpArg('page');
        $page = is_null($page) ? 1 : intval($page);
        $page = $page <= 0 ? 1 : $page;
        $email = Arguments::getHttpArg('email');
        $email = is_null($email) ? '' : base64_decode($email);
        // Get reviewers
        $data = Services::conferences()
                        ->paper()
                        ->review()
                        ->editor()
                        ->getReviewerList($conferenceId,
                                          $email,
                                          $page,
                                          $countPerPage);
        self::return($data);
    }
}