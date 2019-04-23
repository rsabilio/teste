<?php
//
// SIMP
// Descricao: Arquivo para listar entidades e codigos de maneira hierarquica
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.14
// Data: 14/02/2008
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

// Obter link do XML a ser aberto
$link  = util::get_dado('link', 'string');
$arq   = basename(parse_url($link, PHP_URL_PATH));
$input = util::get_dado('input', 'string', false);

// Obter itens a serem abertos
$itens_abertos = util::get_dado('a', 'array', false, array());
array_multisort(array_keys($itens_abertos), SORT_ASC, SORT_NUMERIC, $itens_abertos);

// Consultar o XML
$id = md5(__FILE__.':'.$link);
if (cache_arquivo::em_cache($id)) {
    $xml_str = cache_arquivo::get_valor($id);
} else {
    $xml_str = http::get_conteudo_link($link);
    if ($xml_str) {
        cache_arquivo::set_valor($id, $xml_str, 120); // 2 minutos
    }
}

if ($xml_str) {
    $xml = simplexml_load_string($xml_str);

    // Se informou os itens a serem abertos
    if (!empty($itens_abertos)) {

        $nivel = 0;
        foreach ($itens_abertos as $posicao) {
            $posicao = (int)$posicao;

            // Se existe a posicao no nivel
            if ($xml->item[$posicao]) {
                $xml = &$xml->item[$posicao];

            // Se nao existe a posicao no nivel
            } else {
                $xml_str = <<<XML
<?xml version="1.0"?>
<!-- Nenhum Item -->
<item/>
XML;
                $xml = simplexml_load_string($xml_str);
            }
        }
    }

    // Remover os filhos
    if (!$input) {
        foreach ($xml->item as $item) {
            $item->addAttribute('eh_grupo', isset($item->item) ? '1' : '0');
            unset($item->item);
        }
    }

    // Obter conteudo xml a ser exibido
    $str_xml = $xml->asXML();
} else {
    $str_xml = <<<XML
<?xml version="1.0"?>
<item />
XML;
}

$opcoes_http = array(
    'arquivo' => $arq,
    'compactacao' => true
);

/// Exibir os possiveis itens
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $str_xml;
exit(0);
