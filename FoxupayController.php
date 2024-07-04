<?php

namespace App\Http\Controllers\Pay;


use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class FoxupayController extends PayController
{
    const rate = 7.2;

    public function gateway(string $payway, string $orderSN)
    {
        try {
            // 加载网关
            $this->loadGateWay($orderSN, $payway);
            //构造要请求的参数数组，无需改动
            $parameter = [
                "uid" => $this->payGateway->merchant_id,
                "amount" => round(((float)$this->order->actual_price) / self::rate, 6),//原价
                'receiveAddress' => $this->payGateway->merchant_key,
                'outNo' => $this->order->order_sn,
                'redirectUrl' => route('foxupay-return', ['order_id' => $this->order->order_sn]),
                'notifyUrl' => url($this->payGateway->pay_handleroute . '/notify_url'),
            ];
            $parameter['signature'] = $this->VerifySign($parameter, $this->payGateway->merchant_pem);
            $client = new Client([
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
            ]);
            $response = $client->post('http://upay.daguoli.cn/api/trade/create', ['body' => $this->getData($parameter)]);
            $body = (string)$response->getBody(); // 将响应体转换为字符串
            $body = json_decode($body, true);
            if (!isset($body['code']) || $body['code'] != 200) {
                return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $body['msg']);
            }
            return redirect()->away($body['data']['url']);
        } catch (RuleValidationException $exception) {
        } catch (GuzzleException $exception) {
            return $this->err($exception->getMessage());
        }
    }

    private function getData(array $parameter)
    {
        $sign = '';
        foreach ($parameter as $key => $val) {
            if ($val != '') {
                if ($sign != '') {
                    $sign .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
            }
        }
        return $sign;
    }

    private function VerifySign(array $parameter, string $signKey)
    {
        ksort($parameter);
        reset($parameter); //内部指针指向数组中的第一个元素
        $sign = '';
        foreach ($parameter as $key => $val) {
            if ($val != '') {
                if ($key != 'signature') {
                    if ($sign != '') {
                        $sign .= "&";
                    }
                    $sign .= "$key=$val"; //拼接为url参数形式
                }
            }
        }
        //密码追加进入开始MD5签名
        return hash("sha256", $sign . $signKey);
    }

    public function notifyUrl(Request $request)
    {
        $data = $request->all();
        $order = $this->orderService->detailOrderSN($data['outNo']);
        if (!$order) {
            return 'fail1';
        }
        $payGateway = $this->payService->detail($order->pay_id);
        if (!$payGateway) {
            return 'fail2';
        }
        if ($payGateway->pay_handleroute != '/pay/foxupay') {
            return 'fail3';
        }
        //合法的数据
        $signature = $this->VerifySign($data, $payGateway->merchant_pem);
        if ($data['signature'] != $signature) { //不合法的数据
            return 'fail4';  //返回失败 继续补单
        } else {
            //合法的数据
            //业务处理
            $amount = round($data['amount'] * self::rate, 2);
            $this->orderProcessService->completedOrder($data['outNo'], $amount, $data['tradeNo']);
            return 'ok';
        }
    }

    public function returnUrl(Request $request)
    {
        $oid = $request->get('order_id');
        // 异步通知还没到就跳转了，所以这里休眠2秒
        sleep(2);
        return redirect(url('detail-order-sn', ['orderSN' => $oid]));
    }

}
