<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 22:57
 */

namespace myConf\Models;

use myConf\Exceptions\DbTransactionException;
use myConf\Models\Fields\ConferenceMembers;

/**
 * Class Conferences
 * @package myConf\Models
 * @author _g63<522975334@qq.com>
 * @version 2019.1
 *
 */
class Conference extends \myConf\BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    private $modelCategory = null;

    public function category() : Category {
        if (is_null($this->modelCategory) === true) {
            $this->modelCategory = new Category();
        }
        return $this->modelCategory;
    }

    private $modelPapers = null;

    public function paper() : Papers {
        if (is_null($this->modelPapers) === true) {
            $this->modelPapers = new Papers();
        }
        return $this->modelPapers;
    }

    private $modelMembers = null;

    public function member() : ConferenceMember {
        if (is_null($this->modelMembers) === true) {
            $this->modelMembers = new ConferenceMember();
        }
        return $this->modelMembers;
    }

    private $modelSessions = null;

    public function session() : PaperSession {
        if (is_null($this->modelSessions) === true) {
            $this->modelSessions = new PaperSession();
        }
        return $this->modelSessions;
    }
    
    private $modelReviewer = null;

    public function reviewer() : Reviewer {
        if (is_null($this->modelReviewer)) {
            $this->modelReviewer = new Reviewer();
        }
        return $this->modelReviewer;
    }

    private function newConferenceDataChecker(array $data, int $admin) : bool
    {
        //首先需要其非空
        if (empty($data))
        {
            return false;
        }
        //检查关键字段
        if (isset($data[\myConf\Models\Fields\Conference::Name]) === false)
        {
            return false;
        }
        if (isset($data[\myConf\Models\Fields\Conference::Url]) === false)
        {
            return false;
        }
        if (isset($data[\myConf\Models\Fields\Conference::StartTime]) === false)
        {
            return false;
        }
        //检查管理员
        if ($this->tables()->Users->exist(strval($admin)) === false)
        {
            return false;
        }
        return true;
    }

    /**
     * 新建一个会议
     * @param array $conferenceData
     * @return int
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbException
     */
    public function new(array $conferenceData, int $conferenceAdmin) : int {
        $ret = 0;
        if ($this->newConferenceDataChecker($conferenceData, $conferenceAdmin) === true) {
            try {
                \myConf\Utils\DB::transBegin();
                $ret = $this->tables()->Conferences->insert($conferenceData);
                $this->tables()->ConferenceMembers->insert([
                    \myConf\Models\Fields\ConferenceMembers::ConferenceId => $ret,
                    \myConf\Models\Fields\ConferenceMembers::UserId => $conferenceAdmin,
                    \myConf\Models\Fields\ConferenceMembers::Roles => ['admin', 'editor', 'scholar'],
                ]);
                //添加一个默认分类
                $catId = $this->tables()->Categories->insert([
                    \myConf\Models\Fields\ConferenceCategory::ConferenceId => $ret,
                    \myConf\Models\Fields\ConferenceCategory::DisplayOrder => 0,
                    \myConf\Models\Fields\ConferenceCategory::Title => 'Default Category',
                    \myConf\Models\Fields\ConferenceCategory::Type => 0,
                ]);
                //给默认分类添加一篇文章
                $this->tables()->Documents->insert([
                    \myConf\Models\Fields\ConferenceDocument::CategoryId => $catId,
                    \myConf\Models\Fields\ConferenceDocument::Title => 'Default Document',
                    \myConf\Models\Fields\ConferenceDocument::HtmlText => '',
                ]);
                \myConf\Utils\DB::transEnd();
            } catch(DbTransactionException $e) {
                $ret = 0;
            }
        }
        return $ret;
    }

    public function delete() : void
    {

    }

    public function getList()
    {

    }

    /**
     * @param int $conference_id
     * @param string $conference_name
     * @param int $conference_start_time
     * @param string $conference_banner
     * @param string $conference_qr_code
     * @param string $conference_host
     * @param bool $use_paper_submission
     * @param int $paper_submission_deadline
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function update_conference(int $conference_id, $conference_name = '', $conference_start_time = 0,
                                      $conference_banner = '',
                                      $conference_qr_code = '', $conference_host = '', $use_paper_submission = true, $paper_submission_deadline = 0)
    {
        $this->tables()->Conferences->set(strval($conference_id), array(
                'conference_name' => $conference_name,
                'conference_start_time' => $conference_start_time,
                'conference_banner_image' => $conference_banner,
                'conference_qr_code' => $conference_qr_code,
                'conference_host' => $conference_host,
                'conference_use_paper_submit' => $use_paper_submission,
                'conference_paper_submit_end' => $paper_submission_deadline,
            )
        );
    }

    /**
     * 通过url获取会议信息
     * @param string $url
     * @return array
     */
    public function getByUrl(string $url) : array
    {
        return $this->tables()->Conferences->fetchFirst(['conference_url' => $url]);
    }

    /**
     * 通过id获取会议信息
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function GetById(int $conference_id) : array {
        return $this->tables()->Conferences->get(strval($conference_id));
    }

    /**
     * 判断用户是否加入了某个会议
     * @param int $conference_id
     * @param int $user_id
     * @return bool
     */
    public function UserJointIn(int $conference_id, int $user_id) : bool {
        return $this->tables()->ConferenceMembers->UserJointInConference($user_id, $conference_id);
    }

    /**
     * 得到用户角色
     * @param int $conference_id
     * @param int $user_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function GetUserRoles(int $conference_id, int $user_id) : array {
        $user = $this->tables()->ConferenceMembers->get(['user_id' => $user_id, 'conference_id' => $conference_id]);
        return explode(',', $user['user_role']);
    }

    /**
     * 设置用户的角色。
     * @param int $conference_id
     * @param int $user_id
     * @param array $roles
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function SetUserRoles(int $conference_id, int $user_id, array $roles = array()) : void {
        $this->tables()->ConferenceMembers->set([
            'conference_id' => $conference_id,
            'user_id' => $user_id,
        ], ['user_role' => implode(',', $roles)]);
    }

    /**
     * 得到Home页面的所有栏目列表
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function GetCategories(int $conference_id) : array {
        $result = array();
        $ids = $this->tables()->Categories->get_ids_by_conference($conference_id);
        foreach ($ids as $id) {
            $result [] = $this->tables()->Categories->get(strval($id));
        }
        return $result;
    }

    /**
     * 得到某个会议的第一个栏目
     * @param int $conference_id
     * @return array
     */
    public function GetFirstCategories(int $conference_id) : array {
        return $this->tables()->Categories->fetchFirst(array('conference_id' => $conference_id), 'category_display_order', 'ASC');
    }

    /**
     * 返回当前的会议是否存在
     * @param int $conference_id
     * @return bool
     */
    public function exists(int $conference_id) : bool {
        return $this->tables()->Conferences->exist(strval($conference_id));
    }

    /**
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function getMembers(int $conference_id) : array {
        $members = $this->tables()->ConferenceMembers->GetUserFromConference($conference_id);
        foreach ($members as &$member) {
            $account_data = $this->tables()->Users->get($member['user_id']);
            $member['user_email'] = $account_data['user_email'];
            $member['user_name'] = $account_data['user_name'];
            $scholar_data = $this->tables()->Scholars->get($member['user_email']);
            $member['first_name'] = $scholar_data['scholar_first_name'];
            $member['last_name'] = $scholar_data['scholar_last_name'];
            $member['prefix'] = $scholar_data['scholar_prefix'];
            $member['institution'] = $scholar_data['scholar_institution'];
            $member['department'] = $scholar_data['scholar_department'];
        }
        return $members;
    }

    /**
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function GetSessions(int $conference_id) : array {
        $data = [];
        $ids = $this->tables()->PaperSessions->get_conference_sessions($conference_id);
        foreach($ids as $id) {
            $data []= $this->tables()->PaperSessions->get($id);
        }
        return $data;
    }
    

}