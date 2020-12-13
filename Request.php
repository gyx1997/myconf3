<?php

namespace myConf;


class Request
{
    /**
     * @var Response $_response 响应管理器
     */
    private $_response;

    /**
     * @var \myConf\BaseController $controller 控制器
     */
    private $_controller;

    /**
     * @var string 控制器完整类名
     */
    private $_controller_class_name;

    /**
     * @var bool 是否是ajax请求
     */
    private $_ajax;

    /**
     * @var array 当前所有的变量
     */
    private $_vars;

    /**
     * @var string 模板名
     */
    private $_template;

    /**
     * Request constructor.
     * @param string $controller 默认的控制器
     */
    public function __construct(string $controller)
    {
        $this->_controller_class_name = '\\myConf\\Controllers\\' . $controller;
    }

    /**
     * 返回response对象
     * @return \myConf\Response
     */
    public function response() : \myConf\Response {
        return $this->_response;
    }

    /**
     * @throws \myConf\Exceptions\ClassNotFoundException
     * @throws \myConf\Exceptions\HttpStatusException
     */
    public function Run() : void {
        if (!class_exists($this->_controller_class_name)) {
            throw new \myConf\Exceptions\ClassNotFoundException('CLASS_NOT_FOUND', 'Requested controller class not found.');
        }
        $class = $this->_controller_class_name;
        /**
         * @var \myConf\BaseController $controller
         */
        $controller = new $class();
        $vars = [];
        $controller->run($vars);
        $this->_vars = $vars;
        $this->_template = $controller->getTemplateName();
        return;
    }

    /**
     * @param string $message
     * @param int $code
     */
    public function show_error(string $message, int $code) : void {
        $this->_response->handled_error($message, $code);
    }

    /**
     * @return string
     */
    public function template_name() : string {
        return $this->_template;
    }

    /**
     * @return array
     */
    public function result_variables() : array {
        return $this->_vars;
    }
}