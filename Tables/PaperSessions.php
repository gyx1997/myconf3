<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/1/16
 * Time: 14:08
 */

namespace myConf\Tables;


use myConf\Utils\DB;

class PaperSessions extends \myConf\BaseSingleKeyTable
{
    /**
     * PaperSessions constructor.
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 返回逻辑主键名
     * @return string
     */
    public function primaryKey()
    {
        return 'session_id';
    }

    /**
     * 返回实际的主键名称
     * @return string
     */
    public function actualPrimaryKey(): string
    {
        return 'session_id';
    }

    /**
     * 返回当前的表名
     * @return string
     */
    public function tableName(): string
    {
        return DB::MakeTable('paper_sessions');
    }

    /**
     * @param int $conference_id
     * @param bool $force_from_db
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function get_conference_sessions(int $conference_id, bool $force_from_db = false) : array {
        $data_ids = [];
        $cache_key = '<sess_in_conf>[' . strval($conference_id) . ']';
        if ($force_from_db === false) {
            try {
                $data_ids = $this->Cache->get($cache_key);
            } catch (\myConf\Exceptions\CacheMissException $e) {
                $force_from_db = true;
            }
        }
        if ($force_from_db === true) {
            $data = $this->fetchAll(['session_conference_id' => $conference_id], 'session_display_order', 'ASC');
            $data_ids = [];
            foreach($data as $session) {
                $data_ids []= $session['session_id'];
            }
            $this->Cache->set($cache_key, $data_ids);
        }
        return $data_ids;
    }

    /**
     * @param int $conference_id
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function delete_conference_sessions_cache(int $conference_id) : void {
        $cache_key = '<sess_in_conf>[' . strval($conference_id) . ']';
        $this->Cache->delete($cache_key);
    }

    /**
     * 得到某个会议中session的display_order的最大值
     * @param int $conference_id
     * @return int
     */
    public function get_conference_sessions_max_display_order(int $conference_id) : int {
        $display_order_max = DB::FetchFirstRaw('SELECT MAX(session_display_order) FROM ' . $this->tableName() . ' WHERE session_conference_id = ' . $conference_id)['MAX(session_display_order)'];
        return isset($display_order_max) ? intval($display_order_max) : 0;
    }

    /**
     * 重写父类方法，解决缓存不一致问题
     * @param array $data
     * @return int
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function insert(array $data = []) : int {
        $id = parent::insert($data);
        $this->delete_conference_sessions_cache($data['session_conference_id']);
        return $id;
    }

    /**
     * 重写父类方法，解决缓存不一致问题
     * @param string $pk_val
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function delete($pk_val) : void {
        $sess = parent::get($pk_val);
        !empty($sess) && $this->delete_conference_sessions_cache($sess['session_conference_id']);
        parent::delete($pk_val);
    }

    /**
     * @param string $pk_val
     * @param array $data
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set($pk_val, array $data = []) : void {
        //如果修改了display_order字段，那么旧的显示缓存必须要清理掉，其他字段修改没有影响
        if(isset($data['session_display_order'])) {
            $sess = parent::get($pk_val);
            !empty($sess) && $this->delete_conference_sessions_cache($sess['session_conference_id']);
        }
        parent::set($pk_val, $data);
    }
}