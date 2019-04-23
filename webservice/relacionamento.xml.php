<?php
//
// SIMP
// Descricao: Arquivo para listar entidades e codigos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.22
// Data: 20/12/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');


/// Obter dados
$dados = util::get_dado('dados', 'string');
$input = util::get_dado('input', 'string', false);
list($classe, $campo_preencher, $campo_exibir, $condicoes) = explode(';', base64_decode($dados));
$condicoes = unserialize(base64_decode($condicoes));
if (is_null($condicoes) || (is_string($condicoes) && empty($condicoes))) {
    $condicoes = condicao_sql::vazia();
}
$campos = array(
    $campo_preencher,
    $campo_exibir
);
$ordem = array(
    $campo_preencher => true
);

$registros = objeto::get_objeto($classe)->consultar_varios_iterador($condicoes, $campos, $ordem);

/// Montar conteudo XML
$xml = "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" ?>\n".
       "<?xml-stylesheet type=\"text/xsl\" href=\"{$CFG->wwwroot}webservice/relacionamento.xsl.php?dados={$dados}&amp;input={$input}\" ?>\n".
       "<entidades>\n";
foreach ($registros as $registro) {
    $codigo = $registro->__get($campo_preencher);
    if (is_int($codigo) || is_float($codigo)) {
        $codigo = texto::numero($codigo);
    }
    $valor  = $registro->__get($campo_exibir);
    $xml .= "<entidade>".
            "<codigo><![CDATA[{$codigo}]]></codigo>".
            "<valor><![CDATA[{$valor}]]></valor>\n".
            "</entidade>\n";
}
//$xml .= '<memoria>'.memoria::formatar_bytes(memory_get_usage())."</memoria>\n";
$xml .= "</entidades>";

$opcoes_http = array(
    'arquivo' => 'lista_'.$classe.'.xml',
    'compactacao' => true
);

/// Exibir os possiveis itens
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $xml;
exit(0);
