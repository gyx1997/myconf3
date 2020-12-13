<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 17:32
 */

namespace myConf\Models;


class Scholar extends \myConf\BaseModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $chn_full_name
     * @param string $address
     * @param string $institution
     * @param string $department
     * @param string $prefix
     * @return int
     */
    public function create_new(string $email, string $first_name = '', string $last_name = '', string $chn_full_name = '', string $address = '', string $institution = '', string $department = '', string $prefix = '') : int {
        return $this->tables()->Scholars->insert([
            'scholar_email' => $email,
            'scholar_first_name' => $first_name,
            'scholar_last_name' => $last_name,
            'scholar_chn_full_name' => $chn_full_name,
            'scholar_address' => $address,
            'scholar_institution' => $institution,
            'scholar_department' => $department,
            'scholar_prefix' => $prefix,
        ]);
    }

    /**
     * 获取某一个scholar的信息
     * @param string $email
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function getByEmail(string $email) : array {
        return $this->tables()->Scholars->get($email);
    }

    /**
     * 更新scholar信息
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $institution
     * @param string $department
     * @param string $address
     * @param string $prefix
     * @param string $chn_full_name
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set_by_email(string $email, string $first_name, string $last_name, string $institution, string $department, string $address, string $prefix = '', string $chn_full_name = '') : void
    {
        $data_to_update = array(
            'scholar_first_name' => $first_name,
            'scholar_last_name' => $last_name,
            'scholar_institution' => $institution,
            'scholar_department' => $department,
            'scholar_address' => $address,
            'scholar_prefix' => $prefix,
            'scholar_chn_full_name' => $chn_full_name
        );
        $this->tables()->Scholars->set($email, $data_to_update);
        return;
    }

    /**
     * 判断scholar是否存在
     * @param string $email
     * @return bool
     */
    public function exist_by_email(string $email) : bool
    {
        return $this->tables()->Scholars->exist($email);
    }
}