<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/17
 * Time: 10:33
 */

namespace myConf\Controllers;

use myConf\Exceptions\HttpStatusException;
use myConf\Services;
use myConf\Utils\Arguments;
use myConf\Utils\Template;

/**
 * 管理面板控制器
 * Class Admin
 * @package myConf\Controllers
 */
class Admin extends \myConf\BaseController
{
    private static $privilegeTable = [
        'index' => ['admin', 'sadmin'],
        'conference' => ['admin', 'sadmin'],
        'user' => ['admin', 'sadmin'],
        'sys' => ['sadmin']
    ];

    /**
     * check the privilege.
     * @throws HttpStatusException
     * @throws \myConf\Exceptions\SendRedirectInstructionException
     * @throws \myConf\Exceptions\UserNotExistsException
     */
    private function checkPrivilege() {
        if ($this->checkLoginStatus() === false)
        {
            $this->_login_redirect();
        }
        $user = $this->Services->Account->user_account_info($this->userId);
        if (in_array($user[\myConf\Models\Fields\Users::Role], self::$privilegeTable[$this->methodName]) === false)
        {
            throw new HttpStatusException(403, 'FORBIDDEN', 'You are not allowed to visit this page.');
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->checkPrivilege();
        //
        //throw new \myConf\Exceptions\HttpStatusException(404, 'NOT_FOUND', 'The page you are requesting is not found on this server.');
    }

    public function index(): void
    {

    }

    public function conference() : void
    {
        switch($this->actionName)
        {
            case 'add':
            {
                break;
            }
            default:

        }
    }
    
    public function user()
    {
        switch($this->actionName)
        {
            case 'edit':
                {
                    break;
                }
            case 'register':
                {
                    $email = trim(base64_decode(Arguments::getHttpArg('email')));
                    break;
                }
            case '':
            case 'list':
            default:
                {
                    $page = intval(Arguments::getHttpArg('page'));
                    $countPerPage = intval(Arguments::getHttpArg('cpPage'));
                    $email = trim(base64_decode(Arguments::getHttpArg('email')));
                    $conferenceJointIn = intval(Arguments::getHttpArg('confId'));
                    //Template::setTemplate();
                    break;
                }
        }
    }

    public function sys(): void
    {
        if ($this->actionName === 'opcache' && $this->do === 'reset') {
            opcache_reset();
        } else if ($this->actionName === 'dcache' && $this->do === 'reset') {
            \myConf\Cache::clear();
        } else if ($this->actionName === 'template' && $this->do === 'clear') {
            \myConf\Utils\Template::clear_compiled_template();
        }
    }
}