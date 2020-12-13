<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 23:40
 */

namespace myConf\Models;

use myConf\Utils\DB;

class Document extends \myConf\BaseModel
{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 得到某个id的document
     * @param int $id
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function get_by_id(int $id) : array {
        return $this->tables()->Documents->get(strval($id));
    }

    /**
     * @param int $documentId
     *
     * @return bool
     */
    public function exist(int $documentId)
    {
        return $this->tables()->Documents->exist(strval($documentId));
    }

    /**
     * 更新document的内容，并更新对应的attachment信息
     * @param int $id
     * @param string $content
     * @param string $title
     * @param array $aids
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\DbTransactionException
     */
    public function UpdateDocumentById(int $id, string $content = '', string $title = '', array $aids = array()) : void {
        //先找出已经使用的
        $old_attachments = $this->tables()->Attachments->get_used('document', $id);
        $old_attachment_ids = array();
        foreach ($old_attachments as $old_attachment) {
            $old_attachment_ids [$old_attachment['attachment_id']] = true;
        }
        DB::transBegin();
        //将使用了的附件都标记为1，同时更新其tag（document使用，并更新其tag_id）
        foreach ($aids as $aid) {
            if (!isset($old_attachment_ids[$aid])) {
                $this->tables()->Attachments->set(
                    strval($aid),
                    [
                        'attachment_tag_type' => \myConf\Models\Attachment::tag_type_document,
                        'attachment_tag_id' => $id,
                        'attachment_used' => 1,
                    ]
                );
                $this->tables()->Attachments->SetUsedStatus($aid, true);
            }
            $old_attachment_ids[$aid] = false;
        }
        //标记没有使用的附件
        foreach ($old_attachment_ids as $aid => $is_unused) {
            if ($is_unused === true) {
                $this->tables()->Attachments->SetUsedStatus($aid, false);
            }
        }
        //更新文档内容
        $this->tables()->Documents->set(strval($id), ['document_html' => $content, 'document_title' => $title]);
        DB::transEnd();
    }

    /**
     * 将document从显示列表上移一位
     * @param int $id
     */
    public function move_up(int $id) : void {
        //todo: add method body
    }

    /**
     * 将document从显示列表下移一位
     * @param int $id
     */
    public function move_down(int $id) : void {
        //todo: add method body.
    }

    /**
     * 更新document
     * @deprecated
     * @see \myConf\Models\Documents::set()
     * @param int $document_id
     * @param string $document_title
     * @param string $document_html
     */
    public function modify_document(int $document_id, string $document_title, string $document_html) : void {
        $this->db->where('document_id', $document_id);
        $this->db->update($this->_table(), array(
            'document_title' => $document_title,
            'document_html' => $document_html
        ));
    }

    /**
     * 添加一个document
     * @param int $category_id
     * @param string $document_title
     * @param string $document_html
     * @return int
     */
    public function add_document(int $category_id, string $document_title, string $document_html) : int {
        $this->db->insert($this->_table(), array(
            'document_category_id' => $category_id,
            'document_title' => $document_title,
            'document_html' => $document_html
        ));
        return $this->db->insert_id();
    }
}