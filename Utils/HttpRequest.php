<?php
    
    
    namespace myConf\Utils;
    
    
    use myConf\Errors;

    class HttpRequest
    {
        public static function httpGetSync(string $url, array $parameters = array())
        {
            // Transform key-value parameters to query string.
            $querySegments = array();
            foreach ($parameters as $key => $value)
            {
                $querySegments []= $key . '=' . strval($value);
            }
            $queryStr = implode('&', $parameters);
            $urlWithQueryString = $url . '?' . $queryStr;
            $curlResource = curl_init();
            if ($curlResource === false) {
                return false;
            }
            // Set Url.
            curl_setopt($curlResource, CURLOPT_URL, $url);
            // Set timeout.
            curl_setopt($curlResource, CURLOPT_TIMEOUT, 2);
            // Disable SSL Verification.
            curl_setopt($curlResource, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curlResource, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlResource, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curlResource, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curlResource, CURLOPT_POSTFIELDS,$parameters);
            // Return data.
            curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($curlResource);
            // Close the curl session.
            curl_close($curlResource);
            return $result;
        }
    }
    
    