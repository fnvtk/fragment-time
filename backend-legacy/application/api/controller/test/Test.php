<?php


namespace app\api\controller\test;

use app\api\controller\Video;
use app\common\controller\Api;
use Exception;
use think\cache\driver\Redis;
use think\Db;
use think\Log;

class Test extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $domain2 = 'https://test1.quwanzhi.com';
    protected $domain = 'https://krs.quwanzhi.com';



    public function kuClass(){
        $data = Db::table('WechatMomentInfoClass')->where(['isDelete' => 0,'userId' => 59])->select();
        foreach ($data as  $k => $v){
            $data2 = Db::table('WechatMomentInfo')
                ->where(['classId' => $v['id'], 'isDelete' => 0])
                ->select();
            foreach ($data2 as  $kk => $vv){
                $imgs = json_decode($vv['httpUrls'],true);
                if (is_array($imgs)) {
                    $id = '';
                    $idss = [];
                    $img_ids = [];
                    $video_ids = [];
                    foreach ($imgs as $kkk => $vvv) {
                        if (!empty($vvv)) {
                        $img_data = [
                            'sName' => $this->getName($vvv),
                            'sObjectName' => 'business/material',
                            'sFilePath' => $vvv,
                            'dNewTime' => datetime(time()),
                            'NewUserId' => $v['userId'],
                            'sCdnUrl' => $vvv,
                        ];
                        $res = Db::table('SysAttach_copy1')->insertGetId($img_data);
                        if ($res) {
                            if (empty($id)) {
                                $id = $res;
                            }

                            if(strpos($vvv,'mp.weixin.qq.com') !== false){
                                if (is_image($vvv)) {
                                    $img_ids[] = $res;
                                } else {
                                    $video_ids[] = $res;
                                }

                            }else{
                                $idss[] = $res;
                            }

                        }
                    }
                }
                }
                if (!empty($img_ids)){
                    $TypeId = 2;
                    $VideoId = '';
                    $sThumb = $id;
                    $SourceId = 'moment';
                    $sPic = implode(',',$img_ids);
                }else{
                    $TypeId = 3;
                    $VideoId = implode(',',$video_ids);
                    $sThumb = '';
                    $SourceId = 'manual';
                    $sPic = '';
                }
                if ($TypeId != 28){
                    $sql_data = [
                        'sName' => $v['name'],
                        'dNewTime' => $vv['createTime'],
                        'dEditTime' => $vv['updateTime'],
                        'dCollectTime' => datetime(time()),
                        'TypeId' => $TypeId,
                        'SourceId' => 'manual',
                        'sContent' => $vv['content'],
                        'sThumb' => $sThumb,
                        'sPic' => $sPic,
                        'VideoId' => $VideoId,
                        'QueueId' => '0',
                        'bClassify' => 1,
                        'bManual' => 0,
                    ];
                    Db::table('Material_copy1')->insertGetId($sql_data);
                }

            }

        }
        exit_data(234);
    }

    public function getName($name = ''){
        $name = explode('/',$name);
        return end($name);
    }



    //获取指定微信号朋友圈内容
    public function test()
    {
        exit(21);
        set_time_limit(0);
        Db::startTrans();
        try {
            $username = $this->request->post('username', 'karuo');
            $friendId = $this->request->post('friendId', '18714866');
            $processId = $this->request->post('processId', '');
            $count = $this->request->post('count', 10);
            if (empty($username)) {
                $list = Db::table('CompanyAccount')
                    ->whereNotNull('PasswordAuth')
                    ->select();
            } else {
                $list = Db::table('CompanyAccount')
                    ->where(['Username' => $username])
                    ->whereNotNull('PasswordAuth')
                    ->select();
            }
            if (!empty($processId)) {
                $class = Db::table('WechatMomentInfoClass')->where(['processId' => $processId])->find();
                if (empty($class)) {
                    $classId = 0;
                } else {
                    $classId = $class['id'];
                }
            } else {
                $classId = 0;
            }
//            Log::write('采集账号列表：' . json_encode($list, 256));
            $urlToken = $this->domain2 . '/api/kr/Token/getToken';
            $urlMoment = $this->domain . '/api/ws/communication/getMoments';
            $urlMomentUrl = $this->domain . '/api/ws/communication/getMomentSourceRealUrl';
            $urlSearchWechat = $this->domain . '/api/wechat/friend/searchWechat';


            foreach ($list as $k => $v) {
                $where['IsDeleted'] = 0;
                $where['IsPassed'] = 1;
                if (empty($friendId) && empty($username)) {
                    $where['IsCollectionMoment'] = 1;
                    $where['AccountUserName'] = $v['Username'];
                } else {
                    $where['Id'] = $friendId;
                }
                Db::table('Kefure_WechatFriend')
                    ->where($where)
                    ->update(['hasCollection' => 0]);
                $friends = Db::table('Kefure_WechatFriend')
                    ->where($where)
                    ->select();


                if (count($friends) > 0) {

                    foreach ($friends as $key => $value) {
                        //默认登录信息
                        $loginParams = [
                            'username' => $v['Username'],
                            'password' => authcode($v['PasswordAuth'])
                        ];
                        //验证好友归属
                        $userParams = [
                            'friendKeyword' => $value['WechatId'],
                            'wechatAccountKeyword' => $value['OwnerWechatId']
                        ];
                        $result = requestCurl($urlSearchWechat, $userParams, 'POST');
                        $result = json_decode($result, true);

                        if ($result['code'] == 200) {
                            $resultUserData = isset($result['data'][0]) ? $result['data'][0] : '';
                            //账号防止迁移处理
                            if (!empty($resultUserData) && !empty($resultUserData['accountUserName']) && $resultUserData['accountUserName'] != $v['Username']) {
                                $account = Db::table('CompanyAccount')
                                    ->where(['Username' => $resultUserData['accountUserName']])
                                    ->whereNotNull('PasswordAuth')
                                    ->find();
                                $loginParams = [
                                    'username' => $account['Username'],
                                    'password' => authcode($account['PasswordAuth'])
                                ];
                            }
                        }

                        //获取当前用户token
                        $resultToken = requestCurl($urlToken, $loginParams, 'POST');
                        $resultToken = json_decode($resultToken, 1);
                        if ($resultToken['code'] == 200) {
                            $header = [
                                'authorized:' . $resultToken['data']['access_token']
                            ];
                            $params = [
                                'count' => $count,
                                'accountId' => $resultToken['data']['accountId'],
                                'wechatAccountId' => $value['WechatAccountId'],
                                'wechatFriendId' => $value['Id']
                            ];

                            $result = requestCurl($urlMoment, $params, 'POST', $header);
                            //保存采集记录
                            Db::table('WechatMomentLog')->insert([
                                'content' => $result,
                                'params' => json_encode($params, 256),
                                'createTime' => datetime(time())
                            ]);
                            $result = json_decode($result, 1);

//                            exit_data($result);
                            if ($result['code'] == 200 && isset($result['data']['result']) && is_array($result['data']['result'])) {
                                Db::table('Kefure_WechatFriend')->where(['Id' => $value['Id']])->update(['hasCollection' => 1]);
                                foreach ($result['data']['result'] as $k1 => $v1) {
                                    //判断是否已读取该条朋友圈
                                    $log = Db::table('WechatMomentInfo')
                                        ->where(['snsId' => $v1['snsId']])
                                        ->find();
                                    $insert = [];
                                    $momentEntity = $v1['momentEntity'];
                                    if (isset($momentEntity['urls'])) {
                                        $snsUrls = json_encode($momentEntity['urls'], 256);
                                    } else {
                                        $snsUrls = [];
                                    }
                                    if (empty($log)) {
                                        $insert['wechatFriendId'] = $result['data']['wechatFriendId'];
                                        $insert['wechatAccountId'] = $result['data']['wechatAccountId'];
                                        $insert['wechatId'] = $momentEntity['userName'];
                                        $insert['snsId'] = $momentEntity['snsId'];
                                        $insert['snsUrls'] = $snsUrls;
                                        $insert['type'] = $v1['type'];
                                        $insert['content'] = $momentEntity['content'];
                                        $insert['contentCreateTime'] = $v1['createTime'];
                                        $insert['lat'] = $momentEntity['lat'];
                                        $insert['lng'] = $momentEntity['lng'];
                                        $insert['title'] = $momentEntity['title'] ?? '';
                                        $insert['location'] = $momentEntity['location'];
                                        $insert['picSize'] = $momentEntity['picSize'];
                                        $insert['createTime'] = date('Y-m-d H:i:s');
                                        $insert['companyId'] = $v['CompanyId'];
                                        $insert['classId'] = $classId;
                                        Db::table('WechatMomentInfo')->insert($insert);
                                        //采集链接
                                        if (isset($momentEntity['urls']) && !empty($momentEntity['urls'])) {
                                            $type = 'CmdDownloadMomentImages';
                                            if ($v1['type'] == 1) {
                                                $params = [
                                                    'accountId' => $resultToken['data']['accountId'],
                                                    'snsId' => $momentEntity['snsId'],
                                                    'snsUrls' => $momentEntity['urls'],
                                                    'wechatAccountId' => $value['WechatAccountId'],
                                                    'type' => $type
                                                ];
                                                $resultUrl = requestCurl($urlMomentUrl, $params, 'POST', $header);
//                                            Log::write('采集朋友圈素材结果：' . $resultUrl);
                                                $resultUrl = json_decode($resultUrl, 1);

                                                if ($resultUrl['code'] == 200) {
                                                    if (!is_array($resultUrl['data'])) {
                                                        $data = json_decode($resultUrl['data'], 1);
                                                    } else {
                                                        $data = $resultUrl['data'];
                                                    }
                                                    if (isset($data['urls'])) {
                                                        $update['httpUrls'] = json_encode($data['urls'], 256);
                                                        $update['updateTime'] = date('Y-m-d H:i:s');
                                                        Db::table('WechatMomentInfo')->where(['snsId' => $data['snsId']])->update($update);
                                                    }
                                                } else {
                                                    //未成功抓取到图片再请求一次
                                                    $params = [
                                                        'accountId' => $resultToken['data']['accountId'],
                                                        'snsId' => $momentEntity['snsId'],
                                                        'snsUrls' => $momentEntity['urls'],
                                                        'wechatAccountId' => $value['WechatAccountId'],
                                                        'type' => $type
                                                    ];
                                                    $resultUrl = requestCurl($urlMomentUrl, $params, 'POST', $header);
//                                            Log::write('采集朋友圈素材结果：' . $resultUrl);
                                                    $resultUrl = json_decode($resultUrl, 1);

                                                    if ($resultUrl['code'] == 200) {
                                                        if (!is_array($resultUrl['data'])) {
                                                            $data = json_decode($resultUrl['data'], 1);
                                                        } else {
                                                            $data = $resultUrl['data'];
                                                        }
                                                        if (isset($data['urls'])) {
                                                            $update['httpUrls'] = json_encode($data['urls'], 256);
                                                            $update['updateTime'] = date('Y-m-d H:i:s');
                                                            Db::table('WechatMomentInfo')->where(['snsId' => $data['snsId']])->update($update);
                                                        }
                                                    }
                                                }
                                            } elseif ($v1['type'] == 15) {

                                            } elseif ($v1['type'] == 3) {
                                                $update['httpUrls'] = json_encode($momentEntity['urls'], 256);
                                                $update['updateTime'] = date('Y-m-d H:i:s');
                                                Db::table('WechatMomentInfo')->where(['snsId' => $momentEntity['snsId']])->update($update);
                                            }
                                        }
                                    } else {
                                        if (empty($log['httpUrls']) && !empty($log['snsUrls'])) {
                                            if ($log['type'] == 1) {
                                                $params = [
                                                    'accountId' => $resultToken['data']['accountId'],
                                                    'snsId' => $log['snsId'],
                                                    'snsUrls' => json_decode($log['snsUrls'], 1),
                                                    'wechatAccountId' => $value['WechatAccountId'],
                                                    'type' => 'CmdDownloadMomentImages'
                                                ];
                                                $resultUrl = requestCurl($urlMomentUrl, $params, 'POST', $header);
                                                $resultUrl = json_decode($resultUrl, 1);

                                                if ($resultUrl['code'] == 200) {
                                                    if (!is_array($resultUrl['data'])) {
                                                        $data = json_decode($resultUrl['data'], 1);
                                                    } else {
                                                        $data = $resultUrl['data'];
                                                    }
                                                    if (isset($data['urls'])) {
                                                        $update['httpUrls'] = json_encode($data['urls'], 256);
                                                        $update['updateTime'] = date('Y-m-d H:i:s');
                                                        Db::table('WechatMomentInfo')->where(['snsId' => $data['snsId']])->update($update);
                                                    }
                                                } else {
                                                    $params = [
                                                        'accountId' => $resultToken['data']['accountId'],
                                                        'snsId' => $log['snsId'],
                                                        'snsUrls' => json_decode($log['snsUrls'], 1),
                                                        'wechatAccountId' => $value['WechatAccountId'],
                                                        'type' => 'CmdDownloadMomentImages'
                                                    ];
                                                    $resultUrl = requestCurl($urlMomentUrl, $params, 'POST', $header);
                                                    $resultUrl = json_decode($resultUrl, 1);

                                                    if ($resultUrl['code'] == 200) {
                                                        if (!is_array($resultUrl['data'])) {
                                                            $data = json_decode($resultUrl['data'], 1);
                                                        } else {
                                                            $data = $resultUrl['data'];
                                                        }
                                                        if (isset($data['urls'])) {
                                                            $update['httpUrls'] = json_encode($data['urls'], 256);
                                                            $update['updateTime'] = date('Y-m-d H:i:s');
                                                            Db::table('WechatMomentInfo')->where(['snsId' => $data['snsId']])->update($update);
                                                        }
                                                    }
                                                }
                                            } elseif ($log['type'] == 3) {
                                                $update['httpUrls'] = $snsUrls;
                                                $update['updateTime'] = date('Y-m-d H:i:s');
                                                Db::table('WechatMomentInfo')->where(['snsId' => $momentEntity['snsId']])->update($update);
                                            }

                                        }
                                    }
                                    sleep(5);
                                }
                            }

                        }
                    }
                }
            }
            Db::commit();
            Log::write('执行自动采集朋友圈信息成功');
        } catch (Exception $e) {
            Db::rollback();
            Log::write('执行自动采集朋友圈信息失败');
            Log::write($e->getMessage());
        }

    }







    public function test234()
    {
        set_time_limit(0);
        Db::startTrans();
        try {
            //当天23点59分59秒
            $time = time();
            $dateTime = date('Y-m-d H:i:s', $time);
            $time23 = strtotime(date('Y-m-d', $time)) + 86399;
            //查询朋友圈同步设置
            $process = Db::table('WechatMomentProcess')
                ->where(['status' => 1, 'isDelete' => 0])
                ->where(['id' => 6])
                ->select();

            foreach ($process as $k => $v) {
                $v['isRepeat'] = 1;
                if (!empty($v['wechatAccountIds'])) {
                    $beginTime = strtotime(date('Y-m-d', $time) . ' ' . $v['taskBeginTime']);
                    $endTime = strtotime(date('Y-m-d', $time) . ' ' . $v['taskEndTime']);
                    //在计划时间内才运行

                    if ($time >= $beginTime && $time <= $endTime) {
                        $ids = [];
                        Db::table('WechatMomentProcess')->where(['id' => $v['id']])->update(['beginTime' => datetime(time())]);
                        if (!empty($v['friendIds'])) {
                            $friendIds = trim($v['friendIds'], ',');
                            if (!empty($friendIds)) {
                                $friendIds = explode(',', $friendIds);
                                $friendInfoList = Db::table('WechatMomentInfo')
                                    ->where(['isDelete' => 0])
                                    ->whereIn('wechatFriendId', $friendIds)
                                    ->field('id,type,httpUrls')
                                    ->select();
                                foreach ($friendInfoList as $kk => $vv) {
                                    if ($vv['type'] != 28) {
                                        if (!empty($vv['httpUrls'])) {
                                            if ($v['isRepeat'] == 0) {
                                                $whereRepeat = ['infoId' => $vv['id'], 'isDelete' => 0, 'type' => 1, 'processId' => $v['id']];
                                                $taskRepeat = Db::table('WechatMomentTask')
                                                    ->where($whereRepeat)
                                                    ->find();
                                                if (empty($taskRepeat)) {
                                                    array_push($ids, $vv['id']);
                                                }
                                            } else {
                                                array_push($ids, $vv['id']);
                                            }
                                        }
                                    } else {
                                        if ($v['isRepeat'] == 0) {
                                            $whereRepeat = ['infoId' => $vv['id'], 'isDelete' => 0, 'type' => 1, 'processId' => $v['id']];
                                            $taskRepeat = Db::table('WechatMomentTask')
                                                ->where($whereRepeat)
                                                ->find();
                                            if (empty($taskRepeat)) {
                                                array_push($ids, $vv['id']);
                                            }
                                        } else {
                                            array_push($ids, $vv['id']);
                                        }
                                    }
                                }
                                $ids = array_unique($ids);
                            }
                        }
                        if (!empty($v['classIds'])) {
                            $classIds = trim($v['classIds'], ',');
                            if (!empty($classIds)) {
                                $classIds = explode(',', $classIds);
                                $classInfoList = Db::table('WechatMomentInfo')
                                    ->where(['isDelete' => 0])
                                    ->whereIn('classId', $classIds)
                                    ->field('id')
                                    ->select();
                                //是否重复发
                                if ($v['isRepeat'] == 0) {
                                    foreach ($classInfoList as $kk => $vv) {
                                        $whereRepeat = ['infoId' => $vv['id'], 'isDelete' => 0, 'type' => 1, 'processId' => $v['id']];
                                        $taskRepeat = Db::table('WechatMomentTask')
                                            ->where($whereRepeat)
                                            ->find();
                                        if (empty($taskRepeat)) {
                                            array_push($ids, $vv['id']);
                                        }
                                    }
                                } else {
                                    $tempIds = array_column($classInfoList, 'id');
                                    $ids = array_merge($ids, $tempIds);
                                }
                                $ids = array_unique($ids);
                            }
                        }




                        //列出所有内容库
                        $infoList = Db::table('WechatMomentInfo')
                            ->whereIn('id', $ids)
                            ->order('contentCreateTime', 'desc')
                            ->select();



                        //删除晚于当前时间的任务(保留一条/10分钟)
                        $whereTask = [];
                        $whereTask['type'] = 1;
                        $whereTask['processId'] = $v['id'];
                        $whereTask['isDelete'] = 0;
                        $whereTask['isSend'] = 0;
                        $taskId =  Db::table('WechatMomentTask')
                            ->field('id,infoId')
                            ->where($whereTask)
                            ->order('startTime ASC')
                            ->find();

                        if (!empty($taskId)){
                            $whereTask['id'] = ['neq',$taskId['id']];
                            $ids[] = $taskId['infoId'];
                        }else{
                            $whereTask['startTime'] = ['gt', datetime(time() - 600)];
                        }



                        Db::table('WechatMomentTask')
                            ->where($whereTask)
                            ->update(['isDelete' => 1, 'deleteTime' => datetime(time())]);


                        //内容库没有数据跳出
                        if (empty($infoList)) {
                            continue;
                        }

                        //查询已有任务
                        $taskListTemp = Db::table('WechatMomentTask')
                            ->where(['isDelete' => 0, 'type' => 1, 'processId' => $v['id']])
                            ->whereIn('infoId', $ids)
                            ->order('id', 'desc')
                            ->group('infoId')
                            ->select();
                        $tempInfoIds = array_column($taskListTemp, 'infoId');
                        $tempInfoIds1 = array_column($infoList, 'id');
                        //留存未发送的内容库
                        $newInfoIds = [];
                        //判断是否有发送过数据
                        if (!empty($tempInfoIds)) {
                            foreach ($tempInfoIds1 as $kk => $vv) {
                                $newId = '';
                                foreach ($tempInfoIds as $kkk => $vvv) {
                                    if ($vv == $vvv) {
                                        $newId = '';
                                        break;
                                    } else {
                                        $newId = $vv;
                                    }
                                }
                                if (!empty($newId)) {
                                    $newInfoIds[] = $newId;
                                }
                            }
                        } else {
                            $newInfoIds = $tempInfoIds1;
                        }
                        //按最新发布的优先安排未发的内容库
                        $newInfoList = Db::table('WechatMomentInfo')
                            ->whereIn('id', $newInfoIds)
                            ->order('id', 'desc')
                            ->select();

                        if ($v['isAll'] != 1) {
                            $dateBeginTime = date('Y-m-d H:i:s', $beginTime - 300);//五分钟内
                            $dateEndTime = date('Y-m-d H:i:s', $endTime);
                            //查询当天已发布的任务数量
                            $isSendTaskNum = Db::table('WechatMomentTask')
                                ->where(['isDelete' => 0, 'type' => 1, 'processId' => $v['id']])
                                ->whereBetween('startTime', [$dateBeginTime, $dateEndTime])
                                ->count();
                            /*if ($isSendTaskNum >= $v['roomCount']) {
                                continue;
                            }*/
                        } else {
                            $isSendTaskNum = 0;
                        }

                        //初始化时间
                        $startTime = time();
                        //查询最后一条发送的数据
                        $finalTask = Db::table('WechatMomentTask')
                            ->where(['type' => 1, 'processId' => $v['id'], 'isDelete' => 0,'isSend' =>1])
                            ->order('id DESC')
                            ->find();
                        if (!empty($finalTask)) {
                            $startTime = strtotime($finalTask['startTime']);
                            $intervalTime = rand($v['intervalBeginTime'], $v['intervalEndTime']);
                            $startTime += $intervalTime * 60;
                        }

                        //循环发送内容库
                        if (!empty($v['isRepeat'])) {
                            //没有最新内容发送开始循环
                            $newInfoCount = count($newInfoList);
                            if (empty($newInfoList)) {
                                //搜索最后一条数据位置
                                $index = array_search($finalTask['infoId'], $tempInfoIds1) + 1;
                                $newInfoList = $infoList;
                                //删除已发的数据
                                array_splice($newInfoList, 0, $index);
                                if (count($newInfoList) == 0) {
                                    $newInfoList = $infoList;
                                }elseif ($newInfoCount < 5){
                                    $newInfoList = array_merge($newInfoList,$infoList) ;
                                }
                            }
                        } else {
                            if (empty($newInfoList)) {
                                continue;
                            }
                        }


                        //删除保留的任务
                        if (!empty($taskId)){
                            foreach ($newInfoList as $kk => $vv){
                                if ($vv['id'] == $taskId['infoId']){
                                    unset($newInfoList[$kk]);
                                }
                            }
                        }

                        //添加发布任务
                        foreach ($newInfoList as $k1 => $v1) {
                            //重新生成任务
                            if ($k1 > 0) {
                                $intervalTime = rand($v['intervalBeginTime'], $v['intervalEndTime']);
                                $startTime += $intervalTime * 60;
                            } else {
                                $intervalTime = 0;
                            }

                            //超过12点即终止
                            if ($startTime >= $time23) {
                                break;
                            } else {
                                //小于当日开始时间则将开始时间设置为当日开始时间
                                $tempDateTime = strtotime(date('Y-m-d', $startTime) . $v['taskBeginTime']);
                                if ($startTime < $tempDateTime) {
                                    $startTime = $tempDateTime;
                                }


                                //小于当前时间则按当前时间
                                if ($startTime < time()) {
                                    $startTime = time();
                                }

                                $insertOne = [
                                    'createdTime' => $dateTime,
                                    'infoId' => $v1['id'],
                                    'wechatAccountIds' => $v['wechatAccountIds'],
                                    'intervalTime' => $intervalTime,
                                    'startTime' => date('Y-m-d H:i:s', $startTime),
                                    'type' => 1,
                                    'userId' => $v['userId'],
                                    'companyId' => $v['companyId'],
                                    'processId' => $v['id']
                                ];
                                Db::table('WechatMomentTask')->insert($insertOne);
                            }
                        }
                    }
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Log::write('新版自动更新朋友圈转发任务失败，错误信息：' . $e->getMessage());
            dump($e->getMessage() . $e->getLine());
        }
    }




    public function test6()
    {
        $sendWhere = [];
        $sendWhere['isSend'] = 0;
        $sendWhere['sendTime'] = ['<', datetime(time() + 30)];
        $taskSend = Db::table('WechatFriendRequestTaskSend')
            ->where($sendWhere)
            ->select();
        foreach ($taskSend as $key => $val) {
            if ($val['sendTime'] <= time()) {
                $result = array(
                    "content" => $val['content'],
                    "msgType" => $val['msgType'],
                    "wechatAccountId" => $val['wechatAccountId'],
                    "wechatFriendId" => $val['wechatFriendId'],
                );

                $res = Db::table('WechatFriendRequestTaskSend')
                    ->where(['id' => $val['id']])
                    ->update(['isSend' => 1]);


                //break;
            }
        }

        exit_data($taskSend);
    }



    public function map($key = '橙心优选',$pn = 0,$nn = 0)
    {

        sleep(1);
        $api_url = 'https://map.baidu.com/';
        if ($pn > 1){
            $nn = 10 * ($pn - 1);
        }
        $params = [
            'newmap' => 1,
            'reqflag' => 'pcmap',
            'biz' => 1,
            'from' => 'webmap',
            'da_par' => 'baidu',
            'pcevaname' => 'pc4.1',
            'qt' => 's',
            'c' => 194,
            'wd' => $key,
            'wd2' => '',
            'pn' => $pn,
            'nn' => $nn,
            'db' => 0,
            'sug' => 0,
            'addr' => 0,
            'da_src' => 'searchBox.button',
            'on_gel' => 1,
            'src' => 7,
            'gr' => 3,
            'l' => 11,
            'rn' => 50,
            'tn' => 'B_NORMAL_MAP',
            'u_loc' => '',
            'ie' => 'utf-8',
            'b' => '',
            't' => (time() - 50) * 1000,
            'newfrom' => '',
        ];
        $res = requestCurl($api_url, $params, 'GET');
        $res = json_decode($res, true);

        if (!is_array($res)) {
            return errorJson('错误');
        }

        if (!isset($res['content'])){
            return false;
        }
        if (count($res['content']) >= 10){
            $pn = $pn + 1;
        }else{
            $pn = -1;
        }

        foreach ($res['content'] as $k => $v) {
            if (!empty($v['tel'])){
                $newData = [
                    'name' =>   $v['name'],
                    'addr' =>   $v['addr'],
                    'tel' =>   $v['tel'],
                    'key' =>   $key,
                ];
                Db::table('MapTest')->insertGetId($newData);
            }
        }

        if($pn > 0){
            $this->map($key,$pn);
        }

    }

    public function test2()
    {

        $d = ['公司'];
        foreach ($d as $k => $v){
            $this->map($v);
        }


        exit_data(12);
    }


    public function test1()
    {
        $api_url = 'https://kf.quwanzhi.com:9991';
        //kr账号信息
        $params = [
            'grant_type' => 'password',
            'username' => 'kr_xf3',
            'password' => 'xf123456',
        ];

        //=========== 登入kf系统 开始 ================
        $url = $api_url . '/token';
        $header = array(
            'client:kefu-client',
            'Content-Type:text/plain',
            /* 'verifycode:gg2b',
             'verifysessionid:1ac012f7-df32-424d-ab87-4882576a974f',*/
        );

        $res = requestCurl($url, $params, 'POST', $header);
        $result_array = json_decode($res, true);
        exit_data($result_array);
    }

    public function test4()
    {


        exit_data(42);
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        Db::startTrans();
        try {
            //当天23点59分59秒
            $time = time();
            $dateTime = date('Y-m-d H:i:s', $time);
            $time23 = strtotime(date('Y-m-d', $time)) + 86399;
            //查询社群推送设置
            $processRoom = Db::table('WechatMomentProcessRoom')
                ->where(['status' => 1, 'isDelete' => 0])
                ->order('id', 'desc')
                ->select();
            foreach ($processRoom as $k => $v) {
                //解析时间段
                $timeSlotList = json_decode($v['timeSlot'], 1);
                //数据排序
                $sendOrder = !empty($v['sendOrder']) ? 'DESC' : 'ASC';
                if (!empty($v['roomIds']) && is_array($timeSlotList)) {
                    $flagTime = false;
                    $timeArr = [];
                    $roomBeginTime = '';
                    foreach ($timeSlotList as $kt => $vt) {
                        $timeBeginTemp = strtotime(date('Y-m-d', $time) . ' ' . $vt['roomBeginTime']);
                        $timeEndTemp = strtotime(date('Y-m-d', $time) . ' ' . $vt['roomEndTime']);
                        if ($time >= $timeBeginTemp && $time <= $timeEndTemp) {
                            $flagTime = true;
                        }
                        if ($kt == 0) {
                            $roomBeginTime = $vt['roomBeginTime'];
                        }
                        array_push($timeArr, $timeBeginTemp);
                        array_push($timeArr, $timeEndTemp);
                    }
                    sort($timeArr);
                    $beginTime = $timeArr[0];
                    $endTime = $timeArr[count($timeArr) - 1];
                    //wong
                    Db::table('WechatMomentProcessRoom')->where(['id' => $v['id']])->update(['beginTime' => datetime(time())]);
                    //在计划时间内才运行
                    if ($flagTime) {
                        $ids = [];
                        if (!empty($v['friendIds'])) {
                            $friendIds = trim($v['friendIds'], ',');
                            if (!empty($friendIds)) {
                                $friendIds = explode(',', $friendIds);
                                $friendInfoList = Db::table('WechatMomentInfo')
                                    ->where(['isDelete' => 0])
                                    ->whereIn('wechatFriendId', $friendIds)
                                    ->field('id,type,httpUrls')
                                    ->select();
                                //根据isSendNoSource设置获取ids
                                if ($v['isSendNoSource'] == 1) {
                                    $ids = array_column($friendInfoList, 'id');
                                } else {
                                    foreach ($friendInfoList as $kk => $vv) {
                                        if ($vv['type'] != 28) {
                                            if (!empty($vv['httpUrls'])) {
                                                array_push($ids, $vv['id']);
                                            }
                                        } else {
                                            array_push($ids, $vv['id']);
                                        }
                                    }
                                }
                            }
                        }
                        if (!empty($v['classIds'])) {
                            $classIds = trim($v['classIds'], ',');
                            if (!empty($classIds)) {
                                $classIds = explode(',', $classIds);
                                $classInfoList = Db::table('WechatMomentInfo')
                                    ->where(['isDelete' => 0])
                                    ->whereIn('classId', $classIds)
                                    ->field('id')
                                    ->select();
                                $tempIds = array_column($classInfoList, 'id');
                                $ids = array_merge($ids, $tempIds);
                                $ids = array_unique($ids);
                            }
                        }
                        if (!empty($v['roomJDIds'])) {
                            $roomJDIds = trim($v['roomJDIds'], ',');
                            if (!empty($roomJDIds)) {
                                $roomJDIds = explode(',', $roomJDIds);
                                $roomJDInfoList = Db::table('WechatMomentInfo')
                                    ->where(['isDelete' => 0])
                                    ->whereIn('roomId', $roomJDIds)
                                    ->field('id')
                                    ->select();
                                $tempIds = array_column($roomJDInfoList, 'id');
                                $ids = array_merge($ids, $tempIds);
                                $ids = array_unique($ids);
                            }
                        }


                        //列出所有内容库
                        $infoList = Db::table('WechatMomentInfo')
                            ->whereIn('id', $ids)
                            ->order('contentCreateTime', $sendOrder)
                            ->select();

                        //内容库没有数据跳出
                        if (empty($infoList)) {
                            continue;
                        }


                        //删除晚于当前时间的任务(保留一条/10分钟)
                        $whereTask = [];
                        $whereTask['type'] = 2;
                        $whereTask['processId'] = $v['id'];
                        $whereTask['isDelete'] = 0;
                        $whereTask['isSendRoom'] = 0;
                        $taskId = Db::table('WechatMomentTask')
                            ->field('id,infoId')
                            ->where($whereTask)
                            ->order('startTime ASC')
                            ->find();
                        if (!empty($taskId)) {
                            $whereTask['id'] = ['neq', $taskId['id']];
                            $ids[] = $taskId['infoId'];
                        } else {
                            $whereTask['startTime'] = ['gt', datetime(time() - 600)];
                        }
                        $whereTask['startTime'] = ['gt', datetime(time() - 600)];
                        Db::table('WechatMomentTask')
                            ->where($whereTask)
                            ->update(['isDelete' => 1, 'deleteTime' => datetime(time())]);


                        //查询已有任务
                        $taskListTemp = Db::table('WechatMomentTask')
                            ->where(['isDelete' => 0, 'type' => 2, 'processId' => $v['id'], 'isSendRoom' => 1])
                            ->whereIn('infoId', $ids)
                            ->order('id', 'desc')
                            ->group('infoId')
                            ->select();
                        $tempInfoIds = array_column($taskListTemp, 'infoId');
                        $tempInfoIds1 = array_column($infoList, 'id');
                        //留存未发送的内容库
                        $newInfoIds = [];
                        //判断是否有发送过数据
                        if (!empty($tempInfoIds)) {
                            foreach ($tempInfoIds1 as $kk => $vv) {
                                $newId = '';
                                foreach ($tempInfoIds as $kkk => $vvv) {
                                    if ($vv == $vvv) {
                                        $newId = '';
                                        break;
                                    } else {
                                        $newId = $vv;
                                    }
                                }
                                if (!empty($newId)) {
                                    $newInfoIds[] = $newId;
                                }
                            }
                        } else {
                            $newInfoIds = $tempInfoIds1;
                        }

                        //按最新发布的优先安排未发的内容库
                        $newInfoList = Db::table('WechatMomentInfo')
                            ->whereIn('id', $newInfoIds)
                            ->order('id', $sendOrder)
                            ->select();

                        if ($v['isAll'] != 1) {
                            $dateBeginTime = date('Y-m-d H:i:s', $beginTime - 300);//五分钟内
                            $dateEndTime = date('Y-m-d H:i:s', $endTime);
                            //查询当天已发布的任务数量
                            $isSendTaskNum = Db::table('WechatMomentTask')
                                ->where(['isDelete' => 0, 'type' => 2, 'processId' => $v['id'], 'isSendRoom' => 1])
                                ->whereBetween('startTime', [$dateBeginTime, $dateEndTime])
                                ->count();
                            if ($isSendTaskNum >= $v['roomCount']) {
                                continue;
                            }
                        } else {
                            $isSendTaskNum = 0;
                        }


                        //初始化时间
                        $startTime = time();
                        //查询最后一条发送的数据
                        $finalTask = Db::table('WechatMomentTask')
                            ->where(['type' => 2, 'processId' => $v['id'], 'isDelete' => 0, 'isSendRoom' => 1])
                            ->order('id DESC')
                            ->find();
                        if (!empty($finalTask)) {
                            $startTime = strtotime($finalTask['startTime']);
                            $intervalTime = rand($v['intervalBeginTime'], $v['intervalEndTime']);
                            $startTime += $intervalTime * 60;
                        }
                        //循环发送内容库
                        if (!empty($v['isLoops'])) {
                            //没有最新内容发送开始循环
                            if (empty($newInfoList)) {
                                //搜索最后一条数据位置
                                $index = array_search($finalTask['infoId'], $tempInfoIds1) + 1;
                                $newInfoList = $infoList;
                                //删除已发的数据
                                array_splice($newInfoList, 0, $index);
                                if (count($newInfoList) == 0) {
                                    $newInfoList = $infoList;
                                }
                            }
                        } else {
                            if (empty($newInfoList)) {
                                continue;
                            }
                        }


                        //删除保留的任务
                        if (!empty($taskId)) {
                            foreach ($newInfoList as $kk => $vv) {
                                if ($vv['id'] == $taskId['infoId']) {
                                    unset($newInfoList[$kk]);
                                }
                            }
                        }


                        //添加发布任务
                        foreach ($newInfoList as $k1 => $v1) {
                            //重新生成任务
                            if ($k1 > 0) {
                                $intervalTime = rand($v['intervalBeginTime'], $v['intervalEndTime']);
                                $startTime += $intervalTime * 60;
                            } else {
                                $intervalTime = 0;
                            }
                            //发送时间
                            foreach ($timeSlotList as $kt => $vt) {
                                $timeEndTemp = strtotime(date('Y-m-d', $startTime) . ' ' . $vt['roomEndTime']);
                                if (isset($timeSlotList[$kt + 1])) {
                                    $timeBeginTemp1 = strtotime(date('Y-m-d', $startTime) . ' ' . $timeSlotList[$kt + 1]['roomBeginTime']);
                                    if ($startTime >= $timeEndTemp && $startTime <= $timeBeginTemp1) {
                                        $startTime = $timeBeginTemp1;
                                    }
                                } else {
                                    if ($startTime > $timeEndTemp) {
                                        $timeEndTempAfter = strtotime(date('Y-m-d', $startTime + 86399));
                                        $tempDateTime = strtotime(date('Y-m-d', $timeEndTempAfter) . $roomBeginTime);
                                        $startTime = $tempDateTime;
                                    }
                                }
                            }
                            //超过12点即终止
                            if ($startTime >= $time23) {
                                break;
                            } else {
                                //小于当日开始时间则将开始时间设置为当日开始时间
                                $tempDateTime = strtotime(date('Y-m-d', $startTime) . $roomBeginTime);
                                if ($startTime < $tempDateTime) {
                                    $startTime = $tempDateTime;
                                }

                                //小于当前时间则按当前时间
                                if ($startTime < time()) {
                                    $startTime = time();
                                }


                                $insertOne = [
                                    'createdTime' => $dateTime,
                                    'infoId' => $v1['id'],
                                    'roomIds' => $v['roomIds'],
                                    'intervalTime' => $intervalTime,
                                    'startTime' => date('Y-m-d H:i:s', $startTime),
                                    'type' => 2,
                                    'userId' => $v['userId'],
                                    'companyId' => $v['companyId'],
                                    'processId' => $v['id']
                                ];
                                Db::table('WechatMomentTask')->insert($insertOne);
                            }

                        }
                    }
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Log::write('新版自动更新社群转发任务失败，错误信息：' . $e->getMessage());
            dump($e->getMessage() . $e->getLine());
        }
    }

    public function emoji()
    {
        $html = '<ul class="list-unstyled"><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚂" data-original-title="机车"><a href="javascript:"><img alt="运输|旅行|火车|蒸汽|蒸汽火车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/steam-locomotive_1f682.png" data-original="https://emoji.emojipic.cn/pic/72/apple/steam-locomotive_1f682.png" width="38" style=""></a><a href="/emoji-steam-locomotive" class="link" target="_blank" title="运输|旅行|火车|蒸汽|蒸汽火车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚃" data-original-title="火车车厢"><a href="javascript:"><img alt="交通|旅行|火车|铁路|电车|轨道车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/railway-car_1f683.png" data-original="https://emoji.emojipic.cn/pic/72/apple/railway-car_1f683.png" width="38" style=""></a><a href="/emoji-railway-car" class="link" target="_blank" title="交通|旅行|火车|铁路|电车|轨道车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚄" data-original-title="高速列车"><a href="javascript:"><img alt="交通|旅行|火车|高速|动车|新干线|高铁" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/high-speed-train_1f684.png" data-original="https://emoji.emojipic.cn/pic/72/apple/high-speed-train_1f684.png" width="38" style=""></a><a href="/emoji-high-speed-train" class="link" target="_blank" title="交通|旅行|火车|高速|动车|新干线|高铁">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚅" data-original-title="子弹头列车"><a href="javascript:"><img alt="动车|子弹头高速列车|新干线|火车|高速|高铁" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/high-speed-train-with-bullet-nose_1f685.png" data-original="https://emoji.emojipic.cn/pic/72/apple/high-speed-train-with-bullet-nose_1f685.png" width="38" style=""></a><a href="/emoji-high-speed-train-with-bullet-nose" class="link" target="_blank" title="动车|子弹头高速列车|新干线|火车|高速|高铁">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚆" data-original-title="火车"><a href="javascript:"><img alt="交通|旅行|火车|铁路" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/train_1f686.png" data-original="https://emoji.emojipic.cn/pic/72/apple/train_1f686.png" width="38" style=""></a><a href="/emoji-train" class="link" target="_blank" title="交通|旅行|火车|铁路">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚇" data-original-title="地铁"><a href="javascript:"><img alt="交通|旅行|火车|地铁" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/metro_1f687.png" data-original="https://emoji.emojipic.cn/pic/72/apple/metro_1f687.png" width="38" style=""></a><a href="/emoji-metro" class="link" target="_blank" title="交通|旅行|火车|地铁">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚈" data-original-title="轻轨"><a href="javascript:"><img alt="交通|旅行|火车|轻轨|铁路" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/light-rail_1f688.png" data-original="https://emoji.emojipic.cn/pic/72/apple/light-rail_1f688.png" width="38" style=""></a><a href="/emoji-light-rail" class="link" target="_blank" title="交通|旅行|火车|轻轨|铁路">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚉" data-original-title="车站"><a href="javascript:"><img alt="交通|旅行|火车|车站" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/station_1f689.png" data-original="https://emoji.emojipic.cn/pic/72/apple/station_1f689.png" width="38" style=""></a><a href="/emoji-station" class="link" target="_blank" title="交通|旅行|火车|车站">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚊" data-original-title="有轨电车"><a href="javascript:"><img alt="交通|旅行|火车|电车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/tram_1f68a.png" data-original="https://emoji.emojipic.cn/pic/72/apple/tram_1f68a.png" width="38" style=""></a><a href="/emoji-tram" class="link" target="_blank" title="交通|旅行|火车|电车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚝" data-original-title="单轨铁路"><a href="javascript:"><img alt="交通|旅行|火车|度假|单轨" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/monorail_1f69d.png" data-original="https://emoji.emojipic.cn/pic/72/apple/monorail_1f69d.png" width="38" style=""></a><a href="/emoji-monorail" class="link" target="_blank" title="交通|旅行|火车|度假|单轨">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚞" data-original-title="山区铁路"><a href="javascript:"><img alt="交通|旅行|火车|山地|铁路" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/mountain-railway_1f69e.png" data-original="https://emoji.emojipic.cn/pic/72/apple/mountain-railway_1f69e.png" width="38" style=""></a><a href="/emoji-mountain-railway" class="link" target="_blank" title="交通|旅行|火车|山地|铁路">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚋" data-original-title="有轨电车"><a href="javascript:"><img alt="交通|旅行|火车|电车|汽车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/tram-car_1f68b.png" data-original="https://emoji.emojipic.cn/pic/72/apple/tram-car_1f68b.png" width="38" style=""></a><a href="/emoji-tram-car" class="link" target="_blank" title="交通|旅行|火车|电车|汽车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚌" data-original-title="公共汽车"><a href="javascript:"><img alt="交通|公共汽车|公交车|大巴" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/bus_1f68c.png" data-original="https://emoji.emojipic.cn/pic/72/apple/bus_1f68c.png" width="38" style=""></a><a href="/emoji-bus" class="link" target="_blank" title="交通|公共汽车|公交车|大巴">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚍" data-original-title="迎面而来的巴士"><a href="javascript:"><img alt="交通|公共汽车|旅行|迎面而来的车辆|公交车|大巴" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/oncoming-bus_1f68d.png" data-original="https://emoji.emojipic.cn/pic/72/apple/oncoming-bus_1f68d.png" width="38" style=""></a><a href="/emoji-oncoming-bus" class="link" target="_blank" title="交通|公共汽车|旅行|迎面而来的车辆|公交车|大巴">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚎" data-original-title="无轨电车"><a href="javascript:"><img alt="交通|公共汽车|旅行|无轨电车|电车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/trolleybus_1f68e.png" data-original="https://emoji.emojipic.cn/pic/72/apple/trolleybus_1f68e.png" width="38" style=""></a><a href="/emoji-trolleybus" class="link" target="_blank" title="交通|公共汽车|旅行|无轨电车|电车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚐" data-original-title="小型公共汽车"><a href="javascript:"><img alt="交通|公共汽车|小巴" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/minibus_1f690.png" data-original="https://emoji.emojipic.cn/pic/72/apple/minibus_1f690.png" width="38" style=""></a><a href="/emoji-minibus" class="link" target="_blank" title="交通|公共汽车|小巴">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚑" data-original-title="救护车"><a href="javascript:"><img alt="交通|救护车|医院|急救" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/ambulance_1f691.png" data-original="https://emoji.emojipic.cn/pic/72/apple/ambulance_1f691.png" width="38" style=""></a><a href="/emoji-ambulance" class="link" target="_blank" title="交通|救护车|医院|急救">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚒" data-original-title="消防车"><a href="javascript:"><img alt="交通|消防|发动机|911|救火车|消防车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/fire-engine_1f692.png" data-original="https://emoji.emojipic.cn/pic/72/apple/fire-engine_1f692.png" width="38" style=""></a><a href="/emoji-fire-engine" class="link" target="_blank" title="交通|消防|发动机|911|救火车|消防车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚓" data-original-title="警车"><a href="javascript:"><img alt="交通|汽车|车辆|警察|警车|巡逻" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/police-car_1f693.png" data-original="https://emoji.emojipic.cn/pic/72/apple/police-car_1f693.png" width="38" style=""></a><a href="/emoji-police-car" class="link" target="_blank" title="交通|汽车|车辆|警察|警车|巡逻">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚔" data-original-title="迎面而来的警车"><a href="javascript:"><img alt="交通|汽车|车辆|迎面|警察|警车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/oncoming-police-car_1f694.png" data-original="https://emoji.emojipic.cn/pic/72/apple/oncoming-police-car_1f694.png" width="38" style=""></a><a href="/emoji-oncoming-police-car" class="link" target="_blank" title="交通|汽车|车辆|迎面|警察|警车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚕" data-original-title="出租车"><a href="javascript:"><img alt="交通|汽车|车辆|出租车|的士" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/taxi_1f695.png" data-original="https://emoji.emojipic.cn/pic/72/apple/taxi_1f695.png" width="38" style=""></a><a href="/emoji-taxi" class="link" target="_blank" title="交通|汽车|车辆|出租车|的士">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚖" data-original-title="迎面而来的出租车"><a href="javascript:"><img alt="交通|汽车|车辆|迎面|出租车|的士" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/oncoming-taxi_1f696.png" data-original="https://emoji.emojipic.cn/pic/72/apple/oncoming-taxi_1f696.png" width="38" style=""></a><a href="/emoji-oncoming-taxi" class="link" target="_blank" title="交通|汽车|车辆|迎面|出租车|的士">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚗" data-original-title="汽车用品"><a href="javascript:"><img alt="交通|汽车|车辆|轿车|红色" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/automobile_1f697.png" data-original="https://emoji.emojipic.cn/pic/72/apple/automobile_1f697.png" width="38" style=""></a><a href="/emoji-automobile" class="link" target="_blank" title="交通|汽车|车辆|轿车|红色">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚘" data-original-title="迎面而来的汽车"><a href="javascript:"><img alt="交通|汽车|车辆|迎面|轿车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/oncoming-automobile_1f698.png" data-original="https://emoji.emojipic.cn/pic/72/apple/oncoming-automobile_1f698.png" width="38" style=""></a><a href="/emoji-oncoming-automobile" class="link" target="_blank" title="交通|汽车|车辆|迎面|轿车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚙" data-original-title="运动型多用途车"><a href="javascript:"><img alt="交通|汽车|车辆|SUV|越野车|运动型多用途车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/recreational-vehicle_1f699.png" data-original="https://emoji.emojipic.cn/pic/72/apple/recreational-vehicle_1f699.png" width="38" style=""></a><a href="/emoji-recreational-vehicle" class="link" target="_blank" title="交通|汽车|车辆|SUV|越野车|运动型多用途车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚚" data-original-title="送货车"><a href="javascript:"><img alt="运输|卡车|交货|货车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/delivery-truck_1f69a.png" data-original="https://emoji.emojipic.cn/pic/72/apple/delivery-truck_1f69a.png" width="38" style=""></a><a href="/emoji-delivery-truck" class="link" target="_blank" title="运输|卡车|交货|货车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚛" data-original-title="铰接式货车"><a href="javascript:"><img alt="运输|卡车|运输车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/articulated-lorry_1f69b.png" data-original="https://emoji.emojipic.cn/pic/72/apple/articulated-lorry_1f69b.png" width="38" style=""></a><a href="/emoji-articulated-lorry" class="link" target="_blank" title="运输|卡车|运输车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚜" data-original-title="拖拉机"><a href="javascript:"><img alt="运输|拖拉机" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/tractor_1f69c.png" data-original="https://emoji.emojipic.cn/pic/72/apple/tractor_1f69c.png" width="38" style=""></a><a href="/emoji-tractor" class="link" target="_blank" title="运输|拖拉机">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛵" data-original-title="摩托车"><a href="javascript:"><img alt="交通|汽车|踏板车|电动车|摩托车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/motor-scooter_1f6f5.png" data-original="https://emoji.emojipic.cn/pic/72/apple/motor-scooter_1f6f5.png" width="38" style=""></a><a href="/emoji-motor-scooter" class="link" target="_blank" title="交通|汽车|踏板车|电动车|摩托车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚲" data-original-title="自行车"><a href="javascript:"><img alt="交通|旅行|自行车|脚踏车|单车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/bicycle_1f6b2.png" data-original="https://emoji.emojipic.cn/pic/72/apple/bicycle_1f6b2.png" width="38" style=""></a><a href="/emoji-bicycle" class="link" target="_blank" title="交通|旅行|自行车|脚踏车|单车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛴" data-original-title="脚踏车"><a href="javascript:"><img alt="交通|踏板车|滑板车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/scooter_1f6f4.png" data-original="https://emoji.emojipic.cn/pic/72/apple/scooter_1f6f4.png" width="38" style=""></a><a href="/emoji-scooter" class="link" target="_blank" title="交通|踏板车|滑板车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚏" data-original-title="公交站"><a href="javascript:"><img alt="交通|公交站|公交车站|公共汽车站|站牌" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/bus-stop_1f68f.png" data-original="https://emoji.emojipic.cn/pic/72/apple/bus-stop_1f68f.png" width="38" style=""></a><a href="/emoji-bus-stop" class="link" target="_blank" title="交通|公交站|公交车站|公共汽车站|站牌">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛣️" data-original-title="高速公路"><a href="javascript:"><img alt="交通|高速公路|公路" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/motorway_1f6e3.png" data-original="https://emoji.emojipic.cn/pic/72/apple/motorway_1f6e3.png" width="38" style=""></a><a href="/emoji-motorway" class="link" target="_blank" title="交通|高速公路|公路">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛤️" data-original-title="铁路轨道"><a href="javascript:"><img alt="运输|铁路|轨道|火车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/railway-track_1f6e4.png" data-original="https://emoji.emojipic.cn/pic/72/apple/railway-track_1f6e4.png" width="38" style=""></a><a href="/emoji-railway-track" class="link" target="_blank" title="运输|铁路|轨道|火车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛢️" data-original-title="油桶"><a href="javascript:"><img alt="运输|油桶|桶|石油桶" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/oil-drum_1f6e2.png" data-original="https://emoji.emojipic.cn/pic/72/apple/oil-drum_1f6e2.png" width="38" style=""></a><a href="/emoji-oil-drum" class="link" target="_blank" title="运输|油桶|桶|石油桶">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="⛽" data-original-title="燃油泵"><a href="javascript:"><img alt="运输|燃料|加油站|柴油|油泵|燃油" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/fuel-pump_26fd.png" data-original="https://emoji.emojipic.cn/pic/72/apple/fuel-pump_26fd.png" width="38" style=""></a><a href="/emoji-fuel-pump" class="link" target="_blank" title="运输|燃料|加油站|柴油|油泵|燃油">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚨" data-original-title="警车灯"><a href="javascript:"><img alt="交通|物品|警察|汽车|照明|灯|警报|警灯|警示|警车灯" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/police-cars-revolving-light_1f6a8.png" data-original="https://emoji.emojipic.cn/pic/72/apple/police-cars-revolving-light_1f6a8.png" width="38" style=""></a><a href="/emoji-police-cars-revolving-light" class="link" target="_blank" title="交通|物品|警察|汽车|照明|灯|警报|警灯|警示|警车灯">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚥" data-original-title="水平交通灯"><a href="javascript:"><img alt="交通|停车|灯光|水平|交通灯|信号灯|横向的红绿灯|红绿灯" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/horizontal-traffic-light_1f6a5.png" data-original="https://emoji.emojipic.cn/pic/72/apple/horizontal-traffic-light_1f6a5.png" width="38" style=""></a><a href="/emoji-horizontal-traffic-light" class="link" target="_blank" title="交通|停车|灯光|水平|交通灯|信号灯|横向的红绿灯|红绿灯">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚦" data-original-title="垂直交通灯"><a href="javascript:"><img alt="交通|停车|灯光|交通灯|信号灯|红绿灯|纵向的红绿灯" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/vertical-traffic-light_1f6a6.png" data-original="https://emoji.emojipic.cn/pic/72/apple/vertical-traffic-light_1f6a6.png" width="38" style=""></a><a href="/emoji-vertical-traffic-light" class="link" target="_blank" title="交通|停车|灯光|交通灯|信号灯|红绿灯|纵向的红绿灯">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛑" data-original-title="停车标志"><a href="javascript:"><img alt="交通|八角形|标志|停止|停止标志|八边形" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/octagonal-sign_1f6d1.png" data-original="https://emoji.emojipic.cn/pic/72/apple/octagonal-sign_1f6d1.png" width="38" style=""></a><a href="/emoji-octagonal-sign" class="link" target="_blank" title="交通|八角形|标志|停止|停止标志|八边形">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚧" data-original-title="施工"><a href="javascript:"><img alt="运输|物体|施工|路障" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/construction-sign_1f6a7.png" data-original="https://emoji.emojipic.cn/pic/72/apple/construction-sign_1f6a7.png" width="38" style=""></a><a href="/emoji-construction-sign" class="link" target="_blank" title="运输|物体|施工|路障">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="⛵" data-original-title="帆船"><a href="javascript:"><img alt="运输|帆船|船" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/sailboat_26f5.png" data-original="https://emoji.emojipic.cn/pic/72/apple/sailboat_26f5.png" width="38" style=""></a><a href="/emoji-sailboat" class="link" target="_blank" title="运输|帆船|船">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛶" data-original-title="独木舟"><a href="javascript:"><img alt="交通|船|独木舟" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/canoe_1f6f6.png" data-original="https://emoji.emojipic.cn/pic/72/apple/canoe_1f6f6.png" width="38" style=""></a><a href="/emoji-canoe" class="link" target="_blank" title="交通|船|独木舟">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚤" data-original-title="快艇"><a href="javascript:"><img alt="交通|旅行|船只|海洋|度假|热带|快艇|船" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/speedboat_1f6a4.png" data-original="https://emoji.emojipic.cn/pic/72/apple/speedboat_1f6a4.png" width="38" style=""></a><a href="/emoji-speedboat" class="link" target="_blank" title="交通|旅行|船只|海洋|度假|热带|快艇|船">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛳️" data-original-title="客船"><a href="javascript:"><img alt="运输|乘客|船舶|船只|客船|客轮" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/passenger-ship_1f6f3.png" data-original="https://emoji.emojipic.cn/pic/72/apple/passenger-ship_1f6f3.png" width="38" style=""></a><a href="/emoji-passenger-ship" class="link" target="_blank" title="运输|乘客|船舶|船只|客船|客轮">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="⛴️" data-original-title="轮渡"><a href="javascript:"><img alt="交通|渡船|船|渡轮|轮船" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/ferry_26f4.png" data-original="https://emoji.emojipic.cn/pic/72/apple/ferry_26f4.png" width="38" style=""></a><a href="/emoji-ferry" class="link" target="_blank" title="交通|渡船|船|渡轮|轮船">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛥️" data-original-title="机动船"><a href="javascript:"><img alt="交通|船|摩托艇" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/motor-boat_1f6e5.png" data-original="https://emoji.emojipic.cn/pic/72/apple/motor-boat_1f6e5.png" width="38" style=""></a><a href="/emoji-motor-boat" class="link" target="_blank" title="交通|船|摩托艇">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚢" data-original-title="船"><a href="javascript:"><img alt="交通|旅行|船只|海洋" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/ship_1f6a2.png" data-original="https://emoji.emojipic.cn/pic/72/apple/ship_1f6a2.png" width="38" style=""></a><a href="/emoji-ship" class="link" target="_blank" title="交通|旅行|船只|海洋">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="✈️" data-original-title="飞机"><a href="javascript:"><img alt="交通|飞机|飞行|旅行|度假" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/airplane_2708.png" data-original="https://emoji.emojipic.cn/pic/72/apple/airplane_2708.png" width="38" style=""></a><a href="/emoji-airplane" class="link" target="_blank" title="交通|飞机|飞行|旅行|度假">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛩️" data-original-title="小型飞机"><a href="javascript:"><img alt="交通|飞机|飞行|玩具飞机|小型飞机" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/small-airplane_1f6e9.png" data-original="https://emoji.emojipic.cn/pic/72/apple/small-airplane_1f6e9.png" width="38" style=""></a><a href="/emoji-small-airplane" class="link" target="_blank" title="交通|飞机|飞行|玩具飞机|小型飞机">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛫" data-original-title="飞机起飞"><a href="javascript:"><img alt="交通|飞机|飞行|旅行|度假|出发|值机|登机|航班起飞|起飞" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/airplane-departure_1f6eb.png" data-original="https://emoji.emojipic.cn/pic/72/apple/airplane-departure_1f6eb.png" width="38" style=""></a><a href="/emoji-airplane-departure" class="link" target="_blank" title="交通|飞机|飞行|旅行|度假|出发|值机|登机|航班起飞|起飞">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🛬" data-original-title="飞机抵达"><a href="javascript:"><img alt="交通|飞机|飞行|旅行|度假|抵达|到达|航班降落|降落" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/airplane-arriving_1f6ec.png" data-original="https://emoji.emojipic.cn/pic/72/apple/airplane-arriving_1f6ec.png" width="38" style=""></a><a href="/emoji-airplane-arriving" class="link" target="_blank" title="交通|飞机|飞行|旅行|度假|抵达|到达|航班降落|降落">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="💺" data-original-title="座"><a href="javascript:"><img alt="座位|椅子" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/seat_1f4ba.png" data-original="https://emoji.emojipic.cn/pic/72/apple/seat_1f4ba.png" width="38" style=""></a><a href="/emoji-seat" class="link" target="_blank" title="座位|椅子">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚁" data-original-title="直升机"><a href="javascript:"><img alt="交通|飞机|飞行|旅行|直升机|直升飞机" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/helicopter_1f681.png" data-original="https://emoji.emojipic.cn/pic/72/apple/helicopter_1f681.png" width="38" style=""></a><a href="/emoji-helicopter" class="link" target="_blank" title="交通|飞机|飞行|旅行|直升机|直升飞机">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚟" data-original-title="悬空铁路"><a href="javascript:"><img alt="运输|火车|悬挂|铁路|空中轨道列车|空轨" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/suspension-railway_1f69f.png" data-original="https://emoji.emojipic.cn/pic/72/apple/suspension-railway_1f69f.png" width="38" style=""></a><a href="/emoji-suspension-railway" class="link" target="_blank" title="运输|火车|悬挂|铁路|空中轨道列车|空轨">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚠" data-original-title="山地索道"><a href="javascript:"><img alt="交通|旅游|索道|空中|缆车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/mountain-cableway_1f6a0.png" data-original="https://emoji.emojipic.cn/pic/72/apple/mountain-cableway_1f6a0.png" width="38" style=""></a><a href="/emoji-mountain-cableway" class="link" target="_blank" title="交通|旅游|索道|空中|缆车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="🚡" data-original-title="架空索道"><a href="javascript:"><img alt="空中|索道|缆车" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/aerial-tramway_1f6a1.png" data-original="https://emoji.emojipic.cn/pic/72/apple/aerial-tramway_1f6a1.png" width="38" style=""></a><a href="/emoji-aerial-tramway" class="link" target="_blank" title="空中|索道|缆车">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="⚠️" data-original-title="警告"><a href="javascript:"><img alt="符号|标点符号|标记|警告" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/warning-sign_26a0.png" data-original="https://emoji.emojipic.cn/pic/72/apple/warning-sign_26a0.png" width="38" style=""></a><a href="/emoji-warning-sign" class="link" target="_blank" title="符号|标点符号|标记|警告">&gt;</a></div></li><li><a href="javascript:"></a><div class="thumbnail" data-toggle="tooltip" data-placement="top" title="" data-clipboard-text="⛔" data-original-title="禁止入内"><a href="javascript:"><img alt="不|进入|停止|交通|禁止通行|请勿入内|请勿驶入" class="lazy" src="https://emoji.emojipic.cn/pic/72/apple/no-entry_26d4.png" data-original="https://emoji.emojipic.cn/pic/72/apple/no-entry_26d4.png" width="38" style=""></a><a href="/emoji-no-entry" class="link" target="_blank" title="不|进入|停止|交通|禁止通行|请勿入内|请勿驶入">&gt;</a></div></li><div class="clearfix"></div></ul>';

// 正则表达式模式匹配data-clipboard-text属性的值
        $pattern = '/data-clipboard-text="([^"]+)"/';
        preg_match_all($pattern, $html, $matches);
// 提取匹配的结果
        $dataClipboardText = $matches[1];

// 正则表达式模式匹配data-original-title属性的值
        $pattern = '/data-original-title="([^"]+)"/';
        preg_match_all($pattern, $html, $matches);

// 提取匹配的结果
        $dataOriginalTitle = $matches[1];


        $data = [];

        // 遍历数组并组合成二维数组
        for ($i = 0; $i < count($dataClipboardText); $i++) {
            $data[] = array(
                'val' => $dataClipboardText[$i],
                'name' => $dataOriginalTitle[$i]
            );
        }


        foreach ($data as $k => $v) {
            $sql_data = [
                'type' => 11,
                'name' => $v['name'],
                'val' => $v['val'],
            ];

            Db::table('emoji')->insertGetId($sql_data);
        }


        exit_data($data);
    }

    public function get_dy_video($url)
    {
        $url = input('url', $url);
        $res = array("code" => 1);

        if (strpos($url, "v.douyin.com") !== false) {
            try {
                set_time_limit(0);
                $UserAgent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)";
                $UserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36 Edg/81.0.416.53";
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_ENCODING, "");
                curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                $data = curl_exec($curl);
                curl_close($curl);

                exit_data($data);
                preg_match("/<p class=\"desc\">(?<desc>[^<>]*)<\\/p>/i", $data, $name);
                preg_match("/playAddr: \"(?<url>[^\"]+)\"/i", $data, $url);
                if (empty($name["desc"])) {
                    $res["msg"] = "地址格式不正确";
                    return $res;
                }
                $video_url = $url["url"];
                $res["code"] = 0;
                $res["company"] = "抖音";
                $res["real_url"] = $video_url;
                $res["duration"] = 0;
                exit_data($res);
                return $res;
            } catch (Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        } else {
            $res["msg"] = "视频解析失败，请重试";
            return $res;
        }
    }
}
