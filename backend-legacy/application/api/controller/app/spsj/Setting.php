<?php

namespace app\api\controller\app\spsj;

use app\common\controller\Api;
use think\Db;

class Setting extends Api
{
    use OssHttpsTrait;

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $DB;

    public function _initialize()
    {
        parent::_initialize();
    }


    public function index()
    {
        $appid = 1;
        $companyId = input('companyId', 0);
        $where = [];
        $where['appId'] = $appid;
        $where['is_show'] = 1;


        if (!empty($companyId)) {
            $setting = Db::table('WechatApp_setting')
                ->where(['appId' => $appid, 'companyId' => $companyId])
                ->find();

            if (empty($setting)) {
                $setting = Db::table('WechatApp')
                    ->where('id', $appid)
                    ->find();
                $where['companyId'] = 0;
            } else {
                $where['companyId'] = $companyId;
            }
        } else {
            $setting = Db::table('WechatApp')
                ->where('id', $appid)
                ->find();
            $where['companyId'] = 0;
        }



        $setting['other'] = json_decode($setting['other'], true);
        if(!empty($setting['other']['wechat_pay'])){
             unset($setting['other']['wechat_pay']);
        }
        $setting['announcement'] = json_decode($setting['announcement'], true);
//        $setting['companyId'] = -1;
        $introduction = json_decode($setting['introduction'], true);
        $distribution = json_decode($setting['distribution'], true);
        $nameData = [];
        $briefData = [];
        foreach ($introduction as $k => &$v) {
            $nameData[] = $v['name'];
            $briefData[] = $v['brief'];
        }
        unset($v);
        $setting['introduction'] = [
            'name' => $nameData,
            'brief' => $briefData,
        ];


        //导航栏
        $bottomNav = Db::table('WechatApp_url')
            ->where($where)
            ->where(array('place' => 'bottomNav'))
            ->order('sort DESC')
            ->select();
        $bottomNav = $this->urlSplice($bottomNav);

        //首页轮播图
        $honeBanner = Db::table('WechatApp_url')
            ->where($where)
            ->where(array('place' => 'honeBanner'))
            ->order('sort DESC')
            ->select();
        $honeBanner = $this->urlSplice($honeBanner);

        //首页导航栏一
        $homeNav = Db::table('WechatApp_url')
            ->where($where)
            ->where(array('place' => 'homeNav'))
            ->order('sort DESC')
            ->select();
        $homeNav = $this->urlSplice($homeNav);

        //首页导航栏二
        $homeNav2 = Db::table('WechatApp_url')
            ->where($where)
            ->where(array('place' => 'homeNav2'))
            ->order('sort DESC')
            ->select();
        $homeNav2 = $this->urlSplice($homeNav2);

        //个人中心
        $personal = Db::table('WechatApp_url')
            ->where($where)
            ->where(array('place' => 'personal'))
            ->order('sort DESC')
            ->select();
        $personal = $this->urlSplice($personal);

        $data = [
            'setting' => $setting,
            'bottomNav' => $bottomNav,
            'homeNav' => $homeNav,
            'homeNav2' => $homeNav2,
            'honeBanner' => $honeBanner,
            'personal' => $personal,
            'distribution' => $distribution,
        ];
        
        // 转换所有 OSS 链接为 HTTPS
        $data = $this->convertOssToHttps($data);
        
        $this->success('获取成功', $data);
    }

    public function urlSplice($data = [])
    {
        foreach ($data as $k => &$v) {

            if ($v['url'] == '/pages/task/list') {
                $v['url'] = '/pages/task/list?i=' . $v['id'] . '&type=' . $v['parameter'];
            } else if ($v['url'] == '/pages/task/index') {
                $task = Db::table('WechatApp_task')
                    ->field('id,name,type')
                    ->where(array('id' => $v['parameter']))
                    ->find();
                if (!empty($task)) {
                    $v['url'] = '/pages/task/index?i=' . $v['id'] . '&type=' . $task['type'] . '&tid=' . $v['parameter'];
                } else {
                    $v['url'] = '/pages/task/index?i=' . $v['id'] . '&tid=' . $v['parameter'];
                }
            } else {
                $v['url'] = $v['url'] . '?i=' . $v['id'];
            }
        }
        unset($v);

        return $data;

    }

}