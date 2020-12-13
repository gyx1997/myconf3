<?php
    
    
    namespace myConf\Methods\Conference\PaperSubmit;

    use myConf\Models\Constants\PaperStatus;
    use myConf\Utils\Arguments;
    use myConf\Services;

    /**
     * Class Delete
     *
     * @package myConf\Methods\Conferences\PaperSubmit
     */
    class Delete extends \myConf\BaseMethod
    {
        /**
         * @requestUrl /conference/{confUrl}/paper-submit/delete
         */
        public static function deletePaper()
        {
            // Initialize and validate input.
            $paperId = Arguments::getHttpArg('id');
            $paperVersion = Arguments::getHttpArg('ver');
            if (is_null($paperId) || is_null($paperVersion))
            {
                self::retError(400, -1, 'REQUEST_PARAM_INVALID', 'Necessary parameter(s) missing.');
                return;
            }
            // Delete the paper. Note that the paper will not be
            // deleted directly. Instead, it will be moved to the trash box.
            Services::conferences()
                    ->paper()
                    ->delete($paperId,
                             $paperVersion,
                             array(PaperStatus::Saved));
            self::return();
        }
    }