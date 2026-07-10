<?php
namespace app\api\controller\game;

use app\common\controller\Api;
use EasyWeChat\Factory;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class Longxia extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $api_url = 'https://web.api.yx915.com';
    protected $header;

    /**
     * 初始化
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $appid = input('appid', 'hezuoapp');
        $key = input('key', 'f5647c124ee570e1aa2fb2cdc64cddf5');
        if (empty($appid) || empty($key)) {
            $this->error('appid或key缺失');
        }

        $time = time() . rand(100,999);
        $token = strtoupper(md5($appid.$key.$time));

        $this->header = [
            'X-Service-App:' .$appid,
            'X-Service-Timestamp:'.$time,
            'X-Service-Token:'.$token,
        ];
    }


    public function names(){
        $url = $this->api_url.'/kcData/Data/Names';
        $shop = input('shop',29);
        $params=[
            'shop' => $shop
        ];
        $res = requestCurl($url,$params,'POST', $this->header,'dataBuild');
        $res = json_decode($res,true);


        exit_data($res);
    }


    public function lists(){
        $url = $this->api_url.'/kcData/Data/Lists';
        $shop = input('shop',29);
        $openid = input('openid','476921188d272f66eb32a697bd06768b');
        $name = input('name','王者荣耀');
        $page = input('page',1);
        $cont = input('cont','');
        $state = input('state',2);
        $qu = input('qu','');
        $fu = input('fu','');
        $price = input('price','0,1000000');
        $cookie = input('cookie','4804e619d898ca7f972e5dae20be3f86');
        $params=[
            'shop' => $shop,
            'openid' => $openid,
            'name' => $name,
            'Page' => $page,
            'Cont' => $cont,
            'state' => $state,
            'qu' => $qu,
            'fu' => $fu,
            'price' => $price,
            'cookie' => $cookie,
        ];

        $res = requestCurl($url,$params,'POST', $this->header,'dataBuild');
        $res = json_decode($res,true);


        exit_data($res);
    }
}
