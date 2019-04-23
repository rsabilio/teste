<?php
//
// SIMP
// Descricao: Atalho para logar como um usuario
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 17/03/2011
// Modificado: 06/04/2011
// License: LICENSE.TXT
// Copyright (C) 2011  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Logar Como';
$nav[$CFG->wwwmods.$modulo.'/index.php'] = 'Desenvolvimento';
$nav[''] = 'Logar Como';
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Bloquear caso necessario
require_once($CFG->dirmods.$modulo.'/bloqueio.php');

$erros = array();
if (isset($_GET['op'])) {
    tratar_eventos($erros);
}

/// Imprimir Pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo($titulo);
if (!empty($erros)) {
    mensagem::erro($erros);
}
imprimir_formulario();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


/// @ Funcoes


//
//     Trata os eventos requisitados
//
function tratar_eventos(&$erros) {
// Array[String] $erros: erros ocorridos
//
    global $CFG;

    $tempo_cookie = 157680000; // 5 anos

    switch ($_GET['op']) {
    case 'logar':
        if (isset($_GET['cod_usuario'])) {
            $usuario = new usuario('', $_GET['cod_usuario']);
            if ($usuario->existe()) {
                logar_como_usuario($usuario);
            } else {
                $erros[] = 'Usu&aacute;rio inv&aacute;lido';
            }
        } elseif (isset($_GET['login'])) {
            $usuario = new usuario('login', $_GET['login']);
            if ($usuario->existe()) {
                logar_como_usuario($usuario);
            } else {
                $erros[] = 'Usu&aacute;rio inv&aacute;lido';
            }
        }
        break;
    case 'remover':
        if (isset($_GET['cod_usuario'])) {
            $cod_usuario = (int)$_GET['cod_usuario'];
            $simp_usuarios = explode('.', $_COOKIE['simp_usuarios']);
            $pos = array_search($cod_usuario, $simp_usuarios);
            if ($pos !== false) {
                unset($simp_usuarios[$pos]);
                setcookie('simp_usuarios', implode('.', $simp_usuarios), $CFG->time + $tempo_cookie, $CFG->path, $CFG->dominio_cookies);
            }
        }
        header('Location: '.$CFG->wwwroot.'index.php');
        exit(0);

    case 'adicionar':
        if (isset($_GET['cod_usuario'])) {
            $cod_usuario = (int)$_GET['cod_usuario'];
            if (!isset($_COOKIE['simp_usuarios'])) {
                $simp_usuarios = array($cod_usuario);
                setcookie('simp_usuarios', implode('.', $simp_usuarios), $CFG->time + $tempo_cookie, $CFG->path, $CFG->dominio_cookies);
            } else {
                $simp_usuarios = explode('.', $_COOKIE['simp_usuarios']);
                if (!in_array($cod_usuario, $simp_usuarios)) {
                    $simp_usuarios[] = $cod_usuario;
                    setcookie('simp_usuarios', implode('.', $simp_usuarios), $CFG->time + $tempo_cookie, $CFG->path, $CFG->dominio_cookies);
                }
            }
        }
        header('Location: '.$CFG->wwwroot.'index.php');
        exit(0);
    }
}


//
//     Loga como o usuario especificado
//
function logar_como_usuario($usuario) {
// usuario $usuario: usuario a ser logado
//
    global $CFG;

    // Registrar saida do usuario antigo
    if (isset($_SESSION[$CFG->codigo_session])) {
        $cod_antigo = $_SESSION[$CFG->codigo_session];
        $log = new log_sistema();
        $log->inserir($cod_antigo, LOG_SAIDA, 0, $cod_antigo, 'usuario', $_SERVER['HTTP_USER_AGENT']);
    }
    
    // Limpar cache
    objeto::limpar_cache('usuario', $usuario->cod_usuario);
    objeto::limpar_cache('usuarios_grupos');
    objeto::limpar_cache('permissao');
    objeto::limpar_cache('arquivo');
    objeto::limpar_cache('grupo');

    // Limpar Sessao
    $_SESSION = array();

    // Gravar Sessao
    $_SESSION[$CFG->codigo_session] = $usuario->cod_usuario;
    $_SESSION['md5_user_agent'] = md5($_SERVER['HTTP_USER_AGENT']);

    // Registrar entrada do novo usuario
    $cod_novo = $_SESSION[$CFG->codigo_session];
    $log = new log_sistema();
    $log->inserir($cod_novo, LOG_ENTRADA, 0, $cod_novo, 'usuario', $_SERVER['HTTP_USER_AGENT']);

    // Redirecionar
    header('Location: '.$CFG->wwwroot.'index.php');
    exit(0);
}


//
//     Imprime o formulario de login
//
function imprimir_formulario() {
    global $CFG;
    $action = $CFG->site;
    link::normalizar($action, true);

    $login = isset($_GET['login']) ? $_GET['login'] : '';

    $form = new formulario($action, 'form_logar_como', 'formulario', 'get', false);
    $form->campo_busca('login', 'login', 'usuario', 'login', $login, null, 255, 30, 'Login');
    $form->campo_hidden('op', 'logar');
    $form->campo_submit('enviar', 'enviar', 'Logar');
    $form->imprimir();
}
