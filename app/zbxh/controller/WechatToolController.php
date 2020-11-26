<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\zbxh\controller;
//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\HomeBaseController;
use cmf\controller\HomeOtherBaseController;
use EasyWeChat\Factory;
use think\Db;

/**
 * 个人中心
 */
class WechatToolController extends HomeOtherBaseController
{
    /**
     * 验证微信信息
     */
    public function wechatregister()
    {
        $query = $this->request->param();
//        Db::name("log")->insert(["key" => "事件", "value" => "进入wechatregister"]);
        $token = $query['token'];
//        Db::name("log")->insert(['key' => '微信验证', 'value' => 'token:' . $token]);
        $config = $this->assembleWxConfig($token);
        session("token", $token);
        $app = Factory::officialAccount($config);

        $app->server->push(function ($message) {
//            Db::name("log")->insert(["key" => "事件", "value" => "进入message"]);
//            Db::name("log")->insert(["key" => "事件", "value" => $message['Event']]);
            $token = session("token");
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select()->toArray();
            $token = session("token");

            switch ($message['Event']) {
                case 'subscribe':
                    return '欢迎关注【' . $wxconfig[0]['name'] . '】公众账号！ ';
                    break;
            }
        });

        $response = $app->server->serve();

        // 将响应输出
        $response->send();
        exit; // Laravel 里请使用：return $response;
    }
}
