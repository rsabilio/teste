<?php
//
// SIMP
// Descricao: Arquivo de autenticacao HTTP
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 21/09/2010
// Modificado: 05/10/2010
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

/// Verificar se o sistema esta' instalado
if (!$CFG->versao) {
    header('Location: '.$CFG->wwwroot.'instalar.php');
    exit(0);
}

$modulo = modulo::get_modulo(__FILE__);

/// Dados da Pagina
$id_pagina = 'pagina_login';
$titulo    = 'HTTP';
$nav[]     = $modulo.'#index.php';
$nav[]     = $modulo.'#'.basename(__FILE__);
$estilos   = array($CFG->wwwmods.$modulo.'/estilos.css');

// Guardar controle de erros em sessao
$abriu_agora = false;
if (!isset($_SESSION[__FILE__])) {
    
    // Guardar o time atual e o time do ultimo acesso para controlar os erros
    // que ja foram mostrados para o usuario, afinal, a autenticacao HTTP
    // nao permite que seja mostrada a mensagem de erro.
    $abriu_agora = true;
    $_SESSION[__FILE__]['acesso_atual'] = $CFG->time;
    $_SESSION[__FILE__]['ultimo_acesso'] = $CFG->time;
    $_SESSION[__FILE__]['mostrou_erros'] = array();
}

// Se nao enviou o usuario
if (!isset($_SERVER['PHP_AUTH_USER']) || $abriu_agora) {
    solicitar_autenticacao($id_pagina, $titulo, $nav, $estilos);

// Se enviou o usuario, checar se a senha confere
} elseif (possui_erros($erros)) {
    
    // Se ja mostrou os erros do ultimo acesso: autenticar novamente
    if ($_SESSION[__FILE__]['mostrou_erros'][$_SESSION[__FILE__]['ultimo_acesso']]) {
        
        // Passar o ultimo acesso
        unset($_SESSION[__FILE__]['mostrou_erros'][$_SESSION[__FILE__]['ultimo_acesso']]);
        $_SESSION[__FILE__]['ultimo_acesso'] = $_SESSION[__FILE__]['acesso_atual'];
        $_SESSION[__FILE__]['acesso_atual'] = $CFG->time;
        
        solicitar_autenticacao($id_pagina, $titulo, $nav, $estilos);
    }
    
    // Se nao mostrou os erros do ultimo acesso: mostrar erros
    $_SESSION[__FILE__]['mostrou_erros'][$_SESSION[__FILE__]['ultimo_acesso']] = true;
    
    // Imprimir pagina
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    mensagem::erro($erros);
    echo "<p><a href=\"{$CFG->site}\">Tentar Novamente</a></p>\n";
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);
    
// Se autenticou corretamente
} else {
    $usuario = get_usuario(array('login', 'cancelado', 'senha'));

    // Gerar Log
    $log = new log_sistema();
    if (!$log->inserir($usuario->cod_usuario, LOG_ENTRADA, 0, $usuario->cod_usuario, 'usuario', $_SERVER['HTTP_USER_AGENT'])) {
        $link_login = link::adicionar_atributo($CFG->wwwlogin, 'erro', 'log');
        header('Location: '.$link_login);
        exit(1);
    }

    // Apagar controle de erros deste arquivo
    unset($_SESSION[__FILE__]);

    // Gravar Sessao
    $_SESSION[$CFG->codigo_session] = $usuario->cod_usuario;
    $_SESSION['md5_user_agent'] = md5($_SERVER['HTTP_USER_AGENT']);

    header('Location: '.$CFG->wwwroot.'index.php');
    exit(0);
}


//
//     Solicita a autenticacao via HTTP
//
function solicitar_autenticacao($id_pagina, $titulo, $nav, $estilos) {
// String $id_pagina: identificador da pagina
// String $titulo: titulo da pagina
// Array[String] $nav: barra de navegacao
// Array[String] $estilos: vetor de estilos
//
    global $CFG;
    $realm = texto::strip_acentos($CFG->titulo);
    header("WWW-Authenticate: Basic realm=\"{$realm}\"");
    header('HTTP/1.0 401 Unauthorized');

    $_SESSION[__FILE__]['mostrou_erros'][$CFG->time] = false;

    // Imprimir pagina
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    mensagem::erro('&Eacute; necess&aacute;rio se autenticar para acessar o sistema.');
    echo "<p><a href=\"{$CFG->site}\">Tentar Novamente</a></p>\n";
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);
}


//
//    Valida os campos enviados no formulario
//
function possui_erros(&$erros) {
// Array[String] $erros: vetor de erros
//
    global $CFG;
    $erros = array();

    $validacao = validacao::get_instancia();

    // Checar se os cookies estao habilitados
    if (!isset($_COOKIE[$CFG->nome_cookie])) {
        $erros[] = 'Seu navegador n&atilde;o est&aacute; salvando os cookies. Procure saber se seu navegador d&aacute; suporte a este recurso ou se ele apenas est&aacute; desabilitado. Este sistema requer cookies para funcionar.';
        if (!DEVEL_BLOQUEADO) {
            if ($CFG->localhost) {
                $erros[] = '[DEBUG-DEVEL] As configura&ccedil;&otilde;es (arquivo config.php) indicam que o host &eacute; local.';
            } else {
                $erros[] = '[DEBUG-DEVEL] As configura&ccedil;&otilde;es (arquivo config.php) indicam que o host &eacute; registrado. Se isso n&atilde;o &eacute; verdade, altere as configura&ccedil;&otilde;es definindo "$localhost = true;" no local adequado.';
            }
        }
        return true;
    }

    $dados = new stdClass();
    $dados->login = $_SERVER['PHP_AUTH_USER'];
    $dados->senha = $_SERVER['PHP_AUTH_PW'];

    // Login
    if ($dados->login === '') {
        $erros[] = 'Faltou preencher o login';
    }

    // Senha
    if ($dados->senha === '') {
        $erros[] = 'Faltou preencher a senha';
    }

    if (!empty($erros)) {
        return true;
    }
    $usuario = get_usuario(array('login', 'cancelado', 'senha'));

    // Se esta no modo de manutencao
    if ($CFG->fechado && !$usuario->possui_grupo(COD_ADMIN)) {
        $erros[] = 'O sistema est&aacute; fechado temporariamente para manuten&ccedil;&atilde;o';

    // Autenticar usuario
    } else {
        if (($CFG->autenticacao == 'simp') && (!$usuario->existe())) {
            $erros[] = 'Usu&aacute;rio inv&aacute;lido (talvez o login esteja digitado errado)';
        } elseif ($usuario->cancelado) {
            $erros[] = 'Usu&aacute;rio cancelado';
        } elseif (!$usuario->validar_senha($dados->login, $dados->senha, $erros)) {
            if (empty($erros)) {
                $erros[] = 'Usu&aacute;rio/Senha inv&aacute;lidos';
            }
        }
    }
    return !empty($erros);
}


//
//     Obtem um usuario pelo seu login completo
//
function get_usuario($campos = false) {
// Array[String] || Bool $campos: campos a serem consultados automaticamente
//
    $login = $_SERVER['PHP_AUTH_USER'];
    return new usuario('login', $login, $campos);
}
