<?php


namespace myConf\Models\Constants;


class PaperStatus
{
    /**
     * The paper is submitted to system and ready for review.
     */
    public const Submitted = 0;
    /**
     * Editor start to review this paper.
     */
    public const UnderReview = 1;
    /**
     * The paper is accepted by the editor.
     */
    public const Accepted = 2;
    /**
     * The paper is rejected by the editor.
     */
    public const Rejected = 3;
    /**
     * The paper need further revision.
     */
    public const Revision = 4;
    /**
     * The paper is saved to system, but not submitted and cannot be reviewed.
     */
    public const Saved = -1;
    /**
     * This paper is logically deleted.
     */
    public const TrashBox = -2;
}