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

namespace app\jiaxiao\controller;

use cmf\controller\HomeBaseController;
use EasyWeChat\Factory;
use EasyWeChat\payment\Order;
use think\Db;

class GroupbuyingController extends HomeBaseController
{
    /**
     * 跳转拼团列表
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        return $this->fetch('index');
    }

    /**
     * 获取团购列表数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getgbdata()
    {
        $param = $this->request->param();
        $where["isdelete"] = 0;
        if (array_key_exists("status", $param)) {
            $where["gb.status"] = $param['status'];
        }
        $gbresult = Db::name("group_buying")
            ->alias("gb")
            ->field("gb.*,ds.name as dname,ds.thumbnail as dthumbnail,ds.addr as daddr,p.name as pname,p.type as ptype,p.price as pprice,p.pick_up as ppick_up,p.time_frame as ptime_frame,p.p_one_car as pp_one_car")
            ->join("company ds", "gb.sid = ds.id")
            ->join("price p", "gb.pid = p.id")
            ->where($where)
            ->select()->toArray();
        for ($i = 0; $i < count($gbresult); $i++) {
            $gbresult[$i]['dthumbnail'] = cmf_get_image_url($gbresult[$i]['dthumbnail']);
        }
        return $this->echoSuccess($gbresult);
    }

    public function mygroubyinglistData()
    {
        $param = $this->request->param();
        $where["gb.isdelete"] = 0;
        $openid = session('openid');
        $where["ur.openid"] = $openid;
        $gbresult = Db::name("user_recharge")
            ->alias("ur")
            ->field("gb.*,ds.name as dname,ds.thumbnail as dthumbnail,ds.addr as daddr,p.name as pname,p.type as ptype,p.price as pprice,p.pick_up as ppick_up,p.time_frame as ptime_frame,p.p_one_car as pp_one_car")
            ->join('group_buying gb', "ur.gbid = gb.id")
            ->join("company ds", "gb.sid = ds.id")
            ->join("price p", "gb.pid = p.id")
            ->where($where)
            ->select()->toArray();
        for ($i = 0; $i < count($gbresult); $i++) {
            $gbresult[$i]['dthumbnail'] = cmf_get_image_url($gbresult[$i]['dthumbnail']);
        }
        return $this->echoSuccess($gbresult);
    }

    /**
     * 跳转团购详情页面
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gbdetail()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $openid = session("openid");
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('updateAppMessageShareData', 'updateTimelineShareData'), false);
        $user = Db::name("user")->where(["openid" => $openid])->find();
        if (empty($user["mobile"])) {
            $this->assign("hasphone", 0);
        } else {
            $this->assign("hasphone", 1);
        }
        $this->assign("id", $id);
        $this->assign("jscode", $jscode);
        return $this->fetch("gbdetail");
    }

    /**
     * 获取团购详情数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getgbdetail()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $where["gb.id"] = $id;
        $where["isdelete"] = 0;

        $gbresult = Db::name("group_buying")
            ->alias("gb")
            ->field("gb.*,ds.name as dname,ds.thumbnail as dthumbnail,ds.addr as daddr,p.name as pname,p.type as ptype,p.price as pprice,p.pick_up as ppick_up,p.time_frame as ptime_frame,p.p_one_car as pp_one_car")
            ->join("company ds", "gb.sid = ds.id")
            ->join("price p", "gb.pid = p.id")
            ->where($where)
            ->find();
        $openid = session('openid');
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $recharge = Db::name("user_recharge")->where(["gbid" => $id, "status" => 2])->select()->toArray();
        $openid = session("openid");
        $rechargeuser = Db::name("user_recharge")->where(["gbid" => $id, "status" => 2, "openid" => $openid])->select()->toArray();
        if (count($rechargeuser) > 0) {
            $gbresult['is_join'] = 1;
        } else {
            $gbresult['is_join'] = 0;
        }
        if (count($recharge) > 0) {
            $gbresult['recharge'] = array_reverse($recharge);
        } else {
            $gbresult['recharge'] = [];
        }
        $gbresult['dthumbnail'] = cmf_get_image_url($gbresult['dthumbnail']);
        if ($gbresult['virtual_p_count'] > 0) {
            $gbresult['virtual_name'] = array_reverse(json_decode($gbresult['virtual_name'], true));
        } else {
            $gbresult['virtual_name'] = [];
        }
        if (empty($user['mobile'])) {
            $gbresult['hasphone'] = false;
        } else {
            $gbresult['hasphone'] = true;
        }
        return $this->echoSuccess($gbresult);
    }

    /**
     * 支付参团【小程序用】
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxappjoingroubuying()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $gbresult = Db::name("group_buying")->where(["id" => $id, "isdelete" => 0])->find();
        $gbrechage = Db::name("user_recharge")->where(["gbid" => $id, "status" => 2])->select()->toArray();
        $pcount = count($gbrechage) + $gbresult['virtual_p_count'];
        if ($gbresult['status'] != 1) {
            echo "团购已结束！";
            return $this->fetch("rechargeorder");
        }
        if ($pcount >= $gbresult['p_count']) {
            echo "团购已超过人数上线！";
            return $this->fetch("rechargeorder");
        }
        if (time() >= $gbresult['f_time']) {
            echo "团购已超时！";
            return $this->fetch("rechargeorder");
        }
        $openid = $param['openid'];
        foreach ($gbrechage as $item) {
            if ($item['openid'] == $openid) {
                echo "您已参加团购，请勿重复参加！";
                return $this->fetch("rechargeorder");
            }
        }
        $res = $this->wxapprechargeorder($openid, $gbresult);
        return $this->echoSuccess($res);
    }

    /**
     * 支付参团【微信h5用】
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function joingroubuying()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $gbresult = Db::name("group_buying")->where(["id" => $id, "isdelete" => 0])->find();
        $gbrechage = Db::name("user_recharge")->where(["gbid" => $id, "status" => 2])->select()->toArray();
        $pcount = count($gbrechage) + $gbresult['virtual_p_count'];
        if ($gbresult['status'] != 1) {
            echo "团购已结束！";
            return $this->fetch("rechargeorder");
        }
        if ($pcount >= $gbresult['p_count']) {
            echo "团购已超过人数上线！";
            return $this->fetch("rechargeorder");
        }
        if (time() >= $gbresult['f_time']) {
            echo "团购已超时！";
            return $this->fetch("rechargeorder");
        }
        $openid = session("openid");
        foreach ($gbrechage as $item) {
            if ($item['openid'] == $openid) {
                echo "您已参加团购，请勿重复参加！";
                return $this->fetch("rechargeorder");
            }
        }
        $res = $this->rechargeorder($openid, $gbresult);
        $this->assign("jsconfig", $res);
        return $this->fetch("wechatindex/rechargeorder");
    }

    /**
     * 组装团购订单【微信小程序用】
     * @param $openid
     * @param $gbresult
     * @return array|string|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxapprechargeorder($openid, $gbresult)
    {
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $pid = $gbresult['pid'];
        $phone = $user['mobile'];
        $money = $gbresult["g_price"];
        $user_recharge['account'] = $user["user_nickname"];
        $user_recharge['uid'] = $user["id"];
        $user_recharge['phone'] = $phone;
        $user_recharge['money'] = $money;
        $user_recharge['orderid'] = 'TG' . time() . $user["id"];
        $user_recharge['chremark'] = "审核中";
        $user_recharge['cashtype'] = 1;
        $user_recharge['operateid'] = $user["id"];
        $user_recharge['status'] = 1;
        $user_recharge['ctime'] = time();
        $user_recharge['re_type'] = "wx";
        $user_recharge['product_id'] = $pid;
        $user_recharge['openid'] = $openid;
        $user_recharge['gbid'] = $gbresult['id'];
        Db::name("user_recharge")->insert($user_recharge);
        $config = $this->wxappzhifuConfig(session("token"), $_SERVER['REQUEST_URI']);
        $res = $this->wxappzhifu($config, $user_recharge, $openid);
        return $res;
    }

    /**
     * 拼装团购订单【微信H5用】
     * @param $openid
     * @param $gbresult
     * @return array|string|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeorder($openid, $gbresult)
    {
        $user = Db::name("user")->where(["openid" => $openid])->find();
        $pid = $gbresult['pid'];
        $phone = $user['mobile'];
        $money = $gbresult["g_price"];
        $user_recharge['account'] = $user["user_nickname"];
        $user_recharge['uid'] = $user["id"];
        $user_recharge['phone'] = $phone;
        $user_recharge['money'] = $money;
        $user_recharge['orderid'] = 'TG' . time() . $user["id"];
        $user_recharge['chremark'] = "审核中";
        $user_recharge['cashtype'] = 1;
        $user_recharge['operateid'] = $user["id"];
        $user_recharge['status'] = 1;
        $user_recharge['ctime'] = time();
        $user_recharge['re_type'] = "wx";
        $user_recharge['product_id'] = $pid;
        $user_recharge['openid'] = $openid;
        $user_recharge['gbid'] = $gbresult['id'];
        if (!empty($openid)) {
            Db::name("user_recharge")->insert($user_recharge);
            $config = $this->wxzhifuConfig(session("token"), $_SERVER['REQUEST_URI']);
            $res = $this->wechatzhifu($config, $user_recharge);
        }
        return $res;
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
    public function wxappzhifu($config, $data, $openid)
    {
        $app = Factory::payment($config);
        $jssdk = $app->jssdk;
        $result = $app->order->unify([
            'body' => '报名支付',
            'out_trade_no' => $data['orderid'],
            'total_fee' => $data['money'] * 100,
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

    public function wechatzhifu($config, $data)
    {
        $app = Factory::payment($config);
        $jssdk = $app->jssdk;
        $openid = session('openid');
        $result = $app->order->unify([
            'body' => '报名支付',
            'out_trade_no' => $data['orderid'],
            'total_fee' => $data['money'] * 100,
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


}
