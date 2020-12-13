<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 15:24
 */

namespace myConf;


use myConf\Exceptions\HttpStatusException;
use myConf\Exceptions\SendExitInstructionException;
use myConf\Utils\Arguments;
use myConf\Utils\File;
use myConf\Utils\Session;
use myConf\Utils\Env;

/**
 * Class BaseController
 *
 * @package myConf
 * @author  _g63<522975334@qq.com>
 * @version 2019.1
 * @property-read \myConf\Services  $Services
 * @property-read \myConf\Callbacks $Callbacks
 * @property-read \CI_Session       $Session
 */
class BaseController extends BaseLayer
{
    public $input;
    public $uri;
    public $load;
    public $library;
    public $session;

    /**
     * @var int 当前登录的用户ID。
     */
    protected $userId = 0;
    /**
     * @var array 当前用户的全部信息。
     */
    protected $userData = array();
    /**
     * @var int 登录时间。
     */
    protected $loginTime = 0;
    /**
     * @var mixed|string 控制器类名，URI第2段
     */
    protected $controllerName = '';
    /**
     * @var mixed|string 方法名，即URI第3段
     */
    protected $methodName = '';
    /**
     * @var mixed|string 动作名，即URI第4段
     */
    protected $actionName = '';
    /**
     * @var mixed|string 操作名
     */
    protected $do = '';
    /**
     * @var string 当前URL使用BASE64编码后的字串
     */
    protected $urlEncoded = '';
    /**
     * @var bool|string 当前接收到的用于跳转的redirect参数。
     */
    protected $urlRedirect = '';

    private $_service_manager;


    /**
     * @var object The method instance.
     */
    protected $methodInstance = null;


    /**
     * BaseController constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        /* Initialize Codeigniter super object and sub methods for compatibility. */
        $CI = &get_instance();
        $this->benchmark = $CI->benchmark;
        $this->db = $CI->db;
        $this->email = $CI->email;
        $this->input = $CI->input;
        $this->lang = $CI->lang;
        $this->load = $CI->load;
        $this->output = $CI->output;
        $this->security = $CI->security;
        $this->session = $CI->session;
        $this->uri = $CI->uri;
        $this->zip = $CI->zip;
        /* Get controller class name. */
        $this->controllerName = $this->uri->segment(1, '');
        self::setGlobal('controller_name', $this->controllerName);
        /* Define special controllers with url remapping. */
        $specialControllerUrlMapping = array(
            'conference' => array(
                'class'  => 1,
                'method' => 3,
                'action' => 4,
                'do' => 5,
            ),
        );
        /* Remap the method and action from url. */
        if (array_key_exists($this->controllerName, $specialControllerUrlMapping))
        {
            // Special controller(s).
            $mapping_rule = $specialControllerUrlMapping[$this->controllerName];
            $this->methodName = strtolower($CI->uri->segment($mapping_rule['method'], ''));
            $this->actionName = strtolower($CI->uri->segment($mapping_rule['action'], ''));
            $this->do = strtolower($CI->uri->segment($mapping_rule['do'], ''));
        }
        else
        {
            // Normal controller(s).
            $this->methodName = strtolower($CI->uri->segment(2, ''));
            $this->actionName = strtolower($CI->uri->segment(3, ''));
            $this->do = strtolower($CI->uri->segment(4, ''));
        }
        // Set default method and action.
        if (strlen($this->methodName) === 0)
        {
            $this->methodName = 'index';
        }
        if (strlen($this->actionName) === 0)
        {
            $this->actionName = 'default';
        }
        if (strlen($this->do) === 0)
        {
            $this->do = $this->input->get('do');
        }
        // Add method name and action name to global variable list.
        self::setGlobal('method_name', $this->methodName);
        self::setGlobal('action_name', $this->actionName);
        $this->urlEncoded = base64_encode(Env::get_current_url());
        $url_redirect = base64_decode(Env::get_redirect());
        $this->urlRedirect = $url_redirect === '' ? '/' : $url_redirect;
        // Add other parameters to global variable list.
        self::setGlobal('url_redirect', $this->urlRedirect);
        self::setGlobal('url_encoded', $this->urlEncoded);
        self::setGlobal('do', $this->do);
        if ($this->controllerName === 'install')
        {
            exit('INVALID OPERATION.');
        }
        else
        {
            /* Load service manager. */
            $this->_service_manager = new \myConf\Services;
            //检查登录情况
            $this->checkLoginStatus();
        }
    }
    
    /**
     *
     */
    public function __finalizer()
    {
    
    }
    
    /**
     * @throws \myConf\Exceptions\HttpStatusException
     */
    public final function run(): void
    {
        $method = str_replace('-', '_', $this->methodName);
        if (!method_exists($this, $method))
        {
            throw new \myConf\Exceptions\HttpStatusException(404, 'METHOD_NOT_FOUND', 'Method "' . $method . '" from requested URL not found.');
        }
        //执行子方法
        $this->$method();
        //返回数据
        $this->collectOutputVariables();
        return;
    }

    /**
     * 获取输出变量列表
     */
    protected function collectOutputVariables(): void
    {
        /* Those output variables is needed to be shown on the html page,
           which are not necessary for ajax requests.
           So, they are not included when ajax requests are received. */
        if (defined('REQUEST_IS_AJAX') === false)
        {
            $this->addRetVariables(array(
                'title'        => 'myConf',
                'footer1'      => $this->Services->Config->get_footer(),
                'mitbeian'     => $this->Services->Config->get_mitbeian(),
                'csrf_name'    => $this->security->get_csrf_token_name(),
                'csrf_hash'    => $this->security->get_csrf_hash(),
                'url'          => $this->urlEncoded,
                'login_status' => $this->hasLogin(),
                'login_user'   => $this->userData,
                'class'        => $this->controllerName,
                'method'       => $this->methodName,
                'action'       => $this->actionName,
                'do'           => $this->do,
            ), OUTPUT_VAR_HTML_ONLY);
        }
    }

    /**
     * Send the data to output variable.
     */
    protected function returnData() {
        /* Check whether $this->methodRet has been set. */
        if (empty(self::$retVal)) {
            /* If it is unset, trigger a notice. */
            trigger_error('Controller method result data undefined.', E_USER_WARNING);
            /* Then returns http 500 error. */
            self::$retVal = array(
                'httpCode'   => 500,
                'statusCode' => -1,
                'status'     => 'RET_VAL_UNDEFINED',
                'data'       => array('description' => 'Return value undefined.'),
            );
        }
        /* Check http errors. */
        if (self::$retVal['httpCode'] >= 400) {
            /* Exceptions. */
            throw new HttpStatusException(self::$retVal['httpCode'], self::$retVal['status'],
                self::$retVal['data']['description']);
        }
        /* If there are not any http errors, add output data. */
        $this->addRetVariables(self::$retVal);
    }

    /**
     * 将变量添加入输出列表
     *
     * @param array $vars 一组变量
     * @param int   $type 变量最终输出方式，分别为全部输出，只在HTML输出，只在JSON输出。
     */
    public final function addRetVariables(array $vars = array(),
                                          int $type = OUTPUT_VAR_ALL): void
    {
        $retVal = &$GLOBALS['myConf']['ret']['data'];
        foreach ($vars as $key => $value)
        {
            // Get old data which key is $key. If it does not exist,
            // make it an empty array for using array_merge easily.
            $rawValue = is_null($retVal) === false && array_key_exists($key, $retVal) === true
                ? $retVal[$key] : array();
            if (!isset($rawValue))
            {
                $rawValue = array();
            }
            // If the old value is an array,
            // use function array_merge to prevent items
            // in the old value (array) to be replaced.
            // Otherwise, set it directly.
            $targetValue = is_array($value) ? array_merge($rawValue,  $value) :
                $value;
            // Append new $data to $outputVariables[$key]['value'].
            $retVal[$key] = array(
                'type'  => $type,
                'value' => $targetValue,
            );
        }
    }

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        $method = ucfirst(str_replace('-', '_', $this->methodName));
        $action = ucfirst(str_replace('-', '_', $this->actionName));
        return ucfirst($this->controllerName) . DIRECTORY_SEPARATOR . ucfirst($method) . DIRECTORY_SEPARATOR . ucfirst($action);
    }

    /**
     * dummy Method. Should be over-written in Derived Controllers
     */
    public function index(): void
    {
        return;
    }

    /**
     * 魔术方法，获取控制器使用的类
     *
     * @param $key
     *
     * @return \CI_Session|\myConf\Services|null
     */
    public function __get($key)
    {
        if ($key === 'Services')
        {
            return $this->_service_manager;
        }
        else if ($key === 'Session')
        {
            return $this->session;
        }
        else
        {
            return null;
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function checkLoginStatus(): bool
    {
        try
        {
            /* Get user_id from session data. */
            $rawUserId = Session::get_user_data('user_id');
            if (isset($rawUserId) === false)
            {
                /* If user_id unset, set $this->userId to 0,
                   which means the current user has not logged in. */
                $this->userId = 0;
            }
            else
            {
                $this->userId = intval($rawUserId);
            }
            /* Add user_id to global variable list. */
            self::setGlobal('user_id', $this->userId);
            /* Check whether the  user has logged in. */
            if ($this->userId === 0)
            {
                return false;
            }
            $this->loginTime = Session::get_user_data('login_time');
            $this->userData = $this->Services->Account->user_account_info($this->userId);
            //避免session失效问题，刷新session
            $this->_set_login($this->userData);
            /* Add global arguments. */
            Arguments::sendFuncArg('user_id', $this->userId);
            self::setGlobal('user_id', $this->userId);
            return true;
        } catch (\myConf\Exceptions\SessionKeyNotExistsException $e)
        {
            return false;
        } catch (\myConf\Exceptions\UserNotExistsException $e)
        {
            throw new \Exception('User has logged in, but we cannot get his/her account data. Check whether cache layer goes wrong, or there has been a web attack.');
        }
    }

    /**
     * 登录操作
     *
     * @param array $user_data
     */
    protected function _set_login(array $user_data): void
    {
        Session::set_user_data('user_id', $user_data['user_id']);
        Session::set_user_data('login_time', time());
        $this->userId = intval($user_data['user_id']);
    }

    /**
     * @throws Exceptions\SendRedirectInstructionException
     */
    protected function _set_logout(): void
    {
        Session::destroy();
        $this->selfRedirect();
    }

    /**
     * 检查是否登录，如果没有登录，跳转到登录页面。
     *
     * @throws Exceptions\SendRedirectInstructionException
     */
    protected function _login_redirect(): void
    {
        if (!$this->hasLogin())
        {
            $this->RedirectTo('/account/login/?redirect=' . $this->urlEncoded);
            exit();
        }
    }

    /**
     * 检查是否登录。
     *
     * @return bool
     */
    protected function hasLogin(): bool
    {
        return $this->userId !== 0;
    }

    /**
     * 抛出异常，进行跳转
     *
     * @throws Exceptions\SendRedirectInstructionException
     */
    protected function selfRedirect(): void
    {
        //exit($this->_url_redirect);
        throw new \myConf\Exceptions\SendRedirectInstructionException($this->urlRedirect === '' ? '/' : $this->urlRedirect);
    }

    /**
     * 跳转到指定的URL。
     *
     * @param string $target
     *
     * @throws Exceptions\SendRedirectInstructionException
     */
    protected function RedirectTo(string $target): void
    {
        throw new \myConf\Exceptions\SendRedirectInstructionException($target);
    }

    /**
     * 立即退出执行
     *
     * @param null $data
     *
     * @throws SendExitInstructionException
     */
    public function exit_promptly($data = null)
    {
        if (isset($data))
        {
            throw new SendExitInstructionException(\myConf\Exceptions\SendExitInstructionException::DO_OUTPUT_JSON, $data);
        }
        throw new SendExitInstructionException();
    }
}