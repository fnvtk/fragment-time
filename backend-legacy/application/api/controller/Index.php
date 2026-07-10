<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    //查询同步朋友圈任务,自动推送微信群消息
    public function test()
    {

        exit_data(32234);
        $data =  Db::table('CompanyWechat')->select();
        foreach ($data as $key => $val) {
            $wechat = Db::table('Wechat')->alias('w')
                ->field('ww.Id')
                ->join('Kefure_WechatAccount ww','w.WechatStringId=ww.WechatId')
                ->where(['w.Id' => $val['WechatId']])
                ->find();


            $c = Db::table('Company')->alias('c')
                ->field('cc.lId')
                ->join('Account_copy1 cc','c.Name=cc.sName')
                ->where(['c.Id' => $val['CompanyId']])
                ->find();


            $newData = [
                'AccountId' => $c['lId'],
                'WeChatId' => $wechat['Id'],
            ];

            Db::table('AccountWechat_copy1')->insertGetId($newData);
        }


        exit_data(222);

        $data = Db::table('Company')->select();
        foreach ($data as $key => $val) {

            $s = Db::table('Account_copy1')
                ->where(['sName' => $val['Name']])
                ->find();


            $user = Db::table('CompanyAccount')
                ->where(['CompanyId' => $val['Id'], 'PurviewAdmin' => 1])
                ->find();
            if (empty($s)) {
                $accountData = [
                    'sName' => $val['Name'],
                    'NewUserId' => 1,
                    'EditUserId' => 1,
                    'OwnerId' => 1,
                    'dNewTime' => datetime(strtotime($val['CreatedTime'])),
                    'dEditTime' => datetime(time()),
                    'sManagerName' => $user['Username'],
                    'sManagerMobile' => $user['Username'],
                ];
               $accountId = Db::table('Account_copy1')->insertGetId($accountData);
            } else {
                $accountId = $s['lId'];
            }

            $userList = Db::table('CompanyAccount')
                ->where(['CompanyId' => $val['Id']])
                ->select();

            foreach ($userList as $k => $v) {
                $u = Db::table('SysUser_copy1')->where(['sLoginName' => $v['Username']])->find();
                $userData = [
                    'sName' => $v['Username'],
                    'AccountId' => $accountId,
                    'dNewTime' => datetime(time()),
                    'dEditTime' => datetime(time()),
                    'SysRoleId' => 5,
                    'sLoginName' => $v['Username'],
                    'sPassword' => '123456',
                    'sMobile' => $v['Username'],
                    'SysSolutionId' => 3,
                    'bActive' => 1,
                    'bMain' => 0,
                ];

                if (empty($u)){
                    $userId = Db::table('SysUser_copy1')->insertGetId($userData);
                }else{
                   Db::table('SysUser_copy1')->where(['sLoginName' => $v['Username']])->update($userData);
                    $userId = $u['lID'];
                }



                if ($v['PurviewAdmin'] == 1){
                    Db::table('SysUser_copy1')->where(['sLoginName' => $v['Username']])->update(['SysRoleId' => 4 ,'bMain' => 1]);
                    Db::table('Account_copy1')->where(['lId' => $accountId])->update(['ManagerUserId' => $userId ]);
                }
            }
        }


        exit_data(1111);
    }

}
