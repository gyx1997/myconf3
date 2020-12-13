<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/4/21
     * Time: 21:19
     */

    namespace myConf\Controllers;

    class Member extends \myConf\BaseController {

        /**
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function query() {
            $data = [];
            switch($this->actionName) {
                case 'scholar':
                    {
                        $data = $this->Services->Scholar->get(base64_decode($this->input->get('email')));
                        break;
                    }
                case 'user-full':
                    {
                        break;
                    }
            }
            $this->addRetVariables([
                'status' => 'SUCCESS',
                'found' => !empty($data),
                'data' => $data,
            ]);
        }

    }