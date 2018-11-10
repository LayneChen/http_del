<?php

class receive{
    /**
     * 描述 : WMS的appkey的密匙
     * 作者 : 高梁
     */
    private static $secret = 'sandboxbef2a72d0712f30eae1d4049e';

    /**
     * 描述 : WMS的appkey
     * 作者 : 高梁
     */
    private static $appKey = '1025237823';

    /**
     * 描述 : 奇门的URL
     * 作者 : 高梁
     */
    private static $url = "http://qimen.api.taobao.com/router/qimen/service?";

    /**
     * 描述 : 分销商名称
     * 作者 : 高梁
     */
    private static $userInfo = array(
        'oName' => ''
    );

    /**
     * 描述 : 分销商ID
     * 作者 : 高梁
     */
    private static $mallUserId = '';

    /**
     * 描述 : 接受创建发货单的请求（奇门到WMS）
     * 作者 : 高梁
     */
    public static function receiveRequest($body) {
//        $body      = file_get_contents('php://input');
        if(!self::isXML($body)) {
            echo '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <flag>failure</flag>
                <code>30033</code>
                <message>不是正确的XML数据</message>
            </response>';
            exit;
        }
        $param     = $_GET;
        $sign      = $param['sign'];
        unset($param['sign']);
        $signCheck = self::sign(self::$secret, $param , $body);                                                         //通过本地签名算法，得到校验签名

        if(!isset($param['method'])) {                                                                                  //是否为正确请求
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <flag>failure</flag>
                <code>30002</code>
                <message>无效接口</message>
            </response>';
        } else if ( $param['method'] == 'deliveryorder.create') {                                                       //接收订单
            $createTime      = date("Y-m-d H:i:s",time());
            if( $sign === $signCheck ) {
                $response = self::deliveryorderCreate($body);
            } else {
                $response = array(
                    'flag'    => 'failure',
                    'code'    => '10004',
                    'message' => 'sign签名错误',
                );
            }
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <deliveryOrderId></deliveryOrderId>
                <createTime>' . $createTime . '</createTime>
                <flag>' . $response['flag'] . '</flag>
                <code>' . $response['code'] . '</code>
                <message>' . $response['message'] . '</message>
            </response>';

        } elseif ($param['method'] == 'order.cancel') {                                                                 //取消订单
            if( $sign === $signCheck ) {
                $response = array(
                    'flag'    => 'success',
                    'code'    => '20000',
                    'message' => '取消成功',
                );
            } else {
                $response = array(
                    'flag'    => 'failure',
                    'code'    => '10004',
                    'message' => 'sign签名错误',
                );
            }
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <flag>' . $response['flag'] . '</flag>
                <code>' . $response['code'] . '</code>
                <message>' . $response['message'] . '</message>
            </response>';

        } elseif ($param['method'] == 'inventory.query') {                                                              //查询库存
            if( $sign === $signCheck ) {
                $result = self::inventoryQuery($body);
            } else {
                $response = array(
                    'flag'    => 'failure',
                    'code'    => '10004',
                    'message' => 'sign签名错误',
                    'items'   => array()
                );
            }
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <flag>' . $result['flag'] . '</flag>
                <code>' . $result['code'] . '</code>
                <message>' . $result['message'] . '</message>
                <items>';
            if(!isset($result['items']) || !is_array($result['items'])) $result['items'] = array();
            foreach ($result['items'] as $key => $val ) {
                $response .= '<item> 
                    <itemCode>' . $key . '</itemCode>  
                    <itemId>' . $key . '</itemId>  
                    <quantity>' . $val['physi'] . '</quantity> 
                    <lockQuantity>'. $val['takes'] . '</lockQuantity>
                    <inventoryType>ZP</inventoryType> 
                    <warehouseCode></warehouseCode>
                    <batchCode></batchCode>
                    <productDate></productDate>  
                    <expireDate></expireDate>  
                    <produceCode></produceCode>  
                    <extendProps>
                        <key1></key1>  
                        <key2></key2> 
                    </extendProps>
                </item>';
            }
            $response .= '</items></response>';

        }elseif ($param['method'] == 'singleitem.synchronize') {
            $createTime      = date("Y-m-d H:i:s",time());
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <deliveryOrderId></deliveryOrderId>
                <createTime>' . $createTime . '</createTime>
                <flag>' . 'success' . '</flag>
                <code>' . '20000' . '</code>
                <message>' . '同步成功' . '</message>
            </response>';
            
        }elseif($param['method'] == 'items.synchronize'){
            $createTime      = date("Y-m-d H:i:s",time());
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <deliveryOrderId></deliveryOrderId>
                <createTime>' . $createTime . '</createTime>
                <flag>' . 'success' . '</flag>
                <code>' . '20000' . '</code>
                <message>' . '同步成功' . '</message>
            </response>';
        }
        
        else {
            $response = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <flag>failure</flag>
                <code>30002</code>
                <message>无效接口</message>
            </response>';
        }

        echo $response;
    }


    /**
     * 描述 : 创建订单
     * 参数 :
     *      body : 奇门传过来的原始数据
     * 返回 :
     *      response : {
     *          flag    : success/failure
     *          code    : 响应码
     *          message : 响应信息
     *      }
     * 作者 : 高梁
     */
    public static function deliveryorderCreate(&$body) {
        $arrayInfo     = simplexml_load_string($body);                                                                  //读取xml,转换成对象数组
        $arrayInfo     = self::object_array($arrayInfo);
        $deliveryOrder = self::slashesDeep($arrayInfo['deliveryOrder']);                                                  //转义
        $xmlInfo       = self::slashesDeep($body);                                                                        //转义，转码为原始数据

        $endDate       = date("Y-m-d H:i:s",time());
        $orderLine     = self::slashesDeep($arrayInfo['orderLines']['orderLine']);                                        //转义
        $oderData      = array();
        $items         = array();
        self::getSecArray($oderData, $orderLine);

        $re = self::checkCustomerId($oderData[0]['ownerCode'], $deliveryOrder['warehouseCode']);                              //获取分销商的信息
        
        if(empty(self::$mallUserId) || $re) {
            return array(
                'flag'    => 'failure',
                'code'    => '40030',
                'message' => 'ownerCode无效',
            );
        }
        foreach ($oderData as $val) {
            $items[]    = array(
                'quantity'   => isset($val['planQty']) ? $val['planQty'] : "",
                'goodsName'  => isset($val['itemName']) ? $val['itemName'] : "",
                'goodsSn'    => isset($val['itemCode']) ? $val['itemCode'] : "",
                'goodsPrice' => isset($val['actualPrice']) ? $val['actualPrice'] : ""
            );
        }

        $extendProps       = isset($deliveryOrder['extendProps']) ? $deliveryOrder['extendProps'] : array();
        $goodsAmount       = isset($deliveryOrder['itemAmount']) && !empty($deliveryOrder['itemAmount']) ? $deliveryOrder['itemAmount'] : 0;
        $orderAmount       = isset($deliveryOrder['totalAmount']) && !empty($deliveryOrder['totalAmount']) ? $deliveryOrder['totalAmount'] : 0;
        $shippingFee       = isset($deliveryOrder['freight']) && !empty($deliveryOrder['freight']) ? $deliveryOrder['freight'] : 0;
        $moneyPaid         = isset($extendProps['paid']) && !empty($extendProps['paid']) ? $extendProps['paid'] : 0;

        $paymentOrderSn    = isset($oderData[0]['sourceOrderCode']) && empty($oderData[0]['sourceOrderCode']) ? '' : $oderData[0]['sourceOrderCode'];
        $paymentInfoName   = isset($extendProps['buyer_name']) && !empty($extendProps['buyer_name']) ? $extendProps['buyer_name'] : '';
        $idCardNumber      = isset($extendProps['id_card']) && !empty($extendProps['id_card']) ? $extendProps['id_card'] : '';
        $paymentInfoNumber = isset($extendProps['pay_id']) && !empty($extendProps['pay_id']) ? $extendProps['pay_id'] : '';
        $paymentInfoMethod = isset($extendProps['pay_ent_name']) && !empty($extendProps['pay_ent_name']) ? $extendProps['pay_ent_name'] : '';
        $paymentInfoCode   = isset($extendProps['pay_ent_no']) && !empty($extendProps['pay_ent_no']) ? $extendProps['pay_ent_no'] : '';
        $ordersInfo  =array();
        $orderInfo   = array(
            'orderSn'           => (isset($deliveryOrder['deliveryOrderCode']) ? $deliveryOrder['deliveryOrderCode'] : "") . '_' . self::$mallUserId . '_',
            'apiId'             => self::$mallUserId,
            'buyerNick'         => isset($deliveryOrder['senderInfo']['name']) && !empty($deliveryOrder['senderInfo']['name']) ? $deliveryOrder['senderInfo']['name'] : "",
            'paymentFlowNumber' => $paymentInfoNumber,
            'consignee'         => isset($deliveryOrder['receiverInfo']['name']) ? $deliveryOrder['receiverInfo']['name'] : "",
            'country'           => '中国',
            'province'          => isset($deliveryOrder['receiverInfo']['province']) ? $deliveryOrder['receiverInfo']['province'] : "",
            'city'              => isset($deliveryOrder['receiverInfo']['city']) ? $deliveryOrder['receiverInfo']['city'] : "",
            'district'          => (isset($deliveryOrder['receiverInfo']['area']) ? $deliveryOrder['receiverInfo']['area'] : "")
                . (isset($deliveryOrder['receiverInfo']['town']) ? $deliveryOrder['receiverInfo']['town'] : ""),
            'address'           => isset($deliveryOrder['receiverInfo']['detailAddress']) ? $deliveryOrder['receiverInfo']['detailAddress'] : "",
            'zipcode'           => isset($deliveryOrder['receiverInfo']['zipCode']) && !empty($deliveryOrder['receiverInfo']['zipCode']) ? $deliveryOrder['receiverInfo']['zipCode'] : "",
            'tel'               => isset($deliveryOrder['receiverInfo']['tel']) && !empty($deliveryOrder['receiverInfo']['tel']) ? $deliveryOrder['receiverInfo']['tel'] : "",
            'mobile'            => isset($deliveryOrder['receiverInfo']['mobile']) && !empty($deliveryOrder['receiverInfo']['mobile']) ? $deliveryOrder['receiverInfo']['mobile'] : "",
            'siteType'          => 'qimenWMS',
            'siteName'          => isset($deliveryOrder['shopNick']) && !empty($deliveryOrder['shopNick']) ? $deliveryOrder['shopNick'] : "",
            'siteUrl'           => '',
            'consumerNote'      => isset($deliveryOrder['buyerMessage']) && !empty($deliveryOrder['buyerMessage']) ? $deliveryOrder['buyerMessage']: "",
            'goodsAmount'       => $goodsAmount,                                                                        //商品金额
            'shippingFee'       => $shippingFee,                                                                        //运费
            'orderAmount'       => $orderAmount,                                                                        //订单金额
            'moneyPaid'         => $moneyPaid,                                                                          //支付金额
            'idCardNumber'            => $idCardNumber,
            'paymentOrderSn'          => $paymentOrderSn,                                                               //推送支付企业的订单号
            'paymentInfoCode'         => $paymentInfoCode,                                                              //企业备案号
            'paymentInfoMethod'       => $paymentInfoMethod,                                                            //支付方式
            'paymentInfoNumber'       => $paymentInfoNumber,                                                            //支付流水号
            'paymentInfoName'         => $paymentInfoName,                                                              //支付人
            'paymentInfoIdCardNumber' => $idCardNumber,                                                                 //支付人的身份证
            'paymentAccount'          => "htdolphin@163.com",
            'receiptor'         => '',
            'isCheck'           => false,
            'items'             => $items
        );                                                                                                              //组成订单
        $ordersInfo[] = $orderInfo;                                                                                     //推送订单必要信息

//        open_mode_default_dealer_order::updateMallOrder($orderInfo["orderSn"], $orderInfo["apiId"], $xmlInfo, $endDate);//插入原始数据
//        $result = open_mode_default_dealer_order::pushOrderDataInfo(self::$userInfo, $ordersInfo, true);                //推送网站，同步到API
        if( isset($ordersInfo) ) {
            $response = array(
                'flag'    => 'success',
                'code'    => '20000',
                'message' => '创建订单成功',
            );
        } else {
            $response = array(
                'flag'    => 'failure',
                'code'    => '40015',
                'message' => '创建失败',
            );
        }
        return $response;
    }

    /**
     * 描述 : 取消订单
     * 参数 :
     *      body : 奇门传过来的原始数据
     * 返回 :
     *      response : {
     *          flag    : success/failure
     *          code    : 响应码
     *          message : 响应信息
     *      }
     * 作者 : 高梁
     */
    public static function orderCancel(&$body) {
        $arrayInfo  = simplexml_load_string($body);                                                                     //读取xml,转换成对象数组
        $arrayInfo  = self::object_array($arrayInfo);
        self::slashesDeep($arrayInfo);                                                                                    //转义

        self::checkCustomerId($arrayInfo['ownerCode'], $arrayInfo['warehouseCode']);                                    //得到分销商的信息
        if(empty(self::$mallUserId)) {
            return array(
                'flag'    => 'failure',
                'code'    => '40030',
                'message' => 'ownerCode无效',
            );
        }

        $data = array(
            'orderSn' => $arrayInfo['orderCode'] . '_' . self::$mallUserId . '_'
        );
        $result = FALSE;
//        $result = open_mode_default_dealer_order::cancel(self::$userInfo, $data);                                       //调用API取消订单接口
        if($result) {
            $response = array(
                'flag'    => 'success',
                'code'    => '20000',
                'message' => '取消成功',
            );
        } else {
            $response = array(
                'flag'    => 'failure',
                'code'    => '40008',
                'message' => $result['data'],
            );
        }
//        file_put_contents('time.txt',
//            $data["orderSn"] . '取消时间戳:' . open_mall_qimen_sdk_tool::getTime() . PHP_EOL, FILE_APPEND
//        );
        return $response;
    }

    /**
     * 描述 : 生成签名
     * 参数 :
     *      secret : App Secret
     *      param  : {
     *          method      : 请求的气门方法
     *          format      : 数据格式，默认XML
     *          app_key     : App Key
     *          v           : 奇门版本，默认2.0
     *          sign_method : md5
     *          customerId  : WMS颁发给用户的ID
     *          timestamp   : 当前时间
     *      }
     *      body   : 数据内容
     * 返回 :
     * 作者 : 高梁
     */
    public static function sign($secret, $param, $body) {
        ksort($param);
        $outputStr = '';
        foreach ($param as $k => &$v) {
            $outputStr .= $k . $v;
        }
        $outputStr = $secret . $outputStr . $body . $secret;
        return strtoupper(md5($outputStr));
    }



    /**
     * 描述 : 判断是否为XML
     * 返回 : TRUE：是；FALSE：不是
     * 作者 : 高梁
     */
    private static function isXML($xmlStr){
        $xml_parser = xml_parser_create();
        $result = xml_parse($xml_parser, $xmlStr, true);
        xml_parser_free($xml_parser);
        return $result;
    }

    /**
     * 描述 : 深度遍历对象或数组
     * 参数 :
     *      array  : 数组或对象
     * 返回 :
     *      array 数组
     * 作者 : 高梁
     */
    private static function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $key = (string)$key;
                $array[$key] = self::object_array($value);
            }
        }
        return $array;
    }

    /**
     * 描述 : 转换成二位数组访问
     * 参数 :
     *      &secArray  : 转换的数组
     *      data       : 数据
     * 作者 : 高梁
     */
    private static function getSecArray(&$secArray,$data) {
        if(isset($data[0])){
            $secArray   = $data;
        }else{
            $secArray[] = $data;
        }
    }


    /********************************************************************工具类*******************************************
     * 描述 : 深度加/删反斜杠
     * 参数 :
     *     &data : 指定替换的数组
     *      func : addslashes(默认)=添加反斜杠, stripslashes=删除反斜杠
     * 作者 : Edgar.lee
     */
    public static function &slashesDeep(&$data, $func = 'addslashes') {
        $waitList = array(&$data);                                                                                      //待处理列表

        do {
            $wk = key($waitList);
            $wv = &$waitList[$wk];
            unset($waitList[$wk]);

            if( is_array($wv) ) {
                $result = array();                                                                                      //结果列表
                foreach($wv as $k => &$v) {
                    $result[$func($k)] = &$v;
                    $waitList[] = &$v;
                }
                $wv = $result;
            } else if( is_string($wv) ) {
                $wv = $func($wv);
            }
        } while( !empty($waitList) );

        return $data;
    }

    /**
     * 描述 : 检查奇门ERP的身份
     * 参数 :
     *      $customerId : 对接奇门ERP的身份ID
     * 作者 : 高梁
     */
    private static function checkCustomerId($customerId, $warehouseCode) {
        return $customerId =='HTC00006'  && $warehouseCode == 'HTW00006' ? TRUE : FALSE;
    }

}



$body = file_get_contents("php://input");
if(isset($_REQUEST)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[request]:".print_r($_REQUEST,TRUE),FILE_APPEND);
}
if(isset($body)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[body]:".print_r($body,TRUE),FILE_APPEND);
}

$receive = new receive();
$receive::receiveRequest($body);