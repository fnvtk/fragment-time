<?php

namespace app\api\controller\app\spsj;

/**
 * OSS 链接 HTTPS 转换 Trait
 */
trait OssHttpsTrait
{
    /**
     * 将 OSS 链接转换为 HTTPS
     * @param mixed $data 需要处理的数据（可以是数组、字符串等）
     * @return mixed 处理后的数据
     */
    protected function convertOssToHttps($data)
    {
        $ossDomain = 'karuosiyujzk.oss-cn-shenzhen.aliyuncs.com';
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->convertOssToHttps($value);
            }
        } elseif (is_string($data) && !empty($data)) {
            // 检查是否包含 OSS 域名
            if (strpos($data, $ossDomain) !== false) {
                // 如果已经是 https://，直接返回
                if (strpos($data, 'https://' . $ossDomain) !== false) {
                    // 检查是否有重复的协议（如 https://http://）
                    if (preg_match('/https:\/\/https?:\/\//', $data)) {
                        $data = preg_replace('/https:\/\/https?:\/\//', 'https://', $data);
                    }
                    return $data;
                }
                
                // 先移除可能存在的重复协议
                $data = preg_replace('/https?:\/\/https?:\/\//', 'https://', $data);
                
                // 替换 http:// 为 https://
                if (strpos($data, 'http://' . $ossDomain) !== false) {
                    $data = preg_replace('/http:\/\/' . preg_quote($ossDomain, '/') . '/', 'https://' . $ossDomain . '/', $data);
                }
                // 替换协议相对 URL (//) 为 https://
                elseif (preg_match('/\/\/' . preg_quote($ossDomain, '/') . '/', $data)) {
                    $data = preg_replace('/\/\/' . preg_quote($ossDomain, '/') . '/', 'https://' . $ossDomain . '/', $data);
                }
                // 如果直接以域名开头（没有协议），添加 https://
                elseif (strpos($data, $ossDomain) === 0) {
                    $data = 'https://' . $data;
                }
                // 如果字符串中包含域名但不在开头，使用正则替换
                else {
                    // 匹配 http:// 或 // 开头的 OSS 链接
                    $data = preg_replace('/(https?:\/\/|\/\/)' . preg_quote($ossDomain, '/') . '/', 'https://' . $ossDomain . '/', $data);
                }
                
                // 最后再次检查并清理重复协议
                $data = preg_replace('/https:\/\/https?:\/\//', 'https://', $data);
            }
        }
        
        return $data;
    }
}

