<?php
//
// SIMP
// Descricao: Arquivo para gerar o arquivo INI de instalacao das permissoes de determinado grupo
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 22/01/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');

$grupo = util::get_entidade('grupo');
$ini = objeto::get_objeto('permissao')->get_ini($grupo, $nome_arquivo);

$opcoes_http = array(
    'arquivo' => $nome_arquivo, 
    'disposition' => 'attachment',
    'compactacao' => true
);

http::cabecalho('text/plain; charset='.$CFG->charset, $opcoes_http);
echo $ini;
exit(0);
