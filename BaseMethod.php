<?php


namespace myConf;


use myConf\Errors as Err;
use myConf\Errors\Services\Services as E_SERVICE;

class BaseMethod extends TopLayers
{
    /**
     * @var \CI_Controller Codeigniter controller super object.
     */
    private $ci = null;
    private $services = null;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->services = new Services();
    }

    /**
     * @return null|\CI_Controller
     */
    protected function CI() {
        return $this->ci;
    }

    /**
     * Get all available services.
     * @return Services
     */
    protected function Services() {
        return $this->services;
    }
}