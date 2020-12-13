<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/24
 * Time: 13:48
 */

namespace myConf\Models;

use myConf\Models\Conferences\Papers\Reviewers;
use myConf\Utils\DB;

/**
 * Class Paper
 * @package myConf\Models
 * @author _g63<522975334@qq.com>
 * @version 2019.1
 */
class Papers extends \myConf\BaseModel
{

    private $modelReviewers = null;

    public function reviewers() : Reviewers
    {
        if (is_null($this->modelReviewers))
        {
            $this->modelReviewers = new Reviewers();
        }
        return $this->modelReviewers;
    }

    //下面是paper_status的取值列表
    public const paper_status_submitted = 0;
    public const paper_status_under_review = 1;
    public const paper_status_passed = 2;
    public const paper_status_rejected = 3;
    public const paper_status_revision = 4;
    public const paper_status_saved = -1;
    //下面是paper session的取值列表
    public const paper_session_type_internal = 0;
    public const paper_session_type_custom = 1;
    //tinyint 类型，2~255可供自定义使用
    public const paper_session_type_student_paper = 2;

    /**
     * Paper constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Finish the review of a given paper.
     * @param int    $paper_id  Paper's logic identifier.
     * @param int    $paper_version Paper's version.
     * @param int    $paper_status Paper's status which should be one of the following constants : Accepted, Revision and Rejected.
     * @param string $paper_comments The editor's review comments for the paper.
     */
    public function finishReview(int $paper_id, int $paper_version, int $paper_status, string $paper_comments = '') : void {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->tables()->Papers->set(
            array('paper_logic_id' => strval($paper_id), 'paper_version' => $paper_version),
            array('paper_status' => $paper_status, 'paper_comments' => $paper_comments)
        );
    }
    
    /**
     * @param int    $user_id
     * @param int    $conference_id
     * @param array  $authors
     * @param int    $pdf_aid
     * @param int    $copyright_aid
     * @param string $paper_type
     * @param string $suggested_session
     * @param string $title
     * @param string $abstract
     * @param int    $status
     * @param string $custom_suggested_session
     * @param int    $specified_paper_id
     * @param int    $specified_paper_version
     *
     * @return bool|int
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function add(int $user_id, int $conference_id, array $authors, int
    $pdf_aid, int $copyright_aid, string $paper_type, string
    $suggested_session, string $title, string $abstract, int $status, string
    $custom_suggested_session = '', int $specified_paper_id = 0, int
    $specified_paper_version =
    0) {
        // Data needed to be read from database is out of the transaction block to avoid deadlock.
        $session = $this->tables()->PaperSessions->get(strval(intval($suggested_session)));
        $session_display_order_max = $this->tables()->PaperSessions->get_conference_sessions_max_display_order($conference_id);
        // specifiedPaperId = 0 means add an new paper.
        if ($specified_paper_id === 0) {
            $paper_logic_id = $this->tables()->Papers->GetNewPaperLogicId();
            $paper_version = 1;
        } else {
            $paper_logic_id = $specified_paper_id;
            $paper_version = $specified_paper_version;
        }
        
        /* Start of transaction. */
        DB::transBegin();



        //如果不存在的话，写入session信息
        if (empty($session) && intval($suggested_session) !== -2) {
            $session_id = $this->tables()->PaperSessions->insert([
                'session_conference_id' => $conference_id,
                'session_text' => $custom_suggested_session,
                'session_type' => self::paper_session_type_custom,
                'session_display_order' => $session_display_order_max + 1
            ]);
            $suggested_session = $session_id;
        }
        //插入paper信息
        $paper_id = $this->tables()->Papers->insert([
            'paper_logic_id' => $paper_logic_id,
            'paper_version' => $paper_version,
            'user_id' => $user_id,
            'conference_id' => $conference_id,
            'pdf_attachment_id' => $pdf_aid,
            'copyright_attachment_id' => $copyright_aid,
            'paper_submit_time' => time(),
            'paper_status' => $status,
            'paper_type' => $paper_type,
            'paper_suggested_session' => $suggested_session,
            'paper_title' => $title,
            'paper_abstract' => $abstract,
        ]);
        //插入作者信息（如果填写了的话）
        !empty($authors) && $this->tables()->PaperAuthors->insertArray($this->parseAuthors($paper_id, $authors));
        //原子性操作，修改attachment_tag_id，即attachment的关联的表的id
        if ($pdf_aid !== 0) {
            $this->tables()->Attachments->set($pdf_aid, [
                'attachment_tag_id' => $paper_id,
                'attachment_tag_type' => \myConf\Tables\Attachments::tag_type_paper,
                'attachment_used' => 1,
            ]);
        }

        if ($copyright_aid !== 0)
        {
            $this->tables()->Attachments->set($copyright_aid,
                                            array('attachment_tag_id' => $paper_id,
                                                  'attachment_tag_type' => \myConf\Tables\Attachments::tag_type_paper,
                                                  'attachment_used' => 1
                                            ));
        }

        /* End of transaction. */
        $transSucceeded = DB::transEnd();

        if ($transSucceeded === false)
        {
            return false;
        }

        return $paper_id;
    }

    /**
     * 根据会议ID和用户ID获取当前某个会议某个用户的所有文章
     * @param int $conference_id
     * @param int $user_id
     * @return array
     */
    public function GetPapersFromConferenceByUserId(int $conference_id, int $user_id): array
    {
        //$this->tables()->Papers->SqlFetchAll('SELECT * FROM `' . $this->tables()->Papers->Table() .  '` WHERE ' . \myConf\Models\Fields\Paper::Status . ' >= -1 AND', []);
        $papers = $this->tables()->Papers->fetchAll(['conference_id' => $conference_id, 'user_id' => $user_id]);
        $papersNew = [];
        foreach ($papers as &$paper) {
            $authors = $this->tables()->PaperAuthors->fetchAll(['paper_id' => $paper['paper_id']]);
            $paper['authors'] = $authors;
            if ($paper[\myConf\Models\Fields\Paper::Status] != \myConf\Models\Constants\PaperStatus::TrashBox) {
                $papersNew [] = $paper;
            }
        }
        return $papersNew;
    }
    
    public function getMaxVersionOfPaper(int $paperLogicId) : int
    {
        // Get the table name.
        $tableName = $this->tables()->Papers->tableName();
        // Get the string value of paper logic id.
        $paperLogicId = strval($paperLogicId);
        // Get the max version.
        $sqlResult = $this->tables()
            ->Papers
            ->sqlFetchFirst("SELECT MAX(paper_version) FROM $tableName WHERE `paper_logic_id` = $paperLogicId", array());
        if (empty($sqlResult))
        {
            return -1;
        }
        return $sqlResult['MAX(paper_version)'];
    }

    /**
     * 根据文章的逻辑编号和版本号得到文章内容
     * @param int $paper_logic_id
     * @param int $paper_version
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function getContent(int $paper_logic_id, int $paper_version): array
    {
        $paper_base_data = $this->tables()->Papers->get(['paper_logic_id' => $paper_logic_id, 'paper_version' => $paper_version]);
        if (empty($paper_base_data)) {
            return [];
        }
        $paper_authors = $this->tables()->PaperAuthors->fetchAll(['paper_id' => $paper_base_data['paper_id']]);
        $paper_base_data['authors'] = $paper_authors;
        $paper_base_data['content_attach_info'] = (intval($paper_base_data['pdf_attachment_id']) === 0 ? null : $this->tables()->Attachments->get(strval($paper_base_data['pdf_attachment_id'])));
        $paper_base_data['copyright_attach_info'] = (intval($paper_base_data['copyright_attachment_id']) === 0 ? null : $this->tables()->Attachments->get(strval($paper_base_data['copyright_attachment_id'])));
        return $paper_base_data;
    }

    /**
     * @param int $logicId
     * @param int $version
     * @return bool
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function exists(int $logicId, int $version) : bool {
        return $this->tables()->Papers->exist(['paper_logic_id' => $logicId, 'paper_version' => $version]);
    }

    /**
     * @param int $paper_logic_id
     * @param int $paper_version
     * @param array|null $authors
     * @param array|null $pdf_file_info
     * @param array|null $copyright_file_info
     * @param string|null $paper_type
     * @param string|null $suggested_session
     * @param string|null $title
     * @param string|null $abstract
     * @param string|null $custom_suggested_session
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function Update(int $paper_logic_id, int $paper_version, int $paper_status, array $authors = null, int $paper_aid = 0, int $copyright_aid = 0, string $paper_type = null, string $suggested_session = null, string $title = null, string $abstract = null, string $custom_suggested_session = null): void
    {
        //先获取旧的信息，进行比对，如果有出入则进行修改
        $old_data = $this->getContent($paper_logic_id, $paper_version);
        $base_data_to_update = [];
        //获取session信息(如果必要的话)
        if (isset($suggested_session)) {
            $suggested_session = intval($suggested_session);
            $session = $this->tables()->PaperSessions->get(strval($suggested_session));
            $session_display_order_max = $this->tables()->PaperSessions->get_conference_sessions_max_display_order($old_data['conference_id']);
        }
        //下面开始事务修改信息
        DB::transBegin();
        //如果上传了新的paper，那么修改相关信息
        if ($paper_aid !== 0 && $paper_aid !== intval($old_data['pdf_attachment_id'])) {
            $this->tables()->Attachments->set($paper_aid, [
                'attachment_tag_id' => $old_data['paper_id'],
                'attachment_tag_type' => \myConf\Tables\Attachments::tag_type_paper,
                'attachment_used' => 1,
            ]);
            $base_data_to_update['pdf_attachment_id'] = $paper_aid;
        }
        //如果上传了新的copyright，修改相关信息
        if ($copyright_aid !== 0 && $copyright_aid !== intval($old_data['copyright_attachment_id'])) {
            $this->tables()->Attachments->set($copyright_aid, [
                'attachment_tag_id' => $old_data['paper_id'],
                'attachment_tag_type' => \myConf\Tables\Attachments::tag_type_paper,
                'attachment_used' => 1
            ]);
            $base_data_to_update['copyright_attachment_id'] = $copyright_aid;
        }
        //如果不存在的话，写入session信息
        if (empty($session) && intval($suggested_session) !== -2) {
            $session_id = $this->tables()->PaperSessions->insert([
                'session_conference_id' => $old_data['conference_id'],
                'session_text' => $custom_suggested_session,
                'session_type' => self::paper_session_type_custom,
                'session_display_order' => $session_display_order_max + 1,
            ]);
            $suggested_session = $session_id;
        }
        //下面开始处理作者信息
        if (isset($authors) && !empty($authors)) {
            //先清除旧的信息
            foreach ($old_data['authors'] as $current_author) {
                $this->tables()->PaperAuthors->delete($current_author['author_id']);
            }
            //再插入新的数据
            $this->tables()->PaperAuthors->insertArray($this->parseAuthors($old_data['paper_id'], $authors));
        }
        //其他的一些信息
        isset($paper_type) && $base_data_to_update['paper_type'] = $paper_type;
        isset($suggested_session) && $base_data_to_update['paper_suggested_session'] = $suggested_session;
        isset($title) && $base_data_to_update['paper_title'] = $title;
        isset($abstract) && $base_data_to_update['paper_abstract'] = $abstract;
        $base_data_to_update['paper_status'] = $paper_status;
        $this->tables()->Papers->set([
                'paper_logic_id' => $paper_logic_id,
                'paper_version' => $paper_version
            ], $base_data_to_update
        );
        DB::transEnd();
    }

    public function add_new_version(int $paper_logic_id, int $current_version, int $user_id, int $conference_id, array $authors, array $pdf_file_info, array $copyright_file_info, string $paper_type, string $suggested_session, string $title, string $abstract, int $paper_status = 0, string $custom_suggested_session = '') : int {

    }

    /**
     * Move a specified paper to trash box which makes it invisible.
     * @param int $paperLogicId
     * @param int $paperVersion
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function MovePaperToTrashBox(int $paperLogicId, int $paperVersion) : void {
        $this->tables()->Papers->set([
                'paper_logic_id' => $paperLogicId,
                'paper_version' => $paperVersion
            ], ['paper_status' => Constants\PaperStatus::TrashBox]
        );
    }

    /**
     * @param int $paper_logic_id
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function delete(int $paper_logic_id, int $paper_version): void
    {
        $paper = $this->tables()->Papers->get(['paper_logic_id' => $paper_logic_id, 'paper_version' => $paper_version]);
        $authors = $this->tables()->PaperAuthors->fetchAll(['paper_id' => $paper_logic_id]);
        intval($paper['paper_suggested_session']) !== -2 && $paper_session = $this->tables()->PaperSessions->get($paper['paper_suggested_session']);
        DB::transBegin();
        //如果session是自己添加的，且没有人用了，那么需要删除之
        /*/
        if ($paper_session['session_type'] === self::paper_session_type_custom) {
            //TODO 没有进行检测是否有人使用!
            $this->tables()->PaperSessions->delete($paper_session['session_id']);
        }
        */
        //删除所有作者信息
        foreach($authors as $author) {
            $this->tables()->PaperAuthors->delete($author['author_id']);
        }
        //删除paper信息
        $this->tables()->Papers->delete(['paper_logic_id' => $paper_logic_id, 'paper_version' => $paper_version]);
        //删除对应的附件信息
        intval($paper['pdf_attachment_id']) !== 0 &&
    $this->tables()->Attachments->delete
    ($paper['pdf_attachment_id']);
        intval($paper['copyright_attachment_id']) !== 0 &&
        $this->tables()->Attachments->delete($paper['copyright_attachment_id']);
        DB::transEnd();
    }

    /**
     * 从输入数组转换到数据库表数组
     * @param int $paper_id
     * @param array $authors
     * @return array
     */
    private function parseAuthors(int $paper_id, array $authors): array
    {
        $data_authors_array = [];
        $display_order = 0;
        foreach ($authors as $author) {
            $data_authors_array [] = [
                'paper_id' => $paper_id,
                'author_email' => $author['email'],
                'author_display_order' => $display_order,
                'author_address' => $author['address'],
                'author_institution' => $author['institution'],
                'author_department' => $author['department'],
                'author_first_name' => $author['first_name'],
                'author_last_name' => $author['last_name'],
                'author_chn_full_name' => $author['chn_full_name'],
                'author_prefix' => $author['prefix'],
            ];
            $display_order++;
        }
        return $data_authors_array;
    }
    
    /**
     * 得到会议的所有文章
     * @param int $conferenceId
     * @param int $start
     * @param int $count
     * @return array
     */
    public function getAll(int $conferenceId, int $paperStatus = -1, int $start = 0, int $count = 0, int $paperTopicId = -1) : array {
        //Initialization of local variables.
        $rawData = [];
        $result = [];
        //Check whether attribute 'PaperStatus' is restricted.
        if ($paperStatus >= 0)
        {
            $whereArray = ['conference_id' => $conferenceId];
            $whereArray['paper_status'] = $paperStatus;
            //Check whether attribute 'TopicId' (aka. 'SuggestedSession') is restricted.
            if ($paperTopicId >= 0) {
                $whereArray['paper_suggested_session'] = $paperTopicId;
            }
            if ($start == 0 && $count == 0) {
                $rawData = $this->tables()->Papers->fetchAll($whereArray, 'paper_logic_id', 'ASC');
            } else {
                $rawData = $this->tables()->Papers->fetchAll($whereArray, 'paper_logic_id', 'ASC', $start, $count);
            }
        }
        else
        {
            $whereStr = " WHERE conference_id = $conferenceId AND paper_status >= 0";
            if ($paperTopicId >= 0)
            {
                $whereStr .= " AND paper_suggested_session = $paperTopicId";
            }
            if ($start === 0 && $count === 0)
            {
                $limitStr = '';
            }
            else
            {
                $limitStr = "LIMIT $start, $count";
            }
            $orderByStr = 'ORDER BY paper_logic_id ASC';
            
            /** @noinspection SqlNoDataSourceInspection */
            $rawData = $this->tables()->Papers->sqlFetchAll('SELECT * FROM ' . $this->tables()->Papers->tableName() . $whereStr . ' ' . $orderByStr . ' ' . $limitStr, []);
        }
        //Get author's information for each paper which is visible.
        foreach ($rawData as &$paper) {
            if ($paper['paper_status'] >= 0) {
                $paper['authors'] = $this->tables()->PaperAuthors->fetchAll(['paper_id' => $paper['paper_id']]);
                $result [] = $paper;
            }
        }
        
        return $result;
    }
    
    /**
     * @param int $conferenceId
     * @param int $paperStatus
     * @return int
     */
    public function count(int $conferenceId,
                          int $paperStatus = -1,
                          int $paperTopicId = -1,
                          bool $groupByReviewers = false)
    {
        // Use internal function or raw SQL statement.
        if ($paperStatus >= 0)
        {
            $whereArray = ['conference_id' => $conferenceId];
            if ($paperTopicId >= 0)
            {
                $whereArray['paper_suggested_session'] = $paperTopicId;
            }
            $whereArray['paper_status'] = $paperStatus;
            return $this->tables()->Papers->count($whereArray);
        }
        else
        {
            $whereStr = " WHERE conference_id = $conferenceId AND paper_status >= 0";
            if ($paperTopicId >= 0)
            {
                $whereStr .= " AND paper_suggested_session = $paperTopicId";
            }
            $result = $this->tables()->Papers->SqlFetchFirst('SELECT COUNT(1) FROM ' . $this->tables()->Papers->tableName
                                                           () . $whereStr,
                                                           array());
            return $result['COUNT(1)'];
        }
    }
    
    private function countPaper(int $conferenceId,
                                int $paperStatus = -1,
                                int $paperTopicId = -1)
    {

    }
    
    
}