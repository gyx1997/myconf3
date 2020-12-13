<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 17:22
 */

namespace myConf;

/**
 * Class Models 模型管理器
 *
 * @package myConf
 * @property-read \myConf\Models\User         $User
 * @property-read \myConf\Models\Scholar      $Scholar
 * @property-read \myConf\Models\Config       $Config
 * @property-read \myConf\Models\Document     $Document
 * @property-read \myConf\Models\Conference   $Conferences
 * @property-read \myConf\Models\Category     $Category
 * @property-read \myConf\Models\ConfMember   $ConfMember
 * @property-read \myConf\Models\Attachment   $Attachment
 * @property-read \myConf\Models\Papers       $Paper
 * @property-read \myConf\Models\PaperSession $PaperSession
 * @property-read \myConf\Models\Reviewer     $PaperReview
 * @method
 */
class Models
{
    /**
     * @var array 模型数组
     */
    private $_models = array();
    
    /**
     * Models constructor which is not accessible.
     */
    private function __construct() { }
    
    private static $selfInstance = null;
    
    /**
     * @return Models
     */
    public static function instance()
    {
        if (is_null(self::$selfInstance)) {
            self::$selfInstance = new Models();
        }
        return self::$selfInstance;
    }
    
    /**
     * 返回指定的模型（大小写敏感）
     * @param string $model_name
     * @return mixed
     */
    public function __get(string $model_name): \myConf\BaseModel
    {
        if (!isset($this->_models[$model_name])) {
            $class_name = '\\myConf\\Models\\' . $model_name;
            $this->_models[$model_name] = new $class_name();
        }
        return $this->_models[$model_name];
    }
    
    /**
     * @param string $functionName
     * @param        $functionArguments
     *
     * @return BaseModel
     */
    public function __call(string $functionName, $functionArguments): \myConf\BaseModel
    {
        // Make the first character of the model uppercase.
        if ($functionName[0] >= 'a') { $functionName[0] -= 32; }
        if (!isset($this->_models[$functionName])) {
            $class_name = '\\myConf\\Models\\' . $functionName;
            $this->_models[$functionName] = new $class_name();
        }
        return $this->_models[$functionName];
    }

    private static $modelPapers = null;

    /**
     * Papers Operation.
     *
     * @return Models\Papers
     */
    public static function papers() : \myConf\Models\Papers {
        if (is_null(self::$modelPapers) === true) {
            self::$modelPapers = new \myConf\Models\Papers();
        }
        return self::$modelPapers;
    }

    private $modelConferences = null;

    public function conference() : \myConf\Models\Conference
    {
        if (is_null($this->modelConferences) === true) {
            $this->modelConferences = new \myConf\Models\Conference();
        }
        return $this->modelConferences;
    }
    
    private $modelUsers = null;
    
    public function users() : \myConf\Models\User
    {
        if (is_null($this->modelUsers))
        {
            $this->modelUsers = new \myConf\Models\User();
        }
        return $this->modelUsers;
    }
    
    private $modelReviewers = null;
    
    public function reviewers() : \myConf\Models\Reviewer
    {
        if (is_null($this->modelReviewers))
        {
            $this->modelReviewers = new \myConf\Models\Reviewer();
        }
        return $this->modelReviewers;
    }
}