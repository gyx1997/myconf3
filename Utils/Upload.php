<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 21:17
 */

namespace myConf\Utils;

/**
 * Class Upload 上传文件相关的静态方法类
 *
 * @package myConf\Utils
 */
class Upload
{
    public static $checked = array();

    /**
     * 处理指定表单名上传的文件，返回原文件名、目标文件名、文件大小和MIME类型。
     *
     * @param string $field            POST表单名
     * @param string $new_file_name    新文件名
     * @param string $path_to_store    存储路径
     * @param bool   $use_original_ext 使用原扩展名，默认true
     *
     * @return array 关联数组。original_name为原文件名，file_size为文件大小，mime_type为MIME类型。
     * @throws \myConf\Exceptions\FileUploadException
     */
    public static function ParseUploadFile(string $field,
                                           string $new_file_name,
                                           string $path_to_store,
                                           bool $use_original_ext = true): array
    {
        $file = $_FILES[$field];
        self::checkUploadError($field);
        !$use_original_ext && $new_file_name .= 'attach';
        $stored_name = $path_to_store . DIRECTORY_SEPARATOR . $new_file_name;
        if (!is_dir($path_to_store) && !mkdir($path_to_store, 0777, true))
        {
            throw new \myConf\Exceptions\FileUploadException('MKDIR_ERROR', 'An error occurred when trying to make directory "' . $path_to_store . '".');
        }
        if (@move_uploaded_file($file['tmp_name'], $stored_name) === false)
        {
            throw new \myConf\Exceptions\FileUploadException('MOVE_FILE_ERROR', 'An error occurred when trying to move "' . $file['tmp_name'] . '" to "' . $stored_name . '".');
        }
        return array(
            'original_name' => $file['name'],
            'stored_name'   => $stored_name,
            'file_size'     => $file['size'],
            'mime_type'     => $file['type'],
        );
    }

    /**
     * 得到上传文件的原文件名
     *
     * @param string $field
     *
     * @return string
     * @throws \myConf\Exceptions\FileUploadException
     */
    public static function GetOriginalFileName(string $field): string
    {
        self::checkUploadError($field);
        return $_FILES[$field]['name'];
    }

    /**
     * 检查是否有上传错误
     *
     * @param string $field
     *
     * @throws \myConf\Exceptions\FileUploadException
     */
    private static function checkUploadError(string $field): void
    {
        if (isset(self::$checked[$field]))
        {
            return;
        }
        $file = $_FILES[$field];
        if (!isset($file))
        {
            throw new \myConf\Exceptions\FileUploadException('NO_SUCH_FILE', 'No such file which form field is named "' . $field . '" in upload files, or the file was not uploaded');
        }
        if ($file['error'] !== 0)
        {
            if ($file['error'] === 4)
            {
                throw new \myConf\Exceptions\FileUploadException('NO_SUCH_FILE', 'No file selected to upload.');
            }
            else
            {
                throw new \myConf\Exceptions\FileUploadException('PHP_UPLOAD_ERROR', 'PHP file upload received an error which code is "' . strval($file['error']) . '".');
            }
        }
        if (!file_exists($file['tmp_name']))
        {
            throw new \myConf\Exceptions\FileUploadException('TMP_FILE_NOT_FOUND', 'Temporary file "' . $file['tmp_name'] . '" does not exists');
        }
        self::$checked[$field] = true;
        return;
    }
}