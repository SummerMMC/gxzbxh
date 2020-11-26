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
use cmf\controller\HomeOtherBaseController;
use EasyWeChat\Factory;
use EasyWeChat\payment\Order;
use think\Db;

class WechatotherindexController extends HomeOtherBaseController
{
    /**
     * 跳转首页
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('checkJsApi',
            'openLocation',
            'getLocation',
            'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
        $this->assign("jscode", $jscode);
        $this->assign("topcid", 4);
        return $this->fetch('index');
    }

    public function getAlldate()
    {
        $param = $this->request->param();
        $alldata = [];
        //资讯中心菜单
        if (array_key_exists("zxzxmid", $param)) {
            $alldata['zxzxmenu'] = $this->getMenu($param["zxzxmid"]);
        } else {
            $alldata['zxzxmenu'] = $this->getMenu(0);
        }
        //热点数据
        $postmodel = new PortalPostModel();
        $hotdatapostlist = $postmodel->getPortalHotList($param['hotlimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
        $alldata['hotdata'] = $hotdatapostlist;
        //资讯中心
        $zxzxpostlist = $postmodel->getPortalListByCid($param['zxzxcid'], $param['zxzxlimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
        foreach ($zxzxpostlist as $item) {
            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        $alldata['zxzx'] = $zxzxpostlist;
        //认识协会
        if (array_key_exists("rsxhmid", $param)) {
            $alldata['rsxhmenu'] = $this->getMenu($param["rsxhmid"]);
        } else {
            $alldata['rsxhmenu'] = $this->getMenu(0);
        }
        //企业文化
        $alldata['qywhlist'] = $this->getlistdata($param['qywhcid'], $param['qywhlimit']);
        //放心示范店
        $alldata['fxsflist'] = $this->getlistdata($param['fxsfcid'], $param['fxsflimit']);
        //会员活动
        $alldata['hyhdlist'] = $this->getlistdata($param['hyhdcid'], $param['hyhdlimit']);
        //会展活动
        $alldata['hzhdlist'] = $this->getlistdata($param['hzhdcid'], $param['hzhdlimit']);
        //协会动态
        $alldata['xhdtlist'] = $this->getlistdata($param['xhdtcid'], $param['xhdtlimit']);
        //通知公告
        $alldata['tzgglist'] = $this->getlistdata($param['tzggcid'], $param['tzgglimit']);
        //专业人才
        if (array_key_exists("zyrcmid", $param)) {
            $alldata['zyrcmenu'] = $this->getMenu($param["zyrcmid"]);
        } else {
            $alldata['zyrcmenu'] = $this->getMenu(0);
        }
        return $this->echoSuccess($alldata);
    }

    public function imglist()
    {
        $param = $this->request->param();
        //首页图片
        $postmodel = new PortalPostModel();
        $imagepostlist = $postmodel->getPortalImgList($param['imglimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
        $alldata['image'] = $imagepostlist;
        foreach ($imagepostlist as $item) {
            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        return $this->echoSuccess($alldata);
    }

    /**
     * 获取列表数据
     */
    public function getpostlistdata()
    {
        $param = $this->request->param();
        $cid = $param['cid'];
        $per_page = 10;
        $page = 1;
        if (isset($param['per_page'])) {
            $per_page = $param['per_page'];
            if ($per_page > 1000) {
                $per_page = 1000;
            }
        }
        if (isset($param['page'])) {
            $page = $param['page'];
        }
        $postlist = Db::name("portal_post")->alias("p")->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")->order("p.is_top desc,pcp.list_order desc,p.published_time desc")->where(["pcp.category_id" => $cid, "p.post_status" => 1])->page($page . ',' . $per_page)->select()->toArray();
        foreach ($postlist as $key => $item) {
            $postlist[$key]["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        return $this->echoSuccess($postlist);
    }

    public function postlist()
    {
        $param = $this->request->param();
        if (array_key_exists("cid", $param)) {
            $pid = $param['cid'];
            $this->assign("cid", $pid);
            session("pid", $param['cid']);
        }
        $cobj = Db::name("portal_category")->where(["id" => session("pid")])->find();
        $this->assign("cobj", $cobj);
        $this->assign("topcid", $this->getTopcid(session("pid"))['cid']);
        if (0 == $this->getTopcid(session("pid"))["post_id"]) {
            $clist = $this->getMenu($this->getTopcid(session("pid"))['cid']);
            $this->assign("clist", $clist);
            $config = $this->assembleWxConfig(session("token"));
            $app = Factory::officialAccount($config);
            $jscode = $app->jssdk->buildConfig(array('checkJsApi',
                'openLocation',
                'getLocation',
                'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
            $this->assign("jscode", $jscode);
            return $this->fetch("postlist");
        } else {
            $post = Db::name("portal_post")->alias("p")
                ->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")
                ->where(["p.id" => $this->getTopcid(session("pid"))["post_id"]])->find();
            $this->assign("topcid", $this->getTopcid($post["category_id"])['cid']);
            if (empty($post["post_wxcontent"])) {
                $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_content"]));
            } else {
                $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_wxcontent"]));
            }
            $post['more'] = json_decode($post['more'], true);
            if (array_key_exists("files", $post['more'])) {
                foreach ($post['more']["files"] as $key => $item) {
                    $post['more']["files"][$key]["url"] = cmf_get_image_url($item["url"]);
                }
            }
            Db::name("portal_post")->update(["post_hits" => $post["post_hits"] + 1, "id" => $post["id"]]);
            $config = $this->assembleWxConfig(session("token"));
            $app = Factory::officialAccount($config);
            $jscode = $app->jssdk->buildConfig(array('checkJsApi',
                'openLocation',
                'getLocation',
                'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
            $this->assign("jscode", $jscode);
            $this->assign("post", $post);
            return $this->fetch("post");
        }
        $this->fetch("post");
    }

    /**
     * 内容详情页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function post()
    {
        $param = $this->request->param();
        $pid = $param['pid'];
        $post = Db::name("portal_post")->alias("p")
            ->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")
            ->where(["p.id" => $pid])->find();
        if ($post['post_status'] != 1) {
            return $this->redirect("/zbxh/wechatotherindex/zbxherror", ["text" => "文章不存在或尚未发布!"]);
        }
        $this->assign("topcid", $this->getTopcid($post["category_id"])['cid']);
        if (empty($post["post_wxcontent"])) {
            $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_content"]));
        } else {
            $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_wxcontent"]));
        }
        if (!empty($post['more'])) {
            $post['more'] = json_decode($post['more'], true);
            if (array_key_exists("files", $post['more'])) {
                foreach ($post['more']["files"] as $key => $item) {
                    $post['more']["files"][$key]["url"] = cmf_get_image_url($item["url"]);
                }
            }
        }
        $uip = $_SERVER["REMOTE_ADDR"];
        $uipcount = Db::name("user_hit")->where(["pid" => $post["post_id"], "ip" => $uip])->count();
        if ($uipcount == 0) {
            Db::name("user_hit")->insertGetId(["pid" => $post["post_id"], "ip" => $uip]);
            Db::name("portal_post")->update(["post_hits" => intval($post["post_hits"]) + 1, "id" => $post["post_id"]]);
        }
        if (array_key_exists("token", $param)) {
            session("token", $param["token"]);
        }
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('checkJsApi',
            'openLocation',
            'getLocation',
            'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
        $this->assign("jscode", $jscode);
        $this->assign("post", $post);
        return $this->fetch("post");
    }

    public function cardindex()
    {
        $param = $this->request->param();
        $uid = $param['uid'];
        $field = "u.id as uid,u.user_status as user_status,u.real_name as real_name,u.user_nickname as user_nickname,u.avatar as avatar,u.id as uid,u.job as job,u.department as department,u.position as position,c.name as cname,u.user_status as user_status,c.addr as caddr,u.mobile as mobile";
        $user = Db::name("user")->field($field)->alias("u")->leftJoin("user_company uc", "uc.uid = u.id")->leftJoin("company c", "c.id = uc.cid and c.status = 1")->where(["u.id" => $uid])->find();
        if ($user["user_status"] != 1) {
            return $this->redirect("/zbxh/wechatotherindex/zbxherror", ["text" => "用户尚未认证"]);
        }
        $cucard = Db::name("user_company_card")->where(["uid" => $uid])->find();
        if ($cucard["status"] != 2) {
            return $this->redirect("/zbxh/wechatotherindex/zbxherror", ["text" => "名片正在审核中"]);
        }
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
        $config = $this->assembleWxConfig(session("token"));
        $app = Factory::officialAccount($config);
        $jscode = $app->jssdk->buildConfig(array('checkJsApi',
            'openLocation',
            'getLocation',
            'hideOptionMenu', 'updateAppMessageShareData', 'updateTimelineShareData'), false);
        $this->assign("jscode", $jscode);
        return $this->fetch("cardindex");
    }

    public function wechatopen()
    {
        return $this->fetch("wechatopen");
    }

    public function zbxherror()
    {
        $param = $this->request->param();
        if (array_key_exists("text", $param)) {
            $this->assign("text", $param["text"]);
        } else {
            $this->assign("text", "页面无法访问，请点击上一页重试");
        }
        return $this->fetch("zbxherror");
    }

    public function pushinfor()
    {
        $param = $this->request->param();
        $tag = $param["tag"];
        $uid = $param["uid"];
        $pid = $param["pid"];
        if ($tag == 1) {
            $user = Db::name("user")->where("job is not null")->select()->toArray();
            $post = Db::name("portal_post")->where(["id" => $pid, "post_status" => 1])->find();
            if (empty($post)) {
                return $this->echoError(null, "此文章尚未发布");
            }
            foreach ($user as $item) {
                if (!empty($item["openid"])) {
                    $arra = [
                        'first' => '广西珠宝协会发布新的信息',
                        'keyword1' => $post['post_title'],
                        'keyword2' => "已发布",
                        'keyword3' => date("Y-m-d", $post['published_time']),
                        'remark' => '点击详情查看详细信息',
                    ];
                    $this->sendtemplatemsg($item["openid"], "ZAExTtRLBdZ1V1FVYD0cpyZ7hc_I8aH4TFPOlNOhNGM", $arra, $this->getSiteHost() . "/zbxh/wechatotherindex/post?pid=" . $pid);
                }
            }
            return $this->success();
        }
        if ($tag == 2) {
            $user = Db::name("user")->where(["id" => $uid])->find();
            $post = Db::name("portal_post")->where(["id" => $pid, "post_status" => 1])->find();
            if (empty($post)) {
                return $this->echoError(null, "此文章尚未发布");
            }
            if (!empty($user["openid"])) {
                $arra = [
                    'first' => '广西珠宝协会发布新的信息',
                    'keyword1' => $post['post_title'],
                    'keyword2' => "已发布",
                    'keyword3' => date("Y-m-d", $post['published_time']),
                    'remark' => '点击详情查看详细信息',
                ];
                $this->sendtemplatemsg($user["openid"], "ZAExTtRLBdZ1V1FVYD0cpyZ7hc_I8aH4TFPOlNOhNGM", $arra, $this->getSiteHost() . "/zbxh/wechatotherindex/post?pid=" . $pid);
            }
            return $this->success();
        }
    }

    public function sendcode()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $user = Db::name("user")->where(["id" => $id])->find();
        if (!empty($user["openid"])) {
            $arra = [
                'first' => '组织/企业认证通知',
                'keyword1' => date("Y-m-d", time()),
                'keyword2' => "组织/企业认证码",
                'keyword3' => "已发送",
                'remark' => '您的认证码为:【' . $user["code"] . "】,点击查看详情即可认证组织/企业。",
            ];
            $this->sendtemplatemsg($user["openid"], $this->getYwTempid(), $arra, $this->getSiteHost() . "/zbxh/wechatindex/companycertification");
        }
        return $this->success("发送成功");
    }

}
