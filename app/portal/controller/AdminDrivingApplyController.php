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


class AdminDrivingApplyController extends AdminBaseController
{
    /**
     * 跳转后台管理教练页面
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
        $where = [];
        $this->assign("phone", "");
        $this->assign("name", "");
        $this->assign("status", "");
        if (array_key_exists("phone", $param) && !empty($param["phone"])) {
            $where["phone"] = $param["phone"];
            $this->assign("phone", $param["phone"]);
        }
        if (array_key_exists("name", $param) && !empty($param["name"])) {
            $where["name"] = $param["name"];
            $this->assign("name", $param["name"]);
        }
        if (array_key_exists("category", $param)) {
            $where["area"] = $param["category"];
        }
        if (array_key_exists("status", $param) && !empty($param["status"])) {
            $where["status"] = $param['status'];
            $this->assign("status", $param["status"]);
        } else {
            $where["status"] = 0;
            $this->assign("status", 0);
        }
        $category = Db::name("portal_category")->where(["status" => 1])->select()->toArray();
        $this->assign("category", $category);
        $user = Db::name("driving_apply")->where($where)->paginate($limit);
        $page = $user->render();
        $this->assign("page", $page);
        $this->assign("user", $user);
        return $this->fetch('index');
    }

    /**
     * 更新状态
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update()
    {
        $param = $this->request->param();
        $id = $param["id"];
        Db::name("driving_apply")->where(["id" => $id])->update(["status" => 1]);
        return $this->success();
    }
}
