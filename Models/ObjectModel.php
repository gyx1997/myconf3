<?php
    
    
    namespace myConf\Models;
    
    
    abstract class ObjectModel extends \myConf\BaseModel
    {
        private $objectUniqueId;
    
        /**
         * ObjectModel constructor.
         *
         * @param int|array $initData The object's unique identifier.
         */
        public function __construct($initData)
        {
            // Call parent constructor first.
            parent::__construct();
            // If $uniqueIndex is an array, it means the composite primary key.
            $this->objectUniqueId = $initData;
        }
    
        /**
         * Returns the unique identifier of this object.
         * @return array|int
         */
        protected function uniqueId()
        {
            return $this->objectUniqueId;
        }
        
        public abstract function save();
    
        protected abstract function packFields() : array;
    
        protected abstract function unpackFields(array $rawData);
    }