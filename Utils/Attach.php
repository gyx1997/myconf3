<?php

namespace myConf\Utils;

use myConf\Exceptions\AttachFileCorruptedException;
use myConf\Exceptions\SendExitInstructionException;

/**
 * Class Attach 附件文件相关的静态方法类
 * @package myConf\Utils
 * @see \myConf\Utils\Upload
 */
class Attach
{
    public const file_type_pdf = 1;
    public const file_type_jpeg = 2;
    public const file_type_png = 3;

    /**
     * 处理附件。
     * @param string $file_field_name
     * @return array
     * @throws \myConf\Exceptions\FileUploadException
     */
    public static function parse(string $file_field_name): array
    {
        // Define result array.
        $result = ['error' => '', 'status' => ''];
        // Parse upload file information from $_UPLOAD with class \myConf\Utils\Upload.
        $stored_data = self::_get_stored_file_data(Upload::GetOriginalFileName($file_field_name));
        $file_data = Upload::ParseUploadFile($file_field_name,
                                             $stored_data['short_name'],
                                             ATTACHMENT_DIR . $stored_data['directory'],
                                             true);
        // Check whether it is image using extensions.
        $is_image = ($stored_data['extension'] == 'jpg' || $stored_data['extension'] == 'jpeg'
            || $stored_data['extension'] == 'png' || $stored_data['extension'] == 'gif') ? 1 : 0;
        if ($is_image === 1)
        {
            $image_info = @getimagesize(FCPATH
                                        . RELATIVE_ATTACHMENT_DIR
                                        . $stored_data['directory']
                                        . $stored_data['short_name']);
        }
        $result['mime_type'] = $file_data['mime_type'];
        $result['original_name'] = $file_data['original_name'];
        $result['is_image'] = $is_image;
        $result['size'] = $file_data['file_size'];
        // Merge upload file data.
        $result = array_merge($result, $stored_data);
        return $result;
    }

    /**
     * @param $file_name
     * @return array
     */
    private static function _get_stored_file_data($file_name)
    {
        $file_ext = File::filename_extension($file_name);
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = '{yy}/{mm}/{dd}/';
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $stored_dir = $format;
        //生成文件名
        $short_name = md5(strval($d[4]) . strval($d[5]) . strval($d[6]) . $file_name) . '.' . $file_ext;
        $full_path = $stored_dir . $short_name;
        return array(
            'full_name' => $full_path,
            'short_name' => $short_name,
            'directory' => $stored_dir,
            'extension' => $file_ext
        );
    }

    /**
     * @param string $attach_relative_path
     * @param string $file_name_original
     * @param int $file_size
     * @param int $download_speed
     * @throws AttachFileCorruptedException
     */
    public static function DownloadAttachment(string $attach_relative_path, string $file_name_original, int $file_size, int $download_speed = 80000) : void {
        $file_absolute_path = ATTACHMENT_DIR . $attach_relative_path;
        if (!@file_exists($file_absolute_path))
        {
            throw new AttachFileCorruptedException('ATTACH_NOT_EXIST', 'File "' . $file_absolute_path . '" does not exist.');
        }
        set_time_limit(1800);
        ob_clean();
        header("Content-type:application/octet-stream");
        header("Content-Disposition:attachment;filename=" . $file_name_original);
        header("Accept-ranges:bytes");
        header("Accept-length:" . $file_size);
        $fp = @fopen($file_absolute_path, 'r');
        if ($fp === false)
        {
            throw new AttachFileCorruptedException('ATTACH_CANNOT_READ', 'File "' . $file_absolute_path . '" cannot be read.');
        }
        $buffer = $download_speed / 10;
        $buffer_count = 0;
        while (!@feof($fp) && $file_size - $buffer_count > 0) {
            $data = fread($fp, $buffer);
            $buffer_count += $buffer;
            echo $data;
            flush();
            ob_flush();
            usleep(100000);
        }
        @fclose($fp);
        // Download success. Just exit.
        shutdown(0);
        return;
    }

    /**
     * @param string $attach_relative_path
     * @param string $file_name_original
     * @param int $file_size
     * @param int $file_type
     * @throws AttachFileCorruptedException
     */
    public static function preview(string $attach_relative_path,
                                   string $file_name_original,
                                   int $file_size,
                                   int $file_type)
    {
        $file_absolute_path = ATTACHMENT_DIR . $attach_relative_path;
        // Check the existence of the target attachment file.
        if (!@file_exists($file_absolute_path))
        {
            throw new AttachFileCorruptedException('ATTACH_NOT_EXIST',
                                                   'File "'
                                                   . $file_absolute_path
                                                   . '" does not exist.');
        }
        // Set the script maximum execution time to 30 minutes.
        set_time_limit(1800);
        ob_clean();
        // If pdf file is requested, send special header for inline pdf reader.
        if ($file_type === self::file_type_pdf)
        {
            header("Content-type:application/pdf");
            header("Content-Disposition:inline;filename=" . $file_name_original);
        }
        header("Accept-ranges:bytes");
        header("Accept-length:" . $file_size);
        // Try to open the attachment file.
        $fp = @fopen($file_absolute_path, 'r');
        if ($fp === false)
        {
            // The target file cannot be opened.
            throw new AttachFileCorruptedException('ATTACH_CANNOT_READ',
                                                   'File "'
                                                   . $file_absolute_path . '" cannot be read.');
        }
        // Set the buffer for reading file.
        $buffer = 1048576;
        $buffer_count = 0;
        // Iterations for reading file content (binary form).
        while (!@feof($fp) && $file_size - $buffer_count > 0)
        {
            // Read the current block to buffer.
            $data = fread($fp, $buffer);
            $buffer_count += $buffer;
            // Send data.
            echo $data;
            // Force refresh the output buffer of php.
            flush();
            ob_flush();
            usleep(100000);
        }
        // Close the file handler.
        @fclose($fp);
        // Download success. Just exit.
        shutdown(0);
        return;
    }
}