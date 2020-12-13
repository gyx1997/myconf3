<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 16:59
     */

    namespace myConf\Tables;

    use \myConf\Utils\DB;

    class Users extends \myConf\BaseSingleKeyTable {

        private static $fields_extra = array('avatar', 'organization');

        public function __construct() {
            parent::__construct();
        }

        public function primaryKey() : string {
            return 'user_id';
        }

        protected function actualPrimaryKey() : string {
            return 'user_id';
        }

        public function tableName() : string {
            return DB::MakeTable('users');
        }

        /**
         * 用户注册时创建一个用户
         * @param $username
         * @param $password
         * @param $email
         * @param $salt
         * @return int
         */
        public function createNew($username, $password, $email, $salt, $role) {
            return $this->insert(
                [
                    'user_name' => $username,
                    'user_email' => $email,
                    'user_password' => $password,
                    'password_salt' => $salt,
                    'is_frozen' => 1,
                    'user_role' => $role,
                    'user_avatar' => '',
                ]
            );
        }

        /**
         * 激活ID
         * @param int $user_id
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function activate(int $user_id) {
            $this->set(strval($user_id), ['is_frozen' => 0]);
        }

        /**
         * 根据用户名获取用户信息
         * @param $username
         * @return array
         */
        public function get_by_username(string $username) : array {
            return DB::FetchFirst($this->tableName(), ['user_name' => $username]);
        }

        /**
         * 更新密码
         * @param string $user_email
         * @param string $password
         * @param string $salt
         */
        public function update_user_password_by_email(string $user_email, string $password, string $salt) : void {
            DB::Update($this->tableName(), [
                    'user_password' => $password,
                    'password_salt' => $salt,
                ], ['user_email' => $user_email]);
        }

        /**
         * 根据用户email获取用户信息
         * @param $email
         * @return array
         */
        public function get_by_email(string $email) : array {
            return DB::FetchFirst($this->tableName(), ['user_email' => $email]);
        }

        /**
         * 根据username判断用户是否存在
         * @param string $username
         * @return bool
         */
        public function exist_by_username(string $username) : bool {
            return $this->existUsingWhere(array('user_name' => $username));
        }

        /**
         * 根据电子邮件判断用户是否存在
         * @param string $email
         * @return bool
         */
        public function exist_by_email(string $email) {
            return $this->existUsingWhere(array('user_email' => $email));
        }
    }