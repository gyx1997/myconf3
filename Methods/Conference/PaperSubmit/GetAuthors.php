<?php
    
    
    namespace myConf\Methods\Conference\PaperSubmit;
    
    
    use myConf\Services;
    use myConf\Utils\Arguments;

    class GetAuthors extends \myConf\BaseMethod
    {
        public static function getAuthors()
        {
            // Get input and do validation.
            $scholarEmail = Arguments::getHttpArg('email');
            if (is_null($scholarEmail))
            {
                self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
                return false;
            }
            // Decode the request email from base64 string.
            $scholarEmail = base64_decode($scholarEmail);
            // Get the scholar data.
            $scholarData = Services::scholars()
                                   ->get($scholarEmail);
            // Return data.
            self::return(array('found' => !empty($scholarData),
                               'data' => $scholarData,
                         ));
        }
    }