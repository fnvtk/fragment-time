<?php


namespace app\api\controller\app\spsj;


use app\common\controller\Api;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;

class Task extends Api
{
    use OssHttpsTrait;
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $DB;
    protected $appid;
    protected $wechatApp;
    protected $userInfo;
    protected $oss;

    public function _initialize()
    {
        parent::_initialize();
        $this->oss = [
            'bucket' => 'karuosiyujzk',
            'region' => 'oss-cn-shenzhen.aliyuncs.com',
            'accessKeyId' => getenv('ALIYUN_OSS_ACCESS_KEY_ID') ?: '',
            'accessKeySecret' => getenv('ALIYUN_OSS_ACCESS_KEY_SECRET') ?: ''
        ];


        $this->appid = $this->request->post('appid', 'wx6489c26045912fe1');
        if (empty($this->appid)) {
            $this->error('非法进入');
        }
        $this->wechatApp = Db::table('WechatApp')
            ->where(array('appId' => $this->appid))
            ->find();

        if (empty($this->wechatApp)) {
            $this->error('应用不存在');
        }

        $this->uid = $this->request->post('uid');
        if (!empty($this->uid)) {
            $this->userInfo = Db::table('WechatApp_fans')
                ->where(array('id' => $this->uid, 'appid' => $this->wechatApp['id']))
                ->find();
        }

    }

    /**
     * 获取热门任务
     */
    public function getHotTask()
    {
        if ($this->request->isPost()) {
            $uid = $this->request->post('uid');
            $page = $this->request->post('page');
            $companyId = $this->request->post('companyId', 0);

            $where = [];
            $where['appid'] = $this->wechatApp['id'];
            $where['isDel'] = 0;
            $where['isShow'] = 1;
            $where['isHot'] = 1;
            $where['status'] = 1;
            $where['companyId'] = $companyId;

            // 过滤已结束的任务：deadline 为空、为 '0000-00-00 00:00:00' 或大于当前时间
            $currentTime = date('Y-m-d H:i:s');
            $where[] = function($query) use ($currentTime) {
                $query->where('deadline', '>', $currentTime)
                    ->whereOr('deadline', '=', '0000-00-00 00:00:00')
                    ->whereOr('deadline', 'null');
            };

            $data = Db::table('WechatApp_task')
                ->field('id,name,type,pic,createTime,brief,reward,addReward,drawType,maxUserNum,drawNum,deadline')
                ->where($where)
                ->page($page, 10)
                ->order('sort DESC,id DESC')
                ->select();

            foreach ($data as $k => &$v) {
                if ($v['type'] == 2 && !empty($v['addReward']) && $v['addReward'] > 0) {
                    $reward = ' ' . $v['addReward'] * $v['maxUserNum'] . '~' . $v['reward'] * $v['maxUserNum'];
                    $v['money'] = '+ ' . $reward;
                } else {
                    $v['money'] = '+ ' . $v['reward'];
                }
            }
            unset($v);


            if (!empty($uid)) {
                foreach ($data as $k => &$v) {
                    $where = [];
                    $where['taskId'] = $v['id'];
                    $where['uid'] = $uid;
                    $where['isDel'] = 0;
                    if (!empty($v['drawType'])) {
                        $time = date('Y-m-d');
                        if ($v['drawType'] == 1){
                            $where['createTime'] = ['between', [$time . ' 00:00:00', $time . ' 23:59:59']];
                        }else{
                            $where['createTime'] = ['between', [$v['createTime'], $time . ' 23:59:59']];
                        }
                    }

                    $taskReceive = Db::table('WechatApp_taskReceive')
                        ->field('id,status')
                        ->where($where)
                        ->order('id DESC')
                        ->find();

                    // 统计已领取数量时，包括审核中的任务（不排除）
                    // 这样如果每天限制1次，无论任务处于什么状态，都不能再次领取
                    $taskNum = Db::table('WechatApp_taskReceive')
                        ->field('id,status')
                        ->where($where)
                        ->count();

                    // 检查是否有未提交的任务（status == 0），如果有，不能领取新任务
                    $unsubmittedTask = Db::table('WechatApp_taskReceive')
                        ->field('id,status')
                        ->where($where)
                        ->where('status', 0)
                        ->order('id DESC')
                        ->find();

                    // 如果有未提交的任务，优先显示未提交状态
                    if (!empty($unsubmittedTask)) {
                        $taskReceive = $unsubmittedTask;
                    }
                    
                    // 如果最新任务是审核中，且还有领取次数，且没有未提交的任务，允许再次领取（显示为待领取）
                    if (empty($unsubmittedTask) && !empty($taskReceive) && $taskReceive['status'] == 1 && !empty($v['drawType']) && ($v['drawType'] == 1 || $v['drawType'] == 2) && $taskNum < $v['drawNum']) {
                        $v['taskStatus'] = -1;
                        $v['statusTxt'] = '待领取';
                    } else if (empty($unsubmittedTask) && !empty($v['drawType']) && $taskNum < $v['drawNum']) {
                        $taskReceive['status'] = -1;
                    }

                    if (empty($taskReceive)) {
                        $v['taskStatus'] = -1;
                        $v['statusTxt'] = '待领取';
                    } else if ($taskReceive['status'] == 0) {
                        $v['taskStatus'] = 0;
                        $v['statusTxt'] = '待提交';
                    } else if ($taskReceive['status'] == 1) {
                        $v['taskStatus'] = 1;
                        $v['statusTxt'] = '审核中';
                    } else if ($taskReceive['status'] == 2) {
                        $v['taskStatus'] = 2;
                        $v['statusTxt'] = '已通过';
                    } else if ($taskReceive['status'] == -2) {
                        $v['taskStatus'] = -2;
                        $v['statusTxt'] = '已驳回';
                    }



                }
                unset($v);
            }

            // 转换 OSS 链接为 HTTPS
            $data = $this->convertOssToHttps($data);
            $this->success('获取成功', $data);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 任务详情
     */
    public function getTaskDetails()
    {
        if ($this->request->isPost()) {
            //168
            $data = input();


            if (!isset($data['tid']) || !isset($data['type']) || empty($data['tid']) || empty($data['type'])) {
                $this->error('参数缺失');
            }
            $task = Db::table('WechatApp_task')
                ->where(array('id' => $data['tid'], 'isDel' => 0))
                ->find();
            if (empty($task)) {
                $this->error('任务不存在或者已结束');
            }
            
            // 检查任务是否已过期
            if (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00' && strtotime($task['deadline']) < time()) {
                $this->error('任务已结束，无法查看');
            }

            unset($task['updataTime'], $task['deleteTime'], $task['isDel'], $task['status'], $task['appId']);
            $task['steps'] = json_decode($task['steps'], 1);

            //步骤处理
            $steps = $task['steps'];
            $key = 0;
            if (!empty($steps)) {
                foreach ($steps as $k => &$v) {
                    foreach ($v as $kk => &$vv) {
                        $vv['key'] = $key;
                        $key++;
                        if (!empty($vv['data'])) {
                            // 确保 data 是字符串类型，如果是数组则先转换为字符串
                            if (is_array($vv['data'])) {
                                $keyword_list = implode("\n", $vv['data']);
                            } else {
                                $keyword_list = trim($vv['data']);
                            }
                            $keyword_arr = explode("\n", $keyword_list);
                            $vv['data'] = $keyword_arr;
                            $newData = [];
                            foreach ($vv['data'] as $kkk => $vvv) {
                                $newData[] = [
                                    'text' => $vvv,
                                    'value' => $vvv,
                                ];
                            }
                            $vv['data'] = $newData;
                            unset($newData);
                        }

                        //图片处理
                        if (!empty($vv['imgs'])) {
                            foreach ($vv['imgs'] as $kkk => &$vvv) {
                                // 确保 url 是字符串类型
                                if (isset($vvv['url']) && is_string($vvv['url']) && strpos($vvv['url'], 'http://') !== false) {
                                    $url = explode('http://', $vvv['url']);
                                    if (count($url) > 1) {
                                        $vvv['url'] = 'https://' . $url[1];
                                    }
                                }
                            }
                            unset($vvv);
                        }

                    }
                    unset($vv);
                }
                unset($v);
                $task['steps'] = $steps;
            }


            //重置任务数量
            if ($task['type'] == 2 || $task['type'] == 7) {
                if (!empty($task['dataPacketId'])) {
                    if (!empty($task['recoveryTask'])) {
                        $currentTime = date('Y-m-d H:i:s');
                        $itemNum = Db::table('WechatApp_dataPacketList')
                            ->where('dataPacketId', $task['dataPacketId'])
                            ->where(function ($query) use ($currentTime) {
                                $query->where('retrieveTime', '<=', $currentTime)
                                    ->whereOr('retrieveTime', '=', '0000-00-00 00:00:00');
                            })
                            ->whereIn('status', [0, 1])
                            ->order('id DESC')
                            ->count();
                    } else {
                        $itemNum = Db::table('WechatApp_dataPacketList')
                            ->where('dataPacketId', $task['dataPacketId'])
                            ->whereIn('status', [0])
                            ->order('id DESC')
                            ->count();
                    }

                    $itemNum2 = Db::table('WechatApp_dataPacketList')
                        ->where('dataPacketId', $task['dataPacketId'])
                        ->whereIn('status', [0, 1, 2])
                        ->count();
                    $task['sumTotal'] = $itemNum2;
                    $task['surplus'] = $itemNum;
                } else {
                    $itemNum = Db::table('WechatFriendRequestItem')
                        ->field('Id,PhoneOrWechatId')
                        ->where(array('WechatFriendRequestTaskId' => $task['taskId'], 'IsTaskReceive' => 0))
                        ->count();

                    $itemNum2 = Db::table('WechatFriendRequestItem')
                        ->field('Id,PhoneOrWechatId')
                        ->where(array('WechatFriendRequestTaskId' => $task['taskId']))
                        ->count();
                    $task['sumTotal'] = $itemNum2;
                    $task['surplus'] = $itemNum;
                }
            } else {
                $task['surplus'] = $task['sumTotal'] - $task['receiveNum'];
            }


            //已领取数据
            $where = [];
            $where['uid'] = $this->userInfo['id'];
            $where['taskId'] = $data['tid'];
            $where['isDel'] = 0;
            if (!empty($data['rid'])) {
                $where['id'] = $data['rid'];
            } else if (!empty($task['drawType'])) {
                $time = date('Y-m-d');
                if ($task['drawType'] == 1){
                    $where['createTime'] = ['between', [$time . ' 00:00:00', $time . ' 23:59:59']];
                }else{
                    $where['createTime'] = ['between', [$task['createTime'], $time . ' 23:59:59']];
                }
            }
            $taskReceive = Db::table('WechatApp_taskReceive')->where($where)->order('id DESC')->find();
            
            // 统计已领取数量时，包括审核中的任务（不排除）
            // 这样如果每天限制1次，无论任务处于什么状态，都不能再次领取
            $taskNum = Db::table('WechatApp_taskReceive')->where($where)->count();

            // 检查是否有未提交的任务（status == 0），如果有，不能领取新任务，应该显示提交页面
            $unsubmittedTask = Db::table('WechatApp_taskReceive')
                ->where($where)
                ->where('status', 0)
                ->order('id DESC')
                ->find();

            // 参考旧版逻辑：如果有指定的 rid，或者（有任务记录且达到领取限制），则已领取
                if(empty($data['rid'])){
                // 如果有未提交的任务，优先使用未提交的任务
                if (!empty($unsubmittedTask)) {
                    $taskReceive = $unsubmittedTask;
                } else {
                    $taskReceive = Db::table('WechatApp_taskReceive')->where($where)->whereIn('status', [0,1,-2])->order('id DESC')->find();
                }
            }

            // 如果有未提交的任务，必须显示提交页面，不能领取新任务
            if (!empty($unsubmittedTask)) {
                // 显示未提交的任务信息，设置所有必要字段
                $taskReceive = $unsubmittedTask;
                $task['isReceive'] = 1;
                $task['taskStatus'] = $taskReceive['status'];
                $task['taskCode'] = $taskReceive['taskCode'];
                $task['rid'] = $taskReceive['id'];
                $task['reason'] = $taskReceive['reason'];
                $retrieveTime = date('Y-m-d H:i:s', strtotime($taskReceive['createTime']) + $task['taskTime'] * 60);
                $task['retrieveTime'] = $retrieveTime;
                //数据填充
                $formData = json_decode($taskReceive['data'], true);
                $task['formData'] = $formData;
                
                $dataPacket = Db::table('WechatApp_dataPacket')
                    ->where('id', $task['dataPacketId'])
                    ->find();
                $task['dataPacket'] = $dataPacket;
                
                //额外数据处理
                if ($task['type'] == 2 || $task['type'] == 6 || $task['type'] == 7) {
                    $taskReceiveId = $taskReceive['taskReceiveId'] ?: $taskReceive['id'];
                    $item = Db::table('WechatApp_taskReceive')
                        ->field('id,taskData,status')
                        ->where(array('taskReceiveId' => $taskReceiveId))
                        ->select();
                    
                    foreach ($item as $k => &$v) {
                        $taskData = json_decode($v['taskData'], true);
                        if (!empty($dataPacket['type'])) {
                            $v['img_url'] = $taskData['val'];
                        }
                        if ($v['status'] == 0) {
                            $v['statusTxt'] = '待提交';
                        } else if ($v['status'] == 1) {
                            $v['statusTxt'] = '审核中';
                        } else if ($v['status'] == 2) {
                            $v['statusTxt'] = '已通过';
                        } else if ($v['status'] == -2) {
                            $v['statusTxt'] = '已驳回';
                        }
                        if ($task['type'] == 2 || $task['type'] == 7) {
                            if ($task['type'] == 2) {
                                $v['phoneOrWechatId2'] = $this->substrCut($taskData['val']);
                            } else {
                                $v['phoneOrWechatId2'] = '视频剪辑【' . ($k + 1) . '】';
                            }
                            $v['fid'] = $taskData['id'];
                            $v['phoneOrWechatId'] = $taskData['val'];
                        } else {
                            $v['fid'] = $taskData['id'];
                            $v['phoneOrWechatId'] = $v['taskData'];
                            $v['phoneOrWechatId2'] = $v['taskData'];
                        }
                        unset($v['taskData']);
                    }
                    unset($v);
                    $task['otherData'] = $item;
                }
                
                if (!empty($task['dataPacketId'])) {
                    if ($task['taskStatus'] == 0) {
                        $retrieveTime = strtotime($retrieveTime);
                        if ($retrieveTime < time()) {
                            $task['taskStatus'] = -3;
                        }
                    }
                }
            } elseif (!empty($taskReceive) && $taskReceive['status'] == 1 && !empty($task['drawType']) && ($task['drawType'] == 1 || $task['drawType'] == 2) && $taskNum < $task['drawNum']){
                // 可多次领取且最新任务是审核中，且还有领取次数，且没有未提交的任务，允许再次领取
                $task['isReceive'] = 0;
                $task['taskStatus'] = 0;
                $task['taskCode'] = '';
            } elseif (!empty($data['rid']) || (!empty($taskReceive) && (empty($task['drawType']) || ($task['drawType'] == 0) || ($task['drawType'] > 0 && $taskNum >= $task['drawNum'])))) {
                // 有指定的 rid 或达到领取限制，显示已领取
                // 如果有指定的 rid，需要重新查询对应的任务记录
                if (!empty($data['rid']) && (empty($taskReceive) || $taskReceive['id'] != $data['rid'])) {
                    $taskReceive = Db::table('WechatApp_taskReceive')->where(['id' => $data['rid']])->find();
                }
                
                if (!empty($taskReceive)) {
                    $task['isReceive'] = 1;
                    $task['taskStatus'] = $taskReceive['status'];
                    $task['taskCode'] = $taskReceive['taskCode'];
                    $task['rid'] = $taskReceive['id'];
                    $task['reason'] = $taskReceive['reason'];
                    $retrieveTime = date('Y-m-d H:i:s', strtotime($taskReceive['createTime']) + $task['taskTime'] * 60);
                    $task['retrieveTime'] = $retrieveTime;
                    //数据填充
                    $formData = json_decode($taskReceive['data'], true);
                    $task['formData'] = $formData;

                    $dataPacket = Db::table('WechatApp_dataPacket')
                        ->where('id', $task['dataPacketId'])
                        ->find();
                    $task['dataPacket'] = $dataPacket;


                    //额外数据处理
                    if ($task['type'] == 2 || $task['type'] == 6 || $task['type'] == 7) {
                        if (!empty($data['rid'])){
                            $taskReceiveId = $taskReceive['id'];
                        }else{
                            $taskReceiveId = $taskReceive['taskReceiveId'];
                        }

                        $item = Db::table('WechatApp_taskReceive')
                            ->field('id,taskData,status')
                            ->where(array('taskReceiveId' => $taskReceiveId))
                            ->select();


                        foreach ($item as $k => &$v) {
                            $taskData = json_decode($v['taskData'], true);
                            if (!empty($dataPacket['type'])) {
                                $v['img_url'] = $taskData['val'];
                            }

                            if ($v['status'] == 0) {
                                $v['statusTxt'] = '待提交';
                            } else if ($v['status'] == 1) {
                                $v['statusTxt'] = '审核中';
                            } else if ($v['status'] == 2) {
                                $v['statusTxt'] = '已通过';
                            } else if ($v['status'] == -2) {
                                $v['statusTxt'] = '已驳回';
                            }

                            if ($task['type'] == 2 || $task['type'] == 7) {
                                if ($task['type'] == 2) {
                                    $v['phoneOrWechatId2'] = $this->substrCut($taskData['val']);
                                } else {
                                    $v['phoneOrWechatId2'] = '视频剪辑【' . ($k + 1) . '】';
                                }
                                $v['fid'] = $taskData['id'];
                                $v['phoneOrWechatId'] = $taskData['val'];
                            } else {
                                $v['fid'] = $taskData['id'];
                                $v['phoneOrWechatId'] = $v['taskData'];
                                $v['phoneOrWechatId2'] = $v['taskData'];
                            }


                            unset($v['taskData']);
                        }
                        unset($v);
                        $task['otherData'] = $item;
                    }


                    if (!empty($task['dataPacketId'])) {
                        if ($task['taskStatus'] == 0) {
                            $retrieveTime = strtotime($retrieveTime);
                            if ($retrieveTime < time()) {
                                $task['taskStatus'] = -3;
                            }
                        }
                    }
                } else {
                    // 如果 taskReceive 为空，设置未领取
                    $task['isReceive'] = 0;
                    $task['taskStatus'] = 0;
                    $task['taskCode'] = '';
                }
            } else {
                // 既不是审核中，也没有达到领取限制，显示未领取
                $task['isReceive'] = 0;
                $task['taskStatus'] = 0;
                $task['taskCode'] = '';
            }

            // 转换 OSS 链接为 HTTPS
            $task = $this->convertOssToHttps($task);
            $this->success('获取成功', $task);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * *号
     * @param $userName
     * @return string
     */
    public function substrCut($userName)

    {
        $strlen = mb_strlen($userName, 'utf-8');
        $firstStr = mb_substr($userName, 0, 3, 'utf-8');
        $lastStr = mb_substr($userName, -3, 3, 'utf-8');
        $resultName = $firstStr . '****' . $lastStr;
        return $resultName;

    }

    /**
     * 任务列表
     */
    public function getTaskList()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $companyId = input('companyId', 0);
            $uid = $this->request->post('uid');
            if (!isset($data['type']) || empty($data['type'])) {
                $this->error('参数缺失');
            }
            $page = $data['page'];

            $where = [];
            $where['appid'] = $this->wechatApp['id'];
            $where['isDel'] = 0;
            $where['isShow'] = 1;
            $where['status'] = 1;
            $where['type'] = $data['type'];
            $where['companyId'] = $companyId;

            // 过滤已结束的任务：deadline 为空、为 '0000-00-00 00:00:00' 或大于当前时间
            $currentTime = date('Y-m-d H:i:s');
            $where[] = function($query) use ($currentTime) {
                $query->where('deadline', '>', $currentTime)
                    ->whereOr('deadline', '=', '0000-00-00 00:00:00')
                    ->whereOr('deadline', 'null');
            };

            $data = Db::table('WechatApp_task')
                ->field('id,name,type,pic,createTime,brief,reward,addReward,drawType,maxUserNum,drawNum,deadline')
                ->where($where)
                ->order('sort DESC,id DESC')
                ->page($page, 10)
                ->select();


            foreach ($data as $k => &$v) {
                if ($v['type'] == 2 && !empty($v['addReward']) && $v['addReward'] > 0) {
                    $reward = $v['addReward'] * $v['maxUserNum'] . '~' . $v['reward'] * $v['maxUserNum'];
                    $v['money'] = '+' . $reward;
                } else {
                    $v['money'] = '+' . $v['reward'];
                }
                if (!empty($uid)) {
                    $where = [];
                    $where['taskId'] = $v['id'];
                    $where['uid'] = $uid;
                    $where['isDel'] = 0;
                    if (!empty($v['drawType'])) {
                        $time = date('Y-m-d');
                        if ($v['drawType'] == 1){
                            $where['createTime'] = ['between', [$time . ' 00:00:00', $time . ' 23:59:59']];
                        }else{
                            $where['createTime'] = ['between', [$v['createTime'], $time . ' 23:59:59']];
                        }
                    }
                    $taskReceive = Db::table('WechatApp_taskReceive')
                        ->field('id,status')
                        ->where($where)
                        ->order('id DESC')
                        ->find();

                    // 统计已领取数量时，包括审核中的任务（不排除）
                    // 这样如果每天限制1次，无论任务处于什么状态，都不能再次领取
                    $taskNum = Db::table('WechatApp_taskReceive')
                        ->field('id,status')
                        ->where($where)
                        ->count();

                    // 如果最新任务是审核中，且还有领取次数，允许再次领取（显示为待领取）
                    if (!empty($taskReceive) && $taskReceive['status'] == 1 && !empty($v['drawType']) && ($v['drawType'] == 1 || $v['drawType'] == 2) && $taskNum < $v['drawNum']) {
                        $v['taskStatus'] = -1;
                        $v['statusTxt'] = '待领取';
                    } else if (!empty($v['drawType']) && $taskNum < $v['drawNum']) {
                        $taskReceive['status'] = -1;
                    }

                    if (empty($taskReceive)) {
                        $v['taskStatus'] = -1;
                        $v['statusTxt'] = '待领取';
                    } else if ($taskReceive['status'] == 0) {
                        $v['taskStatus'] = 0;
                        $v['statusTxt'] = '待提交';
                    } else if ($taskReceive['status'] == 1) {
                        $v['taskStatus'] = 1;
                        $v['statusTxt'] = '审核中';
                    } else if ($taskReceive['status'] == 2) {
                        $v['taskStatus'] = 2;
                        $v['statusTxt'] = '已通过';
                    } else if ($taskReceive['status'] == -2) {
                        $v['taskStatus'] = -2;
                        $v['statusTxt'] = '已驳回';
                    }
                }

            }
            unset($v);

            // 转换 OSS 链接为 HTTPS
            $data = $this->convertOssToHttps($data);
            $this->success('获取成功', $data);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 我的任务列表
     */
    public function getMyTaskList()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $uid = $this->request->post('uid');
            if (!isset($uid) || empty($uid)) {
                $this->error('参数缺失');
            }
            $page = $data['page'];

            $data = Db::table('WechatApp_taskReceive')->alias('myTask')
                ->join('WechatApp_task task', 'task.id = myTask.taskId')
                ->field('task.id,task.name,task.type,task.pic,task.createTime,task.brief,task.reward,myTask.status as taskStatus,myTask.createTime,myTask.id as rid')
                ->where(array('task.appid' => $this->wechatApp['id'], 'task.isDel' => 0, 'myTask.uid' => $uid, 'myTask.taskReceiveId' => 0, 'myTask.isDel' => 0))
                ->order('myTask.createTime DESC,myTask.id DESC')
                ->page($page, 10)
                ->select();
            if (!empty($uid)) {
                foreach ($data as $k => &$v) {
                    if ($v['taskStatus'] == 0) {
                        $v['statusTxt'] = '待提交';
                    } else if ($v['taskStatus'] == 1) {
                        $v['statusTxt'] = '审核中';
                    } else if ($v['taskStatus'] == 2) {
                        $v['statusTxt'] = '已通过';
                    } else if ($v['taskStatus'] == -2) {
                        $v['statusTxt'] = '已驳回';
                    }
                }
                unset($v);
            }
            // 转换 OSS 链接为 HTTPS
            $data = $this->convertOssToHttps($data);
            $this->success('获取成功', $data);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 领取任务
     */
    public function receiveTask()
    {
        if ($this->request->isPost()) {
            $receiveNum = 1;
            $data = $this->request->post();
            $test = $this->request->post('test');
            $task = Db::table('WechatApp_task')
                ->where(['id' => $data['rid'], 'isDel' => 0])
                ->find();
            if (empty($task)) {
                $this->error('任务不存在或者已结束');
            }
            
            // 检查任务是否已过期
            if (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00' && strtotime($task['deadline']) < time()) {
                $this->error('任务已结束，无法领取');
            }
            
            //验证用户
            $user = $this->checkingUser($data['rid'], $this->userInfo['id']);
            $user = json_decode($user, true);
            if ($user['code'] == 1) {
                unset($this->userInfo);
                $this->userInfo = $user['data'];
            }


            $where = [];
            $where['uid'] = $this->userInfo['id'];
            $where['taskId'] = $task['id'];
            $where['isDel'] = 0;
            if (!empty($task['drawType'])) {
                $time = date('Y-m-d');
                if ($task['drawType'] == 1){
                    $where['createTime'] = ['between', [$time . ' 00:00:00', $time . ' 23:59:59']];
                }else{
                    $where['createTime'] = ['between', [$task['createTime'], $time . ' 23:59:59']];
                }
            }
            
            // 统计已领取数量时，包括审核中的任务（不排除）
            // 这样如果每天限制1次，无论任务处于什么状态，都不能再次领取
            $taskReceive = Db::table('WechatApp_taskReceive')
                ->where($where)
                ->count();

            // 检查是否有未提交的任务（status == 0），如果有，不能领取新任务
            $unsubmittedTask = Db::table('WechatApp_taskReceive')
                ->where($where)
                ->where('status', 0)
                ->order('id DESC')
                ->find();

            if (!empty($unsubmittedTask)) {
                // 有未提交的任务，不能领取新任务
                $this->error('您有未提交的任务，请先提交后再领取');
            }

            if (!empty($taskReceive)) {
                if ($task['drawType'] == 0){
                    // 单次任务：只要领取过（不管状态），就不能再领取
                    $this->error('您已经领取过该任务了');
                } elseif (($task['drawType'] == 1 || $task['drawType'] == 2) && $taskReceive >= $task['drawNum']){
                    // 多次任务：已领取数量（包括审核中的）达到限制，不能再领取
                    $this->error('您已达到领取限制');
                }
            }

            if ($task['type'] == 2) {
                if (!empty($task['dataPacketId'])) {
                    $currentTime = date('Y-m-d H:i:s');
                    if (!empty($task['recoveryTask'])) {
                        $itemNum = Db::table('WechatApp_dataPacketList')
                            ->where('dataPacketId', $task['dataPacketId'])
                            ->where(function ($query) use ($currentTime) {
                                $query->where('retrieveTime', '<=', $currentTime)
                                    ->whereOr('retrieveTime', '=', '0000-00-00 00:00:00');
                            })
                            ->whereIn('status', [0, 1])
                            ->order('id DESC')
                            ->count();
                    } else {
                        $itemNum = Db::table('WechatApp_dataPacketList')
                            ->where('dataPacketId', $task['dataPacketId'])
                            ->whereIn('status', [0])
                            ->order('id DESC')
                            ->count();
                    }


                    if ($itemNum < $task['maxUserNum']) {
                        $this->error('该任务数量不足，请联系管理员补充');
                    }
                } else {
                    $itemNum = Db::table('WechatFriendRequestItem')
                        ->field('Id,PhoneOrWechatId')
                        ->where(array('WechatFriendRequestTaskId' => $task['taskId'], 'IsTaskReceive' => 0))
                        ->count();
                    if ($itemNum < $task['maxUserNum']) {
                        $this->error('该任务数量不足，请联系管理员补充');
                    }
                }

            }


            $taskCode = 'NO' . date('YmdHis');
            $sql_data = [
                'projectId' => $task['projectId'],
                'taskId' => $task['id'],
                'uid' => $this->userInfo['id'],
                'taskData' => json_encode($task, 256),
                'createTime' => date('Y-m-d H:i:s'),
                'taskCode' => $taskCode,
            ];
            $res = Db::table('WechatApp_taskReceive')->insertGetId($sql_data);
            if (!empty($res)) {
                //添加好友时创建子任务
                if ($task['type'] == 2 || $task['type'] == 7) {
                    $receiveNum = $task['maxUserNum'];
                    if (!empty($task['dataPacketId'])) {

                        if (!empty($task['recoveryTask'])) {
                            $currentTime = date('Y-m-d H:i:s');
                            $dataPacketList = Db::table('WechatApp_dataPacketList')
                                ->where('dataPacketId', $task['dataPacketId'])
                                ->where(function ($query) use ($currentTime) {
                                    $query->where('retrieveTime', '<=', $currentTime)
                                        ->whereOr('retrieveTime', '=', '0000-00-00 00:00:00');
                                })
                                ->whereIn('status', [0, 1])
                                ->order('id DESC')
                                ->limit($task['maxUserNum'])
                                ->select();
                        } else {
                            $dataPacketList = Db::table('WechatApp_dataPacketList')
                                ->where('dataPacketId', $task['dataPacketId'])
                                ->whereIn('status', [0])
                                ->order('id DESC')
                                ->limit($task['maxUserNum'])
                                ->select();
                        }

                        foreach ($dataPacketList as $k => $v) {
                            $retrieveTime = $task['taskTime'] * 60 + time();
                            $retrieveTime = date('Y-m-d H:i:s', $retrieveTime);
                            $v['fid'] = $this->userInfo['id'];
                            $v['collectionTime'] = date('Y-m-d H:i:s');
                            $v['retrieveTime'] = $retrieveTime;
                            $sql_data2 = [
                                'projectId' => $task['projectId'],
                                'taskId' => $task['id'],
                                'uid' => $this->userInfo['id'],
                                'taskData' => json_encode($v, 256),
                                'createTime' => date('Y-m-d H:i:s'),
                                'taskReceiveId' => $res,
                                'taskCode' => $taskCode . '-' . ($k + 1),
                            ];
                            $res2 = Db::table('WechatApp_taskReceive')->insertGetId($sql_data2);
                            if (!empty($res2)) {
                                $sql_data3 = [
                                    'status' => 1,
                                    'fid' => $this->userInfo['id'],
                                    'collectionTime' => date('Y-m-d H:i:s'),
                                    'retrieveTime' => $retrieveTime,
                                ];
                                Db::table('WechatApp_dataPacketList')->where(['id' => $v['id']])->update($sql_data3);
                            }
                        }
                    } else {
                        //此代码已在2023-03-20废除
                        $item = Db::table('WechatFriendRequestItem')
                            ->field('Id,PhoneOrWechatId')
                            ->where(array('WechatFriendRequestTaskId' => $task['taskId'], 'IsTaskReceive' => 0))
                            ->order('Id DESC')
                            ->limit($task['maxUserNum'])
                            ->select();
                        foreach ($item as $k => $v) {
                            $sql_data2 = [
                                'projectId' => $task['projectId'],
                                'taskId' => $task['id'],
                                'uid' => $this->userInfo['id'],
                                'taskData' => json_encode($v, 256),
                                'createTime' => date('Y-m-d H:i:s'),
                                'taskReceiveId' => $res,
                                'taskCode' => $taskCode . '-' . ($k + 1),
                            ];
                            $res2 = Db::table('WechatApp_taskReceive')->insertGetId($sql_data2);
                            if (!empty($res2)) {
                                $sql_data3 = [
                                    'IsTaskReceive' => 1,
                                    'TaskReceiveId' => $res2,
                                ];
                                Db::table('WechatFriendRequestItem')->where(['Id' => $v['Id']])->update($sql_data3);
                            }
                        }
                    }
                }
                //企微添加、视频剪辑子任务
                if ($task['type'] == 6) {
                    $receiveNum = $task['maxUserNum'];
                    for ($i = 1; $i <= $receiveNum; $i++) {
                        if ($task['type'] == 6) {
                            $taskDataName = '企微添加任务【' . $i . '】';
                        }
                        $sql_data2 = [
                            'projectId' => $task['projectId'],
                            'taskId' => $task['id'],
                            'uid' => $this->userInfo['id'],
                            'taskData' => $taskDataName,
                            'createTime' => date('Y-m-d H:i:s'),
                            'taskReceiveId' => $res,
                            'taskCode' => $taskCode . '-' . $i,
                        ];
                        $res2 = Db::table('WechatApp_taskReceive')->insertGetId($sql_data2);
                    }
                }


                //更新任务领取量
                Db::table('WechatApp_task')->where(['id' => $task['id'], 'isDel' => 0])->setInc('receiveNum', $receiveNum);

                $this->userInfo['rid'] = $res;
                // 转换 OSS 链接为 HTTPS
                $this->userInfo = $this->convertOssToHttps($this->userInfo);
                $this->success('任务领取成功', $this->userInfo);

            } else {
                $this->error('网络繁忙，请稍后再试~');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 根据任务判断当前用户是否在发布任务的公司注册会员
     * @param null $taskId
     * @param null $uid
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws PDOException
     */
    public function checkingUser($taskId = null, $uid = null)
    {

        $taskData = Db::table('WechatApp_task')->alias('task')
            ->field('task.*,account.CompanyId as companyId')
            ->join('CompanyAccount account', 'task.uid = account.Id')
            ->where(array('task.id' => $taskId))
            ->find();
        if (empty($taskData)) {
            $taskData = Db::table('WechatApp_task')
                ->where(array('id' => $taskId))
                ->find();
            $taskData['companyId'] = 0;
        }

        //查询出当前用户信息
        $user1 = Db::table('WechatApp_fans')
            ->field('appid,openid,nickName,mobile,gender,avatarUrl,password,birthday,companyId')
            ->where(array('id' => $uid))
            ->find();


        //根据当前用户查询是否有关联发布任务的公司
        $user2 = Db::table('WechatApp_fans')
            ->where(array('openid' => $user1['openid'], 'companyId' => $taskData['companyId']))
            ->find();

        if (empty($user2)) {
            $sql_data = $user1;
            $sql_data['companyId'] = $taskData['companyId'];
            $sql_data['invitationCode'] = createNoncestr(10);
            $sql_data['createTime'] = date('Y-m-d H:i:s');
            $sql_data['updataTime'] = date('Y-m-d H:i:s');
            $res = Db::table('WechatApp_fans')->insertGetId($sql_data);
            if (!empty($res)) {
                $user3 = Db::table('WechatApp_fans')
                    ->where(array('id' => $res))
                    ->find();
                $returnData = [
                    'mgs' => '新用户',
                    'code' => 1,
                    'data' => $user3
                ];
                return json_encode($returnData, 256);
            } else {
                $returnData = [
                    'mgs' => '用户新增失败',
                    'code' => 0,
                    'data' => ''
                ];
                return json_encode($returnData, 256);
            }
        } else {
            $returnData = [
                'mgs' => '老用户',
                'code' => 1,
                'data' => $user2
            ];
            return json_encode($returnData, 256);
        }
    }

    /**
     * 提交任务
     */
    public function submitTask()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $rid = $this->request->post('rid');
            $tid = $this->request->post('tid');
            
            // 接收 data 参数：前端直接发送对象，后端会自动解析
            $formData = isset($data['data']) ? $data['data'] : [];
            
            // 确保是数组格式
            if (!is_array($formData)) {
                $this->error('数据格式错误：期望对象格式');
            }

            $task = Db::table('WechatApp_task')
                ->where(['id' => $tid, 'isDel' => 0])
                ->find();
            if (empty($task)) {
                $this->error('该任务不存在或者已下线');
            }

            // ===== 表单必填校验（参考 WechatApp_task.steps 配置） =====
            // 规则：对于 steps 中需要用户"填写/上传"的项（通常 isUpdate = 1），
            //       用户提交的数据中必须在对应位置提供文本或图片至少一种，不允许全空。
            if (empty($formData)) {
                $this->error('请先完善任务内容后再提交');
            }

            $steps = [];
            if (!empty($task['steps'])) {
                $steps = json_decode($task['steps'], true);
            }

            // 只有当 steps 结构存在时，才按 steps 精确校验；否则退回到"整体至少有内容"的简单校验
            if (is_array($steps) && !empty($steps)) {
                $isValid = true;

                foreach ($steps as $gIndex => $stepGroup) {
                    if (!is_array($stepGroup)) {
                        continue;
                    }
                    foreach ($stepGroup as $iIndex => $stepItem) {
                        if (!is_array($stepItem)) {
                            continue;
                        }

                        // 只校验需要用户上传/编辑的项（约定 isUpdate = 1）
                        $needUserInput = isset($stepItem['isUpdate']) && intval($stepItem['isUpdate']) === 1;
                        $type = isset($stepItem['type']) ? intval($stepItem['type']) : 0;

                        // type 0 为文字介绍，无需用户填写，直接跳过
                        if (!$needUserInput || $type === 0) {
                            continue;
                        }

                        // 使用 key 从 formData 对象中获取数据
                        $key = isset($stepItem['key']) ? $stepItem['key'] : null;
                        if ($key === null) {
                            continue;
                        }
                        
                        $userVal = array_key_exists($key, $formData) ? $formData[$key] : null;

                        // 统一的“是否有值”判断：字符串非空，或数组中存在非空项
                        $hasValue = false;
                        if (is_array($userVal)) {
                            foreach ($userVal as $v) {
                                if ($v !== '' && $v !== null) {
                                    $hasValue = true;
                                    break;
                                }
                            }
                        } else {
                            if (trim((string)$userVal) !== '') {
                                $hasValue = true;
                            }
                        }

                        $errorTitle = isset($stepItem['title']) ? $stepItem['title'] : '';

                        // 只验证 type 7 多维表单的 fields 必填字段，其他类型不验证
                        if ($type === 7) {
                            // 多维表单：userVal 应该是 JSON 字符串
                            // type 7 只验证 fields 里面的必填字段，不检查是否有数据或最小行数
                            $multiFormData = [];
                            if (is_string($userVal)) {
                                $multiFormData = json_decode($userVal, true);
                            } elseif (is_array($userVal)) {
                                $multiFormData = $userVal;
                            }
                            
                            // 只验证 fields 中的必填字段
                            if (isset($stepItem['fields']) && is_array($stepItem['fields']) && is_array($multiFormData)) {
                                foreach ($multiFormData as $rowIndex => $row) {
                                    if (!is_array($row)) {
                                        continue; // 跳过无效行，不强制要求
                                    }
                                    foreach ($stepItem['fields'] as $field) {
                                        // 处理 required 字段：可能是字符串 "true"/"false" 或布尔值
                                        $isRequired = false;
                                        if (isset($field['required'])) {
                                            if ($field['required'] === true || $field['required'] === 'true' || $field['required'] === '1' || $field['required'] === 1) {
                                                $isRequired = true;
                                            }
                                        }
                                        
                                        if ($isRequired) {
                                            $fieldName = isset($field['fieldName']) ? $field['fieldName'] : '';
                                            if (empty($row[$fieldName])) {
                                                $isValid = false;
                                                $this->error('请完成任务步骤：' . $errorTitle . '（第' . ($rowIndex + 1) . '行的"' . $fieldName . '"为必填项）');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$isValid) {
                    $this->error('请完整填写并上传所有任务内容后再提交');
                }
            } else {
                // 无 steps 配置时，兜底校验：整体至少有一项非空文本或图片
                $hasNonEmpty = false;
                array_walk_recursive($formData, function ($value, $key) use (&$hasNonEmpty) {
                    if ($hasNonEmpty) {
                        return;
                    }
                    if ($key === 'url') {
                        if (!empty($value)) {
                            $hasNonEmpty = true;
                        }
                    } else {
                        if ($value !== '' && $value !== null) {
                            $hasNonEmpty = true;
                        }
                    }
                });

                if (!$hasNonEmpty) {
                    $this->error('请先填写或上传任务内容后再提交');
                }
            }
        
           
            // 计算实际收益（针对多维表单 type 7）
            $actualReward = floatval($task['reward']); // 默认收益为任务设置的单价
            $dataCount = 1; // 默认数据条数为1
            
            // 遍历 steps，查找 type 7 的多维表单，计算数据条数
            if (is_array($steps) && !empty($steps)) {
                foreach ($steps as $gIndex => $stepGroup) {
                    if (!is_array($stepGroup)) {
                        continue;
                    }
                    foreach ($stepGroup as $iIndex => $stepItem) {
                        if (!is_array($stepItem)) {
                            continue;
                        }
                        
                        $type = isset($stepItem['type']) ? intval($stepItem['type']) : 0;
                        $needUserInput = isset($stepItem['isUpdate']) && intval($stepItem['isUpdate']) === 1;
                        
                        // 只处理 type 7 且需要用户输入的多维表单
                        if ($type === 7 && $needUserInput) {
                            $key = isset($stepItem['key']) ? $stepItem['key'] : null;
                            if ($key !== null && array_key_exists($key, $formData)) {
                                $userVal = $formData[$key];
                                
                                // 解析多维表单数据
                                $multiFormData = [];
                                if (is_string($userVal)) {
                                    $multiFormData = json_decode($userVal, true);
                                } elseif (is_array($userVal)) {
                                    $multiFormData = $userVal;
                                }
                                
                                // 计算有效数据条数（过滤空行）
                                if (is_array($multiFormData) && !empty($multiFormData)) {
                                    $validCount = 0;
                                    foreach ($multiFormData as $row) {
                                        if (is_array($row)) {
                                            // 检查行中是否有非空值
                                            $hasValue = false;
                                            foreach ($row as $fieldValue) {
                                                if ($fieldValue !== '' && $fieldValue !== null) {
                                                    $hasValue = true;
                                                    break;
                                                }
                                            }
                                            if ($hasValue) {
                                                $validCount++;
                                            }
                                        }
                                    }
                                    if ($validCount > 0) {
                                        $dataCount = $validCount;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // 计算实际收益 = 单价 × 数据条数
            $actualReward = floatval($task['reward']) * $dataCount;
            
            // 通过校验后再编码入库
            $formData = json_encode($formData, 256);

            $taskReceive = Db::table('WechatApp_taskReceive')
                ->where(['uid' => $this->userInfo['id'], 'taskId' => $task['id'], 'id' => $rid])
                ->find();


            if (empty($taskReceive)) {
                $this->error('您还未领取该任务,请先领取后在提交~');
            }

            $sql_data = [
                'data' => $formData,
                'status' => 1,
                'submitTime' => date('Y-m-d H:i:s'),
                'actualReward' => $actualReward, // 保存实际收益
                'dataCount' => $dataCount, // 保存数据条数
            ];
            $res = Db::table('WechatApp_taskReceive')->where(['id' => $taskReceive['id']])->update($sql_data);
            if (!empty($res)) {
                $this->success('任务提交成功,等待平台审核');
            } else {
                $this->error('任务提交失败,请稍后在试~');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 文件上传
     * @return Json
     * @throws OssException
     */
    public function uploadFile()
    {
        if ($this->request->isPost()) {
            // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录https://ram.console.aliyun.com创建RAM账号。
            $accessKeyId = $this->oss['accessKeyId'];
            $accessKeySecret = $this->oss['accessKeySecret'];
            // Endpoint以杭州为例，其它Region请按实际情况填写。
            $endpoint = $this->oss['region'];
            $bucket = $this->oss['bucket'];
            //对应阿里云中的目录，以年月日（例如：20210603）为目录名称
            $object = "prt/";
            /**
             *基本逻辑：首先将文件上传到自己的服务器中，接着将文件上传到阿里云OSS中，接着删除自己服务器中的文件
             */
            $file = request()->file('file');
            if ($file) {
                //本地的目录构建
                $filePaths = ROOT_PATH . 'public/uploads/';
                if (!file_exists($filePaths)) {
                    mkdir($filePaths, 0777, true);
                }
                $info = $file->move($filePaths);

                if ($info) {
                    $imgPath = ROOT_PATH . 'public/uploads/' . $info->getSaveName();
                    //文件上传成功到服务器后再将文件上传到阿里云oss
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $re = $ossClient->uploadFile($bucket, $object . $info->getSaveName(), $imgPath);
                    if ($re["info"]["http_code"] == 200) {
                    //上传阿里云成功之后删除自己服务器中
                    $local_file = $imgPath;
                    if (file_exists($local_file)) {
                        unlink($local_file);
                    }
                    // 转换 OSS 链接为 HTTPS
                    $url = $this->convertOssToHttps($re["info"]['url']);
                    $this->success('文件上传成功', $url);
//                        return json(array('msg' => '文件上传成功!', 'data' => $re["info"]['url'], 'code' => 1));
                    }
                } else {
                    $this->error('上传的文件非法！');
//                    return json(array('msg' => '上传的文件非法!', 'code' => 0));
                }
            }
        } else {
            $this->error('非法访问!');
            return json(array('msg' => '非法访问!', 'code' => 0));
        }
    }

    /**
     * 添加用户奖励
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function addUserReward()
    {
        if ($this->request->isPost()) {
            $rid = $this->request->post('rid', '');
            $uid = $this->request->post('uid', '');
            if (empty($rid) || empty($uid)) {
                $this->error('参数缺失');
            }
            $taskReceive = Db::table('WechatApp_taskReceive')
                ->where(['id' => $rid, 'uid' => $uid])
                ->find();

            $taskData = json_decode($taskReceive['taskData'], true);
            $task = Db::table('WechatApp_task')->where('id', $taskReceive['taskId'])->find();
            $taskData['addReward'] = $task['addReward'];
            //设置添加奖励金
            if (isset($taskData['addReward']) && !empty($taskData['addReward']) && $taskData['addReward'] > 0) {
                if (empty($taskReceive)) {
                    $this->error('该任务不存在');
                }
                $bill = Db::table('WechatApp_bill')
                    ->where(['type' => 3, 'sourceId' => $taskReceive['id'], 'uid' => $uid])
                    ->find();

                if (!empty($bill)) {
                    $this->error('已领取过该添加奖励');
                }
                $fans = Db::table('WechatApp_fans')
                    ->where(['id' => $uid])
                    ->find();
                //奖励金额
                $money = $taskData['addReward'];
                //拼接账单说明
                if (!empty($taskData['val'])) {
                    $phone = '【' . $this->substrCut($taskData['val']) . '】';
                } else {
                    $phone = '';
                }
                $explain = '添加用户' . $phone . '收入';
                //更新用户总收益
                Db::table('WechatApp_fans')->where(array('id' => $fans['id']))->setInc('totalIncome', $money);
                //更新用户余额
                $res = Db::table('WechatApp_fans')->where(array('id' => $fans['id']))->setInc('balance', $money);
                if ($res) {
                    $sql_data = [
                        'projectId' => $taskReceive['projectId'],
                        'type' => 3,
                        'uid' => $taskReceive['uid'],
                        'money' => $money,
                        'balance' => $fans['balance'] + $money,
                        'sourceId' => $taskReceive['id'],
                        'explain' => $explain,
                        'createTime' => date('Y-m-d H:i:s'),
                    ];
                    Db::table('WechatApp_bill')->insertGetId($sql_data);
                    $this->success('成功');
                }
            } else {
                $this->error('未设置添加奖励');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 获取任务类型
     * @return array|false|string
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getTaskType()
    {
        $list = Db::table('WechatApp_taskType')->select();
        foreach ($list as $key => &$val) {
            $val['defaultSteps'] = json_decode($val['defaultSteps'], true);
        }
        unset($val);
        // 转换 OSS 链接为 HTTPS
        $list = $this->convertOssToHttps($list);
        $this->success('获取成功', $list);
    }


    /**
     * 获取链接二维码
     * @return array|false|string
     */
    public function getUrlQrCode()
    {
        if ($this->request->isPost()) {
            $taskid = input('taskid');

            if (!empty($taskid)) {
                $id = input('id');
                $appid = input('appid');
                $uid = input('uid');
                $companyId = input('companyId');
                $task = Db::table('WechatApp_task')->field('id,dataPacketId,companyId,name,addReward')->where(['id' => $taskid, 'isDel' => 0])->find();
                if ($task['addReward']) {
                    $res = $this->scanCodeReward($task, $id, $uid);
                }
            }


            $html_url = input('html_url');
            //去除非链接的数据
            if (strpos($html_url, 'http://') !== false || strpos($html_url, 'https://') !== false) {
                //判断链接是否为图片
                $is_image = is_image($html_url);
                if (!empty($is_image)) {
                    $img_url = $html_url;
                } else {
                    $img_url = getQRcode64($html_url);
                }
            } else {
                $img_url = '';
            }
            // 转换 OSS 链接为 HTTPS
            $img_url = $this->convertOssToHttps($img_url);
            $this->success('成功', $img_url);
        } else {
            $this->error('非法访问');
        }
    }


    public function scanCodeReward($task = [], $id = null, $uid = null)
    {
        if (empty($task) || empty($id) || empty($uid)) {
            return errorJson('参数缺失');
        }
        Db::startTrans();
        try {
            $bill = Db::table('WechatApp_bill')
                ->where(['type' => 3, 'uid' => $uid, 'sourceId' => $id])
                ->find();
            if (!empty($bill)) {
                return errorJson('该任务已经领取过奖励了');
            }
            $fasn = Db::table('WechatApp_fans')->where(['id' => $uid])->find();
            if (!empty($fasn)) {
                $res = Db::table('WechatApp_fans')->where(['id' => $uid])->setInc('balance', 1);
                if (!empty($res)) {
                    $sql_data = [
                        'type' => 3,
                        'uid' => $uid,
                        'money' => $task['addReward'],
                        'balance' => $fasn['balance'] + $task['addReward'],
                        'sourceId' => $id,
                        'explain' => '【' . $task['name'] . '】活动额外奖励【' . $task['addReward'] . '】元',
                        'createTime' => date('Y-m-d H:i:s'),
                        'companyId' => $task['companyId'],
                        'taskId' => $task['id'],
                    ];
                    $res = Db::table('WechatApp_bill')->insertGetId($sql_data);
                    if ($res) {
                        Db::commit();
                        return successJson('成功');
                    } else {
                        Db::rollback();
                        return errorJson('失败');
                    }
                }

            }
        } catch (\Exception $e) {
            Db::rollback();
            return errorJson('失败：' . $e->getMessage());
        }
    }

}
