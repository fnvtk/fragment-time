<?php

namespace app\api\controller\app\spsj;

/**
 * 微信支付商家转账服务类
 * 用于处理微信支付V3商家转账到零钱功能
 */
class WechatPayTransfer
{
    // 微信支付API域名
    const API_BASE_URL = 'https://api.mch.weixin.qq.com';
    const API_BASE_URL_BACKUP = 'https://api2.mch.weixin.qq.com';

    // 配置信息
    private $mchId;          // 商户号
    private $appId;          // 小程序/公众号AppID
    private $apiV3Key;       // API v3密钥
    private $privateKey;     // 商户私钥（用于签名）
    private $certSerialNo;  // 证书序列号（用于加密敏感信息）
    private $publicKey;      // 微信支付公钥（用于验证回调）

    /**
     * 构造函数
     * @param array $config 配置数组
     *   - mch_id: 商户号
     *   - app_id: 小程序/公众号AppID
     *   - api_v3_key: API v3密钥
     *   - private_key: 商户私钥文件路径或内容
     *   - cert_serial_no: 证书序列号
     *   - public_key: 微信支付公钥文件路径或内容（可选，用于验证回调）
     */
    public function __construct($config)
    {
        $this->mchId = $config['mch_id'] ?? '';
        $this->appId = $config['app_id'] ?? '';
        $this->apiV3Key = $config['api_v3_key'] ?? '';
        $this->certSerialNo = $config['cert_serial_no'] ?? '';

        // 处理私钥（支持文件路径、URL或直接内容）
        if (isset($config['private_key'])) {
            if (file_exists($config['private_key'])) {
                // 本地文件
                $this->privateKey = file_get_contents($config['private_key']);
            } elseif (filter_var($config['private_key'], FILTER_VALIDATE_URL)) {
                // URL地址
                $this->privateKey = file_get_contents($config['private_key']);
                if ($this->privateKey === false) {
                    \think\Log::error('无法从URL加载私钥: ' . $config['private_key']);
                    $this->privateKey = '';
                }
            } else {
                // 直接是私钥内容
                $this->privateKey = $config['private_key'];
            }
        }

        // 处理公钥（可选，支持文件路径、URL或直接内容）
        if (isset($config['public_key'])) {
            if (file_exists($config['public_key'])) {
                // 本地文件
                $this->publicKey = file_get_contents($config['public_key']);
            } elseif (filter_var($config['public_key'], FILTER_VALIDATE_URL)) {
                // URL地址
                $this->publicKey = file_get_contents($config['public_key']);
                if ($this->publicKey === false) {
                    \think\Log::error('无法从URL加载公钥: ' . $config['public_key']);
                    $this->publicKey = '';
                }
            } else {
                // 直接是公钥内容
                $this->publicKey = $config['public_key'];
            }
        }
    }

    /**
     * 发起转账
     * @param array $params 转账参数
     *   - out_bill_no: 商户单号（必填）
     *   - openid: 收款用户OpenID（必填）
     *   - transfer_amount: 转账金额，单位：分（必填）
     *   - transfer_remark: 转账备注（必填）
     *   - transfer_scene_id: 转账场景ID（必填，如：1000现金营销，1006企业报销）
     *   - user_name: 收款用户姓名（选填，>=2000元必填）
     *   - transfer_scene_report_infos: 转账场景报备信息（必填）
     *   - notify_url: 通知地址（选填）
     *   - user_recv_perception: 用户收款感知（选填）
     * @return array
     */
    public function createTransfer($params)
    {
        $url = self::API_BASE_URL . '/v3/fund-app/mch-transfer/transfer-bills';

        // 构建请求体
        $body = [
            'appid' => $this->appId,
            'out_bill_no' => $params['out_bill_no'],
            'transfer_scene_id' => $params['transfer_scene_id'],
            'openid' => $params['openid'],
            'transfer_amount' => intval($params['transfer_amount']),
            'transfer_remark' => $params['transfer_remark'],
            'transfer_scene_report_infos' => $params['transfer_scene_report_infos'] ?? [],
        ];

        // 可选参数
        if (isset($params['user_name']) && !empty($params['user_name'])) {
            // 需要加密
            $body['user_name'] = $this->encryptSensitiveData($params['user_name']);
        }

        if (isset($params['notify_url']) && !empty($params['notify_url'])) {
            $body['notify_url'] = $params['notify_url'];
        }

        if (isset($params['user_recv_perception']) && !empty($params['user_recv_perception'])) {
            $body['user_recv_perception'] = $params['user_recv_perception'];
        }

        $result = $this->request('POST', $url, $body);

        return $result;
    }

    /**
     * 查询转账单（通过商户单号）
     * @param string $outBillNo 商户单号
     * @return array
     */
    public function queryByOutBillNo($outBillNo)
    {
        $url = self::API_BASE_URL . '/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/' . $outBillNo;
        return $this->request('GET', $url);
    }

    /**
     * 查询转账单（通过微信单号）
     * @param string $transferBillNo 微信转账单号
     * @return array
     */
    public function queryByTransferBillNo($transferBillNo)
    {
        $url = self::API_BASE_URL . '/v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/' . $transferBillNo;
        return $this->request('GET', $url);
    }

    /**
     * 撤销转账
     * @param string $transferBillNo 微信转账单号
     * @return array
     */
    public function cancelTransfer($transferBillNo)
    {
        $url = self::API_BASE_URL . '/v3/fund-app/mch-transfer/transfer-bills/' . $transferBillNo . '/cancel';
        return $this->request('POST', $url);
    }

    /**
     * 发送HTTP请求
     * @param string $method 请求方法
     * @param string $url 请求URL
     * @param array $body 请求体（POST时使用）
     * @return array
     */
    private function request($method, $url, $body = [])
    {
        $timestamp = time();
        $nonce = $this->generateNonce();
        $bodyStr = !empty($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : '';

        // 构建签名
        $signature = $this->buildSignature($method, $url, $timestamp, $nonce, $bodyStr);

        // 构建请求头
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: WechatPay-APIv3-PHP',
            'Authorization: ' . $this->buildAuthorization($method, $url, $timestamp, $nonce, $bodyStr),
        ];

        // 如果有证书序列号，添加到请求头
        if (!empty($this->certSerialNo)) {
            $headers[] = 'Wechatpay-Serial: ' . $this->certSerialNo;
        }

        // 发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            try {
                \think\Log::error('微信支付请求失败: ' . $error);
            } catch (\Exception $e) {
                // 日志写入失败不影响错误返回
            }
            return ['success' => false, 'error' => ['code' => 'CURL_ERROR', 'message' => $error]];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200) {
            return ['success' => true, 'data' => $result];
        } else {
            try {
                \think\Log::error('微信支付API错误: HTTP ' . $httpCode . ', Response: ' . $response);
            } catch (\Exception $e) {
                // 日志写入失败不影响错误返回
            }
            return ['success' => false, 'http_code' => $httpCode, 'error' => $result];
        }
    }

    /**
     * 构建签名
     * @param string $method 请求方法
     * @param string $url 请求URL（不包含域名）
     * @param int $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $body 请求体
     * @return string
     */
    private function buildSignature($method, $url, $timestamp, $nonce, $body)
    {
        $urlParts = parse_url($url);
        $urlPath = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');

        $message = $method . "\n" .
            $urlPath . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";

        openssl_sign($message, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * 构建Authorization头
     * @param string $method
     * @param string $url
     * @param int $timestamp
     * @param string $nonce
     * @param string $body
     * @return string
     */
    private function buildAuthorization($method, $url, $timestamp, $nonce, $body)
    {
        $urlParts = parse_url($url);
        $urlPath = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');

        $signature = $this->buildSignature($method, $url, $timestamp, $nonce, $body);

        $serialNo = $this->certSerialNo ?: 'YOUR_CERT_SERIAL_NO';

        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->mchId,
            $nonce,
            $timestamp,
            $serialNo,
            $signature
        );
    }

    /**
     * 加密敏感信息（使用微信支付公钥加密）
     * @param string $data 待加密数据
     * @return string base64编码的加密数据
     */
    private function encryptSensitiveData($data)
    {
        if (empty($this->publicKey)) {
            \think\Log::warning('未配置微信支付公钥，敏感数据未加密');
            return $data;
        }

        $encrypted = '';
        if (openssl_public_encrypt($data, $encrypted, $this->publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            return base64_encode($encrypted);
        }

        \think\Log::error('敏感数据加密失败');
        return $data;
    }

    /**
     * 生成随机字符串
     * @param int $length 长度
     * @return string
     */
    private function generateNonce($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}

