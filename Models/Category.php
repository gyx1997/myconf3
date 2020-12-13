<?php

namespace myConf\Models;

/**
 * Class Category
 * @package myConf\Models
 * @author _g63<522975334@qq.com>
 * @version 2019.1
 */
class Category extends \myConf\BaseModel
{
    /**
     * Category constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 判断指定的栏目是否存在
     * @param int $category_id
     * @return bool
     */
    public function exist(int $category_id) : bool {
        return $this->tables()->Categories->exist(strval($category_id));
    }

    /**
     * @param int $category_id
     * @return array
     */
    public function first_document(int $category_id) : array {
        return $this->tables()->Documents->fetchFirst(array('document_category_id' => $category_id));
    }

    /**
     * @param int $conferenceId
     * @param string $categoryName
     * @param int $category_type
     * @return int
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function New(int $conferenceId, string $categoryName, int $category_type) : int
    {
        \myConf\Utils\DB::transBegin();
        $category_id = $this->tables()->Categories->insert(
            array(
                'conference_id' => $conferenceId,
                'category_title' => $categoryName,
                'category_type' => $category_type
            )
        );
        $this->tables()->Documents->insert(array(
            'document_category_id' => $category_id,
            'document_title' => 'Untitled Document',
            'document_html' => '',
        ));
        \myConf\Utils\DB::transEnd();
        //刷新缓存
        $this->tables()->Categories->get_ids_by_conference($conferenceId, true);
        return $category_id;
    }

    /**
     * @param int $conference_id
     * @param int $category_id
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function delete(int $category_id) : void
    {
        /* Get the conference id of this category for cache refresh. */
        $conference_id = $this->tables()->Categories->get(strval($category_id))['conference_id'];
        /* Delete the category. */
        $this->tables()->Categories->delete(strval($category_id));
        /* Force refresh non-primary key cache data. */
        $this->tables()->Categories->get_ids_by_conference($conference_id, true);
    }

    /**
     * 修改某个category的名称（标题）
     * @param int $category_id
     * @param string $new_category_name
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function rename(int $category_id, string $new_category_name) : void
    {
        /* Get the conference id of this category for cache refresh. */
        $conference_id = $this->tables()->Categories->get(strval($category_id))['conference_id'];
        /* Rename the category. */
        $this->tables()->Categories->set(strval($category_id), array('category_title' => $new_category_name));
        /* Force refresh non-primary key cache data. */
        $this->tables()->Categories->get_ids_by_conference($conference_id, true);
    }

    /**
     * 修改某个category的display_order。
     * @param int $category_id
     * @param int $display_order
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set_category_display_order(int $category_id, int $display_order): void
    {
        $this->tables()->Categories->set($category_id, array('category_display_order' => $display_order));
    }

    /**
     * 将指定的category上移一位
     * @param int $conference_id
     * @param int $category_id
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function move_up(int $category_id) : void {
        /* Get the conference id. */
        $conference_id = $this->tables()->Categories->get($category_id)['conference_id'];
        $categories = $this->tables()->Categories->get_ids_by_conference($conference_id);
        //找到当前记录的id号
        $i = 0;
        foreach ($categories as $cid) {
            if ($cid == $category_id) {
                break;
            }
            $i++;
        }
        if ($i != 0) {
            $j = 0;
            //不是第一个，需要更新
            //因为多条UPDATE，需要使用事务
            \myConf\Utils\DB::transBegin();
            foreach ($categories as $cid) {
                $this->tables()->Categories->set($cid, array('category_display_order' => $j == $i - 1 ? $i : ($j == $i ? $i - 1 : $j)));
                $j++;
            }
            \myConf\Utils\DB::transEnd();
        }
        //删除旧缓存
        $this->tables()->Categories->delete_conference_categories_cache($conference_id);
    }

    /**
     * 将指定的category下移一位
     * @param int $conference_id
     * @param int $category_id
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function move_down(int $category_id) : void {
        /* Get the conference id. */
        $conference_id = $this->tables()->Categories->get($category_id)['conference_id'];
        $categories = $this->tables()->Categories->get_ids_by_conference($conference_id, true);
        $i = 0;
        $category_count = count($categories);
        foreach ($categories as $cid) {
            if ($cid == $category_id) {
                break;
            }
            $i++;
        }
        if ($i < $category_count - 1) {
            $j = 0;
            \myConf\Utils\DB::transBegin();
            foreach ($categories as $cid) {
                $this->tables()->Categories->set($cid, array('category_display_order' => $j === $i + 1 ? $i : ($j === $i ? $i + 1 : $j)));
                $j++;
            }
            \myConf\Utils\DB::transEnd();
        }
        //删除旧缓存
        $this->tables()->Categories->delete_conference_categories_cache($conference_id);
    }
}