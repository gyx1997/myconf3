<?php
    
    
    namespace myConf\Models\Objects;
    
    
    use myConf\Models\Exceptions\ResourceNotFoundException;

    /**
     * Class Conferences
     *
     * @package myConf\Models\Objects
     *
     * @property-read int $Id
     */
    class Conference extends \myConf\Models\ObjectModel
    {
        private $conferenceId;
        private $conferenceData;
    

        
        public function __construct($initData)
        {
            parent::__construct($initData);
            // Decide uniqueId type
            if (is_int($initData) === true)
            {
                // Get the conference by conference_id
                if ($this->tables()->Conferences->exist(strval($initData)) === false)
                {
                    throw new ResourceNotFoundException("The requested resource (conference) with uniqueId $initData not found.");
                }
                
                $this->conferenceData = $this->tables()->Conferences->get(strval($initData));
                $this->conferenceId = $initData;
            }
            else
            {
                // Get the conference by conference_url
                $this->conferenceData = $this->tables()->Conferences->get_by_url($initData);
                $this->conferenceId = $this->conferenceData['conference_id'];
            }
        }
    
        public function __get($key)
        {
        
        }
        
        public function __set($key, $value)
        {
            switch($key)
            {
                case 'StartTime':
                    $this->conferenceData['conference_start_time'] = $value;
                    break;
                case 'Status':
                case 'PaperDeadline':
                case 'PaperSubmitEnabled':
                case 'BannerFile':
                case 'LogoFile':
                case 'Name':
                case 'Host':
            }
        }
        
        
        protected function packFields() : array
        {
            return array(
                'conference_start_time' => $this->startTime,
                'conference_paper_submit_end' => $this->paperSubmissionDeadline,
            );
        }
        
        protected function unpackFields(array $rawData)
        {
            $this->startTime = $rawData['conference_start_time'];
            $this->paperSubmissionDeadline = $rawData['conference_paper_submit_end'];
        }
    
        // Writable fields
        
        private $startTime;
        /**
         * @return int
         */
        public function getStartTime()
        {
            return $this->startTime;
        }
        /**
         * @param int $startTime
         */
        public function setStartTime($startTime)
        {
            $this->startTime = $startTime;
        }
        
        private $paperSubmissionDeadline;
        /**
         * @return int
         */
        public function getPaperSubmissionDeadline()
        {
            return $this->paperSubmissionDeadline;
        }
        /**
         * @param int $deadline
         */
        public function setPaperSubmissionDeadline($deadline)
        {
            $this->paperSubmissionDeadline =  $deadline;
        }
        
        private $usePaperSubmssion;
        private $status;
        private $name;
        private $bannerImage;
        private $logoImage;
        private $host;
        
        // Readonly fields
        private $id;
        private $url;
        

        
        public function save()
        {
            // TODO: Implement save() method.
        }
    }