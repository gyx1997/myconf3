<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/16
     * Time: 0:16
     */

    namespace myConf\Tables;

    use \myConf\Utils\DB;

    class ConferenceMembers extends \myConf\BaseMultiRelationTable {

        /**
         * ConferenceMembers constructor.
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function __construct() {
            parent::__construct();
        }

        /**
         * 实际主键
         * @return string
         */
        protected function actualPrimaryKey() : string {
            return 'id';
        }

        /**
         * 返回逻辑主键名。
         * @return array
         */
        public function primaryKey() : array {
            return ['user_id', 'conference_id'];
        }

        /**
         * 返回当前表名
         * @return string
         */
        public function tableName() : string {
            return DB::MakeTable('conference_members');
        }

        /**
         * 获取会议的成员。获取数据库所有满足条件的记录
         * @param $conference_id
         * @return array
         */
        public function GetUserFromConference(int $conference_id) : array {
            //NOTE:使用连表查询，写死了表明，后面需要注意
            //NOTE:过滤角色信息，请在上一层处理，因为返回的是数组展开的字符串
            /*
            $scholar_table = $this->make_table('scholars');
            $user_table = $this->make_table('users');
            $this_table = $this->table();;
            $sql = "SELECT $scholar_table.scholar_first_name AS first_name, $scholar_table.scholar_last_name AS last_name, $this_table.user_id, $this_table.conference_id, $this_table.user_role, $user_table.user_name, $user_table.user_email FROM $this_table INNER JOIN $user_table ON $user_table.user_id = $this_table.user_id INNER JOIN $scholar_table ON $scholar_table.scholar_email = $user_table.user_email WHERE $user_table.user_id = $this_table.user_id AND $this_table.conference_id = " . strval($conference_id) . " ORDER BY $this_table.id ASC";
            $data = $this->fetch_all_raw($sql);
            foreach ($data as &$item) {
                $item['user_roles'] = explode(',', $item['user_role']);
                unset($item['user_role']);
            }
            return $data;
            */
            $members = DB::FetchAll($this->tableName(), ['conference_id' => $conference_id]);
            foreach ($members as &$member) {
                unset($member['conference_id']);
                unset($member['id']);
                $member['user_roles'] = explode(',', $member['user_role']);
                unset($member['user_role']);
            }
            return $members;
        }

        public function get_user_ids_by_conference(int $conference_id) : array {

        }

        /**
         * 得到某个会议的参与人数
         * @param int $conference_id
         * @return int
         */
        public function get_conference_members_count(int $conference_id) : int {
            $sql_result = DB::FetchAllRaw('SELECT COUNT(1) FROM ' . $this->tableName() . ' WHERE conference_id = ' . strval($conference_id));
            return intval($sql_result['COUNT(1)']);
        }

        /**
         * 得到用户参与的会议列表
         * @param int $user_id
         * @param int $start
         * @param int $limit
         * @return array
         */
        public function GetConferencesByUser(int $user_id, int $start = 0, int $limit = 10) : array {
            return DB::FetchAll($this->tableName(), ['user_id' => $user_id], '', '', $start, $limit);
        }

        /**
         * 判断一个用户是否加入了这个会议。
         * @param $user_id
         * @param $conference_id
         * @return bool
         */
        public function UserJointInConference(int $user_id, int $conference_id) : bool {

            return $this->existUsingWhere(array('user_id' => $user_id, 'conference_id' => $conference_id));
        }

        /**
         * 将用户加入会议
         * @param int $user_id
         * @param int $conference_id
         */
        public function user_join_in_conference(int $user_id, int $conference_id) : void {
            DB::Insert($this->tableName(), [
                'user_id' => $user_id,
                'conference_id' => $conference_id,
                'user_role' => 'scholar',
            ]);
        }

        /**
         * 将用户移出会议
         * @param int $user_id
         * @param int $conference_id
         */
        public function RemoveUserFromConference(int $user_id, int $conference_id) : void {
            DB::Delete($this->tableName(), ['user_id' => $user_id, 'conference_id' => $conference_id]);
        }

        /**
         * 从参会者身上移除角色
         * @param int $user_id
         * @param int $conference_id
         * @param array $roles
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function set_user_roles_in_conference(int $user_id, int $conference_id, array $roles) : void {
            try {
                //使用set虽然效率较低（需要判断主键），但是避免了重新更新缓存的代码。
                $this->set([
                    'user_id' => $user_id,
                    'conference_id' => $conference_id,
                ], ['user_role' => implode(',', $roles)]);
            } catch (\myConf\Exceptions\DbCompositeKeysException $e) {
                //dummy because this exception should never be thrown.
            }
        }
    }