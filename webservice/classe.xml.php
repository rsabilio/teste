<?php
//
// SIMP
// Descricao: Gera a descricao das classes em formato XML
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.4
// Data: 02/03/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');


/// Obter classe
$classe = util::get_dado('classe', 'string');


/// Obter entidade
$entidade = objeto::get_objeto($classe);

/// Montar conteudo XML
$xml = "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" ?>\n".
       $entidade->get_definicao_xml();

$opcoes_http = array(
    'arquivo' => $classe.'.xml',
    'tempo_expira' => 3600,
    'compactacao' => true,
    'ultima_mudanca' => filemtime($CFG->dirclasses.'entidade/'.$classe.'.class.php')
);

/// Exibir o XML
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $xml;
exit(0);
