<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\mobile_code_demo;
//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

use Qcloud\Sms\SmsSingleSender;

/**
 * MobileCodeDemoPlugin
 */
class MobileCodeDemoPlugin extends Plugin
{

    public $info = [
        'name' => 'MobileCodeDemo',
        'title' => '手机验证码演示插件',
        'description' => '手机验证码演示插件',
        'status' => 1,
        'author' => 'ThinkCMF',
        'version' => '1.0'
    ];

    public $has_admin = 0;//插件是否有后台管理界面

    public function install() //安装方法必须实现
    {
        return true;//安装成功返回true，失败false
    }

    public function uninstall() //卸载方法必须实现
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的send_mobile_verification_code钩子方法
    public function sendMobileVerificationCode($param)
    {
//        $mobile        = $param['mobile'];//手机号
//        $code          = $param['code'];//验证码
//        $config        = $this->getConfig();
//        $expire_minute = intval($config['expire_minute']);
//        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
//        $expire_time   = time() + $expire_minute * 60;
//        $result        = false;
//
////        $result = [
////            'error'     => 1,
////            'message' => '服务商返回结果错误'
////        ];
//
//        $result = [
//            'error'     => 0,
//            'message' => '演示插件,您的验证码是'.$code
//        ];
//        return $result;
        // 短信应用 SDK AppID
        $appid = 1400339822; // SDK AppID 以1400开头
        // 短信应用 SDK AppKey
        $appkey = "5fdac51ae879e6e4d2f83826e728afab";
        // 需要发送短信的手机号码
        $phoneNumbers = $param['phone'];
        // 短信模板 ID，需要在短信控制台中申请
        $templateId = 650895;  // NOTE: 这里的模板 ID`7839`只是示例，真实的模板 ID 需要在短信控制台中申请
        $smsSign = "小宝学车"; // NOTE: 签名参数使用的是`签名内容`，而不是`签名ID`。这里的签名"腾讯云"只是示例，真实的签名需要在短信控制台申请

        try {
            $ssender = new SmsSingleSender($appid, $appkey);
            $params = [$param["code"]];
            $result = $ssender->sendWithParam("86", $phoneNumbers, $templateId,
                $params, $smsSign, "", "");
            $data = [
                'code' => 1,
                'result' => $result
            ];
            return $data;
        } catch (\Exception $e) {
            $data = [
                'code' => 0,
                'result' => ''
            ];
        }
    }
}