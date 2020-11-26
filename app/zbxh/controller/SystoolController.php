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

use cmf\controller\HomeOtherBaseController;
use EasyWeChat\Factory;
use think\Db;
use QL\QueryList;

/**
 * 个人中心
 */
class SystoolController extends HomeOtherBaseController
{
    /**
     * 验证微信信息
     */
    public function checkgroupbuying()
    {
//        Db::name("log")->insert(["key" => "checkgroupbuying", "value" => "进入"]);
        $gbresult = Db::name("group_buying")->where(["status" => 1])->select()->toArray();
        foreach ($gbresult as $key => $item) {
            $rulist = Db::name("user_recharge")->where(['gbid' => $item['id'], "status" => 2])->select()->toArray();
            $pcount = count($rulist) + $item['virtual_p_count'];
            //如果参团人数满了就关闭此团购
            if ($pcount >= $item['p_count']) {
                Db::name("group_buying")->where(["id" => $item['id']])->update(["status" => 2]);
                $this->updateusertopricebygid($item['id']);
            }
            //如果团购时间过了关闭此团购
            if (time() >= $item['f_time']) {
                if ($pcount < $item['p_count']) {
                    $parray = json_decode($item['virtual_name'], true);
                    if (empty($parray)) {
                        $parray = [];
                    }
                    for ($i = 0; $i < $item['p_count'] - $pcount; $i++) {
                        $xing = $this->rdname();
                        array_push($parray, ["name" => $xing . "**"]);
                    }
                    Db::name("group_buying")->where(["id" => $item['id']])->update(["status" => 2, "virtual_name" => json_encode($parray), 'sy_p_count' => 0]);
                    $this->updateusertopricebygid($item['id']);
                } else {
                    Db::name("group_buying")->where(["id" => $item['id']])->update(["status" => 2]);
                    $this->updateusertopricebygid($item['id']);
                }
            } else {
                //计算和更新团购表中还差多少人成单
                $syc = $item["p_count"] - $pcount;
                Db::name("group_buying")->where(["id" => $item['id']])->update(["sy_p_count" => $syc]);
            }

        }
        exit();
    }

    /**
     * 微信支付回调
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function wxzfcallback()
    {
//        Db::name("log")->insert(["key" => "测试回调", "value" => "进入回调"]);
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
//        Db::name("log")->insert(["key" => "xml内容", "value" => $jsonxml]);
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
//                Db::name("log")->insert(["key" => "支付成功", "value" => $out_trade_no]);
                //根据订单号查询订单
                $urresult = Db::name("user_recharge")->where(["orderid" => $out_trade_no])->find();
                if (count($urresult) <= 0) {
                    $urresult = Db::name("user_recharge")->where(["bk_orderid" => $out_trade_no])->find();
                }
//                Db::name("log")->insert(["key" => "支付用户ID", "value" => $urresult["uid"]]);
//                Db::name("log")->insert(["key" => "支付状态", "value" => $urresult["deposit_status"]]);
                $user = Db::name("user")->where(["id" => $urresult["uid"]])->find();

                //以下为团购操作
                //判断此订单是否为团购订单
                if ($urresult['gbid'] != null) {
                    //把支付订单更新为已支付
                    Db::name("user_recharge")->where(["orderid" => $out_trade_no])->update(["status" => 2, "service_orderid" => $result['transaction_id']]);
                    //根据订单ID查询出团购信息
                    $gbresult = Db::name("group_buying")->where(['id' => $urresult['gbid'], "isdelete" => 0])->find();
                    //判断是否能查到团购信息
                    if (count($gbresult) > 0) {
                        //根据团购ID查询团购订单
                        $rulist = Db::name("user_recharge")->where(['gbid' => $urresult['gbid'], "status" => 2])->select()->toArray();
                        //计算当前参团人数
                        $pcount = count($rulist) + $gbresult['virtual_p_count'];
                        //如果参团人数满了就关闭此团购
                        if ($pcount >= $gbresult['p_count']) {
                            Db::name("group_buying")->where(["id" => $urresult['gbid']])->update(["status" => 2]);
                            $this->updateusertopricebygid($urresult['gbid']);
                        }
                        //如果团购时间过了关闭此团购
                        if (time() >= $gbresult['f_time']) {
                            if ($pcount < $gbresult['p_count']) {
                                $parray = json_decode($gbresult['virtual_name'], true);
                                for ($i = 0; $i < $gbresult['p_count'] - $pcount; $i++) {
                                    $xing = $this->rdname();
                                    array_push($parray, ["name" => $xing . "**"]);
                                }
                                Db::name("group_buying")->where(["id" => $urresult['gbid']])->update(["status" => 2, "virtual_name" => json_encode($parray)]);
                                $this->updateusertopricebygid($urresult['gbid']);
                            } else {
                                Db::name("group_buying")->where(["id" => $urresult['gbid']])->update(["status" => 2]);
                                $this->updateusertopricebygid($urresult['gbid']);
                            }
                        }
                        //计算和更新团购表中还差多少人成单
                        $syc = $gbresult["p_count"] - $pcount;
                        Db::name("group_buying")->where(["id" => $urresult['gbid']])->update(["sy_p_count" => $syc]);
                    }
                    //以下为订单支付回调更新状态的操作
                } else {
                    if ($urresult['deposit_status'] == 3) {
                        if ($urresult['real_deposit_pay'] * 100 == 0 && $urresult['deposit'] * 100 == $result['total_fee']) {
//                            Db::name("log")->insert(["key" => "回调状态", "value" => "进入支付定金"]);
//                            Db::name("log")->insert(["key" => "回调状态", "value" => "订单" . $out_trade_no]);
                            //支付订单更新已支付定金
                            Db::name("user_recharge")->where(["id" => $urresult["id"]])->update(["real_deposit_pay" => $result['total_fee'] / 100, "service_orderid" => $result['transaction_id'], "deposit_status" => 1]);
//                            Db::name("log")->insert(["key" => "更新为已支付", "value" => $urresult["product_id"]]);
                            $price = Db::name("price")->where(["id" => $urresult["product_id"]])->find();
//                            Db::name("log")->insert(["key" => "获取产品", "value" => "获取"]);
                            $arra = [
                                'first' => '恭喜您已交付定金成功',
                                'keyword1' => $out_trade_no,
                                'keyword2' => $user['user_nickname'],
                                'keyword3' => $price['name'],
                                'keyword4' => $urresult['deposit'] . "元",
                                'keyword5' => date("Y-m-d H:i", time()),
                                'remark' => '点击详情可以查看订单信息以及补款操作',
                            ];
                            Db::name("log")->insert(["key" => "组装数组", "value" => "组装"]);
                            $this->sendrechargemsg($urresult["openid"], $arra, "http://jiaxiao.henbaoli.com/jiaxiao/wechatindex/rechargelist.html");
                            Db::name("log")->insert(["key" => "发送消息", "value" => "发送"]);
                            $arra = [
                                'first' => '有新学员交付定金成功',
                                'keyword1' => $out_trade_no,
                                'keyword2' => $user['user_nickname'],
                                'keyword3' => $price['name'],
                                'keyword4' => $urresult['deposit'] . "元",
                                'keyword5' => date("Y-m-d H:i", time()),
                                'remark' => '可以登录后台订单管理中进行核对',
                            ];
                            $this->sendrechargemsg($this->getSystemopenid(), $arra, "");
                        }
                    }
                    //判断是否是在补款状态
                    if ($urresult['deposit_status'] == 1) {
                        //判断此次支付金额是否与补款金额一致，如果一致就把订单更新为已付款和已补款 real_deposit_pay 不等于0 证明已经付过定金
//                        Db::name("log")->insert(["key" => "回调状态", "value" => "real_deposit_pay" . $urresult['real_deposit_pay'] * 100]);
//                        Db::name("log")->insert(["key" => "回调状态", "value" => "supplement_mony" . $urresult['supplement_mony'] * 100]);
//                        Db::name("log")->insert(["key" => "回调状态", "value" => "total_fee".$result['total_fee']]);
                        if ($urresult['real_deposit_pay'] * 100 > 0 && $urresult['supplement_mony'] * 100 == $result['total_fee']) {
//                            Db::name("log")->insert(["key" => "回调状态", "value" => "进入补款"]);
//                            Db::name("log")->insert(["key" => "回调状态", "value" => "订单" . $out_trade_no]);
                            //把支付订单更新为已支付
                            if ($out_trade_no == $urresult['bk_orderid']) {
                                Db::name("user_recharge")->where(["id" => $urresult["id"]])->update(["status" => 2, "deposit_status" => 2, "real_sn_pay" => $result['total_fee'] / 100, "deposit_service_orderid" => $result['transaction_id']]);
                                Db::name("user")->where(["id" => $urresult["uid"]])->update(["is_price" => 1]);
                                $price = Db::name("price")->where(["id" => $urresult["product_id"]])->find();
                                $arra = [
                                    'first' => '恭喜您已交付补款成功',
                                    'keyword1' => $out_trade_no,
                                    'keyword2' => $user['user_nickname'],
                                    'keyword3' => $price['name'],
                                    'keyword4' => $urresult['supplement_mony'] . "元",
                                    'keyword5' => date("Y-m-d H:i", time()),
                                    'remark' => '点击详情可以查看订单信息',
                                ];
                                Db::name("log")->insert(["key" => "组装数组", "value" => "组装"]);
                                $this->sendrechargemsg($urresult["openid"], $arra, "http://jiaxiao.henbaoli.com/jiaxiao/wechatindex/rechargelist.html");
                                Db::name("log")->insert(["key" => "发送消息", "value" => "发送"]);
                                $arra = [
                                    'first' => '有学员交付补款成功',
                                    'keyword1' => $out_trade_no,
                                    'keyword2' => $user['user_nickname'],
                                    'keyword3' => $price['name'],
                                    'keyword4' => $urresult['supplement_mony'] . "元",
                                    'keyword5' => date("Y-m-d H:i", time()),
                                    'remark' => '可以登录后台订单管理中进行核对',
                                ];
                                $this->sendrechargemsg($this->getSystemopenid(), $arra, "");
                            }
                        }
                    } else if ($urresult['deposit_status'] == 0 && $result['total_fee'] == $urresult['money'] * 100) {
                        //把支付订单更新为已支付
                        Db::name("user_recharge")->where(["id" => $urresult["id"]])->update(["status" => 2, "service_orderid" => $result['transaction_id']]);
                        Db::name("user")->where(["id" => $urresult["uid"]])->update(["is_price" => 1]);
                        $price = Db::name("price")->where(["id" => $urresult["product_id"]])->find();
                        $arra = [
                            'first' => '恭喜您已交付全款成功',
                            'keyword1' => $out_trade_no,
                            'keyword2' => $user['user_nickname'],
                            'keyword3' => $price['name'],
                            'keyword4' => $urresult['money'] . "元",
                            'keyword5' => date("Y-m-d H:i", time()),
                            'remark' => '点击详情可以查看订单信息',
                        ];
                        Db::name("log")->insert(["key" => "组装数组", "value" => "组装"]);
                        $this->sendrechargemsg($urresult["openid"], $arra, "http://jiaxiao.henbaoli.com/jiaxiao/wechatindex/rechargelist.html");
                        Db::name("log")->insert(["key" => "发送消息", "value" => "发送"]);
                        $arra = [
                            'first' => '有新学员交付全款成功',
                            'keyword1' => $out_trade_no,
                            'keyword2' => $user['user_nickname'],
                            'keyword3' => $price['name'],
                            'keyword4' => $urresult['money'] . "元",
                            'keyword5' => date("Y-m-d H:i", time()),
                            'remark' => '可以登录后台订单管理中进行核对',
                        ];
                        $this->sendrechargemsg($this->getSystemopenid(), $arra, "");
                    }
                }
//                Db::name("log")->insert(["key" => "执行状态", "value" => "执行完毕"]);
                return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            }
        }
    }

    public function changeqtype()
    {
        $result = Db::query("select qb.id as qid,count(qb.id) as acount from cmf_question_bank as qb left join cmf_answer_bank as ab on qb.id = ab.qid group by qb.id");
        for ($i = 0; $i < count($result); $i++) {
            var_dump($result[$i]['qid'] . "---------" . intval($result[$i]['acount']));
            var_dump(intval($result[$i]['acount']) == 2);
            if (intval($result[$i]['acount']) == 2) {
                Db::name("question_bank")->where(["id" => $result[$i]['qid']])->update(["answer_type" => 1]);
            } else {
                Db::name("question_bank")->where(["id" => $result[$i]['qid']])->update(["answer_type" => 2]);
            }
        }
    }

    /**
     * 发送支付信息
     * @param $openid
     * @param $arra
     * @param $url
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendrechargemsg($openid, $arra, $url)
    {
        $this->sendtemplatemsg($openid, $this->getRechargeTempid(), $arra, $url);
    }

    public function getzbxhinfo()
    {
        $cid = 4;
        $ql = QueryList::getInstance();
        $ul = $ql->get("http://www.gxzb.org/plus/list.php?tid=92")->find('.ul_list');
        $ullist = $ul->find("li")->map(function ($row) {
            $tempall = [];
            $temp = [];
            $temp['title'] = $row->find('a')->attr("title");
            $temp['link'] = $row->find('a')->attr("href");
            array_push($tempall, $temp);
            return $tempall;
        });
        foreach ($ullist as $item) {
            $reulst = Db::name("portal_post")->where(["post_title" => $item[0]["title"], "cid" => $cid])->select()->toArray();
            //不采集文章列表
            $fil = ["6月16日 缘与美与您相约上海滩"];
            if (count($reulst) == 0 && !in_array($item[0]["title"],$fil)) {
                sleep(1);
                Db::name("log")->insert(["key" => "开始采集", "value" => $item[0]["title"]]);
                $contentlink = $ql->get("http://www.gxzb.org" . $item[0]["link"])->find('.ctn_750');
                $contentlink->find("img")->map(function ($row) {
                    sleep(1);
                    $imgsrc = $row->attr('src');
                    $url = "http://www.gxzb.org" . $imgsrc;

                    $filename = 'upload/default/old/' . date("dMYHis") . '.jpg';//文件名称生成

                    $content = file_get_contents($url);

                    file_put_contents($filename, $content);

                    $filename = str_replace("upload/", "", $filename);

                    $row->attr('src', $filename);

                });
                $contentlink->find(".detail_info")->find("span")->remove();
                $time = $contentlink->find(".detail_info")->text();
                $time = str_replace("发布日期：", "", $time);
                $publishtime = strtotime($time);
                $contentlink->find(".detail_info")->remove();
                $contentlink->find(".pages")->remove();
                $contentlink->find(".detail_title")->remove();
//        var_dump(htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode(iconv("gb2312//IGNORE", "utf-8", $contentlink->html())), true)));
                $content = htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode(iconv("gb2312//IGNORE", "utf-8", $contentlink->html())), true));
                $data["post_title"] = $item[0]["title"];
                $data["cid"] = $cid;
                $data["post_keywords"] = $item[0]["title"];
                $data["post_excerpt"] = $item[0]["title"];
                $data["post_type"] = 1;
                $data["post_format"] = 1;
                $data["user_id"] = 1;
                $data["post_status"] = 1;
                $data["create_time"] = $publishtime;
                $data["published_time"] = $publishtime;
                $data["post_content"] = $content;
                $pid = Db::name("portal_post")->insertGetId($data);
                Db::name("portal_category_post")->insertGetId(["post_id" => $pid, "category_id" => $cid]);
                Db::name("log")->insert(["key" => "采集完毕", "value" => $item[0]["title"]]);
            } else {
                Db::name("log")->insert(["key" => "采集采集", "value" => "重复文章-" . $item[0]["title"]]);
            }

        }
    }

}
