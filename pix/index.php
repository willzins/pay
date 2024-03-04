<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');

    //Inclui arquivo de classe 
    include_once('../system/mercadopago/pix/vendor/autoload.php');
    
    $descricao_compra  = "";
    $valor_compra      = "";
    $email_comprador   = "";

    $nome_produto      = "";
    $valor_produto     = "";
    $descricao_produto = "";

    $fullname          = ""; 
    $email             = "";  
    $cpf               = "";
    $ddd               = "";
    $phone             = "";
    $cep               = "";
    $endereco          = "";
    $numero_endereco   = "";
    $complemento       = "";
    $bairro            = "";
    $cidade            = "";
    $uf                = "";

    $codigo     = $_GET['codpgto'];

    $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_musteri_link`='$codigo'");

    $ref = $pedido['sp_code'];

    $url_status      = "https://".$_SERVER['HTTP_HOST']."/status/index.php?codpgto=".$ref;
    $url_notificacao = "https://".$_SERVER['HTTP_HOST']."/webhook/index.php?codpgto=".$ref;

    $transacao_descricao   = $pedido['sp_paket_adi'];

    $valor_final_formatado = number_format($pedido['sp_musteri_tutar'],2,",",".");
    $valor_final = number_format($pedido['sp_musteri_tutar'], 2, '.', '');

    $description                   = $transacao_descricao;                         
    $transaction_amount            = $valor_final;        
    $docType                       = "CPF";
    $fullname                      = $pedido['sp_musteri_adi'];       
    $email                         = $pedido['sp_musteri_mail']; 
    $phone                         = $pedido['sp_musteri_telefon'];   
    $docNumber                     = "";       

    $expFullName                   = explode(" ", $fullname);
    $comprador_primeiro_nome       = $expFullName[0]; 
    $comprador_sobrenome           = $expFullName[1]; 
    
    $success = 1;


    if(($pedido['payload']=="") or is_null($pedido['payload'])){    

        //////////////////////////////////////////            
        
        MercadoPago\SDK::setAccessToken($access_token);

        $payment = new MercadoPago\Payment();

        
        $payment->notification_url = $url_notificacao;
        $payment->transaction_amount = $transaction_amount;
        $payment->description = $transacao_descricao;
        $payment->payment_method_id = "pix";
        $payment->payer = array(
            "email" => $email,
            "first_name" => $comprador_primeiro_nome,
            "last_name" => $comprador_sobrenome,
            "identification" => array(
                "type" => "CPF",
                "number" => $docNumber
                )
            );
            
        $payment->external_reference = $ref;  
        
        $payment->save();
            
        $pix  = json_decode(json_encode($payment), true);
            
        $code      = $pix['point_of_interaction']['transaction_data']['qr_code'];  
        $code64    = $pix['point_of_interaction']['transaction_data']['qr_code_base64'];
        
        //$txID      = $pix['id'];
                    
        $QRCode = '<img src="data:image/png;base64,' . $code64 . '" width="300px" height="300px">';
        
        $codeGerado = $code;

        $status = "Aguardando pagamento";
        $detalheStatus = "Aguardando pagamento";

        $db->Query("UPDATE `siparisler` SET `sp_durum` = '6', `payload` = '".$code."', `qrcode` = '".$code64."', `status`='$status', `detalhe_status`='$detalheStatus'  WHERE `sp_musteri_link`='$codigo'");
            
    
    }else{
        
        $total       = $valor_final;                    
        $valorCobrado = number_format($total, 2, '.', '');        
        $codeGerado = $pedido['payload'];
            
        //QR CODE
        $image = $pedido['qrcode'];        
        $QRCode = '<img src="data:image/png;base64,' . $image . '" width="300px" height="300px">';
    }

    $pedido = $db->QueryFetchArray("SELECT * FROM `siparisler` WHERE `sp_musteri_link`='$codigo'");

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
<!-- Global site tag (gtag.js) - Google Ads: 10859770455 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-10859770455"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-10859770455');
</script>

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
                            <h1 class="font-weight-bold">Finalização de pagamento</h1>
                            <p>Para processar seu pedido, por favor efetue o pagamento</p>
                        </div>
                        
                    </div>
                </div>
                <div class="dalga-head"></div>
            </section>
            <main id="main" style="padding: 50px 0px">
                <div class="col-md-8 offset-md-2" id="order_section_list">
                    <div id="msform">
                        <ul id="progressbar">
                            <li class="active" data-wizart="1">Link</li>
                            <li data-wizart="2" class="active">Informações pessoais</li>
                            <li data-wizart="3" class="active">Metódos de pagamento</li>
                            <li data-wizart="4" class="active">Finalização de pagamento</li>
                        </ul>
                        <fieldset class="keskin shadow-none p-0 mb-5 bank-kart" data-section="3">
                            
                            <div class="row">

                                <div class="box-2 col-md-6">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <p class="fw-bold h4">Dados para pagamento</p>                                
                                            <span class="sub-title">
                                                (não feche esta página)
                                            </span> 
                                         
                                        </div>
                                                                    
                                        <div class="col-md-12">
                                            <div class="d-flex text-muted mb-0">
                                                <div class="card mt-2 col-12 text-center">  
                                                    <div class="card-body">
                                                        <span><b>Chave pix aleatória</b></span>
                                                        <div class="col-12" style="margin-top: 9px;">
                                                            <input type="text" id="select4" class="form-control" value="<?=$codeGerado;?>" readonly>
                                                            <button style="margin-top: 3px;" class="btn btn-danger form-group control-copydiv" onClick="return fieldtoclipboard.copyfield(event, 'select4')">COPIAR CÓDIGO</button>
                                                        </div>

                                                        <div class="col-12" id="verifcandopagamento" style="display: none;"><br>
                                                            <div class="spinner-border text-danger" role="status">
                                                                <span class="sr-only">Verificando pagamento...</span>
                                                            </div>
                                                            <p>
                                                            <span>Verificando pagamento...</span>
                                                            </p>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                                                            
                                    </div>


                                </div>
                                <style type="text/css">
                                    .sub-title{color: grey; font-size: 18px;}

                                </style>

                                <div class="box-2 col-md-6">
                                    <div class="row">
                                                                                                            
                                    <div class="col-md-12">
                                            <h4 class=" pb-2 mb-0 mt-3"><i class="fa-solid fa-qrcode"></i> Aponte sua câmera</h4>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="d-flex text-muted mb-0">
                                                <div class="card mt-2 col-12 text-center">
                                                    <div class="card-body">
                                                        <div class="col-12">
                                                            <?=$QRCode?>  
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                          
                                            
                                    </div>
                                </div>
                                        <div class="col-md-12">
                                            <div class="d-flex text-muted mb-0">
                                                <div class="card mt-2 col-12 text-center" style="background-color: #dc3545; border-radius: 10px;">  
                                                    <div class="card-body" style="color:white;text-align:left;">
                                                        <span><b>Você está comprando:</b></span>
                                                         <span>
                                            <?=$transacao_descricao?> 
                                            <p><b>Valor total:</b> R$ <?=$valor_final_formatado?>
                                                         <p>Após o pagamento, retorne a esta página para visualizar o seu número do pedido.</p>

                                                       </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                                                            
                                    </div>


                                </div>
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
        
        <script src="https://sdk.mercadopago.com/js/v2"></script>

        <script type="text/javascript">var public_key = "<?=$public_key?>";</script>
        
        <script src="../system/mercadopago/js/mercadopagocartao.js"></script>

        <script src="../system/js/clipboard/fieldtoclipboard.js" type="text/javascript"></script>

        <script type="text/javascript">

            // Função responsável por fazer a requisição para os arquivos
            // php responsáveis por verificar o status dos pedidos 
            function atualizar(){                

                // requisição via post/ajax 
                $.post('verifica_pagamento.php', {ref:'<?=$ref;?>'}, function(retorna){
                    //Pega o retorno da requisição e coloca na div 
                    var status = JSON.stringify(retorna);
                                        
                    if(status.substr(-2) == 'd"'){
                        window.location.href = "<?=$url_status;?>";
                    }
                });
                
            }

            function loading(){
                document.getElementById("verifcandopagamento").style.display = "block";
            }
            
            // Definindo intervalo que a função será chamada
            setInterval("atualizar()", 2000);
            setInterval("loading()", 10000);           
            
        </script>

    </body>
</html>