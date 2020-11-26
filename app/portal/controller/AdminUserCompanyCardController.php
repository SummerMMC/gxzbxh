<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use EasyWeChat\Factory;
use EasyWeChat\payment\Order;


class AdminUserCompanyCardController extends AdminBaseController
{
    /**
     * 跳转后台管理企业页面
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        if (array_key_exists("limit", $param)) {
            $limit = $param['limit'];
        } else {
            $limit = 10;
        }
        $where = array();
        $pagewhere = array();
        if (array_key_exists("name", $param)) {
            $where[] = array("name", 'like', '%' . $param["name"] . '%');
            $this->assign("name", $param["name"]);
            $limit = 50;
        }
        if (array_key_exists("mobile", $param) && $param["mobile"] != "") {
            $where[] = array("u.mobile", '=', $param["mobile"]);
            $this->assign("mobile", $param["mobile"]);
            $pagewhere["u.mobile"] = $param["mobile"];
        }
        if (array_key_exists("id", $param) && $param["id"] != "") {
            $where[] = array("ucc.id", '=', $param["id"]);
            $this->assign("id", $param["id"]);
            $pagewhere["ucc.id"] = $param["id"];
        }
        if (array_key_exists("status", $param) && $param["status"] != "") {
            $where[] = array("ucc.status", '=', $param["status"]);
            $pagewhere["ucc.status"] = $param["status"];
        } else {
//            $where[] = array("ucc.status", '=', 1);
//            $pagewhere["ucc.status"] = 1;
        }
        $field = "
            ucc.id as id,
            u.id as uid,
            c.name as name,
            u.user_nickname as nickname,
            u.real_name as real_name,
            u.mobile as mobile,
            ucc.status as status
            ";
        $driving = Db::name("user_company_card")->field($field)->alias("ucc")
            ->leftJoin("user u", "ucc.uid = u.id")
            ->leftJoin("user_company uc", "ucc.uid = uc.uid")
            ->leftJoin("company c", "uc.cid = c.id")
            ->where($where)->paginate($limit);
        $driving->appends($pagewhere);
        $page = $driving->render();
        $this->assign("page", $page);
        $this->assign("card", $driving);
        return $this->fetch('index');
    }

    public function edit()
    {
        $param = $this->request->param();
        $uid = $param["id"];
        $field = "u.id as uid,u.real_name as real_name,u.user_nickname as user_nickname,u.avatar as avatar,u.id as uid,c.job as job,u.department as department,u.position as position,c.name as cname,u.user_status as user_status,c.addr as caddr,u.mobile as mobile";
        $user = Db::name("user")->field($field)->alias("u")->leftJoin("user_company uc", "uc.uid = u.id")->leftJoin("company c", "c.id = uc.cid and c.status = 1")->where(["u.id" => $uid])->find();
        $cucard = Db::name("user_company_card")->where(["uid" => $uid])->find();
        if (!empty($cucard) && !empty($cucard['avatar'])) {
            $this->assign("avatar", cmf_get_image_url($cucard['avatar']));
        } else {
            $this->assign("avatar", "");
        }
        if (!empty($cucard) && !empty($cucard['xiangcelist'])) {
            $tempxc = json_decode($cucard['xiangcelist'], true);
            foreach ($tempxc as $key => $item) {
                $tempxc[$key] = cmf_get_image_url($item);
            }
            $this->assign("xiangcelist", $tempxc);
        } else {
            $this->assign("xiangcelist", []);
        }
        $this->assign("cucardobj", $cucard);
        $this->assign("user", $user);
        return $this->fetch("edit");
    }

    public function check()
    {
        $param = $this->request->param();
        $cardid = $param['id'];
        $status = $param['status'];
        $note = $param['note'];
        if ($status == 3) {
            Db::name("user_company_card")->update(["status" => $status, "checknote" => $note, "id" => $cardid]);
        } else {
            Db::name("user_company_card")->update(["status" => $status, "checknote" => '', "id" => $cardid]);
        }
        $usercard = Db::name("user_company_card")->where(["id" => $cardid])->find();
        $token = session("token");
        $config = $this->assembleWxConfig($token);
        $app = Factory::officialAccount($config);
        if ($status == 3) {
            $arra = [
                'first' => '组织/企业名片审核通知',
                'keyword1' => date("Y-m-d", $usercard["ctime"]),
                'keyword2' => "组织/企业名片审核",
                'keyword3' => "不通过",
                'remark' => "请点击查看详情确认不通过原因并进行修改。",
            ];
            $app->template_message->send([
                'touser' => $usercard["openid"],
                'template_id' => $this->getYwTempid(),
                'url' => $this->getSiteHost() . "/zbxh/usercompanycard/add",
                'data' => $arra,
            ]);
        } else {
            $arra = [
                'first' => '组织/企业名片审核通知',
                'keyword1' => date("Y-m-d", $usercard["ctime"]),
                'keyword2' => "组织/企业名片审核",
                'keyword3' => "通过",
                'remark' => "请点击查看详情即可转发名片。",
            ];
            $app->template_message->send([
                'touser' => $usercard["openid"],
                'template_id' => $this->getYwTempid(),
                'url' => $this->getSiteHost() . "/zbxh/usercompanycard/add",
                'data' => $arra,
            ]);
        }
        return $this->success();
    }

    public function delete()
    {
        $param = $this->request->param();
        $cardid = $param['id'];
        $card = Db::name("user_company_card")->where(["id" => $cardid])->find();
        Db::name("user_company_card")->delete($card);
        return $this->success("操作成功");
    }
}
