<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2019/4/23
     * Time: 0:16
     */

    namespace myConf\Utils;

    /**
     * Class Debugger
     * @package myConf\Utils
     * @author _g63<522975334@qq.com>
     * @version 2019.5
     */
    class Debugger {

        private static function export_array(array $x, int $indent_level = 0, bool $html = TRUE) : string {
            $rs = '';
            if (empty($x)) {
                return '[Empty Array]';
            }
            if ($html === TRUE) {
                foreach ($x as $k => $v) {
                    if (is_array($v)) {
                        $i = $indent_level * 4;
                        while ($i--) {
                            $rs .= '&nbsp;';
                        }
                        self::export_array($v, $indent_level + 1, $html);
                    }
                }
            }
            return $rs;
        }

        /**
         * @param $x
         * @param string $f
         * @param int $l
         */
        public static function print_and_stop($x, string $f, int $l) : void {
            if (ENVIRONMENT === 'development') {
                echo sprintf('file %s, line %d <br/>', $f, $l);
                print_r($x);
                exit(0);
            }
        }
    }