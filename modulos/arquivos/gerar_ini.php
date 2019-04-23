<?php
//
// SIMP
// Descricao: Arquivo para gerar o arquivo INI de instalacao dos arquivos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 22/01/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');

$ini = objeto::get_objeto('arquivo')->get_ini();

// Enviar documento
$opcoes_http = array(
    'arquivo'     => 'arquivo.ini',
    'compactacao' => true,
    'disposition' => 'attachment'
);
http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);
echo $ini;
exit(0);
