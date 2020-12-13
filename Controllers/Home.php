<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/1/8
     * Time: 0:12
     */

    namespace myConf\Controllers;

    use myConf\Utils\Email;
    use myConf\Utils\HttpRequest;

    class Home extends \myConf\BaseController {

        /**
         * @throws \myConf\Exceptions\SendRedirectInstructionException
         */
        public function index() : void {
            $this->RedirectTo('/conference/2020CSRSWTC/');
        }
        
        public function test() {
            echo Email::canSend('s');
            exit();
        }
        
        public function test2() {
            Email::updateBlacklist('',0,0);
        }
    }