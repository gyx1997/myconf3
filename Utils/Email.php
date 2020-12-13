<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/2/1
     * Time: 10:16
     */

    namespace myConf\Utils;

    use http\Client\Curl\User;

    class Email {

        private static $mail_service = 'SendCloud';

        private static $send_cloud_path = APPPATH . DIRECTORY_SEPARATOR . 'myConf' . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR . 'SendCloud' . DIRECTORY_SEPARATOR;

        /**
         * @var string SendCloud用户ID号
         */
        private static $send_cloud_user_id;

        /**
         * @var string SendCloud API Key
         */
        private static $send_cloud_api_key;

        /**
         * 初始化
         */
        public static function init() : void {
            $sendCloudConfig = \myConf\Utils\Config::get('send_cloud');
            if (isset($sendCloudConfig[ENVIRONMENT])) {
                self::$mail_service = 'SendCloud';
                self::$send_cloud_user_id = $sendCloudConfig[ENVIRONMENT]['user_id'];
                self::$send_cloud_api_key = $sendCloudConfig[ENVIRONMENT]['api_key'];
            } else {
                self::$mail_service = 'Unknown';
            }
        }

        public static function sendAccountVerificationEmail(string $from, string $from_name, string $to, string $subject, string $content)
        {
            if (self::canSend($to) === false)
            {
                return;
            }
            self::send_mail($from, $from_name, $to, $subject, $content);
        }
        
        public static function sendReviewInvitationEmail()
        {
        
        }
        
        /**
         * 通过合适的渠道投递一封电子邮件
         * @param string $from
         * @param string $to
         * @param string $subject
         * @param string $content
         * @param string $from_name
         */
        public static function send_mail(string $from, string $from_name, string $to, string $subject, string $content) {
            if (self::$mail_service === 'SendCloud') {
                require_once self::$send_cloud_path . 'SendCloud.php';
                require_once self::$send_cloud_path . 'util/HttpClient.php';
                require_once self::$send_cloud_path . 'util/Mail.php';
                require_once self::$send_cloud_path . 'util/Mimetypes.php';
                $sc = new \SendCloud(self::$send_cloud_user_id, self::$send_cloud_api_key, 'v2');
                $mail = new \Mail();
                $mail->setFrom($from);
                $mail->addTo($to);
                $mail->setFromName($from_name);
                $mail->setSubject($subject);
                $mail->setContent($content);
                $result = $sc->sendCommon($mail);
            } else if(self::$mail_service === 'CodeIgniterSMTP') {
                $result = NULL;
            }
            //调试模式
            $file = APPPATH . '/debug/' . str_replace($to, '_' , '@') . '-' .
                date('y-m-d',time()) . md5(time()) . '.html';
            $fcontent = "<html><body><h1>From $from_name ($from)</h1><h1>To $to</h1><h1>$subject</h1><p>$content</p></body></html>";
            file_put_contents($file, $fcontent);
            //file_put_contents($file . '.log', var_export($result));
        }
        
        private static function getTemplate($templateName)
        {

        }
        
        public static function sendPaperResultNotificationEmail()
        {
        
        }
        
        public static function canSend($targetEmail)
        {
            $bounceList = self::queryInvalidEmails('https://api.sendcloud.net/apiv2/bounce/list');
            $spamReportedList = self::queryInvalidEmails('https://api.sendcloud.net/apiv2/spamreported/list');
            $unsubscribeList = self::queryInvalidEmails('https://api.sendcloud.net/apiv2/unsubscribe/list');
            $invalidEmails = array_merge($bounceList, $spamReportedList, $unsubscribeList);
            return in_array($targetEmail, $invalidEmails);
        }
        
        private static function queryInvalidEmails(string $apiUrl)
        {
            // Initialize array of invalid emails.
            $invalidEmails = array();
            // Initialize authentication array.
            /*
            $sendCloudAuth = array(
                array(
                    'apiUser' => self::$send_cloud_user_id,
                    'apiKey' => self::$send_cloud_api_key,
                )
            );*/
            $sendCloudAuth = array(
                'apiUser' => 'myconf_app_trigger',
                'apiKey' => 'E8G9wHzTAmrtBHPn',
            );
            $apiResult = self::httpRequestSync($apiUrl,
                                                $sendCloudAuth,
                                                array('days' => 90));
            // If have data, add to invalid emails.
            if ($apiResult !== false &&
                $apiResult['statusCode'] === 200 &&
                $apiResult['info']['count'] > 0) {
                foreach($apiResult['info']['dataList'] as $email)
                {
                    $invalidEmails [] = isset($email['email']) ? $email['email'] : $email['receiver'];
                }
            }
            return $invalidEmails;
        }
    
        private static function httpRequestSync(string $url, array $auth = array(), array $parameters = array())
        {
            $paramFinal = array_merge($auth, $parameters);
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
            //curl_setopt($curlResource, CURLOPT_POSTFIELDS,$auth);
            curl_setopt($curlResource, CURLOPT_POSTFIELDS,$paramFinal);
            // Return data.
            curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);
        
            $result = curl_exec($curlResource);
            // Close the curl session.
            curl_close($curlResource);
            if ($result === false)
            {
                return false;
            }
            return json_decode($result, true);
        }

        
    }
    //初始化电子邮箱类
    Email::init();