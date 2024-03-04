<?php
    /// Valida o arquivo
    $starttime = microtime(true);
    define('BASEPATH', true);
    include_once('../system/config/config.php');

    //Inclui arquivo de classe
    include_once('../system/mercadopago/cartao/vendor/autoload.php');  
    
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

    $valorCobrado = $valor_final;

    $status = "Aguardando pagamento";
    $detalheStatus = "Aguardando pagamento";

    $db->Query("UPDATE `siparisler` SET `sp_durum` = '6', `status`='$status', `detalhe_status`='$detalheStatus'  WHERE `sp_musteri_link`='$codigo'");
    
    
    $valorCobrado = number_format($valorCobrado, 2, '.', ''); 

    MercadoPago\SDK::setAccessToken($access_token); 
    
    $preference = new MercadoPago\Preference();
    
    $item = new MercadoPago\Item();
    
    $item->title = $transacao_descricao;
    $item->quantity = 1;
    $item->unit_price = $transaction_amount;
    
    $preference->items = array($item);
    
    $preference->notification_url = $url_notificacao;
    
    $preference->back_urls = array(
        "success" => $url_status,
        "failure" => $url_status,
        "pending" => $url_status
    );
    
    $preference->external_reference = $ref;
    
    $preference->payment_methods = array(
        "excluded_payment_types" => array(
            array("id" => "ticket"),
            array("id" => "atm"),
            array("id" => "digital_wallet"),
            array("id" => "bank_transfer")
        ),
        "installments" => 12
    );
    
    
    $preference->auto_return = "approved";
    
    $preference->save();

    redirect($preference->init_point);  


    /*
    if($_POST){
        
        MercadoPago\SDK::setAccessToken($access_token); 

        foreach($_POST as $nome_campo => $valor){
           $comando = "\$" . $nome_campo . "='" . $valor . "';";
           eval($comando);
        }

        $payment                        = new MercadoPago\Payment();
        $payment->token                 = $token;
        $payment->transaction_amount    = $transaction_amount;
        $payment->description           = $description;
        $payment->installments          = $installments;
        $payment->notification_url      = $url_notificacao;
        $payment->payment_method_id     = $payment_method_id;            
        
        $payment->payer = array(
            "email" => $email,
            "first_name" => $comprador_primeiro_nome,
            "last_name" => $comprador_sobrenome,
            "identification" => array(
                "type" => $docType,
                "number" => $docNumber
            ),
            "address"=>  array(
                "zip_code" => $cep,
                "street_name" => $endereco,
                "street_number" => $numero_endereco,
                "neighborhood" => $bairro,
                "city" => $cidade,
                "federal_unit" => $uf
            )
        );
    
        $payment->save();

        if($payment->status =="approved"){
            $status = "Aprovado";
            $detalheStatus = "Pagamento aprovado.";

            $db->Query("UPDATE `siparisler` SET `sp_durum` = '2', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_musteri_link`='$codigo'");
         
            redirect($url_status);
            exit;
            
        }elseif($payment->status =="in_process"){
            $status = "Análise";
            $detalheStatus = "Pagamento em análise. Pode levar até 2 dias úteis para aprovação, mas geralmente em alguns a análise é concluída.";
                       
            $db->Query("UPDATE `siparisler` SET `sp_durum` = '2', `status`='$status', `detalhe_status`='$detalheStatus' WHERE `sp_musteri_link`='$codigo'");
         
            redirect($url_status);
            exit;
            
        }elseif($payment->status ==""){
            $erro = $payment->error->message;            
        }else{
            
            if($payment->status_detail == "cc_rejected_bad_filled_card_number"){
                $erro = 'Ops! Algo deu errado: verifique o número do cartão e tente novamente.';
            }
            
            if($payment->status_detail == "cc_rejected_bad_filled_date"){
                $erro = 'Ops! Algo deu errado: verifique a data de vencimento do cartão e tente novamente.';
            }
            
            if($payment->status_detail == "cc_rejected_bad_filled_other"){
                $erro = 'Ops! Algo deu errado: revise os dados do cartão e tente novamente.';
            }
            
            if($payment->status_detail == "cc_rejected_bad_filled_security_code"){
                $erro = 'Ops! Algo deu errado: verifique o código de segurança do cartão e tente novamente.';
            }
            
            if($payment->status_detail == "cc_rejected_blacklist"){
                $erro = 'Ops! Algo deu errado: não conseguimos processar o seu pagamento. Entre em contato com a operadora ou tente outra forma de pagamento.';
            }
            
            if($payment->status_detail == "cc_rejected_call_for_authorize"){
                $erro = 'Ops! Algo deu errado: Você deve autorizar o uso do seu cartão junto à operadora.';
            }
            
            if($payment->status_detail == "cc_rejected_card_disabled"){
                $erro = 'Ops! Algo deu errado: Você deve ligar para a operadora do seu cartão para ativar seu cartão.';
            }
            
            if($payment->status_detail == "cc_rejected_card_error"){
                $erro = 'Ops! Algo deu errado: não conseguimos processar o seu pagamento. Entre em contato com a operadora ou tente outra forma de pagamento.';
            }
            
            if($payment->status_detail == "cc_rejected_duplicated_payment"){
                $erro = 'Ops! Você já efetuou um pagamento com esse valor à poucos minutos. Caso precise pagar novamente, utilize outro cartão ou outra forma de pagamento.';
            }
            
            if($payment->status_detail == "cc_rejected_high_risk"){
                $erro = 'Ops! Algo deu errado: não conseguimos processar o seu pagamento. Entre em contato com a operadora ou tente outra forma de pagamento.';
            }
            
            if($payment->status_detail == "cc_rejected_insufficient_amount"){
                $erro = 'Ops! Algo deu errado: parece que o seu cartão possui saldo insuficiente.';
            }
            
            if($payment->status_detail == "cc_rejected_max_attempts"){
                $erro = 'Ops! Algo deu errado: Você atingiu o limite de tentativas permitido. Entre em contato com a operadora ou tente outra forma de pagamento.';
            }
            
            if($payment->status_detail == "cc_rejected_other_reason"){
                $erro = 'Ops! Algo deu errado: não conseguimos processar o seu pagamento. Entre em contato com a operadora ou tente outra forma de pagamento.';
            }
                        
        }
    
    }  
    */
?>

<!--
<!DOCTYPE html> 
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
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
                            </ul>
                        </li>
                        <li class="menubut1 keskin xrb"><a href="https://osegredodafama.com.br/contato/"><i class="fas fa-envelope"></i> Entre em contato</a></li>
                        <li class="menubut2 keskin xrbs"><a href="#" data-toggle="modal" rel="noreferrer" data-target="#sorgu"><i class="fas fa-search"></i> Acompanhar pedido</a></li>
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
                                
                                <div class="box-1 user col-md-6">
                                    <div class="box-inner-1">
                                        <p class="dis info mb-3 fw-bold h4">Informações</p>
                                        <p class="dis info mb-3">Seu pagamento será feito no cartão de crédito</p>
                                        <div class="justify-content-md-center">
                                            <img class="d-block mx-auto mb-1" src="../images/credit-card.png" alt="" width="70">

                                            <label for="one" class="box py-2 first">
                                                <div class="d-flex align-items-start">
                                                    <span class="circle"></span>
                                                    <div class="course">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <span class="fw-bold">
                                                            <?=$transacao_descricao?>
                                                            </span>
                                                            R$ <?=$valor_final_formatado?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>       
                                        
                                    </div>
                                </div>                    

                                <div class="box-2 col-md-6 text-left">
                                    
                                    <p class="fw-bold h4">Dados para pagamento</p>
                                    <p class="dis mb-3">Preencha os dados abaixo para efetuar o pagamento</p>

                                    <?php if($erro!=""){ ?>
                                    <div class="alert alert-danger mb-1" role="alert">
                                        <?=$erro;?>
                                    </div>
                                    <?php } ?>
                                        
                                    <form method="post" id="pay" name="pay">
                                        
                                        <input type="hidden" name="description" id="description" value="<?=$transacao_descricao?>"/>
                                        <input type="hidden" name="transaction_amount" id="transaction_amount" value="<?=$valor_final;?>"/>
                                        <input type="hidden" name="payment_method_id" id="payment_method_id"/>
                                        
                                        <div style="display: none;">
                                            <select id="docType" name="docType" data-checkout="docType">
                                                <option value="CPF"></option>
                                            </select>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="form-group col-12">
                                            
                                                <div class="mb-1 mt-4">
                                                    <p class="h6">Dados do dono do cartão</p>
                                                    <hr/>
                                                </div>

                                                <div class="mb-3 mt-3">
                                                    <p class="dis fw-bold mb-2">Nome completo</p>
                                                    <input type="text" class="form-control" id="fullname" name="fullname" data-checkout="fullname" value="<?=$fullname;?>" required="" autofocus autocomplete="off">
                                                </div>

                                                <div class="mb-3 mt-3">
                                                    <p class="dis fw-bold mb-2">Email</p>
                                                    <input type="email" class="form-control" id="email" name="email" data-checkout="email" value="<?=$email;?>" required="" autofocus autocomplete="off">
                                                </div>
                                                
                                                <div class="mb-3 mt-3">
                                                    <p class="dis fw-bold mb-2">CPF (Apenas números)</p>
                                                    <input type="number" class="form-control" id="docNumber" data-checkout="docNumber" value="<?=$cpf;?>" required="" autofocus autocomplete="off">
                                                </div>

                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">Telefone <strong style="color: blue;">(Com DDD e sem espaços)</strong></p>
                                                    <input type="number" name="phone" id="phone"  value="<?=$ddd.$phone;?>" class="form-control" id="textinput" required>
                                                </div>

                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">CEP (Apenas endereço do Brasil)</p>
                                                    <input type="number" class="form-control" name="cep" value="<?=$cep;?>" type="text" id="cep" value="" required>
                                                </div>

                                            </div>  
                                            
                                            <div class="form-group col-12 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Endereço</p>
                                                <input type="text" name="endereco" value="<?=$endereco;?>" id="endereco" class="form-control" required>
                                            </div>

                                            <div class="form-group col-5 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Número</p>
                                                <input type="number" class="form-control" name="numero_endereco" value="<?=$numero_endereco;?>" id="numero" required>
                                            </div>

                                            <div class="form-group col-7 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Complemento</p>
                                                <input type="text" class="form-control" name="complemento" value="<?=$complemento;?>"  id="complemento">
                                            </div>

                                            <div class="form-group col-12 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Bairro</p>    
                                                <input type="text" class="form-control" value="<?=$bairro;?>" name="bairro" id="bairro" required>
                                            </div>

                                            <div class="form-group col-12 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Cidade</p>    
                                                <input type="text" class="form-control" value="<?=$cidade;?>" name="cidade" id="cidade" required>
                                            </div>

                                            <div class="form-group col-12 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">Estado</p>   
                                                <input type="text" class="form-control" value="<?=$uf;?>" name="uf" id="uf" required>
                                            </div>

                                            <div class="form-group" style="display: none;">
                                                <label class="label mb-1" for="text4b">Cod. IBGE</label>
                                                <input type="text" class="form-control" id="ibge">
                                            </div>

                                            <div class="form-group col-12 mb-3 endCompleto" style="display: none;">
                                                <p class="dis fw-bold mb-2">País</p>
                                                <input type="text" value="Brasil" class="form-control" name="pais" id="pais" disabled>
                                            </div>

                                            <div class="form-group col-12">
                                            
                                                <div class="mb-1 mt-4">
                                                    <p class="h6">Dados do cartão</p>
                                                    <hr/>
                                                </div>
                                                
                                                <div class="mb-3 mt-3">
                                                    <p class="dis fw-bold mb-2">Nome como consta no cartão</p>
                                                    <input type="text" class="form-control" style="text-transform: uppercase;" id="cardholderName" data-checkout="cardholderName" placeholder="" required="" autofocus autocomplete="off">
                                                </div>

                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">Número do cartão</p>
                                                    <input type="text" class="form-control cc_numero" id="cardNumber" data-checkout="cardNumber" onselectstart="return false" onCopy="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete=off required=""/>
                                                    <div class="brand"></div>
                                                </div>
                                            </div>

                                            <div class="form-group col-5">
                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">Mês de validade</p>
                                                    <select class="form-control custom-select" id="cardExpirationMonth" data-checkout="cardExpirationMonth">
                                                        <option value="01">01 - JAN</option>
                                                        <option value="02">02 - FEV</option>
                                                        <option value="03">03 - MAR</option>
                                                        <option value="04">04 - ABR</option>
                                                        <option value="05">05 - MAI</option>
                                                        <option value="06">06 - JUN</option>
                                                        <option value="07">07 - JUL</option>
                                                        <option value="08">08 - AGO</option>
                                                        <option value="09">09 - SET</option>
                                                        <option value="10">10 - OUT</option>
                                                        <option value="11">11 - NOV</option>
                                                        <option value="12">12 - DEZ</option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div class="form-group col-7">
                                                <p class="dis fw-bold mb-2">Ano de validade</p>                                
                                                <select class="form-control custom-select" id="cardExpirationYear" data-checkout="cardExpirationYear">
                                                    <option value="22">2022</option>
                                                    <option value="23">2023</option>
                                                    <option value="24">2024</option>
                                                    <option value="25">2025</option>
                                                    <option value="26">2026</option>
                                                    <option value="27">2027</option>
                                                    <option value="28">2028</option>
                                                    <option value="29">2029</option>
                                                    <option value="30">2030</option>
                                                    <option value="31">2031</option>
                                                    <option value="32">2032</option>
                                                    <option value="33">2033</option>
                                                    <option value="34">2034</option>
                                                    <option value="35">2035</option>
                                                    <option value="36">2036</option>
                                                    <option value="37">2037</option>
                                                </select>

                                            </div>

                                            <div class="form-group col-6">                            
                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">CVV</p>
                                                    <input type="number" id="securityCode" data-checkout="securityCode" class="form-control cc_cod_seguranca" onKeyDown="limitText(this,4);" onKeyUp="limitText(this,4);" data-checkout="cardNumber" onselectstart="return false" onpaste="return false" onCopy="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete=off required=""/>
                                                </div>
                                            </div>   

                                            <div class="form-group col-12">                            
                                                <div class="mb-3">
                                                    <p class="dis fw-bold mb-2">Parcelas</p>
                                                    <select id="installments" class="form-control" name="installments"></select>
                                                </div>
                                            </div> 
                                            
                                        </div>

                                        <div class="form-group col-12">
                                            <button type="submit" id="pgtoCredit" class="btn btn-success form-group mt-2 w-100"">AUTORIZAR PAGAMENTO</button>
                                        </div>    

                                        <div class="form-group text-center" id="loading" style="display: none;">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </div>

                                    </form>                        
                                    
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

        <script type='text/javascript'>        
            
            $(document).ready(function() {
                function limpa_formulário_cep() {
                    // Limpa valores do formulário de cep.
                    $("#endereco").val("");
                    $("#bairro").val("");
                    $("#cidade").val("");
                    $("#uf").val("");
                    $("#ibge").val("");
                }
                
                //Quando o campo cep perde o foco.
                $("#cep").blur(function() {
                    
                    //Nova variável "cep" somente com dígitos.
                    var cep = $(this).val().replace(/\D/g, '');

                    //Verifica se campo cep possui valor informado.
                    if (cep != "") {
                        $(".endCompleto").show(); 

                        //Expressão regular para validar o CEP.
                        var validacep = /^[0-9]{8}$/;

                        //Valida o formato do CEP.
                        if(validacep.test(cep)) {

                            //Preenche os campos com "..." enquanto consulta webservice.
                            $("#endereco").val("...");
                            $("#bairro").val("...");
                            $("#cidade").val("...");
                            $("#uf").val("...");
                            $("#ibge").val("...");

                        //Consulta o webservice viacep.com.br/
                            $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                                if (!("erro" in dados)) {
                                    //Atualiza os campos com os valores da consulta.
                                    $("#endereco").val(dados.logradouro);
                                    $("#bairro").val(dados.bairro);
                                    $("#cidade").val(dados.localidade);
                                    $("#uf").val(dados.uf);
                                    $("#ibge").val(dados.ibge);
                                } //end if.
                                else {
                                    //CEP pesquisado não foi encontrado.
                                    limpa_formulário_cep();
                                    alert("CEP não encontrado.");
                                }
                            });
                        } //end if.
                        else {
                            //cep é inválido.
                            limpa_formulário_cep();
                            alert("Formato de CEP inválido.");
                        }
                    } //end if.
                    else {
                        //cep sem valor, limpa formulário.
                        limpa_formulário_cep();
                    }
                });
            });

            $("#cardholderName").on("input", function(){
            var regexp = /[^A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ ]/g;
            if($(this).val().match(regexp)){
                $(this).val( $(this).val().replace(regexp,'') );
            }
            });
            
            function limitText(limitField, limitNum) { 
                if (limitField.value.length > limitNum) { 
                limitField.value = limitField.value.substring(0, limitNum); 
                } 
            }
            
            $('form#pay').submit( function( e ) {
                $("#pgtoCredit").hide();
                $("#loading").show(); 
            });
        </script>
    </body>
</html>
-->