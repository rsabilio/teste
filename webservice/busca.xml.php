<?php
//
// SIMP
// Descricao: Script de busca de um campo semelhante
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.2.0.4
// Data: 30/04/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

/// Obter parametros da busca
$busca         = util::get_dado('busca', 'string');
$id_parametros = util::get_dado('id', 'string');

$parametros = formulario::get_parametros_busca($id_parametros);
$classe           = $parametros['classe'];
$campo            = $parametros['campo'];
$condicoes_extras = $parametros['condicoes'];

// Montar condicoes
$condicoes = condicao_sql::montar($campo, 'LIKE', '%'.$busca.'%');
if ($condicoes_extras) {
    $condicoes = condicao_sql::sql_and(array($condicoes, $condicoes_extras));
}


/// Obter entidade
$entidade = objeto::get_objeto($classe);


/// Consultar as entidades
$entidades = $entidade->consultar_varios_iterador($condicoes, array($campo), array($campo => true));
$mostrados = array();

/// Montar conteudo XML
$xml = "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" ?>\n".
       "<busca>\n";
foreach ($entidades as $entidade) {
    $valor = $entidade->__get($campo);
    if (isset($mostrados[$valor])) { continue; }
    $mostrados[$valor] = true;

    $xml .= "<resultado><![CDATA[{$valor}]]></resultado>\n";
}
//$xml .= '<memoria>'.memoria::formatar_bytes(memory_get_usage())."</memoria>\n";
$xml .= "</busca>";

$opcoes_http = array(
    'arquivo' => 'busca_'.$classe.'.xml',
    'compactacao' => true
);

/// Exibir possiveis itens
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $xml;
exit(0);
