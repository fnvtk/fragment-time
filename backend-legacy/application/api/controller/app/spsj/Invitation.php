<?php


namespace app\api\controller\app\spsj;


use app\common\controller\Api;
use OSS\OssClient;
use think\Db;
use EasyWeChat\Factory;


class Invitation extends Api
{
    use OssHttpsTrait;
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $appid = '';
    protected $app;
    protected $config;
    protected $wechatApp;

    public function _initialize()
    {
        parent::_initialize();
        $this->appid = input('appId');
        if (empty($this->appid)) {
            $this->error('重要参数缺失');
        }


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
     * 获取邀请者基础信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserData()
    {
        if ($this->request->isPost()) {
            $invitationCode = $this->request->post('invitationCode', '');
            if (!empty($invitationCode)) {
                $user = Db::table('WechatApp_fans')
                    ->field('id,nickName,avatarUrl,invitationCode')
                    ->where(array('invitationCode' => $invitationCode))
                    ->find();
                if (empty($user)) {
                    $this->error('邀请码有误');
                } else {
                    // 转换 OSS 链接为 HTTPS
                    $user = $this->convertOssToHttps($user);
                    $this->success('获取成功', $user);
                }
            } else {
                $this->error('邀请码为空');
            }
        } else {
            $this->error('非法访问');
        }
    }
}