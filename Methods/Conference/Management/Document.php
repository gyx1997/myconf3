<?php


namespace myConf\Methods\Conference\Management;


use myConf\Errors;
use myConf\Services;
use myConf\Utils\Arguments;

class Document extends \myConf\BaseMethod
{
    /**
     * Document constructor.
     */
    public function __construct()
    {
        return parent::__construct();
    }

    /**
     * @requestUrl /conference/{confUrl}/management/document/?do=edit
     * @httpGet int id
     */
    public static function showEditPage()
    {
        /* Get the input data from http request and do validation. */
        $documentId = Arguments::getHttpArg('id');
        if (is_null($documentId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        /* Check the target document's existence. */
        Services::conferences()->category()->Documents()->documentExist($documentId);
        /* Return the valid document id or error (if document not exists). */
        self::return(array('document_id' => $documentId));
    }

    /**
     * @requestUrl /conference/{confUrl}/management/document/?do=get
     * @httpGet int id
     */
    public static function getDocumentData()
    {
        /* Get the input data from http request and do validation. */
        $documentId = Arguments::getHttpArg('id');
        if (is_null($documentId))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
            return;
        }
        $data = Services::conferences()->category()->Documents()->getDocumentData($documentId);
        self::return($data);
    }

    /**
     * @requestUrl /conference/{confUrl}/management/document/?do=submit
     * @httpPost int document_id
     * @httpPost string document_html
     */
    public static function submitDocumentData()
    {
        //TODO document's title in POST form unused.

        /* Get the input data from http request and do validation. */
        $documentId = Arguments::getHttpArg('document_id', true);
        $documentHtml = Arguments::getHttpArg('document_html', true);
        if (is_null($documentId) || is_null($documentHtml))
        {
            self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) in post form missing.');
            return;
        }
        $documentId = intval($documentId);
        Services::conferences()->category()->Documents()->submitContent($documentId, '', $documentHtml);
        self::return();
    }

    public static function uploadDocumentAttachment()
    {

    }
}