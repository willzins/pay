<?php

    

    /// Dados de conexão com o banco de dados

    $configDB['sql_host']       = 'localhost'; /// Local do banco

    $configDB['sql_database']   = 'osegredodafama'; /// Nome do banco

    $configDB['sql_username']   = 'osegredodafama';  /// Nome do banco		   

    $configDB['sql_password']   = 'osegredodafama855'; /// Senha do banco

    $configDB['sql_extenstion'] = 'MySQLi'; /// Extenção de conexão. NÃO ALTERAR.

      

    /// Incluindo arquivo de funções para banco de dados

    include('MySQLi.php');

    

    /// Conectando ao banco de dados

    $db = new MySQLConnection($configDB['sql_host'], $configDB['sql_username'], $configDB['sql_password'], $configDB['sql_database']);

    $db->Connect();

    

    $db->SetNames();

    

?>

