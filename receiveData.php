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
                $response = array(
                    'flag'    => 'success',
                    'code'    => '20000',
                    'message' => '创建订单成功',
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

        } else {
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