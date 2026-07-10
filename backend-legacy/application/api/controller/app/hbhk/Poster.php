<?php
/**
 * 存客宝小程序接口
 */

namespace app\api\controller\app\hbhk;

use app\common\controller\Api;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use EasyWeChat\Kernel\Http\StreamResponse;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class Poster extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $wechatApp;
    protected $config;
    protected $app;
    protected $oss;

    /**
     * 初始化
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $appid = input('appid', 'wx789850448e26c91d');
        if (empty($appid)) {
            $this->error('重要参数缺失');
        }
        $this->oss = [
            'bucket' => 'karuosiyujzk',
            'region' => 'oss-cn-shenzhen.aliyuncs.com',
            'accessKeyId' => getenv('ALIYUN_OSS_ACCESS_KEY_ID') ?: '',
            'accessKeySecret' => getenv('ALIYUN_OSS_ACCESS_KEY_SECRET') ?: ''
        ];


        $this->wechatApp = Db::table('WechatApp')
            ->where('appid', $appid)
            ->find();
        $this->wechatApp['other'] = json_decode($this->wechatApp['other'], true);
        $this->config = [
            'app_id' => $this->wechatApp['appId'],
            'secret' => $this->wechatApp['appSecret'],
        ];
        $this->app = Factory::miniProgram($this->config);
    }


    public function getTaskManage()
    {
        if ($this->request->isPost()) {
            $openid = $this->request->post('openid', '');
            $taskId = $this->request->post('taskId', '');
            if (empty($taskId) || empty($openid)) {
                $this->error('参数缺失');
            }
            $data = Db::table('WechatFriendRequestTaskManage')
                ->where(['taskId' => $taskId, 'openid' => $openid, 'isdel' => 0])
                ->find();
            if (!empty($data)){
                $this->success('您是管理员');
            }else{
                $this->error('您不是管理员');
            }


        } else {
            $this->error('非法访问');

        }
    }


    /**
     * 获取openid
     */
    public function getOpenid()
    {
        if ($this->request->isPost()) {
            $taskId= $this->request->post('taskId', '');
            $code = $this->request->post('code', '');
            $iv = $this->request->post('iv', '');
            $encryptedData = $this->request->post('encryptedData', '');
            if (empty($code) || empty($iv) || empty($encryptedData)) {
                $this->error('参数缺失');
            }


            $wechat = $this->app->auth->session($code);
            $openid = isset($wechat['openid']) ? $wechat['openid'] : '';

            $taskManage = Db::table('WechatFriendRequestTaskManage')
                ->where(['taskId' => $taskId, 'openid' => $openid])
                ->find();

            if (!empty($taskManage)){
                $this->success('您已经是管理员请勿重复绑定');
            }

            $mobile = $this->wechatDecrypt($wechat['session_key'], $iv, $encryptedData);
            if (empty($mobile)) {
                $this->error('手机号获取异常');
            }


            $data = [
                'taskId' => $taskId,
                'openid' => $openid,
                'mobile' => $mobile['phoneNumber'],
                'createTime' => datetime(time()),
            ];
            $res = Db::table('WechatFriendRequestTaskManage')->insertGetId($data);
            if (empty($res)) {
                $this->success('管理员绑定成功');
            }else{
                $this->error('管理员绑定失败');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 解析号码
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
     * 获取海报信息
     */
    public function getPosterData()
    {
        if ($this->request->isPost()) {
            $taskId = input('taskId', '');
            if (empty($taskId)) {
                $task = Db::table('WechatFriendRequestTask')
                    ->field('Id,Name,Guid,poster,posterTips,CompanyId')
                    ->where('Id', $this->wechatApp['other']['taskId'])
                    ->find();
            } else {
                $task = Db::table('WechatFriendRequestTask')
                    ->field('Id,Name,Guid,poster,posterTips,CompanyId')
                    ->where('Id', $taskId)
                    ->find();
            }

            if (!empty($task['poster'])) {
                $poster = json_decode($task['poster'], true);
                $posterNum = count($poster) - 1;
                $task['poster'] = $poster[rand(0, $posterNum)];
            } else {
                $task['poster'] = 'http://karuosiyujzk.oss-cn-shenzhen.aliyuncs.com/prt/20230614/d373a63061339265d7fc0cdee098e6c1.jpg';
//                $this->error('请先上传海报！');
            }

            if ($task['Id'] == $this->wechatApp['other']['taskId'] && !empty($this->wechatApp['other']['process'])) {
                $task['process'] = 1;
            } else {
                $task['process'] = 0;
            }

            $this->success('获取成功', $task);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 获取号码并存储到对应任务
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function sendUser()
    {
        if ($this->request->isPost()) {
            $key = $this->request->post('key', '');
            $code = $this->request->post('code', '');
            $iv = $this->request->post('iv', '');
            $encryptedData = $this->request->post('encryptedData', '');
            if (empty($key) || empty($code) || empty($iv) || empty($encryptedData)) {
                $this->error('参数缺失');
            }
            $task = Db::table('WechatFriendRequestTask')
                ->field('Id,CompanyId,Name,Guid,poster,Topic,posterTips')
                ->where('Guid', $key)
                ->find();
            if (empty($task)) {
                $this->error('该任务已停用');
            }
            $wechat = $this->app->auth->session($code);
            $openid = isset($wechat['openid']) ? $wechat['openid'] : '';
            $mobile = $this->wechatDecrypt($wechat['session_key'], $iv, $encryptedData);

            if (empty($mobile)) {
                $this->error('手机号获取异常');
            }

            $item = Db::table('WechatFriendRequestItem')->where([
                'WechatFriendRequestTaskId' => $task['Id'],
                'PhoneOrWechatId' => $mobile['phoneNumber'],
            ])->find();


            if (empty($item)) {
                $labelsData = ['海报', $task['Name'], $task['Topic']];
                $sql_data = array(
                    'WechatFriendRequestTaskId' => $task['Id'],
                    'PhoneOrWechatId' => $mobile['phoneNumber'],
                    'Status' => 0,
                    'CompanyId' => $task['CompanyId'],
                    'CreatedTime' => date('Y-m-d H:i:s'),
                    'Labels' => json_encode($labelsData, 256),
                );
                $res = Db::table('WechatFriendRequestItem')->insertGetId($sql_data);
                if (!empty($res)) {
                    $this->success($task['posterTips'], $openid);
                } else {
                    $this->error('系统繁忙,请稍后在试~');
                }
            } else {
                $this->error('您的信息我们已收到，请勿重复提交', $openid);
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 生成小程序码
     * @return false|string
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws OssException
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws PDOException
     */
    public function getCode()
    {
        $taskId = input('taskId');
        if (empty($taskId)) {
            $this->error('参数缺失');
        }
        $task = Db::table('WechatFriendRequestTask')
            ->field('Id,CompanyId,Name,posterQrCode')
            ->where('Id', $taskId)
            ->find();

        if (empty($task)) {
            $this->error('任务不存在或已被删除');
        }
        if (empty($task['posterQrCode'])) {
            $accessKeyId = $this->oss['accessKeyId'];
            $accessKeySecret = $this->oss['accessKeySecret'];
            // Endpoint以杭州为例，其它Region请按实际情况填写。
            $endpoint = $this->oss['region'];
            $bucket = $this->oss['bucket'];
            //对应阿里云中的目录，以年月日（例如：20210603）为目录名称
            $object = "prt/";
            $response = $this->app->app_code->getUnlimit($taskId, [
                'page' => 'pages/about/about',
                'width' => 500,
            ]);
            if ($response instanceof StreamResponse) {
                $qrCodeUrl = '';
                $fileName = 'qrcode_' . $taskId . '.png';
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

                if (!empty($qrCodeUrl)) {
                    $res = Db::table('WechatFriendRequestTask')
                        ->where('Id', $task['Id'])
                        ->update(['posterQrCode' => $qrCodeUrl]);
                    if ($res) {
                        $this->success('二维码保存成功', $qrCodeUrl);
                    } else {
                        $this->error('二维码保存保存失败，但生成成功', $qrCodeUrl);
                    }
                } else {
                    $this->error('二维码生成失败');
                }
            } else {
                return json_encode($response, 256);
            }

        } else {
            $this->success('获取成功', $task['posterQrCode']);
        }
    }


    /**
     * 配置信息
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getTaskData()
    {
        if ($this->request->isPost()) {
            $companyId = $this->request->post('companyId', '');
            $taskId = $this->request->post('taskId', '');
            $openid = $this->request->post('openid', '');


            if (empty($companyId) || empty($taskId) || empty($openid)) {
                $this->error('参数缺失');
            }

            $data = Db::table('WechatFriendRequestTask')
                ->where(['Id' => $taskId, 'companyId' => $companyId])
                ->find();
            if ($data) {
                $this->success('获取成功', $data);
            } else {
                $this->error('任务不存在');
            }
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 模板数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getTemplateData()
    {
        if ($this->request->isPost()) {
            $companyId = $this->request->post('companyId', '');
            $taskId = $this->request->post('taskId', '');
            $mode = $this->request->post('mode', 0);
            $openid = $this->request->post('openid', '');

            if (empty($companyId) || empty($taskId) || empty($openid)) {
                $this->error('参数缺失');
            }

            $data = Db::table('WechatFriendRequestTask')
                ->where(['Id' => $taskId, 'companyId' => $companyId])
                ->find();

            if (empty($data)) {
                $this->error('任务不存在或已被删除');
            }

            $templateData = Db::table('WechatFriendRequestTaskPassedReplyTemplate')
                ->field('Id,mode,MsgType,Content,Interval,WechatFriendRequestTaskId')
                ->where(['mode' => $mode, 'WechatFriendRequestTaskId' => $taskId])
                ->order('Interval ASC')
                ->select();

            //数据处理
            foreach ($templateData as $key => &$val) {
                if ($val['MsgType'] == 1) {
                    $val['VideoUrl'] = '';
                    $val['ImageUrl'] = '';
                } else if ($val['MsgType'] == 3) {
                    $val['VideoUrl'] = '';
                    $val['ImageUrl'] = $val['Content'];
                } else if ($val['MsgType'] == 43) {
                    $val['VideoUrl'] = $val['Content'];
                    $val['ImageUrl'] = '';
                }
            }
            unset($val);

            $this->success('获取成功', $templateData);
        } else {
            $this->error('非法访问');
        }
    }


    /**
     * 话术编辑
     * @throws Exception
     * @throws PDOException
     */
    public function saveTemplate()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['MsgType'] == 1) {
                unset($data['VideoUrl'], $data['ImageUrl']);
            } else if ($data['MsgType'] == 3) {
                $data['Content'] = $data['ImageUrl'];
                unset($data['VideoUrl'], $data['ImageUrl']);
            } else if ($data['MsgType'] == 43) {
                $data['Content'] = $data['VideoUrl'];
                unset($data['VideoUrl'], $data['ImageUrl']);
            }

            if (!empty($data['Id'])) {
                $id = $data['Id'];
                unset($data['Id']);

                $res = Db::table('WechatFriendRequestTaskPassedReplyTemplate')
                    ->where(['Id' => $id])
                    ->update($data);
                if ($res) {
                    $this->success('话术更新成功');
                } else {
                    $this->error('话术更新失败');
                }

            } else {
                $res = Db::table('WechatFriendRequestTaskPassedReplyTemplate')->insertGetId($data);
                if ($res) {
                    $this->success('话术新增成功');
                } else {
                    $this->error('话术新增失败');
                }
            }

        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 话术删除
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws PDOException
     */
    public function delTemplate()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $template = Db::table('WechatFriendRequestTaskPassedReplyTemplate')
                ->where(['Id' => $data['Id'], 'WechatFriendRequestTaskId' => $data['WechatFriendRequestTaskId']])
                ->find();

            if (empty($template)) {
                $this->error('话术不存在');
            }

            $res = Db::table('WechatFriendRequestTaskPassedReplyTemplate')
                ->where(['Id' => $data['Id']])
                ->delete();

            if ($res) {
                $this->success('话术删除成功');
            } else {
                $this->error('话术删除失败');
            }

        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 保存任务
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws PDOException
     */
    public function saveTask()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $task = Db::table('WechatFriendRequestTask')
                ->where(['Id' => $data['Id'], 'CompanyId' => $data['CompanyId']])
                ->find();

            if (empty($task)) {
                $this->error('任务不存在');
            }
            $data['UpdateTime'] = datetime(time());

            $id = $data['Id'];
            $companyId = $data['CompanyId'];
            $openid = $data['Openid'];

            unset($data['Id'], $data['CompanyId'], $data['Openid']);
            $res = Db::table('WechatFriendRequestTask')
                ->where(['Id' => $id, 'CompanyId' => $companyId])
                ->update($data);

            if ($res) {
                $this->success('任务编辑成功');
            } else {
                $this->error('任务编辑失败');
            }
        } else {
            $this->error('非法访问');
        }
    }

}
