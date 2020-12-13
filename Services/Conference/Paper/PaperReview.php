<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/4/23
     * Time: 0:49
     */
    
    namespace myConf\Services\Conference\Paper;

    use myConf\Services\Conference\Paper\Review\Editor;
    use myConf\Services\Conference\Paper\Review\Reviewer;

    /**
     * Class PaperReview
     *
     * @package myConf\Services\Conferences\Papers
     */
    class PaperReview extends \myConf\BaseService {

        /**
         * Get the instance of sub-service Papers.
         * @return Editor
         */
        public function editor()
        {
            if (!isset($this->subServices['paperReviewEditor']))
            {
                $this->subServices['paperReviewEditor'] = new Editor();
            }
            return $this->subServices['paperReviewEditor'];
        }
    
        /**
         * @return Reviewer
         */
        public function reviewer()
        {
            if (!isset($this->subServices['paperReviewReviewer']))
            {
                $this->subServices['paperReviewReviewer'] = new Reviewer();
            }
            return $this->subServices['paperReviewReviewer'];
        }

        /**
         * @param string $reviewer_email
         * @param int $paper_id
         * @param int $paper_version
         * @return bool
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function reviewer_exists_in_paper(string $reviewer_email, int $paper_id, int $paper_version) : bool {
            return $this->models()
                        ->reviewers()
                        ->reviewer_exists($paper_id, $paper_version, $reviewer_email);
        }
        
        public function reviewer_save_review(string $reviewer_email, int
        $paper_id, int $paper_version, string $review_action, string
                                             $review_comment) : void {
            $this->Models->PaperReview->saveReview($paper_id,
                                                   $paper_version, $reviewer_email, $review_action,
                                                   $review_comment);
        }
        
        public function reviewer_submit_review(string $reviewer_email, int
        $paper_id, int $paper_version, string $review_action, string
                                               $review_comment) : void {
            $this->Models->PaperReview->submitReview($paper_id,
                                                     $paper_version, $reviewer_email, $review_action,
                                                     $review_comment);
        }
        
        /**
         * 编辑结束审稿
         * @param int $paper_id
         * @param int $paper_ver
         * @param string $review_result
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function finishReview(int $paper_id, int $paper_ver, string $review_result, string $comment = '') : void {
            $arr_result_mapping = [
                'reject' => \myConf\Models\Papers::paper_status_rejected,
                'accept' => \myConf\Models\Papers::paper_status_passed,
                'revision' => \myConf\Models\Papers::paper_status_revision,
            ];
            $this->Models->PaperReview->editor_finished_review($paper_id, $paper_ver, $arr_result_mapping[$review_result], $comment);
            $paper = $this->Models->Paper->getContent($paper_id, $paper_ver);
            $author_info = $this->Models->User->get_by_id($paper['user_id']);
            $conf_info = $this->Models->Conference->GetById($paper['conference_id']);
            if ($review_result == 'reject') {
                $content = "Your paper %s is rejected.";
            } else if ($review_result == 'accept') {
                $content = "Your paper %s is accepted.";
            } else {
                $content = "Your paper %s is accepted with revision. Please login the conference's website and submit a revision version.";
            }
            $content .= '<br/> Here are the editor\'s comments<br/><div style="border: 1px solid gray; padding: 5px; margin: 5px;">%s</div>';
            $content = sprintf($content, $paper['paper_title'], $comment);
            \myConf\Utils\Email::send_mail('PaperReview@myconf.cn', 'PaperReview', $author_info['user_email'], 'Paper acceptance notice for conference ' . $conf_info['conference_name'], $content);
        }
        
        public function get_review_status(int $paper_id, int $paper_version,
                                          string $reviewer_email
        ) : array {
            return $this->Models->PaperReview->getReviewStatus
            ($paper_id,
             $paper_version, $reviewer_email);
        }
        
        public function get_paper_review_details(int $paper_id,
                                                 int $paper_version): array
        {
            return $this->Models->PaperReview->get_paper_review_status($paper_id, $paper_version);
        }
    }