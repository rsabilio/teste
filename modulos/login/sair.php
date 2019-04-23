<?php
//
// SIMP
// Descricao: Arquivo com formulario de log-in
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 25/05/2010
// Modificado: 05/10/2010
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

// Pagina para onde o usuario e' redirecionado apos deslogar
$link_principal = $CFG->wwwlogin;

/// Verificar se o sistema esta' instalado
if (!$CFG->versao) {
    header("Location: {$CFG->wwwroot}instalar.php");
    exit(0);
}

// Se deslogou do sistema
if (isset($_SESSION[$CFG->codigo_session])) {
    $cod = $_SESSION[$CFG->codigo_session];

    // Gerar Log
    $log = new log_sistema();
    if ($log->inserir($cod, LOG_SAIDA, 0, $cod, 'usuario', $_SERVER['HTTP_USER_AGENT'])) {
        $avisos[] = 'At&eacute; logo.';
    } else {
        $erros[] = 'Erro ao gerar o log de sa&iacute;da';
        $erros = array_merge($erros, $log->get_erros());
    }

    // Destruir sessao
    destruir_sessao(true);
}

// Redirecionar para pagina de login
$link_principal = link::adicionar_atributo($link_principal, 'saiu', '1');
header('Location: '.$link_principal);
exit(0);


//
//     Destroi a sessao atual
//
function destruir_sessao($cookie = true) {
// Bool $cookie: indica se deve destruir o cookie tambem
//
    global $CFG;
    if (isset($_COOKIE[$CFG->id_session])) {
        if (isset($_SESSION)) {
            $_SESSION[$CFG->codigo_session] = 0;
            $_SESSION['md5_user_agent'] = '';
            @session_destroy();
            unset($_SESSION);
        }

        // Destruir o cookie que guarda o ID da sessao
        if ($cookie) {
            setcookie($CFG->id_session, false, $CFG->time - 1, $CFG->path, $CFG->dominio_cookies);
        }
    }
}
