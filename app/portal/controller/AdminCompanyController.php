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
use EasyWeChat\Factory;
use think\Db;


class AdminCompanyController extends AdminBaseController
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
        if (array_key_exists("name", $param) && $param["name"] != "") {
            $where[] = array("name", 'like', '%' . $param["name"] . '%');
            $this->assign("name", $param["name"]);
            $limit = 50;
        }
        if (array_key_exists("id", $param) && $param["id"] != "") {
            $where[] = array("id", '=', $param["id"]);
            $this->assign("id", $param["id"]);
            $pagewhere["id"] = $param["id"];
        }
        if (array_key_exists("endtime", $param) && $param["endtime"] != "") {
            if ($param["endtime"] == 2) {
                $where[] = array("end_time", '<', time());
                $limit = 1500;
            } else {
                $where[] = array("end_time", '>=', time());
                $limit = 1500;
            }
        }
        if (array_key_exists("status", $param) && $param["status"] != "") {
            $where[] = array("status", '=', $param["status"]);
            $pagewhere["status"] = $param["status"];
        } else {
//            $where[] = array("status", '=', 0);
//            $pagewhere["status"] = 0;
        }
        $driving = Db::name("company")->where($where)->paginate($limit);
        $driving->appends($pagewhere);
        $page = $driving->render();
        $this->assign("page", $page);
        $this->assign("coach", $driving);
        return $this->fetch('index');
    }

    /**
     * 添加企业
     * @return mixed
     */
    public function add()
    {
        $category = Db::name("portal_category")->where(["status" => 1])->select()->toArray();
        $this->assign("category", $category);
        return $this->fetch("add");
    }

    /**
     * 提交企业数据
     */
    public function addsub()
    {
        $param = $this->request->param();
        $driving = array();
        $driving['name'] = $param['name'];
        $driving['uid'] = $param['uid'];
        $driving['phone'] = $param['phone'];
        $driving['addr'] = $param['addr'];
        $driving['job'] = $param['job'];
        $driving['thumbnail'] = $param['thumbnail'];
        $imgarray = explode(',', $param['imgstr']);
        if (count($imgarray) > 0 && $imgarray[0] != "") {
            $driving['accessory'] = json_encode($imgarray);
        }
        $driving['start_time'] = strtotime($param['start_time']);
        $driving['end_time'] = strtotime($param['end_time']);
        $driving['linkman'] = $param['linkman'];
        $driving['content'] = $param['content'];
        $driving['status'] = 1;

        $user = Db::name("user")->where(["id" => $param['uid']])->find();
        $ucont = Db::name("user_company")->where(["uid" => $user["id"]])->count();

        if ($ucont > 0) {
            echo $this->error($user["real_name"] . "已认证企业，请勿重复认证!");
        }
        if (empty($user)) {
            echo $this->error("输入用户ID不存在，请确认后在保存!");
        } else {
            $did = Db::name("company")->insertGetId($driving);
            Db::name("user")->update(["user_status" => 1, "mobile" => $driving['phone'], "did" => $did, "real_name" => $driving['linkman'], "job" => $driving['job'], "id" => $user["id"]]);
            $data = [];
            $data["uid"] = $user["id"];
            $data['cid'] = $did;
            $data['lead'] = 1;
            Db::name("user_company")->insert($data);
            if (array_key_exists("sonuser", $param)) {
                $linearray = explode(",", $param["sonuser"]);
                $jobarray = explode(",", $param["jobstr"]);
                $sondata = [];
                if (count($linearray) > 0 && !empty($linearray[0])) {
                    foreach ($linearray as $key => $item) {
                        $sondata['uid'] = $item;
                        $sondata['cid'] = $did;
                        $sondata['lead'] = 2;
                        Db::name("user_company")->insert($sondata);
                        Db::name("user")->update(["did" => $did, "job" => $jobarray[$key], "user_status" => 1, "id" => $item]);
                    }
                }
            }
        }
        echo $this->success();
    }

    /**
     * 跳转修改页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $param = $this->request->param();
        $result = Db::name("company")->where(["id" => $param['id']])->select()->toArray();
        $category = Db::name("portal_category")->where(["status" => 1])->select()->toArray();
        $result[0]['accessory'] = json_decode($result[0]['accessory'], true);
        $user = Db::name("user")->where(["id" => $result[0]["uid"]])->find();
        $sonuser = Db::name("user_company")->alias("uc")->leftJoin("user u", "uc.uid = u.id")->where(["uc.cid" => $param['id'], "uc.lead" => 2])->select()->toArray();
        if (count($sonuser) > 0) {
            $sonuserstr = "";
            foreach ($sonuser as $key => $item) {
                if ($key < count($sonuser) - 1) {
                    $sonuserstr .= $item["uid"] . ",";
                } else {
                    $sonuserstr .= $item["uid"];
                }
            }
            $this->assign("sonuserinput", $sonuserstr);
        } else {
            $this->assign("sonuserinput", "");
        }
        $this->assign("sonuser", $sonuser);
        $this->assign("user", $user);
        $this->assign("company", $result[0]);
        return $this->fetch("edit");
    }

    /**
     * 更新企业数据
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editsub()
    {
        $param = $this->request->param();
        $driving = array();
        $driving['name'] = $param['name'];
        $driving['uid'] = $param['uid'];
        $driving['phone'] = $param['phone'];
        $driving['addr'] = $param['addr'];
        $driving['job'] = $param['job'];
        $driving['thumbnail'] = $param['thumbnail'];
        $imgarray = explode(',', $param['imgstr']);
        if (count($imgarray) > 0) {
            $driving['accessory'] = json_encode($imgarray);
        }
        $driving['start_time'] = strtotime($param['start_time']);
        $driving['end_time'] = strtotime($param['end_time']);
        $driving['linkman'] = $param['linkman'];
        $driving['content'] = $param['content'];

        $user = Db::name("user")->where(["id" => $param['uid']])->find();
        if (empty($user)) {
            echo $this->error("输入用户ID不存在，请确认后在保存!");
        } else {
            Db::name("user_company")->where(["cid" => $param['id']])->delete();
            Db::name("user")->update(["mobile" => "", "did" => "", "real_name" => "", "job" => "", "id" => $driving['uid']]);
            Db::name("company")->where(['id' => $param['id']])->update($driving);
            Db::name("user")->update(["mobile" => $driving['phone'], "did" => $param['id'], "real_name" => $driving['linkman'], "job" => $driving['job'], "id" => $user["id"]]);
            $data = [];
            $data["uid"] = $user["id"];
            $data['cid'] = $param['id'];
            $data['lead'] = 1;
            Db::name("user_company")->insert($data);
            if (array_key_exists("sonuser", $param)) {
                $linearray = explode(",", $param["sonuser"]);
                $jobarray = explode(",", $param["jobstr"]);
                $sondata = [];
                if (count($linearray) > 0 && !empty($linearray[0])) {
                    foreach ($linearray as $key => $item) {
                        $sondata['uid'] = $item;
                        $sondata['cid'] = $param['id'];
                        $sondata['lead'] = 2;
                        Db::name("user_company")->insert($sondata);
                        Db::name("user")->update(["did" => $param['id'], "job" => $jobarray[$key], "user_status" => 1, "id" => $item]);
                    }
                }
            }
        }
        echo $this->success();
    }

    /**
     * 禁用企业
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbidden()
    {
        $param = $this->request->param();
        if (array_key_exists("ids", $param)) {
            $idl = $param['ids'];
            foreach ($idl as $key => $item) {
                $users = Db::name("user_company")->where(["cid" => $item])->select()->toArray();
                foreach ($users as $item) {
                    Db::name("user")->update(["user_status" => 2, "id" => $item["uid"]]);
                }
                $uclist = Db::name("user_company")->where(["cid" => $item["uid"]])->select();
                foreach ($uclist as $item) {
                    Db::name("user")->update(["user_status" => 2, "id" => $item["uid"]]);
                }
                Db::name("company")->where(['id' => $item])->update(["status" => 0]);
            }
        }
        $this->success("操作成功", '');
    }

    /**
     * 启用企业
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function startuse()
    {
        $param = $this->request->param();
        if (array_key_exists("ids", $param)) {
            $idl = $param['ids'];
            foreach ($idl as $key => $item) {
                $users = Db::name("user_company")->where(["cid" => $item])->select()->toArray();
                foreach ($users as $item) {
                    Db::name("user")->update(["user_status" => 1, "id" => $item["uid"]]);
                }
                $uclist = Db::name("user_company")->where(["cid" => $item["uid"]])->select();
                foreach ($uclist as $item) {
                    Db::name("user")->update(["user_status" => 1, "id" => $item["uid"]]);
                }
                $user = Db::name("user")->where(["id" => $item["uid"]])->find();
                Db::name("company")->where(['id' => $item])->update(["status" => 1]);
                $token = session("token");
                $config = $this->assembleWxConfig($token);
                $app = Factory::officialAccount($config);
                $arra = [
                    'first' => '组织/企业认证申请通知',
                    'keyword1' => date("Y-m-d", time()),
                    'keyword2' => "组织/企业认证申请",
                    'keyword3' => "通过",
                    'remark' => "您的组织/企业认证已经通过。",
                ];
                $app->template_message->send([
                    'touser' => $user["openid"],
                    'template_id' => $this->getYwTempid(),
                    'url' => "",
                    'data' => $arra,
                ]);
            }
        }
        $this->success("操作成功", '');
    }

    public function changelist()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $order_id = $param["order_id"];
        Db::name("company")->where(["id" => $id])->update(["order_id" => $order_id]);
        return $this->success();
    }

    /**
     * 添加用户
     */
    public function adduser()
    {
        $param = $this->request->param();
        $uid = $param['uid'];
        $user = Db::name("user")->where(["id" => $uid])->find();
        $com = Db::name("company")->where(["uid" => $uid])->find();
        if (empty($user)) {
            return $this->error("系统无此用户，请核实后在添加");
        } else if (!empty($com)) {
            return $this->error("此用户已在【" . $com["name"] . "】公司注册，请换一个用户");
        } else {
            return $this->success("", "", $user);
        }
    }

    /**
     * 修改用户状态
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function changeuserstatus()
    {
        $param = $this->request->param();
        $uid = $param['id'];
        Db::name("user")->update(["user_status" => 2, "job" => "", "id" => $uid]);
        return $this->success();
    }

    public function delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $com = Db::name("company")->where(["id" => $id])->find();
        Db::name("user")->update(["user_status" => 2, "job" => "", "id" => $com["uid"]]);
        $ulist = Db::name("user_company")->where(["cid" => $id])->select()->toArray();
        foreach ($ulist as $item) {
            Db::name("user")->update(["user_status" => 2, "job" => "", "id" => $item["uid"]]);
            Db::name("user_company")->delete($item);
            $ucc = Db::name("user_company_card")->where(["uid" => $item["uid"]])->find();
            Db::name("user_company_card")->delete($ucc);
        }
        Db::name("company")->delete($com);
        return $this->success("操作成功");
    }
}
