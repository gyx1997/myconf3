<?php /** @noinspection ALL */
    
    /**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 16:01
 */

namespace myConf\Controllers;

use myConf\Authenticators\Conference\Conference as Authenticator;
use myConf\Errors;
use myConf\Exceptions\HttpStatusException;
use myConf\Exceptions\SendRedirectInstructionException;
use myConf\Utils\Arguments;
use myConf\Errors\Services\Services as E_SERVICE;



class Conference extends \myConf\BaseController
{

    /**
     * @var array 权限表
     */

    // TODO 使用数据库构造完整的 Role-Based Access Control 权限系统
    private $privilegeTable;

    /**
     * @var array 当前会议信息
     */
    private $conferenceData;
    /**
     * @var string 当前会议的二级URL
     */
    private $conferenceUrl;
    /**
     * @var int|mixed 当前会议的ID号
     */
    private $conferenceId = 0;

    /**
     * Conferences constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        // Get the conference's short name from Url.
        $this->conferenceUrl = $this->uri->segment(2, '');
        // Check the existence of requested conference.
        // If conference exists, load its data.
        $this->conferenceData = $this->Services
            ->Conference
            ->loadFromUrl($this->conferenceUrl);
        /* An error occurred when the conference does not exist. */
        if (Errors::getLastError() === 0)
        {
            // Set $conferenceId.
            $this->conferenceId = $this->conferenceData['conference_id'];
            // Set global variables.
            self::setGlobal('user_id', $this->userId);
            self::setGlobal('conference_id', $this->conferenceId);
            self::setGlobal('conference_data', $this->conferenceData);
            // Do authentication.
            Authenticator::authenticate();
            if (Authenticator::authSuccess() === false) {
                if ($this->userId === 0)
                {
                    // If authentication fails and current user does not logged in,
                    // redirect to login page. Otherwise, the user really does not
                    // have the permission. Access will be denied.
                    $this->_login_redirect();
                }
            }
        }
    }
    
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    public function paper_review() : void {
        switch($this->actionName) {
            case 'editor-list':
            case 'editor':
                {
                    switch($this->do) {
                        case 'addReviewer':
                        case 'addreviewer':
                            \myConf\Methods\Conference\PaperReview\Editor::addReviewer();
                            break;
                        case 'endReview':
                        case 'endreview':
                            \myConf\Methods\Conference\PaperReview\Editor::finishReview();
                            break;
                        case 'delete':
                            \myConf\Methods\Conference\PaperReview\Editor::deletePaper();
                            break;
                        case 'getReviewers':
                        case 'getreviewers':
                            \myConf\Methods\Conference\PaperReview\Editor::getReviewers();
                            break;
                        case 'makedecision':
                            \myConf\Methods\Conference\PaperReview\Editor::showDecisionPage();
                            \myConf\Utils\Template::setTemplate('/Conference/Paper-review/Editor-finish');
                            break;
                        case 'view':
                        default:
                            \myConf\Methods\Conference\PaperReview\Editor::viewStatusFromPaperList();
                            \myConf\Utils\Template::setTemplate('/Conference/Paper-review/Editor-list');
                            break;
                    }
                    break;
                }
            case 'editor-preview':
                \myConf\Methods\Conference\PaperReview\Editor::viewPaper();
                break;
            case 'reviewer-status':
                \myConf\Methods\Conference\PaperReview\Editor::viewStatusFromReviewerList();
                break;
            case 'reviewer-tasks':
                switch($this->do)
                {
                    case 'enterReview':
                        \myConf\Methods\Conference\PaperReview\Reviewer::enterReview();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/paper-review/reviewer-tasks/');
                        break;
                    default:
                        \myConf\Methods\Conference\PaperReview\Reviewer::showReviewTasks();
                }
                break;
            case 'show-review':
                switch($this->do)
                {
                    case 'save':
                    case 'submit':
                        \myConf\Methods\Conference\PaperReview\Reviewer::submitReview();
                        break;
                    default:
                        \myConf\Methods\Conference\PaperReview\Reviewer::showReviewPage();
                        break;
                }
        }
    }


    public function index() : void
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        \myConf\Methods\Conference\Home\Homepage::loadPage();
        return;
    }

    /**
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @throws HttpStatusException Exceptions thrown when authenticate fails.
     * @throws SendRedirectInstructionException Exceptions thrown when redirect instruction needs.
     */
    public function management()
    {
        // Authentication passed, parse Url next.
        switch ($this->actionName) {
            case 'default':
            {
                // Default page unset, redirect to overview page.
                $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/overview/');
                break;
            }
            case 'overview':
            {
                if ($this->do == 'submit') {
                    \myConf\Methods\Conference\Management\Overview::updateConference();
                } else {
                    // Return basic data of the current conference.
                    // Just get the data from global variables.
                    $GLOBALS['myConf']['ret'] = array(
                        'httpCode' => 200,
                        'status' => 'SUCCESS',
                        'statusCode' => 0,
                        'data' => array('conference' => self::getGlobal('conference_data'))
                    );
                }
                break;
            }
            case 'category':
            {
                switch ($this->do) {
                    case 'add':
                    {
                        \myConf\Methods\Conference\Management\Category::addCategory();
                        break;
                    }
                    case 'rename':
                    {
                        \myConf\Methods\Conference\Management\Category::renameCategory();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/category/');
                        break;
                    }
                    case 'remove':
                    {
                        \myConf\Methods\Conference\Management\Category::removeCategory();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/category/');
                        return;
                    }
                    case 'up':
                    {
                        \myConf\Methods\Conference\Management\Category::moveUpCategory();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/category/');
                        break;
                    }
                    case 'down':
                    {
                        \myConf\Methods\Conference\Management\Category::moveDownCategory();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/category/');
                        break;
                    }
                    default:
                    {
                        /* Show category list. */
                        /** @noinspection PhpFullyQualifiedNameUsageInspection */
                        \myConf\Methods\Conference\Management\Category::showCategoryList();
                    }
                }
                break;
            }
            case 'participant':
                switch ($this->do)
                {
                    case 'getAll':
                        \myConf\Methods\Conference\Management\Members::getAllMembers();
                        break;
                    case 'toggleRole':
                        \myConf\Methods\Conference\Management\Members::toggleRole();
                        break;
                    case 'remove':
                        \myConf\Methods\Conference\Management\Members::removeMember();
                        break;
                    default:
                        /* Just show the page rendered by the template. Nothing need to be done here. */
                        break;
                }
                break;
            case 'document':
            {
                switch ($this->do) {
                    case 'submit':
                    {
                        \myConf\Methods\Conference\Management\Document::submitDocumentData();
                        usleep(500000);
                        break;
                    }
                    case 'get':
                    {
                        \myConf\Methods\Conference\Management\Document::getDocumentData();
                        break;
                    }
                    case 'edit':
                    {
                        \myConf\Methods\Conference\Management\Document::showEditPage();
                        break;
                    }
                    case 'putAttachment':
                    {
                        break;
                    }
                    default:
                    {
                        throw new HttpStatusException(400, 'UNKNOWN_DO_PARAM', 'The request parameters are invalid.');
                    }
                }
                break;
            }
            case 'suggested-session':
            {
                switch($this->do){
                    case 'add':
                        \myConf\Methods\Conference\Management\SuggestedSession::add();
                        break;
                    case 'down':
                        \myConf\Methods\Conference\Management\SuggestedSession::moveDown();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/suggested-session/');
                        break;
                    case 'up':
                        \myConf\Methods\Conference\Management\SuggestedSession::moveUp();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/suggested-session/');
                        break;
                    case 'edit':
                        \myConf\Methods\Conference\Management\SuggestedSession::update();
                        $this->RedirectTo('/conference/' . $this->conferenceUrl . '/management/suggested-session/');
                        break;
                    case 'delete':
                        \myConf\Methods\Conference\Management\SuggestedSession::delete();
                        break;
                    default:
                        \myConf\Methods\Conference\Management\SuggestedSession::getAll();
                }
                break;
            }
            case 'papers':
            {
                switch($this->do) {
                    case 'delete':
                    {
                        $result = $this->Services->Paper->deletePaper();
                        $this->addRetVariables($result);
                        break;
                    }
                    case 'view':
                    default:
                    {
                        $data = $this->Services->Paper->getList();
                        $sessions = $this->Services->Conference->getSessions($this->conferenceId);
                        $data['sessions'] = $sessions;
                        $this->addRetVariables($data);
                    }
                }
                break;
            }
            default:
            {
                throw new HttpStatusException(404, 'NOT_FOUND', 'The requested URL is not found on this server.');
            }
        }
    }
    
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    public function paper_submit()
    {
        switch ($this->actionName)
        {
            case 'new':
                switch ($this->do)
                {
                    case 'submit':
                    case 'save':
                        \myConf\Methods\Conference\PaperSubmit\NewPaper::submit();
                        break;
                    default:
                        \myConf\Methods\Conference\PaperSubmit\NewPaper::showNewPage();
                }
                break;
            case 'edit':
                switch ($this->do)
                {
                    case 'submit':
                    case 'save':
                        \myConf\Methods\Conference\PaperSubmit\Edit::submitPaper();
                        break;
                    default:
                        \myConf\Methods\Conference\PaperSubmit\Edit::showEditPage();
                }
                break;
            case 'preview':
                \myConf\Methods\Conference\PaperSubmit\Preview::previewPaper();
                break;
            case 'delete':
                \myConf\Methods\Conference\PaperSubmit\Delete::deletePaper();
                break;
            case 'author':
                \myConf\Methods\Conference\PaperSubmit\GetAuthors::getAuthors();
                break;
            case 'default':
                \myConf\Methods\Conference\PaperSubmit\Papers::showList();
                break;
            case 'revision':
                switch($this->do)
                {
                    case 'submit':
                    case 'save':
                        \myConf\Methods\Conference\PaperSubmit\Revision::submitRevision();
                        break;
                    default:
                        \myConf\Methods\Conference\PaperSubmit\Revision::showRevision();
                }
                break;
        }
    }

    /**
     * @throws \myConf\Exceptions\SendExitInstructionException
     * @throws \myConf\Exceptions\SendRedirectInstructionException
     */
    public function member() {
        switch ($this->do) {
            case 'register':
                {
                    if ($this->Services->Conference->userJointIn($this->userId, $this->conferenceId)) {
                        $this->exit_promptly(array('status' => 'ALREADY_JOIN'));
                    }
                    $this->Services->Conference->AddMemberToConference($this->conferenceId, $this->userId);
                    $this->selfRedirect();
                    break;
                }
        }
    }

    /**
     * Collect common data for output.
     */
    protected function collectOutputVariables(): void
    {
        /* Call parent's function. */
        parent::collectOutputVariables();
        /* Variables of control data for html page. They are not
           included in response of ajax requests. */
        if (defined('REQUEST_IS_AJAX') === false)
        {
            $this->addRetVariables(array(
                'tab_page'            => $this->methodName,
                'auth_management'     => self::getGlobal('auth_admin') || self::getGlobal('auth_creator'),
                'auth_review'         => self::getGlobal('auth_reviewer'),
                'auth_arrange_review' => self::getGlobal('auth_editor'),
                'conference'          => $this->conferenceData,
            ), OUTPUT_VAR_HTML_ONLY);
        }
    }
}
