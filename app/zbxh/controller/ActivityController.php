<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\jiaxiao\controller;
//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\HomeBaseController;
use EasyWeChat\Factory;
use think\Db;

/**
 * 个人中心
 */
class ActivityController extends HomeBaseController
{
    /**
     * 验证微信信息
     */
    public function earnest()
    {
        $param = $this->request->param();
        if (array_key_exists("pid", $param)) {
            session("pid", $param['pid']);
            $this->assign("pid", session("pid"));
        } else {
            $this->assign("pid", 0);
        }
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (empty($user["mobile"])) {
            $this->assign("hasphone", 0);
        } else {
            $this->assign("hasphone", 1);
        }
        $this->assign("aid", "1");
        $config = $this->assembleWxConfig(session('token'));
        $app = Factory::officialAccount($config);
        $jssdk = $app->jssdk->buildConfig(array('updateAppMessageShareData', 'updateTimelineShareData'), false);
        $this->assign("jssdk", $jssdk);
        return $this->fetch("earnest");
    }

}
