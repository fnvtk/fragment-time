<?php


namespace app\api\controller\app\spsj;


use app\common\controller\Api;
use OSS\OssClient;
use think\Db;
use EasyWeChat\Factory;

class User extends Api
{
    use OssHttpsTrait;
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $DB;
    protected $config;
    protected $app;
    protected $wechatApp = [];
    protected $loginType;
    protected $appid;
    protected $oss;
    protected $test;


    public function _initialize()
    {
        parent::_initialize();
        $this->appid = input('appId',input('appid',''));
        $this->test = input('test',1);
        $this->loginType = input('loginType', 'wechat');
        if (empty($this->appid) && empty($this->test)) {
            $this->error('重要参数缺失');
        }
        $this->oss = [
            'bucket' => 'karuosiyujzk',
            'region' => 'oss-cn-shenzhen.aliyuncs.com',
            'accessKeyId' => getenv('ALIYUN_OSS_ACCESS_KEY_ID') ?: '',
            'accessKeySecret' => getenv('ALIYUN_OSS_ACCESS_KEY_SECRET') ?: ''
        ];

        $this->wechatApp = Db::table('WechatApp')
            ->where(array('appId' => $this->appid))
            ->find();
        $this->config = [
            'app_id' => $this->wechatApp['appId'],
            'secret' => $this->wechatApp['appSecret'],
            'response_type' => 'array',
        ];
        $this->app = Factory::miniProgram($this->config);
    }


    /**
     * 用户微信登录
     */
    public function login()
    {
        if ($this->request->isPost()) {
            //手机号登录
            if ($this->loginType == 'phone') {
                $code = $this->request->post('code', '');
                $appId = $this->request->post('appId', '');
                $iv = $this->request->post('iv', '');
                $companyId = $this->request->post('companyId', 0);
                $encryptedData = $this->request->post('encryptedData', '');
                $invitationCode = $this->request->post('invitationCode', '');
                $invitationId = $this->request->post('invitationId', 0);

                if (!empty($invitationCode) && !empty($invitationId)) {
                    $invitationUserDate = Db::table('WechatApp_fans')
                        ->where(array('id' => $invitationId, 'invitationCode' => $invitationCode))
                        ->find();
                    if (empty($invitationUserDate)) {
                        $invitationId = 0;
                        $companyId = 0;
                    } else {
                        $invitationId = $invitationUserDate['id'];
                        $companyId = $invitationUserDate['companyId'];
                    }
                }

                $wechat = $this->app->auth->session($code);
                $mobile = $this->wechatDecrypt($wechat['session_key'], $iv, $encryptedData);
                //登录
                if (!empty($wechat['openid'])) {
                    $userDate = Db::table('WechatApp_fans')
                        ->where(array('openid' => $wechat['openid'], 'mobile' => $mobile['phoneNumber'], 'companyId' => $companyId))
                        ->find();
                    if (empty($userDate)) {
                        $sql_data = [
                            'appid' => $this->wechatApp['id'],
                            'mobile' => $mobile['phoneNumber'],
                            'openid' => $wechat['openid'],
                            'createTime' => date('Y-m-d H:i:s'),
                            'updataTime' => date('Y-m-d H:i:s'),
                            'invitationId' => $invitationId,
                            'companyId' => $companyId,
                            'password' => md5('123456'),
                        ];
                        $res = Db::table('WechatApp_fans')->insertGetId($sql_data);
                        //生成邀请码
                        $this->getInvitationCode($res);
                    } else {
                        $res = Db::table('WechatApp_fans')
                            ->where(array('openid' => $wechat['openid']))
                            ->update(['mobile' => $mobile['phoneNumber'], 'updataTime' => date('Y-m-d H:i:s')]);
                        $userDate['id'];
                    }
                }
                //获取用户最新信息
                $userDate = $this->getUserInfo($appId, $mobile['phoneNumber'], $companyId);
                // 转换 OSS 链接为 HTTPS
                $userDate = $this->convertOssToHttps($userDate);
                $this->success('操作成功', $userDate);
            } else if ($this->loginType == 'wechat') {
                $appId = $this->request->post('appId', '');
                $mobile = $this->request->post('mobile', '');
                $openid = $this->request->post('openid', '');
                $companyId = $this->request->post('companyId', 0);
                if (!$mobile && !$openid) {
                    $this->error('参数缺失');
                }

                //登录
                $userDate = $this->getUserInfo($appId, $mobile, $companyId);
                // 转换 OSS 链接为 HTTPS
                $userDate = $this->convertOssToHttps($userDate);
                $this->success('获取成功', $userDate);
            } else {
                $this->success('账号登录');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 密码登录
     */
    public function pwdlogin()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username', '');
            $password = $this->request->post('password', '');
            if (empty($username) || empty($password)) {
                $this->error('用户名及密码不能为空');
            }

            $userDate = Db::table('WechatApp_fans')
                ->where(array('mobile' => $username, 'password' => md5($password)))
                ->find();
            if (empty($userDate)) {
                $this->error('用户名或者密码错误');
            } else {
                $userDate = $this->getUserInfo($this->appid, $userDate['mobile'], $userDate['companyId']);
                // 转换 OSS 链接为 HTTPS
                $userDate = $this->convertOssToHttps($userDate);
                $this->success('登录成功', $userDate);
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 手机号注册
     */
    public function pwdRegister()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username', '');
            $nickName = $this->request->post('nickName', '');
            $password = $this->request->post('password', '');
            $password2 = $this->request->post('password2', '');
            if (empty($username) || empty($nickName) || empty($password) || empty($password2)) {
                $this->error('手机号、昵称、密码、确认密码不能为空！');
            }
            if ($password != $password2) {
                $this->error('两次输入的密码不一致');
            }

            $userDate = Db::table('WechatApp_fans')
                ->where(array('mobile' => $username))
                ->find();
            if (!empty($userDate)) {
                $this->error('该手机号已注册请直接登录');
            } else {
                $sql_data = array(
                    'avatarUrl' => '../../static/avatar.png',
                    'appid' => $this->wechatApp['id'],
                    'nickName' => $nickName,
                    'mobile' => $username,
                    'gender' => 0,
                    'createTime' => date('Y-m-d H:i:s'),
                    'password' => md5($password),
                );
                $res = Db::table('WechatApp_fans')->insertGetId($sql_data);
                //生成邀请码
                $this->getInvitationCode($res);
                if (!empty($res)) {
                    $userDate = Db::table('WechatApp_fans')
                        ->where(array('id' => $res))
                        ->find();
                    // 转换 OSS 链接为 HTTPS
                    $userDate = $this->convertOssToHttps($userDate);
                    $this->success('注册成功', $userDate);
                } else {
                    $this->error('注册失败请稍后再试~');
                }


            }
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 提现单创建超过 24 小时仍为待审核(0)或待领取(3)时，自动退回余额（账单 type=2，与后台驳回一致）
     * @param int $uid
     * @return bool 是否发生了退回
     */
    private function revertExpiredWithdrawalsForUser($uid)
    {
        if (empty($uid)) {
            return false;
        }
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
        $candidates = Db::table('WechatApp_withdraw')
            ->where('uid', $uid)
            ->whereIn('status', [0, 3])
            ->where('createTime', '<', $cutoff)
            ->select();
        if (empty($candidates)) {
            return false;
        }
        $changed = false;
        foreach ($candidates as $w) {
            Db::startTrans();
            try {
                $row = Db::table('WechatApp_withdraw')->where('id', $w['id'])->lock(true)->find();
                if (empty($row) || !in_array((int) $row['status'], [0, 3], true)) {
                    Db::rollback();
                    continue;
                }
                if (strtotime($row['createTime']) >= time() - 86400) {
                    Db::rollback();
                    continue;
                }
                $n = Db::table('WechatApp_withdraw')
                    ->where('id', $row['id'])
                    ->whereIn('status', [0, 3])
                    ->update([
                        'status' => 2,
                        'reason' => '提现单超过24小时未处理/未领取，余额已自动退回',
                        'updataTime' => date('Y-m-d H:i:s'),
                    ]);
                if (!$n) {
                    Db::rollback();
                    continue;
                }
                $userInfo = Db::table('WechatApp_fans')->where('id', $row['uid'])->lock(true)->find();
                if (empty($userInfo)) {
                    Db::rollback();
                    continue;
                }
                Db::table('WechatApp_fans')->where('id', $row['uid'])->setInc('balance', $row['money']);
                $newBal = floatval($userInfo['balance']) + floatval($row['money']);
                $bill = [
                    'projectId' => isset($userInfo['projectId']) ? $userInfo['projectId'] : 0,
                    'uid' => $row['uid'],
                    'type' => 2,
                    'money' => $row['money'],
                    'balance' => $newBal,
                    'createTime' => date('Y-m-d H:i:s'),
                    'sourceId' => $row['id'],
                    'explain' => '提现超时自动退回',
                ];
                if (isset($row['companyId'])) {
                    $bill['companyId'] = $row['companyId'];
                }
                Db::table('WechatApp_bill')->insert($bill);
                Db::commit();
                $changed = true;
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
        return $changed;
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($appId = null, $mobile = null, $companyId = 0)
    {
        $appId = $this->request->post('appid', $appId);
        $mobile = $this->request->post('mobile', $mobile);
        $fanWhere = array('fans.mobile' => $mobile, 'app.appid' => $appId, 'fans.companyId' => $companyId);
        $userInfo = Db::table('WechatApp_fans')->alias('fans')
            ->field('fans.*')
            ->join('WechatApp app', 'fans.appid=app.id')
            ->where($fanWhere)
            ->find();

        if (empty($userInfo)) {
            if ($this->request->isPost()) {
                $this->error('用户不存在');
            }
            return [];
        }

        if ($this->revertExpiredWithdrawalsForUser($userInfo['id'])) {
            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($fanWhere)
                ->find();
        }

        //计算收入
        $today = strtotime(date('Y-m-d'));
        $userInfo['todayProfit'] = Db::table('WechatApp_bill')
            ->where(array('uid' => $userInfo['id'], 'type' => 1))
            ->whereBetween('createTime', [date('Y-m-d H:i:s', $today), date('Y-m-d H:i:s', $today + 86400)])
            ->sum('money');

        /*   $userInfo['yesterdayProfit'] = Db::table('WechatApp_bill')
               ->where(array('uid' => $userInfo['id'], 'type' => 1))
               ->whereBetween('createTime', [date('Y-m-d H:i:s', $today - 86400), date('Y-m-d H:i:s', $today)])
               ->sum('money');*/
        $userInfo['withdrawing'] = Db::table('WechatApp_withdraw')
            ->where('uid', $userInfo['id'])
            ->whereIn('status', [0, 3])
            ->sum('money');


        $userInfo['todayProfit'] = sprintf("%01.2f", $userInfo['todayProfit']);
//        $userInfo['yesterdayProfit'] = sprintf("%01.2f", $userInfo['yesterdayProfit']);
        $userInfo['withdrawing'] = sprintf("%01.2f", $userInfo['withdrawing']);

        $userInfo['userList'] = $this->getUserList($appId, $userInfo['openid']);
        // 转换 OSS 链接为 HTTPS
        $userInfo = $this->convertOssToHttps($userInfo);
        if ($this->request->isPost()) {
            $this->success('获取成功', $userInfo);
        } else {
            return $userInfo;
        }
    }


    /**
     * 获取所有账号
     * @param null $appId
     * @param null $opendid
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserList($appId = null, $opendid = null)
    {
        $userList = Db::table('WechatApp_fans')->alias('fans')
            ->field('fans.id,fans.nickName,fans.avatarUrl,fans.balance,fans.companyId,c.Name as companyName')
            ->join('Company c', 'fans.companyId=c.Id', 'left')
            ->join('WechatApp app', 'fans.appid=app.id')
            ->where(array('fans.openid' => $opendid, 'app.appid' => $appId))
            ->select();
        return $userList;
    }


    /**
     * 解密微信信息
     * @param string $session
     * @param string $iv
     * @param string $encryptedData
     * @return mixed
     */
    public function wechatDecrypt($session = '', $iv = '', $encryptedData = '')
    {

        if (!empty($session) && !empty($iv) && !empty($encryptedData)) {
            $decryptedData = $this->app->encryptor->decryptData($session, $iv, $encryptedData);
            return $decryptedData;
        } else {
            $this->error('缺失');
        }
    }


    /**
     * 用户信息编辑
     */
    public function userInfoEdit()
    {
        if ($this->request->isPost()) {
            $uid = $this->request->post('uid');
            $nickName = $this->request->post('nickName');
            $mobile = $this->request->post('mobile');
            $gender = $this->request->post('gender');
            $avatarUrl = $this->request->post('avatarUrl');
            $birthday = $this->request->post('birthday');
            $password = $this->request->post('password');

            if (empty($uid)) {
                $this->error('重要参数缺失');
            }
            if (empty($nickName) || empty($mobile) || empty($avatarUrl) || empty($birthday)) {
                $this->error('请完善所有信息');
            }

            $violationData = '首个,首选,全球首发,全国首家,全网首发,首款,首家,独家,独家配方,全国销量冠军,国家级产品,国家,国家免检,国家领导人,填补国内空白,国家级,世界级,第一,唯一,精确,顶级,最,最佳,最具,最爱,最赚,最优,最优秀,最好,最大,最大程度,最高,最高档,最高级,最奢侈,最低,最低级,最低价,最底,最便宜,时尚最低价,最流行,最受欢迎,最时尚,最符合,最舒适,最先,最先进,最先进科学,最先进加工工艺,最先享受,最后,最后一波,最新,最新科技,最新科学,填补国内空白,绝对,第一品牌,金牌,名牌,巨星,奢侈,至尊,顶级享受,处方,复方,解毒,祖传,秘制,强力,特效,全效,强效,奇效,高效,速效,神效,神丹,神仙,抗敏,防敏,脱敏,斑立净,无斑,生发,毛发再生,止脱,溶脂,吸脂,NO.1,TOP.1,独一无二,一流';


            $userInfo = Db::table('WechatApp_fans')
                ->where(array('id' => $uid, 'appid' => $this->wechatApp['id']))
                ->find();
            if (empty($userInfo)) {
                $this->error('用户不存在');
            }
            $sql_data = array(
                'nickName' => $nickName,
                'gender' => $gender,
                'updataTime' => date('Y-m-d H:i:s'),
            );

            //头像
            if ($avatarUrl == '../../static/avatar.jpg') {
                $this->error('请上传头像');
            } else {
                $sql_data['avatarUrl'] = $avatarUrl;
            }

            //生日
            if ($birthday == '0000-00-00') {
                $this->error('请选择您是生日');
            } else {
                $sql_data['birthday'] = $birthday;
            }

            //密码
            if (!empty($password)) {
                $sql_data['password'] = md5($password);
            }


            //手机号
            if ($mobile != $userInfo['mobile']) {
                $user = Db::table('WechatApp_fans')
                    ->where(array('mobile' => $mobile, 'appid' => $this->wechatApp['id']))
                    ->whereNotIn('id', $uid)
                    ->find();
                if (!empty($user)) {
                    $this->error('该手机号已经被其他用户绑定，请重新输入手机号~');
                } else {
                    $sql_data['mobile'] = $mobile;
                }
            }
            $res = Db::table('WechatApp_fans')
                ->where(array('id' => $uid, 'appid' => $this->wechatApp['id']))
                ->update($sql_data);
            if (!empty($res)) {
                $userInfo = Db::table('WechatApp_fans')
                    ->where(array('id' => $uid, 'appid' => $this->wechatApp['id']))
                    ->find();
                // 转换 OSS 链接为 HTTPS
                $userInfo = $this->convertOssToHttps($userInfo);
                $this->success('资料修改成功~', $userInfo);
            } else {
                $this->error('资料修改失败请稍后再试~');
            }
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 申请提现
     */
    public function withdrawal()
    {
        if ($this->request->isPost()) {
            $appId = $this->request->post('appid', '');
            $openid = $this->request->post('openid', '');
            $money = $this->request->post('money', '');

            $companyId = $this->request->post('companyId', 0);
            $where = [];
            $where['fans.companyId'] = $companyId;
            $where['fans.openid'] = $openid;
            $where['app.appid'] = $appId;


            if ($money <= 0) {
                $this->error('请输入金额');
            }

            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*,app.id as aid')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($where)
                ->find();
            if (empty($userInfo)) {
                $this->error('用户不存在');
            }
            $this->revertExpiredWithdrawalsForUser($userInfo['id']);
            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*,app.id as aid')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($where)
                ->find();
            if ($userInfo['balance'] < $money) {
                $this->error('最高提现金额为:' . $userInfo['balance'] . '元');
            }
            
            //新增提现记录
            $sql_data = [
                'uid' => $userInfo['id'],
                'status' => 0,
                'money' => $money,
                'balance' => $userInfo['balance'] - $money,
                'companyId' => $companyId,
                'createTime' => date('Y-m-d H:i:s'),
                'updataTime' => date('Y-m-d H:i:s'),
            ];
            $res = Db::table('WechatApp_withdraw')->insertGetId($sql_data);
            if ($res) {
                //新增金额变动记录
                $sql_data2 = [
                    'uid' => $userInfo['id'],
                    'type' => -2,
                    'money' => $money,
                    'balance' => $userInfo['balance'] - $money,
                    'createTime' => date('Y-m-d H:i:s'),
                    'companyId' => $companyId,
                    'sourceId' => $res,
                    'explain' => '用户发起提现',
                ];
                Db::table('WechatApp_bill')->insertGetId($sql_data2);
                //改变余额
                Db::table('WechatApp_fans')->where(array('id' => $userInfo['id']))->setDec('balance', $money);
                
                // 检查是否开启免审核提现
                $autoApproved = $this->checkAutoApproval($res, $userInfo, $money, $openid, $companyId);
                
                if ($autoApproved) {
                    $this->success('提现申请已自动审核，正在处理中~');
                } else {
                    $this->success('提现申请成功~');
                }
            } else {
                $this->error('网络繁忙请稍后在试~');
            }
        } else {
            $this->error('非法访问');
        }
    }
    
    /**
     * 检查并执行免审核提现
     * @param int $withdrawId 提现记录ID
     * @param array $userInfo 用户信息
     * @param float $money 提现金额
     * @param string $openid 用户openid
     * @param int $companyId 公司ID
     * @return bool 是否自动审核通过
     */
    private function checkAutoApproval($withdrawId, $userInfo, $money, $openid, $companyId)
    {
        try {
            // 获取微信支付配置
            $wechatApp = Db::table('WechatApp')->where('id', $userInfo['aid'])->find();
            if (empty($wechatApp) || empty($wechatApp['pay'])) {
                return false;
            }
            
            $payConfig = json_decode($wechatApp['pay'], true);
            if (empty($payConfig)) {
                return false;
            }
            
            // 检查是否开启免审核（处理字符串类型的布尔值）
            $autoApproveEnabled = $payConfig['auto_approve_enabled'] ?? false;
            // 兼容字符串 "true" 和布尔值 true
            if ($autoApproveEnabled === 'true' || $autoApproveEnabled === true) {
                $autoApproveEnabled = true;
            } else {
                $autoApproveEnabled = false;
            }
            
            if (!$autoApproveEnabled) {
                return false;
            }
            
            // 检查单笔限额
            $singleMax = floatval($payConfig['single_withdraw_max'] ?? 0);
            if ($singleMax > 0 && $money > $singleMax) {
                return false;
            }
            
            // 检查当日提现次数和金额
            $today = date('Y-m-d 00:00:00');
            $todayWithdrawals = Db::table('WechatApp_withdraw')
                ->where('uid', $userInfo['id'])
                ->where('createTime', '>=', $today)
                ->where('id', '<>', $withdrawId)  // 排除当前这笔
                ->where('status', 'in', [1, 3])  // 只统计已通过的（1:已领取, 3:待领取）
                ->select();
            
            $todayCount = count($todayWithdrawals);
            $todayAmount = array_sum(array_column($todayWithdrawals, 'money'));
            
            // 检查次数限制
            $dailyCountLimit = intval($payConfig['daily_withdraw_count'] ?? 0);
            if ($dailyCountLimit > 0 && $todayCount >= $dailyCountLimit) {
                return false;
            }
            
            // 检查金额限制
            $dailyAmountLimit = floatval($payConfig['daily_withdraw_amount'] ?? 0);
            if ($dailyAmountLimit > 0 && ($todayAmount + $money) > $dailyAmountLimit) {
                return false;
            }
            
            // 符合条件，执行自动打款
            return $this->processAutoWithdrawal($withdrawId, $userInfo, $money, $openid, $payConfig, $companyId);
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 执行自动提现打款
     * @param int $withdrawId 提现记录ID
     * @param array $userInfo 用户信息
     * @param float $money 提现金额
     * @param string $openid 用户openid
     * @param array $payConfig 支付配置
     * @param int $companyId 公司ID
     * @return bool 是否成功
     */
    private function processAutoWithdrawal($withdrawId, $userInfo, $money, $openid, $payConfig, $companyId)
    {
        try {
            // 检查必要配置
            if (empty($payConfig['mch_id']) || empty($payConfig['api_v3_key']) || 
                empty($payConfig['private_key']) || empty($payConfig['cert_serial_no'])) {
                return false;
            }
            
            // 生成商户单号
            $outBillNo = 'TX' . date('YmdHis') . mt_rand(1000, 9999) . $withdrawId;
            
            // 获取小程序appid
            $wechatApp = Db::table('WechatApp')->where('id', $userInfo['aid'])->find();
            $appid = $wechatApp['appId'] ?? '';
            
            // 构建微信支付配置
            $wechatPayConfig = [
                'mch_id' => $payConfig['mch_id'],
                'app_id' => $appid,
                'api_v3_key' => $payConfig['api_v3_key'],
                'private_key' => $payConfig['private_key'],
                'cert_serial_no' => $payConfig['cert_serial_no'],
            ];
            
            // 实例化微信支付类（同命名空间下的类）
            $wechatPay = new WechatPayTransfer($wechatPayConfig);
            
            // 准备转账参数
            $transferSceneId = $payConfig['transfer_scene_id'] ?? '1005';
            $promotionName = $payConfig['promotion_name'] ?? '兼职人员';
            $rewardDesc = $payConfig['reward_desc'] ?? '当日兼职费';
            
            $transferSceneReportInfos = [
                [
                    'info_type' => '岗位类型',
                    'info_content' => $promotionName,
                ],
                [
                    'info_type' => '报酬说明',
                    'info_content' => $rewardDesc,
                ],
            ];
            
            $transferParams = [
                'out_bill_no' => $outBillNo,
                'openid' => $openid,
                'transfer_amount' => intval($money * 100),  // 转为分
                'transfer_remark' => $rewardDesc,
                'appid' => $appid,
                'transfer_scene_id' => $transferSceneId,
                'transfer_scene_report_infos' => $transferSceneReportInfos,
            ];
            
            // 调用转账接口
            $result = $wechatPay->createTransfer($transferParams);
            
            // 处理结果
            if (isset($result['success']) && $result['success']) {
                $data = $result['data'] ?? [];
                
                // 更新提现记录
                Db::table('WechatApp_withdraw')->where('id', $withdrawId)->update([
                    'status' => 3,  // 待领取
                    'out_bill_no' => $outBillNo,
                    'transfer_bill_no' => $data['transfer_bill_no'] ?? '',
                    'mch_id' => $payConfig['mch_id'],
                    'wechat_pay_state' => $data['state'] ?? 'PROCESSING',
                    'package_info' => $data['package_info'] ?? '',
                    'pay_type' => 'wechat',
                    'updataTime' => date('Y-m-d H:i:s'),
                ]);
                
                return true;
            } else {
                return false;
            }
            
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * 用户金额变动
     */
    public function getUserBill()
    {
        if ($this->request->isPost()) {
            $appId = $this->request->post('appId', '');
            $openid = $this->request->post('openid', '');
            $page = $this->request->post('page', 1);
            $companyId = $this->request->post('companyId', 0);
            $where = [];
            $where['fans.companyId'] = $companyId;
            $where['fans.openid'] = $openid;
            $where['app.appid'] = $appId;


            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*,app.id as aid')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($where)
                ->find();
            if (empty($userInfo)) {
                $this->error('该用户不存在');
            }
            $this->revertExpiredWithdrawalsForUser($userInfo['id']);
            $list = Db::table('WechatApp_bill')
                ->where(array('uid' => $userInfo['id']))
                ->order('id DESC')
                ->page($page, 10)
                ->select();

            foreach ($list as $key => &$val) {
                if ($val['type'] == 1) {
                    $val['typeTxt'] = '任务收益';
                    if (!empty($val['explain'])) {
                        $val['typeTxt'] = $val['typeTxt'] . '（' . $val['explain'] . '）';
                    }
                } elseif ($val['type'] == 3) {
                    $val['typeTxt'] = '添加收益';
                    if (!empty($val['explain'])) {
                        $val['typeTxt'] = $val['typeTxt'] . '（' . $val['explain'] . '）';
                    }
                } elseif ($val['type'] == 2) {
                    $val['typeTxt'] = '提现驳回';
                } elseif ($val['type'] == -2) {
                    $val['typeTxt'] = '提现申请';
                }
            }
            unset($val);

            // 转换 OSS 链接为 HTTPS
            $list = $this->convertOssToHttps($list);
            $this->success('获取成功', $list);
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 用户金额变动
     */
    public function getUserWithdraw()
    {
        if ($this->request->isPost()) {
            $appId = $this->request->post('appid', '');
            $openid = $this->request->post('openid', '');
            $page = $this->request->post('page', 1);
            $companyId = $this->request->post('companyId', 0);
            $where = [];
            $where['fans.companyId'] = $companyId;
            $where['fans.openid'] = $openid;
            $where['app.appid'] = $appId;


            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*,app.id as aid')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($where)
                ->find();
            if (empty($userInfo)) {
                $this->error('该用户不存在');
            }
            $this->revertExpiredWithdrawalsForUser($userInfo['id']);
            $list = Db::table('WechatApp_withdraw')
                ->where(array('uid' => $userInfo['id']))
                ->order('id DESC')
                ->page($page, 10)
                ->select();

            // 转换 OSS 链接为 HTTPS
            $list = $this->convertOssToHttps($list);
            $this->success('获取成功', $list);
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 生成邀请码
     * @param null $fid
     * @return array|false|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getInvitationCode($fid = null)
    {
        if (empty($fid)) {
            return errorJson('参数缺失');
        }
        $fans = Db::table('WechatApp_fans')
            ->field('id,openid,nickName,invitationCode')
            ->where(array('id' => $fid))
            ->find();
        if (empty($fans)) {
            return errorJson('该用户不存在');
        }

        if (!empty($fans['invitationCode'])) {
            return successJson('邀请码获取成功', $fans['invitationCode']);
        } else {
            $code = createNoncestr(10);
            $codeData = Db::table('WechatApp_fans')
                ->field('invitationCode')
                ->where(array('invitationCode' => $code))
                ->find();
            if (!empty($codeData)) {
                return $this->getInvitationCode($fid);
            } else {
                $sql_data = [
                    'invitationCode' => $code
                ];
                $res = Db::table('WechatApp_fans')->where('id', $fans['id'])->update($sql_data);
                if (!empty($res)) {
                    return successJson('邀请码生成成功', $code);
                } else {
                    return $this->getInvitationCode($fid);
                }
            }
        }
    }


    /**
     * 生成海报
     * @return false|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \OSS\Core\OssException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getPoster()
    {
        if ($this->request->isPost()) {

            $uid = $this->request->post('uid', '');
            $appid = $this->request->post('appid', '');
            $openid = $this->request->post('openid', '');
            $companyId = $this->request->post('companyId', 0);
            if (empty($openid)) {
                return json_encode(successJson('参数缺失'), 256);
            }

            $where = [];
            $where['fans.openid'] = $openid;
            $where['fans.companyId'] = $companyId;
            $userInfo = Db::table('WechatApp_fans')->alias('fans')
                ->field('fans.*,app.id as aid')
                ->join('WechatApp app', 'fans.appid=app.id')
                ->where($where)
                ->find();
            if (empty($userInfo)) {
                $this->error('该用户不存在');
            }

            if (empty($userInfo['invitationCode'])) {
                $invitationCode = $this->getInvitationCode($userInfo['id']);
                if ($invitationCode['code'] == 200) {
                    $userInfo['invitationCode'] = $invitationCode['data'];
                }
            }


            $setting = null;
            
            // 如果有公司ID，优先查找公司专属配置
            if (!empty($companyId)) {
                $setting = Db::table('WechatApp_setting')
                    ->where(['appId' => $appid, 'companyId' => $companyId])
                    ->find();
            }
            
            // 如果 WechatApp_setting 没有找到，使用 WechatApp 的全局配置
            if (empty($setting)) {
                $setting = $this->wechatApp;
            }
            
            $distribution = json_decode($setting['distribution'], true);
            
            // 检查海报配置是否存在
            if (empty($distribution['poster']['posterImg'])) {
                $this->error('海报背景图未配置，请在后台配置后再试');
            }
            
            $posterImg = $distribution['poster']['posterImg'];
            $backgroundInfo = getimagesize($posterImg);
            $backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
            $background = $backgroundFun($posterImg);
            $backgroundWidth = imagesx($background);  //背景宽度
            $backgroundHeight = imagesy($background);  //背景高度
            $w_bl = $backgroundWidth / 450;
            $h_bl = $backgroundHeight / 667;


            //生成二维码
            $qrCode = $this->getCode('pages/userCenter/index', $userInfo['invitationCode']);
            $qrCode = json_decode($qrCode, true);
            
            // 获取二维码配置，提供默认值
            $qrCodeLeft = isset($distribution['poster']['qrCodeLeft']) ? floatval($distribution['poster']['qrCodeLeft']) : 0;
            $qrCodeTop = isset($distribution['poster']['qrCodeTop']) ? floatval($distribution['poster']['qrCodeTop']) : 0;
            $qrCodeWidth = isset($distribution['poster']['qrCodeWidth']) ? floatval($distribution['poster']['qrCodeWidth']) : 150;
            $qrCodeHeight = isset($distribution['poster']['qrCodeHeight']) ? floatval($distribution['poster']['qrCodeHeight']) : 150;
            
            $imgs[] = array(
                'url' => $qrCode['data'],
                'left' => $qrCodeLeft * $w_bl,
                'top' => $qrCodeTop * $h_bl,
                'right' => 0,
                'stream' => 0,
                'bottom' => 0,
                'width' => $qrCodeWidth * $h_bl,
                'height' => $qrCodeHeight * $h_bl,
                'opacity' => 100
            );

            $config = array(
                'image' => $imgs,
                'background' => $posterImg,
            );

            //海报路径
            $posters_name = md5(time() . uniqid()) . '.png';
            $posters_src = $this->request->root(true) . '/uploads/posters/' . $posters_name; //访问地址
            $posters_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/posters/';//上传地
            og_mkdirs($posters_path); //创建文件
            $posters_path = $posters_path . $posters_name;   //实际的上传地址
            $this->createPoster($config, $posters_path);


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
            //文件上传成功到服务器后再将文件上传到阿里云oss
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $re = $ossClient->uploadFile($bucket, $object . $posters_name, $posters_path);
            if ($re["info"]["http_code"] == 200) {
                //上传阿里云成功之后删除自己服务器中
                $local_file = $posters_path;
                if (file_exists($local_file)) {
                    unlink($local_file);
                }
                $posters_src = $re["info"]['url'];
            }

            // 转换 OSS 链接为 HTTPS（统一处理，避免重复协议）
            $posters_src = $this->convertOssToHttps($posters_src);
            $this->success('海报生成成功', $posters_src);
        } else {
            return successJson('非法访问');
        }
    }


    /**
     * 生成海报
     * @param array $config
     * @param string $filename
     * @return bool|string
     */
    function createPoster($config = array(), $filename = "")
    {
        //如果要看报什么错，可以先注释调这个header
        if (empty($filename)) header("content-type: image/png");
        $imageDefault = array(
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'width' => 100,
            'height' => 100,
            'opacity' => 100
        );
        $textDefault = array(
            'text' => '',
            'left' => 0,
            'top' => 0,
            'fontSize' => 32,       //字号
            'fontColor' => '0,0,0', //字体颜色
            'angle' => 0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
        $color = imagecolorallocate($imageRes, 255, 255, 255);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));
        //处理了图片
        if (!empty($config['image'])) {
            foreach ($config['image'] as $key => $val) {

                $val = array_merge($imageDefault, $val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                if ($val['stream']) {   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];
                //建立画板 ，缩放图片至指定尺寸
                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);
                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $resWidth, $resHeight);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) - $val['width'] : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) - $val['height'] : $val['top'];
                //放置图像
                imagecopymerge($imageRes, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], 100);//左，上，右，下，宽度，高度，透明度
            }
        }
        //处理文字
        if (!empty($config['text'])) {
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault, $val);
                list($R, $G, $B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];
                imagettftext($imageRes, $val['fontSize'], $val['angle'], $val['left'], $val['top'], $fontColor, $val['fontPath'], $val['text']);
            }
        }
        //生成图片
        $res = imagejpeg($imageRes, $filename, 90);
        imagedestroy($imageRes);
        if (!$res) return false;
        return $filename;
    }


    /**
     * 生成小程序码
     * @return false|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \OSS\Core\OssException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getCode($url = null, $data = null)
    {
        if (empty($url) || empty($data)) {
            $this->error('参数缺失');
        }
        $accessKeyId = $this->oss['accessKeyId'];
        $accessKeySecret = $this->oss['accessKeySecret'];
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = $this->oss['region'];
        $bucket = $this->oss['bucket'];
        //对应阿里云中的目录，以年月日（例如：20210603）为目录名称
        $object = "prt/";
        $response = $this->app->app_code->getUnlimit($data, [
            'page' => $url,
            'width' => 500,
        ]);
        $name = md5(time() . createNoncestr(5));
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $qrCodeUrl = '';
            $fileName = 'qrcode_' . $name . '.png';
            $fileUrl = ROOT_PATH . 'public/uploads/';
            $imgPath = $fileUrl . $fileName;
            $qrCode = $response->saveAs($fileUrl, $fileName);
            if (!empty($qrCode)) {
                //文件上传成功到服务器后再将文件上传到阿里云oss
                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                $re = $ossClient->uploadFile($bucket, $object . $fileName, $imgPath);
                if ($re["info"]["http_code"] == 200) {
                    //上传阿里云成功之后删除自己服务器中
                    $local_file = $imgPath;
                    if (file_exists($local_file)) {
                        unlink($local_file);
                    }
                    $qrCodeUrl = $re["info"]['url'];
                } else {
                    $qrCodeUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/public/uploads/' . $fileName;
                }
            }
            return json_encode(successJson('二维码保存成功', $qrCodeUrl), 256);
        } else {
            return json_encode(errorJson($response), 256);
        }
    }


    public function invitationList()
    {
        $uid = $this->request->post('uid', '168');
        $openid = $this->request->post('openid', '');
        $companyId = $this->request->post('companyId', 0);


        $where = [];
        $where['invitationId'] = $uid;
        $where['companyId'] = $companyId;

        $userInfo = Db::table('WechatApp_fans')
            ->field('id,nickName,avatarUrl,invitationId')
            ->where('id',$uid)
            ->find();


        $list = Db::table('WechatApp_fans')
            ->field('id,nickName,avatarUrl,invitationId')
            ->where($where)
            ->order('id DESC')
            ->select();
        $leve1_num = Db::table('WechatApp_fans')->where($where)->count();
        $leve2_num = 0;
        foreach ($list as $k => &$v) {
            $v['leve'] = '下级';
            $v['invitationUser'] = !empty($userInfo['nickName']) ? $userInfo['nickName'] : '-';
            $v['invitationCount'] = Db::table('WechatApp_fans')->where(['invitationId' => $v['id']])->count();;
            if ($v['invitationCount'] > 0){
                $v['list'] = Db::table('WechatApp_fans')
                    ->field('id,nickName,avatarUrl,invitationId')
                    ->where(['invitationId' => $v['id']])
                    ->order('id DESC')
                    ->select();
                $leve2_num += $v['invitationCount'];
                foreach ($v['list'] as $kk => &$vv) {
                    $vv['leve'] = '下下级';
                    $vv['invitationUser'] = !empty($v['nickName']) ? $v['nickName'] : '-';
                    $vv['invitationCount'] = Db::table('WechatApp_fans')->where(['invitationId' => $vv['id']])->count();;
                }
                unset($vv);
            }





        }
        unset($v);


        $data = [
            'list' => $list,
            'leve1_num' => $leve1_num,
            'leve2_num' => $leve2_num,
        ];
        // 转换 OSS 链接为 HTTPS
        $data = $this->convertOssToHttps($data);
        $this->success('获取成功',$data);
    }
}
