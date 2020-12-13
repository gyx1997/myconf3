<?php


namespace myConf\Methods\Attachment;

use myConf\Exceptions\FileUploadException;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods of attachment upload.
 *
 * @package myConf\Methods\Attachment
 */
class Hub extends \myConf\BaseMethod
{
    /**
     * Upload constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /attachment/put/
     */
    public static function upload()
    {
        /* Input preparation. */
        $file_field = Arguments::getHttpArg('ff');
        if (is_null($file_field))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        /* Get action from $GLOBALS. */
        $actionName = self::getGlobal('action_name');
        /* Upload attachment. */
        $attach_data = Services::attachment()->upload($file_field);
        self::return(array(
                         //给UEditor专用
                         'state' => 'SUCCESS',
                         //通用的状态标记
                         'status' => 'SUCCESS',
                         //对于图片直接进行下载，不需要中转进行文件重命名。
                         //也可以充分利用后期的CDN
                         'url' => $actionName === 'file' ? '/attachment/get/' . $actionName . '/?aid=' . $attach_data['attachment_id'] : $attach_data['file_name'],
                         'title' => $attach_data['original_name'],
                         'original' => $attach_data['original_name'],
                         'type' => $attach_data['extension'],
                         'size' => $attach_data['file_size'],
                         'aid' => $attach_data['attachment_id'],
                     ),
                     true);
    }

    /**
     * @requestUrl /attachment/get/
     */
    public static function download()
    {
        /* Input preparation. */
        $attachmentId = Arguments::getHttpArg('aid');
        if (is_null($attachmentId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        $attachmentId = intval($attachmentId);
        /* Get action name(type) from $GLOBALS. */
        $actionName = self::getGlobal('action_name');
        $type = ($actionName === 'image' ? 'image' : 'file');
        /* Download file. */
        Services::attachment()->download($attachmentId, $type);
        self::return();
    }

    /**
     * @requestUrl /attachment/preview/pdf/
     */
    public static function previewPdf()
    {
        /* Input preparation. */
        $attachmentId = Arguments::getHttpArg('aid');
        if (is_null($attachmentId))
        {
            self::retError(400,
                           -1,
                           'REQUEST_PARAM_INVALID',
                           'Necessary parameter(s) missing.');
            return;
        }
        $attachmentId = intval($attachmentId);
        /* Get action name(type) from $GLOBALS. */
        $actionName = self::getGlobal('action_name');
        if ($actionName !== 'pdf')
        {
            self::retError(404,
                           -1,
                           'PAGE_NOT_FOUND',
                           'The requested page not found.');
            return;
        }
        Services::attachment()->download_pdf_as_preview($attachmentId);
    }
}