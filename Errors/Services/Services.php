<?php


namespace myConf\Errors\Services;

use myConf\Errors\Services\Services as E_SERVICE_PAPER;

/**
 * Provides error number constants for service layer class Paper.
 *
 * @package myConf\Errors\Services
 */
class Services
{
    /**
     * Represents the specified paper does not exist.
     */
    public const E_PAPER_NOT_EXISTS = 0x10000001;
    /**
     * Represents the specified paper id has already been occupied, which means the paper has already existed.
     */
    public const E_PAPER_ALREADY_EXISTS = 0x10000002;
    /**
     * Represents the status of the given paper is invalid.
     */
    public const E_PAPER_STATUS_INVALID = 0x10000003;
    /**
     * Represents the paper's $authors array is empty.
     */
    public const E_PAPER_AUTHORS_EMPTY = 0x10000004;
    /**
     * Represents the conference with given url or id does not exist.
     */
    public const E_CONFERENCE_NOT_EXISTS = 0x10200001;
    /**
     * Represents the category not exists.
     */
    public const E_CONFERENCE_CATEGORY_NOT_EXISTS = 0x10200002;
    /**
     * Represents the conference document does not exist.
     */
    public const E_CONFERENCE_DOCUMENT_NOT_EXISTS = 0x10200003;
    /**
     * Represents an error occurred during adding a scholar.
     */
    public const E_ADD_SCHOLAR_FAILED = 0x10000006;
    /**
     * Represents an error occurred during adding a paper.
     */
    public const E_ADD_PAPER_FAILED = 0x10000007;
    /**
     * Represents an error occurred during upload the banner.
     */
    public const E_BANNER_UPLOAD_FAILED = 0x10000008;
    
    public const E_SUGGESTED_SESSION_EMPTY_STRING = 0x10000009;
    
    public const E_SUGGESTED_SESSION_DUPLICATE = 0x1000000a;
    
    public const E_SUGGESTED_SESSION_NOT_EXISTS = 0x1000000b;
    
    public const E_SUGGESTED_SESSION_IN_USE = 0x1000000c;
    
    /**
     *
     */
    public const E_REVIEWER_ALREADY_EXISTS = 0x1000000d;
    /**
     * Represents that the user does not select a banner image for the conference.
     */
    public const E_BANNER_UNSET = 0x1000000e;
    
    public const E_REVIEW_RECORD_NOT_EXISTS = 0x1000000f;
    /**
     * Represents that the specified user does not exist.
     */
    public const E_USER_NOT_EXISTS = 0x10100001;
    /**
     * Represents password error.
     */
    public const E_USER_PASSWORD_ERROR = 0x10100002;
    /**
     * Represents that the current user's login has been frozen due to too many failures.
     */
    public const E_USER_LOGIN_FROZEN = 0x10100003;
    /**
     * Represents that the user has already logged in.
     */
    public const E_USER_ALREADY_LOGIN = 0x10100004;
    /**
     * Represents that the user has not logged in.
     */
    public const E_USER_NOT_LOGIN = 0x10100005;
    /**
     * Represents that the user has already registered.
     */
    public const E_USER_ALREADY_REGISTERED = 0x10100006;
    /**
     * Represents that the user has not joint in the given conference.
     */
    public const E_USER_NOT_JOINT_IN_CONFERENCE = 0x10100007;

    public const E_UPLOAD_FILE_NOT_SELECTED = 0x1fe00001;

    public const E_UPLOAD_FILE_UPLOAD_ERROR = 0x1fe00002;

    public const E_ATTACHMENT_NOT_FOUND = 0x1fe00003;

    /**
     * Represents that the input chapter incorrect.
     */
    public const E_MISC_CAPTCHA_ERROR = 0x1ff00001;

    public const E_MISC_HASH_KEY_ERROR = 0x1ff00002;


    /**
     * Error message string mapper.
     */
    public const errorMessage = array(
        0 => 'SUCCESS',
        self::E_ADD_PAPER_FAILED      => 'PAPER_ADD_FAILED',
        self::E_ADD_SCHOLAR_FAILED    => 'SCHOLAR_ADD_FAILED',
        self::E_CONFERENCE_NOT_EXISTS => 'CONF_NOT_FOUND',
        self::E_CONFERENCE_CATEGORY_NOT_EXISTS => 'CATEGORY_NOT_FOUND',
        self::E_CONFERENCE_DOCUMENT_NOT_EXISTS => 'DOC_NOT_FOUND',
        self::E_PAPER_ALREADY_EXISTS  => 'PAPER_ALREADY_EXISTS',
        self::E_PAPER_AUTHORS_EMPTY   => 'AUTHOR_EMPTY',
        self::E_PAPER_NOT_EXISTS      => 'PAPER_NOT_FOUND',
        self::E_PAPER_STATUS_INVALID  => 'PAPER_STATUS_INVALID',
        self::E_BANNER_UPLOAD_FAILED  => 'BANNER_UPLOAD_FAILED',
        self::E_USER_NOT_EXISTS => 'USER_EMAIL_ERROR',
        self::E_USER_PASSWORD_ERROR => 'PASSWORD_ERROR',
        self::E_USER_LOGIN_FROZEN => 'LOGIN_FROZEN',
        self::E_USER_ALREADY_LOGIN => 'ALREADY_LOGIN',
        self::E_USER_NOT_LOGIN => 'NOT_LOGIN',
        self::E_USER_ALREADY_REGISTERED => 'EMAIL_EXISTS',
        self::E_MISC_CAPTCHA_ERROR => 'CAPTCHA_ERR',
        self::E_MISC_HASH_KEY_ERROR => 'HASH_KEY_ERROR',
        self::E_UPLOAD_FILE_NOT_SELECTED => 'FILE_NOT_SELECTED',
        self::E_UPLOAD_FILE_UPLOAD_ERROR => 'FILE_UPLOAD_ERROR',
        self::E_USER_NOT_JOINT_IN_CONFERENCE => 'USER_NOT_JOINT_IN_CONFERENCE',
        self::E_ATTACHMENT_NOT_FOUND => 'ATTACHMENT_NOT_FOUND',
        self::E_SUGGESTED_SESSION_DUPLICATE => 'SUGGESTED_SESSION_DUPLICATE',
        self::E_SUGGESTED_SESSION_EMPTY_STRING => 'SUGGESTED_SESSION_EMPTY',
        self::E_SUGGESTED_SESSION_NOT_EXISTS => 'SUGGESTED_SESSION_NOT_FOUND',
        self::E_SUGGESTED_SESSION_IN_USE => 'SUGGESTED_SESSION_IN_USE',
        self::E_REVIEWER_ALREADY_EXISTS => 'REVIEWER_ALREADY_EXISTS',
        self::E_REVIEW_RECORD_NOT_EXISTS => 'REVIEW_NOT_EXISTS',
    );
}