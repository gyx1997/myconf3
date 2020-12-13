<?php
    
    
    namespace myConf\Models;
    
    
    class CollectionModel extends \myConf\BaseModel
    {
        public function __construct()
        {
            parent::__construct();
        }
        
        public abstract function get($uniqueId);
    }