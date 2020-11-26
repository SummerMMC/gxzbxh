<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;

class AdminBaseController extends BaseController
{

    protected function initialize()
    {
        // 监听admin_init
        hook('admin_init');
        parent::initialize();
        $sessionAdminId = session('ADMIN_ID');
        if (!empty($sessionAdminId)) {
            $user = Db::name('user')->where('id', $sessionAdminId)->find();

            if (!$this->checkAccess($sessionAdminId)) {
                $this->error("您没有访问权限！");
            }
            $this->assign("admin", $user);
        } else {
            if ($this->request->isPost()) {
                $this->error("您还没有登录！", url("admin/public/login"));
            } else {
                return $this->redirect(url("admin/Public/login"));
            }
        }
    }

    protected function getSiteHost()
    {
        return "http://wx.gxzbxh.com";
    }

    protected function getAdminOpenid()
    {
        return "oNEUX66FDz9W2xowvq2ULDiFSEiE";
    }

    protected function getYwTempid()
    {
        return "8MUOzH3KTliO686Q6F6ILF6o1yWazxH8nbZZB5srTcw";
    }

    public function _initializeView()
    {
        $cmfAdminThemePath = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();

        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        $root = cmf_get_root();

        //使cdn设置生效
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$root}/{$themePath}",
                '__STATIC__' => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__' => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }

        config('template.view_base', WEB_ROOT . "$themePath/");
        config('template.tpl_replace_string', $viewReplaceStr);
    }

    /**
     * 初始化后台菜单
     */
    public function initMenu()
    {
    }

    /**
     *  检查后台用户访问权限
     * @param int $userId 后台用户id
     * @return boolean 检查通过返回true
     */
    private function checkAccess($userId)
    {
        // 如果用户id是1，则无需判断
        if ($userId == 1) {
            return true;
        }

        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        $rule = $module . $controller . $action;

        $notRequire = ["adminIndexindex", "adminMainindex"];
        if (!in_array($rule, $notRequire)) {
            return cmf_auth_check($userId);
        } else {
            return true;
        }
    }

    /**
     * 拼装微信配置信息
     * @param $wxdata
     * @return array
     */
    public function assembleWxConfig($token)
    {
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        $config = [
            'app_id' => $wxconfig[0]['app_id'],
            'secret' => $wxconfig[0]['secret'],
            'token' => $wxconfig[0]['token'],
            'aes_key' => $wxconfig[0]['EncodingAESKey'],
            'response_type' => $wxconfig[0]['response_type'],
        ];
        return $config;
    }

    /**
     * 生成随机账号
     * @param $length
     * @param string $chars
     * @return string
     */
    function random($length, $chars = '0123456789')
    {
        $hash = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }
}