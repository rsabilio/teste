<?php
//
// SIMP
// Descricao: Cria a lista de MP3 no formato XSPF (XML Shareable Playlist Format)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 17/09/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

$lista = $_GET['item'];

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
$xml .= "<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\">\n";
$xml .= "<trackList>";
foreach ($lista as $chave => $item) {
    $descricao = is_int($chave) ? 'Ajuda' : $chave;
    $item = urlencode($item);
    $xml .= "<track>";
    $xml .= "<location>{$CFG->wwwroot}webservice/fala.mp3.php?arq={$item}</location>";
    $xml .= "<annotation>{$descricao}</annotation>";
    $xml .= "</track>\n";
}
$xml .= "</trackList>\n";
$xml .= "</playlist>";

$md5 = md5(implode(':', $lista));

$opcoes_http = array(
    'arquivo' => 'playlist.'.$md5.'.xml',
    'tempo_expira' => TEMPO_EXPIRA,
    'compactacao' => true,
    'ultima_mudanca' => filemtime($CFG->dirarquivos.'fala/')
);

/// Exibir o XML
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $xml;
exit(0);

