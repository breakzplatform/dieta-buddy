<?php

// ALTERAR TODAS AS CHAVES

// TOKENS
$verify_token = "CHAVE_BOT";
$access_token = "CHAVE_PÁGINA";

//API Url
$url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;

// BANCO
$conndb = mysqli_connect('localhost', 'root', '');

// VERIFICAÇAO
$hub_verify_token = null;
if (isset($_REQUEST['hub_challenge'])) {
    $challenge        = $_REQUEST['hub_challenge'];
    $hub_verify_token = $_REQUEST['hub_verify_token'];
}
if ($hub_verify_token === $verify_token) {
    echo $challenge;
    die();
}

// CONVERS
$input  = json_decode(file_get_contents('php://input'), true);
$sender = $input['entry'][0]['messaging'][0]['sender']['id'];

// VERIFICA O TIPO DA MENSAGEM: TEXTO, IMAGEM OU POSTBACK
if (isset($input['entry'][0]['messaging'][0]['message']))
    $message = $input['entry'][0]['messaging'][0]['message']['text'];

if (isset($input['entry'][0]['messaging'][0]['postback']))
    $postback = $input['entry'][0]['messaging'][0]['postback']['payload'];


if (isset($input['entry'][0]['messaging'][0]['message']['quick_reply']))
    $postback = $input['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];

$image = null;
if (isset($input['entry'][0]['messaging'][0]['message']['attachments'][0])){
    if($input['entry'][0]['messaging'][0]['message']['attachments'][0]['type'] == "image") {
        $image = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['url'];
    }
}


// STRINGS USADAS PARA O ENVIO;
$message_to_reply = '';
$jsonData         = '';
$custom_template = false;



if ($image != null) {
        // É UMA IMAGEM
        
        $ch = curl_init();

        // PERGUNTA AO WATSON
        curl_setopt($ch, CURLOPT_URL, "https://gateway-a.watsonplatform.net/visual-recognition/api/v3/classify?api_key=CHAVE_BLUEMIX&url=".urlencode($image)."&version=2016-05-19");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);


        $result = curl_exec($ch);
        curl_close ($ch);

        $resultjson = json_decode($result, true);

        $message_to_reply = "Eu acho que isso é ";
        $message_to_reply .= $resultjson['images'][0]['classifiers'][0]['classes'][0]['class'];
        $message_to_reply .= ", acertei?";

        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message": {
                "text": "' . $message_to_reply . '"
            }
        }';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }
        curl_close ($ch);

        
        // CALORIAS DO USUARIO
        $query    = sprintf("SELECT cal FROM `fb_bot`.`user` WHERE id='" . $sender . "'");
        $resultdb = mysqli_query($conndb, $query);

        $user = mysqli_fetch_assoc($resultdb);
        $cal_user = $user['cal'];

        // QUANTAS CALORIAS O CIDADAO CONSUMIU NO DIA
        $query    = sprintf("SELECT consumido FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        $day_cal = mysqli_fetch_assoc($resultdb);
        $consumido = $day_cal['consumido'];


        // SUBTRAI CALORIAS DO USUARIO PELAS CALORIAS DO DIA. SE DER MAIOR QUE ZERO, SÓ AVISAR. sE DER NEGATIVO, RECLAMAR.
        $val_cal = intval($cal_user) - intval($consumido);


        // REQUEST DE CALORIAS DA PARADA
        $cal_ = file_get_contents('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/cal.php?comida='.urlencode($resultjson['images'][0]['classifiers'][0]['classes'][0]['class']));


        // SALVA NUMA TABELA AUXILIAR CASO O USUARIO VÁ COMER OU NAO
        $query    = sprintf("REPLACE INTO `fb_bot`.`aux` (id, name, cal) VALUES('" . $sender . "', '".$resultjson['images'][0]['classifiers'][0]['classes'][0]['class']."', ".$cal_.")");
        $resultdb = mysqli_query($conndb, $query);
    

        if(intval($cal_) > 100){
            $message_to_reply = "Isso tem em média ".$cal_." calorias. Não exagere, ein! Você ainda pode comer ".$val_cal." calorias.";
        } else if(intval($cal_) <= 100) {
            $message_to_reply = "Isso tem aproximadamente ".$cal_." calorias. Pode comer tranquilo :D. Você ainda tem ".$val_cal." calorias.";
        } else {
            $message_to_reply = "Não encontrei isso no meu banco de dados... Tem certeza que isso é comida?";
        }

        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message": {
                "text": "' . $message_to_reply . '"
            }
        }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
            
        }
        curl_close ($ch);

        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },
            "message":{
                "attachment": {
                    "type":"template",
                    "payload":{
                        "template_type":"button",
                        "text":"Você vai consumir?",
                        "buttons":[
                        {
                            "type":"postback",
                            "title": "Sim, eu vou",
                            "payload": "vou-comer"
                        },
                        {
                            "type":"postback",
                            "title": "Não",
                            "payload": "nao-vou"
                        }
                        ]
                    }
                }
            }
        }';




} else if ($postback != null) {
    // É POSTBACK
    if ($postback == 'init-bot') {

        
        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message":{
                "attachment":{
                "type":"image",
                "payload":{
                    "url":"http://i.imgur.com/P6NeRr0.png"
                }
                }
            }
        }';
        
                
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v2.6/me/messages?access_token=EAAFgbf6983oBAKdJc9GB720PqHxNmh4MJMPgRqCFgfLCBqUAqZCyDAXqGFDgyxtZCSZAMTZByCBnrxLzS6oTXKSWg1RTdey1qJvbOBerBPMH8KtK5GSNVmtH010TR6MN6QIStfMW4qYituv6mbjB3ZCsnr3s9iHau2FNVgh5kHgZDZD");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $headers = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close ($ch);


        // CRIA ELE NO BANCO SE NAO EXISTE
        $message_to_reply = 'Tudo bom? Meu nome é Mica. Seja bem-vindo ao Dieta Buddy. Vamos configurar o seu perfil? É só responder umas perguntas básicas.';
        
        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message": {
                "text": "' . $message_to_reply . '",
            }
        }';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }
        
        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message": {
                "text": "Qual seu gênero?",
                "quick_replies": [{
                    "content_type": "text",
                    "title": "Feminino",
                    "payload": "init-bot-feminino"
                }, {
                    "content_type": "text",
                    "title": "Masculino",
                    "payload": "init-bot-masculino"
                }]
            }
        }';
        
    } else if ($postback == 'init-bot-masculino' || $postback == 'init-bot-feminino') {
        
        if ($postback == 'init-bot-feminino') {
            $query    = sprintf("REPLACE INTO `fb_bot`.`user` (`id`, `sexo`, `idade`, `cal`) VALUES ('" . $sender . "', 'Feminino', '0', '0')");
            $resultdb = mysqli_query($conndb, $query);

        } else {
            $query    = sprintf("REPLACE INTO `fb_bot`.`user` (`id`, `sexo`, `idade`, `cal`) VALUES ('" . $sender . "', 'Masculino', 0, 0)");
            $resultdb = mysqli_query($conndb, $query);
        }
        
        
        $query    = sprintf("DELETE FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);


        $jsonData = '{
            "recipient": {
                "id": "' . $sender . '"
            },

            "message": {
                "text": "Qual sua idade?",
                "quick_replies": [{
                    "content_type": "text",
                    "title": "14 a 19 anos",
                    "payload": "init-bot-idade-14"
                }, {
                    "content_type": "text",
                    "title": "20 a 24 anos",
                    "payload": "init-bot-idade-21"
                }, {
                    "content_type": "text",
                    "title": "25 a 65 anos",
                    "payload": "init-bot-idade-25"
                }, {
                    "content_type": "text",
                    "title": "Mais de 65",
                    "payload": "init-bot-idade-65"
                }]
            }
        }';
        
    } else if (0 === strpos($postback, 'init-bot-idade-')) {
        
        $cal_ = '';
        
        switch (substr($postback, -2)) {
            case 14:
                
                $cal_ = "3200";
                
                $query    = sprintf("UPDATE `fb_bot`.`user` SET cal=3200 WHERE id='" . $sender . "'");
                $resultdb = mysqli_query($conndb, $query);
                
                break;
            case 21:
                
                $cal_ = "3500";
                
                $query    = sprintf("UPDATE `fb_bot`.`user` SET cal=3500 WHERE id='" . $sender . "'");
                $resultdb = mysqli_query($conndb, $query);
                
                break;
            case 25:
                
                $cal_ = "3000";
                
                // SE É HOMEM
                $query    = sprintf("UPDATE `fb_bot`.`user` SET cal=3000 WHERE id='" . $sender . "'");
                $resultdb = mysqli_query($conndb, $query);
                
                break;
            case 65:
                
                $cal_ = "1800";
                
                $query    = sprintf("UPDATE `fb_bot`.`user` SET cal=1800 WHERE id='" . $sender . "'");
                $resultdb = mysqli_query($conndb, $query);
                
                break;
        }
        
        $message_to_reply = 'Pronto! Fiz as contas aqui... baseado na sua idade e gênero, você precisa comer ' . $cal_ . ' calorias por dia!';
        
        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }
        
        // TUTORIAL MENSAGEM 1
        $message_to_reply = 'Quando você comer alguma coisa, me avisa dizendo que \"eu comi ...\" seguido dos alimentos que você consumiu.';

        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }
        

        // Tutorial mensagem 2
        $message_to_reply = 'Eu vou anotar tudo no meu caderninho e te dizer o quanto você pode consumir de calorias até o fim do dia.';
        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }

        // TUTORIAL MENSAGEM 3
        $message_to_reply = 'Você também pode me mandar uma foto de uma coisa que você pretende comer, que eu vou tentar descobrir quantas calorias ela tem. Aí você decide se come ou não!';
        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }

        // TUTORIAL MENSAGEM FINAL
        $message_to_reply = 'Estou de olho em você! Até mais (:';
        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
    
    } else if ($postback == 'vou-comer') {
        $query    = sprintf("SELECT * FROM `fb_bot`.`aux` WHERE id='" . $sender . "'");
        $resultdb = mysqli_query($conndb, $query);

        $aux = mysqli_fetch_assoc($resultdb);
        $cal_aux = $aux['cal'];
        $name_aux = $aux['name'];

        $query    = sprintf("SELECT consumido FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        $day_cal = mysqli_fetch_assoc($resultdb);
        $consumido = $day_cal['consumido'];

        $consumido_final = 0;
        $consumido_final = intval($consumido) + intval($aux['cal']);

        $query    = sprintf("UPDATE `fb_bot`.`day_cal` SET consumido='".$consumido_final."' WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        
        $message_to_reply_0 = 'Anotei que você comeu '.$name_aux;
        $message_to_reply_1 = 'Você já consumiu '.$consumido_final.' calorias hoje.';

        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message":{
                    "attachment":{
                    "type":"template",
                    "payload":{
                        "template_type":"generic",
                        "elements":[
                            {
                                "title":"' . $message_to_reply_0 . '!",
                                "image_url":"https://placeholdit.imgix.net/~text?txtsize=100&txt=+ '.urlencode($aux['cal']).' calorias&w=700&h=400",
                                "subtitle":"'.$message_to_reply_1.'"      
                            }
                        ]
                    }
                    }
                }
        }';

    } else if ($postback == 'nao-vou') {

        $message_to_reply = 'Certo! Lembre-se que é saudável se alimentar de duas em duas horas.';
        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';

    }
} else {
    // É CONVERSAÇÃO
    $message = str_replace('... ', ',', $message);
    $message = strtolower ($message);

    if (0 === strpos($message, 'eu comi') || 0 === strpos($message, 'eu bebi') || 0 === strpos($message, 'eu tomei') || 0 === strpos($message, 'bebi') || 0 === strpos($message, 'comi') || 0 === strpos($message, 'tomei')) {

        // VERIFICA SE HOJE EXISTE COM O ID DO CARA
        $query    = sprintf("SELECT COUNT(*) as total FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        $count = mysqli_fetch_assoc($resultdb);
        $num_rows = $count['total'];

        if($num_rows == 0){
            // SE NAO EXISTE, CRIA
            $query    = sprintf("REPLACE INTO `fb_bot`.`day_cal` (`id`, `day`, `consumido`) VALUES ('" . $sender . "', CURRENT_DATE, 0)");
            $resultdb = mysqli_query($conndb, $query);

            $message_to_reply = 'Primeira refeição do dia! Espero que seja algo saudável, ein! O café da manhã é a refeição mais importante do dia.';

            $jsonData = '{
            "recipient": {
                    "id": "' . $sender . '"
                },
                "message": {
                    "text": "' . $message_to_reply . '"
                }
                }';

                            
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $headers   = array();
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
                $result = curl_exec($ch);
            }
        }
        
        // VERIFICA A QUANTIDADE DE CALORIAS DA PARADA

        
        // REQUEST DE CALORIAS
        $message = str_replace('eu ', '', $message);
        $message = str_replace('tomei', 'tome', $message);
        
        $comida = substr($message, 4);

        $comida_exp = explode(',', $text = str_replace(' e ', ',', $comida));

        $cal_ = 0;
        if(sizeof($comida_exp) > 1){
            
            foreach ($comida_exp as $comida_){
                $cal_aux = file_get_contents('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/cal.php?comida='.urlencode(trim($comida_)));
                $cal_ = $cal_ + intval($cal_aux);
            }

        } else {
            $cal_ = file_get_contents('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/cal.php?comida='.urlencode(trim($comida_exp[0])));
        }

        
        //VE QUANTAS CALORIAS AINDA ESTÁ DISPONIVEL

        // CALORIAS DO USUARIO
        $query    = sprintf("SELECT cal FROM `fb_bot`.`user` WHERE id='" . $sender . "'");
        $resultdb = mysqli_query($conndb, $query);

        $user = mysqli_fetch_assoc($resultdb);
        $cal_user = $user['cal'];

        // QUANTAS CALORIAS O CIDADAO CONSUMIU NO DIA
        $query    = sprintf("SELECT consumido FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        $day_cal = mysqli_fetch_assoc($resultdb);
        $consumido = $day_cal['consumido'];


        // SUBTRAI CALORIAS DO USUARIO PELAS CALORIAS DO DIA. SE DER MAIOR QUE ZERO, SÓ AVISAR. sE DER NEGATIVO, RECLAMAR.
        $val_cal = intval($cal_user) - intval($consumido);

        $final_cal = $val_cal - intval($cal_);

        if($final_cal > 0) {
            if(intval($cal_) > 300){
                $message_to_reply = $comida." tem aproximadamente ".$cal_." calorias. Que tal comer algo mais saudável na próxima vez? Você ainda tem ".$final_cal." pra consumir hoje, tenho tudo anotado aqui.";
            } else if(intval($cal_) <= 300) {
                $message_to_reply = $comida." tem aproximadamente ".$cal_." calorias, anotei aqui no meu caderinho.";
                $message2 = "Você ainda tem ".$final_cal." pra consumir hoje.";
                $custom_template = true;
            } else {
                $message_to_reply = "Não encontrei isso no meu banco de dados... Tem certeza que isso é comida?";
            }
        } else {
            if(intval($cal_) > 0){
                $message_to_reply = $comida." tem normalmente ".$cal_." calorias.";
                $message2 = "Você já ultrapassou o seu limite diário de calorias! Fecha essa boca!";
                $custom_template = true;
            } else {
                $message_to_reply = "Não encontrei isso no meu banco de dados... Tem certeza que isso é comida?";
            }
        }

        $consumido_final = 0;
        $consumido_final = intval($consumido) + intval($cal_);

        $query    = sprintf("UPDATE `fb_bot`.`day_cal` SET consumido='".$consumido_final."' WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        /*
        */


    } else if(strpos($message, 'caloria') !== false) {
        
        // CALORIAS DO USUARIO
        $query    = sprintf("SELECT cal FROM `fb_bot`.`user` WHERE id='" . $sender . "'");
        $resultdb = mysqli_query($conndb, $query);

        $user = mysqli_fetch_assoc($resultdb);
        $cal_user = $user['cal'];

        // QUANTAS CALORIAS O CIDADAO CONSUMIU NO DIA
        $query    = sprintf("SELECT consumido FROM `fb_bot`.`day_cal` WHERE id='" . $sender . "' AND day = CURDATE()");
        $resultdb = mysqli_query($conndb, $query);

        $day_cal = mysqli_fetch_assoc($resultdb);
        $consumido = $day_cal['consumido'];


        // SUBTRAI CALORIAS DO USUARIO PELAS CALORIAS DO DIA. SE DER MAIOR QUE ZERO, SÓ AVISAR. sE DER NEGATIVO, RECLAMAR.
        $val_cal = intval($cal_user) - intval($consumido);

        $message_to_reply = 'Você ainda tem '.$val_cal.' para consumir hoje. Use com sabedoria ;)';
    } else {

        $message_to_reply = 'Não entendi o que você quis dizer :(';

        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }

        
        $message_to_reply = 'Pra me avisar o que você comeu, digita \"eu comi ...\" seguido do que você se alimentou.';
        

        $jsonData = '{
                "recipient": {
                    "id": "' . $sender . '"
                },

                "message": {
                    "text": "' . $message_to_reply . '"
                }
            }';
        
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $headers   = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
            $result = curl_exec($ch);
        }

        
        $message_to_reply = 'Por exemplo, se você comeu pão, queijo e mamão, você escreve \"eu comi pão, queijo e mamão\" que eu vou anotar quantas calorias você consumiu.';

    }
    
    $message_to_reply = ucfirst(trim($message_to_reply));

if($custom_template){

    $jsonData = '{
    "recipient": {
        "id": "' . $sender . '"
    },
    "message":{
                "attachment": {
                    "type":"template",
                    "payload":{
                        "template_type":"generic",
                        "elements":[
                            {
                                "title":"'.$message_to_reply.'",
                                "subtitle":"'.$message2.'",
                                 
          }
        ]
                    }
                }
            }
    }';


} else {
    $jsonData = '{
    "recipient": {
        "id": "' . $sender . '"
    },
    "message": {
        "text": "' . $message_to_reply . '"
    }
    }';
}
    
}
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$headers   = array();
$headers[] = "Content-Type: application/json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

if (isset($input['entry'][0]['messaging'][0]['message']) || isset($input['entry'][0]['messaging'][0]['postback'])) {
    $result = curl_exec($ch);
}