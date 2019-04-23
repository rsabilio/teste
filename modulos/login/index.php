<?php
//
// SIMP
// Descricao: Arquivo com formulario de log-in
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.5
// Data: 03/03/2007
// Modificado: 26/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

/// Verificar se o sistema esta' instalado
if (!$CFG->versao) {
    header('Location: '.$CFG->wwwroot.'instalar.php');
    exit(0);
}


/// Constantes
define('MAX_ERROS',     5);
define('TEMPO_ESPERA', 60);


/// Dados do Formulario
$modulo = modulo::get_modulo(__FILE__);


/// Dados da Pagina
$id_pagina = 'pagina_login';
$titulo    = 'Log-in';
$nav[]     = $modulo.'#'.basename(__FILE__);
$estilos   = array($CFG->wwwmods.$modulo.'/estilos.css');

if ($CFG->autenticacao == 'simp') {
    $mensagem_senha = '<p>Caso voc&ecirc; tenha esquecido a sua senha, favor acessar a '.
                      'op&ccedil;&atilde;o "Esqueci Minha Senha", abaixo do formul&aacute;rio.</p>';
} else {
    $mensagem_senha = '<p>Caso voc&ecirc; tenha esquecido a sua senha, favor entrar em contato '.
                      'com os respons&aacute;veis pelo sistema ('.texto::proteger_email($CFG->email_padrao).')</p>';
}

$ajuda = <<<AJUDA
  <p>Este formul&aacute;rio &eacute; respons&aacute;vel pela autentica&ccedil;&atilde;o de
     usu&aacute;rios para acessar o sistema.</p>
  <p>O login &eacute; um texto que identifica, de maneira &uacute;nica, determinado usu&aacute;rio do
     sistema. A senha, &eacute; um texto que garante que o informante do login realmente &eacute; quem
     diz ser.</p>
  {$mensagem_senha}
AJUDA;


// Avisos e Erros
$avisos = array();
$erros  = array();


// Se deslogou do sistema
if (isset($_GET['saiu'])) {
    $avisos[] = 'At&eacute; logo.';

// Se a sessao expirou
} elseif (isset($_COOKIE['sessao_expirada']) && $_COOKIE['sessao_expirada']) {
    $avisos[] = 'O tempo da sess&atilde;o expirou. Por favor, autentique-se novamente.';
    setcookie('sessao_expirada', null, $CFG->time - 1, $CFG->path, $CFG->dominio_cookies);
} else {
    if (isset($_GET['erro'])) {
        switch ($_GET['erro']) {
        case 'log':
            $erros[] = 'Erro ao gerar log';
            break;
        case 'usuario_inexistente':
            $erros[] = 'Usu&aacute;rio inexistente';
            break;
        }
    }
}

// Metodo de autenticacao HTTP
if ($CFG->autenticacao_http) {
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    if ($avisos) { mensagem::aviso($avisos); }
    if ($erros)  { mensagem::erro($erros);   }
    echo '<p><a id="entrar_http" href="'.$CFG->wwwmods.$modulo.'/http.php">Entrar no Sistema</a></p>';
    imprimir_links($pagina);
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);
}

/// Se errou muitas vezes o preenchimento, esperar
if (!iniciar_erros()) {
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    mensagem::comentario($CFG->site, $ajuda);
    echo '<p>Voc&ecirc; preencheu o formul&aacute;rio incorretamente mais de '.MAX_ERROS.' vezes.</p>';
    echo '<p>O sistema bloqueou o seu acesso por '.TEMPO_ESPERA.' segundos por uma quest&atilde;o de seguran&ccedil;a.</p>';
    echo '<p>Por favor, aguarde. Caso enfrente dificuldades, entre em contato com os respons&aacute;veis pelo sistema.</p>';
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);
}

$dados = formulario::get_dados();
sanitizar($dados);

// Se nao enviou os dados de log-in: imprimir formulario
if (!$dados) {

    // Imprimir pagina
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    if ($avisos) { mensagem::aviso($avisos); }
    if ($erros)  { mensagem::erro($erros);   }
    mensagem::comentario($CFG->site, $ajuda);
    imprimir_form();
    imprimir_links($pagina);
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);

// Conferir os dados do usuario
} elseif (possui_erros($dados, $erros)) {

    // Se deseja guardar login para posterior entrada
    if ($dados->lembrar_login) {
        $CFG->cookies['login'] = $dados->login;

    // Se nao deseja guardar login para posterior entrada
    } else {

        // Se nao deseja guardar login, entao apaga-lo
        if (isset($CFG->cookies['login'])) {
            unset($CFG->cookies['login']);
        }
    }
    cookie::salvar($CFG->cookies);

    // Gerar log de erro
    $usuario = new usuario('login', $dados->login, array('login', 'senha', 'cancelado'));
    $cod_usuario = $usuario->existe() ? $usuario->cod_usuario : 0;
    $log = new log_sistema();
    $log->inserir($cod_usuario, LOG_ENTRADA, 1, $cod_usuario, 'usuario', 'tentou logar como '.$dados->login.' ('.$_SERVER['HTTP_USER_AGENT'].')');

    // Imprimir pagina
    $pagina = new pagina($id_pagina);
    $pagina->cabecalho($titulo, $nav, $estilos);
    $pagina->inicio_conteudo();
    mensagem::erro($erros);
    mensagem::comentario($CFG->site, $ajuda);
    imprimir_form($dados);
    imprimir_links($pagina);
    $pagina->fim_conteudo();
    $pagina->rodape();
    exit(0);

// Se nao houve erro no log-in: guardar sessao
} else {
    $usuario = new usuario('login', $dados->login, array('login', 'senha', 'cancelado'));

    // Se o usuario nao existe mais (sabe-se la como...)
    if (!$usuario->existe()) {

        // Destruir a sessao para nao deixar lixo no servidor
        destruir_sessao(true);

        // Gerar Log
        $log = new log_sistema();
        $log->inserir($usuario->cod_usuario, LOG_ENTRADA, 1, 0, 'usuario', 'usuario apagado '.$_SERVER['HTTP_USER_AGENT']);

        $link_login = link::adicionar_atributo($CFG->wwwlogin, 'erro', 'usuario_inexistente');
        header('Location: '.$link_login);
        exit(1);
    }

    // Se deseja guardar login para posterior entrada
    if ($dados->lembrar_login) {
        $CFG->cookies['login'] = $dados->login;

    // Se nao deseja guardar login, entao apaga-lo
    } else {
        if (isset($CFG->cookies['login'])) {
            unset($CFG->cookies['login']);
        }
    }
    cookie::salvar($CFG->cookies);

    // Gerar Log
    $log = new log_sistema();
    if (!$log->inserir($usuario->cod_usuario, LOG_ENTRADA, 0, $usuario->cod_usuario, 'usuario', $_SERVER['HTTP_USER_AGENT'])) {
        $link_login = link::adicionar_atributo($CFG->wwwlogin, 'erro', 'log');
        header('Location: '.$link_login);
        exit(1);
    }

    // Gravar Sessao
    $_SESSION[$CFG->codigo_session] = $usuario->cod_usuario;
    $_SESSION['md5_user_agent'] = md5($_SERVER['HTTP_USER_AGENT']);

    // Ir para pagina principal ou de destino
    if (isset($_GET['destino'])) {
        $destino = texto::decodificar(base64_decode($_GET['destino']));
        header('Location: '.$destino);
        exit(0);
    } else {
        header('Location: '.$CFG->wwwroot.'index.php');
        exit(0);
    }
}


//
//     Iniciar os dados de sessao de erros
//
function iniciar_erros() {
    foreach ($_SESSION as $chave => $valor) {
        if ($chave != __FILE__) {
            unset($_SESSION[$chave]);
        }
    }
    if (!isset($_SESSION[__FILE__])) {
        $_SESSION[__FILE__] = array(
            'time'         => false,
            'numero_erros' => 0
        );
    }

    // Se excedeu o numero de erros
    if ($_SESSION[__FILE__]['numero_erros'] > MAX_ERROS) {

        // Se nao marcou o tempo de inicio
        if (!$_SESSION[__FILE__]['time']) {
            $_SESSION[__FILE__]['time'] = time();
        }

        // Se esta no tempo de espera
        if (time() < $_SESSION[__FILE__]['time'] + TEMPO_ESPERA) {
            return false;

        // Se ja cumpriu o tempo de espera
        } else {
            $_SESSION[__FILE__]['time'] = false;
            $_SESSION[__FILE__]['numero_erros'] = 0;
        }
    }
    return true;
}


//
//     Incrementa o numero de erros
//
function incrementar_numero_erros() {
    $_SESSION[__FILE__]['numero_erros'] += 1;
}


//
//     Imprime o formulario de log-in
//
function imprimir_form($dados = null) {
// Object $dados: dados enviados pelo formulario na forma de objeto
//
    global $CFG, $pagina;

    if (is_null($dados)) {
        $dados = new stdClass();
    }

    // Lembrar login, caso seja possivel
    $login_padrao = 'login';
    if (!isset($dados->login)) {

        // Se possui um login nos cookies
        if (isset($CFG->cookies['login'])) {
            $login_padrao = $CFG->cookies['login'];

        // Se informou via get
        } elseif (isset($_GET['login']) && $_GET['login'] != '') {
            $login_padrao = trim($_GET['login']);
        }
    }

    $padrao = array(
        'login'         => $login_padrao,
        'senha'         => '',
        'lembrar_login' => isset($CFG->cookies['login'])
    );
    $dados = formulario::montar_dados($padrao, $dados);

    // Checar se esta' no modo de manutencao
    if ($CFG->fechado) {

        $mensagem = 'O sistema est&aacute; fechado temporariamente para manuten&ccedil;&atilde;o.';
        if ($CFG->motivo_fechado) {
            $mensagem .= ' O seguinte motivo foi deixado: '.$CFG->motivo_fechado;
        }

        mensagem::aviso($mensagem);
    }

    $action = $CFG->wwwlogin;
    if (isset($_GET['destino'])) {
        $destino = $_GET['destino'];
        $action = link::adicionar_atributo($action, 'destino', $destino);
    }

    $usuario = new usuario();
    $form = new formulario($action, 'form_login', false, '', 0);
    $form->titulo_formulario('Autentica&ccedil;&atilde;o no sistema');
    if ($CFG->autenticacao !== 'simp') {
        $drivers = autenticacao::get_drivers(true);
        $form->campo_informacao('Autentica&ccedil;&atilde;o via: '.$drivers[$CFG->autenticacao]);
    }
    $usuario->campo_formulario($form, 'login', $dados->login);
    $usuario->campo_formulario($form, 'senha', '');
    $form->campo_bool('lembrar_login', 'lembrar_login', 'Lembrar login neste computador', $dados->lembrar_login);
    $form->campo_submit('entrar', 'entrar', 'Entrar', true, true);
    $form->imprimir();
}


//
//    Valida os campos enviados no formulario
//
function possui_erros(&$dados, &$erros) {
// Object $dados: dados enviados pelo formulario
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

    // Login
    if ($dados->login === '') {
        $erros[] = 'Faltou preencher o login';
    } elseif (!$validacao->validar_campo('LOGIN', $dados->login, $erro_campo)) {
        $erros[] = 'Campo "login" possui caracteres inv&aacute;lidos ou n&atilde;o est&aacute; no padr&atilde;o.'.
                   ($erro_campo ? ' Detalhes: '.$erro_campo : '');
    } else {
        $dados->login = objeto::get_objeto('usuario')->converter_login($dados->login);
    }

    // Senha
    if ($dados->senha === '') {
        $erros[] = 'Faltou preencher a senha';
    }

    if (!empty($erros)) {
        return true;
    }
    $usuario = new usuario('login', $dados->login, array('login', 'senha', 'cancelado'));

    // Se esta no modo de manutencao
    if ($CFG->fechado && !$usuario->possui_grupo(COD_ADMIN)) {
        $erros[] = 'O sistema est&aacute; fechado temporariamente para manuten&ccedil;&atilde;o';

    // Autenticar usuario
    } else {
        if (($CFG->autenticacao == 'simp') && (!$usuario->existe())) {
            $erros[] = 'Usu&aacute;rio inv&aacute;lido (talvez o login esteja digitado errado)';
            incrementar_numero_erros();
        } elseif ($usuario->cancelado) {
            $erros[] = 'Usu&aacute;rio cancelado';
            incrementar_numero_erros();
        } elseif (!$usuario->validar_senha($dados->login, $dados->senha, $erros)) {
            if (empty($erros)) {
                $erros[] = 'Usu&aacute;rio/Senha inv&aacute;lidos';
            }
            incrementar_numero_erros();
        }
    }

    return !empty($erros);
}


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


//
//     Imprime a lista de opcoes adicionais
//
function imprimir_links($pagina) {
// pagina $pagina: objeto que imprime a pagina
//
    global $CFG;

    // Lista de links com opcoes
    $links = array();

    // Link outras formas de acesso
    $acessos = array_map('basename', glob($CFG->dirroot.'acesso/*', GLOB_ONLYDIR));
    $diff = array_diff($acessos, array('usuario'));
    if (!empty($diff)) {
        $l = $CFG->wwwroot.'acesso/index.php';
        $links[] = link::texto($l, 'Acesso', 'Outras formas de acesso', '', '', 1);
    }

    // Link de Nova senha
    $l = $CFG->wwwmods.'login/nova_senha.php';
    $links[] = link::texto($l, 'Esqueci minha senha', 'Gerar nova senha', '', '', 1);

    // Link de Ajuda
    $l = $CFG->wwwmods.'ajuda/index.php?login=1';
    $links[] = link::texto($l, 'Ajuda', 'T&oacute;picos de Ajuda', '', '', 1);

    $pagina->listar_opcoes($links);
}


//
//     Sanitiza os dados submetidos
//
function sanitizar(&$dados) {
// stdClass $dados: dados a serem sanitizados
//
    if (!$dados) {
        return;
    }
    $dados->login = formulario::filtrar('string', $dados->login);
    $dados->senha = formulario::filtrar('string', $dados->senha);
    $dados->lembrar_login = formulario::filtrar('bool', $dados->lembrar_login);
}
