<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/23
     * Time: 22:49
     */

    namespace myConf\Services;

    use myConf\Errors as err;
    use myConf\Errors\Services\Services as errServices;
    use myConf\Exceptions\AttachFileCorruptedException;
    use myConf\Exceptions\CacheDriverException;
    use myConf\Exceptions\FileUploadException;
    use myConf\Exceptions\SendExitInstructionException;
    use myConf\Utils\Attach;

    class Attachment extends \myConf\BaseService {

        public function __construct() {
            parent::__construct();
        }

        /**
         * Service for upload an attachment.
         * @param string $fileField
         * @return array|bool
         */
        public function upload(string $fileField) {
            try
            {
                /* Parse uploaded file. */
                $attach_info = Attach::parse($fileField);
            }
            catch (FileUploadException $e)
            {
                err::setError(errServices::E_UPLOAD_FILE_UPLOAD_ERROR, 200, $e->getShortMessage(), $e->getMessage());
                return false;
            }
            /* Add the attachment file information to data layer. */
            $attachment_id = $this->Models->Attachment->AddAsUnknownAttached(
                $attach_info['full_name'],
                $attach_info['original_name'],
                $attach_info['size'],
                $attach_info['is_image']
            );
            return array(
                'attachment_id' => $attachment_id,
                'original_name' => $attach_info['original_name'],
                'file_size' => $attach_info['size'],
                'extension' => $attach_info['extension'],
                'file_name' => RELATIVE_ATTACHMENT_DIR . $attach_info['full_name'],
            );
        }

        /**
         * @param int $attachment_id
         * @param string $type
         *
         * @throws AttachFileCorruptedException
         * @throws CacheDriverException
         * @throws SendExitInstructionException
         */
        public function download(int $attachment_id, string $type = 'file') {
            // Get the attachment's information from model layer.
            $attach_info = $this->Models->Attachment->get($attachment_id);
            if (empty($attach_info))
            {
                err::setError(errServices::E_ATTACHMENT_NOT_FOUND, 404,
                    errServices::errorMessage[errServices::E_ATTACHMENT_NOT_FOUND], 'The requested attachment with id' . strval($attachment_id) . ' not found on this server.');
                return false;
            }
            // If it is not an image, increase its download times.
            if ($type === 'file')
            {
                $this->Models->Attachment->increase_download_times($attachment_id);
            }
            try
            {
                // Finally, the download will start.
                Attach::DownloadAttachment($attach_info['attachment_file_name'], $attach_info['attachment_original_name'], $attach_info['attachment_file_size']);
                return true;
            }
            catch(AttachFileCorruptedException $e)
            {
                // This exception means that the file does not exist in
                // the storage, but its information is still in database. */
                err::setError(errServices::E_ATTACHMENT_NOT_FOUND,
                              404,
                              errServices::errorMessage[errServices::E_ATTACHMENT_NOT_FOUND],
                              'The requested attachment with id'
                              . strval($attachment_id)
                              . ' not found on this server.');
                return false;
            }
        }

        /**
         * @param int $attachment_id
         *
         * @throws AttachFileCorruptedException
         * @throws CacheDriverException
         * @throws SendExitInstructionException
         */
        public function download_pdf_as_preview(int $attachment_id) : void {
            if ($this->Models->Attachment->AttachmentExists($attachment_id))
            {
                $attach_info = $this->Models->Attachment->get($attachment_id);
                Attach::preview($attach_info['attachment_file_name'], $attach_info['attachment_original_name'], $attach_info['attachment_file_size'], Attach::file_type_pdf);
            }
            err::setError(errServices::E_ATTACHMENT_NOT_FOUND, 404,
                errServices::errorMessage[errServices::E_ATTACHMENT_NOT_FOUND], 'The requested attachment with id' . strval($attachment_id) . ' not found on this server.');
            return;
        }

        /**
         * @param int $limit
         * @param int $start
         * @param bool $image_only
         */
        public function UeditorGetFileList($documentId, $limit, $start, $image_only = false) {
            $files = $this->Models->Attachment->GetAttachmentList($this->Models->Attachment::tag_type_document, $documentId, $image_only, $start, $limit);
            if (count($files) === 0) {
                return ['state' => 'no match file', 'start' => $start, 'total' => 0, 'list' => array()];
            }
            $file_list = array();
            foreach ($files as $file) {
                $file_list [] = array(
                    'url' => $file['attachment_file_name'],
                    'mtime' => $file['attachment_upload_time'],
                    'original' => $file['attachment_original_name'],
                );
            }
            return ['state' => 'SUCCESS', 'start' => $start, 'total' => count($files), 'list' => $file_list];
        }
    }