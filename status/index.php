<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');
    include_once("mercadopago/mercadopago.php");
    include_once('../class/Bombeja.php');
    
    date_default_timezone_set('America/Sao_Paulo');

    $url_site = "https://osegredodafama.com.br";
    
    $statusPgto     = 'Aguardando pagamento...';
    $description    = 'Seu pagamento ainda não foi reconhecido. Faça o pagamento para receber o produto.';
    $subdescription = 'Clique no botão abaixo e volte para o site';
    $imgStatus      = '<iframe src="https://giphy.com/embed/3JVwqeD0733JGNb8EB" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
    $urlSite        = $url_site;

    // Iniciar busca site_nome_recibos      
    if($db->QueryGetNumRows("SELECT * FROM `siparisler` WHERE `sp_code`='".$_GET['codpgto']."'") == 0){
        $statusPgto = 'Número de pedido não encontrado';
        $description  = 'Não encontramos a sua compra. Verfifique o número e tente novamente.';
        $imgStatus  = '<iframe src="https://giphy.com/embed/wx9SNpvMP4V6Fv13bF" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
        $urlSite    = $url_site;
    }else{   
        $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_code`='".$_GET['codpgto']."'");         
        
        $transaction['status']="PAGAMENTO APROVADO";
        
        if($pedido['status']!="Aprovado"){            
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/search?external_reference='.$_GET['codpgto'],
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

            if($stausTransaction=="pending"){
                $transaction['status'] = "AGUARDANDO PAGAMENTO";               
                

            }elseif(($stausTransaction=="rejected") or ($stausTransaction=="Canceled")){
                $transaction['status'] = "PAGAMENTO RECUSADO";

                $status = "Recusado";
                $detalheStatus = "O seu pagamento foi recusado, selecione outra forma de pagamento e tente novamente.";
                       
                $db->Query("UPDATE `siparisler` SET `sp_durum` = '5', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_code`='".$_GET['codpgto']."'");
                
                
            }elseif($stausTransaction=="approved"){
                $transaction['status'] = "PAGAMENTO APROVADO";

                $status = "Aprovado";
                $detalheStatus = "O seu pagamento foi aprovado..";       
                
                $db->Query("UPDATE `siparisler` SET `sp_durum` = '2', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_code`='".$_GET['codpgto']."'");
                
                $servico = $db->QueryFetchArray("SELECT * FROM `siparis_islem` WHERE `sp_code`='".$pedido['sp_id']."'");

                if($servico['panel_code']=="0"){
                    $idServico = explode("-", $servico['islem_smm'])[1]; 
        
                    $api = new Bombeja;
                    $resposta = $api->api($tokenApi, "add", $idServico, $servico['islem_item'], $servico['islem_miktar'] );
        
                    $db->Query("UPDATE `siparis_islem` SET `panel_code` = '".$resposta['order']."' WHERE `sp_code`='".$pedido['sp_id']."'");
                 }   

            }elseif($stausTransaction=="in_process"){
                $transaction['status'] = "PAGAMENTO EM ANÁLISE";                
                
                $status = "Análise";
                $detalheStatus = "Pagamento em análise. Pode levar até 2 dias úteis para aprovação.";
                       
                $db->Query("UPDATE `siparisler` SET `sp_durum` = '6', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_code`='".$_GET['codpgto']."'");
                


            }
        }       
        
        if($transaction['status']=="INICIADO"){
            $statusPgto = 'Pagamento Iniciado...';
            $description  = 'Selecione uma forma de pagamento para para finalizar a compra.';
            $subdescription = 'Clique no botão abaixo para voltar ao site e selecionar uma forma de pagamento.';
            $imgStatus  = '<iframe src="https://giphy.com/embed/3FoezIxMT0ZT8XCwSE" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
            $urlSite    = $url_site;
        }elseif($transaction['status']=="AGUARDANDO PAGAMENTO"){
            $statusPgto = 'Aguardando pagamento...';
            $description  = 'Seu pagamento ainda não foi reconhecido. Faça o pagamento para receber o produto.';
            $subdescription = 'Clique no botão abaixo e volte para o site';
            $imgStatus  = '<iframe src="https://giphy.com/embed/3JVwqeD0733JGNb8EB" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
            $urlSite    = $url_site;
        }elseif($transaction['status']=="PAGAMENTO APROVADO"){
            $statusPgto = 'Pagamento aprovado!';
            $description  = 'Seu pagamento foi aprovado e o produto comprado será entregue.';
            $subdescription = 'Clique no botão abaixo e volte para o site';
            $imgStatus  = '<iframe src="https://giphy.com/embed/eHcyHQdfPMrDJ6fCXs" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
            $urlSite    = $url_site;
        }elseif($transaction['status']=="PAGAMENTO REPROVADO"){
            $statusPgto = 'Pagamento reprovado!';
            $description  = 'Seu pagamento foi reprovado '.$transaction['status_detalhe'];
            $subdescription = 'Clique no botão abaixo, volte para o site e escolha outra forma de pagamento.';
            $imgStatus  = '<iframe src="https://giphy.com/embed/3oz8xPm88glcaY173O" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
            $urlSite    = $url_site;
        }elseif($transaction['status']=="PAGAMENTO EM ANÁLISE"){
            $statusPgto = 'Pagamento em análise!';
            $description  = 'Seu pagamento está em análise '.$transaction['status_detalhe'];
            $subdescription = 'Clique no botão abaixo, volte para o site e escolha outra forma de pagamento.';
            $imgStatus  = '<iframe src="https://giphy.com/embed/fiq0fjUoYaLBKd3R7k" frameBorder="0" class="giphy-embed W-300" allowFullScreen></iframe>';
            $urlSite    = $url_site;
        }
    }    
    
    
    
?>
<!DOCTYPE html> 
<html lang="pt-br">
    <head><meta charset="utf-8">
        
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?=$description?> - O Segredo da Fama</title>
        <meta content="<?=$description?> - O Segredo da Fama" name="title">
        <meta property="og:title" content="<?=$description?> - O Segredo da Fama">
        <meta property="og:description" content="Para processar seu pedido, por favor efetue o pagamento..">
        <meta content="Para processar seu pedido, por favor efetue o pagamento.." name="description">
        <link href="https://osegredodafama.com.br/upload/icon-o-segredo-367440.png" rel="icon">
        <link href="https://osegredodafama.com.br/upload/icon-o-segredo-367440.png" rel="apple-touch-icon">
        <meta property="og:url" content="https://osegredodafama.com.br/fazer-pedido/cb9ef828519ffb4296bee5a1e3c83c3e/" />
        <link rel="canonical" href="https://osegredodafama.com.br/fazer-pedido/cb9ef828519ffb4296bee5a1e3c83c3e/" />
        <meta name="robots" content="noindex" />
        <meta property="og:locale" content="pt_BR" />
        <meta property="og:type" content="website" />
        <link href="https://osegredodafama.com.br/themes/default/assets/bootstrap/css/bootstrap.min.css?version=3.1" rel="stylesheet">
        <link href="https://osegredodafama.com.br/themes/default/css/style.min.css?version=1.0" rel="stylesheet" asyc>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> 
        <style type="text/css"> .xrb, .mobile-nav, #progressbar li.active:before, #progressbar li.active:after, #msform .action-button, .btn-order.bo-ileri {background-color: #a8061a !important;} .xrc, .mobile-nav.d-lg-none .drop-down ul li a {color: #151414 !important;} .mobile-nav-toggle i{color: #151414} .xrbs {background-color: #a8061a !important;} #header {background: #17141400;} .main-nav a,.mobile-nav-toggle i {color: #ffffff; } .main-nav .drop-down > a:after { border-color: transparent #ffffff #ffffff transparent;} .menubut {background: #ffffff} </style>
        
        <script src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script> 

    </head>
    <body>
        <header id="header" class="fixed-top">
            <div class="container">
                <div class="logo float-left"> <a href="https://osegredodafama.com.br/"><img src="https://osegredodafama.com.br/upload/logo-o-segredo-teste1-666620.png" alt="" class="img-fluid"></a> </div>
                <nav class="main-nav float-right d-none d-lg-block">
                    <ul>
                        <li><a href="https://osegredodafama.com.br/">Ínicio</a></li>
                        <li class="drop-down">
                            <a href="javascript:void(0)">Serviços</a>
                            <ul class="keskin">
                                <li><a href="https://osegredodafama.com.br/instagram/">Instagram</a></li>
                                <li><a href="https://osegredodafama.com.br/tiktok/">TikTok</a></li>
                                <li><a href="https://osegredodafama.com.br/youtube/">Youtube</a></li>
                                <li><a href="https://osegredodafama.com.br/twitter/">Twitter</a></li>
                            </ul>
                        </li>
                        <li class="menubut1 keskin xrb"><a href="https://osegredodafama.com.br/contato/"><i class="fas fa-envelope"></i> Entre em contato</a></li>

                        <li class="menubut2 keskin xrbs"><a href="https://osegredodafama.com.br/fazer-pedido/<?=$pedido['sp_musteri_link']?>"><i class="fas fa-search"></i> Acompanhar pedido</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <div id="content_ajax">
            <section id="introx" class="clearfix xrb des3 ns-ortala">
                <div class="container alans1 fadeInUp">
                    <div class="text-center text-white">
                        <div class="sipbaslik">
                            <i class="fas fa-file-invoice"></i> 
                            <h1 class="font-weight-bold">Status do pagamento</h1>
                            <p>Veja abaixo o status do seu pagamento</p>
                        </div>
                    </div>
                </div>
                <div class="dalga-head"></div>
            </section>
            <main id="main" style="padding: 50px 0px">
                <div class="col-md-8 offset-md-2" id="order_section_list">
                    <div id="msform">
                        
                        <fieldset class="keskin shadow-none p-0 mb-5 bank-kart" data-section="3">
                            
                            <div class="row">                                
                                <div class="col-md-12 text-center">
                                <!-- App Capsule -->
                                    <div id="appCapsule">

                                        <div class="section">
                                            <div class="splash-page mt-5 mb-5">
                                                <div class="mb-3"><?=$imgStatus?></div>
                                                <h2 class="mb-2"><?=$statusPgto?></h2>
                                                <p>NÚMERO DO SEU PEDIDO:</p>
                                                <p><strong><?=$_GET['codpgto'];?></strong></p>
                                                <h6 class="mb-2"><?=$subdescription?></h6>
                                                
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mx-auto col-md-offset-2">
                                                    <a href="<?=$urlSite?>" class="btn btn-lg btn-danger btn-block">Voltar para o site</a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>    
                                <!-- * App Capsule -->

                            </div>
                        </fieldset>
                    </div>
                </div>
            </main>
            <style type="text/css"> .payment-none { text-decoration: line-through; } </style>
        </div>
        <footer id="footer" class="xrb des3">
            <div class="dalga-ust"></div>
            <div class="footer-top">
                <div class="container">
                    <div class="row">
                        <div class="col-md-8 offset-md-2 text-center">
                            <h3 class="font-weight-bold mb-1">Ainda tem dúvidas?</h3>
                            <p>Não perca tempo, temos uma equipe especializada para lhe atender. </p>
                            <a href="https://wa.link/4le64t" class="btn footerilet xrc keskin"><i class="fa-brands fa-whatsapp fa-xl"></i> Whatsapp</a> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6 fsoya"> O Segredo da Fama © 2022 Todos os direitos reservados. </div>
                        <div class="col-md-6 fsaya"> <a href="https://osegredodafama.com.br/">Ínicio</a><a href="https://osegredodafama.com.br/termos/">Termos </a><a href="https://osegredodafama.com.br/contato/">Contato</a> </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="sorgu" style="background: rgba(0, 0, 0, 0.80);max-width:100%" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" style="max-width:100%" role="document">
                    <div class="container">
                        <div class="modal-content" id="_orderstatu" style="background: none">
                            <div class="modal-body">
                                <div class="container">
                                    <div class="sorgusss row">
                                        <div class="col-md-8 offset-md-2 pl-0 pr-0">
                                            <div class="kapabut text-center"> <i class="fas fa-times-circle" id="_statuclose" data-dismiss="modal" style="border: none;cursor: pointer;font-size: 25px;"></i> </div>
                                            <div class="sorgualan text-center">
                                                <form id="order_search" method="POST" onsubmit="orderstatu(this); return false;">
                                                    <input type="hidden" name="action" value="response"> <input type="hidden" name="include" value="order_search"> 
                                                    <h2 class="font-weight-bold">Acompanhar pedido</h2>
                                                    <p class="mb-3">Por favor insira o seu número do pedido abaixo para mais informações sobre seu pedido.</p>
                                                    <input type="text" required="" class="keskin" name="sp_code" value="3945760099" placeholder="Digite o número do pedido"> <button type="submit" id="order_search_btn" class="btn sorgubut xrb keskin"><i class="fas fa-search"></i></button> 
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div> 
            <div class="sol-bars">
                <div class="whatsappico keskin"><a title="Contate-nos no Whatsapp" class="wpbut" href="https://wa.me/51995132311?text=Olá, tenho uma dúvida." target="_blank">Whatsapp <i class="fab fa-whatsapp"></i></a></div>
            </div>
        </footer>
        <link href="https://osegredodafama.com.br/themes/default/assets/owlcarousel/assets/owl.carousel.min.css?version=3.0" rel="stylesheet" async>
        <link href="https://osegredodafama.com.br/themes/default/assets/animate/animate.min.css?version=3.0" rel="stylesheet" async>
        <script src="https://osegredodafama.com.br/themes/default/assets/jquery/jquery-migrate.min.js" async></script> <script src="https://osegredodafama.com.br/themes/default/assets/bootstrap/js/bootstrap.bundle.min.js" async></script> <script src="https://osegredodafama.com.br/themes/default/assets/mobile-nav/mobile-nav.js" async></script> <script src="https://osegredodafama.com.br/themes/default/assets/wow/wow.min.js" async></script> <script src="https://osegredodafama.com.br/themes/default/assets/owlcarousel/owl.carousel.min.js"></script> <script src="https://kit.fontawesome.com/d3897fd5a7.js" crossorigin="anonymous"></script> <script src="https://osegredodafama.com.br/themes/default/assets/main.js?version=3.1"></script> 
        
     <!-- Event snippet for O Segredo da Fama- Conversão conversion page -->
<script>
  gtag('event', 'conversion', {
      'send_to': 'AW-10859770455/LtZRCJKh5eIDENfkq7oo',
      'value': 0.0,
      'currency': 'BRL',
      'transaction_id': ''
  });
</script>

<!-- Meta Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '1344452819464704');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=1344452819464704&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->

    </body>
</html>