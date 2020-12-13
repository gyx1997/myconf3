<?php
    
    
    namespace myConf\Utils;
    
    
    class Paging
    {
        /**
         * Calculate the paging parameters for MySQL limit clause.
         * @param     $itemCount
         * @param int $requestedPageNum
         * @param int $itemNumPerPage
         *
         * @return array
         */
        public static function calc($itemCount, $requestedPageNum = 1, $itemNumPerPage = 10)
        {
            return array('start' => ($requestedPageNum - 1) * $itemNumPerPage,
                         'limit' => $itemNumPerPage,
                         'page_count' => ceil((float)$itemCount / $itemNumPerPage));
        }
    }
    