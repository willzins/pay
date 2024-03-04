<?php
/// Setar para true para debugar erros
ini_set('display_errors',0);
ini_set('display_startup_erros',0);
//error_reporting(E_ALL);  /// Descomentar para debugar erros

/// Setando charset e timezona 
ini_set('default_charset', 'UTF-8');
date_default_timezone_set('America/Sao_Paulo');

/// Otimização
ini_set('implicit_flush', 1);
set_time_limit(0);

/// Iniciando sessão
if ( session_status() !== PHP_SESSION_ACTIVE ) {
    session_start();
}

$access_token = "APP_USR-6527948639108607-033102-e62f7917ada2fc77a60f0181f66f4f6d-154847063";
$public_key   = "APP_USR-6a4b08ff-ca2f-42b0-8624-6bfcb62e22b5";

$client_id    = "6527948639108607";
$client_secret= "ej5SN3MOPx5suEHFkgyR1BfItoHq7aGZ";

$tokenApi = "dd5a48a0c74a756fe43d2c10a045c9de";

/// Definindo raiz desse arquivo e diretório do arquivo de funções
define('BASE_PATH', realpath(dirname(__FILE__)));
define('FUNCTIONS_PATH', realpath(dirname(__FILE__).'/..'));

/// Incluindo arquivo de funções
include(FUNCTIONS_PATH.'/functions/functions.php');

/// Incluindo arquivo de conexão com o banco de dados
include(BASE_PATH.'/database/database.php');

/// Controle de sessão
$is_online = false;
if(isset($_SESSION['EX_login'])){
	$ses_id = $db->EscapeString($_SESSION['EX_login']);
    $data	= $db->QueryFetchArray("SELECT *,UNIX_TIMESTAMP(`online`) AS `online` FROM `usuarios` WHERE `id`='".$ses_id."' AND `banido`='0' LIMIT 1");
	$is_online = true;

	if(empty($data['id'])){
		session_destroy();
		$is_online = false;
	}elseif($data['online']+600 < time() && defined('IS_AJAX')){
		$db->Query("UPDATE `usuarios` SET `online`=NOW() WHERE `id`='".$data['id']."'");
		$_SESSION['EX_login'] = $data['id'];
	}

}else{

	if(isset($_COOKIE['autoLogin'])){
		if(!empty($ses_user) && !empty($ses_hash)){
			$data = $db->QueryFetchArray("SELECT *,UNIX_TIMESTAMP(`online`) AS `online` FROM `usuarios` WHERE (`email`='".$ses_user."') AND (`senha`='".$ses_hash."' AND `banido`='0') LIMIT 1");

			if(empty($data['id'])){
				unset($_COOKIE['autoLogin']);
			}else{
				$_SESSION['EX_login'] = $data['id'];
				$is_online = true;
			}

		}else{
			unset($_COOKIE['autoLogin']);
		}
	}
}

header('Content-type: text/html; charset=pt-br');
?>