<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 15:17
     */

    use myConf\Errors;
    use myConf\Exceptions\ClassNotFoundException;
    use myConf\Exceptions\HttpStatusException;
    use myConf\Utils\Template;
    // Define MY_CONF constant.
    define('MY_CONF', true);
    //
    define('OUTPUT_VAR_NONE', 0);
    define('OUTPUT_VAR_HTML_ONLY', 1);
    define('OUTPUT_VAR_JSON_ONLY', 2);
    // When the type of one variable is set to 3 which binary representation
    // is 11 in 2 bits, it would be output in both page request and json request. */
    define('OUTPUT_VAR_ALL', 3);
    // Http status definitions.
    define('HTTP_STATUS_CODE', array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        // 2xx success.
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // 3xx redirect.
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        // 4xx errors.
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // 5xx errors.
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        511 => 'Network Authentication Required',
    ));
    
    // Class autoload registration.
    spl_autoload_register(function ($class_name) {
        $file_to_load = APPPATH . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        if (file_exists($file_to_load))
        {
            include $file_to_load;
            return true;
        }
        return false;
    });
    
    // Static classes initialization.
    \myConf\Utils\DB::init();
    \myConf\Utils\Session::init();
    \myConf\Utils\Email::init();
    \myConf\Utils\Arguments::init();
    
    // Initialize super global variable $GLOBAL.
    $GLOBALS['myConf'] = array(
        // $GLOBALS['myConf']['data'] is an array which stores common global variables.
        'data' => array(),
        // $GLOBALS['myConf']['error'] is an array which stores data of the last
        // occurred error. It is defined with keys 'errNo', 'errLayer',
        // 'errStr' and 'errDesc'.
        'err' => array(
            'internal_code' => 0,
            'http_code' => 200,
            'status_str' => 'SUCCESS',
            'description' => '',
        ),
        // $GLOBALS['myConf']['ret'] is an array which stores data to response.
        // It should have keys 'httpCode', 'statusCode', 'status' and 'data'.
        // All additional data should be put in $GLOBALS['data'].
        // If its not an ajax request, these data would be embedded into the html page. */
        'ret' => NULL,
        // $GLOBALS['myConf']['template'] is a string which will be rendered.
        // Note that ajax requests do not need template(s).
        // If template is empty string, default template will be used. 
        'template' => '',
    );
    
    /**
     * Function for error handling.
     * @param string $message Error message to display.
     * @param int $code Error code
     */
    function handle_error($message, $code)
    {
        try
        {
            // Make data array of error page.
            $errData = array(
                'message' => $message,
                'status' => strval($code) . ' ' . HTTP_STATUS_CODE[$code],
                'date' => date('M jS, Y',
                               time())
            );
            http_response_code($code);
            // Render error page.
            Template::render('/Common/Error', array('data' => $errData));
        }
        catch (\Throwable $e)
        {
            // Fatal error which cannot be processed.
            echo 'Fatal error.';
        }
    }
    
    /**
     * @param $status
     */
    function shutdown($status)
    {
        session_commit();
        exit($status);
    }
    
    /**
     * @param int $visibilityMode
     *
     * @return array
     */
    function get_output_with_filter(int $visibilityMode) {
        // Define $retVal array.
        $retVal = array(
            'httpCode' => $GLOBALS['myConf']['ret']['httpCode'],
            'status' => $GLOBALS['myConf']['ret']['status'],
            'statusCode' => $GLOBALS['myConf']['ret']['statusCode'],
        );
        // Get data from global return data zone.
        $data = $GLOBALS['myConf']['ret']['data'];
        foreach ($data as $dataKey => $dataItem)
        {
            // Use bit operation AND to get the visibility mode.
            if (($dataItem['type'] & $visibilityMode) > 0)
            {
                if (isset($dataItem['outside']) === true
                    && $dataItem['outside'] === true)
                {
                    // This data item need to be shown outside the data key.
                    $retVal[$dataKey] = $dataItem['value'];
                }
                else
                {
                    // Otherwise, it should inside 'data' key.
                    $retVal['data'][$dataKey] = $dataItem['value'];
                }
            }
        }
        // Return the processed data ($retVal).
        return $retVal;
    }
    
    /**
     * If this function is called, only Json will be returned.
     */
    function request_declare_as_ajax()
    {
        if (defined('REQUEST_IS_AJAX') === false)
        {
            define('REQUEST_IS_AJAX', true);
        }
    }
    
    /**
     * @return bool True if this request is ajax request (or has
     *              been declared as ajax request). False otherwise.
     */
    function request_is_ajax()
    {
        $CI = &get_instance();
        return defined('REQUEST_IS_AJAX') ||
            $CI->input->get('ajax') === 'true'
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }
    // Get CodeIgniter super object.
    $CI = &get_instance();
    
    try
    {
        // Define alias (reference) of $GLOBAL['ret'].
        $retData = &$GLOBALS['myConf']['ret'];
        // Load controller.
        $controllerClassName = ucfirst(strtolower($CI->uri->segment(1, '')));
        $controllerClassName === '' && $controllerClassName = 'Home';
        $controllerFullClassName = DIRECTORY_SEPARATOR
            . 'myConf'
            . DIRECTORY_SEPARATOR
            . 'Controllers'
            . DIRECTORY_SEPARATOR
            . $controllerClassName;
        
        // Check the controller's existence.
        if (class_exists($controllerFullClassName) === false)
        {
            // Controller class not found, return 404 error.
            handle_error('Requested controller class not found.', 404);
            shutdown(-1);
        }
        // Get the instance of controller.
        $controller = new $controllerFullClassName;
        // Run the controller if making an instance of the controller succeed.
        $controller->run();
        // Check whether an error occurred.
        if (Errors::getLastError() > 0)
        {
            // Set the return data with the error.
            $retData = array(
                'httpCode'   => $GLOBALS['myConf']['err']['http_code'],
                'statusCode' => $GLOBALS['myConf']['err']['internal_code'],
                'status'     => $GLOBALS['myConf']['err']['status_str'],
                'data'       => array(
                    'description' => array(
                        'type'  => OUTPUT_VAR_ALL,
                        'value' => $GLOBALS['myConf']['err']['description']
                    ),
                ),
            );
        }
        // Check whether the return data has been set.
        if (is_null($retData)) {
            // If it is unset, trigger a notice.
            trigger_error('Controller method result data undefined.', E_USER_WARNING);
            // Then returns http 500 error.
            $retData = array(
                'httpCode'   => 500,
                'statusCode' => -1,
                'status'     => 'RET_VAL_UNDEFINED',
                'data'       => array('description' => array('type' => OUTPUT_VAR_ALL, 'value' => 'Return value undefined.')),
            );
        }
        // Check http errors.
        if ($retData['httpCode'] >= 400)
        {
            // Http response code >= 400 means unrecoverable error occurred.
            handle_error($retData['data']['description']['value'],
                         $retData['httpCode']);
            log_message('ERROR',
                        '[DEBUG] System Defined error occurred. ' . PHP_EOL
                        . '								[INFO]  ' .
                        $retData['httpCode'] . ', ' . dechex($retData['statusCode']) . ', ' . $retData['status'] . ', ' .
                        $retData['data']['description']['value']);
            shutdown(-1);
        }
        else if($retData['httpCode'] >= 300)
        {
            // Http response code 3xx means redirect required.
            header('location:' . $retData['data']['urlRedirectTo']['value']);
            shutdown(0);
        }
        // If there are not any http errors (http 200 returned), add output data.
        if (request_is_ajax() === true)
        {
            // Is ajax request, Json will be returned.
            header('Content-Type:application/json;charset=utf-8');
            $result = get_output_with_filter(OUTPUT_VAR_JSON_ONLY);
            echo json_encode($result);
        }
        else
        {
            $result = get_output_with_filter(OUTPUT_VAR_HTML_ONLY);
            // Get static files domain.
            $result['data']['StaticDomain'] = '';
            $result['data']['AttachmentDomain'] = '';
            // If specified template is set, use it.
            $specialTemplate = Template::getTemplate();
            if (strlen($specialTemplate) > 0)
            {
                $templateName = $specialTemplate;
            }
            else
            {
                $templateName = $controller->getTemplateName();
            }
            // Return html page rendered from template.
            Template::render($templateName, $result);
        }
        shutdown(0);
    } catch (\myConf\Exceptions\SendRedirectInstructionException $e) {
        //跳转指令
        header('location:' . $e->getRedirectURL());
    }
    catch(\myConf\Exceptions\SendExitInstructionException $e)
    {
        // Nothing need to be done here.
    }
    catch (ClassNotFoundException $e)
    {
        // 控制器没有找到，理论上是返回404的
        handle_error('The path you requested was not found.', 404);
    }
    catch (\Throwable $e)
    {
        // Php errors occurred in controller.
        $error_log_str =
            'Details --> { ' . PHP_EOL .
            '									RequestURL => ' . \myConf\Utils\Env::get_current_url() . PHP_EOL .
            '									Message => ' . $e->getMessage() . PHP_EOL .
            '									File => ' . $e->getFile() . PHP_EOL .
            '									Line => ' . $e->getLine() . PHP_EOL .
            '									Trace => ' . PHP_EOL . '{ ' . $e->getTraceAsString() . PHP_EOL .
            '								}'. PHP_EOL . '}' . PHP_EOL
        ;
        log_message('ERROR', '[DEBUG] A php error occurred. ' . PHP_EOL . '								[ERROR] ' . $error_log_str);
        $str = 'A fatal error occurred that your request could not be processed properly. We are so sorry for the inconvenience we have caused. <br/> Critical Information has been written down to our logging system to help us analyze and solve this problem. <br/>';
        if (ENVIRONMENT === 'production')
        {
            handle_error($str, 500);
        }
        else
        {
            $trace_str = '';
            foreach ($e->getTrace() as $trace) {
                $trace_str .= '<div><span style="font-family:Consolas;">' . (isset($trace['class']) ? $trace['class'] : '') . '::' . $trace['function'] . '</span><p>Line ' . $trace['line'] . ' in File ' . $trace['file'] . '</p></div>';
            }
            handle_error($str . '<div style="border: 1px #333333 solid; padding: 10px;"><h3>Debug Information</h3><p>' .
                         $e->getMessage() . '</p><p> At Line : <strong>' . $e->getLine() . '</strong> , in File : ' . $e->getFile() . '</p><p> <strong>BackTrace</strong> : </p>' . $trace_str . '</p></div>', 500);
        }
        
    }
    finally
    {
        shutdown(-1);
    }