<?php

namespace myConf\Services\Conference;
/* Imports of parent class. */

use myConf\BaseService;

/* Imports for error handler. */

use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors\Services\Services as E_SERVICES;
use myConf\Errors;
use myConf\Services\Conference\Category\Document;
use myConf\Utils\Arguments;

/**
 * Class Category
 *
 * @package myConf\Services
 */
class Category extends BaseService
{
    /**
     * Category constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    private $documentsService = null;

    /**
     * @return Document
     */
    public function Documents()
    {
        if (is_null($this->documentsService))
        {
            $this->documentsService = new Document();
        }
        return $this->documentsService;
    }

    /**
     * @param int $conferenceId
     * @param int $categoryId
     * @return array|false
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\CategoryNotFoundException
     */
    public function show(int $conferenceId, int $categoryId)
    {
        /* Get all categories in specified conference. */
        $categories = $this->Models->conference()->GetCategories($conferenceId);
        /* Make an array (list) of category identifiers. */
        $cat_ids = array();
        foreach ($categories as $cid)
        {
            $cat_ids [] = $cid['category_id'];
        }
        if ($categoryId === 0) {
            /* If categoryId unset, returns the first
               category of this conference. */
            $categoryId = reset($categories)['category_id'];
        } else {
            /* Otherwise, the existence of the category with
               $categoryId need to be checked. */
            if (!in_array($categoryId, $cat_ids)) {
                /* If it does not exist, report an error. */
                Errors::setError(E_SERVICE::E_CONFERENCE_CATEGORY_NOT_EXISTS, 404);
                return false;
            }
        }
        /* Get the first document of this category. */
        $document = $this->Models->conference()->category()->first_document($categoryId);
        if (empty($document) === true) {
            /* If the document does not exist, report an error with 404 Not Found. */
            Errors::setError(E_SERVICE::E_CONFERENCE_DOCUMENT_NOT_EXISTS, 404);
            return false;
        }
        return array(
            'category_id' => $categoryId,
            'category_list' => $categories,
            'document' => $document
        );
    }

    /**
     * Add an new category to a given conference.
     *
     * @param int    $conferenceId
     * @param string $category_text
     * @param int    $category_type
     */
    public function add(int $conferenceId,
                        string $category_text,
                        int $category_type)
    {
        /* Conferences shall exist, since $conferenceId is
           passed by function call not http $_GET or $_POST.
           Even though, integrity constraint check should be done here. */
        if ($this->Models->conference()->exists(strval($conferenceId)) === false)
        {
            Errors::setError(E_SERVICES::E_CONFERENCE_NOT_EXISTS);
            return;
        }
        /* Add a new category to the conference. */
        $this->Models->Category->New($conferenceId, $category_text, $category_type);
        return;
    }

    /**
     * @param int $conferenceId
     *
     * @return array|false Returns the category list of the given conference. If an error occurred, return false.
     */
    public function getList(int $conferenceId)
    {
        /* Conferences shall exist, since $conferenceId is
           passed by function call not http $_GET or $_POST.
           Even though, integrity constraint check should be done here. */
        if ($this->Models->conference()->exists(strval($conferenceId)) === false)
        {
            Errors::setError(E_SERVICES::E_CONFERENCE_NOT_EXISTS);
            return false;
        }
        /* Get category list. */
        $cat_data = $this->Models->conference()->GetCategories($conferenceId);
        /* For each category, get the first document. */
        foreach ($cat_data as &$cat)
        {
            $first_doc = $this->Models->Category->first_document($cat['category_id']);
            $cat['first_document_id'] = $first_doc['document_id'];
        }
        return $cat_data;
    }

    /**
     * @param int    $categoryId
     * @param string $newCategoryName
     */
    public function rename(int $categoryId,
                           string $newCategoryName)
    {
        /* Existence check need to be done here, since the system administrator
           can delete the category at any time, but the user(conference administrator)
           may not know. */
        $categoryId = $this->Models->Category->exist(strval($categoryId)) ? $categoryId : 0;
        if ($categoryId === 0)
        {
            Errors::setError(E_SERVICE::E_CONFERENCE_CATEGORY_NOT_EXISTS);
            return;
        }
        /* Rename the category. */
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->Models->Category->rename($categoryId, $newCategoryName);
        return;
    }

    /**
     * @param int $categoryId
     */
    public function remove(int $categoryId)
    {
        $categoryId = $this->Models->Category->exist($categoryId) ? $categoryId : 0;
        if ($categoryId === 0) {
            Errors::setError(E_SERVICE::E_CONFERENCE_CATEGORY_NOT_EXISTS);
            return;
        }
        $this->Models->Category->delete($categoryId);
        return;
    }

    /**
     * @param int $categoryId
     *
     * @return bool
     */
    public function moveUp(int $categoryId)
    {
        $categoryId = $this->Models->Category->exist(strval($categoryId)) ? $categoryId : 0;
        if ($categoryId === 0) {
            Errors::setError(E_SERVICE::E_CONFERENCE_CATEGORY_NOT_EXISTS);
            return false;
        }
        $this->Models->Category->move_up($categoryId);
        return true;
    }

    /**
     * Move down a category from category list.
     * @param int $categoryId The category to be moved down.
     * @return bool If
     */
    public function moveDown(int $categoryId) {
        $categoryId = $this->Models->Category->exist(strval($categoryId)) ? $categoryId : 0;
        if ($categoryId === 0) {
            Errors::setError(E_SERVICE::E_CONFERENCE_CATEGORY_NOT_EXISTS);
            return false;
        }
        $this->Models->Category->move_down($categoryId);
        return true;
    }
}