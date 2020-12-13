<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 22:58
     */
    
    namespace myConf\Services;
    
    /* Import of parent class. */
    use myConf\BaseService;
    use myConf\Exceptions\ConferenceNotFoundException;
    use myConf\Exceptions\PaperSessionNotExistsException;
    
    
    /* Import sub services. */
    use myConf\Services\Conference\Members;
    use myConf\Services\Conference\Category;
    
    /* Imports of utility classes. */
    
    use myConf\Services\Conference\Papers;
    use myConf\Services\Conference\SuggestedSession;
    use myConf\Utils\Arguments;
    /* Imports for error handler. */
    use myConf\Errors\Services\Services as E_SERVICE;
    use myConf\Errors;
    
    class Conference extends BaseService
    {
        
        /* Functions of sub services. */
        
        /**
         * Get the instance of service Category.
         * @return Category Returns the instance of class Category.
         */
        public function category()
        {
            if (!isset($this->subServices['category']))
            {
                $this->subServices['category'] = new Category();
            }
            return $this->subServices['category'];
        }
        
        /**
         * Get the instance of service Members.
         * @return Members
         */
        public function member()
        {
            if (!isset($this->subServices['member']))
            {
                $this->subServices['member'] = new Members();
            }
            return $this->subServices['member'];
        }
        
        /**
         * Get the instance of sub-service Papers.
         * @return Papers
         */
        public function paper()
        {
            if (!isset($this->subServices['paper']))
            {
                $this->subServices['paper'] = new Papers();
            }
            return $this->subServices['paper'];
        }
    
        /**
         * Get the instance of sub-service SuggestedSessions.
         * @return SuggestedSession
         */
        public function session()
        {
            if (!isset($this->subServices['session']))
            {
                $this->subServices['session'] = new SuggestedSession();
            }
            return $this->subServices['session'];
        }
        
        /* End of sub services. */
        
        /**
         * @todo implement the function to add a new conference to system.
         */
        public function addConference()
        {
        
        }
        
        /**
         * 初始化Conference控制器时得到Conference信息
         * @param string $conference_url
         * @return array|false
         * @throws \myConf\Exceptions\ConferenceNotFoundException
         */
        public function loadFromUrl(string $conference_url)
        {
            // Get conference data from given url.
            $conf = $this->Models->conference()->getByUrl($conference_url);
            if (empty($conf) === true) {
                Errors::setError(E_SERVICE::E_CONFERENCE_NOT_EXISTS, 404,
                    E_SERVICE::errorMessage[E_SERVICE::E_CONFERENCE_NOT_EXISTS], 'The requested conference does not exist.');
                return false;
            }
            return $conf;
        }
        
        /**
         * 根据会议的ID号得到会议信息
         * @param int $conference_id
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\ConferenceNotFoundException
         */
        public function loadFromId(int $conference_id)
        {
            $conf = $this->Models->conference()->GetById((strval($conference_id)));
            if (empty($conf)) {
                Errors::setError(E_SERVICE::E_CONFERENCE_NOT_EXISTS, 404);
                return false;
            }
            return $conf;
        }
        
        /**
         * 判断指定用户是否加入了会议
         * @param int $user_id
         * @param int $conference_id
         * @return bool
         */
        public function userJointIn(int $user_id, int $conference_id): bool
        {
            return $this->Models->conference()->member()->jointIn($conference_id, $user_id);
        }
    
        /**
         * @param $adminUid
         * @param $conferenceTitle
         * @param $conferenceUrl
         * @param $conferenceStartTime
         *
         * @return int
         */
        public function new($adminUid, $conferenceTitle, $conferenceUrl, $conferenceStartTime) : int
        {
            $this->Models->Conference->new([], $adminUid);
            return 0;
        }
        
        
        
        /**
         * 更新会议基本信息
         *
         * @param int    $id
         * @param string $title
         * @param string $host
         * @param string $date
         * @param bool   $usePaperSubmission
         * @param string $submit_end_date
         * @param string $bannerFieldName
         *
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\ConferenceNotFoundException
         * @throws \myConf\Exceptions\UpdateConferenceException
         */
        public function updateConference(int $id,
                                         string $title,
                                         string $host,
                                         int $date,
                                         bool $usePaperSubmission,
                                         int $submit_end_date,
                                         string $bannerFieldName = '')
        {
            $data_old = $this->Models->Conference->GetById($id);
            if (empty($data_old)) {
                Errors::setError(E_SERVICE::E_CONFERENCE_NOT_EXISTS, 404);
                return false;
            }
            // Upload banner image file.
            $bannerSuccess = true;
            $bannerImageFilename = $data_old['conference_banner_image'];
            try
            {
                $bannerImageData = \myConf\Utils\Attach::parse($bannerFieldName);
                $bannerImageFilename = $bannerImageData['full_name'];
                // Delete old banner image file.
                @unlink(ATTACHMENT_DIR . $data_old['conference_banner_image']);
            }
            catch (\myConf\Exceptions\FileUploadException $e)
            {
                $bannerSuccess = false;
            }
            // Update conference data.
            $this->Models->Conference->update_conference($id, $title, $date, $bannerImageFilename, $data_old['conference_qr_code'], $host, $usePaperSubmission, $submit_end_date);
            // Return operation status.
            if ($bannerSuccess === false)
            {
                Errors::setError(E_SERVICE::E_BANNER_UPLOAD_FAILED, 200,
                                 E_SERVICE::errorMessage[E_SERVICE::E_BANNER_UPLOAD_FAILED], 'Banner image upload failed. Maybe file unselected.');
                return false;
            }
            return true;
        }
        
        /**
         * @param int $session_id
         * @throws PaperSessionNotExistsException
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbTransactionException
         */
        public function moveDownSession(int $session_id) : void {
            if ($this->Models->PaperSession->exist($session_id) === false){
                throw new PaperSessionNotExistsException('CONF_SESS_NOT_EXISTS', 'The requested session of does not exists, or it does not belong to this conference.');
            }
            $this->Models->PaperSession->move_down($session_id);
        }
        
        /**
         * @param int $session_id
         * @throws PaperSessionNotExistsException
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbTransactionException
         */
        public function moveUpSession(int $session_id) : void {
            if ($this->Models->PaperSession->exist($session_id) === false){
                throw new PaperSessionNotExistsException('CONF_SESS_NOT_EXISTS', 'The requested session of does not exists, or it does not belong to this conference.');
            }
            $this->Models->PaperSession->move_up($session_id);
        }
        
        /**
         * @param int $session_id
         * @param int $session_type
         * @param string $session_text
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function updateSession(int $session_id, int $session_type, string $session_text) : void {
            //最后一个参数-1，不更新它的display_order
            $this->Models->PaperSession->update_session($session_id, $session_type, $session_text, -1);
        }
        
        /**
         * @param int $session_id
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\PaperSessionAlreadyUsedException
         */
        public function deleteSession(int $session_id) : void {
            $this->Models->PaperSession->delete_session($session_id);
        }
        
        
        
        /**
         * @param int $conference_id
         * @param array $roles_restrict
         * @param string $name_restrict
         * @param string $email_restrict
         * @return array
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function getMembers(int $conference_id, array $roles_restrict = array(), string $name_restrict = '', string $email_restrict = '') : array {
            //先定义返回结果集
            $membersDataSet = array();
            $members = $this->Models->Conference->getMembers($conference_id);
            $rolesRestrictEmpty = empty($roles_restrict);
            foreach ($members as $member) {
                //过滤信息
                if ($name_restrict !== '' && strpos($member['user_name'], $name_restrict) === false) {
                    continue;
                }
                if ($email_restrict !== '' && strpos($member['user_email'], $email_restrict) === false) {
                    continue;
                }
                $continue = false;
                //如果角色约束不为空，则检查是否符合角色要求
                if ($rolesRestrictEmpty == false) {
                    foreach ($roles_restrict as $role) {
                        if (in_array($role, $member['user_role']) == false) {
                            $continue = true;
                            break;
                        }
                    }
                    if ($continue == true) {
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
        public function memberIsRole(int $conference_id, int $user_id, string $role) {
            return in_array($role, $this->Models->conference()->member()->getRoles($conference_id, $user_id));
        }
        
        /**
         * 将用户添加某个角色
         * @param int $conference_id
         * @param int $user_id
         * @param string $role
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function AddRoleToMember(int $conference_id, int $user_id, string $role) : void {
            $roles = $this->Models->conference()->member()->getRoles($conference_id, $user_id);
            //先判断是否已经存在这个角色，如果存在就不用进行数据库操作了。
            //否则，增加额外的数据库操作，且缓存也失效了。
            //下面的member_remove_role同理
            if (!in_array($role, $roles)) {
                $roles [] = $role;
                $this->Models->conference()->member()->setRoles($conference_id, $user_id, $roles);
            }
        }
        
        public function toggleRoleOfMember() {
            //Initialization of variables.
            $conferenceId = Arguments::getFuncArg('conference_id');
            $userId = Arguments::getHttpArg('uid');
            $roleName = Arguments::getHttpArg('role');
            
            if (is_null($userId) || is_null($roleName)) {
            
            }
        }
        
        /**
         * 将用户移除某个角色
         * @param int $conference_id
         * @param int $user_id
         * @param string $role
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function RemoveRoleFromMember(int $conference_id, int $user_id, string $role) {
            //Get roles of given conference member.
            $roles = $this->Models->conference()->member()->getRoles($conference_id, $user_id);
            
            //If the current user does not exist, return directly.
            if (empty($roles)) {
                return $this->getResultArray(200, 'USER_NOT_JOINT_IN_CONFERENCE');
            }
            
            $roles_new = array();
            foreach ($roles as $r) {
                $r !== $role && $roles_new [] = $r;
            }
            
            if (!empty(array_diff($roles, $roles_new))) {
                $this->Models->conference()->member()->setRoles($conference_id, $user_id, $roles_new);
            }
            
            return $this->getResultArray(200, 'SUCCESS');
        }
        
        /**
         * @param int $conference_id
         * @param int $user_id
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbCompositeKeysException
         */
        public function RemoveMemberFromConference(int $conference_id, int $user_id) : void {
            $this->Models->conference()->member()->remove($conference_id, $user_id);
        }
        
        /**
         * @param int $conference_id
         * @param int $user_id
         */
        public function AddMemberToConference(int $conference_id, int $user_id) : void {
            $this->Models->conference()->member()->add($conference_id, $user_id);
        }
        
        /**
         * @param int $conferenceId
         *
         * @return array
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function getSessions(int $conferenceId) : array {
            $sessions = $this->Models->conference()->session()->getAll($conferenceId);
            $sessions_dispatched = [];
            foreach ($sessions as $session){
                $sessions_dispatched[intval($session['session_type'])] []= $session;
            }
            return $sessions_dispatched;
        }
        
        public function getSessionsUndispatched($conferenceId) {
            return $this->Models->conference()->session()->getAll($conferenceId);
        }
        
        public function editorViewPaperList() {
            $conferenceId = Arguments::getFuncArg('conference_id');
            
        }
        
        /**
         * @param int $conferenceId
         * @param string $sessionText
         * @param int $sessionType
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function AddNewSessionToConference(int $conferenceId,
                                                  string $sessionText,
                                                  int $sessionType) {
            // Since the conference id is got from global variable list, which
            // has already been verified in the constructor of controller
            // Conferences. So the existence of the conference does not need
            // to be checked.
            // However, the empty session and the duplicate session need to
            // be checked further.
            return $this->Models->PaperSession->add_session($conferenceId, $sessionText, $sessionType);
        }
        
        /**
         * 会议未找到时的异常
         * @return ConferenceNotFoundException
         */
        private static function exceptionConferenceNotFound(): \myConf\Exceptions\ConferenceNotFoundException
        {
            return new \myConf\Exceptions\ConferenceNotFoundException('CONF_NOT_FOUND', 'The requested conference does not exists, or has been renamed or deleted.');
        }
        
        /**
         * 栏目没找到时的异常
         * @return \myConf\Exceptions\CategoryNotFoundException
         */
        private static function exceptionCategoryNotFound(): \myConf\Exceptions\CategoryNotFoundException
        {
            return new \myConf\Exceptions\CategoryNotFoundException('CAT_NOT_FOUND', 'The request category does not exists, or has been deleted.');
        }
        
    }