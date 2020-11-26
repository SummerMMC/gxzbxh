<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\zbxh\controller;
//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\HomeBaseController;
use cmf\controller\HomeOtherBaseController;
use EasyWeChat\Factory;
use think\Db;

/**
 * 个人中心
 */
class UsercompanycardController extends HomeBaseController
{
    public function add()
    {
        $openid = session('openid');
        $field = "u.id as uid,u.real_name as real_name,u.user_nickname as user_nickname,u.avatar as avatar,u.id as uid,u.job as job,u.department as department,u.position as position,c.name as cname,u.user_status as user_status,c.addr as caddr,u.mobile as mobile";
        $user = Db::name("user")->field($field)->alias("u")->leftJoin("user_company uc", "uc.uid = u.id")->leftJoin("company c", "c.id = uc.cid and c.status = 1")->where(["u.openid" => $openid])->find();
        if ($user["user_status"] != 1) {
            return $this->redirect("/zbxh/wechatotherindex/zbxherror", ["text" => "用户尚未认证，无法使用名片功能"]);
        }
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'chooseWXPay', 'getLocalImgData', 'hideMenuItems'), false);
        $this->assign("jscode", $jscode);
        $cucard = Db::name("user_company_card")->where(["openid" => $openid])->find();
        if (empty($cucard)) {
            $this->assign("cucard", ["has" => "no"]);
            $this->assign("avatar", "");
            $this->assign("xiangcelist", "");
            $this->assign("xiangcelistedit", "");
        } else {
            $this->assign("cucard", ["has" => "yes", "status" => $cucard['status']]);
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
                $this->assign("xiangcelist", json_encode($tempxc));
                $this->assign("xiangcelistedit", $cucard['xiangcelist']);
            } else {
                $this->assign("xiangcelist", "");
                $this->assign("xiangcelistedit", "");
            }
        }
        $this->assign("cucardobj", $cucard);
        $this->assign("user", $user);
        return $this->fetch("add");
    }

    public function subcard()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $tempuser = Db::name("user")->where(["openid" => $openid])->find();
        $tempucc = Db::name("user_company_card")->where(["openid" => $openid])->find();
        if (!empty($tempucc)) {
            return $this->echoError(null, "此用户已添加过名片，请勿重复添加");
        }
        if ($tempuser["user_status"] != 1) {
            return $this->echoError(null, "此用户未认证，无法使用名片功能");
        }
        if (!empty($param["name"])) {
            $user["real_name"] = $param["name"];
        }
        if (!empty($param["phone"])) {
            $user["mobile"] = $param["phone"];
        }
        $user["department"] = $param["department"];
        $user["position"] = $param["position"];
        $user["id"] = $tempuser["id"];
        Db::name("user")->update($user);
        $data["note"] = $param["note"];
        $data["wechat"] = $param["wechat"];
        $data["uid"] = $tempuser["id"];
        $data["status"] = 1;
        $data["openid"] = $openid;
        $data["ctime"] = time();
        $avatar = $param["avatar"];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $avatar, $result)) {
            $type = $result[2];
            $new_file = "default/card/" . date("dMYHis") . "." . $type;
            if (file_put_contents("upload/" . $new_file, base64_decode(str_replace($result[1], '', $avatar)))) {
                $data["avatar"] = $new_file;
            }
        }
        if (array_key_exists("xiangcelist", $param)) {
            $templist = $param["xiangcelist"];
            $temparr = [];
            $new_file = "";
            if (count($templist) > 0) {
                foreach ($templist as $key => $item) {
                    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $item, $result)) {
                        $type = $result[2];
                        $new_file = "default/card/" . $key . time() . "." . $type;
                        if (file_put_contents("upload/" . $new_file, base64_decode(str_replace($result[1], '', $item)))) {
                            array_push($temparr, $new_file);
                        }
                    }
                }
                $data["xiangcelist"] = json_encode($temparr);
            }
        }
        $cardid = Db::name("user_company_card")->insertGetId($data);
        $arra = [
            'first' => '组织/企业名片申请通知',
            'keyword1' => date("Y-m-d", time()),
            'keyword2' => $cardid . "-" . $param["name"] . "名片审核申请",
            'keyword3' => "申请中",
            'remark' => "请尽快登录后台组织/企业名片中进行审核!",
        ];
        $this->sendtemplatemsg($this->getAdminOpenid(), $this->getYwTempid(), $arra, "");
        return $this->echoSuccess();
    }

    public function editcard()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $tempuser = Db::name("user")->where(["openid" => $openid])->find();
        if ($tempuser["user_status"] != 1) {
            return $this->echoError(null, "此用户未认证，无法使用名片功能");
        }
        if (!empty($param["name"])) {
            $user["real_name"] = $param["name"];
        }
        if (!empty($param["phone"])) {
            $user["mobile"] = $param["phone"];
        }
        $user["department"] = $param["department"];
        $user["position"] = $param["position"];
        $user["id"] = $tempuser["id"];
        Db::name("user")->update($user);
        $data["note"] = $param["note"];
        $data["wechat"] = $param["wechat"];
        $data["uid"] = $tempuser["id"];
        $data["status"] = 1;
        $data["openid"] = $openid;
        $avatar = $param["avatar"];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $avatar, $result)) {
            $type = $result[2];
            $new_file = "default/card/" . date("dMYHis") . "." . $type;
            if (file_put_contents("upload/" . $new_file, base64_decode(str_replace($result[1], '', $avatar)))) {
                $data["avatar"] = $new_file;
            }
        }
        if (array_key_exists("xiangcelist", $param)) {
            $templist = $param["xiangcelist"];
            $temparr = [];
            if (count($templist) > 0) {
                foreach ($templist as $key => $item) {
                    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $item, $result)) {
                        $type = $result[2];
                        $new_file = "default/card/" . $key . time() . "." . $type;
                        if (file_put_contents("upload/" . $new_file, base64_decode(str_replace($result[1], '', $item)))) {
                            array_push($temparr, $new_file);
                        }
                    } else {
                        array_push($temparr, $item);
                    }
                }
                $data["xiangcelist"] = json_encode($temparr);
            } else {
                $data["xiangcelist"] = "";
            }
        } else {
            $data["xiangcelist"] = "";
        }
        $data["id"] = $param["id"];
        Db::name("user_company_card")->update($data);
        $arra = [
            'first' => '组织/企业名片申请通知',
            'keyword1' => date("Y-m-d", time()),
            'keyword2' => $param["id"] . "-" . $param["name"] . "名片审核申请",
            'keyword3' => "申请中",
            'remark' => "请尽快登录后台组织/企业名片中进行审核!",
        ];
        $this->sendtemplatemsg($this->getAdminOpenid(), $this->getYwTempid(), $arra, "");
        return $this->echoSuccess();
    }
}
