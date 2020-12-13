<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/19
 * Time: 15:47
 */

namespace myConf;

use myConf\Utils\Template;

class Response
{
    /**
     * @var array 需要输出的变量
     */
    private $_vars = array();

    /**
     * @param array $variables
     */
    public function add_variables(array $variables = array()): void
    {
        foreach ($variables as $key => $value) {
            $this->_vars[$key] = $value;
        }
    }

    /**
     * 渲染并返回HTML页面
     * @param string $template_name
     * @throws Exceptions\TemplateNotFoundException
     * @throws Exceptions\TemplateParseException
     */
    public function html(string $template_name): void
    {
        Template::render($template_name, $this->_vars);
    }

    /**
     * 返回JSON页面
     */
    public function json(): void
    {
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($this->_vars);
    }

    /**
     * 返回错误提示信息
     * @param string $message
     * @param int $code
     */
    public function handled_error(string $message, int $code = 500)
    {
        try {
            $err_data = array(
                'message' => $message,
                'status' => strval($code) . ' ' . HTTP_STATUS_CODE[$code],
                'date' => date('M jS, Y', time())
            );
            http_response_code($code);
            Template::render('/Common/Error', array('data' => array_merge($err_data, $this->_vars)));
        } catch (\Throwable $e) {
            echo 'Fatal error.';
        }
    }
}