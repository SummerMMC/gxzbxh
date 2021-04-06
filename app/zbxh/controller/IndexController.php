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

use app\cartoon\api\MyUserApi;
use app\zbxh\model\PortalPostModel;
use cmf\controller\HomeOtherBaseController;
use EasyWeChat\Factory;
use think\Db;

class IndexController extends HomeOtherBaseController
{
    /**
     * 跳转首页
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        if (array_key_exists("cid", $param)) {
            $this->assign("topcid", $this->getTopcid($param["cid"]));
        } else {
            $this->assign("topcid", 0);
        }

        $alldata = [];
        //资讯中心菜单
        $alldata['zxzxmenu'] = $this->getMenu(1);
        //热点数据
        $postmodel = new PortalPostModel();
        $hotdatapostlist = $postmodel->getPortalHotList(10, "p.is_top desc,pcp.list_order desc,p.published_time desc");
        $alldata['hotdata'] = $hotdatapostlist;

        //企业广告
        $alldata['qygglist'] = $this->getlistdata(29, 9);

        //资讯中心
        $zxzxpostlist = $postmodel->getPortalListByCid(8, 7, "p.is_top desc,pcp.list_order desc,p.published_time desc");
        foreach ($zxzxpostlist as $item) {
            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        $alldata['zxzx'] = $zxzxpostlist->toArray();
        //认识协会
        $alldata['rsxhmenu'] = $this->getMenu(4);
        //企业文化
        $alldata['qywhlist'] = $this->getlistdata(13, 5);
        //放心示范店
        $alldata['fxsflist'] = $this->getlistdata(14, 5);
        //会员活动
        $alldata['hyhdlist'] = $this->getlistdata(15, 2);
        //会展活动
        $alldata['hzhdlist'] = $this->getlistdata(3, 6);
        //协会动态
        $alldata['xhdtlist'] = $this->getlistdata(9, 5);
        //通知公告
        $alldata['tzgglist'] = $this->getlistdata(5, 5);
        //专业人才
        $alldata['zyrcmenu'] = $this->getMenu(21);

        $this->assign("alldata", $alldata);

        return $this->fetch('index');
    }

    public function test()
    {
        return $this->fetch("test");
    }

    public function getAlldate()
    {
        $param = $this->request->param();
        $alldata = [];
        //资讯中心菜单
//        if (array_key_exists("zxzxmid", $param)) {
//            $alldata['zxzxmenu'] = $this->getMenu($param["zxzxmid"]);
//        } else {
//            $alldata['zxzxmenu'] = $this->getMenu(0);
//        }
        //热点数据
        $postmodel = new PortalPostModel();
//        $hotdatapostlist = $postmodel->getPortalHotList($param['hotlimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
//        $alldata['hotdata'] = $hotdatapostlist;
        //首页图片
        $imagepostlist = $postmodel->getPortalImgList($param['imglimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
        foreach ($imagepostlist as $item) {
            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
        }
        $alldata['image'] = $imagepostlist;
        //资讯中心
//        $zxzxpostlist = $postmodel->getPortalListByCid($param['zxzxcid'], $param['zxzxlimit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
//        foreach ($zxzxpostlist as $item) {
//            $item["thumbnail"] = cmf_get_image_url($item["thumbnail"]);
//        }
//        $alldata['zxzx'] = $zxzxpostlist;
//        //认识协会
//        if (array_key_exists("rsxhmid", $param)) {
//            $alldata['rsxhmenu'] = $this->getMenu($param["rsxhmid"]);
//        } else {
//            $alldata['rsxhmenu'] = $this->getMenu(0);
//        }
//        //企业文化
//        $alldata['qywhlist'] = $this->getlistdata($param['qywhcid'], $param['qywhlimit']);
//        //放心示范店
//        $alldata['fxsflist'] = $this->getlistdata($param['fxsfcid'], $param['fxsflimit']);
//        //会员活动
//        $alldata['hyhdlist'] = $this->getlistdata($param['hyhdcid'], $param['hyhdlimit']);
//        //会展活动
//        $alldata['hzhdlist'] = $this->getlistdata($param['hzhdcid'], $param['hzhdlimit']);
//        //协会动态
//        $alldata['xhdtlist'] = $this->getlistdata($param['xhdtcid'], $param['xhdtlimit']);
//        //通知公告
//        $alldata['tzgglist'] = $this->getlistdata($param['tzggcid'], $param['tzgglimit']);
//        //专业人才
//        $alldata['zyrcmenu'] = $this->getMenu(21);

        $this->assign("alldata", $alldata);

        return $this->echoSuccess($alldata);
    }

    /**
     * 获取热点新闻
     * @throws \think\exception\DbException
     */
    public function hotdata()
    {
        $param = $this->request->param();
        $postmodel = new PortalPostModel();
        $postlist = $postmodel->getPortalHotList($param['limit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
        return $this->echoSuccess($postlist);
    }


    /**
     * 获取菜单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuAjax()
    {
        $param = $this->request->param();
        if (array_key_exists("mid", $param)) {
            return $this->echoSuccess($this->getMenu($param["mid"]));
        } else {
            return $this->echoSuccess($this->getMenu(0));
        }
    }

    public function getPlist()
    {
        $param = $this->request->param();
        $postmodel = new PortalPostModel();
        $postlist = $postmodel->getPortalListByCid($param['cid'], $param['limit'], "p.is_top desc,pcp.list_order desc,p.published_time desc");
    }

    /**
     * 列表页面
     * @return mixed
     */
    public function postlist()
    {
        $param = $this->request->param();
        if (array_key_exists("cid", $param)) {
            $pid = $param['cid'];
            session("pid", $pid);
        }
        $this->assign("cid", session("pid"));
        $this->assign("topcid", $this->getTopcid(session("pid"))['cid']);
        $postobj = Db::name("portal_category")->where(["delete_time" => 0, "status" => 1, "id" => session("pid")])->find();
        $this->assign("postname", $postobj['name']);
        if (0 == $this->getTopcid(session("pid"))["post_id"]) {
            $clist = $this->getMenu($this->getTopcid(session("pid"))['cid']);
            $this->assign("clist", $clist);
            $postmodel = new PortalPostModel();
            $postlist = $postmodel->getPortalListByCidForPage(session("pid"), 10, "p.is_top desc,pcp.list_order desc,p.published_time desc");
            $page = $postlist->render();
            $this->assign("page", $page);
            $this->assign("postlist", $postlist);
            return $this->fetch("postlist");
        } else {
            $post = Db::name("portal_post")->alias("p")
                ->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")
                ->where(["p.id" => $this->getTopcid(session("pid"))["post_id"]])->find();
            $this->assign("topcid", $this->getTopcid($post["category_id"])['cid']);
            $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_content"]));
            if (!empty($post['more'])) {
                $post['more'] = json_decode($post['more'], true);
                if (array_key_exists("files", $post['more'])) {
                    foreach ($post['more']["files"] as $key => $item) {
                        $post['more']["files"][$key]["url"] = cmf_get_image_url($item["url"]);
                    }
                }
            }
            $this->assign("post", $post);
            return $this->fetch("post");
        }
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
        $this->assign("topcid", $this->getTopcid($post["category_id"])['cid']);
        $post["post_content"] = cmf_replace_content_file_url(htmlspecialchars_decode($post["post_content"]));
        if (!empty($post['more'])) {
            $post['more'] = json_decode($post['more'], true);
            if (array_key_exists("files", $post['more'])) {
                foreach ($post['more']["files"] as $key => $item) {
                    $post['more']["files"][$key]["url"] = cmf_get_image_url($item["url"]);
                }
            }
        }
        $this->assign("post", $post);
        return $this->fetch("post");
    }

    /**
     * 微信回调
     */
    public function oauthcallback()
    {
        $query = $this->request->param();
        $token = $query['token'];
        if (!array_key_exists('token', $query)) {
            dump("访问异常!");
            return false;
        }
        $url = $query['requrl'];

        $config = $this->assembleWxConfig($token);

        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        session("wechate_user", $user->toArray());
        session("openid", $user['original']['openid']);
        session("token", $token);
        $this->creatuser();
        if (!array_key_exists('token', $query)) {
            dump("访问异常!");
            return false;
        }
        $wxhost = Db::name("wx_config")->where(["token" => $token])->select()->toArray();
        $reurl = "http://" . $wxhost[0]['host'] . $url;
        $reurl = str_replace('.html', '', $reurl);
        return $this->redirect($reurl);
    }

    public function baidutuisongapi()
    {
        $urls = array(
            'http://www.gxzbxh.com/zbxh/index/index.html',
            'http://www.gxzbxh.com/zbxh/index/post/pid/189.html',
            'http://www.gxzbxh.com/zbxh/index/post/pid/188.html',
        );
        //新增一个数组
//        $urlsss = home_url(add_query_arg(array()));//WordPress的获取本页面url
//        $urls[0] = $urlsss; //向新数组中第零位新增一条数据
        $api = 'http://data.zz.baidu.com/urls?site=www.gxzbxh.com&token=yLVT2XyHRceCxZZ3';//"xxx"为百度推送api的秘钥，人手一个
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);//上边这段是百度推送官方代码
        var_dump($result);
    }
}
