<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Released under the MIT License.
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------

namespace app\demo\controller;

use cmf\controller\HomeBaseController;

class IndexController extends HomeBaseController
{
    public function index()
    {
        $query = $this->request->param();
        $openid = session('openid');
        if (array_key_exists('token', $query)) {
            session('token', $query['token']);
        }
        if (empty($openid)) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $this->OauthWx($url);
        } else {
            $userId = cmf_get_current_user_id();
            if (empty($userId)) {
//                    $userApi = new \app\cartoon\api\MyUserApi();
//                    $user = $userApi->loadLoginUser(); //自动注册用户
            }
        }
//            return $this->fetch("wechat/wechatpage");
        return $this->fetch(':index');
    }

    public function ws()
    {
        return $this->fetch(':ws');
    }
}
