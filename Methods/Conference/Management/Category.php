<?php


namespace myConf\Methods\Conference\Management;

use myConf\BaseMethod;
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors as Err;
use myConf\Services;
use myConf\Utils\Arguments;

/**
 * Methods for category operations.
 *
 * @package myConf\Methods\Conferences\Management
 */
class Category extends BaseMethod
{
    /**
     * Category constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/?do=new
     * @httpPostParam string category_name_text
     * @httpPostParam string category_type_id
     */
    public static function addCategory()
    {
        /* Initialize input. */
        $conferenceId = self::getGlobal('conference_id');
        /* Initialize input from http $_GET and $_POST and do validation. */
        $newCategoryText = Arguments::getHttpArg('category_name_text', true);
        $newCategoryType = Arguments::getHttpArg('category_type_id', true);
        if (is_null($newCategoryText) || is_null($newCategoryType))
        {
            self::retArray(400, -1, 'REQUEST_PARAM_INVALID', array('description' => 'Necessary form field(s) missing.'));
            return;
        }
        /* Trim the spaces. */
        $newCategoryText = trim($newCategoryText);
        $newCategoryType = trim($newCategoryType);
        /* Add a new category. */
        Services::conferences()->category()->add($conferenceId, $newCategoryText, $newCategoryType);
        /* Error handler. */
        self::return();
        return;
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/
     */
    public static function showCategoryList()
    {
        /* Initialize input. */
        $conferenceId = self::getGlobal('conference_id');
        $cat_data = Services::conferences()->category()->getList($conferenceId);
        /* Error handler. */
        $errNo = Err::getLastError();
        $errStr = isset (E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        if ($errNo > 0)
        {
            /* $errNo > 0 means an error occurred. */
            self::retArray(200, $errNo, $errStr);
            return;
        }
        self::retSuccess(array('category_list' => $cat_data));
        return;
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/?do=rename
     * @httpPostParam int category_id
     * @httpPostParam string category_name_text
     */
    public static function renameCategory()
    {
        /* Get input data from http post request. */
        $categoryId = Arguments::getHttpArg('category_id', true);
        $newCategoryName = Arguments::getHttpArg('category_name_text', true);
        /* Validate the post form. */
        if (is_null($categoryId) || is_null($newCategoryName))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) in form missing.');
            return;
        }
        /* Rename the category. */
        Services::conferences()->category()->rename($categoryId, $newCategoryName);
        /* Error handler. */
        $errNo = Err::getLastError();
        $errStr = isset (E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        if ($errNo > 0)
        {
            /* $errNo > 0 means an error occurred. */
            self::retArray(200, $errNo, $errStr);
            return;
        }
        self::retSuccess();
        return;
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/?do=remove
     * @httpGetParam int cid
     */
    public static function removeCategory()
    {
        /* Initialize variables. */
        $categoryId = Arguments::getHttpArg('cid');
        if (is_null($categoryId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary Parameter(s) missing.');
        }
        $categoryId = intval($categoryId);
        /* Remove the specified category. */
        Services::conferences()->category()->remove($categoryId);
        /* Error handler. */
        $errNo = Err::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        if ($errNo > 0)
        {
            /* $errNo > 0 means an error occurred. */
            self::retArray(200, $errNo, $errStr);
            return;
        }
        self::retSuccess();
        return;
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/?do=up
     * @httpGetParam int cid
     */
    public static function moveUpCategory()
    {
        /* Initialize variables. */
        $categoryId = Arguments::getHttpArg('cid');
        if (is_null($categoryId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary Parameter(s) missing.');
            return;
        }
        $categoryId = intval($categoryId);
        /* Move up the specified category. */
        Services::conferences()->category()->moveUp($categoryId);
        /* Error handler. */
        $errNo = Err::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        if ($errNo > 0)
        {
            /* $errNo > 0 means an error occurred. */
            self::retArray(200, $errNo, $errStr);
            return;
        }
        self::retSuccess();
        return;
    }

    /**
     * @requestUrl /conference/{confUrl}/management/category/?do=down
     * @httpGetParam int cid
     */
    public static function moveDownCategory()
    {
        /* Initialize variables. */
        $categoryId = Arguments::getHttpArg('cid');
        if (is_null($categoryId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary Parameter(s) missing.');
            return;
        }
        $categoryId = intval($categoryId);
        /* Move down the specified category. */
        Services::conferences()->category()->moveDown($categoryId);
        /* Error handler. */
        $errNo = Err::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        if ($errNo > 0)
        {
            /* $errNo > 0 means an error occurred. */
            self::retArray(200, $errNo, $errStr);
            return;
        }
        self::retSuccess();
        return;
    }

    private static function validateCategoryId()
    {

    }

    private static function checkError()
    {

    }
}