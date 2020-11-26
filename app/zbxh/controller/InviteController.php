<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\jiaxiao\controller;
//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\HomeBaseController;
use EasyWeChat\Factory;
use think\Db;

/**
 * 个人中心
 */
class InviteController extends HomeBaseController
{
    /**
     * 跳转约车服务页面
     * @return mixed
     */
    public function invitecl()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        return $this->fetch('invitecl');
    }

    /**
     * 获取日期
     */
    public function getDateCount()
    {
        $nextfulldata = date("Y-m-d", strtotime("+1 day"));
        $nextdata = date("d", strtotime("+1 day"));
        $nowdata = intval(date("d"));
        $datacount = intval(date('t'));
        $result = [];
        $dcresult = [];
        if ($nowdata == $datacount) {
            $nextmonth = date("Y-m-d", strtotime("+1 day"));
            $datacount = intval(date('t', strtotime($nextmonth)));
            for ($i = 1; $i <= $datacount; $i++) {
                $data["datenum"] = date("d", strtotime("+" . $i . " day"));
                $data["datetag"] = date("Y-m-d", strtotime("+" . $i . " day"));
                $isweek = date("w", strtotime(date("Y-m-d", strtotime("+" . $i . " day"))));
                $data["week"] = $isweek;
                if ($isweek == "0" || $isweek == "6") {
                    $data["isweek"] = 1;
                } else {
                    $data["isweek"] = 2;
                }
                array_push($dcresult, $data);
            }
        } else {
            for ($i = 1; $i < $datacount; $i++) {
                $data["datenum"] = date("d", strtotime("+" . $i . " day"));
                $data["datetag"] = date("Y-m-d", strtotime("+" . $i . " day"));
                $isweek = date("w", strtotime($data["datetag"]));
                $data["week"] = $isweek;
                if ($isweek == "0" || $isweek == "6") {
                    $data["isweek"] = 1;
                } else {
                    $data["isweek"] = 2;
                }
                array_push($dcresult, $data);
                if (intval($data["datenum"]) == $datacount) {
                    break;
                }
            }
        }
        $result['dcresult'] = $dcresult;
        $result['nextdata'] = $nextfulldata;
        $result['nextnum'] = $nextdata;
        $result['instructor_openid'] = session("openid");

        $isweek = date("w", strtotime($nextfulldata));
        if ($isweek == "0" || $isweek == "6") {
            $result['nextisweek'] = 1;
        } else {
            $result['nextisweek'] = 2;
        }
        return $this->echoSuccess($result);
    }

    /**
     * 获取教练列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getinstructor()
    {
        $openid = session("openid");
        $uresult = Db::name("user")->where(["openid" => $openid])->find();
        $instructor = Db::name("user")->where(["id" => $uresult['instructor_id'], "user_type" => 3])->select()->toArray();
        return $this->echoSuccess($instructor);
    }

    public function getinstructorbystudentopenid()
    {
        $param = $this->request->param();
        $tag = $param['nextdata'];
        $isweek = date("w", strtotime($tag));
        $openid = session("openid");
        $result = Db::name("invite")->where(["student_openid" => $openid, "invite_time" => $tag, "status" => 1])->select()->toArray();
        if ($isweek == "0" || $isweek == "6") {
            $data["isweek"] = 1;
        } else {
            $data["isweek"] = 2;
        }
        $data['result'] = $result;
        return $this->echoSuccess($data);
    }

    /**
     * 保存预约
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveinvite()
    {
        $param = $this->request->param();
        $jsstr = $param['data'];
        $invite_type = $param['invite_type'];
        $time_type = $param['time_type'];
        $instructor_openid = $param['instructor_openid'];
        if (empty($instructor_openid)) {
            return false;
        }
        $data['invite_type'] = $invite_type;
        $data['time_type'] = $time_type;
        $data['instructor_openid'] = $instructor_openid;
        $instructor = Db::name("user")->where(["openid" => $instructor_openid])->find();
        $data['instructor_id'] = $instructor['id'];
        $data['instructor_openid'] = $instructor_openid;
        $data['instructor_name'] = $instructor['real_name'];
        $data['instructor_phone'] = $instructor['mobile'];
        $student = Db::name("user")->where(["openid" => session("openid")])->find();
        $data['student_id'] = $student['id'];
        $data['student_name'] = $student['real_name'];
        $data['student_openid'] = session("openid");
        $data['student_phone'] = $student['mobile'];
        $data['ctime'] = time();
        $data['invite_time'] = $jsstr['datetag'];
        $data['date_num'] = $param['date_num'];
        $result = Db::name("invite")->where(["instructor_openid" => $instructor_openid, "invite_time" => $data['invite_time'], "invite_type" => $invite_type, "status" => 1])->select()->toArray();
        if (count($result) > 0) {
            return $this->echoError([], "此时段已经被预约，请选择其他时段");
        } else {
            $result1 = Db::name("invite")->where(["invite_time" => $data['invite_time'], "invite_type" => $invite_type, "student_openid" => $data['student_openid'], "status" => 1])->select()->toArray();
            if (count($result1) > 0) {
                return $this->echoError([], "您在此时段已经约了其他教练，请选择其他时段");
            } else {
                Db::name("invite")->insertGetId($data);
                $user = Db::name("user")->where(["openid" => $data['student_openid']])->find();
                $instructoruser = Db::name("user")->where(["openid" => $data['instructor_openid']])->find();
                //预约时段：1、9-10，2、10-11，3、11-12，4、14-15，5、15-16，6、16-17，7、17-18
                if ($data['invite_type'] == 1) {
                    $tag = "9点-10点";
                } else if ($data['invite_type'] == 2) {
                    $tag = "10点-11点";
                } else if ($data['invite_type'] == 3) {
                    $tag = "11点-12点";
                } else if ($data['invite_type'] == 4) {
                    $tag = "14点-15点";
                } else if ($data['invite_type'] == 5) {
                    $tag = "15点-16点";
                } else if ($data['invite_type'] == 6) {
                    $tag = "16点-17点";
                } else if ($data['invite_type'] == 7) {
                    $tag = "17点-18点";
                } else if ($data['invite_type'] == 8) {
                    $tag = "8点-9点";
                } else if ($data['invite_type'] == 9) {
                    $tag = "13点-14点";
                }
                $data = $data['invite_time'] . ',' . $tag;
                $arra = [
                    'first' => '有学员预约了学车课程，请及时确认',
                    'keyword1' => $user['real_name'],
                    'keyword2' => $user['mobile'],
                    'keyword3' => $data,
                    'keyword4' => $instructoruser['real_name'],
                    'remark' => '点击详情查看预约信息',
                ];
                $this->sendinvitemsg($instructor_openid, $arra);
                return $this->echoSuccess();
            }

        }
    }

    /**
     * 切换教练获取预约
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeinstructor()
    {
        $param = $this->request->param();
        $instructor_openid = $param['instructor_openid'];
        $nextdata = $param['nextdata'];
        $invite = Db::name("invite")->where(["invite_time" => $nextdata, "instructor_openid" => $instructor_openid, "status" => 1])->select()->toArray();
        return $this->echoSuccess($invite);
    }

    /**
     * 删除预约
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function deleteinvite()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name("invite")->where(["id" => $id])->find();
        $user = Db::name("user")->where(["openid" => $data['student_openid']])->find();
        $instructoruser = Db::name("user")->where(["openid" => $data['instructor_openid']])->find();
        //预约时段：1、9-10，2、10-11，3、11-12，4、14-15，5、15-16，6、16-17，7、17-18
        if ($data['invite_type'] == 1) {
            $tag = "9点-10点";
        } else if ($data['invite_type'] == 2) {
            $tag = "10点-11点";
        } else if ($data['invite_type'] == 3) {
            $tag = "11点-12点";
        } else if ($data['invite_type'] == 4) {
            $tag = "14点-15点";
        } else if ($data['invite_type'] == 5) {
            $tag = "15点-16点";
        } else if ($data['invite_type'] == 6) {
            $tag = "16点-17点";
        } else if ($data['invite_type'] == 7) {
            $tag = "17点-18点";
        } else if ($data['invite_type'] == 8) {
            $tag = "8点-9点";
        } else if ($data['invite_type'] == 9) {
            $tag = "13点-14点";
        }
        $date = $data['invite_time'] . ',' . $tag;
        $arra = [
            'first' => '有学员取消了学车课程，请及时确认',
            'keyword1' => $user['real_name'],
            'keyword2' => $user['mobile'],
            'keyword3' => $date,
            'keyword4' => $instructoruser['real_name'],
            'remark' => '点击详情查看预约信息',
        ];
        $this->sendinvitemsg($data['instructor_openid'], $arra);
        Db::name("invite")->where(["id" => $id])->delete();
        return $this->echoSuccess();
    }

    public function getallinvite()
    {
        $param = $this->request->param();
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $invresult = Db::query("select * from cmf_invite where status = 1 and student_openid = '" . session("openid") . "' and ctime >= " . $beginToday);
        return $this->echoSuccess($invresult);
    }

    public function getallinvitebyinstructor()
    {
        $param = $this->request->param();
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $invresult = Db::query("select * from cmf_invite where status = 1 and instructor_openid = '" . session("openid") . "' and ctime >= " . $beginToday);
        return $this->echoSuccess($invresult);
    }

    public function invitelist()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        return $this->fetch('invitelist');
    }

    public function getinvitebyinstructor()
    {
        $param = $this->request->param();
        $instructor_openid = $param['instructor_openid'];
        $nextdata = $param['nextdata'];
        $invite = Db::name("invite")->where(["invite_time" => $nextdata, "instructor_openid" => $instructor_openid, "status" => 1])->select()->toArray();
        return $this->echoSuccess($invite);
    }

    /**
     * 获取今天的预约
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gettodayinvite()
    {
        $invite_time = date("Y-m-d");
        $open = session("openid");
        $result = Db::name("invite")->where(["invite_time" => $invite_time, "instructor_openid" => $open, "status" => 1])->select()->toArray();
        $data['todayinvite'] = $result;
        $data['invite_time'] = $invite_time;
        return $this->echoSuccess($data);
    }

    /**
     * 预约成功提醒
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendinvitemsg($openid, $arra)
    {
        $this->sendtemplatemsg($openid, $this->getInviteTempid(), $arra, 'http://jiaxiao.henbaoli.com/jiaxiao/Invite/invitecl.html');
    }

    /**
     * 获取学员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getstudent()
    {
        $open = session("openid");
        $user = Db::name("user")->where(["openid" => $open])->find();
        return $this->echoSuccess($user);
    }

    /**
     * 教练取消预约
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function qxinvite()
    {
        $param = $this->request->param();
        $data["id"] = $param["id"];
        $data["qxnote"] = $param["qxnote"];
        Db::name("invite")->where(["id" => $data["id"]])->update(["qxnote" => $data["qxnote"], "status" => 2]);
        $invite = Db::name("invite")->where(["id" => $data["id"]])->find();
        if ($invite['invite_type'] == 1) {
            $tag = "9点-10点";
        } else if ($invite['invite_type'] == 2) {
            $tag = "10点-11点";
        } else if ($invite['invite_type'] == 3) {
            $tag = "11点-12点";
        } else if ($invite['invite_type'] == 4) {
            $tag = "14点-15点";
        } else if ($invite['invite_type'] == 5) {
            $tag = "15点-16点";
        } else if ($invite['invite_type'] == 6) {
            $tag = "16点-17点";
        } else if ($invite['invite_type'] == 7) {
            $tag = "17点-18点";
        } else if ($invite['invite_type'] == 8) {
            $tag = "8点-9点";
        } else if ($invite['invite_type'] == 9) {
            $tag = "13点-14点";
        }
        $date = $invite['invite_time'] . ',' . $tag;
        $instructor = Db::name("user")->where(["id" => $invite["instructor_id"]])->find();
        $arra = [
            'first' => '您有一项预约被教练取消',
            'keyword1' => $invite['student_name'],
            'keyword2' => $instructor['mobile'] . "[教练]",
            'keyword3' => $date,
            'keyword4' => $instructor['real_name'],
            'remark' => '取消原因:' . $data["qxnote"],
        ];
        $this->sendinvitemsg($invite['student_openid'], $arra);
        return $this->echoSuccess();
    }
}
