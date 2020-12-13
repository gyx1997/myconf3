<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 22:39
 */

namespace myConf\Utils;

/**
 * Class File
 * @package myConf\Utils
 */
class File
{
    /**
     * @param string $filename
     * @return string
     */
    public static function filename_extension(string $filename): string
    {
        return substr($filename, strrpos($filename, '.') + 1);
    }

    /**
     * @param string $directory
     * @param bool $include_self
     */
    public static function clear_directory_with_files(string $directory, bool $include_self = true): void
    {
        //先删除目录下的文件：
        $dh = opendir($directory);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $directory . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::clear_directory_with_files($fullpath, true);
                }
            }
        }
        closedir($dh);
        $include_self && rmdir($directory);
    }
}