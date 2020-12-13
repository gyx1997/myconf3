<?php


namespace myConf\Models;


use myConf\BaseModel;

class ConferenceMember extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function getFromConference(int $conference_id) : array {
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
     * @param int $conferenceId
     * @param int $userId
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function remove(int $conferenceId, int $userId) : void {
        $this->tables()->ConferenceMembers->delete(['conference_id' => $conferenceId, 'user_id' => $userId]);
    }

    /**
     * @param int $conference_id
     * @param int $user_id
     */
    public function add(int $conference_id, int $user_id) : void {
        $this->tables()->ConferenceMembers->insert([
            'conference_id' => $conference_id,
            'user_id' => $user_id,
            'user_role' => 'scholar',
        ]);
    }


    /**
     * 判断用户是否加入了某个会议
     * @param int $conference_id
     * @param int $user_id
     * @return bool
     */
    public function jointIn(int $conference_id, int $user_id) : bool {
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
    public function getRoles(int $conference_id, int $user_id) : array {
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
    public function setRoles(int $conference_id, int $user_id, array $roles = array()) : void {
        $this->tables()->ConferenceMembers->set([
            'conference_id' => $conference_id,
            'user_id' => $user_id,
        ], ['user_role' => implode(',', $roles)]);
    }

}