<?php
//
// SIMP
// Descricao: Configuracoes pessoais da Folha de estilos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.1
// Data: 11/03/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

setlocale(LC_ALL, 'C');

$style = array();

if ($CFG->pessoal->tamanho != '100%') {
    $style['body, input, textarea, select']['font-size'] = $CFG->pessoal->tamanho;
}
if ($CFG->pessoal->fonte != 'padrao') {
    $style['body, input, textarea, select']['font-family'] = $CFG->pessoal->fonte;
}

if (!$CFG->pessoal->imagens) {
    $style['*']['background-image'] = 'none !important';
}
if (!$CFG->pessoal->transparencia) {
    $style['*']['opacity'] = '1 !important';
}

// Enviar documento
$opcoes_http = array(
    'arquivo'        => 'pessoal.css',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => $CFG->pessoal->modificacao
);

http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);

echo "@charset \"{$CFG->charset}\";\n";
foreach ($style as $seletor => $propriedades) {
    echo $seletor.'{';
    foreach ($propriedades as $propriedade => $valor) {
        echo $propriedade.':'.$valor.';';
    }
    echo "}\n";
}

exit(0);