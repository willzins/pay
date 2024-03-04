<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');
    include_once('../class/Bombeja.php');

    $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_code`='6245199207'");
    $servico = $db->QueryFetchArray("SELECT * FROM `siparis_islem` WHERE `sp_code`='".$pedido['sp_id']."'");

    if($servico['panel_code']=="0"){

        
        $idServico = explode("-", $servico['islem_smm'])[1]; 



        //exit($tokenApi.", ". "add, ". $idServico.", ". $servico['islem_item'].",". $servico['islem_miktar']);

        
        $api = new Bombeja;
        $resposta = $api->api($tokenApi, "add", $idServico, $servico['islem_item'], $servico['islem_miktar'] );

        print_r($resposta);


        //$db->Query("UPDATE `siparis_islem` SET `panel_code` = '".$resposta['order']."' WHERE `sp_code`='".$pedido['sp_id']."'");
  
        
    } 