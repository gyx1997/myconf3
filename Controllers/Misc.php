<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 18:48
 */

namespace myConf\Controllers;


class Misc extends \myConf\BaseController
{

    /**
     * 验证码
     */
    public function captcha(): void
    {
        define("CAPTCHA_NUMCHARS", 4);
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pass_phrase = "";
        for ($i = 0; $i < CAPTCHA_NUMCHARS; $i++) {
            $pass_phrase .= $str[rand(0, 25)];
        }
        //写入Session
        $this->session->set_tempdata('captcha', $pass_phrase, 300);
        session_write_close();
        //关闭Session防止阻塞
        //下面使用gd库生成验证码的图像
        define("CAPTCHA_WIDTH", 200);    //验证码宽度
        define("CAPTCHA_HEIGHT", 80);    //验证码高度
        $img = imagecreatetruecolor(CAPTCHA_WIDTH, CAPTCHA_HEIGHT);
        $bg_color = imagecolorallocate($img, 225, 225, 225);
        $text_color = imagecolorallocate($img, 0, 0, 0);           //黑色字体
        $graphic_color = imagecolorallocate($img, 64, 64, 64);     //灰色图像
        imagefilledrectangle($img, 0, 0, CAPTCHA_WIDTH, CAPTCHA_HEIGHT, $bg_color);
        for ($i = 0; $i < 10; $i++) {
            imageline($img, rand() % CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, rand() % CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, $graphic_color);
        }
        for ($i = 0; $i < 500; $i++) {
            $current_rgb_value = rand(50, 150);
            imagefilledellipse(
                $img,
                rand() % CAPTCHA_WIDTH,
                rand() % CAPTCHA_HEIGHT,
                5,
                5,
                imagecolorallocate($img, $current_rgb_value, $current_rgb_value, $current_rgb_value)
            );
        }
        //用来在背景图片上产生200个干扰点
        for ($i = 0; $i < 1000; $i++) {
            $pointcolor = imagecolorallocate($img, rand(50, 200), rand(50, 200), rand(50, 200));
            imagesetpixel($img, rand() % CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, $pointcolor);
        }
        imagettftext(
            $img,
            48,
            rand(-15, 15),
            rand(10, 20),
            CAPTCHA_HEIGHT - 20,
            $text_color,
            STATIC_DIR . "ecr.ttf",
            substr($pass_phrase, 0, 2)
        );
        imagettftext(
            $img,
            48,
            rand(-15, 15),
            rand(CAPTCHA_WIDTH - 100, CAPTCHA_WIDTH - 80),
            CAPTCHA_HEIGHT - 20,
            $text_color,
            STATIC_DIR . "ecr.ttf",
            substr($pass_phrase, 2, 2)
        );
        $distortionImage = imagecreatetruecolor(CAPTCHA_WIDTH, CAPTCHA_HEIGHT);
        $randBgColor = $bg_color;
        imagefill($distortionImage, 0, 0, $randBgColor);
        for ($x = 0; $x < CAPTCHA_WIDTH; $x++) {
            for ($y = 0; $y < CAPTCHA_HEIGHT; $y++) {
                $rgbColor = imagecolorat($img, $x, $y);
                imagesetpixel($distortionImage, (int)($x + sin($y / CAPTCHA_HEIGHT * 2.05 * 3.1415926 - 3.1415926 * 0.5) * 4), $y, $rgbColor);
            }
        }
        //用来在背景图片上产生200个干扰点
        for ($i = 0; $i < 8000; $i++) {
            $pointcolor = imagecolorallocate($img, rand(50, 200), rand(50, 200), rand(50, 200));
            imagesetpixel($distortionImage, rand() % CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, $pointcolor);
        }
        header("Content-type: image/png");
        imagepng($distortionImage);
        imagedestroy($img);
        imagedestroy($distortionImage);
        $this->exit_promptly();
    }

    public function upload_success() : void {

    }

}