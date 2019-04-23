<?php
//
// SIMP
// Descricao: Script de busca o progresso de uma operacao
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.4
// Data: 27/01/2010
// Modificado: 04/10/2012
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');


/// Obter progresso
$id = util::get_dado('id', 'string');
list($progresso, $inicio) = progresso::consultar($id);

$agora = time();

if ($inicio && $progresso) {
    $tempo_gasto    = intval($agora - $inicio);
    $tempo_estimado = intval($tempo_gasto * 100 / $progresso);
    $tempo_restante = intval($tempo_estimado - $tempo_gasto);
} else {
    $tempo_gasto    = 0;
    $tempo_estimado = '?';
    $tempo_restante = '?';
}

$texto_progresso      = texto::numero($progresso, 0);
$texto_tempo_gasto    = texto::numero($tempo_gasto, 0);
$texto_tempo_estimado = texto::numero($tempo_estimado, 0);
$texto_tempo_restante = texto::numero($tempo_restante, 0);

$xml = <<<XML
<?xml version="1.0" ?>
<progresso>
<percentual><![CDATA[{$texto_progresso}]]></percentual>
<tempo_gasto><![CDATA[{$texto_tempo_gasto}]]></tempo_gasto>
<tempo_estimado><![CDATA[{$texto_tempo_estimado}]]></tempo_estimado>
<tempo_restante><![CDATA[{$texto_tempo_restante}]]></tempo_restante>
</progresso>
XML;

$opcoes_http = array(
    'arquivo' => 'progresso.xml',
    'compactacao' => true
);

http::cabecalho('text/xml', $opcoes_http);
echo $xml;
exit(0);
