<?php

namespace myConf\Models\Constants;

class ConferenceStatus
{

    /**
     * Conferences Status Constant which means the current conference is normal and can be displayed.
     */
    public const Normal = 0;
    /**
     * Conferences Status Constant which means the current conference cannot be displayed normally.
     * Only the administrators can view and edit.
     */
    public const Moderated = 1;
    /**
     * Conferences status constant which means the current conference is submitted
     * by user and need to be audited by administrator.
     */
    public const PreAudit = 2;

    /**
     * String Mapper.
     */
    public const StringMapper = [
        0 => '正常',
        1 => '隐藏',
        2 => '待审核',
    ];
}