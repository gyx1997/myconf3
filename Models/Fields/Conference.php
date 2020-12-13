<?php


namespace myConf\Models\Fields;

/**
 * Class Conferences
 * 定义了Conference的所有与数据库表字段相关联的内容。
 * @package myConf\Models\Fields
 */
class Conference
{
    /**
     * 唯一标识某一会议的正整数。
     */
    public const Id = 'conference_id';
    /**
     * 会议的开始时间。Unix时间戳。
     */
    public const StartTime = 'conference_start_time';
    /**
     * 会议提交Paper的截止时间。Unix时间戳。
     */
    public const SubmitEndTime = 'conference_paper_submit_end';
    /**
     * 会议的状态。
     */
    public const Status = 'conference_status';
    /**
     * 会议是否使用Paper在线提交系统。用0和1表示的布尔值。
     */
    public const UsePaperSubmitSystem = 'conference_use_paper_submit';
    /**
     * 会议的URL。字符串。
     */
    public const Url = 'conference_url';
    /**
     * 会议的名字。字符串。
     */
    public const Name = 'conference_name';
    /**
     * 会议的头图。字符串。
     */
    public const BannerImage = 'conference_banner_image';
    /**
     * 会议的二维码。字符串。
     */
    public const QRCode = 'conference_qr_code';
    /**
     * 会议的主办方。字符串。
     */
    public const HostBy = 'conference_host';
}