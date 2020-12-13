<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/2/24
     * Time: 10:02
     */

    namespace myConf\Models;

    use myConf\Exceptions\DbCompositeKeysException;

    /**
     * Class PaperReview
     *
     * @package myConf\Models
     * @author _g63<522975334@qq.com>
     * @version 2019.3
     */
    class Reviewer extends \myConf\BaseModel {

        public const review_status_before_review = 0;
        public const review_status_under_review = 1;
        public const review_status_finished_review = 2;

        public const review_result_unknown = 'UNKNOWN';
        public const review_result_passed = 'PASSED';
        public const review_result_revision = 'REVISION';
        public const review_result_reject = 'REJECTED';

        /**
         * PaperReview constructor.
         */
        public function __construct() {
            parent::__construct();
        }

        /**
         * 获取某一文章所有的review记录
         * @param int $paper_id
         * @return array
         */
        public function get_paper_review_status(int $paper_id, int $paper_version) : array {
            return $this->tables()->PaperReview->fetchAll(['paper_id' => $paper_id, 'paper_version' => $paper_version]);
        }

        /**
         * @param int $paper_id
         * @param int $paper_ver
         * @param string $reviewer_email
         * @return array
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function getReviewStatus(int $paper_id,
                                        int $paper_ver,
                                        string $reviewer_email)
        {
            return $this->tables()->PaperReview->get(['paper_id' => $paper_id, 'paper_version' => $paper_ver, 'reviewer_email' => $reviewer_email]);
        }

        /**
         * 编辑添加一个reviewer
         * @param int $paper_id
         * @param string $reviewer_email
         * @return int
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function add_reviewer_to_paper(int $paper_id, int $paper_version, string $reviewer_email) : int {
            //TODO User's role should also be changed.
            //
            return $this->tables()->PaperReview->insert([
                'paper_id' => $paper_id,
                'paper_version' => $paper_version,
                'reviewer_email' => $reviewer_email,
                'review_status' => self::review_status_before_review,
                'review_result' => self::review_result_unknown,
                'review_comment' => ''
            ]);
        }

        /**
         * 某篇paper的指定reviewer是否存在
         * @param int $paper_id
         * @param int $paper_version
         * @param string $reviewer_email
         * @return bool
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function reviewer_exists(int $paper_id, int $paper_version, string $reviewer_email) : bool {
            return $this->tables()->PaperReview->exist(['paper_id' => $paper_id, 'paper_version' => $paper_version, 'reviewer_email' => $reviewer_email]);
        }

        /**
         * 从指定的文章中删除指定的reviewer
         * @param int $paper_id
         * @param string $reviewer_email
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function delete_reviewer_from_paper(int $paper_id, int $paper_version, string $reviewer_email) : void {
            $this->tables()->PaperReview->delete(['paper_id' => $paper_id, 'paper_version' => $paper_version, 'reviewer_email' => $reviewer_email]);
        }

        /**
         * 保存review的记录
         * @param int $paper_id
         * @param string $reviewer_email
         * @param string $review_result
         * @param string $review_comment
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function saveReview(int $paper_id, int $paper_version, string $reviewer_email, string $review_result, string $review_comment) {
            $this->tables()->PaperReview->set(['paper_id'       => $paper_id,
                                             'paper_version'  => $paper_version,
                                             'reviewer_email' => $reviewer_email
                                            ],
                                            ['review_result'  => $review_result,
                                             'review_comment' => $review_comment
                                            ]
            );
        }

        /**
         * 提交review
         * @param int $paper_id
         * @param string $reviewer_email
         * @param string $review_result
         * @param string $review_comment
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function submitReview(int $paper_id, int $paper_version, string $reviewer_email, string $review_result, string $review_comment) : void {
            $this->tables()->PaperReview->set(['paper_id' => $paper_id, 'paper_version' => $paper_version, 'reviewer_email' => $reviewer_email], ['review_result' => $review_result, 'review_comment' => $review_comment, 'review_status' => self::review_status_finished_review]);
        }

        /**
         * 某个审稿人进入评审环节
         * @param int $paper_id
         * @param string $reviewer_email
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function startReview(int $paper_id, int $paper_version, string $reviewer_email) : void {
            $this->tables()->PaperReview->set(['paper_id' => $paper_id, 'paper_version' => $paper_version, 'reviewer_email' => $reviewer_email], ['review_result' => self::review_result_unknown, 'review_status' => self::review_status_under_review]);
        }

        /**
         * 编辑结束审稿
         * @param int $paper_id
         * @param int $paper_version
         * @param int $paper_status
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function editor_finished_review(int $paper_id, int $paper_version, int $paper_status, string $paper_comments = '') : void {
            $this->tables()->Papers->set(
                ['paper_logic_id' => strval($paper_id), 'paper_version' => $paper_version],
                ['paper_status' => $paper_status, 'paper_comments' => $paper_comments]
            );
        }

        /**
         * 得到某个会议中某个审稿人的所有任务
         * @param string $reviewer_email
         * @param int $conference_id
         * @return array
         */
        public function getTasksForReviewer(string $reviewer_email,
                                            int $conference_id = 0) :
        array {
            $table_review = \myConf\Utils\DB::MakeTable('paper_review');
            $table_paper = \myConf\Utils\DB::MakeTable('papers');
            // use joint queries
            $sql = "SELECT $table_review.review_status, $table_review.review_result, $table_paper.* FROM $table_review, $table_paper WHERE $table_review.reviewer_email = '%s' AND $table_paper.paper_logic_id = $table_review.paper_id AND $table_review.paper_version = $table_paper.paper_version" . ($conference_id === 0 ? "" : " AND $table_paper.conference_id = %d");

            if ($conference_id === 0) {
                $results = \myConf\Utils\DB::FetchAllRaw(sprintf($sql, $reviewer_email));
            } else {
                $results = \myConf\Utils\DB::FetchAllRaw(sprintf($sql, $reviewer_email, $conference_id));
            }
            // Remove table names from result set (array).
            foreach($results as &$record) {
                foreach ($record as $key => $val) {
                    if (strpos($key, '.') !== FALSE) {
                        explode('.', $key)[1] = $val;
                        unset($record[$key]);
                    }
                }
            }
            return $results;
        }
        
        public function getTaskCount(int $conferenceId, string $reviewerEmail, int $paperStatus = -1)
        {
            $paperStatusWhereClause = '';
            if ($paperStatus >= 0) {
                $paperStatusWhereClause = ' AND myconf_paper_review.review_status = ?';
            }
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $sqlStr = " SELECT
                        COUNT(1) AS count
                    FROM
                        myconf_paper_review, myconf_papers
                    WHERE
                        myconf_papers.paper_logic_id = myconf_paper_review.paper_id
                    AND
                        myconf_papers.paper_version = myconf_paper_review.paper_version
                    AND myconf_papers.conference_id = ?
                    $paperStatusWhereClause
                    AND myconf_paper_review.reviewer_email = ?";
            $ret = $this->tables()->Papers->sqlFetchFirst($sqlStr,
                                                             array($conferenceId, $paperStatus, $reviewerEmail));
            return $ret['count'];
        }
        
        public function count(int $conferenceId, string $emailRestriction = '')
        {
            // Check whether there is an email restriction.
            if (strlen($emailRestriction) > 0)
            {
                $emailRestriction = addslashes($emailRestriction);
                $emailRestrictionWhereClause = " AND myconf_paper_review.reviewer_email LIKE '%$emailRestriction%' ";
            }
            else
            {
                $emailRestrictionWhereClause = '';
            }
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $sqlStr = ' SELECT
                        COUNT(DISTINCT myconf_paper_review.reviewer_email) AS reviewer_count
                    FROM
                        myconf_paper_review, myconf_papers
                    WHERE
                        myconf_papers.paper_logic_id = myconf_paper_review.paper_id
                    AND
                        myconf_papers.paper_version = myconf_paper_review.paper_version
                    AND
                        myconf_papers.conference_id = ?' . $emailRestrictionWhereClause;
            $ret = $this->tables()->PaperReview->sqlFetchFirst($sqlStr,
                                                             array($conferenceId));
            return intval($ret['reviewer_count']);
        }
    
        /**
         * @param int   $conferenceId
         * @param array $paging
         *
         * @return array An array of string returned which includes emails of all reviewers.
         */
        public function get(int $conferenceId, string $emailRestriction = '', array $paging = array()) {
            // Prepare limit clause.
            if (empty($paging) === false
                && array_key_exists('start', $paging) === true
                && array_key_exists('limit', $paging) === true)
            {
                $limitClause = "LIMIT {$paging['start']}, {$paging['limit']}";
            }
            else
            {
                $limitClause = 'LIMIT 0, 10';
            }
            // Check whether there is an email restriction.
            if (strlen($emailRestriction) > 0)
            {
                $emailRestriction = addslashes($emailRestriction);
                $emailRestrictionWhereClause = " AND myconf_paper_review.reviewer_email LIKE '%$emailRestriction%' ";
            }
            else
            {
                $emailRestrictionWhereClause = '';
            }
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $sqlStr = ' SELECT
                        DISTINCT myconf_paper_review.reviewer_email AS email
                    FROM
                        myconf_paper_review, myconf_papers
                    WHERE
                        myconf_papers.paper_logic_id = myconf_paper_review.paper_id
                    AND
                        myconf_papers.paper_version = myconf_paper_review.paper_version
                    AND
                        myconf_papers.conference_id = ? ' . $emailRestrictionWhereClause . $limitClause;
            // Get the current page of list.
            $reviewers = $this->tables()->PaperReview->sqlFetchAll($sqlStr,
                                                                 array($conferenceId));
            // Get the scholar data of the reviewers
            foreach($reviewers as &$reviewer)
            {
                $scholarData = $this->tables()->Scholars->get($reviewer['email']);
                $reviewer['first_name'] = $scholarData['scholar_first_name'];
                $reviewer['last_name'] = $scholarData['scholar_last_name'];
                $reviewer['institution'] = $scholarData['scholar_institution'];
            }
            // Return merged data of email and scholar information.
            return $reviewers;
        }
    }
