<?php
    
    
    namespace myConf\Utils;
    
    
    class Misc
    {
        public static function generateUsername($email, $password)
        {
            $emailPrefix = substr(explode('@',
                                          $email)[0],
                                  0,
                                  17);
            return $emailPrefix . '-' . substr(md5($email . $password . strval(time())),
                                               0,
                                               32 - strlen  ($emailPrefix) - 1);
        }
    }