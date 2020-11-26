<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;
//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * 个人中心
 */
class AdminOtherServiceController extends AdminBaseController
{
    /**
     * 验证微信信息
     */
    public function index()
    {
        $param = $this->request->param();
        $result = Db::name("other_service")->where(["status" => 0])->paginate(10);
        $page = $result->render();
        $this->assign("page", $page);
        $this->assign("other", $result);
        return $this->fetch("index");
    }

    public function add()
    {
        return $this->fetch("add");
    }

    public function edit()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $result = Db::name("other_service")->where(["id" => $id])->find();
        $this->assign("other", $result);
        return $this->fetch("edit");
    }

    public function addPost()
    {
        $param = $this->request->param();
        $data = $param["post"];
        $data['ctime'] = time();
        Db::name("other_service")->insertGetId($data);
        return $this->success('添加成功!');
    }

    public function delete()
    {
        $param = $this->request->param();
        $id = $param["id"];
        return $this->success("操作成功!");
    }

    public function editPost()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $data = $param["post"];
        Db::name("other_service")->where(["id" => $id])->update($data);
        return $this->success("操作成功");
    }

}
