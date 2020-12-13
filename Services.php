<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 18:24
 */

namespace myConf;

/**
 * Class Services 微服务管理器
 *
 * @package myConf
 * @property-read \myConf\Services\Account $Account
 * @property-read \myConf\Services\Config $Config
 * @property-read \myConf\Services\Conference $Conferences
 * @property-read \myConf\Services\Document $Document
 * @property-read \myConf\Services\Attachment $Attachment
 * @property-read \myConf\Services\Scholar $Scholar
 * @property-read \myConf\Services\Paper $Paper
 */
class Services
{
    /**
     * @var array 当前加载的服务
     */
    private $_services = array();

    /**
     * 返回指定的微服务实例对象（类名大小写敏感）
     * @param string $service_name
     * @return BaseService
     */
    public function __get(string $service_name): \myConf\BaseService
    {
        if (!isset($this->_services[$service_name])) {
            $class_name = '\\myConf\\Services\\' . $service_name;
            $this->_services[$service_name] = new $class_name();
        }
        return $this->_services[$service_name];
    }

    private static $serviceConference = null;
    
    /**
     * @return Services\Conference
     */
    public static function conferences()
    {
        if (is_null(self::$serviceConference)) {
            self::$serviceConference = new \myConf\Services\Conference();
        }
        return self::$serviceConference;
    }

    private static $servicePaper = null;

    public static function Papers() {
        if (is_null(self::$servicePaper)) {
            self::$servicePaper = new \myConf\Services\Conference\Papers();
        }
        return self::$servicePaper;
    }

    private static $serviceAccount = null;

    public static function Accounts()
    {
        if (is_null(self::$serviceAccount))
        {
            self::$serviceAccount = new \myConf\Services\Account();
        }
        return self::$serviceAccount;
    }
    
    private static $serviceScholar = null;
    
    /**
     * Services of scholar.
     * @return Services\Scholar
     */
    public static function scholars()
    {
        if (is_null(self::$serviceScholar))
        {
            self::$serviceScholar = new \myConf\Services\Scholar();
        }
        return self::$serviceScholar;
    }

    private static $serviceAttachment = null;
    
    /**
     * @return Services\Attachment
     */
    public static function attachment()
    {
        if (is_null(self::$serviceAttachment))
        {
            self::$serviceAttachment = new \myConf\Services\Attachment();
        }
        return self::$serviceAttachment;
    }
}