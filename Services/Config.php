<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 18:37
 */

namespace myConf\Services;


class Config extends \myConf\BaseService
{
    /**
     * Config constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function get_footer(): string
    {
        return $this->Models->Config->get('footer1');
    }

    public function get_mitbeian(): string
    {
        return $this->Models->Config->get('mitbeian');
    }

    public function get_title(): string
    {
        return $this->Models->Config->get('title');
    }
}