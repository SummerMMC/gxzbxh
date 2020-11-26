<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\UserRechargeModel;
use api\portal\model\UserModel;
use think\Db;

class RechargeController extends AdminBaseController
{
    public function index()
    {

    }

    /**
     * 打开充值记录列表
     */
    public function rechargelist()
    {
        /**搜索条件**/
        $param = $this->request->param();
        $status = $this->request->param('status');
        $account = trim($this->request->param('account'));
        $phone = trim($this->request->param('phone'));
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $orderid = $this->request->param('orderid');
        $service_orderid = $this->request->param('service_orderid');
        $uid = $this->request->param('uid');
        $order = $this->request->param('diaodan');
        if (array_key_exists("limit", $param)) {
            $limit = $param['limit'];
        } else {
            $limit = 10;
        }
        $where = [];
        if ($uid) {
            $where['uid'] = ['=', $uid];
        }
        if ($status) {
            $where['status'] = ['=', "$status"];
        }
        if ($account) {
            $where['account'] = ['like', "%$account%"];
        }
        if ($phone) {
            $where['phone'] = ['=', "$phone"];
        }
        if ($orderid) {
            $where['orderid'] = ['=', "$orderid"];
        }
        if ($service_orderid) {
            $where['service_orderid'] = ['=', "$service_orderid"];
        }
        if ($start_time && $end_time) {
            $where['ctime'] = [['egt', strtotime($start_time)], ['elt', strtotime($end_time)]];
        } else if ($start_time) {
            $where['ctime'] = ['egt', strtotime($start_time)];
        } else if ($end_time) {
            $where['ctime'] = ['elt', strtotime($end_time)];
        }
        $recharge = Db::name('user_recharge')->field("id,uid,account,phone,money,orderid,service_orderid,status,re_type,ctime,deposit,balance_due_time,deposit_status")
            ->where($where)
            ->order("ctime DESC")
            ->paginate($limit);
        $recharge->appends(['status' => $status, 'account' => $account, 'uid' => $uid, 'status' => $status, 'phone' => $phone]);
        // 获取分页显示
        $page = $recharge->render();

        $this->assign("page", $page);
        $this->assign("recharge", $recharge);
        return $this->fetch();
    }

    /**
     * 获取图片
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getpaypic()
    {
        $id = $this->request->param('id');
        $dbdata = Db::name("user_recharge")->field("order_pic")->where(["id" => $id])->select()->toArray();
        echo $this->success(['data' => $dbdata[0]]);
    }

    /**
     * 提交修改状态
     */
    public function subStatus()
    {
        $status = $this->request->param('status');
        $id = $this->request->param('id');
        $urmlist = Db::name("user_recharge")->where(['id' => $id])->find();
        if (count($urmlist) > 0) {
            if ($status == 3) {
                if ($urmlist['deposit_status'] == 3) {
                    Db::name("user_recharge")->where(["id" => $id])->update(["real_deposit_pay" => $urmlist['deposit'], "deposit_status" => 1]);
                }
            } else if ($status == 1) {
                if ($urmlist['deposit_status'] == 1) {
                    Db::name("user_recharge")->where(["id" => $id])->update(["status" => 2, "real_sn_pay" => $urmlist['supplement_mony'], "deposit_status" => 2]);
                    Db::name("user")->where(["id" => $urmlist["uid"]])->update(["is_price" => 1]);
                }
            } else if ($status == 0) {
                Db::name("user_recharge")->where(["id" => $id])->update(["status" => 2, "real_deposit_pay" => $urmlist['deposit'], "real_sn_pay" => $urmlist['supplement_mony'], "deposit_status" => 0]);
                Db::name("user")->where(["id" => $urmlist["uid"]])->update(["is_price" => 1]);
            } else {
                //                $userRechargeModel->save([
//                    'utime' => time(),
//                    'operateid' => $uid,
//                    'status' => $status,
//                    'chremark' => $chremark
//                ], ['id' => $id]);
            }
            echo json_encode(['status' => -1]);
        }
    }

    /**
     * 增加积分
     */
    public function addcion()
    {
        $uid = $this->request->param('uid');
        $cion = $this->request->param('cion');
//        Db::name('user_score_log')->insert(['user_id' => $uid, 'create_time' => time(), 'action' => 'hand', 'score' => 0, 'coin' => $cion, 'ip' => get_client_ip(0, false)]);
        $data = [
            'uid' => $uid,
            'money' => 0,
            'account' => '',
            'email' => '',
            'phone' => '',
            'cashtype' => 1,//自动充值
            'status' => 2,//进行中
            'ctime' => time(),//进行中
            'orderid' => '2' . time() . $uid,
            'chremark' => '审核中',
            're_type' => '',//充值类型
            'op_type' => 1,//业务类型
            'coin' => $cion,
            'recharge_domino' => "localhost",
            'promotion_id' => 0,
            'cml_id' => ''
        ];
        Db::name("user_recharge")->insert($data);
        $user = Db::name("user")->where(['id' => $uid])->find();
        Db::name("user")->update(['coin' => intval($user['coin']) + intval($cion), 'id' => $uid]);
        echo json_encode(['status' => -1]);
    }

    /**
     * 增加vip
     */
    public function addvip()
    {
        $uid = $this->request->param('uid');
        $vip_tag = $this->request->param('vip_tag');
        $data = [
            'uid' => $uid,
            'money' => 0,
            'account' => "",
            'email' => "",
            'phone' => "",
            'cashtype' => 1,//自动充值
            'status' => 2,//进行中
            'ctime' => time(),//进行中
            'orderid' => '2' . time() . $uid,
            'chremark' => '审核中',
            're_type' => '',//充值类型
            'op_type' => 2,//业务类型
            'coin' => 0,
            'vip_type' => $vip_tag,
            'recharge_domino' => "localhost",
            'promotion_id' => 0,
            'cml_id' => ""
        ];
        Db::name("user_recharge")->insert($data);
        if ($vip_tag == 1) {
            $rearray = Db::name("user")->where(["id" => $uid])->select()->toArray();
            $timeArray["id"] = $uid;
            $timeArray["is_vip"] = 1;
            if (count($rearray) > 0 && $rearray[0]['vip_closetime'] >= time()) {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', $rearray[0]['vip_closetime'] + 1 * 24 * 60 * 60));
            } else {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', time() + 1 * 24 * 60 * 60));
            }
            Db::name("user")->update($timeArray);
        }
        if ($vip_tag == 2) {
            $rearray = Db::name("user")->where(["id" => $uid])->select()->toArray();
            $timeArray["id"] = $uid;
            $timeArray["is_vip"] = 1;
            if (count($rearray) > 0 && $rearray[0]['vip_closetime'] >= time()) {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', $rearray[0]['vip_closetime'] + 30 * 24 * 60 * 60));
            } else {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60));
            }
            Db::name("user")->update($timeArray);
        }
        if ($vip_tag == 3) {
            $rearray = Db::name("user")->where(["id" => $uid])->select()->toArray();
            $timeArray["id"] = $uid;
            $timeArray["is_vip"] = 1;
            if (count($rearray) > 0 && $rearray[0]['vip_closetime'] >= time()) {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', $rearray[0]['vip_closetime'] + 365 * 24 * 60 * 60));
            } else {
                $timeArray['vip_closetime'] = strtotime(date('Y-m-d H:i:s', time() + 365 * 24 * 60 * 60));
            }
            Db::name("user")->update($timeArray);
        }
        echo $this->success();
    }

    /**
     * 提现
     * @return mixed
     */
    public function withdraw()
    {
        $pay_munber = \think\Config::get('pay_config');
        $juhe_AL = $pay_munber['juheAL'];
        $this->assign("juheAL", $juhe_AL);
        return $this->fetch("withdraw");
    }

    /**
     *
     */
    public function subwithdraw()
    {
        $pay_munber = \think\Config::get('pay_config');
        $juhe_AL = $pay_munber['juheAL'];
        $money = $this->request->param('money');
        $uid = cmf_get_current_user_id();
        $biz_content['version'] = $juhe_AL['version'];
        $biz_content['mch_id'] = $juhe_AL['customerid'];
        $biz_content['out_order_no'] = '1' . time() . $uid;
        $biz_content['payment_fee'] = intval($money) * 100;
        $biz_content['payee_acct_no'] = $juhe_AL['payee_acct_no'];
        $biz_content['payee_acct_name'] = $juhe_AL['payee_acct_name'];
        $biz_content['card_type'] = $juhe_AL['card_type'];
        $biz_content['payee_acct_type'] = $juhe_AL['payee_acct_type'];
        $biz_content['settle_type'] = $juhe_AL['settle_type'];
        $biz_content['remark'] = $juhe_AL['remark'];
        $biz_content['notify_url'] = $juhe_AL['withdraw_notifyurl'];;


        $biz_content_jsonstr = json_encode($biz_content);
        $signature = md5('biz_content=' . $biz_content_jsonstr . "&key=" . $juhe_AL['userkey']);
        $url = $juhe_AL['withdraw_url'] . "?sign_type=" . $juhe_AL['interfacetype'] . "&signature=" . strtoupper($signature) . "&biz_content=" . $biz_content_jsonstr;
//        Db::name("log")->insert(['key'=>'代付拼装url','value'=>$url]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 0); //启用POST提交
        $file_contents = curl_exec($ch);
//        Db::name("log")->insert(['key'=>'代付结果','value'=>$file_contents]);
        $result = json_decode($file_contents, true);
        if ($result['ret_code'] == 0) {
//            Db::name("log")->insert(['key'=>'状态','value'=>$result['ret_msg']]);
            if ($result['ret_msg'] == 'success') {
                $user_withdraw['bank_card_id'] = $juhe_AL['payee_acct_no'];
                $user_withdraw['ctime'] = time();
                $user_withdraw['status'] = 1;
                $user_withdraw['order_id'] = $biz_content['out_order_no'];
                $user_withdraw['money'] = $money;
                $user_withdraw['uid'] = $uid;
                Db::name("user_withdraw")->insert($user_withdraw);
                $out['status'] = '-1';
                $out['msg'] = $result['ret_msg'];
            } else {
                $out['status'] = '1';
                $out['msg'] = $result['ret_msg'];
            }
        } else {
            $out['status'] = '1';
            $out['msg'] = $result['ret_msg'];
        }
        curl_close($ch);
        $outstr = json_encode($out);
        echo $outstr;

    }

    /**
     * 获取支付信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPaySet()
    {
        $result = Db::name("pay_set")->select()->toArray();
        echo $this->success(['data' => $result]);
    }

    /**
     * 更新支付状态
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatepaystatus()
    {
        $status = $this->request->param('status');
        $id = $this->request->param('id');
        Db::name("pay_set")->update(['status' => $status, 'id' => $id]);
        echo $this->success();
    }

    /**
     * 获取套餐信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getcomboxlist()
    {
        $result = Db::name("pay_combo")->select()->toArray();
        echo $this->success(['data' => $result]);
    }

    /**
     * 更新套餐状态
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatecomboxstatus()
    {
        $status = $this->request->param('status');
        $id = $this->request->param('id');
        Db::name("pay_combo")->update(['status' => $status, 'id' => $id]);
        echo $this->success();
    }

    /**
     * 新增套餐
     */
    public function createcombox()
    {
        $data['money'] = $this->request->param('money');
        $data['coin'] = $this->request->param('coin');
        $data['show_money'] = $this->request->param('show_money');
        $data['type'] = $this->request->param('type');
        $data['vip_type'] = $this->request->param('vip_type');
        $data['note'] = $this->request->param('note');
        Db::name('pay_combo')->insert($data);
        echo $this->success();
    }

    /**
     * 修改套餐
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatecombox()
    {
        $data['id'] = $this->request->param('id');
        $data['money'] = $this->request->param('money');
        $data['coin'] = $this->request->param('coin');
        $data['show_money'] = $this->request->param('show_money');
        $data['type'] = $this->request->param('type');
        $data['vip_type'] = $this->request->param('vip_type');
        $note = str_replace("&lt;", "<", $this->request->param('note'));
        $note = str_replace("&quot;", '"', $note);
        $note = str_replace("&gt;", '>', $note);
        $data['note'] = $note;
        Db::name("pay_combo")->update($data);
        echo $this->success();
    }

    public function deletecombox()
    {
        $id = $this->request->param('id');
        Db::name('pay_combo')->delete($id);
        echo $this->success();
    }

    public function cleanpic()
    {

        $dresult = Db::name('user_recharge')->field('id')->where("order_pic != ''")->select()->toArray();
        foreach ($dresult as $item) {
            Db::name("user_recharge")->update(['order_pic' => '', 'id' => $item['id']]);
        }
        echo $this->success();
    }

    /**
     * 获取是否有掉单
     */
    public function gethasrecharge()
    {
        $data = Db::name("user_recharge")->where("order_pic is not null and order_pic <> '' and status = 1")->select()->toArray();
        $red["count"] = count($data);
        echo $this->success("", "", $red);
    }

}