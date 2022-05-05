<?php 
defined("IN_IA") or exit("Access Denied");
class Tommie_duanshipingModuleWxapp extends WeModuleWxapp
{
    public function doPageLogin()
    {
        global $_W, $_GPC;
        $maxnums = pdo_get("ims_tommie_douyin_config", array("uniacid" => $_GPC["i"]), ["mix_num", "invite_award"]);
        $maxnum = $maxnums["mix_num"];
        $isuser = pdo_get("tommie_douyin_member", array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"]));
        if (!empty($isuser)) 
		{
            $user_data = array("nickname" => $_W["fans"]["nickname"], "headimg" => $_W["fans"]["headimgurl"], "sex" => $_W["fans"]["sex"], "province" => $_W["fans"]["tag"]["province"], "city" => $_W["fans"]["tag"]["city"], "regtime" => time());
            $result = pdo_update("tommie_douyin_member", $user_data, array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"]));
            if (!empty($result)) 
			{
                return $this->result(0, "成功", array("data" => $result, "openid" => $_W["fans"]["openid"]));
            }
        } else {
            $user_data = array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"], "invite_openid" => $_GPC["inviterOpenid"], "nickname" => $_W["fans"]["nickname"], "headimg" => $_W["fans"]["headimgurl"], "sex" => $_W["fans"]["sex"], "province" => $_W["fans"]["tag"]["province"], "city" => $_W["fans"]["tag"]["city"], "maximum" => $maxnum, "regtime" => time());
            $result = pdo_insert("tommie_douyin_member", $user_data);
            if (!empty($result)) {
                if (!empty($_GPC["inviterOpenid"])) {
                    $inviteaward = $maxnums["invite_award"];
                    $invitedata = ["maximum +=" => $inviteaward];
                    pdo_update("tommie_douyin_member", $invitedata, array("uniacid" => $_GPC["i"], "openid" => $_GPC["inviterOpenid"]));
                }
                return $this->result(0, "成功", array("data" => $result, "openid" => $_W["fans"]["openid"]));
            }
        }
    }
    public function doPageInvite()
    {
        global $_W, $_GPC;
        $user_data = pdo_getall("ims_tommie_douyin_member", array("uniacid" => $_GPC["i"], "invite_openid" => $_W["fans"]["openid"]));
        return $this->result(0, "成功", $user_data);
    }
    public function doPageQuery()
    {
        global $_W, $_GPC;
        $config = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), ["client", "clientkey"]);
        $VideoDownloadURL = $config["client"];
        if (empty($_W["fans"]["openid"])) {
            return $this->result(3, "你还没有登陆", array("error" => 10001));
        } else {
            $num = pdo_get("tommie_douyin_member", array("openid" => $_W["fans"]["openid"]), array("maximum"));
            $num = intval($num["maximum"]);
            $isvip = pdo_get("tommie_douyin_vipmember", array("openid" => $_W["fans"]["openid"]), array("end_time"));
            if (!empty($isvip)) {
                $isvip = intval($isvip["end_time"]) > time() ? true : false;
            } else {
                $isvip = false;
            }
            if ($num >= 1 || $isvip) {
                $url = trim($_GPC["url"]);
                $timestamp = time();
                $resulturl = ihttp_get($VideoDownloadURL . $url);
                $downurl = json_decode($resulturl["content"], true);
                if ($downurl["status"] == "101") {
                    $downurl = $downurl["data"]["url"];
                    $downurl = urlencode($downurl);
                    $erron = 0;
                    $message = "解析成功";
                    $data_arr = array("downurl" => $downurl);
                    if ($isvip != true) {
                        $numdata = ["maximum -=" => 1];
                        pdo_update("tommie_douyin_member", $numdata, array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"]));
                    }
                } else {
                    $erron = 1;
                    $message = "解析失败,请联系客服";
                    $data_arr = array("downurl" => $downurl["msg"]);
                }
                return $this->result($erron, $message, $data_arr);
            } else {
                $erron = 1;
                $message = "您的次数已用完，请联系客服";
                $data_arr = array("num" => $num);
                return $this->result($erron, $message, $data_arr);
            }
        }
    }
    public function doPageIndex()
    {
        global $_W, $_GPC;
       $result = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), ["app_name", "help_url", "invite_award", "onpayenter", "isaudit", "api_url", "title", "description", "qq_group", "share_title", "qq_num", "ad_id", "share_img", "mix_num", "is_member", "copytext", "adimg", "adtext", "is_pay", "progress"]);
        $result["share_img"] = $_W["attachurl"] . $result["share_img"];
        $data = array("index" => $result, "url" => $_W["attachurl"]);
        return $this->result(0, "成功", $data);
    }
    public function doPageVideo()
    {
        global $_W, $_GPC;
        $sql = "select title,img_url,video_id from ims_tommie_douyin_video where uniacid = :uniacid";
        $pageindex = $_GPC["pages"];
        $pagesize = 16;
        $p = ($pageindex - 1) * 16;
        $sql .= " order by id desc limit " . $p . "," . $pagesize;
        $orderdata = pdo_fetchall($sql, array(":uniacid" => $_W["uniacid"]));
        $isaudit = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), array("isaudit", "invite_award", "share_title", "share_img"));
        $tuijian = pdo_getall("tommie_douyin_tuijian", ["uniacid" => $_GPC["i"]]);
        $data = array("isaudit" => $isaudit, "url" => $_W["attachurl"], "videolist" => $orderdata, "tuijian" => $tuijian);
        return $this->result(0, $isaudit["isaudit"], $data);
    }
    public function doPagePlaydownload()
    {
        global $_W, $_GPC;
        if (empty($_W["fans"]["openid"])) {
            return $this->result(3, "你还没有登陆", array("error" => 10001));
        } else {
            $num = pdo_get("tommie_douyin_member", array("openid" => $_W["fans"]["openid"]), array("maximum"));
            $num = intval($num["maximum"]);
            $isvip = pdo_get("tommie_douyin_vipmember", array("openid" => $_W["fans"]["openid"]), array("end_time"));
            if (!empty($isvip)) {
                $isvip = intval($isvip["end_time"]) > time() ? true : false;
            } else {
                $isvip = false;
            }
            if ($num >= 1 || $isvip) {
                $config = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), ["client", "clientkey"]);
                $VideoDownloadURL = $config["client"];
                $videoid = $_GPC["vid"];
                $timestamp = time();
                $resulturl = ihttp_get($VideoDownloadURL . $videoid);
                $resulturl = json_decode($resulturl["content"], true);
                $downurl = $resulturl["data"]["downurl"];
                if (empty($downurl)) {
                    $erron = 3;
                    $message = "下载失败，请重试！";
                    $data_arr = array("downurl" => "api.amemv.com", "apiurl" => '');
                } else {
                    $api_url = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), array("api_url"));
                    $numdata = ["maximum -=" => 1];
                    pdo_update("tommie_douyin_member", $numdata, array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"]));
                    $erron = 0;
                    $message = "成功";
                    $data_arr = array("downurl" => $downurl, "apiurl" => $api_url);
                }
                return $this->result($erron, $message, $data_arr);
            } else {
                return $this->result(2, "您的次数已用完", array("downurl" => "api.amemv.com", "apiurl" => ''));
            }
        }
    }
    public function doPagePlay()
    {
        global $_W, $_GPC;
        $result = pdo_get("tommie_douyin_favorite", array("uniacid" => $_W["uniacid"], "favorite_openid" => $_W["fans"]["openid"], "video_id" => $_GPC["videoid"]));
        if (!empty($result)) {
            return $this->result(0, "成功", array("code" => 1));
        } else {
            return $this->result(0, "成功", array("code" => 0));
        }
    }
    public function doPageFavourite()
    {
        global $_W, $_GPC;
        if (!empty($_W["fans"]["openid"])) {
            $isav = pdo_get("tommie_douyin_favorite", array("uniacid" => $_W["uniacid"], "favorite_openid" => $_W["fans"]["openid"], "video_id" => $_GPC["videoid"]));
            if (empty($isav)) {
                $data = array("uniacid" => $_W["uniacid"], "favorite_openid" => $_W["fans"]["openid"], "video_id" => $_GPC["videoid"], "regtime" => time());
                $result = pdo_insert("tommie_douyin_favorite", $data);
                if (!empty($result)) {
                    return $this->result(0, "成功", array("code" => 1));
                } else {
                    return $this->result(1, "收藏失败", array("code" => 0));
                }
            } else {
                $result = pdo_delete("tommie_douyin_favorite", array("video_id" => $_GPC["videoid"]));
                if (!empty($result)) {
                    return $this->result(0, "取消成功", array("code" => 0));
                } else {
                    return $this->result(0, "取消失败", array("code" => 1));
                }
            }
        } else {
            return $this->result(1, "请先登陆", array("code" => 0));
        }
    }
    public function doPageMember()
    {
        global $_W, $_GPC;
        $user = pdo_get("tommie_douyin_member", array("uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"]));
        if (!empty($_W["fans"]["openid"])) {
            if (empty($user["openid"])) {
                $user_data = array("uniacid" => $_GPC["i"], "openid" => $_W["fans"]["openid"], "invite_openid" => '', "nickname" => $_W["fans"]["nickname"], "headimg" => $_W["fans"]["headimgurl"], "sex" => $_W["fans"]["sex"], "province" => $_W["fans"]["tag"]["province"], "city" => $_W["fans"]["tag"]["city"], "maximum" => 5, "regtime" => time());
                $result = pdo_insert("tommie_douyin_member", $user_data);
            }
        }
        $contact = pdo_get("tommie_douyin_config", array("uniacid" => $_W["uniacid"]), ["contact", "client", "qq_num", "qq_group", "is_pay", "isaudit", "is_member", "onpayenter", "invite_award", "help_url"]);
        $endtieme = pdo_get("tommie_douyin_vipmember", array("uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"]));
        $endtieme = intval($endtieme["end_time"]) - time() > 0 ? date("Y-m-d H:i:s", $endtieme["end_time"]) : "1";
        $contact["contact"] = $_W["attachurl"] . $contact["contact"];
        $inviteuum = pdo_getall("tommie_douyin_member", ["uniacid" => $_W["uniacid"], "invite_openid" => $_W["fans"]["openid"]]);
        $count = count($inviteuum);
        return $this->result(0, "成功", array("user" => $user, "contact" => $contact, "endtime" => $endtieme, "inviteuum" => $count));
    }
    public function doPageMyfavourite()
    {
        global $_W, $_GPC;
        $sql = "select v.title,v.img_url,v.video_id from ims_tommie_douyin_favorite f left join ims_tommie_douyin_video v on f.video_id=v.video_id where f.uniacid = :uniacid and f.favorite_openid = :favorite_openid";
        $pageindex = $_GPC["pages"];
        $pagesize = 16;
        $p = ($pageindex - 1) * 16;
        $sql .= " order by f.id desc limit " . $p . "," . $pagesize;
        $orderdata = pdo_fetchall($sql, array(":uniacid" => $_W["uniacid"], ":favorite_openid" => $_W["fans"]["openid"]));
        return $this->result(0, "成功", array("data" => $orderdata, "nickname" => $_W["fans"]["nickname"]));
    }
    public function doPagePay()
    {
        global $_GPC, $_W;
        $orderid = $_GPC["orderid"];
        $money = floatval($_GPC["money"]);
        $numdata = pdo_get("tommie_douyin_payconfig", array("uniacid" => $_W["uniacid"]));
        if ($money == $numdata["money_a"]) {
            $num = intval($numdata["num_a"]);
        } else {
            if ($money == $numdata["money_b"]) {
                $num = intval($numdata["num_b"]);
            } else {
                if ($money == $numdata["money_c"]) {
                    $num = intval($numdata["num_c"]);
                } else {
                    $num = 0;
                }
            }
        }
        $order = array("tid" => $orderid, "user" => $_W["openid"], "fee" => $money, "title" => "充值");
        $pay_params = $this->pay($order);
        if (is_error($pay_params)) {
            return $this->result(1, "支付失败，请重试");
        } else {
            $data = array("uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"], "orderid" => $orderid, "money" => $money, "num" => $num, "regtime" => time());
            pdo_insert("tommie_douyin_order", $data);
        }
        return $this->result(0, '', $pay_params);
    }
    public function payResult($params)
    {
        if ($params["result"] == "success" && $params["from"] == "notify") {
            $cont = pdo_get("tommie_douyin_order", ["orderid" => $params["tid"]]);
            $cont = $cont["num"];
            $user_data = ["maximum +=" => $cont];
            pdo_update("tommie_douyin_member", $user_data, array("uniacid" => $params["uniacid"], "openid" => $params["user"]));
            $paystate = ["paystate" => 1];
            pdo_update("tommie_douyin_order", $paystate, ["orderid" => $params["tid"], "openid" => $params["user"]]);
        }
    }
    public function doPagePayse()
    {
        global $_GPC, $_W;
        $results = pdo_get("tommie_douyin_order", ["orderid" => $_GPC["orderid"]]);
        $cont = $results["num"];
        if ($results["paystate"] == "0") {
            $user_data = ["maximum +=" => $cont];
            pdo_update("tommie_douyin_member", $user_data, array("uniacid" => $_W["uniacid"], "openid" => $results["openid"]));
            $paystate = ["paystate" => 1];
            $upresult = pdo_update("tommie_douyin_order", $paystate, ["orderid" => $_GPC["orderid"], "openid" => $results["openid"]]);
            return $this->result(0, "成功", array("result" => $upresult));
        } else {
            return $this->result(0, "订单已完成", array("result" => "ok"));
        }
    }
    public function doPagePayconfig()
    {
        global $_GPC, $_W;
        $data = pdo_get("tommie_douyin_payconfig", ["uniacid" => $_W["uniacid"]]);
        return $this->result(0, "成功", array("data" => $data));
    }
    public function doPageVippay()
    {
        global $_GPC, $_W;
        $data = pdo_get("tommie_douyin_config", ["uniacid" => $_W["uniacid"]], ["title"]);
        return $this->result(0, "成功", array("weixin" => $data["title"]));
    }
    private function addGivenNum($uniacid, $openid, $num, $key)
    {
        $one = pdo_get("tommie_douyin_member", ["uniacid" => $uniacid, "openid" => $openid], ["given"]);
        if (!empty($one)) {
            if ($one["given"] == 0) {
                $data = ["maximum +=" => $num, "given" => 1];
                $mresult = pdo_update("tommie_douyin_member", $data, array("uniacid" => $uniacid, "openid" => $openid));
                if ($mresult) {
                    $odata = array("uniacid" => $uniacid, "openid" => $openid, "key" => $key, "day" => $num, "regtime" => time());
                    pdo_insert("tommie_douyin_viporder", $odata);
                    return true;
                }
            } else {
                return false;
            }
        }
    }
    public function doPageKeypay()
    {
        global $_GPC, $_W;
        $key = trim($_GPC["key"]);
        $kami_key = $_W["fans"]["openid"] . "_kami";
        $error_num = cache_load($kami_key)[0];
        $error_num = empty($error_num) ? 1 : intval($error_num) + 1;
        $error_tiem = cache_load($kami_key)[1];
        $error_tiem = empty($error_tiem) ? time() + 600 : intval($error_tiem) + 600;
        if (empty($_W["fans"]["openid"])) {
            return $this->result(3, "你还没有登陆", array("error" => 10001));
        }
        if ($key === "jx.muzzz.cn") {
            if ($this->addGivenNum($_W["uniacid"], $_W["fans"]["openid"], 20, $key)) {
                return $this->result(1, "兑换成功，增加20次下载！", array("date" => $key));
            } else {
                return $this->result(1, "您已经兑换过了，请勿重复兑换！", array("date" => $key));
            }
        }
        if ($error_tiem < time()) {
            cache_delete($kami_key);
            $error_num = 1;
        }
        if ($error_num >= 10) {
            return $this->result(1, "错误次数过多，请10分钟后再来。", array("date" => $key));
        }
        $keyinfo = pdo_get("tommie_douyin_keyword_id", ["pwd" => $key]);
        if (!empty($keyinfo)) {
            if ($keyinfo["status"] == 1) {
                return $this->result(1, "您输入的卡密已被使用", array("date" => ''));
            }
            if (substr($key, 0, 6) == "addnum") {
                $data = ["maximum +=" => intval($keyinfo["day"])];
                $mresult = pdo_update("tommie_douyin_member", $data, ["uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"]]);
            } else {
                $tlong = 60 * 60 * 24 * intval($keyinfo["day"]);
                $vip = pdo_get("tommie_douyin_vipmember", ["uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"]]);
                if (!empty($vip)) {
                    $endtime = time() < intval($vip["end_time"]) ? intval($vip["end_time"]) + $tlong : time() + $tlong;
                    $mdata = array("password" => $key, "end_time" => $endtime, "regtime" => time());
                    $mresult = pdo_update("tommie_douyin_vipmember", $mdata, ["uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"]]);
                } else {
                    $endtime = time() + $tlong;
                    $mdata = array("uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"], "password" => $key, "end_time" => $endtime, "regtime" => time());
                    $mresult = pdo_insert("tommie_douyin_vipmember", $mdata);
                }
            }
            if ($mresult) {
                $odata = array("uniacid" => $_W["uniacid"], "openid" => $_W["fans"]["openid"], "key" => $key, "day" => $keyinfo["day"], "regtime" => time());
                $oresult = pdo_insert("tommie_douyin_viporder", $odata);
            }
            if ($oresult) {
                $kdata = array("openid" => $_W["fans"]["openid"], "status" => 1);
                $mresult = pdo_update("tommie_douyin_keyword_id", $kdata, ["pwd" => $key]);
                cache_delete($kami_key);
                return $this->result(0, "兑换成功", array("date" => $key));
            } else {
                return $this->result(1, "兑换失败，请联系管理员！", array("date" => $keyinfo));
            }
        } else {
            cache_write($kami_key, [$error_num, time()]);
            return $this->result(1, "您输入的卡密不存在！", array("date" => ''));
        }
    }
}
function getSubstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    if ($left) {
        $right = strpos($str, $rightStr, $left + strlen($leftStr));
        if ($right && $right > $left) {
            return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
        } else {
            return false;
        }
    } else {
        return false;
    }
}
?>