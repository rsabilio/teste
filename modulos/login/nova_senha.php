<?php
//
// SIMP
// Descricao: Gera nova senha e envia ao usuario
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.4
// Data: 03/03/2007
// Modificado: 11/04/2011
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
define('MAX_ERROS',    10); // Maximo de erros permitidos
define('TEMPO_ESPERA', 60); // Tempo (em segundos) de espera caso exceda o maximo de erros


/// Dados do Formulario
$modulo = modulo::get_modulo(__FILE__);
$email  = texto::proteger_email($CFG->email_padrao);
$ajuda  = <<<AJUDA
  <p>Este formul&aacute;rio permite aos usu&aacute;rios, que esqueceram suas senhas, gerar uma nova senha e envi&aacute;-la por e-mail
  mediante a confirma&ccedil;&atilde;o de alguns campos. Caso os campos pedidos foram esquecidos ou seu e-mail tenha sido alterado,
  favor entrar em contato com os respons&aacute;veis pelo sistema. E-mail de contato principal: {$email}.
</p>
AJUDA;


/// Dados da Pagina
$id_pagina = 'esqueci_senha';
$titulo    = 'Nova Senha';
$nav[]     = $modulo.'#index.php';
$nav[]     = $modulo.'#'.basename(__FILE__);
$estilos   = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Imprimir Pagina
$pagina = new pagina($id_pagina);
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo();
mensagem::comentario($CFG->site, $ajuda);
logica_nova_senha();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Realiza a logica de geracao de nova senha
//
function logica_nova_senha() {
    $dados = formulario::get_dados();
    sanitizar($dados);

    if (!iniciar_erros()) {
        return false;
    }
    
    // Se os dados nao foram submetidos
    if (!$dados) {
        formulario_nova_senha();
    } elseif (!gerar_nova_senha($dados, $erros, $avisos)) {
        mensagem::erro($erros);
        formulario_nova_senha($dados);
    } else {
        mensagem::aviso($avisos);
        formulario_nova_senha();
    }
}


//
//     Iniciar os dados de sessao de erros
//
function iniciar_erros() {
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
            echo '<p>Voc&ecirc; preencheu o formul&aacute;rio incorretamente mais de '.MAX_ERROS.' vezes.</p>';
            echo '<p>O sistema bloqueou o seu acesso a esta ferramenta por '.TEMPO_ESPERA.' segundos por uma quest&atilde;o de seguran&ccedil;a.</p>';
            echo '<p>Por favor, aguarde. Caso enfrente dificuldades, entre em contato com os respons&aacute;veis pelo sistema.</p>';
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
//     Atualiza a senha e envia por e-mail
//
function gerar_nova_senha($dados, &$erros, &$avisos) {
// stdClass $dados: dados submetidos
// Array[String] $erros: vetor de erros
// Array[String] $avisos: vetor de avisos
//
    $erros = array();
    $avisos = array();
    if (!captcha::validar($dados->captcha)) {
        $erros[] = 'Texto da imagem n&atilde;o corresponde';
        incrementar_numero_erros();
        return;
    }
    if (!isset($dados->id)) {
        $erros[] = 'Erro interno do formul&aacute;rio (ID n&atilde;o enviado)';
        return false;
    }
    $id = $dados->id;
    $id_login = $id.'l';
    $id_email = $id.'e';

    if (!isset($dados->$id_login) || !isset($dados->$id_email)) {
        $erros[] = 'Erro interno do formul&aacute;rio (login ou e-mail n&atilde;o enviado)';
        return false;
    }

    $login = $dados->$id_login;
    $email = $dados->$id_email;

    if ($login === '') {
        $erros[] = 'Faltou preencher o login';
    }
    if ($email === '') {
        $erros[] = 'Faltou preencher o e-mail';
    }
    if (!empty($erros)) {
        return false;
    }

    // Checar se o usuario existe
    $campos = array('login', 'email', 'senha', 'cancelado');
    $u = new usuario('login', $login);

    // Se nao existe
    if (!$u->existe()) {
        $erros[] = 'N&atilde;o existe usu&aacute;rio com login "'.texto::codificar($login).'"';
        incrementar_numero_erros();
        return false;

    // Se foi cancelado
    } elseif ($u->cancelado) {
        $erros[] = 'O usu&aacute;rio com login "'.texto::codificar($login).'" est&aacute; cancelado e n&atilde;o pode acessar o sistema';
        return false;
    }

    // Se existe, mas o e-mail nao confere
    if ($u->email != $email) {
        $erros[] = 'O e-mail informado n&atilde;o corresponde ao cadastrado no sistema';
        incrementar_numero_erros();
        return false;
    }

    // Se esta' tudo OK
    $s = senha::gerar(USUARIO_TAM_SENHA, true);
    $u->senha = $s;

    $r = objeto::inicio_transacao();
    if ($u->salvar()) {
        if ($u->enviar_senha($s, 1)) {
            $avisos[] = 'Senha alterada e enviada para o e-mail "'.$u->exibir('email').'"';
        } else {
            $erros = $u->get_erros();
            $r = false;
        }
    } else {
        $erros[] = 'Erro ao salvar nova senha';
        $erros[] = $u->get_erros();
        $r = false;
    }
    $r = objeto::fim_transacao(!$r) && $r;

    // Sobrescrever senha da memoria
    $s = str_repeat(mt_rand(0, 9), USUARIO_TAM_SENHA);
    unset($s);

    if (!$r) {
        $erros[] = 'Alguma opera&ccedil;&atilde;o falhou, ent&atilde;o a senha n&atilde;o foi alterada';
    }

    return $r;
}


//
//     Imprime o formulario de nova senha
//
function formulario_nova_senha($dados = null) {
// stdClass $dados: dados submetidos
//
    global $CFG;
    $action = $CFG->site;

    if (isset($dados->id)) {
        $id = $dados->id;
    } else {
        $id = md5(time().mt_rand(1000, 9999));
    }
    $id_login = $id.'l';
    $id_email = $id.'e';

    $padrao = array(
        $id_login => '',
        $id_email => '',
        'id'      => $id
    );
    $dados = formulario::montar_dados($padrao, $dados);
    
    $email_padrao = texto::proteger_email($CFG->email_padrao);

    $form = new formulario($action, 'nova_senha');
    $form->campo_aviso('<p>Preencha o campo abaixo com o seu login e e-mail para gerar uma senha aleat&oacute;ria e envi&aacute;-la para seu e-mail. Caso n&atilde;o tenha e-mail ou n&atilde;o tenha mais acesso ao ele, entre em contato com os respons&aacute;veis pelo sistema pelo e-mail "'.$email_padrao.'", para trocar a senha manualmente.</p>');
    $form->campo_hidden('id', $dados->id);
    $form->campo_text($id_login, $id_login, $dados->$id_login, 255, 30, 'Login');
    $form->campo_text($id_email, $id_email, $dados->$id_email, 255, 30, 'E-mail');
    $form->campo_captcha();
    $form->campo_submit('enviar', 'enviar', 'Enviar');
    $form->imprimir();
}


//
//     Limpa os dados recebidos do formulario
//
function sanitizar(&$dados) {
// stdClass $dados: dados a serem sanitizados
//
    if (isset($dados->id)) {
        $dados->id = formulario::filtrar('string', $dados->id);
        $id_login = $dados->id.'l';
        $id_email = $dados->id.'e';

        if (isset($dados->$id_login)) {
            $dados->$id_login = formulario::filtrar('string', $dados->$id_login);
        }
        if (isset($dados->$id_email)) {
            $dados->$id_email = formulario::filtrar('string', $dados->$id_email);
        }
    }
}
