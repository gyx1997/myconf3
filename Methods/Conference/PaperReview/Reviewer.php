<?php
    
    
    namespace myConf\Methods\Conference\PaperReview;
    
    
    use myConf\Services;
    use myConf\Utils\Arguments;

    class Reviewer extends \myConf\BaseMethod
    {
        /**
         * @requestUrl /conference/{confUrl}/paper-review/reviewer-tasks/
         */
        public static function showReviewTasks()
        {
            // Get the conference id.
            $conferenceId = self::getGlobal('conference_id');
            $userId = self::getGlobal('user_id');
            // Get tasks of reviewers.
            $taskList = Services::conferences()
                                ->paper()
                                ->review()
                                ->reviewer()
                                ->getTasks($userId,
                                           $conferenceId);
            self::return(array('papers' => $taskList));
        }
    
        /**
         * @requestUrl /conference/{confUrl}/paper-review/reviewer-tasks/?do=enterReview
         */
        public static function enterReview()
        {
            // Input validation.
            $paperId = Arguments::getHttpArg('id');
            $paperVersion = Arguments::getHttpArg('ver');
            if (is_null($paperId) || is_null($paperVersion))
            {
                self::retError(400,
                               -1,
                               'REQUEST_PARAM_INVALID',
                               'Necessary parameter(s) missing.');
                return;
            }
            $paperId = intval($paperId);
            $paperVersion = intval($paperVersion);
            $userId = self::getGlobal('user_id');
            // Enter review.
            Services::conferences()
                    ->paper()
                    ->review()
                    ->reviewer()
                    ->enterReview($userId,
                                  $paperId,
                                  $paperVersion);
            self::return();
        }
    
        /**
         * @requestUrl /conference/{confUrl}/paper-review/
         */
        public static function submitReview()
        {
            // Input parameters validation.
            $paperId = Arguments::getHttpArg('paper_id', true);
            $paperVersion = Arguments::getHttpArg('paper_version', true);
            $reviewAction = Arguments::getHttpArg('review_action', true);
            $reviewComment = Arguments::getHttpArg('review_comment', true);
            if (is_null($paperId) || is_null($paperVersion) || is_null($reviewAction) || is_null($reviewComment))
            {
                self::retError(400,
                               -1,
                               'REQUEST_PARAM_INVALID',
                               'Necessary field(s) in POST form missing.');
            }
            $paperId = intval($paperId);
            $paperVersion = intval($paperVersion);
            // Get the current user id.
            $userId = self::getGlobal('user_id');
            // Determine whether to submit a draft.
            $do = self::getGlobal('do');
            Services::conferences()
                    ->paper()
                    ->review()
                    ->reviewer()
                    ->submitReview($userId,
                                   $paperId,
                                   $paperVersion,
                                   $reviewAction,
                                   $reviewComment,
                                   $do === 'save');
            self::return();
        }
        
        public static function showReviewPage()
        {
            // Get input data and do validation.
            $paperId = Arguments::getHttpArg('id');
            $paperVersion = Arguments::getHttpArg('ver');
            if (is_null($paperId) || is_null($paperVersion))
            {
                self::retError(400,
                               -1,
                               'REQUEST_PARAM_INVALID',
                               'Necessary parameter(s) missing.');
                return;
            }
            $paperId = intval($paperId);
            $paperVersion = intval($paperVersion);
            $userId = self::getGlobal('user_id');
            $data = Services::conferences()
                            ->paper()
                            ->review()
                            ->reviewer()
                            ->getSingleReviewTask($userId,
                                                  $paperId,
                                                  $paperVersion);
            self::return($data);
        }
    }