<?php
    
    
    namespace myConf\Services\Conference\Paper\Review;

    use myConf\Errors;
    use myConf\Errors\Services\Services as E_SERVICES;
    use myConf\Models\Constants\PaperStatus;
    use myConf\Utils\Email;
    use myConf\Utils\Paging;

    /**
     * Class Editor
     *
     * @package myConf\Services\Conferences\Paper\Review
     */
    class Editor extends \myConf\BaseService
    {
    
        /**
         * @param int    $paper_id
         * @param int    $paper_ver
         * @param string $reviewerEmail
         *
         * @return bool
         */
        public function addReviewer(int $paper_id,
                                    int $paper_ver,
                                    string $reviewerEmail)
        {
            //Check whether this reviewer has already been added.
            if ($this->models()
                     ->conference()
                     ->reviewer()
                     ->reviewer_exists($paper_id,
                                       $paper_ver,
                                       $reviewerEmail))
            {
                Errors::setError(E_SERVICES::E_REVIEWER_ALREADY_EXISTS,
                                 200,
                                 E_SERVICES::errorMessage[E_SERVICES::E_REVIEWER_ALREADY_EXISTS],
                                 'Reviewer already exists.');
                return false;
            }
            // Add a reviewer use default settings.
            $this->models()
                 ->conference()
                 ->reviewer()
                 ->add_reviewer_to_paper($paper_id,
                                         $paper_ver,
                                         $reviewerEmail);

            // Get the paper data.
            $paper = $this->models()
                          ->papers()
                          ->getContent($paper_id, $paper_ver);
            // Get the conference data.
            $conference = $this->models()
                               ->conference()
                               ->GetById($paper['conference_id']);
            // Check whether this email has been registered.
            $reviewerEmailRegistered = $this->models()
                                            ->users()
                                            ->exist_by_email($reviewerEmail);
            if ($reviewerEmailRegistered === false) {
                $content = '
                        <h1>审稿邀请函</h1>
                        <p>
                            您好:
                                会议 ' . $conference['conference_name'] . ' 邀请您参与会议的论文评审。
                                请您按照如下步骤进行操作。 <br/>
                                    (1) 打开myconf.cn，使用当前邮箱注册一个账号； <br/>
                                    (2) 登录myconf.cn，在Paper Review - Review Tasks 中寻找被分配给您评审的论文； <br/>
                                    (3) 点击Enter Review，进入审稿环节； <br/>
                                    (4) 点击Go to Review Page，进入审稿页面。您在该页面会看到论文标题、摘要和正文。您需要给出您的评审结果（Action栏）和评审意见（Comments栏）。 <br/>
									附:<br/>
									文章编号 : ' . $paper['paper_logic_id'] . '-' . $paper['paper_version'] . ' <br/>
									文章标题 : ' . $paper['paper_title'] . ' <br/>
                                如果您没有参与这个会议，或者不知道这个会议，请忽略这封邮件。 <br/>
                            系统邮件，请勿回复。谢谢！
                        </p>
                    ';
                Email::send_mail('Account@mail.myconf.cn', 'Account of myconf.cn', $reviewerEmail, 'Invitation for paper review', $content);
                return false;
            } else {
                $content = '
                        <h1>审稿邀请函</h1>
                        <p>
                            您好：<br/>
                                会议 ' . $conference['conference_name'] . ' 邀请您参与会议的论文评审。
                                请您登录myconf.cn，按照如下步骤进行审稿：<br/>
                                    (1) 登录myconf.cn，在Paper Review - Review Tasks 中寻找被分配给您评审的论文；<br/>
                                    (2) 点击Enter Review，进入审稿环节；<br/>
                                    (3) 点击Go to Review Page，进入审稿页面。您在该页面会看到论文标题、摘要和正文。您需要给出您的评审结果（Action栏）和评审意见（Comments栏）。<br/>
                                    附:<br/>
									文章编号 : ' . $paper['paper_logic_id'] . '-' . $paper['paper_version'] . ' <br/>
									文章标题 : ' . $paper['paper_title'] . ' <br/>
                                如果您没有参与这个会议，或者不知道这个会议，请忽略这封邮件。 <br/>
                            系统邮件，请勿回复。谢谢！
                        </p>
                    ';
                Email::send_mail('Account@mail.myconf.cn', 'Account of myconf.cn', $reviewerEmail, 'Invitation for paper review', $content);
            }
            return true;
        }
    
        /**
         * Finish the review of a given paper.
         * @param int    $paper_id
         * @param int    $paper_ver
         * @param int    $review_result
         * @param string $comment
         * @param bool   $emailNotification
         */
        public function finishReview(int $paper_id,
                                     int $paper_ver,
                                     int $review_result,
                                     string $comment = '',
                                     bool $emailNotification = false)
        {
            // Check whether the paper exists.
            if ($this->models()
                     ->papers()
                     ->exists($paper_id,
                              $paper_ver) === false)
            {
                Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                                 200,
                                 E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                                 sprintf('The target paper which id is %d-%d not found.', $paper_id, $paper_ver)
                );
                return;
            }
            // Get the data of the paper.
            $paperData = $this->models()
                              ->papers()
                              ->getContent($paper_id,
                                           $paper_ver);
            // Check whether the status of paper is submitted.
            if (intval($paperData['paper_status']) !== 0)
            {
                Errors::setError(E_SERVICES::E_PAPER_STATUS_INVALID,
                                 200,
                                 E_SERVICES::errorMessage[E_SERVICES::E_PAPER_STATUS_INVALID],
                                 'Cannot give the review result because the paper\'s status is not submitted.');
                return;
            }
            // Finish paper review.
            $this->models()
                 ->papers()
                 ->finishReview($paper_id,
                                $paper_ver,
                                $review_result,
                                $comment);
        
            // Get author's data and conference's data to send notification email.
            $authorData = $this->models()
                               ->users()
                               ->get_by_id($paperData['user_id']);
            $conferenceData = $this->models()
                                   ->conference()
                                   ->GetById($paperData['conference_id']);
            // The notification email will be sent if $emailNotice is true.
            if ($emailNotification === true)
            {
                // Set email variables first.
                $emailVariables = array(
                    'Conference' => $conferenceData['conference_name'],
                    'PaperId' => strval($paper_id) . '-' . strval($paper_ver),
                    'PaperTitle' => $paperData['paper_title'],
                    'EditorComment' => $comment,
                );
                // Define Email template path.
                // TODO Use database to storage different email templates.
                $emailTemplatePath = BASE_SYS_DIR
                    . DIRECTORY_SEPARATOR;
                $emailTemplate = '';
                // Set email content text.
                if ($review_result === PaperStatus::Rejected)
                {
                    $emailTemplate = $emailTemplatePath . 'reject.html';
                    $content = file_get_contents($emailTemplate);
                    if ($content === false)
                    {
                        $content = "Your paper {PaperId} : {PaperTitle} is rejected.";
                        $content .= '<br/> Here are the editor\'s comments<br/><div style="border: 1px solid gray; padding: 5px; margin: 5px;">{EditorComment}</div>';
                    }
                }
                else if ($review_result === PaperStatus::Accepted)
                {
                    $emailTemplate = $emailTemplatePath . 'accept.html';
                    $content = file_get_contents($emailTemplate);
                    if ($content === false)
                    {
                        $content = "Your paper {PaperId} : {PaperTitle} is accepted.";
                        $content .= '<br/> Here are the editor\'s comments<br/><div style="border: 1px solid gray; padding: 5px; margin: 5px;">{EditorComment}</div>';
                    }
                }
                else
                {
                    $emailTemplate = $emailTemplatePath . 'revise.html';
                    $content = file_get_contents($emailTemplate);
                    if ($content === false)
                    {
                        $content = "Your paper {PaperId} : {PaperTitle} will be accepted.
                    However, it needs a little revise.
                    Please login the conference's website and submit a revision.";
                        $content .= '<br/> Here are the editor\'s comments<br/><div style="border: 1px solid gray; padding: 5px; margin: 5px;">{EditorComment}</div>';
                    }

                }
                // Do string replace operations.
                foreach ($emailVariables as $key => $value)
                {
                    $key = '{' . $key . '}';
                    $content = str_replace($key, $value, $content);
                }
                // Send the email.
                Email::send_mail('PaperReview@myconf.cn',
                                 'PaperReview',
                                 $authorData['user_email'],
                                 'Paper Review Result for conference ' . $conferenceData['conference_name'],
                                 $content
                );
            }
            return;
        }
    
        /**
         * @param $conferenceId
         * @param $paperStatus
         * @param $paperSessionId
         * @param $page
         * @param $paperPerPage
         *
         * @return array
         * @noinspection PhpUnhandledExceptionInspection
         */
        public function getReviewStatus($conferenceId,
                                        $paperStatus,
                                        $paperSessionId,
                                        $page,
                                        $paperPerPage)
        {
            // Get count of papers which satisfy the restrictions.
            $paperCount = $this->models()
                               ->conference()
                               ->paper()
                               ->count($conferenceId,
                                       $paperStatus,
                                       $paperSessionId);
            // Calculate the pages.
            $pageCount = ceil((float)$paperCount / $paperPerPage);
            // Get conference papers.
            $papers = $this->models()
                           ->conference()
                           ->paper()
                           ->getAll($conferenceId, $paperStatus, ($page - 1) *
                                                 $paperPerPage,
                                    $paperPerPage,
                                    $paperSessionId);
            // Get additional data (review data, author data, etc.) for each paper.
            foreach ($papers as &$paper)
            {
                $user_info = $this->models()
                                  ->users()
                                  ->get_by_id($paper['user_id']);
                $scholar_info = $this->models()
                    ->Scholar
                    ->getByEmail($user_info['user_email']);
                // Get the paper's session data.
                $paper_session_info = $this->models()
                                           ->conference()
                                           ->session()
                                           ->get(intval($paper['paper_suggested_session']));
                // Get the paper's review data.
                $paper['review_status'] = $this->models()
                                               ->conference()
                                               ->paper()
                                               ->reviewers()
                                               ->getAll($paper['paper_logic_id'],
                                                        $paper['paper_version']);
                // Merge these data together.
                $paper['paper_suggested_session'] = $paper_session_info['session_text'];
                $paper['user_email'] = $user_info['user_email'];
                $paper['user_name'] = $scholar_info['scholar_first_name'] . ', ' . $scholar_info['scholar_last_name'];
            }
            // Get sessions.
            $sessions = $this->models()
                             ->conference()
                             ->session()
                             ->getAll($conferenceId, true);
            // Return merged data.
            return array(
                'papers'       => $papers,
                'paperCount'   => $paperCount,
                'pageCount'    => $pageCount,
                'page'         => $page,
                'paperStatus'  => $paperStatus,
                'paperTopicId' => $paperSessionId,
                'sessions'     => $sessions,
                'countPerPage' => $paperPerPage,
            );
        }
    
        /**
         * @param        $conferenceId
         * @param string $emailRestriction
         * @param int    $page
         * @param int    $paperPerPage
         *
         * @return array|false
         */
        public function getReviewerList($conferenceId,
                                        $emailRestriction = '',
                                        $page = 1,
                                        $paperPerPage = 10)
        {
            // Get the count of reviewers
            $reviewerCount = $this->models()
                                  ->conference()
                                  ->reviewer()
                                  ->count($conferenceId,
                                          $emailRestriction);
            // Calculate paging array.
            $pagingArray = Paging::calc($reviewerCount,
                                        $page,
                                        $paperPerPage);
            // Get all reviewers email.
            $reviewers = $this->models()
                              ->conference()
                              ->reviewer()
                              ->get($conferenceId,
                                    $emailRestriction,
                                    $pagingArray);
    
            $reviewStatus = array(0 => 'Waiting', 1 => 'Reviewing', 2 => 'Finished');
            foreach ($reviewers as &$reviewer)
            {
                $totalCount = 0;
                for ($i = 0; $i <= 2; $i++)
                {
                    $count = $this->models()
                                  ->conference()
                                  ->reviewer()
                                  ->getTaskCount($conferenceId,
                                                 $reviewer['email'],
                                                 $i);
                    $totalCount += $count;
                    $reviewer['taskStatistics'][$reviewStatus[$i]] = $count;
                }
                $reviewer['taskStatistics']['Total'] = $totalCount;
            }
            return array('reviewerList'  => $reviewers,
                         'reviewerCount' => $reviewerCount,
                         'pageCount'     => $pagingArray['page_count'],
                         'page'          => $page,
                         'countPerPage'  => $paperPerPage,
            );
        }
    
        /**
         * @param int $paperId
         * @param int $paperVersion
         *
         * @return array|false
         */
        public function getReviewers(int $paperId, int $paperVersion)
        {
            return $this->getPaperReviewers($paperId, $paperVersion);
        }
    
        /**
         * @param int $paperId
         * @param int $paperVersion
         *
         * @return array|false
         */
        public function preview(int $paperId, int $paperVersion)
        {
            return $this->getPaperData($paperId,
                                       $paperVersion,
                                       false);
        }
    
        /**
         * @param int $paperId
         * @param int $paperVersion
         *
         * @return array|false
         */
        public function loadDecisionPage(int $paperId, int $paperVersion)
        {
            return $this->getPaperData($paperId,
                                       $paperVersion,
                                       true);
        }
        
        // Common private methods.
    
        /**
         * @param      $paperId
         * @param      $paperVersion
         * @param bool $includingReviewers
         *
         * @return array|false
         */
        private function getPaperData($paperId,
                                      $paperVersion,
                                      $includingReviewers = false)
        {
            // Check the existence of specified paper.
            if ($this->paperExist($paperId, $paperVersion) === false)
            {
                return false;
            }
            // Get paper data.
            $paper = $this->models()
                          ->conference()
                          ->paper()
                          ->getContent($paperId,
                                       $paperVersion);
            $suggestedSession = $this->models()
                                     ->conference()
                                     ->session()
                                     ->get(intval($paper['paper_suggested_session']));
            $paper['suggested_session_text'] = $suggestedSession['session_text'];
            // If reviewers need to be included, add them.
            if ($includingReviewers === true)
            {
                $paper['reviewers'] = $this->getPaperReviewers($paperId, $paperVersion);
            }
            return $paper;
        }
    
        /**
         * @param $paperId
         * @param $paperVersion
         *
         * @return array|bool
         */
        private function getPaperReviewers($paperId, $paperVersion)
        {
            // Check the existence of specified paper.
            if ($this->paperExist($paperId, $paperVersion) === false)
            {
                return false;
            }
            // Get raw data from model layer.
            $data = $this->models()
                         ->conference()
                         ->paper()
                         ->reviewers()
                         ->getAll($paperId,
                                  $paperVersion);
            $finalData = array();
            // Define review result to message index mapping.
            $resultToMessageIndex = array(
                'PASSED' => 2,
                'REVISION' => 3,
                'REJECTED'=> 4,
                'UNKNOWN' => 5,
            );
            // Define the message string mapping.
            $resultMessage = array(
                0x0 => 'Waiting',
                0x1 => 'Reviewing',
                0x2 => 'Accept',
                0x3 => 'Revise',
                0x4 => 'Reject',
                0x5 => 'Unknown Review Result',
                0xf => 'Not Registered',
            );
            // Process each reviewer's data.
            foreach ($data as $reviewer)
            {
                if ($this->models()->users()->exist_by_email($reviewer['reviewer_email']) === false)
                {
                    // If the current user does not exist, the paper must not be reviewed.
                    $finalReviewer = array(
                        'email'          => $reviewer['reviewer_email'],
                        'result'        => 0xf,
                        'result_message' => $resultMessage[0xf],
                        'review_comment' => '',
                    );
                }
                else
                {
                    // Fetch his/her scholar information, to get his/her name for display.
                    $scholar = $this->models()->Scholar->getByEmail($reviewer['reviewer_email']);
                    $finalReviewer = array(
                        'email'      => $reviewer['reviewer_email'],
                        'first_name' => $scholar['scholar_first_name'],
                        'last_name'  => $scholar['scholar_last_name'],
                        'comments' => null,
                    );
                    $reviewStatus = intval($reviewer['review_status']);
                    if ($reviewStatus < 2)
                    {
                        // Review status is waiting or reviewing, so comments cannot be shown.
                        $finalReviewer['result'] = $reviewStatus;
                        $finalReviewer['result_message'] = $resultMessage[$reviewStatus];
                        $finalReviewer['comments'] = '';
                    }
                    else
                    {
                        // If the current user has entered the review but
                        // the review has not been finished.
                        $finalReviewer['result'] = $resultToMessageIndex[$reviewer['review_result']];
                        $finalReviewer['result_message'] = $resultMessage[$finalReviewer['result']];
                        $finalReviewer['comments'] = $reviewer['review_comment'];
                    }
                }
                // Add the reviewer to the result array.
                $finalData []= $finalReviewer;
            }
            return $finalData;
        }
    
        /**
         * @param $paperId
         * @param $paperVersion
         *
         * @return bool
         */
        private function paperExist($paperId, $paperVersion)
        {
            // Paper existence check.
            if ($this->models()
                     ->conference()
                     ->paper()
                     ->exists($paperId,
                              $paperVersion) === false)
            {
                Errors::setError(E_SERVICES::E_PAPER_NOT_EXISTS,
                                 404,
                                 E_SERVICES::errorMessage[E_SERVICES::E_PAPER_NOT_EXISTS],
                                 'The requested paper doest not exist.');
                return false;
            }
            return true;
        }
    }