<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/23
     * Time: 23:26
     */

    namespace myConf\Controllers;

    use myConf\Methods\Attachment\Hub;

    /**
     * Class Attachment
     *
     * @package myConf\Controllers
     */
    class Attachment extends \myConf\BaseController {

        /**
         * Attachment constructor.
         * @throws \Exception
         */
        public function __construct()
        {
            parent::__construct();
        }

        public function put()
        {
            // Put attachment must be an ajax request.
            request_declare_as_ajax();
            Hub::upload();
        }

        public function get()
        {
            Hub::download();
        }

        public function preview()
        {
            Hub::previewPdf();
        }

        public function put_temp_file() : void
        {

        }

        public function get_list()
        {
            $documentId = $this->input->get_post('tagid');
            $start = $this->input->get_post('start');
            $limit = $this->input->get_post('size');
            $data = $this->Services->Attachment->UeditorGetFileList($documentId, $limit, $start, $this->actionName === 'image');
            $this->exit_promptly($data);
        }
    }