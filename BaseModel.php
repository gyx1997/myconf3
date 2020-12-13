<?php


namespace myConf;

use myConf\Models\Attachment;

/**
 * Class BaseModel
 *
 * @package myConf
 * @author _g63<522975334@qq.com>
 * @version 2019.1
 * @property-read \myConf\Tables $Tables
 */
class BaseModel
{
    /**
     * @var null|Tables
     */
    private $PaperSession;
    
    /**
     * @return Tables
     */
    protected function tables() {
        return $this->_data_table;
    }
    
    /**
     * @var \myConf\Tables 数据表操作对象实例
     */
    private $_data_table;

    /**
     * myConf_BaseModel constructor.
     */
    public function __construct() {
        $this->_data_table = new Tables();
    }

}