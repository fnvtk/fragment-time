<?php

namespace app\api\controller;

class Video
{
    public function get_tx_video($url)
    {
        $res = array("code" => 1);
        if (strpos($url, "v.qq.com") !== false) {
            if (strpos($url, "vid=") !== false) {
                $para_Arr = parse_url($url);
                $query_Arr = $this->convertUrlQuery($para_Arr["query"]);
                if (empty($query_Arr["vid"])) {
                    $res["msg"] = "地址格式不正确";
                    return $res;
                }
                $vid = $query_Arr["vid"];
            } else {
                preg_match("/\\/([0-9a-zA-Z]+).html/", $url, $match);
                if (empty($match)) {
                    $res["msg"] = "地址格式不正确";
                    return $res;
                }
                $vid = $match[1];
            }
            try {
                set_time_limit(0);
                $getinfo = "http://vv.video.qq.com/getinfo?vids=" . $vid . "&platform=101001&charge=0&otype=xml";
                $curl = $this->curl($getinfo);
                $info_arr = $this->xmlToArray($curl);
                if (isset($info_arr["msg"]) && $info_arr["msg"] == "vid is wrong") {
                    $res["msg"] = "视频出错";
                    return $res;
                }
                $fi = $info_arr["fl"]["fi"];
                if (isset($fi[0])) {
                    $filename = $info_arr["vl"]["vi"]["fn"];
                    $key = $info_arr["vl"]["vi"]["fvkey"];
                    $url = $info_arr["vl"]["vi"]["ul"]["ui"][0]["url"];
                    $video_url = $url . $filename . "?vkey=" . $key;
                } else {
                    $format_id = $fi[1]["id"];
                    $fmt = $fi[1]["name"];
                    $format = "p" . substr($format_id, -3, 3);
                    $key = $info_arr["vl"]["vi"]["fvkey"];
                    $vid = $info_arr["vl"]["vi"]["vid"];
                    $url = $info_arr["vl"]["vi"]["ul"]["ui"][0]["url"];
                    if (5 <= strlen($format_id)) {
                        $mp4 = $vid . "." . $format . ".1.mp4";
                    } else {
                        $mp4 = $vid . ".mp4";
                    }
                    $video_url = $url . $mp4 . "?vkey=" . $key . "&fmt=" . $fmt;
                }
                $res["code"] = 0;
                $res["company"] = "腾讯";
                $res["real_url"] = $video_url;
                $res["duration"] = $info_arr["vl"]["vi"]["td"];
                return $res;
            } catch (\Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        }
    }
    public function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA)), true);
        return $values;
    }
    public function get_ws_video($url)
    {
        $res = array("code" => 1);
        if (strpos($url, "weishi.qq.com") !== false) {
            preg_match("/\\w{17}/", $url, $match);
            if (empty($match)) {
                $res["msg"] = "地址格式不正确";
                return $res;
            }
            $vid = $match[0];
            try {
                set_time_limit(0);
                $getinfo = "https://h5.qzone.qq.com/webapp/json/weishi/WSH5GetPlayPage?feedid=" . $vid;
                $curl = $this->curl($getinfo);
                $info_arr = json_decode($curl, true);
                if (isset($info_arr["ret"]) && $info_arr["msg"] == "vid is wrong") {
                    $res["msg"] = "视频出错";
                    return $res;
                }
                $video_url = $info_arr["data"]["feeds"][0]["video_url"];
                $duration = $info_arr["data"]["feeds"][0]["video"]["duration"];
                $cover = $info_arr["data"]["feeds"][0]["images"][0]["url"];
                $video_url2 = $info_arr["data"]["feeds"][0]["video_spec_urls"][1]["url"];
                $res["code"] = 0;
                $res["company"] = "微视";
                if (!empty($video_url2)) {
                    $res["real_url"] = $video_url2;
                } else {
                    $res["real_url"] = $video_url;
                }
                $res["cover"] = $cover;
                $res["duration"] = $duration / 1000;
                return $res;
            } catch (\Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        } else {
            $res["msg"] = "视频解析失败，请重试";
            return $res;
        }
    }
    public function get_dy_video($url)
    {
        $res = array("code" => 1);
        if (strpos($url, "v.douyin.com") !== false) {
            try {
                set_time_limit(0);
                $UserAgent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)";
                $UserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36 Edg/81.0.416.53";
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_ENCODING, "");
                curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                $data = curl_exec($curl);
                curl_close($curl);
                preg_match("/<p class=\"desc\">(?<desc>[^<>]*)<\\/p>/i", $data, $name);
                preg_match("/playAddr: \"(?<url>[^\"]+)\"/i", $data, $url);
                if (empty($name["desc"])) {
                    $res["msg"] = "地址格式不正确";
                    return $res;
                }
                $video_url = $url["url"];
                $res["code"] = 0;
                $res["company"] = "抖音";
                $res["real_url"] = $video_url;
                $res["duration"] = 0;
                return $res;
            } catch (\Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        } else {
            $res["msg"] = "视频解析失败，请重试";
            return $res;
        }
    }
    public function get_dy_videonew_bk($url)
    {
        $res = array("code" => 1);
        if (strpos($url, "v.douyin.com") !== false || strpos($url, "iesdouyin.com") !== false) {
            try {
                set_time_limit(0);
                $reurl = substr($url, strpos($url, "http"));
                if (0 < strpos($reurl, " ")) {
                    $reurl = substr($reurl, 0, strpos($reurl, " "));
                }
                ini_set("user_agent", "user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3");
                $url = file_get_contents($reurl);
                preg_match("/itemId:\\s+\\\"(.*?)\\\"/sS", $url, $itemId);
                preg_match("/dytk:\\s+\\\"(.*?)\\\"/sS", $url, $dytk);
                $json = file_get_contents("https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $itemId[1] . "&dytk=" . $dytk[1]);
                $data = json_decode($json, true);
                $name = $data["item_list"][0]["desc"];
                $img = $data["item_list"][0]["video"]["origin_cover"]["url_list"][0];
                $img_run = $data["item_list"][0]["video"]["dynamic_cover"]["url_list"][0];
                $zan = $data["item_list"][0]["statistics"]["digg_count"];
                $pl = $data["item_list"][0]["statistics"]["comment_count"];
                $video = $this->getrealurl($data["item_list"][0]["video"]["play_addr"]["url_list"][0]);
                $duration = $data["item_list"][0]["duration"];
                if (empty($video)) {
                    $res["msg"] = "视频解析失败，请重试";
                    return $res;
                }
                $res["code"] = 0;
                $res["company"] = "抖音";
                $res["cover"] = $img;
                $res["real_url"] = $video;
                $res["duration"] = $duration / 1000;
                return $res;
            } catch (\Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        } else {
            $res["msg"] = "视频解析失败，请重试";
            return $res;
        }
    }
    public function get_dy_videonew($url)
    {

        $res = array("code" => 1);
        if (strpos($url, "v.douyin.com") !== false || strpos($url, "iesdouyin.com") !== false) {
            try {
                set_time_limit(0);
                $reurl = substr($url, strpos($url, "http"));
                if (0 < strpos($reurl, " ")) {
                    $reurl = substr($reurl, 0, strpos($reurl, " "));
                }

                $url302 = $this->getrealurl($reurl);
                $b = "https://www.iesdouyin.com/share/video/";
                $c = "/?";
                $itemId = $this->GetBetween($url302, $b, $c);


                $curl = $this->curl("https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $itemId);
                $data = json_decode($curl, true);

                $name = $data["item_list"][0]["desc"];
                $img = $data["item_list"][0]["video"]["origin_cover"]["url_list"][0];
                $img_run = $data["item_list"][0]["video"]["dynamic_cover"]["url_list"][0];
                $zan = $data["item_list"][0]["statistics"]["digg_count"];
                $pl = $data["item_list"][0]["statistics"]["comment_count"];
                $video = $data["item_list"][0]["video"]["play_addr"]["url_list"][0];

                $video = str_replace("playwm", "play", $video);



                $video = $this->getrealurl($video);


                $duration = $data["item_list"][0]["duration"];
                if (empty($video)) {
                    $res["msg"] = "视频解析失败，请重试";
                    return $res;
                }
                $res["code"] = 0;
                $res["company"] = "抖音";
                $res["cover"] = $img;
                $res["real_url"] = $video;
                $res["duration"] = $duration / 1000;
                return $res;
            } catch (\Exception $e) {
                $res["msg"] = "视频解析失败，请重试";
                return $res;
            }
        } else {
            $res["msg"] = "视频解析失败，请重试";
            return $res;
        }
    }
    public function getrealurl_bk($url)
    {
        $header = get_headers($url, 1);
        return $header["location"];
    }
    public function getrealurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU iPhone OS 9_2 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Mobile/13C75 MicroMessenger/6.5.10 NetType/WIFI Language/zh_CN");
        $ret = curl_exec($ch);
        $info = curl_getinfo($ch);
        $retURL = $info["url"];
        curl_close($ch);
        return $retURL;
    }
    public function GetBetween($content, $start, $end)
    {
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return "";
    }

    function convertUrlQuery($query)
    {
        $queryParts = explode("&", $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode("=", $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }


    function curl($durl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;

    }


}

?>
