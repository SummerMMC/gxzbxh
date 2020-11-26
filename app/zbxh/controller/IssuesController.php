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

namespace app\jiaxiao\controller;

use cmf\controller\HomeBaseController;
use EasyWeChat\Factory;
use EasyWeChat\payment\Order;
use think\Db;

class IssuesController extends HomeBaseController
{
    /**
     * 跳转首页
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        return $this->fetch('index');
    }

    /**
     * 根据类型获取题目
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getissuesbytype()
    {
        $param = $this->request->param();
        $type = $param['type'];
        $issuesindex = $param['issuesindex'];
        $count = Db::name("question_bank")->where(["type" => $type])->count();
        $result = Db::name("question_bank")->where(["type" => $type])->limit($issuesindex, 1)->select()->toArray();
        $aresult = Db::name("answer_bank")->where(['qid' => $result[0]['id']])->select()->toArray();
        $data['count'] = $count;
        $data['result'] = $result;
        $data['aresult'] = $aresult;
        return $this->echoSuccess($data);
    }

    /**
     * 获取考试随机题目
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getexamination()
    {
        $param = $this->request->param();
        $index = $param['index'];
        if (intval($index) == 1) {
//            Db::name("log")->insert(['key' => "题目index", "value" => $index]);
            session('temparray', null);
        }
        $temparray = session('temparray');
        if (empty($temparray)) {
            $temparray = [];
        }
        $result = $this->sqlgettimu($index);
        $temp = true;
        while ($temp) {
            if (in_array($result[0]['id'], $temparray)) {
//                Db::name("log")->insert(['key' => "重复模拟考试题目", "value" => $result[0]['id']]);
                $result = $this->sqlgettimu($index);
            } else {
                array_push($temparray, $result[0]['id']);
                session("temparray", $temparray);
//                Db::name("log")->insert(['key' => "模拟考试题目", "value" => $result[0]['id']]);
                $temp = false;
            }
        }
        $aresult = Db::name("answer_bank")->where(["qid" => $result[0]['id']])->select()->toArray();
        $data['result'] = $result;
        $data['aresult'] = $aresult;
        return $this->echoSuccess($data);
    }

    public function sqlgettimu($index)
    {
        if (intval($index) <= 40) {
            $result = Db::query("SELECT t1.*,t2.id as tid FROM `cmf_question_bank` AS t1 
                            JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `cmf_question_bank` where answer_type = 1)-(SELECT MIN(id) FROM `cmf_question_bank` where answer_type = 1))+(SELECT MIN(id) FROM `cmf_question_bank` where answer_type = 1)) AS id) AS t2
                            WHERE t1.id >= t2.id and t1.answer_type = 1
                            ORDER BY t1.id LIMIT 1;");

        } else {
            $result = Db::query("SELECT t1.*,t2.id as tid  FROM `cmf_question_bank` AS t1 
                            JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `cmf_question_bank` where answer_type = 2)-(SELECT MIN(id) FROM `cmf_question_bank` where answer_type = 2))+(SELECT MIN(id) FROM `cmf_question_bank` where answer_type = 2)) AS id) AS t2
                            WHERE t1.id >= t2.id and t1.answer_type = 2
                            ORDER BY t1.id LIMIT 1;");
        }
        return $result;
    }

    /**
     * 提交用户答题记录
     */
    public function subexamination()
    {
        $param = $this->request->param();
        $rightlist = $param['rightlist'];
        $errorlist = $param['errorlist'];
        $openid = $param['openid'];
        $rcont = $param['rcont'];
        $econt = $param['econt'];
        if (!empty($rightlist) || !empty($errorlist)) {
            $data['openid'] = $openid;
            $data['rcount'] = $rcont;
            $data['ecount'] = $econt;
            $data['ctime'] = time();
            $id = Db::name("user_examination")->insertGetId($data);
            $rarray = explode('#14', $rightlist);
            $earray = explode('#14', $errorlist);
            if (!empty($rarray[0])) {
                for ($i = 0; $i < count($rarray); $i++) {
                    $item = explode('#13', $rarray[$i]);
                    $redata['qid'] = $item[0];
                    $redata['title'] = $item[1];
                    $redata['answer'] = $item[2];
                    $redata['type'] = 1;
                    $redata['ex_id'] = $id;
                    $redata['openid'] = $openid;
                    Db::name("user_examination_result")->insert($redata);
                }
            }
            if (!empty($earray[0])) {
                for ($i = 0; $i < count($earray); $i++) {
                    $item = explode('#13', $earray[$i]);
                    $redata['qid'] = $item[0];
                    $redata['title'] = $item[1];
                    $redata['answer'] = $item[2];
                    $redata['type'] = 2;
                    $redata['ex_id'] = $id;
                    $redata['openid'] = $openid;
                    Db::name("user_examination_result")->insert($redata);
                }
            }
            session('temparray', null);
            return $this->echoSuccess();
        }
    }

    /**
     * 查询成绩
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getexaminationlist()
    {
        $param = $this->request->param();
        $openid = $param['openid'];
        $result = Db::name("user_examination")->where(["openid" => $openid])->order("ctime desc")->select()->toArray();
        return $this->echoSuccess($result);
    }

    /**
     * 删除答题结果
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delexamination()
    {
        $param = $this->request->param();
        $id = $param["id"];
        Db::name("user_examination")->where(["id" => $id])->delete();
        Db::name("user_examination_result")->where(["ex_id" => $id])->delete();
        return $this->echoSuccess();
    }

    /**
     * 查询答错的题目
     */
    public function getmistakeslist()
    {
        $param = $this->request->param();
        $openid = $param["openid"];
        $result = Db::query("select title,count(qid) as qcount,qid from cmf_user_examination_result where openid = '" . $openid . "' and type = 2 group by qid order by qcount desc");
        return $this->echoSuccess($result);
    }

    public function getmistakesbyid()
    {
        $param = $this->request->param();
        $id = $param["id"];
        $qresult = Db::name("question_bank")->where(["id" => $id])->find();
        $aresult = Db::name("answer_bank")->where(["qid" => $id])->select()->toArray();
        $data["result"] = $qresult;
        $data['aresult'] = $aresult;
        return $this->echoSuccess($data);
    }

    /**
     * 跳转科目一列表
     * @return mixed
     */
    public function issueslist()
    {
        $param = $this->request->param();
        $openid = session('openid');
        return $this->fetch('issueslist');
    }

    /**
     * 练习详情页面
     * @return mixed
     */
    public function issuesdetail()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("type", $param['type']);
        return $this->fetch('issuesdetail');
    }

    /**
     * 跳转考试页面
     * @return mixed
     */
    public function examination()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        return $this->fetch('examination');
    }

    /**
     * 跳转考试成绩列表页
     * @return mixed
     */
    public function examinationlist()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        return $this->fetch('examinationlist');
    }

    /**
     * 跳转错题列表
     * @return mixed
     */
    public function mistakeslist()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        return $this->fetch('mistakeslist');
    }

    /**
     * 跳转错题详情列表
     * @return mixed
     */
    public function mistakesdetail()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $this->assign("openid", $openid);
        $this->assign("id", $param['id']);
        return $this->fetch('mistakesdetail');
    }

    /**
     * 科目2列表
     * @return mixed
     */
    public function issues2list()
    {
        $param = $this->request->param();
        $openid = session('openid');
        return $this->fetch('issues2list');
    }

    public function issues2detail()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $id = $param["id"];
        $this->assign("id", $id);
        return $this->fetch('issues2detail');
    }

    public function issues3detail()
    {
        $param = $this->request->param();
        $openid = session('openid');
        $id = $param["id"];
        $this->assign("id", $id);
        return $this->fetch('issues3detail');
    }

    public function cleanmistakeslist()
    {
        $openid = session("openid");
        if (!empty($openid)) {
            Db::name("user_examination_result")->where(["openid" => $openid])->delete();
        }
        return $this->echoSuccess();
    }
}
