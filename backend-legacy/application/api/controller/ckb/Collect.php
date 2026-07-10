<?php

namespace app\api\controller\ckb;

use app\common\controller\Api;
use think\Db;

class Collect extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function postdata()
    {
        if ($this->request->isPost()) {
            $key = $this->request->post('key', '');
            $mobile = $this->request->post('mobile', '');
            $labels = $this->request->post('labels', '');
            if (empty($key) || empty($mobile)) {
                $this->error('参数缺失');
            }

            $task = Db::table('WechatFriendRequestTask')
                ->field('Id,Guid,Name,CompanyId')
                ->where(['Guid' => $key])
                ->find();

            if (empty($task)) {
                return json_encode(errorJson('该链接不存在'), 256);
            }
            if (!empty($labels)) {
                $labels = explode(',', $labels);
                $labels = json_encode($labels, 256);
            }



            $user = Db::table('WechatFriendRequestItem')
                ->where(['WechatFriendRequestTaskId' => $task['Id'], 'PhoneOrWechatId' => $mobile])
                ->whereIn('Status',[0,1,2,4])
                ->find();


            if (empty($user)){
                $sqlData = array(
                    'WechatFriendRequestTaskId' => $task['Id'],
                    'CompanyId' => $task['CompanyId'],
                    'PhoneOrWechatId' => $mobile,
                    'CreatedTime' => date('Y-m-d H:i:s'),
                    'Labels' => $labels,
                );
                $res = Db::table('WechatFriendRequestItem')->insertGetId($sqlData);
                if (!empty($res)) {
                    $this->success('成功');
                } else {
                    $this->error('系统繁忙，请稍后再试');
                }
            }else{
                $this->success('该用户已经存在');
            }


        } else {
            $this->error('非法访问');
        }

    }
}
