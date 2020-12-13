<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/16
 * Time: 11:27
 */

namespace myConf\Utils;

class Output
{
    /**
     * @param string $template_name
     * @param array $parameters
     * @throws \myConf\Exceptions\TemplateNotFoundException
     * @throws \myConf\Exceptions\TemplateParseException
     */
    public static function return_template(string $template_name, array $parameters = array()) : void
    {
        $target_template_file = TEMPLATE_BASE_PATH . $template_name;
        //载入模板
        $template = Template::load($target_template_file, ENVIRONMENT !== 'production');
        //分离变量
        foreach ($parameters as $key => $val) {
            $$key = $val;
        }
        //加载变量显示输出
        include($template);
    }

    public static function return_json(array $parameters = array()): void
    {
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($parameters);
    }

    public static function clear_compiled_template(): void
    {
        File::clear_directory_with_files(TEMPLATE_COMPILED_PATH, false);
        return;
    }
}