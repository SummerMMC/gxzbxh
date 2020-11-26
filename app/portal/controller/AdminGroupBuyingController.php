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


class AdminGroupBuyingController extends AdminBaseController
{
    /**
     * 跳转后台管理教练页面
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        $where['status'] = 1;
        $where['isdelete'] = 0;
        $limit = 10;
        if (array_key_exists("gid", $param) && !empty($param["gid"])) {
            $where['gid'] = $param["gid"];
        }
        if (array_key_exists("status", $param) && $param["status"] != 0) {
            $where['status'] = $param["status"];
        }
        $groupbuying = Db::name("group_buying")->where($where)->paginate($limit);
        $page = $groupbuying->render();
        $this->assign("page", $page);
        $this->assign("groupbuying", $groupbuying);
        return $this->fetch('index');
    }

    /**
     * 跳转添加团购页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $category = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("school", $category);
        return $this->fetch('add');
    }

    /**
     * 根据驾校id获取产品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getpid()
    {
        $param = $this->request->param();
        $sid = $param["sid"];
        $presult = Db::name("price")->where(["did" => $sid])->select()->toArray();
        return $this->success("", null, $presult);
    }

    /**
     * 创建团购
     */
    public function addPost()
    {
        $param = $this->request->param();
        $data = $param["post"];
        $data["ctime"] = time();
        $data["status"] = 1;
        $timestr = "+" . $data["p_time"] . "hour";
        $data["f_time"] = strtotime($timestr, $data["ctime"]);
        $data["gid"] = $this->random(8, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
        $data["sy_p_count"] = $data["p_count"] - $data["virtual_p_count"];
        if (!empty($data["virtual_p_count"])) {
            for ($i = 0; $i < $data["virtual_p_count"]; $i++) {
                $xing = $this->rdname();
                $xingar[$i] = ["name" => $xing . "**"];
            }
            $data["virtual_name"] = json_encode($xingar);
        }
        Db::name("group_buying")->insert($data);
        return $this->success('添加成功!');
    }

    public function editPost()
    {
        $param = $this->request->param();
        $data = $param["post"];
        $bfgb = Db::name("group_buying")->where(["id" => $param["id"]])->find();
//        $data["ctime"] = time();
//        $data["status"] = 1;
        $timestr = "+" . $data["p_time"] . "hour";
        $data["f_time"] = strtotime($timestr, $bfgb["ctime"]);
        $ur = Db::name("user_recharge")->where(["gbid" => $param["id"], "status" => 2])->select()->toArray();
        $data["sy_p_count"] = $data["p_count"] - ($data["virtual_p_count"] + count($ur));
        if (!empty($data["virtual_p_count"])) {
            for ($i = 0; $i < $data["virtual_p_count"]; $i++) {
                $xing = $this->rdname();
                $xingar[$i] = ["name" => $xing . "**"];
            }
            $data["virtual_name"] = json_encode($xingar);
        }
        Db::name("group_buying")->where(["id" => $param["id"]])->update($data);
        return $this->success('编辑成功!');
    }

    public function rdname()
    {
        $xing_d = ['赵', '钱', '孙', '李', '周', '吴', '郑', '王', '冯', '陈', '褚', '卫', '蒋', '沈', '韩', '杨', '朱', '秦', '尤', '许', '何', '吕', '施', '张', '孔', '曹', '严', '华', '金', '魏', '陶', '姜', '戚', '谢', '邹', '喻', '柏', '水', '窦', '章', '云', '苏', '潘', '葛', '奚', '范', '彭', '郎', '鲁', '韦', '昌', '马', '苗', '凤', '花', '方', '任', '袁', '柳', '鲍', '史', '唐', '费', '薛', '雷', '贺', '倪', '汤', '滕', '殷', '罗', '毕', '郝', '安', '常', '傅', '卞', '齐', '元', '顾', '孟', '平', '黄', '穆', '萧', '尹', '姚', '邵', '湛', '汪', '祁', '毛', '狄', '米', '伏', '成', '戴', '谈', '宋', '茅', '庞', '熊', '纪', '舒', '屈', '项', '祝', '董', '梁', '杜', '阮', '蓝', '闵', '季', '贾', '路', '娄', '江', '童', '颜', '郭', '梅', '盛', '林', '钟', '徐', '邱', '骆', '高', '夏', '蔡', '田', '樊', '胡', '凌', '霍', '虞', '万', '支', '柯', '管', '卢', '莫', '柯', '房', '裘', '缪', '解', '应', '宗', '丁', '宣', '邓', '单', '杭', '洪', '包', '诸', '左', '石', '崔', '吉', '龚', '程', '嵇', '邢', '裴', '陆', '荣', '翁', '荀', '于', '惠', '甄', '曲', '封', '储', '仲', '伊', '宁', '仇', '甘', '武', '符', '刘', '景', '詹', '龙', '叶', '幸', '司', '黎', '溥', '印', '怀', '蒲', '邰', '从', '索', '赖', '卓', '屠', '池', '乔', '胥', '闻', '莘', '党', '翟', '谭', '贡', '劳', '逄', '姬', '申', '扶', '堵', '冉', '宰', '雍', '桑', '寿', '通', '燕', '浦', '尚', '农', '温', '别', '庄', '晏', '柴', '瞿', '阎', '连', '习', '容', '向', '古', '易', '廖', '庾', '终', '步', '都', '耿', '满', '弘', '匡', '国', '文', '寇', '广', '禄', '阙', '东', '欧', '利', '师', '巩', '聂', '关', '荆'];
        $x = $xing_d[mt_rand(0, count($xing_d) - 1)];
        return $x;
    }

    public function edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $category = Db::name("company")->where(["status" => 1])->select()->toArray();
        $this->assign("school", $category);
        $gbdata = Db::name("group_buying")->where(["id" => $id])->find();
        $this->assign("gbdata", $gbdata);
        return $this->fetch("edit");
    }

    /**
     * 删除团购
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        Db::name("group_buying")->where(["id" => $id])->update(["isdelete" => 1]);
        return $this->success("操作成功!");
    }
}
