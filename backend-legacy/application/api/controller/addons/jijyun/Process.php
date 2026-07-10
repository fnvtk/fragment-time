<?php


namespace app\api\controller\addons\jijyun;


use think\cache\driver\Redis;

class Process extends Base
{
    public function getList(){
        $url = $this->jjy_apiUrl . '/api/openapi/query_process';
        $params = [
            'corp_token' => $this->jjy_corp_token,
        ];
        $res = requestCurl($url, $params, 'POST');
        $res = json_decode($res, 256);
        exit_data($res);
    }
}
