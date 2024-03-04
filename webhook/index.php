<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');
    include_once('../class/Bombeja.php');

    if(isset($_GET['id']) and isset($_GET['codpgto'])){        

        include_once("mercadopago/mercadopago.php");    
        date_default_timezone_set('America/Sao_Paulo');

        $id =  $_GET['id'];  
        $ref = $_GET['codpgto'];

        $mp = new MP($client_id, $client_secret);
        $payment_info = $mp->get_payment_info($id);        

        if($payment_info["response"]["collection"]["status"]=="approved"){
            $status = "Aprovado";
            $detalheStatus = "Pagamento aprovado.";  

            $db->Query("UPDATE `siparisler` SET `sp_durum` = '2', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_code`='".$_GET['codpgto']."'");

            $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_code`='".$ref."'");
            $servico = $db->QueryFetchArray("SELECT * FROM `siparis_islem` WHERE `sp_code`='".$pedido['sp_id']."'");

            if($servico['panel_code']=="0"){
                $idServico = explode("-", $servico['islem_smm'])[1]; 
    
                $api = new Bombeja;
                $resposta = $api->api($tokenApi, "add", $idServico, $servico['islem_item'], $servico['islem_miktar'] );
    
                $db->Query("UPDATE `siparis_islem` SET `panel_code` = '".$resposta['order']."' WHERE `sp_code`='".$pedido['sp_id']."'");
            }
        }

        $fp = fopen("notifica_MP.txt", "a");
        $escreve = fwrite($fp, "ID: ".$_GET['id']." - Ref: ".$payment_info["response"]["collection"]["external_reference"]." - Status: ".$payment_info["response"]["collection"]["status"].PHP_EOL);
        fclose($fp);       

    }    

    
    
?>