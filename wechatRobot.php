<?php
/**
 * @coding:utf-8
 * @description: wechatrobot
 * @author: kevin(email: 841694874@qq.com)
 * @date: 2016-11-27 15:47
 * @Notice : need CLI mode run this code
 *          cookie file need write access
 *          need curl lib        
 **/
 
if(substr(php_sapi_name(), 0, 3) !== 'cli') {
    die("This Programe can only be run in CLI mode");
}
$wechatrobot = new wechatrobot();
class wechatrobot
{
    private $tulingkey = 'ce087d0146ca4605ae62f2f4a0a351b8';
    private $appid;
    private $uuid;
    private $ticket;
    private $scan;
    private $skey;
    private $wxsid;
    private $wxuin;
    private $pass_ticket;
    private $isgrayscale;
    private $profile;
    private $syncKey;
    private $SyncKey = [];
    private $memberlist = [];
    private $contactlist = [];
    private $grouplist = [];
    private $autoreplytogroup = true;
    private $autoreplytoone = true;

    // curl 设置
    private $cookie_file = './wechat_cookies.txt';

    // 特殊用户
    const specialusers = [
        'blogapp' => '微博阅读',
        'blogappweixin' => '微博阅读',
        'brandsessionholder' => 'brandsessionholder',
        'facebookapp' => 'Facebook',
        'feedsapp' => '朋友圈',
        'filehelper' => '文件传输助手',
        'floatbottle' => '漂流瓶',
        'fmessage' => '朋友圈推荐消息',
        'gh_22b87fa7cb3c' => 'gh_22b87fa7cb3c',
        'lbsapp' => 'lbsapp',
        'masssendapp' => '群发助手',
        'medianote' => '语音记事本',
        'meishiapp' => 'meishiapp',
        'newsapp' => '腾讯新闻',
        'notification_messages' => 'notification_messages',
        'officialaccounts' => 'officialaccounts',
        'qmessage' => 'QQ离线助手',
        'qqfriend' => 'qqfriend',
        'qqmail' => 'QQ邮箱',
        'qqsync' => '通讯录同步助手',
        'readerapp' => 'readerapp',
        'shakeapp' => '摇一摇',
        'tmessage' => 'tmessage',
        'userexperience_alarm' => '用户体验报警',
        'voip' => 'voip',
        'weibo' => 'weibo',
        'weixin' => '微信',
        'weixinreminder' => 'weixinreminder',
        'wxid_novlwrv3lqwv11' => 'wxid_novlwrv3lqwv11',
        'wxitil' => '微信小管家'
    ];

    // 默认请求地址
    const dr_url = [
        'tuling_api_url' => 'http://www.tuling123.com/openapi/api?key=531352803455eaf17317cd5b315a347d&info=',
        'login_url' => 'https://wx.qq.com/?lang=zh_cn',
        'appid_url' => 'https://res.wx.qq.com/zh_CN/htmledition/v2/js/webwxApp31e225.js',
        'jslogin_url' => 'https://login.wx.qq.com/jslogin?',
        'qrcode_url' => 'https://login.weixin.qq.com/qrcode/',
        'user_avatar_url' => 'https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?',
        'webwx_new_login_page_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?',
        'webwxinit_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?',
        'sendmsg_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg?',
        'member_list_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact?',
        'statusnotify_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxstatusnotify?',
        'webwxsync_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsync?',
        'webwxbatchgetcontact_url' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?',
        'synccheck_url' => 'https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin/synccheck?',
    ];

    // 消息类型结构
    const message_types_dict = [
        1 => 'text',  # 文本消息
        3 => 'image',  # 图片消息
        34 => 'voice',  # 语音消息
        42 => 'recommend', #名片
        48 => 'sharelocation',  # 位置共享
        51 => 'initmsg',  # 微信初始化消息
        62 => 'video',  # 小视频
        10002 => 'revoke', #撤回消息
    ];

    public function __construct()
    {
        $this->initWechatRobot();
    }
    
    public function initWechatRobot()
    {
        echo "[①] 微信机器人正在启动,请稍后...\r\n";
        echo "[②] 正在获取Appid...\r\n";
        $this->getAppid();
        echo "[②] 正在获取UUID...\r\n";
        $this->getUUID();
        echo "[④] 初始化获取Qrcode...\r\n";
        $this->getQrcode();
        echo "好low,默认等待5秒扫描微信登陆二维码\r\n";
        echo "[⑤] 初始化获取登陆Ticket...\r\n";
        sleep(5);
        $this->getTicket();
        echo "[⑥] 正在登陆...\r\n";
        $this->webwxnewLoginPage();
        echo "[⑦] 微信正在初始化...\r\n";
        $this->webwxInit();
        echo "[⑧] 开启状态通知 ...\r\n";
        $this->webwxStatusNotify();
        echo "[⑨] 正在获取用户列表...\r\n";
        $this->getMemberList();
        echo "[⑩] 进入消息监听模式...\r\n";
        $this->listen();
    }
    
    public function listen()
    {
        $this->synccheck();
        while(true)
        {
            list($ret, $retcode, $selector) = $this->synccheck();
            if($retcode == '1100')
            {
                echo "从微信客户端上登出\r\n";
                break;
            }
            elseif($retcode == '1101')
            {
                echo "从其它设备上登了网页微信\r\n";
                break;
            }
            else if($retcode == '0')
            {
                if($selector == '2')//有新消息
                {
                    echo "有新消息\r\n";
                    $syncRet = $this->webwxsync();
                    $this->handleMessage($syncRet);
                }
                elseif($selector == '3')//  # 未知
                {
                    echo "未知消息\r\n";
                    $syncRet = $this->webwxsync();
                    $this->handleMessage($syncRet); 
                }
                elseif($selector == '4')  # 通讯录更新
                {
                    echo "通讯录更新\r\n";
                    $syncRet = $this->webwxsync();
                    $this->handleMessage($syncRet);                    
                }
                elseif($selector == '6')# 可能是红包
                {
                    echo "可能是红包\r\n";
                    $syncRet = $this->webwxsync();
                    $this->handleMessage($syncRet);                      
                }
                elseif($selector == '7')
                {
                    echo "\t 线路异常...\r\n";
                    $this->synccheck();
                
                }
                elseif($selector == '0')
                {
                    echo "未获取到任何信息...\r\n";
                    continue;
                }
            }
            usleep(800000);
        }
    }
    /**
     *
     *  @description 处理消息
     *  @data array $syncRet message data
     *  @return null
     **/    
    public function handleMessage($syncRet)
    {
        foreach($syncRet['AddMsgList'] as $k => $v)
        {
            $message = $this->_handler_message($v);
            //一些消息不处理
            if($message['type'] == 'recommend' || $message['type'] == 'sharelocation' || $message['type'] == 'initmsg' || $message['type'] == 'revoke')
            {
                continue;
            }
            #开启群消息回复
            #var_dump($message);
            if(($this->autoreplytogroup) && ($message['group'] == true))
            {
                $messageText = $this->tuling($message['Content']);
                $messageTextGroup = '@'.$message['FromMemberName'].' '.$messageText;
                if(($messageText != false) && ($message['group'] == true))
                {
                    $this->sendTextMessage($messageTextGroup,$message['FromUserId']);  
                }
                
            }   
        }
    }
    
    /**
     *
     *  @description 处理消息
     *  @data array $data
     *  @return array
     **/
    public function _handler_message($data)
    {
        $message = [];
        $message['group'] = false;
        $message['type'] = self::message_types_dict[$data['MsgType']];
        $message['FromUserId'] = $data['FromUserName'];
        $message['FromUserName'] = $this->getUserRemarkName($data['FromUserName']);
        $message['ToUserId'] = $data['ToUserName'];
        $message['ToUserName'] = $this->getUserRemarkName($data['ToUserName']);
        $content = str_replace('<br/>', '\n',$data['Content']);
        $content = str_replace('&lt;', '<',$content);
        $content = str_replace('&gt;', '>',$content);
        $message['Content'] = $content;

        if (strpos($message['FromUserId'],'@@') === 0) {
            $message['group'] = true;
            $memberArr = explode(':',$message['Content']);
            $message['FromMemberId'] = $memberArr[0];
            $message['FromMemberName'] = $this->getBatchMemberRemarkName($message['FromUserId'],$message['FromMemberId']);
            $message['Content'] = $memberArr[1];       
        }#群消息s
        return $message;
    }

     /**
     *
     *  @description getBatchMemberRemarkName 根据组ID 和 组成员id 获取 MemberRemarkName
     *  @data  string RemarkName OR  NickName
     *  @return DisplayName OR NickName
     **/        
    public function getBatchMemberRemarkName($groupid, $memberid)
    {
        $name = '未知';
        // 在 grouplist 里面
        if(isset($this->grouplist[$groupid]))
        {
            foreach($this->grouplist[$groupid]['MemberList'] as $vm)
            {
                if($vm['UserName'] == $memberid)
                {
                    return $vm['NickName'];
                }
            }           
        }
        else 
        {
            $new_group = $this->webwxgetbatchcontact($groupid);
            foreach($new_group['MemberList'] as $vm)
            {
                if($vm['UserName'] == $memberid)
                {
                    return $vm['NickName'];
                }
            }    
        }
        return $name;  
    }

    /**
     *
     *  @description getUserRemarkName 获取用户 RemarkName
     *  @data  string RemarkName @12893jiaosdj989u823hdiashd
     *  @return string
     **/   
    public function getUserRemarkName($id)
    {
        $specialusers = array_keys(self::specialusers);
        if($id == $this->profile['UserName'])
        {
            // 自己
            return 'me';
        } 
        if(in_array($id, $specialusers))
        {
            // 自己
            return $specialusers[$id];
        }
        // 群或者个人
        if(strpos($id,'@@') == 0)
        {
            $group_info = $this->webwxgetbatchcontact($id);
            if(!is_null($group_info))
            {
               $this->grouplist[$group_info['UserName']] = $group_info; 
            }
            return $group_info['NickName'];
            
        } else {
            foreach($this->member_list as $k => $v)
            {
                if($v['UserName'] == $id)
                {
                    return $v['NickName'];
                    break;
                }
            }            
        }
    }
    
    /**
     *
     *  @description 根据组Id(groupid) 获取组信息(ContactList)
     *  @groupid string @@12893jiaosdj989u823hdiashd
     *  @return array
     **/
    public function webwxgetbatchcontact($groupid)
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json',
            'Connection' => 'Keep-Alive'
        ];
        $params = [
            'type' => 'ex',
            'r' => time(),
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket
        ];
        $payloadData = json_encode([
            'BaseRequest' => [
                'Uin' => $this->wxuin,
                'Sid' => $this->wxsid,
                'Skey' => $this->skey,
                'DeviceID' => 'e'.rand(10000,99999)
            ],
            'Count' => count($this->grouplist), 
            "List" => [
                [
                    'UserName' => $groupid,
                    'EncryChatRoomId' => ''
                ],
            ]
        ],JSON_UNESCAPED_UNICODE);
        $response = $this->post(self::dr_url['webwxbatchgetcontact_url'].http_build_query($params), $payloadData, $headers);
        if($response) {
            $response = json_decode($response, true);
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == 0)
            {
                echo "获取组信息成功\r\n";
                #file_get_contents('./ContactList.php',print_r($response['ContactList'],true));
                return isset($response['ContactList'][0]) ? $response['ContactList'][0] : null;
            } else {
                echo "获取组信息失败\r\n";
            }
        } else {
            throw new \Exception('获取组信息请求无响应');
        }            
    }
            
    /**
     *
     *  @description sendTextMessage 发送信息基础方法
     *  @messageText string 内容
     *  @toUserName  string 发送给某人
     *  @return bool
     **/
    public function sendTextMessage($messageText, $toUserName = 'filehelper')
    {
         $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'Origin' => 'https://wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json;charset=UTF-8',
            'Connection' => 'Keep-Alive'
        ];

        $params = [
            'r' => time(),
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket
        ];
        $clientMsgId = time();
        $payloadData = json_encode([
            'BaseRequest' => [
                'Uin' => $this->wxuin,
                'Sid' => $this->wxsid,
                'Skey' => $this->skey,
                'DeviceID' => 'e'.rand(10000,99999)
            ],
            'Msg' => [
                'Type' => 1,
                'Content' => $messageText,
                'FromUserName' => $this->profile['UserName'],
                "ToUserName" => $toUserName,
                "LocalID" => $clientMsgId,
                "ClientMsgId" => $clientMsgId
            ],
            "Scene" => 0
        ],JSON_UNESCAPED_UNICODE);

        $response = $this->post(self::dr_url['sendmsg_url'].http_build_query($params), $payloadData, $headers);
        if($response) {
            $response = json_decode($response, true);
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == 0)
            {
                echo "发送信息成功\r\n";
            } else {
                echo "发送信息失败\r\n";
            }
        } else {
            throw new \Exception('发送信息请求无响应');
        }    
    }
    
    /**
     *
     *  @description  异步获取synckey
     *  @return bool
     **/ 
    public function webwxsync()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json;charset=UTF-8',
            'Connection' => 'Keep-Alive'
        ];
        
        $params = [
            'sid' => $this->wxsid,
            'skey' => $this->skey,
            'pass_ticket' => $this->pass_ticket
        ];
        $payloadData = json_encode([
            'BaseRequest' => [
                'Uin' => $this->wxuin,
                'Sid' => $this->wxsid,
                'Skey' => $this->skey,
                'DeviceID' => 'e'.rand(10000,99999)
            ],
            'SyncKey' => $this->SyncKey,
            'rr' => time()
        ]);
        
        $response = $this->post(self::dr_url['webwxsync_url'].http_build_query($params), $payloadData, $headers);
        if($response) {
            $response = json_decode($response, true);
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == 0)
            {
                $this->syncKey = '';
                $this->SyncKey = [];
                $this->SyncKey = $response['SyncKey'];
                if(isset($response['SyncKey']['List']) && is_array($response['SyncKey']['List']))
                {
                    foreach($response['SyncKey']['List'] as $k => $v)
                    {
                        $this->syncKey .= $v['Key'].'_'.$v['Val'].'|';
                    }
                }
                return $response;
                echo "异步获取synckey成功\r\n";
            } else {
                echo "异步获取synckey失败\r\n";
            }
        } else {
            throw new \Exception('异步获取synckey请求无响应');
        }    
    }
    
    /**
     *
     *  @description  异步检查
     *  @return bool
     **/ 
    public function synccheck()
    {
        $headers =  [
            'Accept' => '*/*',
            'Accept-Language' =>'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'webpush.wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?&lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' =>  'Keep-Alive'
        ];
        $params = [
            'sid' => $this->wxsid,
            'uin' => $this->wxuin,
            'skey' => $this->skey,
            'deviceid' => 'e'.rand(10000,99999),
            'synckey' => $this->syncKey,
            '_' =>time(),
        ];
        $response = $this->get(self::dr_url['synccheck_url'].http_build_query($params), $headers);

        if($response) {
            preg_match('/window.synccheck={retcode:"(\d+)",selector:"(\d+)"}/', $response, $matchs);
            if(!empty($matchs)) {
                return $matchs;
            } else {
                throw new \Exception('异步检查失败');
            }
        } else {
            throw new \Exception('网络连接失败');
        }   
    }            
    /**
     * @description getAppid    获取appid
     * @return mixed
     */
    public function getAppId()
    {
        $headers =  [
            'Accept' => '*/*',
            'Accept-Language' =>'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'res.wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' =>  'Keep-Alive'
        ];
        $response = $this->get(self::dr_url['appid_url'], $headers);
        preg_match('/appid=(\w*?)&/', $response, $matchs);

        if(isset($matchs[1]) && !empty($matchs[1])) {
            $this->appid = $matchs[1];
        } else {
            throw new \Exception('未获取到AppId');
        }
    }

    /**
     * @description 获取 getUUID
     * @return mixed
     */
    public function getUUID()
    {
        $headers =  [
            'Accept' => '*/*',
            'Accept-Language' =>'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'res.wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' =>  'Keep-Alive'
        ];
        $params = [
            'appid' => $this->appid,
            'redirect_uri' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage',
            'fun' => 'new',
            'lang' => 'zh_CN',
            '_' => time()
        ];
        $response = $this->get(self::dr_url['jslogin_url'].http_build_query($params), $headers);
        if($response) {
            preg_match('/window.QRLogin.code = (\d+); window.QRLogin.uuid = \"(\S+?)\"/', $response, $matchs);
            if($matchs[1] == '200' && !empty($matchs[2])) {
                $this->uuid = $matchs[2];
            } else {
                throw new \Exception('获取UUID 失败');
            }
        } else {
            throw new \Exception('网络连接失败');
        }
    }

    /**
     * @description 获取二维码
     * @return mixed
     */
    public function getQrcode()
    {
        $headers =  [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' => 'Keep-Alive'
        ];
        $response = $this->get(self::dr_url['qrcode_url'].$this->uuid, $headers);
        if($response) {
            file_put_contents('wechat_qrcode.png', $response);
            /*
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
            $imgURL = $protocol.$_SERVER["SERVER_NAME"].":" . $_SERVER["SERVER_PORT"] .dirname($_SERVER['PHP_SELF']);
            $imgURL = str_replace('\\','/',$imgURL);
            */
            //output images
            echo "open your browser and visit this url(IP://wechat_qrcode.png) \r\n";
        }
    }

    /**
     * @description getTicket 获取票据(重要)
     * @return
     */
    public function getTicket()
    {
        $date = time();
        $headers = [
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host'=> 'login.weixin.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' => 'Keep-Alive'
        ];
        $params = [
            'loginicon' =>'true',
            'uuid' => $this->uuid,
            'tip' => '0',
            'r' => $date,
            '_' => $date
        ];
        $response = $this->get(self::dr_url['user_avatar_url'].http_build_query($params),$headers);
        $pattern = '/window.code=(\d+)[\s\S]*\?ticket=(.*?)&.*scan=(\d+)/';
        if($response) {
            preg_match($pattern, $response, $matchs);
            if(isset($matchs)) {
                if(isset($matchs[1]) && $matchs[1] == '200') {
                    $this->ticket = $matchs[2];
                    $this->scan = $matchs[3];
                } else {
                    throw new \Exception('获取Ticket失败');
                }
            } else {
                throw new \Exception('匹配失败');
            }
        } else {
            throw new \Exception('网络连接失败');
        }
    }

    /**
     * @description webwxnewloginpage 初始化请求获取微信登录必须的 skey wxsid wxuin   pass_ticket isgrayscale #保存登录 cookie
     * @return
     */
    public function webwxnewLoginPage()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Connection' => 'Keep-Alive'
        ];

        $params = [
            'ticket' => $this->ticket,
            'uuid' => $this->uuid,
            'scan' => $this->scan,
            'lang' => 'zh_CN',
            'fun' => 'new',
            'version' => '2'
        ];
        $response = $this->get(self::dr_url['webwx_new_login_page_url'].http_build_query($params), $headers);
        if($response) {
            $pattern = '/<ret>(\d+)<\/ret><message>(.*?)<\/message><skey>(.*?)<\/skey><wxsid>(.*?)<\/wxsid><wxuin>(.*?)<\/wxuin><pass_ticket>(.*?)<\/pass_ticket><isgrayscale>(\d+)<\/isgrayscale>/';
            preg_match($pattern, $response, $matchs);
            if(!empty($matchs)) {
                if(isset($matchs[1]) && $matchs[1] == '0') {
                    $this->skey = $matchs[3];
                    $this->wxsid = $matchs[4];
                    $this->wxuin = $matchs[5];
                    $this->pass_ticket = $matchs[6];
                    $this->isgrayscale = $matchs[7];
                }
            } else {
                throw new \Exception('获取失败');
            }
        } else {
            throw new \Exception('登陆请求无响应');
        }
    }

    /**
     * @description webwxnewloginpage 微信初始化信息 带cookie
     * @return
     */
    public function webwxInit()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json',
            'Connection' => 'Keep-Alive'
        ];

        $params = [
            'r' => time(),
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket
        ];

        $payloadData = json_encode([
            'BaseRequest' =>
            [
                'Uin' => $this->wxuin,
                'Sid' => $this->wxsid,
                'Skey' => $this->skey,
                "DeviceID" => 'e'.rand(2,17)
            ]
        ]);
        $response = $this->post(self::dr_url['webwxinit_url'].http_build_query($params), $payloadData, $headers);
        $response = json_decode($response, true);
        if($response) {
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == 0) {
                echo "微信初始化完成...\r\n";
                echo "当前网页微信共{$response['Count']}聊天(包括个人、群、公众账号)...\r\n";
                echo "当前网页微信聊天群名称...\r\n";
                $contact_list = $response['ContactList'];
                $specialusers = array_keys(self::specialusers);
                if(!empty($contact_list)) {
                    foreach($contact_list as $k => $v) {
                        // 排除特殊用户、和个人,只获取群
                        if((strpos($v['UserName'],'@@') == 0))
                        {
                           $this->grouplist[$v['UserName']] = $v;
                        }
                        if(!in_array($v['UserName'], $specialusers) && (strpos($v['UserName'],'@@') == 0)) {
                            echo '\t'.$v['NickName']."\r\n";
                        } else {
                            unset($contact_list[$k]);
                        }
                    }
                    $this->contactlist = $contact_list;
                } else {
                    echo "当前没有聊天记录\r\n";
                }
                $this->SyncKey = $response['SyncKey'];
                if(isset($response['SyncKey']['List']) && is_array($response['SyncKey']['List']))
                {
                    foreach($response['SyncKey']['List'] as $k => $v)
                    {
                        $this->syncKey .= $v['Key'].'_'.$v['Val'].'|';
                    }
                }
                $this->syncKey = rtrim($this->syncKey, '|');
                $this->profile = $response['User'];
            } else {
                throw new \Exception('登陆初始化错误,可能是未正确扫描二维码');
            }
        } else {
            throw new \Exception('登陆初始化请求无响应');
        }
    }

    /**
     * @description 开启状通知
     * @return
     */
    public function webwxStatusNotify()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json;charset=UTF-8',
            'Connection' => 'Keep-Alive'
        ];

        $params = [
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket
        ];
        $payloadData = json_encode([
            'BaseRequest' => [
                'Uin' => $this->wxuin,
                'Sid' => $this->wxsid,
                'Skey' => $this->skey,
                'DeviceID' => 'e'.rand(2,17)
            ],
            'Code' => 3,
            'FromUserName' => $this->profile['UserName'],
            'ToUserName' => $this->profile['UserName'],
            'ClientMsgId' => time()
        ]);

        $response = $this->post(self::dr_url['statusnotify_url'].http_build_query($params), $payloadData, $headers);
        if($response) {
            $response = json_decode($response, true);
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == 0)
            {
                echo "开启状态通知成功\r\n";
            } else {
                echo "开启状态通知失败\r\n";
            }
        } else {
            throw new \Exception('开启状通知请求无响应');
        }
    }
    /**
     * @description webwxnewloginpage 获取用户列表信息 带cookie
     * @return
     */
    public function getMemberList()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.8,zh-CN;q=0.6,zh;q=0.4,ja;q=0.2',
            'Host' => 'wx.qq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
            'Referer' => 'https://wx.qq.com/?lang=zh_CN',
            'Content-Type' => 'application/json;charset=UTF-8',
            'Connection' => 'Keep-Alive'
        ];

        $params = [
            'r' => time(),
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket,
            'seq' => '0',
            'skey' => $this->skey,
        ];
        $response = $this->get(self::dr_url['member_list_url'].http_build_query($params), $headers);
        if($response) {
            $response = json_decode($response, true);
            if(isset($response['BaseResponse']) && isset($response['BaseResponse']['Ret']) && $response['BaseResponse']['Ret'] == '0')
            {
                $member_list = $response['MemberList'];
                $specialusers = array_keys(self::specialusers);
                if(!empty($member_list)) {
                    foreach($member_list as $k => $v) {

                        if($v['VerifyFlag'] == 8) { // 公众服务号
                            unset($member_list[$k]);
                        } else if($v['VerifyFlag'] == 24) { // 订阅号
                            unset($member_list[$k]);
                        } else if(in_array($v['UserName'], $specialusers)) {
                            unset($member_list[$k]);
                        } else if($v['UserName'] == $this->profile['UserName']) {
                            unset($member_list[$k]);
                        }
                    }
                    $this->member_list = $member_list;
                    echo '获取到当前用户列表,共'.count($this->member_list)."位...\r\n";
                } else {
                    echo "当前没有用户列表信息\r\n";
                }
            } else {
                echo "获取用户列表信息失败\r\n";
            }
        } else {
            throw new \Exception('获取用户列表信息请求无响应');
        }
    }
    
    /**
     *@description tuling API 图灵机器人对接
     *@params words string 关键词
     *@params encoding string 传输编码格式
     *@params byteRetrun bool 二进制数据返回
     *@return mixed
     **/  
    public function tuling($words)
    {
        $errorCode = [40001, 40002, 40004, 40007];
        $headers = ['User-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.109 Safari/537.36'];
        //$words = iconv("UTF-8", "GB2312//IGNORE", $words);
        $requestData = [
            "r" => time(),
            "words" => $words
        ];
        $response = $this->get(self::dr_url['tuling_api_url'].http_build_query($requestData), $headers);
        $response = json_decode($response, true);
        if($response)
        {
             if(in_array($response['code'],$errorCode))
             {
                 return false;
             } else {
                 return $response['text'];  
             } 
        }
        return false;
    }
       
    /**
     * 基类 GET 请求
     * @param $url
     * @param array $headers
     * @param string $user_agent
     * @param bool|TRUE $cookies
     * @param string $proxy
     * @return mixed
     */
    public function get($url, $headers = [], $user_agent = '', $cookies = TRUE, $proxy = '')
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $user_agent);
        if ($cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
        if ($cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0); // 信任任何证书
        curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        //curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if (!empty($proxy)) curl_setopt($process, CURLOPT_PROXY, $proxy);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        $return = curl_exec($process);
        curl_close($process);
        return $return;
    }

    /**
     * 基类 POST 请求
     * @param $url
     * @param $data
     * @param array $headers
     * @param string $user_agent
     * @param bool|TRUE $cookies
     * @param string $proxy
     * @return mixed
     */
    public function post($url, $data, $headers = [], $user_agent = '', $cookies = TRUE, $proxy = '')
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $user_agent);
        if ($cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
        if ($cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0); // 信任任何证书
        curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        //curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if ($proxy) curl_setopt($process, CURLOPT_PROXY, $proxy);
        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($process, CURLOPT_POST, 1);
        $return = curl_exec($process);
        curl_close($process);
        return $return;

    }
}