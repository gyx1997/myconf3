<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/24
 * Time: 18:36
 */

namespace myConf\Services\Conference;

use myConf\Services;
use myConf\BaseService;
use myConf\Exceptions\PaperNotFoundException;

//use myConf\Utils\Input;
use myConf\Models\Constants\PaperStatus;
use myConf\Utils\Arguments;
use myConf\Utils\DB;
use myConf\Utils\Email;
/* Imports for error handler. */
use myConf\Errors\Services\Services as E_SERVICES;
use myConf\Errors;
/**
 * Class Paper
 *
 * @package myConf\Services
 * @author  _g63<522975334@qq.com>
 * @version 2019.1
 */
class Papers extends BaseService
{
    /**
     * Paper constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /* Begin of sub service(s). */
    
    /**
     * Get the instance of sub-service Papers.
     * @return Services\Conference\Paper\PaperReview
     */
    public function review()
    {
        if (!isset($this->subServices['paperReview']))
        {
            $this->subServices['paperReview'] = new Services\Conference\Paper\PaperReview();
        }
        return $this->subServices['paperReview'];
    }
    /* End of sub service(s). */

    public const PaperReviewAcceptedStatus = array(
        'reject'   => PaperStatus::Rejected,
        'accept'   => PaperStatus::Accepted,
        'revision' => PaperStatus::Revision,
    );

    /**
     * 保存草稿
     *
     * @param int    $paperLogicId
     * @param int    $paperVersion
     * @param string $paper_field
     * @param string $copyright_field
     * @param array  $authors
     * @param string $type
     * @param string $title
     * @param string $abstract
     * @param string $suggestedSession
     * @param string $suggestedSessionCustom
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\DbTransactionException
     * @throws \myConf\Exceptions\FileUploadException
     */
    public function save(int $paperLogicId,
                         int $paperVersion,
                         int $paperAttachmentId,
                         int $copyrightAttachmentId,
                         array $authors,
                         string $type,
                         string $title,
                         string $abstract,
                         string $suggestedSession,
                         string $suggestedSessionCustom = ''): void
    {
        $this->Models->Paper->Update($paperLogicId, $paperVersion, PaperStatus::Saved, $authors, $paperAttachmentId, $copyrightAttachmentId, $type, $suggestedSession, $title, $abstract, $suggestedSessionCustom);
    }
    
    /**
     * @param array $authors
     *
     * @return bool
     */
    private function addAuthorsAsScholars($authors = array()) {
        /* Begin transaction of add scholars. */
        DB::transBegin();
        foreach ($authors as $author)
        {
            /* If one author has not been added, it should be added. */
            if ($this->Models->Scholar->exist_by_email($author['email']) === false)
            {
                $this->Models->Scholar->create_new($author['email'], $author['first_name'], $author['last_name'], $author['chn_full_name'], $author['address'], $author['prefix'], $author['institution'], $author['department']);
            }
        }
        /* If the transaction succeeds, the function succeeds. */
        return DB::transEnd();
    }

    /**
     * @param int    $old_paper_id
     * @param int    $old_paper_version
     * @param int    $user_id
     * @param int    $conference_id
     * @param string $title
     * @param string $abstract
     * @param array  $authors
     * @param int    $paper_aid
     * @param int    $copyright_aid
     * @param string $type
     * @param string $suggested_session
     * @param string $custom_suggested_session
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function newRevision(int $old_paper_id,
                                int $old_paper_version,
                                int $user_id,
                                int $conference_id,
                                string $title,
                                string $abstract,
                                array $authors,
                                int $paper_aid,
                                int $copyright_aid,
                                string $type,
                                string $suggested_session,
                                string $custom_suggested_session = '',
                                bool $draft = false)
    {
        // Revision paper must follow an existed paper.
        if ($this->models()
                 ->papers()
                 ->exists($old_paper_id, $old_paper_version) === false)
        {
            Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                             'The specified paper not found.');
            return false;
        }
        // Revision paper must follow the reviewed paper which status is Revision.
        $oldPaperStatus = intval($this->models()
                                      ->papers()
                                      ->getContent($old_paper_id, $old_paper_version)['paper_status']);
        if ($oldPaperStatus !== PaperStatus::Revision)
        {
            Errors::setError(E_SERVICES::E_PAPER_STATUS_INVALID,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_STATUS_INVALID],
                             'Cannot submit revision due to status of  old paper which is not revision.');
        }
        // Add new authors as new scholars.
        $ret = $this->addAuthorsAsScholars($authors);
        if ($ret === false)
        {
            // If adding scholars failed, report errors and return.
            Errors::setError(E_SERVICES::E_ADD_SCHOLAR_FAILED);
            return false;
        }
        // Get the paper's max version with the specified logic id.
        $maxVersion = $this->models()
                           ->conference()
                           ->paper()
                           ->getMaxVersionOfPaper($old_paper_id);
        // Add new paper.
        $newPaperId = $this->models()
                           ->conference()
                           ->paper()
                           ->add($user_id,
                                 $conference_id,
                                 $authors,
                                 $paper_aid,
                                 $copyright_aid,
                                 $type,
                                 $suggested_session,
                                 $title,
                                 $abstract,
                                 $draft === true ? PaperStatus::Saved : PaperStatus::Submitted,
                                 $custom_suggested_session,
                                 $old_paper_id,
                                 // The version of a revision paper must greater than the old one.
                                 $maxVersion + 1);
        if ($newPaperId === false)
        {
            // If adding a new paper failed, report errors and return.
            Errors::setError(E_SERVICES::E_ADD_PAPER_FAILED);
            return false;
        }
        return true;
    }

    /**
     * @param int    $paper_id
     * @param int    $paper_version
     * @param int    $paper_aid
     * @param int    $copyright_aid
     * @param array  $authors
     * @param string $type
     * @param string $title
     * @param string $abstract
     * @param string $suggested_session
     * @param string $suggested_session_custom
     * @param bool   $draft
     */
    public function update(int $paper_id,
                           int $paper_version,
                           int $paper_aid,
                           int $copyright_aid,
                           array $authors,
                           string $type,
                           string $title,
                           string $abstract,
                           string $suggested_session,
                           string $suggested_session_custom = '',
                           bool $draft = false)
    {
        // Update operation must be done on an existing paper.
        /** @noinspection PhpUnhandledExceptionInspection */
        if ($this->models()
                 ->papers()
                 ->exists($paper_id,
                          $paper_version) === false) {
            Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                             'The specified paper not found.');
            return false;
        }
        // Update operation can only be done on paper which status is saved.
        /** @noinspection PhpUnhandledExceptionInspection */
        $paperStatus = intval($this->models()
                                   ->papers()
                                   ->getContent($paper_id, $paper_version)['paper_status']);
        if ($paperStatus !== PaperStatus::Saved)
        {
            Errors::setError(E_SERVICES::E_PAPER_STATUS_INVALID, 403);
            return false;
        }
        // Check authors.
        $authorsRet = $this->addAuthorsAsScholars($authors);
        if ($authorsRet === false)
        {
            /* If adding authors as scholars failed, report an error and return. */
            Errors::setError(E_SERVICES::E_ADD_SCHOLAR_FAILED);
            return false;
        }
        // Update the specified paper.
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->models()
             ->conference()
             ->paper()
            ->Update($paper_id,
                     $paper_version,
                     $draft === true ? PaperStatus::Saved :
                         PaperStatus::Submitted,
                     $authors,
                     $paper_aid,
                     $copyright_aid,
                     $type,
                     $suggested_session,
                     $title, $abstract,
                     $suggested_session_custom);
        return true;
    }

    /**
     * @param int $user_id
     * @param int $conference_id
     *
     * @return array
     */
    public function getUserPapers(int $user_id,
                                  int $conference_id)
    {
        return $this->models()
                    ->conference()
                    ->paper()
                    ->GetPapersFromConferenceByUserId($conference_id,
                                                      $user_id);
    }

    /**
     * @param int $paper_id
     * @param int $paper_version
     *
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function get(int $paper_id,
                        int $paper_version): array
    {
        return $this->models()
                    ->conference()
                    ->paper()
                    ->getContent($paper_id,
                                 $paper_version);
    }

    /**
     * @param int    $user_id
     * @param int    $conference_id
     * @param string $title
     * @param string $abstract
     * @param array  $authors
     * @param int    $paper_aid
     * @param int    $copyright_aid
     * @param string $type
     * @param string $suggested_session
     * @param string $custom_suggested_session
     * @param bool   $draft
     */
    public function new(int $user_id,
                        int $conference_id,
                        string $title,
                        string $abstract,
                        array $authors,
                        int $paper_aid,
                        int $copyright_aid,
                        string $type,
                        string $suggested_session,
                        string $custom_suggested_session = '',
                        $draft = false)
    {
        // Add scholars.
        $ret = $this->addAuthorsAsScholars($authors);
        if ($ret === false) {
            Errors::setError(E_SERVICES::E_ADD_SCHOLAR_FAILED,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_ADD_SCHOLAR_FAILED],
                             'Add scholar(s) failed.');
            return false;
        }
        // Add new paper.
        /** @noinspection PhpUnhandledExceptionInspection */
        $newPaperId = $this->models()
                           ->conference()
                           ->paper()
                           ->add($user_id,
                                 $conference_id,
                                 $authors,
                                 $paper_aid,
                                 $copyright_aid,
                                 $type,
                                 $suggested_session,
                                 $title,
                                 $abstract,
                                 $draft ? PaperStatus::Saved : PaperStatus::Submitted,
                                 $custom_suggested_session);
        if ($newPaperId === false)
        {
            Errors::setError(E_SERVICES::E_ADD_PAPER_FAILED,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_ADD_PAPER_FAILED],
                             'An error occurred when adding a paper.');
            return false;
        }
        return true;
    }

    public function delete(int $logicId,
                           int $version,
                           array $acceptedStatus = [])
    {
        // If $acceptedStatus is unset, all normal status is accepted.
        if (empty($acceptedStatus))
        {
            $acceptedStatus = array(
                PaperStatus::Saved,
                PaperStatus::Submitted,
                PaperStatus::UnderReview,
                PaperStatus::Accepted,
                PaperStatus::Rejected,
                PaperStatus::Revision,
            );
        }
        // Get paper's data from model layer.
        $paper = $this->models()->conference()->paper()->getContent($logicId, $version);
        // If paper with given id has not been found, report an error.
        if (empty($paper) === true)
        {
            Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                             'The paper to be deleted not found.');
            return false;
        }
        // If status is invalid, report an error.
        if (!in_array($paper['paper_status'], $acceptedStatus))
        {
            Errors::setError(E_SERVICES::E_PAPER_STATUS_INVALID,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_STATUS_INVALID],
                             'Paper status invalid. ');
            return false;
        }
        // Move the paper with given id to the trash box.
        // On the other word, in this function, paper would not be deleted directly.
        $this->models()
             ->conference()
             ->paper()
             ->MovePaperToTrashBox($logicId,
                                   $version);
        return true;
    }


    /**
     * @param int $paperLogicId
     * @param int $paperVersion
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function deletePermanently(int $paperLogicId,
                                      int $paperVersion)
    {
        // Get the paper's data.
        $paper = $this->models()
                      ->conference()
                      ->paper()
                      ->getContent($paperLogicId,
                                   $paperVersion);
        // If paper with given id has not been found, report an error.
        if (empty($paper) === true)
        {
            Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                             'The paper to be deleted not found.');
            return false;
        }
        // If the paper is not in the trash box, report an error.
        if ($paper['paper_status'] !== PaperStatus::TrashBox)
        {
            Errors::setError(E_SERVICES::E_PAPER_STATUS_INVALID,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_PAPER_STATUS_INVALID],
                             'Paper status invalid. ');
            return false;
        }
        // Check the attachments including paper content file and paper copyright file.
        $attachment_pdf = $this->models()
                               ->conference()
                               ->attachment()
                               ->get($paper['pdf_attachment_id']);
        if (empty($attachment_pdf) === false)
        {
            $pdf_file = ATTACHMENT_DIR . $attachment_pdf['attachment_file_name'];
            file_exists($pdf_file) && @unlink($pdf_file);
        }
        $attachment_copyright = $this->models()
                                     ->conference()
                                     ->attachment()
                                     ->get($paper['copyright_attachment_id']);
        if (empty($attachment_copyright) === false)
        {
            $copyright_file = ATTACHMENT_DIR . $attachment_copyright['attachment_file_name'];
            file_exists($copyright_file) && @unlink($copyright_file);
        }
        // Delete paper data (including session data, attachment data, etc.) from database.
        $this->models()
             ->conference()
             ->paper()
             ->delete($paperLogicId,
                      $paperVersion);
        return true;
    }

    /**
     * @param $conferenceId
     * @param $paperStatus
     * @param $paperSessionId
     * @param $page
     * @param $paperPerPage
     *
     */
    public function getList($conferenceId,
                            $paperStatus,
                            $paperSessionId,
                            $page,
                            $paperPerPage)
    {
        // Get count of papers which satisfy the restrictions.
        $paperCount = $this->models()
                           ->conference()
                           ->paper()
                           ->count($conferenceId, $paperStatus, $paperSessionId);
        // Calculate the pages.
        $pageCount = ceil((float)$paperCount / $paperPerPage);
        // Get conference papers.
        $papers = $this->models()
                       ->conference()
                       ->paper()
                       ->getAll($conferenceId,
                                $paperStatus, ($page - 1) *
                                             $paperPerPage,
                                $paperPerPage,
                                $paperSessionId);
        // Get additional data (review data, author data, etc.) for each paper.
        foreach ($papers as &$paper)
        {
            $user_info = $this->models()
                              ->users()
                              ->get_by_id($paper['user_id']);
            $scholar_info = $this->Models
                                 ->Scholar
                                 ->getByEmail($user_info['user_email']);
            $paper_session_info = $this->models()
                                       ->conference()
                                       ->session()
                                       ->get(intval($paper['paper_suggested_session']));
            $paper['review_status'] = $this->models()
                                           ->papers()
                                           ->reviewers()
                                           ->getAll($paper['paper_logic_id'],
                                                    $paper['paper_version']);
            $paper['paper_suggested_session'] = $paper_session_info['session_text'];
            $paper['user_email'] = $user_info['user_email'];
            $paper['user_name'] = $scholar_info['scholar_first_name'] . ', ' . $scholar_info['scholar_last_name'];
        }
        return array(
            'papers'       => $papers,
            'paperCount'   => $paperCount,
            'pageCount'    => $pageCount,
            'page'         => $page,
            'paperStatus'  => $paperStatus,
            'paperTopicId' => $paperSessionId,
        );
    }

    public function getPaperCountByConference()
    {

    }
    
    /**
     * @param     $conferenceId
     * @param int $paperStatus
     *
     * @return mixed
     */
    public function GetConferencePaperCount($conferenceId,
                                            $paperStatus = -1)
    {
        return $this->Models->Conference->GetConferencePaperCount($conferenceId, $paperStatus);
    }

    /**
     * @param int    $paper_id
     * @param int    $paper_ver
     * @param string $reviewerEmail
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\ReviewerAlreadyExistsException
     */
    public function addReviewerToPaper(int $paper_id,
                                       int $paper_ver,
                                       string $reviewerEmail)
    {

        //Check whether this reviewer has already been added.
        if ($this->Models->PaperReview->reviewer_exists($paper_id, $paper_ver, $reviewerEmail))
        {
            Errors::setError(E_SERVICES::E_REVIEWER_ALREADY_EXISTS,
                             200,
                             E_SERVICES::errorMessage[E_SERVICES::E_REVIEWER_ALREADY_EXISTS],
                             'Reviewer already exists.');
            return false;
        }

        $this->Models->PaperReview->add_reviewer_to_paper($paper_id, $paper_ver, $reviewerEmail);
        $paper = $this->Models->Paper->getContent($paper_id, $paper_ver);
        if ($this->Models->User->exist_by_email($reviewerEmail) === false) {
            $content = '
                        <h1>审稿邀请函</h1>
                        <p>
                            您好：
                                会议CSQRWTC的主办方邀请您参与会议的论文评审。请按照如下步骤进行操作。 <br/>
                                    (1) 打开myconf.cn，使用当前邮箱注册一个账号； <br/>
                                    (2) 登录myconf.cn，在Paper Review - Review Tasks 中寻找被分配给您评审的论文； <br/>
                                    (3) 点击Enter Review，进入审稿环节； <br/>
                                    (4) 点击Go to Review Page，进入审稿页面。您在该页面会看到论文标题、摘要和正文。您需要给出您的评审结果（Action栏）和评审意见（Comments栏）。 <br/>
									
									文章编号 : ' . $paper['paper_logic_id'] . '-' . $paper['paper_version'] . ' <br/>
									文章标题 : ' . $paper['paper_title'] . ' <br/>
                                如果您没有参与这个会议，或者不知道这个会议，请忽略这封邮件。 <br/>
                            谢谢！
                        </p>
                    ';
            Email::send_mail('Account@mail.myconf.cn', 'Account of myconf.cn', $reviewerEmail, 'Invitation for paper review', $content);
            return false;
        } else {
            $content = '
                        <h1>审稿邀请函</h1>
                        <p>
                            您好：<br/>
                                会议CSQRWTC的主办方邀请您参与会议的论文评审。请您登录myconf.cn，按照如下步骤进行审稿：<br/>
                                    (1) 登录myconf.cn，在Paper Review - Review Tasks 中寻找被分配给您评审的论文；<br/>
                                    (2) 点击Enter Review，进入审稿环节；<br/>
                                    (3) 点击Go to Review Page，进入审稿页面。您在该页面会看到论文标题、摘要和正文。您需要给出您的评审结果（Action栏）和评审意见（Comments栏）。<br/>
									
									文章编号 : ' . $paper['paper_logic_id'] . '-' . $paper['paper_version'] . ' <br/>
									文章标题 : ' . $paper['paper_title'] . ' <br/>
                                如果您没有参与这个会议，或者不知道这个会议，请忽略这封邮件。 <br/>
                            谢谢！
                        </p>
                    ';
            Email::send_mail('Account@mail.myconf.cn', 'Account of myconf.cn', $reviewerEmail, 'Invitation for paper review', $content);
        }
        return true;
    }

    /**
     * Finish the review of a given paper.
     *
     * @param int    $paper_id
     * @param int    $paper_ver
     * @param int    $review_result
     * @param string $comment
     */
    public function finishReview(int $paper_id,
                                 int $paper_ver,
                                 int $review_result,
                                 string $comment = '')
    {

        /* Check whether the paper exists. */
        if ($this->Models->papers()->exists($paper_id, $paper_ver) === false)
        {
            Errors::setError(1);
            return;
        }

        $paperData = $this->Models->papers()->getContent($paper_id, $paper_ver);

        /* Check whether the status of paper is submitted. */
        if ($paperData['paper_status'] != 0)
        {
            Errors::setError(2);
            return;
        }

        /* Finish paper review. */
        $this->Models->papers()->finishReview($paper_id, $paper_ver, $review_result, $comment);

        /* Get author's data and conference's data and send notification email. */
        $authorData = $this->Models->User->get_by_id($paperData['user_id']);
        $conferenceData = $this->Models->Conference->GetById($paperData['conference_id']);

        if ($review_result == 'reject')
        {
            $content = "Your paper %s is rejected.";
        }
        else if ($review_result == 'accept')
        {
            $content = "Your paper %s is accepted.";
        }
        else
        {
            $content = "Your paper %s is accepted with revision. Please login the conference's website and submit a revision version.";
        }

        $content .= '<br/> Here are the editor\'s comments<br/><div style="border: 1px solid gray; padding: 5px; margin: 5px;">%s</div>';
        $content = sprintf($content, $paperData['paper_title'], $comment);

        Email::send_mail('PaperReview@myconf.cn', 'PaperReview', $authorData['user_email'], 'Paper acceptance notice for conference ' . $conferenceData['conference_name'], $content);

        return;
    }



}