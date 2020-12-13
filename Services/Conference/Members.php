<?php


namespace myConf\Services\Conference;


use myConf\BaseService;
use myConf\Errors as err;
use myConf\Errors\Services\Services as errService;

class Members extends BaseService
{
    /**
     * 获取用户成员角色
     * @param int $user_id
     * @param int $conference_id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function getRoles(int $user_id, int $conference_id): array
    {
        return $this->Models->conference()->member()->getRoles($conference_id, $user_id);
    }

    /**
     * @param int $conference_id
     * @param array $roles_restrict
     * @param string $name_restrict
     * @param string $email_restrict
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function getAll(int $conference_id, array $roles_restrict = array(), string $email_restrict = '') : array {
        //先定义返回结果集
        $membersDataSet = array();
        $members = $this->Models->Conference->getMembers($conference_id);
        $rolesRestrictEmpty = empty($roles_restrict);
        foreach ($members as $member)
        {
            if ($email_restrict !== '' && strpos($member['user_email'], $email_restrict) === false)
            {
                continue;
            }
            $continue = false;
            //如果角色约束不为空，则检查是否符合角色要求
            if ($rolesRestrictEmpty == false)
            {
                foreach ($roles_restrict as $role)
                {
                    if (in_array($role, $member['user_role']) == false)
                    {
                        $continue = true;
                        break;
                    }
                }
                if ($continue == true)
                {
                    continue;
                }
            }
            $membersDataSet [] = $member;
        }
        return $membersDataSet;
    }

    /**
     * 判断用户是否是某一个角色
     * @param int $conference_id
     * @param int $user_id
     * @param string $role
     * @return bool
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function isRole(int $conference_id, int $user_id, string $role) {
        return in_array($role, $this->Models->conference()->member()->getRoles($conference_id, $user_id));
    }

    public function addRole(int $conference_id, int $user_id, string $role) {
        $roles = $this->Models->conference()->member()->getRoles($conference_id, $user_id);
        //先判断是否已经存在这个角色，如果存在就不用进行数据库操作了。
        //否则，增加额外的数据库操作，且缓存也失效了。
        //下面的member_remove_role同理
        if (!in_array($role, $roles)) {
            $roles [] = $role;
            $this->Models->conference()->member()->setRoles($conference_id, $user_id, $roles);
        }
        return true;
    }

    /**
     * 将用户移除某个角色
     * @param int $conference_id
     * @param int $user_id
     * @param string $role
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function removeRole(int $conference_id, int $user_id, string $role)
    {
        /* Get roles of given conference member. */
        $roles = $this->Models->conference()->member()->getRoles($conference_id, $user_id);
        /* If the current user has not joint in the conference, return with an error. */
        if (empty($roles))
        {
            err::setError(errService::E_USER_NOT_JOINT_IN_CONFERENCE);
            return false;
        }
        $roles_new = array();
        foreach ($roles as $r)
        {
            $r !== $role && $roles_new [] = $r;
        }
        if (!empty(array_diff($roles, $roles_new)))
        {
            $this->Models->conference()->member()->setRoles($conference_id, $user_id, $roles_new);
        }
        return true;
    }

    /**
     * @param int $conferenceId
     * @param int $userId
     *
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbCompositeKeysException
     */
    public function remove(int $conferenceId, int $userId) : void {
        $this->models()->conference()->member()->remove($conferenceId, $userId);
    }
}