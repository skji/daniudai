<?php

namespace frontend\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\Json;
use Overtrue\Wechat\Auth;
use Overtrue\Wechat\Notice;
use Overtrue\Wechat\Staff;
use Overtrue\Wechat\Js;
use Yeepay\YeepayMPay;
use backend\models\User; 
use backend\models\Loan; 
use backend\models\Student;
use backend\models\School;
use backend\models\Bank;
use backend\models\Yeepay;
use Lixunguan\Yuntongxun\Sdk as Yuntongxun;

class LoanController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    public function actionBank()
    {
        session_start();
        $user = $_SESSION['user'];

        $s = Student::findOne($user['openid']);
        $u = User::findOne($user['openid']);
        $l = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status<1'])->addParams([':wechat_id'=>$user['openid']])->one();

        if (isset($_POST['stu_id'])) {
            $mail = $_POST['email'];
            $stu_id = $_POST['stu_id'];
            $wechat_id = Yii::$app->db->createCommand('SELECT l.wechat_id FROM loan l LEFT JOIN student s ON l.wechat_id=s.wechat_id WHERE (s.stu_id=:stu_id OR s.mail=:mail) AND l.status>1 AND l.status!=4')->bindValues([':stu_id'=>$stu_id, ':mail'=>$mail])->queryScalar();
            if ($wechat_id==FALSE) {
                $school_id = $_POST['school_id'];
                $dorm = $_POST['dorm'];
                $grade = $_POST['grade'];
                $name = $_POST['name'];

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (!isset($s)) {
                        $s = new Student;
                        $s->wechat_id = $user['openid'];
                    }
                    $s->stu_id = $stu_id;
                    $s->school_id = $school_id;
                    $s->dorm = $dorm;
                    $s->grade = $grade;
                    $s->mail = $mail;
                    $s->created_at = time();
                    $s->save();

                    $u->name = $name;
                    $u->updateAttributes(['name']);
                    $transaction->commit();
                } catch(\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
                return json_encode(['stat'=>1]);
            } else {
                return json_encode(['stat'=>2]);
            }
        } else {
            $appId = Yii::$app->params['wechat_appid'];
            $secret = Yii::$app->params['wechat_appsecret'];
            $js = new Js($appId, $secret);
            return $this->renderPartial('bank', ['v'=>Yii::$app->params['assets_version'],'user'=>$u,'loan'=>$l,'js'=>$js]);
        }
    }

    public function actionLend($type='common')
    {
        session_start();
        $user = $_SESSION['user'];
        //$rate = ($type=='common')?0.0002:0.0001;
        $rate = 0.0003;
        $range = 10000 - Yii::$app->db->createCommand('SELECT SUM(money) FROM loan WHERE (status=3 OR status=2 OR status=1) AND wechat_id=:wechat_id')->bindValue(':wechat_id', $user['openid'])->queryScalar();
        $is_auth = 0;
        $u = User::findOne($user['openid']);
        $l = Yii::$app->db->createCommand('SELECT loan_id FROM loan WHERE status>=1 AND wechat_id=:wechat_id')->bindValue(':wechat_id', $user['openid'])->queryScalar();
        if (isset($u) and $l!=FALSE and $u->bank_id!='') {
            if ($u->auth_code=='') {
                return $this->redirect(['loan/auth']);
            } else {
                $is_auth = 1;
            }
        }
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];
        $js = new Js($appId, $secret); 
        return $this->renderPartial('lend', ['v'=>Yii::$app->params['assets_version'], 'range'=>$range, 'is_auth'=>$is_auth, 'rate'=>$rate,'js'=>$js]);
    }

    public function actionIndex()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/index'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }
        $user = $_SESSION['user'];
        
        $open_id = $user['openid'];

        $u = User::findOne($open_id);
        if (!isset($u)) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $u = new User;
                $u->wechat_id = $open_id;
                $u->name = '';
                $u->id = '';
                $u->mobile = '';
                $u->bank = '';
                $u->bank_id = '';
                $u->created_at = time();
                $u->save();
                $transaction->commit();
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            if ($u->ban>=3) {
                return $this->redirect(['loan/ban']);
            }
            if ($u->verify_times<1) {
                return $this->redirect(['loan/failed']);
            }
            $l = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status=1'])->addParams([':wechat_id'=>$user['openid']])->one();
            if (isset($l)) {
                return $this->redirect(['loan/reviewing']);
            }
            $range = 10000 - Yii::$app->db->createCommand('SELECT SUM(money) FROM loan WHERE (status=3 OR status=2 OR status=1) AND wechat_id=:wechat_id')->bindValue(':wechat_id', $user['openid'])->queryScalar();
            if ($range<=0) {
                return $this->redirect(['loan/repays']);
            }
        }
        $js = new Js($appId, $secret);
        return $this->renderPartial('index',['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);
    }

    public function actionSchool()
    {
        session_start();
        $user = $_SESSION['user'];
        $duration = $_REQUEST['duration'];
        $rate = $_REQUEST['rate'];
        $is_auth = $_REQUEST['is_auth'];        
        $range = 10000 - Yii::$app->db->createCommand('SELECT SUM(money) FROM loan WHERE (status=3 OR status=2 OR status=1) AND wechat_id=:wechat_id')->bindValue(':wechat_id', $user['openid'])->queryScalar();
        if ($range<=0) {
            return $this->redirect(['loan/repays']);
        }
        $money = min($range, $_REQUEST['money']);
        $loan = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status<1'])->addParams([':wechat_id'=>$user['openid']])->one();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (isset($loan)) {
                $loan->money = $money;
                $loan->duration = $duration;
                $loan->rate = $rate;
                $loan->start_at = time();
                $loan->end_at = time()+$duration*3600*24;
                $loan->status = 0;
                $loan->save();
            } else if (!isset($loan)) {
                $loan = new Loan;
                $loan->wechat_id = $user['openid'];
                $loan->money = $money;
                $loan->duration = $duration;
                $loan->rate = $rate;
                $loan->status = 0;
                $loan->start_at = time();
                $loan->end_at = time()+$duration*3600*24;
                $loan->created_at = time();
                $loan->save();
            }
            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];
        $js = new Js($appId, $secret); 
        if ($is_auth==1) {
            return $this->redirect(['loan/success']);
        } else {
            return $this->renderPartial('school', ['v'=>Yii::$app->params['assets_version'],'from'=>$rate==0.0001?'graduate':'common', 'js'=>$js]);
        }
    }

    public function actionVerify()
    {
        session_start();
        $user = $_SESSION['user'];
        $u = User::findOne($user['openid']);

        $account = Yii::$app->params['unionpay_account'];
        $privatekey = Yii::$app->params['unionpay_privatekey'];
        $name = $_POST['name'];
        $card = $_POST['bank_card'];
        $cid = $_POST['id_card'];
        $mobile = $_POST['mobile'];
        $bank_name = isset($_POST['bank_name'])?$_POST['bank_name']:'';

        $b1 = Bank::find()
            ->select('bank.*')
            ->leftJoin('loan', '`loan`.`wechat_id`=`bank`.`wechat_id`')
            ->where(['and', "bank.card=$card", 'loan.status>1 and loan.status!=4'])
            ->one();
        $b1 = Bank::find()
            ->select('bank.*')
            ->leftJoin('loan', '`loan`.`wechat_id`=`bank`.`wechat_id`')
            ->where(['and', "bank.cid='$cid'", 'loan.status>1 and loan.status!=4'])
            ->one();
        if (isset($b1)||isset($b2)) {
            $resCode = '0000';
            $stat = 5;
            $resMsg = '身份信息被占用';
        } else if ($u->verify_times>0) {
            $bank = Bank::findOne(['card'=>$card, 'cid'=>$cid, 'mobile'=>$mobile, 'name'=>$name]);
            if (isset($bank)) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $u->id = $cid;
                    $u->mobile = $mobile;
                    $u->bank = $bank_name;
                    $u->bank_id = $card;
                    $u->save();

                    $bank = Bank::findOne($user['openid']);
                    if (!isset($bank)) {
                        $bank = new Bank;
                    }
                    $bank->wechat_id = $user['openid'];
                    $bank->card = $card;
                    $bank->cid = $cid;
                    $bank->mobile = $mobile;
                    $bank->name = $name;
                    $bank->created_at = time();
                    $bank->save();

                    $transaction->commit();
                } catch(\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
                $resCode = '0000';
                $stat = 1;
                $resMsg = '验证成功';
            } else {
                $b1 = Bank::findOne(['cid'=>$cid]);
                $b2 = Bank::findOne(['card'=>$card]);
                if (isset($b1)&&$b1->name!=$name) {
                    $resCode = '0000';
                    $stat = 2;
                    $resMsg = '验证失败';
                } else if (isset($b2)&&($b2->name!=$name||$b2->cid!=$cid)) {
                    $resCode = '0000';
                    $stat = 2;
                    $resMsg = '验证失败';
                } else {
                    $type = 3;
                    $sign = strtoupper(md5('account'.$account.'cid'.$cid.'name'.$name.'type'.$type.$privatekey));
                    $response_type_3 = file_get_contents(Yii::$app->params['unionpay_route'].'?account='.$account.'&name='.$name.'&cid='.$cid.'&type='.$type.'&sign='.$sign);
                    $json_obj = json_decode($response_type_3);

                    if ($json_obj->resCode=='0000'&&$json_obj->stat==1) {
                        $type = 1;
                        $sign = strtoupper(md5('account'.$account.'card'.$card.'name'.$name.'type'.$type.$privatekey));
                        $response_type_1 = file_get_contents(Yii::$app->params['unionpay_route'].'?account='.$account.'&card='.$card.'&name='.$name.'&type='.$type.'&sign='.$sign);
                        $json_obj = json_decode($response_type_1);

                        if ($json_obj->resCode=='0000'&&$json_obj->stat==1) {
                            $user = $_SESSION['user'];
                            $u = User::findOne($user['openid']);

                            $transaction = Yii::$app->db->beginTransaction();
                            try {
                                $u->id = $cid;
                                $u->mobile = $mobile;
                                $u->bank = $bank_name;
                                $u->bank_id = $card;
                                $u->save();

                                $bank = Bank::findOne($user['openid']);
                                if (!isset($bank)) {
                                    $bank = new Bank;
                                }
                                $bank->wechat_id = $user['openid'];
                                $bank->card = $card;
                                $bank->cid = $cid;
                                $bank->mobile = $mobile;
                                $bank->name = $name;
                                $bank->created_at = time();
                                $bank->save();

                                $transaction->commit();
                            } catch(\Exception $e) {
                                $transaction->rollBack();
                                throw $e;
                            }
                        }
                    }

                    $resCode = $json_obj->resCode;
                    $resMsg = $json_obj->resMsg;
                    $stat = $json_obj->stat;
                }

                if ($stat==2) {
                    $u->verify_times--;
                    $u->updateAttributes(['verify_times']);
                }
            }
        } else {
            $resCode = '0000';
            $stat = 2;
            $resMsg = '验证次数超过上限';
        }
        return json_encode(['resCode'=>$resCode, 'resMsg'=>$resMsg, 'stat'=>$stat, 'verify_times'=>$u->verify_times, 'mobile'=>$mobile]);
    }

    public function actionMail()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $mail = $_REQUEST['email'];
        $code = isset($_REQUEST['code'])?$_REQUEST['code']:0;

        session_start();
        $user = $_SESSION['user'];
        $u = User::findOne($user['openid']);

        if ($code!=0) {
            if ($_SESSION['mail_code']==$code OR Yii::$app->params['tmp_mail_code']==$code) {
                $result = 1;
            } else {
                $result = 0;
            }
            return json_encode(['isSuccess'=>$result]);
        } else {
            if (!isset($_SESSION['mail_send_time']) or time()-$_SESSION['mail_send_time']>60) {
                $code = $_SESSION['mail_code'] = rand(100000, 999999);

                Yii::$app->mailer->compose()
                    ->setTo($mail)
                    ->setSubject('【真牛贷】验证邮件')
                    ->setHtmlBody('<p>尊敬的'.$u->name.',这封信来自【真牛贷】。</p><br />'.
                              '<p>您的验证码是:'.$code.'</p><br />'.
                              '<p>您收到这封邮件，是由于您正在申请【真牛贷】。如果不是您本人操作，请联系【真牛贷】微信公众号（昵称“真牛贷”）</p><br />'.
                              '<p>我们会做您最贴心的“小银行”，到永远~')
                    ->send();
                $_SESSION['mail_send_time'] = time();
            }

            if (Yii::$app->request->getIsAjax()) {
                return json_encode(['isSend'=>1]);
            } else {
                $js = new Js($appId, $secret); 

                return $this->renderPartial('mail', ['v'=>Yii::$app->params['assets_version'], 'email'=>$mail, 'js'=>$js]);
            }
        }
    }

    public function actionSms()
    {
        include Yii::getAlias("@frontend/widgets")."/Smsapi.php";

        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $mobile = $_REQUEST['mobile'];
        $code = isset($_REQUEST['code'])?$_REQUEST['code']:0;

        session_start();
        if ($code!=0&&$code!=1) {
            if ($_SESSION['sms_code']==$code OR Yii::$app->params['tmp_sms_code']==$code) {
                $user = $_SESSION['user'];
                $u = User::findOne($user['openid']);
                $auth_code = $u->auth_code;
                if ($auth_code!='') {
                    $auth = 1;
                } else {
                    $auth = 0;
                }

                $result = 1;
            } else {
                $result = 0;
                $auth = 0;
            }
            return json_encode(['isSuccess'=>$result, 'auth'=>$auth]);
        } else if ($code==1) {
            if (!isset($_SESSION['sms_send_time']) or time()-$_SESSION['sms_send_time']>60) {
                $code = $_SESSION['sms_code'] = rand(100000, 999999);
                $sms = new Yuntongxun(Yii::$app->params['ytx_appId'], Yii::$app->params['ytx_accountId'], Yii::$app->params['ytx_token']);
                $sms->sendTemplateSMS($mobile, [$code, 1], 49629);
                $_SESSION['sms_send_time'] = time();

                Yii::$app->mailer->compose()
                    ->setTo('zhenniujun@zhenniudai.com')
                    ->setSubject('【真牛贷】'.$mobile.'_短信验证')
                    ->setHtmlBody($code)
                    ->send();
            }

            return json_encode(['isSend'=>1]);
        }
        $js = new Js($appId, $secret); 

        return $this->renderPartial('sms', ['v'=>Yii::$app->params['assets_version'], 'mobile'=>$mobile, 'js'=>$js]);
    }

    public function actionBan()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 

        return $this->renderPartial('ban', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);

    }

    public function actionEmpty()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 

        return $this->renderPartial('empty', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);

    }

    public function actionReviewing()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 

        return $this->renderPartial('reviewing', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);

    }
    
    public function actionFailed()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 

        return $this->renderPartial('failed', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);

    }

    public function actionPasswordok()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 

        return $this->renderPartial('passwordok', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);

    }

    public function actionSuccess()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (!isset($_SESSION['user'])) {
            return $this->redirect(['loan/index']);
        }
        $user = $_SESSION['user'];
        $u = User::findOne($user['openid']);
        $l = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status<2'])->addParams([':wechat_id'=>$user['openid']])->one();
        $student = Student::findOne($user['openid']);

        if ($l->status<=0) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $l->status = 1;
                $l->updateAttributes(['status']);
                $transaction->commit();
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
            $notice = new Notice($appId, $secret);
            //通知放款员面签
            $s = School::findOne($student->school_id);
            $templateId = Yii::$app->params['templateId_task'];
            $url = Url::to(['loan/me'],TRUE);
            $color = '#FF0000';
            $data = array(
                "first"    => "真牛君呐，又来了一位同学",
                "keyword1" => "{$u->name}，借款{$l->money}元，借{$l->duration}天，手机号{$u->mobile}，学校{$s->name}, 专业{$s->depart}，年级{$student->grade}",
                "keyword2" => "待办",
                "remark"   => "请快速约起来~",
            );
            $university = floor($student->school_id/100);
            $userIds = Yii::$app->params[$university.'_supporter'];
           
            foreach ($userIds as $userId) {
                $messageId = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
            }
            $messageId = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver(Yii::$app->params['demo_supporter'])->send();
        }

        $js = new Js($appId, $secret); 
        if ($l->status==1) {
            return $this->renderPartial('success', ['js'=>$js]);
        //} else if ($l->status>1) {
        //  return $this->renderPartial('success2', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);
        } else {
            return $this->redirect(['loan/index']);
        }
    }

    public function actionMe()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/me'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }
        $user = $_SESSION['user'];

        $open_id = $user['openid'];
        if (in_array($open_id,Yii::$app->params['101_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "101%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['102_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "102%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['103_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "103%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['104_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "104%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['105_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "105%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['106_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "106%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['107_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "107%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['108_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "108%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['109_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "109%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if(in_array($open_id,Yii::$app->params['110_supporter'])) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE stu.school_id LIKE "110%" AND l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
        } else if($open_id==Yii::$app->params['demo_supporter']) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.mobile,s.depart, l.loan_id,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN student stu ON u.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE l.status>=1 ORDER BY l.status ASC, l.updated_at DESC')->queryAll();
            return $this->renderPartial('personal_list',['r'=>$r]);
            //$r = Yii::$app->db->createCommand('SELECT u.name,u.bank,u.bank_id, l.loan_id,l.money,l.duration,t.name AS reviewer,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN user t ON l.reviewer=t.wechat_id WHERE l.status=2')->queryAll();
            //return $this->renderPartial('bank_list', ['verification'=>'demo','r'=>$r]);
        } else if($open_id==Yii::$app->params['admin_supporter']) {
            $r = Yii::$app->db->createCommand('SELECT u.name,u.bank,u.bank_id, l.loan_id,l.money,l.duration,t.name AS reviewer,l.status FROM user u LEFT JOIN loan l ON u.wechat_id=l.wechat_id LEFT JOIN user t ON l.reviewer=t.wechat_id WHERE l.status=2')->queryAll();
            return $this->renderPartial('bank_list', ['verification'=>'admin','r'=>$r]);
        } else {
            $l = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status<4'])->addParams([':wechat_id'=>$user['openid']])->one();
            if (isset($l) AND $l->status>=1) {
                return $this->redirect(['loan/repays']);
            } else {
                return $this->redirect(['loan/index']);
            }
        }
    }

    public function actionPerson($loan_id)
    {
        session_start();
        $user = $_SESSION['user'];
        $open_id = $user['openid'];
        if (in_array($open_id,Yii::$app->params['supporters'])) {
            $r = Yii::$app->db->createCommand('SELECT l.loan_id,l.rate,l.duration,l.money,u.name,u.id,stu.dorm,stu.stu_id,.s.depart,u.mobile FROM loan l LEFT JOIN user u ON l.wechat_id=u.wechat_id LEFT JOIN student stu ON l.wechat_id=stu.wechat_id LEFT JOIN school s ON stu.school_id=s.school_id WHERE l.loan_id=:loan_id')->bindValue(':loan_id',$loan_id)->queryOne();
            return $this->renderPartial('personal_details', ['r'=>$r]);
        } else {
            return $this->redirect(['site/error']);
        }

    }

    public function actionOperate($loan_id, $operation=-1)
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/me'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }
        $user = $_SESSION['user'];
        $open_id = $user['openid'];
        if (($operation==-1 OR $operation==2) AND (in_array($open_id,Yii::$app->params['supporters']))) {
            $l = Loan::findOne($loan_id);
            $u = User::findOne($l->wechat_id);
            $s = Student::findOne($l->wechat_id);
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($operation==2) {
                    Yii::$app->db->createCommand('UPDATE loan l LEFT JOIN student s ON l.wechat_id=s.wechat_id SET l.status=-1 WHERE s.stu_id=:stu_id AND l.status=1')->bindValue(':stu_id', $s->stu_id)->execute();
                }
                $l->reviewer = $open_id;
                $l->status = $operation;
                $l->updateAttributes(['reviewer', 'status']);

                $transaction->commit();
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

            $notice = new Notice($appId, $secret);
            if ($operation==2) {
                $school = School::findOne($s->school_id);
                $templateId = Yii::$app->params['templateId_review'];
                $url1 = Url::to(['loan/me'],TRUE);
                $color = '#FF0000';
                $data1 = array(
                    "first"    => "又一位同学{$u->name}通过审核！",
                    "keyword1" => "{$l->money}元",
                    "keyword2" => "{$l->duration}天",
                    "keyword3" => "{$l->rate}*每个月的天数",
                    "keyword4" => "通过",
                    "remark"   => "姓名：{$u->name}，学校：{$school->name}, 银行类别：{$u->bank}，银行卡号：{$u->bank_id}，借款额{$l->money}元，手机：{$u->mobile}",
                );
                $messageId = $notice->uses($templateId)->withUrl($url1)->andData($data1)->andReceiver(Yii::$app->params['demo_supporter'])->send();
                $messageId = $notice->uses($templateId)->withUrl($url1)->andData($data1)->andReceiver(Yii::$app->params['admin_supporter'])->send();
                $url2 = Url::to(['loan/success'],TRUE);
                $data2 = array(
                    "first"    => "您好！您的借款申请已通过审核",
                    "keyword1" => "{$l->money}元",
                    "keyword2" => "{$l->duration}天",
                    "keyword3" => "{$l->rate}*每个月的天数",
                    "keyword4" => "通过",
                    "remark"   => "我们会在 24 小时内给您汇款，请耐心等待。",
                );
                $messageId = $notice->uses($templateId)->withUrl($url2)->andData($data2)->andReceiver($l->wechat_id)->send();
            } else if ($operation==-1) {
                $u = User::findOne($l->wechat_id);
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $u->ban++;
                    $u->updateAttributes(['ban']);
                    
                    $transaction->commit();
                } catch(\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
                
                $templateId = Yii::$app->params['templateId_review'];
                $url = Url::to(['loan/failed'],TRUE);
                $data = array(
                    "first"    => "您好！您没有通过审核",
                    "keyword1" => "{$l->money}元",
                    "keyword2" => "{$l->duration}天",
                    "keyword3" => "{$l->rate}*每个月的天数",
                    "keyword4" => "未通过",
                    "remark"   => "请核对借款条件及个人信息后再提出申请，谢谢关注。",
                );
                $messageId = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($l->wechat_id)->send();
            }
        } else if ($operation==3 AND $open_id==Yii::$app->params['admin_supporter']) {
            $l = Loan::findOne($loan_id);
            $u = User::findOne($l->wechat_id);
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $l->status = $operation;
                $l->start_at = time();
                $l->end_at = time()+$l->duration*3600*24;
                $l->updateAttributes(['status','start_at','end_at']);
                $transaction->commit();
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

            $notice = new Notice($appId, $secret);
            $templateId = Yii::$app->params['templateId_remit'];
            $bank_id = substr($u->bank_id, -4);
            $url = Url::to(['loan/success'],TRUE);
            $data = array(
                "first"    => "您好，您申请的借款已汇入您尾号为{$bank_id}的银行卡中",
                "keyword1" => "已汇款",
                "keyword2" => "真牛贷",
                "keyword3" => date("Ymd"),
                "remark"   => "请及时查看",
            );
            $messageId = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($l->wechat_id)->send();
        }

        return $this->redirect(['loan/me']);
    }

    public function actionPassword($type=0) // 0.新密码 1.重设密码 3.验证密码 2.第一次借款
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        if (Yii::$app->request->getIsAjax()) {
            $old_pwd = isset($_POST['opwd'])?$_POST['opwd']:NULL;
            $new_pwd = isset($_POST['spwd'])?$_POST['spwd']:NULL;
            $type = $_REQUEST['type'];

            session_start();
            $user = $_SESSION['user'];

            $u = User::findOne($user['openid']);

            if ($type==0 OR $type==2) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $u->auth_code = md5($new_pwd);
                    $u->updateAttributes(['auth_code']);
                    $transaction->commit();
                } catch(\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }

                return json_encode(['type'=>$type, 'stat'=>1]);
            } else if ($type==1&&$u->auth_code!=''&&$u->auth_code==md5($old_pwd)) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $u->auth_code = md5($new_pwd);
                    $u->updateAttributes(['auth_code']);
                    $transaction->commit();
                } catch(\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }

                return json_encode(['type'=>$type, 'stat'=>1]);
            } else if ($type==3&&$u->auth_code!=''&&$u->auth_code==md5($_POST['input_pwd'])) {
                return json_encode(['type'=>$type, 'stat'=>1]);
            } else {
                return json_encode(['type'=>$type, 'stat'=>2]);
            }
        }

        $js = new Js($appId, $secret); 
        return $this->renderPartial('password', ['v'=>Yii::$app->params['assets_version'], 'type'=>$type, 'js'=>$js]);
    }

    public function actionAuth($is_reset=0)
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/auth'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }
    
        $user = $_SESSION['user'];
        $u = User::findOne($user['openid']);

        if ($is_reset) {
            $u->auth_code = '';
            $u->updateAttributes(['auth_code']);
        }

        if (isset($u) and $u->name!='' and $u->id!='') {
            if (Yii::$app->request->getIsAjax()) {
                if ($_POST['name']==$u->name and $_POST['cid']==$u->id) {
                    if ($u->auth_code=='') {
                        return json_encode(['type'=>0, 'stat'=>1]);
                    } else {
                        return json_encode(['type'=>1, 'stat'=>1]);
                    }
                } else {
                    return json_encode(['type'=>0, 'stat'=>2]);
                }
            } else {
                $js = new Js($appId, $secret); 
                return $this->renderPartial('auth', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);
            }
        } else {
            return $this->redirect(['loan/empty']);
        }
    }

    public function actionRepays()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/repays'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }

        $user = $_SESSION['user'];
        $u = User::findOne($user['openid']);

        if (isset($u)) {
            $loans = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status>=0'])->addParams([':wechat_id'=>$user['openid']])->all();
            $range = 10000 - Yii::$app->db->createCommand('SELECT SUM(money) FROM loan WHERE (status=3 OR status=2 OR status=1) AND wechat_id=:wechat_id')->bindValue(':wechat_id', $user['openid'])->queryScalar();
            
            $js = new Js($appId, $secret); 
            return $this->renderPartial('repay_list', ['range'=>$range, 'loans'=>$loans, 'v'=>Yii::$app->params['assets_version'], 'js'=>$js]);            
        } else {
            return $this->redirect(['loan/index']);
        }
    }

    public function actionRepay($loan_id=0)
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        session_start();
        if (empty($_SESSION['user'])) {
            $auth = new Auth($appId, $secret);
            $user = $auth->authorize(Url::to(['loan/repays'], TRUE), 'snsapi_base'); // 返回用户 Bag
            $_SESSION['user'] = $user;
        }
    
        $user = $_SESSION['user'];
        //$l = Loan::find()->where(['and', 'wechat_id=:wechat_id', 'status<4'])->addParams([':wechat_id'=>$user['openid']])->one();
        $l = Loan::findOne($loan_id);
        
        if (isset($l) and $l->status>2) {
            if ($l->status==3) {
                $js = new Js($appId, $secret); 
                $u = User::findOne($user['openid']);
                return $this->renderPartial('repay', ['v'=>Yii::$app->params['assets_version'], 'l'=>$l, 'u'=>$u, 'js'=>$js]);
            } else {
                return $this->redirect(['loan/repayed']);
            }
        }

        return $this->redirect(['loan/index']);
    }

    public function actionRepayed()
    {
        $appId = Yii::$app->params['wechat_appid'];
        $secret = Yii::$app->params['wechat_appsecret'];

        $js = new Js($appId, $secret); 
        return $this->renderPartial('repayed', ['v'=>Yii::$app->params['assets_version'], 'js'=>$js]);
    }

    public function actionBinds()
    {
        $merchantaccount = Yii::$app->params['merchant_account'];
        $merchantPublicKey = Yii::$app->params['merchant_pub'];
        $merchantPrivateKey = Yii::$app->params['merchant_private'];
        $yeepayPublicKey = Yii::$app->params['yeepay_pub'];
        
        $yeepay = new YeepayMPay($merchantaccount, $merchantPublicKey, $merchantPrivateKey, $yeepayPublicKey);
        $identity_id = 'oVnsLt6bzD4hKHqyklrFksy-jRxs';//用户身份标识，是生成绑卡关系的因素之一，在正式环境此值不能固定为一个，要一个用户有唯一对应一个用户标识，以防出现盗刷的风险且一个支付身份标识只能绑定5张银行卡
        $identity_type = 2;     //支付身份标识类型码

        var_dump($yeepay->getBinds($identity_type, $identity_id));
    }
}
