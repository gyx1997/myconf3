<?php
    
    
    namespace myConf\Services\Conference\Paper\Review;
    
    
    use myConf\Errors;
    use myConf\Errors\Services\Services as ESRV;
    use myConf\Exceptions\DbCompositeKeysException;

    class Reviewer extends \myConf\BaseService
    {
    
        /**
         * @param int $userId
         * @param int $paper_id
         * @param int $paper_version
         */
        public function enterReview(int $userId, int $paper_id, int $paper_version)
        {
            // First get the reviewer's email.
            $userEmail = $this->models()->users()->get_by_id($userId)['user_email'];
            // Then the reviewer can enter the review.
            $this->models()->conference()->reviewer()->startReview($paper_id,
                                                                   $paper_version,
                                                                   $userEmail);
            return true;
        }
    
        public function submitReview(int $userId,
                                     int $paper_id,
                                     int $paper_version,
                                     string $review_action,
                                     string $review_comment,
                                     bool $isDraft = false)
        {
            // First get the reviewer's email.
            $userEmail = $this->models()->users()->get_by_id($userId)['user_email'];
            try
            {
                if ($isDraft === true)
                {
                    $this->models()
                         ->conference()
                         ->reviewer()
                         ->saveReview($paper_id,
                                      $paper_version,
                                      $userEmail,
                                      $review_action,
                                      $review_comment
                         );
                }
                else
                {
                    $this->models()
                         ->conference()
                         ->reviewer()
                         ->submitReview($paper_id,
                                      $paper_version,
                                      $userEmail,
                                      $review_action,
                                      $review_comment
                         );
                }
                return true;
            }
            catch (DbCompositeKeysException $e)
            {
                Errors::setError(ESRV::E_REVIEW_RECORD_NOT_EXISTS, 200,
                                 ESRV::errorMessage[ESRV::E_REVIEW_RECORD_NOT_EXISTS], 'The current review does not exist.');
                return false;
            }
        }
    
        /**
         * @param int $userId
         * @param int $conference_id
         *
         * @return array
         */
        public function getTasks(int $userId,
                                 int $conference_id)
        {
            //TODO Existence check needs to be done here.
            $userEmail = $this->models()
                              ->users()
                              ->get_by_id($userId)['user_email'];
            $tasks = $this->models()
                          ->conference()
                          ->reviewer()
                          ->getTasksForReviewer($userEmail, $conference_id);
            $tasksUnhandled = array();
            $tasksFinished = array();
            foreach ($tasks as &$paper)
            {

                $user_info = $this->Models->User->get_by_id($paper['user_id']);
                $scholar_info = $this->Models->Scholar->getByEmail($user_info['user_email']);
                $paper_session_info = $this->Models->PaperSession->get(intval($paper['paper_suggested_session']));
                $paper['paper_suggested_session'] = $paper_session_info['session_text'];
                $paper['user_email'] = $user_info['user_email'];
                $paper['user_name'] = $scholar_info['scholar_first_name'] . ', ' . $scholar_info['scholar_last_name'];
                if (intval($paper['review_status'])  === 2)
                {
                    $tasksFinished []= $paper;
                }
                else
                {
                    $tasksUnhandled []= $paper;
                }
            }
            return array('finished_papers' => $tasksFinished, 'unhandled_papers' => $tasksUnhandled);
        }
    
        /**
         * @param $userId
         * @param $paperId
         * @param $paperVersion
         *
         * @return array|void
         * @throws DbCompositeKeysException
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function getSingleReviewTask($userId, $paperId, $paperVersion)
        {
            // User id not need to be checked since it is got from global
            // variable list, which is added during login. However, paper id
            // needs to be checked.
            if ($this->models()
                     ->conference()
                     ->paper()
                     ->exists($paperId, $paperVersion) ===  false)
            {
                Errors::setError(ESRV::E_PAPER_NOT_EXISTS,
                                 200,
                                 ESRV::errorMessage[ESRV::E_PAPER_NOT_EXISTS],
                                 'The paper to be reviewed does not exist any more.');
                return;
            }
            $userEmail = $this->models()
                              ->users()
                              ->get_by_id($userId)['user_email'];
            // Get the current user's review data.
            $reviewData = $this->models()
                                 ->conference()
                                 ->reviewer()
                                 ->getReviewStatus($paperId,
                                                   $paperVersion,
                                                   $userEmail);
            // Get the paper data.
            $paperData = $this->models()
                          ->conference()
                          ->paper()
                          ->getContent($paperId,
                                       $paperVersion);
            return array('paper' => $paperData,
                         'review_status' => $reviewData);
        }
    }