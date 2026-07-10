<?php

namespace app\api\controller\addons\jijyun;

use app\common\controller\Api;
use think\cache\driver\Redis;

class Base extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    protected $jjy_apiUrl = 'https://openapi.jijyun.cn';
    protected $corp_id = 'jY35Q5VACtzyxpbms8nQbffP3Oep9cON';
    protected $secret = 'ZwUQt7tJibe05hPpyeeqftZ2CApSrEBD';
    protected $jjy_corp_token = '';

    public function _initialize()
    {
        parent::_initialize();
        $redis = new Redis();
        $this->jjy_corp_token = $redis->get('jjy_corp_token');
        if (empty($this->jjy_corp_token)) {
            $url = $this->jjy_apiUrl . '/api/openapi/corp_token';
            $params = [
                'corp_id' => $this->corp_id,
                'secret' => $this->secret,
            ];
            $res = requestCurl($url, $params, 'POST');
            $res = json_decode($res, 256);
            if ($res['code'] == 0) {
                $this->jjy_corp_token = $res['data']['corp_token'];
                $redis->set('jjy_corp_token', $this->jjy_corp_token, 7200);
            } else {
                return $res;
            }
        }
    }
}
