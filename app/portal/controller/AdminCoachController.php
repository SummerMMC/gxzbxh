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


class AdminCoachController extends AdminBaseController
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
        $where["us.user_type"] = 3;
        if (array_key_exists("category", $param) && $param["category"] != 0) {
            $where["us.did"] = $param["category"];
        }
        if (array_key_exists("user_status", $param)) {
            $where["us.user_status"] = $param['user_status'];
        } else {
            $where["us.user_status"] = 1;
        }
        $coach = Db::name("user")->alias("us")->field("us.id as id,pc.name as pname,us.user_nickname as uname,us.user_status as ustatus,us.driving_years as driving_years")->join("company pc", "pc.id = us.did", "left")->where($where)->paginate($limit);
        $page = $coach->render();
        $category = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("category", $category);
        $this->assign("page", $page);
        $this->assign("coach", $coach);
        return $this->fetch('index');
    }

    /**
     * 添加教练
     * @return mixed
     */
    public function add()
    {
        $category = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("category", $category);
        return $this->fetch("add");
    }

    /**
     * 提交教练数据
     */
    public function addsub()
    {
        $param = $this->request->param();
        $user = array();
        $user['user_nickname'] = $param['user_nickname'];
        $user['mobile'] = $param['mobile'];
        $user['did'] = $param['category'];
        $user['driving_years'] = $param['driving_years'];
        $user['avatar'] = $param['thumbnail'];
        $user['user_type'] = 3;
        Db::name("user")->insertGetId($user);
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
        $result = Db::name("user")->where(["id" => $param['id']])->select()->toArray();
        $category = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("category", $category);
        $this->assign("user", $result[0]);
        return $this->fetch("edit");
    }

    /**
     * 更新教练数据
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editsub()
    {
        $param = $this->request->param();
        $user = array();
        $user['user_nickname'] = $param['user_nickname'];
        $user['mobile'] = $param['mobile'];
        $user['did'] = $param['category'];
        $user['driving_years'] = $param['driving_years'];
        $user['avatar'] = $param['thumbnail'];
        $user['user_type'] = 3;
        Db::name("user")->where(['id' => $param['id']])->update($user);
        echo $this->success();
    }

    /**
     * 禁用教练
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbidden()
    {
        $param = $this->request->param();
        if (array_key_exists("ids", $param)) {
            $idl = $param['ids'];
            foreach ($idl as $key => $item) {
                Db::name("user")->where(['id' => $item])->update(["user_status" => 0]);
            }
        }
        $this->success("操作成功", '');
    }

    /**
     * 启用教练
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function startuse()
    {
        $param = $this->request->param();
        if (array_key_exists("ids", $param)) {
            $idl = $param['ids'];
            foreach ($idl as $key => $item) {
                Db::name("user")->where(['id' => $item])->update(["user_status" => 1]);
            }
        }
        $this->success("操作成功", '');
    }
}
