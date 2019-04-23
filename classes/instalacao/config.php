<?php
//
// SIMP
// Descricao: Script de Instalacao da Configuracao Basica
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.13
// Data: 10/09/2007
// Modificado: 04/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('INSTALACAO_CONFIG_TITULO',     'SIMP');
define('INSTALACAO_CONFIG_DESCRICAO',  'Framework');
define('INSTALACAO_CONFIG_AUTOR',      'Rubens Takiguti Ribeiro');
define('INSTALACAO_CONFIG_LINK_AUTOR', 'http://www.tecnolivre.com.br/');
define('INSTALACAO_CONFIG_EMAIL',      isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : 'root@localhost');
define('INSTALACAO_CONFIG_LINGUA',     'pt-br');
define('INSTALACAO_CONFIG_LOCALIDADE', (strtolower(substr(PHP_OS, 0, 3)) == 'win') ? 'Portuguese_Brazil.1252' : 'pt_BR'.($CFG->utf8 ? '.UTF-8' : ''));
define('INSTALACAO_CONFIG_CIDADE',     'Lavras');
define('INSTALACAO_CONFIG_ESTADO',     'MG');
define('INSTALACAO_CONFIG_PALAVRAS',   'simp');
define('INSTALACAO_CONFIG_PREASSUNTO', '['.strtoupper($CFG->sistema).']');


//
//     Instala a configuracao padrao
//
function instalar_config(&$erros) {
// Array[String] $erros: erros ocorridos
//
    $r = true;

    $c = new config('', 1, true, false);
    if ($c->existe()) {
        return true;
    }
    $c->limpar_objeto();

    $c->ajax = 1;
    $c->gd = extension_loaded('gd');
    $c->transparencia = 0.7;
    $c->opaco = 0.9;
    $c->autenticacao = 'simp';
    $c->fechado = false;
    $c->motivo_fechado = '';
    $c->formato_data = '%d/%m/%Y';
    $c->formato_hora = '%H:%M:%S';
    $c->tipo_email = CONFIG_EMAIL_PADRAO;
    $c->smtp_host = '';
    $c->smtp_porta = 25;
    $c->smtp_usuario = '';
    $c->smtp_senha = '';
    $c->titulo = INSTALACAO_CONFIG_TITULO;
    $c->descricao = INSTALACAO_CONFIG_DESCRICAO;
    $c->autor = INSTALACAO_CONFIG_AUTOR;
    $c->link_autor = INSTALACAO_CONFIG_LINK_AUTOR;
    $c->preassunto = INSTALACAO_CONFIG_PREASSUNTO;
    $c->email_padrao = INSTALACAO_CONFIG_EMAIL;
    $c->lingua = INSTALACAO_CONFIG_LINGUA;
    $c->localidade = INSTALACAO_CONFIG_LOCALIDADE;
    $c->cidade = INSTALACAO_CONFIG_CIDADE;
    $c->estado = INSTALACAO_CONFIG_ESTADO;
    $c->palavras = INSTALACAO_CONFIG_PALAVRAS;

    if (!$c->salvar()) {
        $r = false;
        $erros[] = 'Erro ao instalar configura&ccedil;&otilde;es:';
        $erros[] = $c->get_erros();
    }

    return $r;
}
