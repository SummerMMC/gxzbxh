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
class AdminActivityController extends AdminBaseController
{
    /**
     * 验证微信信息
     */
    public function index()
    {
        $param = $this->request->param();
        $result = Db::name("activity")->paginate(10);
        $page = $result->render();
        $this->assign("page", $page);
        $this->assign("activity", $result);
        return $this->fetch("index");
    }

    public function add()
    {
        $sclresult = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("school", $sclresult);
        return $this->fetch("add");
    }

    public function getpid()
    {
        $param = $this->request->param();
        $tag = $param['tag'];
        $sid = $param['sid'];
        if ($tag == 0) {
            $presult = Db::name("price")->where(['did' => 0])->select()->toArray();
        } else {
            $presult = Db::name("price")->where(['did' => $sid])->select()->toArray();
        }
        return $this->success('', null, $presult);
    }

    public function addPost()
    {
        $param = $this->request->param();
        $post = $param['post'];
        $paobj = $param['paobj'];
        $img = $post['qrcode'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
            $type = $result[2];
            $new_file = "upload/activity/" . time() . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img)))) {
                $post['qrcode'] = $new_file;
            }
        }
        $aid = Db::name("activity")->insertGetId($post);
        foreach ($paobj as $item) {
            $item['aid'] = $aid;
            Db::name("activity_price")->insertGetId($item);
        }
        return $this->success('添加成功!');
    }

    public function qruser()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $result = Db::name("activity_user")->where(["aid" => $id])->select()->toArray();
        $activity = Db::name("activity")->where(["id" => $id])->find();
        $this->assign("aresult", $result);
        $this->assign("activity", $activity);
        return $this->fetch("qruser");
    }

    public function saveuserqrcode()
    {
        $param = $this->request->param();
        $data = $param['post'];
        $user = Db::name("user")->where(["id" => $data["uid"]])->find();
        if (!empty($user)) {
            $img = $data['qrcode'];
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
                $type = $result[2];
                $new_file = "upload/activity/" . time() . ".{$type}";
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img)))) {
                    $data['qrcode'] = $new_file;
                }
            }
            $data['name'] = $user['user_nickname'];
            Db::name("activity_user")->insertGetId($data);
        }
        return $this->success("操作成功");
    }

    public function delete()
    {
        $param = $this->request->param();
        $id = $param["id"];
        Db::name("activity")->where(["id" => $id])->delete();
        Db::name("activity_price")->where(["aid" => $id])->delete();
        Db::name("activity_user")->where(["aid" => $id])->delete();
        return $this->success("操作成功!");
    }

}
