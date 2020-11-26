<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use app\zbxh\model\PortalPostModel;
use EasyWeChat\Factory;
use think\Db;
use app\admin\model\ThemeModel;
use think\facade\View;

class HomeBaseController extends BaseController
{

    protected function initialize()
    {
        // 监听home_init
        hook('home_init');
        parent::initialize();
        $siteInfo = cmf_get_site_info();
        View::share('site_info', $siteInfo);
        if (!empty(session("openid"))) {
        } else {
            $this->isWeChat();
        }
        $token = session("token");
        if (empty($token)) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        if (count($wxconfig) <= 0) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
        $this->assign("temphost", "http://zb.aihuaxin.com");
    }

    protected function _initializeView()
    {
        $cmfThemePath = config('template.cmf_theme_path');
        $cmfDefaultTheme = cmf_get_current_theme();

        $themePath = "{$cmfThemePath}{$cmfDefaultTheme}";

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

        config('template.view_base', WEB_ROOT . "{$themePath}/");
        config('template.tpl_replace_string', $viewReplaceStr);

        $themeErrorTmpl = "{$themePath}/error.html";
        if (file_exists_case($themeErrorTmpl)) {
            config('dispatch_error_tmpl', $themeErrorTmpl);
        }

        $themeSuccessTmpl = "{$themePath}/success.html";
        if (file_exists_case($themeSuccessTmpl)) {
            config('dispatch_success_tmpl', $themeSuccessTmpl);
        }


    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $config = [])
    {
        $template = $this->parseTemplate($template);
        $more = $this->getThemeFileMore($template);
        $this->assign('theme_vars', $more['vars']);
        $this->assign('theme_widgets', $more['widgets']);
        $content = $this->view->fetch($template, $vars, $config);

        $designingTheme = cookie('cmf_design_theme');

        if ($designingTheme) {
            $app = $this->request->module();
            $controller = $this->request->controller();
            $action = $this->request->action();

            $output = <<<hello
<script>
var _themeDesign=true;
var _themeTest="test";
var _app='{$app}';
var _controller='{$controller}';
var _action='{$action}';
var _themeFile='{$more['file']}';
if(parent && parent.simulatorRefresh){
  parent.simulatorRefresh();  
}
</script>
hello;

            $pos = strripos($content, '</body>');
            if (false !== $pos) {
                $content = substr($content, 0, $pos) . $output . substr($content, $pos);
            } else {
                $content = $content . $output;
            }
        }

        return $content;
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template)
    {
        // 分析模板文件规则
        $request = $this->request;
        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($module, $template) = explode('@', $template);
        }

        $viewBase = config('template.view_base');

        if ($viewBase) {
            // 基础视图目录
            $module = isset($module) ? $module : $request->module();
            $path = $viewBase . ($module ? $module . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($module) ? APP_PATH . $module . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : config('template.view_path');
        }

        $depr = config('template.view_depr');
        if (0 !== strpos($template, '/')) {
            $template = str_replace(['/', ':'], $depr, $template);
            $controller = cmf_parse_name($request->controller());
            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认规则定位
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . cmf_parse_name($request->action(true));
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $path . ltrim($template, '/') . '.' . ltrim(config('template.view_suffix'), '.');
    }

    /**
     * 获取模板文件变量
     * @param string $file
     * @param string $theme
     * @return array
     */
    private function getThemeFileMore($file, $theme = "")
    {

        //TODO 增加缓存
        $theme = empty($theme) ? cmf_get_current_theme() : $theme;

        // 调试模式下自动更新模板
        if (APP_DEBUG) {
            $themeModel = new ThemeModel();
            $themeModel->updateTheme($theme);
        }

        $themePath = config('template.cmf_theme_path');
        $file = str_replace('\\', '/', $file);
        $file = str_replace('//', '/', $file);
        $webRoot = str_replace('\\', '/', WEB_ROOT);
        $themeFile = str_replace(['.html', '.php', $themePath . $theme . "/", $webRoot], '', $file);

        $files = Db::name('theme_file')->field('more')->where('theme', $theme)
            ->where(function ($query) use ($themeFile) {
                $query->where('is_public', 1)->whereOr('file', $themeFile);
            })->select();

        $vars = [];
        $widgets = [];
        foreach ($files as $file) {
            $oldMore = json_decode($file['more'], true);
            if (!empty($oldMore['vars'])) {
                foreach ($oldMore['vars'] as $varName => $var) {
                    $vars[$varName] = $var['value'];
                }
            }

            if (!empty($oldMore['widgets'])) {
                foreach ($oldMore['widgets'] as $widgetName => $widget) {

                    $widgetVars = [];
                    if (!empty($widget['vars'])) {
                        foreach ($widget['vars'] as $varName => $var) {
                            $widgetVars[$varName] = $var['value'];
                        }
                    }

                    $widget['vars'] = $widgetVars;
                    //如果重名，则合并配置
                    if (empty($widgets[$widgetName])) {
                        $widgets[$widgetName] = $widget;
                    } else {
                        foreach ($widgets[$widgetName] as $key => $value) {
                            if (is_array($widget[$key])) {
                                $widgets[$widgetName][$key] = array_merge($widgets[$widgetName][$key], $widget[$key]);
                            } else {
                                $widgets[$widgetName][$key] = $widget[$key];
                            }
                        }
                    }
                }
            }
        }

        return ['vars' => $vars, 'widgets' => $widgets, 'file' => $themeFile];
    }

    public function checkUserLogin()
    {
        $userId = cmf_get_current_user_id();
        if (empty($userId)) {
            if ($this->request->isAjax()) {
                $this->error("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }
    }

    /**
     * 返回成功!
     * @param $data
     */
    function echoSuccess($data = [], $msg = "操作成功！", $code = -1)
    {
        echo json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 返回错误
     * @param $status
     * @param $data
     * @return string
     */
    function echoError($data = [], $msg = "操作失败！", $code = 0)
    {
        echo json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 拼装微信配置信息
     * @param $wxdata
     * @return array
     */
    public function assembleWxConfig($token)
    {
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        if (count($wxconfig) <= 0) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
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
     * 拼装微信配置信息
     * @param $wxdata
     * @return array
     */
    public function assembleWxhostConfig($token, $requrl)
    {
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        if (count($wxconfig) <= 0) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
        $config = [
            'app_id' => $wxconfig[0]['app_id'],
            'secret' => $wxconfig[0]['secret'],
            'token' => $wxconfig[0]['token'],
            'response_type' => $wxconfig[0]['response_type'],
            'aes_key' => $wxconfig[0]['EncodingAESKey'],
            'oauth' => [
                'scopes' => ['snsapi_userinfo'],
                'callback' => 'zbxh/index/oauthcallback?token=' . $wxconfig[0]['token'] . '&requrl=' . $requrl,
            ],
        ];
        return $config;
    }

    /**
     * 拼装微信支付配置文件
     * @param $token
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxzhifuConfig($token, $requrl)
    {
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        if (count($wxconfig) <= 0) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
        $config = [
            // 前面的appid什么的也得保留哦
            'app_id' => 'wx92f4fb5f7aecc797',
            'mch_id' => '1586202511',
            'key' => 'ImOZ3qyPtZAiFwon04JEcZ4vQmEkokNn',
            'cert_path' => 'jskey/apiclient_cert.pem', // XXX: 绝对路径！！！！
            'key_path' => 'jskey/apiclient_key.pem',      // XXX: 绝对路径！！！！
            'notify_url' => '',     // 你也可以在下单时单独设置来想覆盖它
            // 'device_info'     => '013467007045764',
            // 'sub_app_id'      => '',
            // 'sub_merchant_id' => '',
            // ...
        ];
        return $config;
    }

    public function wxappzhifuConfig($token, $requrl)
    {
        $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
        if (count($wxconfig) <= 0) {
            $token = $this->gethosttotoken($_SERVER["HTTP_HOST"]);
            $wxconfig = Db::name("wx_config")->where(['token' => $token])->select();
            session("token", $token);
        }
        $config = [
            // 前面的appid什么的也得保留哦
            'app_id' => 'wx311b83fe51a42cfc',
            'mch_id' => '1586202511',
            'key' => 'ImOZ3qyPtZAiFwon04JEcZ4vQmEkokNn',
            'cert_path' => 'jskey/apiclient_cert.pem', // XXX: 绝对路径！！！！
            'key_path' => 'jskey/apiclient_key.pem',      // XXX: 绝对路径！！！！
            'notify_url' => '',     // 你也可以在下单时单独设置来想覆盖它
            // 'device_info'     => '013467007045764',
            // 'sub_app_id'      => '',
            // 'sub_merchant_id' => '',
            // ...
        ];
        return $config;
    }

    /**
     * 进行微信网页授权
     * @return mixed
     */
    public function OauthWx()
    {
        $token = $this->gethosttotoken($_SERVER['HTTP_HOST']);

        $wehost = Db::name("wx_config")->where(['token' => $token])->select()->toArray();

        $config = $this->assembleWxhostConfig($token, $_SERVER['REQUEST_URI']);

        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;
        $oauth->redirect()->send();
    }


    /**
     * 根据域名反向获取token
     * @param $host
     * @return string
     */
    public function gethosttotoken($host)
    {
        $host = str_replace('www.', '', $host);
        $host = str_replace('zb.', '', $host);
        $host = str_replace('.com', '', $host);
        $host = str_replace('.cn', '', $host);
        $host = str_replace('.net', '', $host);
        $sitearray = Db::query("select * from cmf_wx_config where host like '%" . $host . "%'");

        if (count($sitearray) > 0) {
            return $sitearray[0]['token'];
        } else {
            return "";
        }

    }

    /**
     * 创建维护用户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creatuser()
    {
        $wxuser = session('wechate_user');
        $openid = session('openid');
        $uresult = Db::name("user")->where(["openid" => $openid])->select()->toArray();
        if (count($uresult) > 0) {
            cmf_update_current_user($uresult[0]);//设置登陆
            $pid = session("pid");
            if (!empty($pid)) {
                Db::name("log")->insert(["key" => "用户更新", "value" => $pid]);
                if ($uresult[0]['pid'] == 0) {
                    Db::name("user")->where(["id" => $uresult[0]['id']])->update(["pid"]);
                }
            }
        } else {
            $pid = session("pid");
            Db::name("log")->insert(["key" => "用户更新", "value" => $pid]);
            $user['user_type'] = 4;
            $user['sex'] = 0;
            if (!empty($pid)) {
                $user['pid'] = $pid;
            }
            $user['create_time'] = time();
            $user['last_login_time'] = time();
            $user['user_status'] = 1;
            $result = $this->random(8, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
            $user['user_login'] = $result;
            $user['last_login_ip'] = get_client_ip(0, true);
            $user['user_pass'] = cmf_password($result);
            $user['user_nickname'] = $wxuser['original']['nickname'];
            $user['avatar'] = $wxuser['original']['headimgurl'];
            $user['openid'] = $wxuser['original']['openid'];
            $userId = Db::name("user")->insertGetId($user);
            $data = Db::name("user")->where('id', $userId)->find();
            cmf_update_current_user($data);//设置登陆
        }
    }

    /**
     * 保存微信小程序用户
     * @param $user_nickname
     * @param $avatar
     * @param $openid
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createwxappuser($user_nickname, $avatar, $openid, $pid)
    {
        $user['user_type'] = 4;
        $user['sex'] = 0;
        $user['create_time'] = time();
        $user['last_login_time'] = time();
        $user['user_status'] = 1;
        $result = $this->random(8, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
        $user['user_login'] = $result;
        $user['last_login_ip'] = get_client_ip(0, true);
        $user['user_pass'] = cmf_password($result);
        $user['user_nickname'] = $user_nickname;
        $user['avatar'] = $avatar;
        $user['openid'] = $openid;
        if ($pid != 0) {
            $user['pid'] = $pid;
        }
        $userId = Db::name("user")->insertGetId($user);
        $data = Db::name("user")->where('id', $userId)->find();
        return $data;
    }

    /**
     *  获取可用域名
     * @param $host
     */
    public function getusablehost($host)
    {
        $host = str_replace('www.', '', $host);
        $host = str_replace('jiaxiao.', '', $host);
        $host = str_replace('.com', '', $host);
        $host = str_replace('.cn', '', $host);
        $host = str_replace('.net', '', $host);
        $sitearray = Db::query("select * from cmf_site_domino where is_usable = 1 and domino like '%" . $host . "%' and pid = 0 order by list_order");
        if (count($sitearray) > 0) {
            $result = Db::name("site_domino")->where(['is_usable' => 1, 'pid' => $sitearray[0]['id']])->select()->toArray();
            if (count($result) > 0) {
                return $result[0]['domino'];
            }
        } else {
            return "";
        }
    }

    /**
     * 判断是否微信打开
     */
    public function isWeChat()
    {
        $ua = $this->request->header('user-agent');
        $param = $this->request->param();
        //MicroMessenger 是android/iphone版微信所带的
        if (strpos($ua, 'MicroMessenger') !== false) {
            $this->assign("is_wechat", "yes");
            $openid = session('openid');
            $token = session('token');

            if (array_key_exists('token', $param)) {
                session('token', $param['token']);
            }
            if (empty($openid) || empty($token)) {
                $this->OauthWx();
            }
//            $userId = cmf_get_current_user_id();
//            if (empty($userId)) {
//                $this->OauthWx();
//            }
            return true;
        } else {
            $this->assign("is_wechat", "no");
            return $this->redirect("/zbxh/wechatotherindex/wechatopen");
        }
        return false;
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

    /**
     * 随机姓
     * @return mixed
     */
    public function rdname()
    {
        $xing_d = ['赵', '钱', '孙', '李', '周', '吴', '郑', '王', '冯', '陈', '褚', '卫', '蒋', '沈', '韩', '杨', '朱', '秦', '尤', '许', '何', '吕', '施', '张', '孔', '曹', '严', '华', '金', '魏', '陶', '姜', '戚', '谢', '邹', '喻', '柏', '水', '窦', '章', '云', '苏', '潘', '葛', '奚', '范', '彭', '郎', '鲁', '韦', '昌', '马', '苗', '凤', '花', '方', '任', '袁', '柳', '鲍', '史', '唐', '费', '薛', '雷', '贺', '倪', '汤', '滕', '殷', '罗', '毕', '郝', '安', '常', '傅', '卞', '齐', '元', '顾', '孟', '平', '黄', '穆', '萧', '尹', '姚', '邵', '湛', '汪', '祁', '毛', '狄', '米', '伏', '成', '戴', '谈', '宋', '茅', '庞', '熊', '纪', '舒', '屈', '项', '祝', '董', '梁', '杜', '阮', '蓝', '闵', '季', '贾', '路', '娄', '江', '童', '颜', '郭', '梅', '盛', '林', '钟', '徐', '邱', '骆', '高', '夏', '蔡', '田', '樊', '胡', '凌', '霍', '虞', '万', '支', '柯', '管', '卢', '莫', '柯', '房', '裘', '缪', '解', '应', '宗', '丁', '宣', '邓', '单', '杭', '洪', '包', '诸', '左', '石', '崔', '吉', '龚', '程', '嵇', '邢', '裴', '陆', '荣', '翁', '荀', '于', '惠', '甄', '曲', '封', '储', '仲', '伊', '宁', '仇', '甘', '武', '符', '刘', '景', '詹', '龙', '叶', '幸', '司', '黎', '溥', '印', '怀', '蒲', '邰', '从', '索', '赖', '卓', '屠', '池', '乔', '胥', '闻', '莘', '党', '翟', '谭', '贡', '劳', '逄', '姬', '申', '扶', '堵', '冉', '宰', '雍', '桑', '寿', '通', '燕', '浦', '尚', '农', '温', '别', '庄', '晏', '柴', '瞿', '阎', '连', '习', '容', '向', '古', '易', '廖', '庾', '终', '步', '都', '耿', '满', '弘', '匡', '国', '文', '寇', '广', '禄', '阙', '东', '欧', '利', '师', '巩', '聂', '关', '荆'];
        $x = $xing_d[mt_rand(0, count($xing_d) - 1)];
        return $x;
    }

    /**
     * 获取小程序配置
     * @return array
     */
    public function getwxappconfig()
    {
        return [
            'app_id' => 'wx311b83fe51a42cfc',
            'secret' => 'd3f35b4e0f5fe72505ad229f43f48714',

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

        ];
    }

    /**
     * 更新用户为已支付
     * @param $uid
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateusertoprice($uid)
    {
        Db::name("user")->where(["id" => $uid])->update(["is_price" => 1]);
    }

    /**
     * 修改团购用户为已支付
     * @param $gid
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateusertopricebygid($gid)
    {
        $gresult = Db::name("user_recharge")->where(['gbid' => $gid])->select()->toArray();
        foreach ($gresult as $item) {
            Db::name("user")->where(["id" => $item['uid']])->update(["is_price" => 1]);
        }
    }

    /**
     * 发送模板信息
     * @param $openid
     * @param $tempid
     * @param $msgarray
     * @param $url
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendtemplatemsg($openid, $tempid, $msgarray, $url)
    {
        $token = session("token");
        $config = $this->assembleWxConfig($token);
        $app = Factory::officialAccount($config);
        $app->template_message->send([
            'touser' => $openid,
            'template_id' => $tempid,
            'url' => $url,
            'data' => $msgarray,
        ]);
    }

    /**
     * 获取预约模板ID
     * @return string
     */
    public function getInviteTempid()
    {
        return "EL2AUf2vTReEXNQqgS_6AQazvpcFUvggYoNtdaoiy1U";
    }

    /**
     * 获取报名模板ID
     * @return string
     */
    public function getSignupTempid()
    {
        return "nBEPKr65xE_18uGwIdkgEuKsRjjgf3ZVoT5qMeuGCJM";
    }

    /**
     * 获取系统openid
     * @return string
     */
    public function getSystemopenid()
    {
        return "oAWh1xPdM4PqB2tYpr7-vNoiC5D4";
    }

    /**
     * 获取缴费模板id
     * @return string
     */
    public function getRechargeTempid()
    {
        return "26mN4oV72mVb9tEE7Z4et1V0lxJlUl-4Vnoj_kMbc98";
    }

    /**
     * 获取缓存
     * @param $key
     * @return bool|mixed
     */
    public function getCache($key)
    {
        $cache = cache($key);
        if ($cache === false) {
            return false;
        } else {
            return $cache;
        }
    }

    /**
     * 设置缓存
     * @param $key
     * @param $reuslt
     * @param int $time
     */
    public function setCache($key, $reuslt, $time = 3600)
    {
        cache($key, $reuslt, $time);
    }

    /**
     * 获取菜单
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenu($mid)
    {
        if ($mid == 0) {
            if ($this->getCache("menu") === false) {
                $menu = Db::name("portal_category")->where(["delete_time" => 0, "status" => 1])->order("list_order desc")->select()->toArray();
                $this->setCache("menu", $menu);
                return $menu;
            } else {
                return $this->getCache("menu");
            }
        } else {
            if ($this->getCache("menu_" . $mid) === false) {
                $menu = Db::name("portal_category")->where(["delete_time" => 0, "status" => 1, "parent_id" => $mid])->order("list_order desc")->select()->toArray();
                foreach ($menu as $key => $item) {
                    $th = json_decode($item['more'], true);
                    $menu[$key]["more"] = cmf_get_image_url($th["thumbnail"]);
                }
                $this->setCache("menu_" . $mid, $menu);
                return $menu;
            } else {
                return $this->getCache("menu_" . $mid);
            }
        }

    }

    /**
     * 获取顶级菜单ID
     * @param $cid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTopcid($cid)
    {
        if ($cid != 0) {
            if ($this->getCache("topcid_" . $cid) === false) {
                $menu = Db::name("portal_category")->where(["delete_time" => 0, "status" => 1, "id" => $cid])->find();
                if ($menu['parent_id'] == 0) {
                    return ['cid' => $cid, "post_id" => $menu['post_id']];
                } else {
                    return ['cid' => $menu['parent_id'], "post_id" => $menu['post_id']];
                }
                $this->setCache("topcid_" . $cid, $menu);
            } else {
                $menu = $this->getCache("topcid_" . $mid);
                if ($menu['parent_id'] == 0) {
                    return ['cid' => $cid, "post_id" => $menu['post_id']];
                } else {
                    return ['cid' => $menu['parent_id'], "post_id" => $menu['post_id']];
                }
            }
        }
    }

    /**
     * 获取资讯中心数据
     * @throws \think\exception\DbException
     */
    public function getlistdata($cid, $limit)
    {
        $param = $this->request->param();
        $postmodel = new PortalPostModel();
        $postlist = $postmodel->getPortalListByCid($cid, $limit, "p.is_top desc,pcp.list_order desc,p.published_time desc");
        foreach ($postlist as $item) {
            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        return $postlist;
    }
}