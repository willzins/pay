<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');
    include_once("../status/mercadopago/mercadopago.php");

    include_once('../class/Bombeja.php');
    
    date_default_timezone_set('America/Sao_Paulo');             
            
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/search?external_reference='.$_POST['ref'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.$access_token
        ),
    ));
    
    $response = curl_exec($curl);
    $dadosCompra = json_decode($response, true);
    
    curl_close($curl);
    
    $stausTransaction = $dadosCompra["results"][0]["status"];

    if($stausTransaction=="approved"){

        $status = "Aprovado";
        $detalheStatus = "Pagamento aprovado.";       
        
        $db->Query("UPDATE `siparisler` SET `sp_durum` = '2', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_code`='".$_POST['ref']."'");
        
        $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_code`='".$_POST['ref']."'");
        $servico = $db->QueryFetchArray("SELECT * FROM `siparis_islem` WHERE `sp_code`='".$pedido['sp_id']."'");

        if($servico['panel_code']=="0"){
            $idServico = explode("-", $servico['islem_smm'])[1]; 

            $api = new Bombeja;
            $resposta = $api->api($tokenApi, "add", $idServico, $servico['islem_item'], $servico['islem_miktar'] );

            $db->Query("UPDATE `siparis_islem` SET `panel_code` = '".$resposta['order']."' WHERE `sp_code`='".$pedido['sp_id']."'");
         }        
        
        echo $stausTransaction;
    }