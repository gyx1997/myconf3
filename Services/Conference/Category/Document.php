<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/23
     * Time: 18:34
     */

    namespace myConf\Services\Conference\Category;

    /* Import error definitions and error handler. */

    use \myConf\Errors as err;
    use \myConf\Errors\Services\Services as errServices;

    class Document extends \myConf\BaseService {
        public function __construct() {
            parent::__construct();
        }

        /**
         * Determines whether the document with given id exists.
         * @param int $documentId The target document id.
         *
         * @return bool True if the document exist. False otherwise.
         */
        public function documentExist(int $documentId)
        {
            if ($this->Models->Document->exist($documentId) === false)
            {
                err::setError(errServices::E_CONFERENCE_DOCUMENT_NOT_EXISTS, 404);
                return false;
            }
            return true;
        }

        /**
         * 得到指定document_id的document的内容
         *
         * @param int $documentId
         *
         * @return array|false
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DocumentNotExistsException
         */
        public function getDocumentData(int $documentId) {
            /* Check whether the required document exists. */
            if ($this->Models->Document->exist($documentId) === false) {
                err::setError(errServices::E_CONFERENCE_DOCUMENT_NOT_EXISTS);
                return false;
            }
            /* Get the document's data. */
            return $this->Models->Document->get_by_id($documentId);
        }

        /**
         * @param int $document_id
         * @param string $title
         * @param string $content
         * @throws \myConf\Exceptions\CacheDriverException
         * @throws \myConf\Exceptions\DbTransactionException
         */
        public function submitContent(int $document_id, string $title, string $content) {
            $files = $this->contentSplitAttachments($content);
            $images = $this->contentSplitImages($content);
            $image_aids = array();
            foreach ($images as $image) {
                $id = $this->Models->Attachment->get_id_from_filename($image);
                $id !== 0 && $image_aids [] = $id;
            }
            $aids = array_merge($files, $image_aids);
            $this->Models->Document->UpdateDocumentById($document_id, $content, $title, $aids);
            return true;
        }

        /**
         * @param string $content
         * @return array
         */
        private function contentSplitAttachments(string $content) : array {
            $aids = array();
            //以下载的特征url进行正则匹配
            $regex_a = "/\"\/attachment\/get\/.*?\/\?aid=.*?\"/";
            $array_links = array();
            if (preg_match_all($regex_a, $content, $array_links)) {
                //将本次新加入的附件标记位使用的附件
                foreach ($array_links[0] as $link) {
                    $link = substr($link, 0, strlen($link) - 1);
                    $aid = intval(substr($link, strpos($link, '?aid=') + 5));
                    $aids [] = $aid;
                }
            }
            return $aids;
        }

        /**
         * @param string $content
         * @return array
         */
        private function contentSplitImages(string $content) : array {
            $images = array();
            //以图片的特征url进行正则匹配
            $regex_a = "/src=\"\/data\/attachment\/.*?\"/";
            $array_links = array();
            if (preg_match_all($regex_a, $content, $array_links)) {
                //将本次新加入的附件标记位使用的附件
                foreach ($array_links[0] as $link) {
                    $link = substr($link, 22, strlen($link) - 23);
                    $images [] = $link;
                }
            }
            return $images;
        }
    }