<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 22:08
 */

namespace myConf\Utils;

/**
 * Class Avatar 头像文件相关的静态方法类
 * @package myConf\Utils
 */
class Avatar
{
    /**
     * 处理上传头像逻辑
     * @param int $user_id
     * @param string $avatar_field
     * @return string
     * @throws \myConf\Exceptions\DirectoryException
     * @throws \myConf\Exceptions\FileUploadException
     */
    public static function ParseAvatar(int $user_id, string $avatar_field): string
    {
        //检查文件夹是否存在
        $base_path = AVATAR_DIR . strval($user_id % 100) . DIRECTORY_SEPARATOR;
        if (!is_dir($base_path)) {
            if (mkdir($base_path, 0777, true) === FALSE) {
                throw new \myConf\Exceptions\DirectoryException('MKDIR_ERROR', 'Trying to make directory "' . $base_path . '" for user avatar but failed.');
            }
        }
        $full_name = \myConf\Utils\Upload::ParseUploadFile($avatar_field, strval($user_id) . 'jpg', $base_path)['stored_name'];
        return substr($full_name, strlen(AVATAR_DIR));
    }
}