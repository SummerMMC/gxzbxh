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

namespace app\zbxh\controller;

use app\zbxh\model\PortalPostModel;
use cmf\controller\HomeBaseController;
use EasyWeChat\Factory;
use EasyWeChat\payment\Order;
use think\Db;

class WechatindexController extends HomeBaseController
{
    /**
     * 跳转个人中心
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function usercenter()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $field = "u.user_nickname as user_nickname,u.avatar as avatar,u.id as uid,u.job as job,u.department as department,u.position as position,c.name as cname,c.status as cstatus";
        $user = Db::name("user")->field($field)->alias("u")->leftJoin("user_company uc", "uc.uid = u.id")->leftJoin("company c", "c.id = uc.cid and c.status = 1")->where(["u.openid" => $openid])->find();
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('hideMenuItems'), false);
        $this->assign("jscode", $jscode);
        $this->assign("user", $user);
        return $this->fetch("usercenter");
    }

    /**
     * 跳转认证页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function companycertification()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $this->assign("user", $user);
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('hideMenuItems'), false);
        $this->assign("jscode", $jscode);
        return $this->fetch("companycertification");
    }

    /**
     * 提交企业申请
     * @return string|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function subcompany()
    {
        $param = $this->request->param();
        if (empty($param["name"])) {
            return $this->echoError(null, "请输入组织/企业名称");
        }
        if (empty($param["linkman"])) {
            return $this->echoError(null, "请输入联系人姓名");
        }
        if (empty($param["phone"])) {
            return $this->echoError(null, "请输入正确手机号");
        }
        if (empty($param["addr"])) {
            return $this->echoError(null, "请输入组织/企业地址");
        }
        if (empty($param["code"])) {
            return $this->echoError(null, "请输入认证吗，认证码请联系协会工作人员获取");
        }
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (strtolower($user["code"]) != strtolower($param["code"])) {
            return $this->echoError(null, "认证吗不正确，请联系协会工作人员");
        }
        $uclist = Db::name("user_company")->where(["uid" => $user])->select()->toArray();
        if (count($uclist) > 0) {
            return $this->echoError(null, "此微信已申请认证组织/企业,请勿重复申请");
        }
        $id = Db::name("company")->insertGetId(["name" => $param["name"], "linkman" => $param["linkman"], "phone" => $param["phone"], "addr" => $param["addr"], "status" => 0, "uid" => $user["id"]]);
        Db::name("user_company")->insertGetId(["uid" => $user["id"], "cid" => $id, "lead" => 1]);
        Db::name("user")->update(["mobile" => $param["phone"], "real_name" => $param["linkman"], "id" => $user["id"]]);
        $token = session("token");
        $config = $this->assembleWxConfig($token);
        $app = Factory::officialAccount($config);
        $arra = [
            'first' => '组织/企业认证申请通知',
            'keyword1' => date("Y-m-d", time()),
            'keyword2' => $id . "-" . $param["name"],
            'keyword3' => "申请中",
            'remark' => "请尽快登录后台核实认证组织/企业信息。",
        ];
        $app->template_message->send([
            'touser' => $this->getAdminOpenid(),
            'template_id' => $this->getYwTempid(),
            'url' => "",
            'data' => $arra,
        ]);
        return $this->echoSuccess();
    }


    /**
     * 跳转驾校详情页面
     * @return mixed
     */
    public function schooldetails()
    {
        $param = $this->request->param();
        $id = $param["did"];
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (empty($user["mobile"])) {
            $this->assign("hasphone", 0);
        } else {
            $this->assign("hasphone", 1);
        }
        $this->assign("did", $id);
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('checkJsApi',
            'openLocation',
            'getLocation',
            'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
        $this->assign("jscode", $jscode);
        return $this->fetch("schooldetails");
    }


    /**
     * @return mixed
     */
    public function promise()
    {
        return $this->fetch('promise');
    }

    /**
     * @return mixed
     */
    public function coach()
    {
        $this->isWeChat();
        return $this->fetch('coach');
    }

    /**
     * @return mixed
     */
    public function baokao()
    {
        $this->isWeChat();
        return $this->fetch('baokao');
    }

    /**
     * @return mixed
     */
    public function changjian()
    {
        $this->isWeChat();
        return $this->fetch('changjian');
    }

    /**
     * @return mixed
     */
    public function xueche()
    {
        $this->isWeChat();
        return $this->fetch('xueche');
    }

    /**
     * @return mixed
     */
    public function guidest()
    {
        $this->isWeChat();
        return $this->fetch('guidest');
    }

    /**
     * @return mixed
     */
    public function listcampus()
    {
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('checkJsApi',
            'openLocation',
            'getLocation',
            'hideOptionMenu'), false);
        $this->assign("jscode", $jscode);
        return $this->fetch('listcampus');
    }


    /**
     * 跳转订单列表页
     * @return mixed
     */
    public function rechargelist()
    {
        return $this->fetch("rechargelist");
    }

    /**
     * 获取订单列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getrechargelist()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $where["ur.openid"] = $openid;
        if (array_key_exists("status", $param)) {
            if ($param["status"] != 0) {
                $where["ur.status"] = $param["status"];
            }
        }
        $rechargelist = Db::name("user_recharge")
            ->alias("ur")
            ->field("ur.*,pri.id as pid,pri.name as pname,pri.type as ptype,pri.price as pprice,pri.pick_up as ppick_up,pri.time_frame as ptime_frame,pri.p_one_car as pp_one_car,pri.did as pdid")
            ->join("price pri", "ur.product_id = pri.id")
            ->where($where)->order("ur.ctime desc")->select()->toArray();
        return $this->echoSuccess($rechargelist);
    }

    /**
     * 跳转订单详情页面
     * @return mixed
     */
    public function rechargedetail()
    {
        $param = $this->request->param();
        $this->assign("id", $param['id']);
        return $this->fetch("rechargedetail");
    }

    /**
     * 报名
     * @return mixed
     */
    public function signup()
    {
        $param = $this->request->param();
        $openid = session('openid');
        if (array_key_exists("pid", $param)) {
            $this->assign("pid", $param['pid']);
        } else {
            $this->assign("pid", "");
        }
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (!empty($user["mobile"])) {
            $this->assign("hasphone", "has");
        } else {
            $this->assign("hasphone", "no");
        }
        return $this->fetch("signup");
    }

    /**
     * 获取订单详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getrechargedetail()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $rid = $param["id"];
        $rechargelist = Db::name("user_recharge")
            ->alias("ur")
            ->field("ur.*,pri.id as pid,pri.name as pname,pri.type as ptype,pri.price as pprice,pri.pick_up as ppick_up,pri.time_frame as ptime_frame,pri.p_one_car as pp_one_car,pri.did as pdid,ds.name as dname")
            ->leftJoin("price pri", "ur.product_id = pri.id")
            ->leftJoin("company ds", "pri.did = ds.id")
            ->where(["ur.id" => $rid])->select()->toArray();
        if ($rechargelist[0]['gbid'] != null && $rechargelist[0]['gbid'] != '') {
            $gbresult = Db::name("group_buying")->where(["id" => $rechargelist[0]['gbid']])->find();
            $rulist = Db::name("user_recharge")->where(['gbid' => $rechargelist[0]['gbid'], "status" => 2])->select()->toArray();
            $pcount = count($rulist) + $gbresult['virtual_p_count'];
            if ($pcount >= $gbresult['p_count']) {
                $rechargelist[0]["gisopen"] = false;
            } else if (time() >= $gbresult['f_time']) {
                $rechargelist[0]["gisopen"] = false;
            } else if ($gbresult['status'] != 1) {
                $rechargelist[0]["gisopen"] = false;
            } else {
                $rechargelist[0]["gisopen"] = true;
            }
        }
        return $this->echoSuccess($rechargelist);
    }

    /**
     * 微信小程序下单
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxapprechargeorder()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $user_recharge = [];
        //判断是否有订单I，如果有那就走补款和支付流程
        if (array_key_exists("payoid", $param)) {
            $user_recharge = Db::name("user_recharge")->where(["id" => $param["payoid"]])->find();
            if ($param['paytag'] == 'bk') {
                $money = $user_recharge['supplement_mony'];
                $user_recharge['orderid'] = $user_recharge['bk_orderid'];
            } else if ($param['paytag'] == 'zf') {
                $money = $user_recharge['money'];
            }
            //如果没有就创建订单
        } else {
            //tag代表是否是定金支付 0 是定金
            $tag = $param['tag'];
            $pid = $param['pid'];
            $presult = Db::name("price")->where(["id" => $pid])->find();
            $money = $presult["price"];
            $user_recharge['money'] = $money;
            if ($tag == 0) {
                $sd = Db::name("company")->where(["id" => $presult['did']])->find();
                $user_recharge['deposit'] = $sd['deposit'];
                $user_recharge['deposit_status'] = 1;
                $user_recharge['supplement_mony'] = $money - $user_recharge['deposit'];
                $money = $sd['deposit'];
                $user_recharge['orderid'] = 'DJ' . time() . $user["id"];
                $user_recharge['bk_orderid'] = 'BK' . time() . $user["id"];
            } else {
                $user_recharge['orderid'] = 'JX' . time() . $user["id"];
            }
            $user_recharge['account'] = $user["user_nickname"];
            $user_recharge['uid'] = $user["id"];
            $user_recharge['phone'] = $user['mobile'];
            $user_recharge['chremark'] = "审核中";
            $user_recharge['cashtype'] = 1;
            $user_recharge['operateid'] = $user["id"];
            $user_recharge['status'] = 1;
            $user_recharge['ctime'] = time();
            $user_recharge['re_type'] = "wx";
            $user_recharge['product_id'] = $pid;
            $user_recharge['openid'] = $openid;
            Db::name("user_recharge")->insert($user_recharge);
        }
        $config = $this->wxappzhifuConfig(session("token"), $_SERVER['REQUEST_URI']);
        $res = $this->wxappzhifu($config, $user_recharge, $money, $openid);
        return $this->echoSuccess($res);
    }

    /**
     * 报名支付
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeorder()
    {
        $param = $this->request->param();
        $openid = session("openid");
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $user_recharge = [];
        if (array_key_exists("actid", $param)) {
            $actid = $param['actid'];
        } else {
            $actid = 0;
        }
        //判断是否有订单I，如果有那就走补款和支付流程
        if (array_key_exists("payoid", $param)) {
            $user_recharge = Db::name("user_recharge")->where(["id" => $param["payoid"]])->find();
            if ($param['paytag'] == 'bk') {
                $money = $user_recharge['supplement_mony'];
                $user_recharge['orderid'] = $user_recharge['bk_orderid'];
            } else if ($param['paytag'] == 'dj') {
                $money = $user_recharge['deposit'];
            } else if ($param['paytag'] == 'zf') {
                $money = $user_recharge['money'];
            }
            //如果没有就创建订单
        } else {
            //tag代表是否是定金支付 0 是定金
            $tag = $param['tag'];
            $pid = $param['pid'];
            if ($tag == 0) {
                if ($actid != 0) {
                    $presult = Db::name("activity_price")->where(["aid" => $actid, "apid" => $pid])->find();
                    $money = $presult["aprice"];
                    $user_recharge['money'] = $money;
                    $user_recharge['deposit'] = $presult['adeposit'];
                    $user_recharge['deposit_status'] = 3;
                    $user_recharge['aid'] = $actid;
                    $user_recharge['supplement_mony'] = $money - $user_recharge['deposit'];
                    $money = $presult['adeposit'];
                } else {
                    $presult = Db::name("price")->where(["id" => $pid])->find();
                    $money = $presult["price"];
                    $user_recharge['money'] = $money;
                    $sd = Db::name("company")->where(["id" => $presult['did']])->find();
                    $user_recharge['deposit'] = $sd['deposit'];
                    $user_recharge['deposit_status'] = 3;
                    $user_recharge['supplement_mony'] = $money - $user_recharge['deposit'];
                    $money = $sd['deposit'];
                }
                $user_recharge['orderid'] = 'DJ' . time() . $user["id"];
                $user_recharge['bk_orderid'] = 'BK' . time() . $user["id"];
            } else {
                $presult = Db::name("price")->where(["id" => $pid])->find();
                $money = $presult["price"];
                $user_recharge['money'] = $money;
                $user_recharge['orderid'] = 'JX' . time() . $user["id"];
            }
            $user_recharge['account'] = $user["user_nickname"];
            $user_recharge['uid'] = $user["id"];
            $user_recharge['phone'] = $user['mobile'];
            $user_recharge['chremark'] = "审核中";
            $user_recharge['cashtype'] = 1;
            $user_recharge['operateid'] = $user["id"];
            $user_recharge['status'] = 1;
            $user_recharge['ctime'] = time();
            $user_recharge['re_type'] = "wx";
            $user_recharge['product_id'] = $pid;
            $user_recharge['openid'] = $openid;
            if ($user['pid'] != 0) {
                $user_recharge['pid'] = $user['pid'];
            }
            if (!empty($openid)) {
                Db::name("user_recharge")->insert($user_recharge);
            } else {
                return false;
            }
        }
        $config = $this->wxzhifuConfig(session("token"), $_SERVER['REQUEST_URI']);
        $res = $this->wechatzhifu($config, $user_recharge, $money);
        $this->assign("jsconfig", $res);
        return $this->fetch("rechargeorder");
    }

    /**
     * 微信小程序支付
     * @param $config
     * @param $data
     * @param $money
     * @return array|string|null
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wxappzhifu($config, $data, $money, $openid)
    {
        $app = Factory::payment($config);
        $jssdk = $app->jssdk;
        $result = $app->order->unify([
            'body' => '报名支付',
            'out_trade_no' => $data['orderid'],
            'total_fee' => $money * 100,
            'notify_url' => 'http://jiaxiao.henbaoli.com/jiaxiao/wechatindex/wxzfcallback', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid,
        ]);
        if ($result["return_code"] != 'SUCCESS') {
            dump($result["return_msg"]);
            return null;
        } else {
            $prepayId = $result['prepay_id'];
            $json = $jssdk->bridgeConfig($prepayId);
            return $json;
        }
    }

    /**
     * 微信支付配置组装
     * @param $config
     * @param $data
     * @param $money
     * @return array|string|null
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wechatzhifu($config, $data, $money)
    {
        $app = Factory::payment($config);
        $jssdk = $app->jssdk;
        $openid = session('openid');
        $result = $app->order->unify([
            'body' => '报名支付',
            'out_trade_no' => $data['orderid'],
            'total_fee' => $money * 100,
            'notify_url' => 'http://jiaxiao.henbaoli.com/jiaxiao/systool/wxzfcallback', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid,
        ]);
        if ($result["return_code"] != 'SUCCESS') {
            dump($result["return_msg"]);
            return null;
        } else {
            $prepayId = $result['prepay_id'];
            $json = $jssdk->bridgeConfig($prepayId);
            return $json;
        }
    }

    /**
     * /jiaxiao/index/sendmsgpass
     * 发送验证码
     * 传参：
     *      phone：电话号码
     */
    public function sendmsgpass()
    {
        $query = $this->request->param();
        $phone = $query["phone"];
        $data = [];
        if (empty($phone)) {
            $data["code"] = 1001;
            $data["msg"] = "电话号码不能为空";
            return $this->echoSuccess($data, $data["msg"], $data["code"]);
        }
        if (!preg_match('/(^(13\d|15[^4\D]|17[0135678]|18\d|14\d|19\d|16\d)\d{8})$/', $phone)) {
            $data["code"] = 1002;
            $data["msg"] = "电话号码格式错误";
            return $this->echoSuccess($data, $data["msg"], $data["code"]);
        }
        $uresult = Db::name("user")->where(["mobile" => $phone])->select()->toArray();
        if (count($uresult) > 0) {
            return $this->echoError([], "手机号已经绑定，请勿重复绑定！");
        }
        $code = cmf_get_verification_code($phone);
        if (empty($code)) {
            $data["code"] = 1003;
            $data["msg"] = "验证码发送过多,请明天再试!";
            return $this->echoSuccess($data, $data["msg"], $data["code"]);
        } else {
            $mm = 5 * 60;//PHP的时间是按秒算的
            $expireTime = time() + $mm;
            cmf_verification_code_log($phone, $code, $expireTime);
            $param["phone"] = $phone;
            $param["code"] = $code;
            $data["msg"] = "发送成功!";
            $result = hook_one("send_mobile_verification_code", $param);
//            var_dump($result);
            return $this->echoSuccess($result, $data["msg"]);
        }
    }

    /**
     * 绑定手机号码
     */
    public function bingphone()
    {
        $param = $this->request->param();
        $name = $param["name"];
        $errMsg = cmf_check_verification_code($param["phone"], $param["code"]);
        $uresult = Db::name("user")->where(["mobile" => $param["phone"]])->select()->toArray();
        $openid = session('openid');
        if (count($uresult) > 0) {
            return $this->echoError([], "手机号已经绑定，请勿绑定！");
        }
        if (!empty($errMsg)) {
            return $this->echoError([], "手机号码或者验证码错误，请重新发送！");
        } else {
            Db::name("user")->where(["openid" => $openid])->update(["mobile" => $param["phone"], "real_name" => $name]);
        }
        return $this->echoSuccess();
    }

    /**
     * 提交报名信息
     * @return string|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function savedrivingapply()
    {
        $param = $this->request->param();
        $data = [];
        $data["name"] = $param["name"];

        if (array_key_exists("subject", $param)) {
            $data["subject"] = $param["subject"];
        }
        if (array_key_exists("sid", $param)) {
            $data["sid"] = $param["sid"];
        }
        if (array_key_exists("pid", $param)) {
            $data["pid"] = $param["pid"];
        }

        if (array_key_exists("aid", $param)) {
            $data["aid"] = $param["aid"];
        }

        $data["ctime"] = time();

        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (!empty($user["mobile"])) {
            $data["phone"] = $user["mobile"];
        } else {
            $data["phone"] = $param["phone"];
            $errMsg = cmf_check_verification_code($param["phone"], $param["code"]);
            if (!empty($errMsg)) {
                return $this->echoError([], "手机号码或者验证码错误，请重新发送！");
            }
        }
//        $uresult = Db::name("driving_apply")->where(["phone" => $data["phone"]])->select()->toArray();
//        if (count($uresult) > 0) {
//            return $this->echoError([], "手机号已经报名，请勿重复报名！");
//        } else {
        $data["openid"] = $openid;
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (empty($user["mobile"])) {
            Db::name("user")->where(["id" => $user['id']])->update(["mobile" => $data["phone"]]);
        }
        $data["uid"] = $user["id"];
        Db::name("driving_apply")->insertGetId($data);
        $date = date("Y-m-d H:i", time());
        $arra = [
            'first' => '有新学员报名啦',
            'keyword1' => $data["name"],
            'keyword2' => $data["phone"],
            'keyword3' => $date,
            'remark' => '请您及时回访!',
        ];
        $this->sendtemplatemsg($this->getSystemopenid(), $this->getSignupTempid(), $arra, '');
//        }
        return $this->echoSuccess();
    }

    /**
     * 微信小程序登录，如果没有注册用户就注册用户
     */
    public function getwechatappuser()
    {
        $config = $this->getwxappconfig();
        $param = $this->request->param();
        $code = $param['code'];
        $nickName = $param['nickName'];
        $avatarUrl = $param['avatarUrl'];
        $pid = $param["pid"];
        $app = Factory::miniProgram($config);
        $wxuser = $app->auth->session($code);
        $tempuser = Db::name("user")->where(["openid" => $wxuser['openid']])->find();
        if (count($tempuser) <= 0) {
            $user = $this->createwxappuser($nickName, $avatarUrl, $wxuser['openid'], $pid);
        } else {
            $user = $tempuser;
        }
        return $this->echoSuccess($user);
    }

    public function updateunionid()
    {
        $param = $this->request->param();
        $openid = $param["openid"];
        $iv = $param['iv'];
        $encryptedData = $param["encryptedData"];
        $code = $param["code"];
        $wxjmsj = $this->localwxencryptedData($code, $encryptedData, $iv);
        var_dump($wxjmsj);
//        Db::name("user")->where(["openid"=>$openid])->update([]);
    }

    /**
     * 解密微信小程序信息
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function wxencryptedData()
    {
        $config = $this->getwxappconfig();
        $param = $this->request->param();
        $code = $param['code'];
        $encryptedData = $param['encryptedData'];
        $iv = $param['iv'];
        $app = Factory::miniProgram($config);
        $session = $app->auth->session($code);
        $decryptedData = $app->encryptor->decryptData($session['session_key'], $iv, $encryptedData);
        return $this->echoSuccess($decryptedData);
    }

    /**
     * 解密微信小程序信息
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function localwxencryptedData($code, $encryptedData, $iv)
    {
        $config = $this->getwxappconfig();
        $app = Factory::miniProgram($config);
        $session = $app->auth->session($code);
        $decryptedData = $app->encryptor->decryptData($session['session_key'], $iv, $encryptedData);
        return $decryptedData;
    }

    /**
     * 微信绑定手机号
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function wxbindphon()
    {
        $param = $this->request->param();
        $phone = $param['phone'];
        $openid = $param['openid'];
        Db::name("user")->where(["openid" => $openid])->update(["mobile" => $phone]);
        return $this->echoSuccess();
    }

    /**
     * 根据ID查找产品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getprice()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $price = Db::name("price")->where(["id" => $id])->find();
        return $this->echoSuccess($price);
    }

    public function createqr()
    {
        $param = $this->request->param();
        if (array_key_exists("upid", $param)) {
            $config = $this->getwxappconfig();
            $app = Factory::miniProgram($config);
            $response = $app->app_code->getUnlimit("upid=" . $param["upid"], [
                "page" => "pages/index/index",
                "width" => 600,
            ]);
            var_dump($response);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $filename = $response->saveAs('/public/upload/userqr/', $param["upid"] . '.png');
                var_dump($filename);
            }
        }

    }

    /**
     * 跳转网页二维码页面
     * @return mixed
     */
    public function createwebqr()
    {
        return $this->fetch('createwebqr');
    }

    /**
     * 通用获取用户方法
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getuser()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        return $this->echoSuccess($user);
    }

    /**
     * 生成保存推广二维码
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function getwebqr()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $img = $param['img'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
            $type = $result[2];
            $new_file = "upload/userwebqr/" . time() . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img)))) {
                Db::name("user")->where(["openid" => $openid])->update(["tuiguang_qr" => $new_file]);
            }
        }
        return $this->echoSuccess(["img" => $new_file]);
    }

    /**
     * 绑定手机号
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function bindphone()
    {
        $param = $this->request->param();
        $phone = $param['phone'];
        $openid = session("openid");
        Db::name("user")->where(["openid" => $openid])->update(["mobile" => $phone]);
        return $this->echoSuccess();
    }

    public function bindphoneweb()
    {
        return $this->fetch("bindphoneweb");
    }

    /**
     * 跳转打开地图
     * @return mixed
     */
    public function schoolmap()
    {
        $param = $this->request->param();
        $this->assign("schoollng", $param['schoollng']);
        $this->assign("schoollat", $param['schoollat']);
        $this->assign("selflat", $param['selflat']);
        $this->assign("selflng", $param['selflng']);

        return $this->fetch("schoolmap");
    }

    /**
     * 跳转报名地址
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function signupmap()
    {
        $param = $this->request->param();
        $this->assign("selflat", $param['selflat']);
        $this->assign("selflng", $param['selflng']);
        return $this->fetch("signupmap");
    }

    public function exercisetime()
    {
        return $this->fetch("exercisetime");
    }

    /**
     * 获取学员学时
     */
    public function getinviteresult()
    {
        $openid = session("openid");
        $now = date("Y-m-d");
        $sql = "select student_name,count(student_openid) as scount from cmf_invite where invite_time < '" . $now . "' and status = 1 and instructor_openid = '" . $openid . "' group by student_openid";
        $result = Db::query($sql);
        return $this->echoSuccess($result);
    }


}
