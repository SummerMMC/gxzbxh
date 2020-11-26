<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use EasyWeChat\Factory;

class WechatAdminController extends AdminBaseController
{
    public function _initialize()
    {
    }

    public function index()
    {

    }

    /**
     * 跳转微信自定义菜单页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxmenulist()
    {
        $wxconfig = Db::name("wx_config")->select();
        $this->assign("wxconfig", $wxconfig);
        return $this->fetch();
    }

    public function getmenu()
    {
        $token = $this->request->param('token');
        $config = $this->assembleWxConfig($token);

        $app = Factory::officialAccount($config);
        $list = $app->menu->list();
        echo $this->success($list);
    }

    public function addmenu()
    {
        $token = $this->request->param('token');
//        $menudata = $this->request->param('menudata');
        $config = $this->assembleWxConfig($token);
        $app = Factory::officialAccount($config);
        $buttons = [];
//        $arr1 = explode("#13", $menudata);
//        foreach ($arr1 as $key => $value) {
//            $arr2 = explode("#", $value);
//            $temp = ['type' => 'view', 'name' => trim($arr2[0]), 'url' => trim($arr2[1])];
//            array_push($buttons, $temp);
//        }

        $buttons = [
            [
                "type" => "view",
                "name" => "首页",
                "url" => $this->getSiteHost() . "/zbxh/wechatotherindex/index"
            ],
            [
                "type" => "view",
                "name" => "个人中心",
                "url" => $this->getSiteHost() . "/zbxh/wechatindex/usercenter"
            ],
//            [
//                "name"       => "菜单",
//                "sub_button" => [
//                    [
//                        "type" => "view",
//                        "name" => "搜索",
//                        "url"  => "http://www.soso.com/"
//                    ],
//                    [
//                        "type" => "view",
//                        "name" => "视频",
//                        "url"  => "http://v.qq.com/"
//                    ],
//                    [
//                        "type" => "click",
//                        "name" => "赞一下我们",
//                        "key" => "V1001_GOOD"
//                    ],
//                ],
//            ],
        ];
        $app->menu->create($buttons);
        echo $this->success();
    }

    public function deletemenu()
    {
        $token = $this->request->param('token');
        $menuid = $this->request->param('menuid');
        $config = $this->assembleWxConfig($token);
        $app = Factory::officialAccount($config);
        $app->menu->delete();
        echo $this->success();
    }

}