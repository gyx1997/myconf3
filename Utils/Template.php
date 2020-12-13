<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/17
 * Time: 19:24
 */

namespace myConf\Utils;

use myConf\Exceptions\TemplateParseException;

define('TEMPLATE_BASE_PATH', APPPATH . 'myConf' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR);
define('TEMPLATE_COMPILED_PATH', CACHE_ROOT_DIR  . 'template' . DIRECTORY_SEPARATOR);

/**
 * Class Template
 *
 * @package myConf\Utils
 */
class Template
{
    /**
     * @param $templateFile
     */
    public static function setTemplate(string $templateFile)
    {
        $GLOBALS['myConf']['template'] = $templateFile;
    }
    
    /**
     * @return string
     */
    public static function getTemplate()
    {
        return $GLOBALS['myConf']['template'];
    }
    
    /**
     * @param string $source 模板源文件
     * @param bool $forceReCompile 是否强制重新编译
     * @return string
     * @throws TemplateParseException
     * @throws \myConf\Exceptions\TemplateNotFoundException
     */
    public static function load(string $source, bool $forceReCompile = false): string
    {
        $compiled_template_dir = TEMPLATE_COMPILED_PATH;
        !is_dir($compiled_template_dir) && @mkdir($compiled_template_dir, 0777, true);
        $md5_name = md5($source);
        $md5_name_prefix = substr($md5_name, 0, 16);
        $md5_name_suffix = substr($md5_name, 16);
        $compiled_template_dir .= $md5_name_prefix . DIRECTORY_SEPARATOR;
        !is_dir($compiled_template_dir) && @mkdir($compiled_template_dir, 0777, true);
        $target_tpl_file = $compiled_template_dir . $md5_name_suffix . '.tpl.php';
        $target_chk_file = $target_tpl_file . '.info';
        // Determine whether it needs re-compiling.
        if ($forceReCompile  === true
            || !file_exists($target_chk_file)
            || !file_exists($target_tpl_file)
            || $source !== file_get_contents($target_chk_file))
        {
            self::compile($source, $target_tpl_file);
        }
        return $target_tpl_file;
    }

    /**
     * 根据指定的动态参数渲染模板HTML
     * @param string $template_name
     * @param array $arguments
     * @return string
     * @throws TemplateParseException
     * @throws \myConf\Exceptions\TemplateNotFoundException
     */
    public static function render(string $template_name, array $arguments = array()): void
    {
        // Locate the template file.
        $target_template_file = TEMPLATE_BASE_PATH . $template_name;
        // Load template file.
        $template = self::load($target_template_file, ENVIRONMENT !== 'production');
        // Split variables.
        /** @noinspection PhpUnusedLocalVariableInspection */
        $status = $arguments['status'];
        $__data = $arguments['data'];
        // Extract from $__data.
        extract($__data);
        //foreach ($__data as $key => $val) {
        //    $$key = $val;
        //}
        // Clear redundant variables.
        unset($arguments);
        unset($template_name);
        unset($target_template_file);
        // Include template file.
        include($template);
    }

    /**
     * 编译模板
     * @param string $source
     * @param string $target
     * @throws TemplateParseException
     * @throws \myConf\Exceptions\TemplateNotFoundException
     */
    public static function compile(string $source, string $target): void
    {
        $source_content = self::parseTemplate($source, true);
        $source_content = '<?php defined(\'MY_CONF\') OR exit(\'No direct script access allowed\'); ?>' . PHP_EOL . $source_content;
        ENVIRONMENT === 'production' && $source_content = self::compress($source_content);
        file_put_contents($target, $source_content);
        file_put_contents($target . '.info', $source);
        function_exists('opcache_compile_file') && opcache_compile_file($target);
        return;
    }

    /**
     * @param string $include_item
     * @param bool $full_path
     * @return string
     * @throws TemplateParseException
     * @throws \myConf\Exceptions\TemplateNotFoundException
     */
    private static function parseTemplate(string $include_item, bool $full_path = false): string
    {
        $file = !$full_path ? TEMPLATE_BASE_PATH . $include_item : $include_item;
        // Replace special characters.
        $file = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file);
        $file = str_replace('-', '_', $file);
        $fileHtml = $file . '.html';
        if (!file_exists($fileHtml)) {
            $filePhp = $file . '.php';
            if (!file_exists($filePhp))
            {
                throw new \myConf\Exceptions\TemplateNotFoundException('TPL_NOT_FOUND',
                                                                       'Template "' . $include_item . '"  not found.'
                );
            }
            $file = $filePhp;
        }else {
            $file = $fileHtml;
        }
        $content = file_get_contents($file);
        $t = self::parseTags($content);
        foreach ($t['pattern'] as &$pattern) {
            $pattern = '`' . preg_quote($pattern, '`') . '`';
        }
        $content = preg_replace($t['pattern'], $t['replacement'], $content, 1);
        if (!isset($content))
            throw new TemplateParseException();
        return $content;
    }

    /**
     * @param string $content
     * @return array
     * @throws TemplateParseException
     */
    private static function parseTags(string &$content): array
    {
        //栈，用来追踪end标记
        $st = new \SplStack();
        //约定语义动作
        //函数原型：function(string &$str, \SplStack &$st) : string {}
        $tag_actions = array(
            'include' => function (string &$param_str, \SplStack &$st): string {
                $params = explode(' ', $param_str);
                if (strpos($param_str, ' ') === false) {
                    return self::parseTemplate($param_str);
                }
                throw new \myConf\Exceptions\TemplateParseException('TPL_PARSE_ERROR', 'Template Parse Error: {{@include}} must have only one parameter.');
            },
            'if' => function (string &$param_str, \SplStack &$st): string {
                $st->push('if');
                return '<?php if (' . $param_str . ') { ?>';
            },
            'switch' => function (string &$param_str, \SplStack &$st): string {
                if (strpos($param_str, ' ') !== false) {
                    throw new \myConf\Exceptions\TemplateParseException('TPL_PARSE_ERROR', 'Template Parse Error: {{@switch}} must have only one parameter.');
                }
                $st->push('switch');
                return '<?php switch (' . $param_str . '){ case null : {break;} ?>';
            },
            'case' => function (string &$param_str, \SplStack &$st): string {
                if (strpos($param_str, ' ') !== false) {
                    throw new \myConf\Exceptions\TemplateParseException('TPL_PARSE_ERROR', 'Template Parse Error: {{@case}} must have only one parameter.');
                }
                $st->push('case');
                return '<?php case ' . $param_str . ': ?>';
            },
            'for' => function (string &$param_str, \SplStack &$st): string {
                $parameters = explode(' ', $param_str);
                $param_count = count($parameters);
                if ($param_count === 3) {
                    $ret = '<?php foreach(' . $parameters[2] . ' as ' . $parameters[0] . '){ ?>';
                } else if ($param_count === 5) {
                    $ret = '<?php foreach(' . $parameters[2] . ' as ' . $parameters[4] . ' => ' . $parameters[0] . '){ ?>';
                } else {
                    throw new \myConf\Exceptions\TemplateParseException('TPL_PARSE_ERROR', 'Template Parse Error: {{@for}} must have this syntax: {{@for $a in $b}} or {{@for $a in $b key $k}}.');
                }
                $st->push('for');
                return $ret;
            },
            'eval' => function (string &$param_str, \SplStack &$st): string {
                $st->push('eval');
                return '<?php ';
            },
            'else' => function (string &$param_str, \SplStack &$st): string {
                return '<?php } else { ?>';
            },
            'elseif' => function (string &$param_str, \SplStack &$st): string {
                return '<?php } else if(' . $param_str . ') { ?>';
            },
            'end' => function (string &$param_str, \SplStack &$st): string {
                if ($st->isEmpty()) {
                    throw new \myConf\Exceptions\TemplateParseException('TPL_PARSE_ERROR', 'End tag mismatch.');
                }
                switch ($st->pop()) {
                    case 'eval':
                        return '?>';
                    case 'case':
                        return '<?php break; ?>';
                    default :
                        return '<?php } ?>';
                }
            }
        );
        $result = array();
        preg_match_all('/\{\{.*?\}\}/', $content, $matches);
        foreach ($matches[0] as $match_str) {
            $to_replace = '';
            $len = strlen($match_str);
            $prefix = $match_str[2];
            if ($prefix === '@') {
                //下面按照特殊标记处理
                $instruction_end = strpos($match_str, ' ');
                if ($instruction_end === FALSE) {
                    $param_str = '';
                    $instruction_name = substr($match_str, 3, $len - 5);
                } else {
                    $param_str = trim(substr($match_str, $instruction_end, $len - $instruction_end - 2));
                    $instruction_name = substr($match_str, 3, $instruction_end - 3);
                }
                if (isset($tag_actions[$instruction_name])) {
                    $to_replace = $tag_actions[$instruction_name]($param_str, $st);
                } else {
                    throw new TemplateParseException('TPL_PARSE_ERROR', 'Tag name "' . $instruction_name . '" invalid.');
                }
            } else if ($prefix === '$') {
                $to_replace = '<?php echo ' . substr($match_str, 2, $len - 4) . '; ?>';
            }
            $result [] = $to_replace;
        }
        return array('pattern' => $matches[0], 'replacement' => $result);
    }

    public static function clear_compiled_template(): void
    {
        File::clear_directory_with_files(TEMPLATE_COMPILED_PATH, false);
        return;
    }

    private static function compress(string $template_str): string
    {
        $chunks = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $template_str, -1, PREG_SPLIT_DELIM_CAPTURE);
        $compress = '';
        foreach ($chunks as $c) {
            if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
                $c = substr($c, 19, strlen($c) - 19 - 20);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 12)) == '<nocompress>') {
                $c = substr($c, 12, strlen($c) - 12 - 13);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) { // JS代码，包含“//”注释的，单行代码不处理
                $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
                $c = '';
                foreach ($tmps as $tmp) {
                    if (strpos($tmp, '//') !== false) { // 对含有“//”的行做处理
                        if (substr(trim($tmp), 0, 2) == '//') { // 开头是“//”的就是注释
                            continue;
                        }
                        $chars = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
                        $is_quot = $is_apos = false;
                        foreach ($chars as $key => $char) {
                            if ($char == '"' && !$is_apos && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_quot = !$is_quot;
                            } elseif ($char == '\'' && !$is_quot && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_apos = !$is_apos;
                            } elseif ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
                                $tmp = substr($tmp, 0, $key); // 不是字符串内的就是注释
                                break;
                            }
                        }
                    }
                    $c .= $tmp;
                }
            }

            $c = preg_replace('/[\\n\\r\\t]+/', ' ', $c); // 清除换行符，清除制表符
            $c = preg_replace('/\\s{2,}/', ' ', $c); // 清除额外的空格
            $c = preg_replace('/>\\s</', '> <', $c); // 清除标签间的空格
            $c = preg_replace('/\\/\\*.*?\\*\\//i', '', $c); // 清除 CSS & JS 的注释
            $c = preg_replace('/<!--[^!]*-->/', '', $c); // 清除 HTML 的注释
            $compress .= $c;
        }
        return $compress;
    }
}