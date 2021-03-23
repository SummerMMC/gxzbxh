<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\zbxh\model;

use app\admin\model\RouteModel;
use think\Db;
use think\db\Query;
use think\Model;
use tree\Tree;

class PortalPostModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    /**
     * 根据栏目ID获取文章
     * @param $cid
     * @param $limit
     * @param $order
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPortalListByCid($cid, $limit, $order)
    {
        if (cache($cid . "_PortalListByCid") === false) {
            $postlist = $this->alias("p")->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")->order($order)->where(["pcp.category_id" => $cid, "p.post_status" => 1])->paginate($limit);
            cache($cid . "_PortalListByCid", $postlist, 3600);
            return $postlist;
        } else {
            return cache($cid . "_PortalListByCid");
        }
    }

    /**
     *
     * @param $cid
     * @param $limit
     * @param $order
     * @return mixed|\think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPortalListByCidForPage($cid, $limit, $order)
    {
        return $this->alias("p")->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")->order($order)->where(["pcp.category_id" => $cid, "p.post_status" => 1])->paginate($limit);
    }

    /**
     * 获取热点新闻
     * @param $limit
     * @param $order
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPortalHotList($limit, $order)
    {
        if (cache("hotdata") === false) {
            $hotdata = $this->alias("p")->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")->group("p.id")->order($order)->where(["p.recommended" => 1, "p.post_status" => 1])->paginate($limit);
            cache("hotdata", $hotdata, 3600);
            return $hotdata;
        } else {
            return cache("hotdata");
        }
    }

    /**
     * 获取首页图片
     * @param $limit
     * @param $order
     * @return mixed|\think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPortalImgList($limit, $order)
    {
        if (cache("index_img_data") === false) {
            $imgdata = $this->alias("p")->leftJoin("portal_category_post pcp", "pcp.post_id = p.id")->order($order)->where(["p.roll" => 1, "p.post_status" => 1])->paginate($limit);
            cache("index_img_data", $imgdata, 3600);
            return $imgdata;
        } else {
            return cache("index_img_data");
        }
    }


}