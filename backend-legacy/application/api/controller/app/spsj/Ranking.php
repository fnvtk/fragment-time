<?php


namespace app\api\controller\app\spsj;


use app\common\controller\Api;
use EasyWeChat\Factory;
use think\Db;

class Ranking extends Api
{
    use OssHttpsTrait;
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $appid = input('appid');
        $this->loginType = input('loginType', 'wechat');
        if (empty($appid)) {
            $this->error('重要参数缺失');
        }

    }


    public function projectList()
    {
        if ($this->request->isPost()) {
            $list = Db::table('WechatApp_project')
                ->where(array('status' => 1))
                ->order('id DESC')
                ->select();

            // 转换 OSS 链接为 HTTPS
            $list = $this->convertOssToHttps($list);
            $this->success('获取成功', $list);
        } else {
            $this->error('非法访问');
        }
    }


    public function rankingList()
    {
        if ($this->request->isPost()) {
            $appid = 1;
            $projectId = $this->request->post('projectId');
            $userId = $this->request->post('uid', 1);
            $companyId = input('companyId', 0);

            // 获取配置
            if (!empty($companyId)) {
                $setting = Db::table('WechatApp_setting')
                    ->where(['appId' => $appid, 'companyId' => $companyId])
                    ->find();
            } else {
                $setting = Db::table('WechatApp')
                    ->where('id', $appid)
                    ->find();
            }

            // 计算时间范围
            $day = 3650;
            $today = strtotime(date('Y-m-d H:i:s'));
            $startTime = date('Y-m-d H:i:s', $today - 86400 * $day);
            $endTime = date('Y-m-d H:i:s', $today);

            // 构建条件（添加表别名 b 避免字段歧义）
            $whereStr = "b.companyId = {$companyId}";
            if (!empty($projectId)) {
                $whereStr .= " AND b.projectId = {$projectId}";
            }

            $myRanking = [];
            
            if ($setting['showTaskMoney']) {
                // 按金额排行 - 一次性查询所有数据
                $sql = "SELECT 
                    b.uid,
                    SUM(b.money) as sumCount,
                    SUM(CASE WHEN b.type = 1 THEN b.money ELSE 0 END) as money,
                    f.openid,
                    f.nickName,
                    f.mobile,
                    f.avatarUrl
                FROM WechatApp_bill b
                LEFT JOIN WechatApp_fans f ON b.uid = f.id
                WHERE b.type = 1 AND {$whereStr}
                    AND b.createTime BETWEEN '{$startTime}' AND '{$endTime}'
                GROUP BY b.uid
                ORDER BY sumCount DESC";
                
                $user = Db::query($sql);
                
                // 计算利润
                foreach ($user as $key => &$val) {
                    if ($val['money'] >= 100) {
                        $val['profit'] = intval($val['money'] / 100) * 100 * 5.5;
                    } else {
                        $val['profit'] = intval($val['money'] / 10) * 10 * 5.5;
                    }
                    
                    // 处理用户信息
                    $val['phone'] = !empty($val['mobile']) ? $this->substrCut($val['mobile']) : '-';
                    $val['nickName'] = !empty($val['nickName']) ? $this->substrCut($val['nickName']) : '-';
                    $val['avatarUrl'] = $val['avatarUrl'] ?: '-';
                    $val['openid'] = $val['openid'] ?: '-';
                    unset($val['mobile']);
                }
                unset($val);
                
            } else {
                // 按任务数排行 - 一次性查询所有数据
                $sql = "SELECT 
                    tr.uid,
                    COUNT(*) as sumCount,
                    f.openid,
                    f.nickName,
                    f.mobile,
                    f.avatarUrl
                FROM WechatApp_taskReceive tr
                LEFT JOIN WechatApp_fans f ON tr.uid = f.id
                WHERE tr.status = 2
                    AND tr.createTime BETWEEN '{$startTime}' AND '{$endTime}'
                GROUP BY tr.uid
                ORDER BY sumCount DESC";
                
                $user = Db::query($sql);
                
                // 处理用户信息
                foreach ($user as $key => &$val) {
                    $val['phone'] = !empty($val['mobile']) ? $this->substrCut($val['mobile']) : '-';
                    $val['nickName'] = !empty($val['nickName']) ? $this->substrCut($val['nickName']) : '-';
                    $val['avatarUrl'] = $val['avatarUrl'] ?: '-';
                    $val['openid'] = $val['openid'] ?: '-';
                    unset($val['mobile']);
                }
                unset($val);
            }

            // 添加排名并找到当前用户
            foreach ($user as $k => &$v) {
                $v['ranking'] = $k + 1;
                if ($v['uid'] == $userId) {
                    $myRanking = $v;
                }
            }
            unset($v);
            
            // 只返回前10名
            $user = array_slice($user, 0, 10);
            
            $data = [
                'list' => $user,
                'myRanking' => $myRanking,
            ];
            
            // 转换 OSS 链接为 HTTPS
            $data = $this->convertOssToHttps($data);
            $this->success('获取成功', $data);
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


}