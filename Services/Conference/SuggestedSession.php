<?php
    
    
    namespace myConf\Services\Conference;
    
    use myConf\Errors as err;
    use myConf\Errors\Services\Services as errServices;
    use myConf\Exceptions\PaperSessionAlreadyUsedException;
    use myConf\Exceptions\PaperSessionNotExistsException;

    /**
     * Class SuggestedSession
     *
     * @package myConf\Services\Conferences
     */
    class SuggestedSession extends \myConf\BaseService
    {
        /**
         * SuggestedSession constructor.
         */
        public function __construct() { parent::__construct(); }
    
        /**
         * @param $conferenceId
         * @param $sessionText
         * @param $sessionType
         *
         * @return int|false
         */
        public function add($conferenceId, $sessionText, $sessionType)
        {
            // Trim the leading and ending spaces.
            $sessionText = trim($sessionText);
            if (strlen($sessionText) === 0)
            {
                // Empty string cannot be added as a suggested session.
                err::setError(errServices::E_SUGGESTED_SESSION_EMPTY_STRING,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_EMPTY_STRING],
                              'Suggested session is empty.');
                return false;
            }
            if ($this->models()
                     ->conference()
                     ->session()
                     ->existInConference($conferenceId,
                                         $sessionText)) {
                // Duplicate session cannot be added.
                err::setError(errServices::E_SUGGESTED_SESSION_DUPLICATE,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_DUPLICATE],
                              'Suggested session already exists.');
                return false;
            }
            return $this->Models->PaperSession->add_session($conferenceId, $sessionText, $sessionType);
        }
    
        /**
         * @param int $conferenceId
         *
         * @return array
         */
        public function getAll(int $conferenceId) : array
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $sessions = $this->models()
                             ->conference()
                             ->session()
                             ->getAll($conferenceId);
            $sessions_dispatched = array();
            foreach ($sessions as $session)
            {
                $sessions_dispatched[intval($session['session_type'])] []= $session;
            }
            return $sessions_dispatched;
        }
    
        /**
         * @param $conferenceId
         *
         * @return array
         */
        public function getAllUndispatched($conferenceId)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $this->models()
                        ->conference()
                        ->session()
                        ->getAll($conferenceId);
        }
    
        /**
         * @param $sessionId
         *
         */
        public function moveUp($sessionId)
        {
            // Check the existence of the given session id.
            if ($this->models()
                     ->conference()
                     ->session()
                     ->exist($sessionId) === false)
            {
                err::setError(errServices::E_SUGGESTED_SESSION_NOT_EXISTS,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_NOT_EXISTS],
                              'The suggested session does not exist.');
                return false;
            }
            // Move down the session.
            $this->models()
                 ->conference()
                 ->session()
                 ->move_up($sessionId);
            return true;
        }
    
        /**
         * @param $sessionId
         *
         */
        public function moveDown($sessionId)
        {
            // Check the existence of the given session id.
            if ($this->models()
                     ->conference()
                     ->session()
                     ->exist($sessionId) === false)
            {
                err::setError(errServices::E_SUGGESTED_SESSION_NOT_EXISTS,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_NOT_EXISTS],
                              'The suggested session does not exist.');
                return false;
            }
            // Move down the session.
            $this->models()
                 ->conference()
                 ->session()
                 ->move_down($sessionId);
            return true;
        }
    
        /**
         * @param $sessionId
         * @param $sessionType
         * @param $sessionText
         *
         * @return bool
         */
        public function update($sessionId, $sessionText, $sessionType)
        {
            // Check the existence of the session with the given id.
            if ($this->models()
                     ->conference()
                     ->session()
                     ->exist($sessionId) === false)
            {
                err::setError(errServices::E_SUGGESTED_SESSION_NOT_EXISTS,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_NOT_EXISTS],
                              'The suggested session does not exist.');
                return false;
            }
            // Get the conference id.
            $conferenceId = $this->models()
                                 ->conference()
                                 ->session()
                                 ->get($sessionId)['session_conference_id'];
            // Check whether the session text would be duplicate.
            if ($this->models()
                     ->conference()
                     ->session()
                     ->existInConference($conferenceId,
                                         $sessionText)) {
                if ($this->models()->conference()->session()->get($sessionId)['session_type'] ===
                    $sessionType)
                {
                    // Duplicate session cannot be added.
                    // Note that the update operation allows the same session
                    // text with different session type.
                    err::setError(errServices::E_SUGGESTED_SESSION_DUPLICATE,
                                  200,
                                  errServices::errorMessage[errServices::E_SUGGESTED_SESSION_DUPLICATE],
                                  'Suggested session already exists.'
                    );
                    return false;
                }
            }
            // Update the session data (type and text).
            $this->Models->PaperSession->update_session($sessionId, $sessionType, $sessionText, -1);
            return true;
        }
    
        /**
         * @param $sessionId
         *
         * @return bool
         */
        public function delete($sessionId)
        {
            // Check the existence of the session with the given id.
            if ($this->models()
                     ->conference()
                     ->session()
                     ->exist($sessionId) === false)
            {
                err::setError(errServices::E_SUGGESTED_SESSION_NOT_EXISTS,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_NOT_EXISTS],
                              'The suggested session does not exist.');
                return false;
            }
            // Trying to delete the given session.
            try
            {
                $this->models()
                     ->conference()
                     ->session()
                     ->delete_session($sessionId);
            }
            catch (PaperSessionAlreadyUsedException $e)
            {
                // If the session is used by paper(s), it cannot be deleted.
                err::setError(errServices::E_SUGGESTED_SESSION_IN_USE,
                              200,
                              errServices::errorMessage[errServices::E_SUGGESTED_SESSION_IN_USE],
                              $e->getMessage());
                return false;
            }
            return true;
        }
    }