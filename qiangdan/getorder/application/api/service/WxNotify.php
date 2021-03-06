<?php


namespace app\api\service;


use think\Db;
use think\Exception;
use think\Loader;
use think\Log;
use JPush\Client as JPush;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

//Loader::import('WxPay.WxPay', EXTEND_PATH, '.Data.php');


class WxNotify extends \WxPayNotify
{
//    protected $data = <<<EOD
//<xml><appid><![CDATA[wxaaf1c852597e365b]]></appid>
//<bank_type><![CDATA[CFT]]></bank_type>
//<cash_fee><![CDATA[1]]></cash_fee>
//<fee_type><![CDATA[CNY]]></fee_type>
//<is_subscribe><![CDATA[N]]></is_subscribe>
//<mch_id><![CDATA[1392378802]]></mch_id>
//<nonce_str><![CDATA[k66j676kzd3tqq2sr3023ogeqrg4np9z]]></nonce_str>
//<openid><![CDATA[ojID50G-cjUsFMJ0PjgDXt9iqoOo]]></openid>
//<out_trade_no><![CDATA[A301089188132321]]></out_trade_no>
//<result_code><![CDATA[SUCCESS]]></result_code>
//<return_code><![CDATA[SUCCESS]]></return_code>
//<sign><![CDATA[944E2F9AF80204201177B91CEADD5AEC]]></sign>
//<time_end><![CDATA[20170301030852]]></time_end>
//<total_fee>1</total_fee>
//<trade_type><![CDATA[JSAPI]]></trade_type>
//<transaction_id><![CDATA[4004312001201703011727741547]]></transaction_id>
//</xml>
//EOD;

    public function NotifyProcess($data, &$msg)
    {
        if ($data['result_code'] == 'SUCCESS') {
            $orderNo = $data['out_trade_no'];
            Db::startTrans();
            try {
                 $order = \db('order')->where('orderid', $orderNo)->lock(true)->find();
                //  Order::where('order_no', '=', $orderNo)->lock(true)->find();
                $this->getcode();
                 if ($order['order_status'] == 1) {
                    \db('order')->where('orderid', $orderNo)->update(['order_status' => 2]);
                }
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                // 如果出现异常，向微信返回false，请求重新发送通知
                return false;
            }
        }
        return true;
    }


    public function getcode()
    {
        $client = new JPush(config('wx.jpush_id'), config('wx.jpush_secret'));
        $client->push()
            ->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert('您有新的订单，请注意查收')
            ->send();
    }
}