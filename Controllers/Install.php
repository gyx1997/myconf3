<?php


namespace myConf\Controllers;


use myConf\BaseController;
use myConf\Utils\DB;

class Install extends BaseController
{
    public function index() : void
    {
        die();
        $mysqli = new \mysqli($this->db->hostname,$this->db->username,$this->db->password,$this->db->database);   //连接MySQL数据库
        if ($mysqli->connect_errno) { //判断是否连接成功
            printf("Connect failed: %s\n", $mysqli->connect_error);
            exit();
        }
        $superAdministratorEmail = "522975334@qq.com";
        $superAdministratorName = "Administrator";
        $superAdministratorPassword = "123456789a";
        $salt = md5(strval(time()));
        $superAdministratorPassword = md5(md5($superAdministratorPassword) . $salt);

        $sql = "DROP DATABASE myconf;
CREATE DATABASE myconf;
USE myconf;
CREATE TABLE myconf_attachments
(
	attachment_id 				int 			NOT NULL auto_increment PRIMARY KEY, 
    attachment_file_name 		char(64) 		NOT NULL,
    attachment_is_image 		tinyint 		NOT NULL DEFAULT 0,
    attachment_used 			tinyint 		NOT NULL DEFAULT 0,
    attachment_file_size 		int 			NOT NULL DEFAULT 0,
    attachment_image_width 		int 			NOT NULL DEFAULT 0,
    attachment_image_height		int 			NOT NULL DEFAULT 0,
	attachment_tag_id 			int				NOT NULL DEFAULT 0,
	attachment_tag_type 		char(16) 		NOT NULL DEFAULT 'unknown',
    attachment_original_name 	varchar(255)	NOT NULL,
    attachment_filename_hash 	int 			NOT NULL DEFAULT 0,
    INDEX	idxfn		(attachment_file_name),
    INDEX	idxfnh		(attachment_filename_hash),
    INDEX   idxtag		(attachment_tag_id, attachment_tag_type)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_categories
(
	category_id					int				NOT NULL auto_increment PRIMARY KEY,
    conference_id				int				NOT NULL DEFAULT 0,
    category_type				int				NOT NULL DEFAULT 0,
    category_title				varchar(64)		NOT NULL ,
    category_display_order		int				NOT NULL DEFAULT 0,
    INDEX	idxconf		(conference_id)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_conference_members
(
	user_id						int				NOT NULL,
    conference_id				int				NOT NULL,
    user_role					varchar(64)		NOT NULL DEFAULT 'scholar',
    PRIMARY KEY			(conference_id, user_id)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_conferences
(
	conference_id				int				NOT NULL auto_increment PRIMARY KEY,
    conference_start_time		int				NOT NULL,
    conference_paper_submit_end	int				NOT NULL DEFAULT 0,
    conference_status			tinyint			NOT NULL DEFAULT 0,
    conference_use_paper_submit	tinyint			NOT NULL DEFAULT 0,
    conference_url				char(64)		NOT NULL UNIQUE,
    conference_name				varchar(255)	NOT NULL,
    conference_banner_image		varchar(255)	NOT NULL DEFAULT '', 
    conference_qr_code			varchar(255)	NOT NULL DEFAULT '',
    conference_host				varchar(64)		NOT NULL DEFAULT '',
    INDEX 	idxurl(conference_url)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_configs
(
	k 							char(32)		NOT NULL PRIMARY KEY,
    v							varchar(255)	NOT NULL DEFAULT ''
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_document
(
	document_id					int 			NOT NULL auto_increment PRIMARY KEY,
    document_category_id		int 			NOT NULL DEFAULT 0,
    document_title 				varchar(255) 	NOT NULL DEFAULT '',
    document_html 				mediumtext
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_paper_authors
(
	author_id					int 			NOT NULL auto_increment PRIMARY KEY,
    paper_id 					int 			NOT NULL DEFAULT 0,
	author_display_order 		int 			NOT NULL DEFAULT 0,
    author_email 				varchar(96)		NOT NULL DEFAULT '',
    author_institution			varchar(100)	NOT NULL DEFAULT '',
    author_department			varchar(100) 	NOT NULL DEFAULT '',
	author_address				varchar(100)	NOT NULL DEFAULT '',
    author_first_name			varchar(32) 	NOT NULL DEFAULT '',
    author_last_name 			varchar(32)		NOT NULL DEFAULT '',
    author_prefix				varchar(32) 	NOT NULL DEFAULT '',
    INDEX	idxpid(paper_id)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_paper_reviews 
(
	review_id					int 			NOT NULL auto_increment PRIMARY KEY,
    paper_id					int 			NOT NULL DEFAULT 0,
    paper_version				int 			NOT NULL DEFAULT 0,
    reviewer_email				char(96)		NOT NULL DEFAULT '',
    review_status				tinyint			NOT NULL DEFAULT 0,
    review_result				char(10)		NOT NULL DEFAULT 'UNKNOWN',
    review_comment				tinytext ,
    INDEX	idxvpk(paper_id, paper_version, reviewer_email),
    INDEX	idxreviewer(reviewer_email)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_papers
(
	paper_id					int 			NOT NULL auto_increment PRIMARY KEY,
    paper_logic_id 				int 			NOT NULL DEFAULT 0,
    paper_version				int 			NOT NULL DEFAULT 1,
    conference_id				int				NOT NULL DEFAULT 0,
    user_id						int 			NOT NULL DEFAULT 0,
    pdf_attachment_id 			int				NOT NULL DEFAULT 0,
    copyright_attachment_id		int				NOT NULL DEFAULT 0,
    paper_submit_time			int				NOT NULL DEFAULT 0,
    paper_suggested_session		int 			NOT NULL DEFAULT 0,
    paper_status				tinyint	 		NOT NULL DEFAULT 0,
    paper_type					char(16) 		NOT NULL DEFAULT 'paper',
    paper_title					varchar(255) 	NOT NULL DEFAULT '',
    paper_abstract 				tinytext ,
    INDEX 	idxvpk(paper_logic_id, paper_version),
    INDEX	idxconfid(conference_id)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_paper_sessions
(
	session_id					int 			NOT NULL auto_increment PRIMARY KEY,
    session_conference_id		int 			NOT NULL DEFAULT 0,
    session_type				int 			NOT NULL DEFAULT 0,
    session_display_order		int				NOT NULL DEFAULT 0,
    session_text				varchar(64)		NOT NULL DEFAULT '',
    INDEX	idxconfid(session_conference_id)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_scholars
(
	scholar_id					int 			NOT NULL auto_increment PRIMARY KEY,
	scholar_email				char(96)		NOT NULL,
    scholar_first_name			varchar(32) 	NOT NULL DEFAULT '',
    scholar_last_name			varchar(32)		NOT NULL DEFAULT '',
    scholar_chn_full_name		varchar(32)		NOT NULL DEFAULT '',
    scholar_address				varchar(100)	NOT NULL DEFAULT '',
    scholar_institution			varchar(100)	NOT NULL DEFAULT '',
    scholar_department			varchar(100)	NOT NULL DEFAULT '',
    scholar_prefix				varchar(32)		NOT NULL DEFAULT '',
    INDEX	idxemail(scholar_email)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_users
(
	user_id						int 			NOT NULL auto_increment PRIMARY KEY,
    user_email					char(96)		NOT NULL UNIQUE,
    user_name					varchar(100)	NOT NULL DEFAULT '',
	user_password				char(32)		NOT NULL,
    password_salt				char(32)		NOT NULL,
    is_frozen					tinyint			NOT NULL DEFAULT 1,
    user_role					char(10)		NOT NULL DEFAULT 'user',
    user_avatar					varchar(128) 	NOT NULL DEFAULT '',
    INDEX idxvpk_email(user_email)
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;

CREATE TABLE myconf_logs
(
	log_id						int 			NOT NULL auto_increment PRIMARY KEY,
    log_ip_addr					int 			NOT NULL DEFAULT 0,
    log_type					char(10)		NOT NULL DEFAULT '',
    log_action					char(10)		NOT NULL DEFAULT '',
    log_desc					varchar(255)	NOT NULL DEFAULT ''
) CHARSET=utf8 , COLLATE=utf8_unicode_ci,	engine = InnoDB;
INSERT INTO myconf_users (user_name, user_email, user_password, password_salt)VALUES('$superAdministratorName', '$superAdministratorEmail','$superAdministratorPassword', '$salt');
INSERT INTO myconf_scholars (scholar_email) VALUES ('$superAdministratorEmail)'


";
        $mysqli->multi_query($sql); //执行sql语句
        echo $mysqli->error;
        $mysqli->close();
        echo("INSTALL SUCCESS!");
        //header('location:/');
        exit();
        //$this->Services->Account->InitSuperAdministrator($superAdministratorEmail, $superAdministratorName, $superAdministratorPassword);
    }
}