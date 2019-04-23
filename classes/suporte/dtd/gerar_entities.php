<?php
//
// SIMP
// Descricao: Script para criar a tabela de entities em PHP
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 18/10/2010
// Modificado: 12/07/2011
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
$linhas1 = array();
$linhas2 = array();


$linhas1[] = "    '&amp;' => '&'";
$linhas1[] = "    '&#38;' => '&'";
$linhas1[] = "    '&#x26;' => '&'";
$linhas2[] = "    '&' => '&amp;'";

foreach (glob(dirname(__FILE__).'/*.ent') as $arq) {
    $entities = parse_entities(file_get_contents($arq));
    foreach ($entities as $nome => $entity) {
        if (preg_match('/^&#([\d]+);$/', $entity, $matches)) {
            $ord = (int)$matches[1];
        } elseif (preg_match('/^&#38;#([\d]+);$/', $entity, $matches)) {
            $ord = (int)$matches[1];
        } else {
            continue;
        }

        $ordh = dechex($ord);
        $valor = chr_utf8($ord);

        if ($valor == '&') {
            continue;
        }

        if ($valor === "'") {
            $linhas1[] = "    '&{$nome};' => \"{$valor}\"";
            $linhas1[] = "    '&#{$ord};' => \"{$valor}\"";
            $linhas1[] = "    '&#x{$ordh};' => \"{$valor}\"";
            $linhas2[] = "    \"{$valor}\" => '&{$nome};'";
        } else {
            $linhas1[] = "    '&{$nome};' => '{$valor}'";
            $linhas1[] = "    '&#{$ord};' => '{$valor}'";
            $linhas1[] = "    '&#x{$ordh};' => '{$valor}'";
            $linhas2[] = "    '{$valor}' => '&{$nome};'";
        }
    }
}
$conteudo_vetor1 = implode(",\n", $linhas1);
$conteudo_vetor2 = implode(",\n", $linhas2);

$script = basename(__FILE__);
echo <<<PHP
<?php
//@ignoredoc
// Arquivo gerado com o script {$script}
\$entities1 = array(
{$conteudo_vetor1}
);
\$entities2 = array(
{$conteudo_vetor2}
);
PHP;
exit(0);


//
//     Obtem as entities de um arquivo
//
function parse_entities($conteudo) {
// String $conteudo: conteudo do arquivo
//
    $entities = array();

    $len = strlen($conteudo);
    $i = 0;
    while ($i < $len) {
        $sub = substr($conteudo, $i);
        $matches = null;

        if (preg_match('/^<!ENTITY\h([^\h]+)[\h]*"([^"]+)">/', $sub, $matches)) {
            $entities[$matches[1]] = $matches[2];
            $i += strlen($matches[0]);
        } elseif (preg_match('/^<!--((?i:[\-][^-]|[^-])*)-->/', $sub, $matches)) {
            $i += strlen($matches[0]);
        } elseif (preg_match('/^([\040\h\v\r\f\n\t]+)/', $sub, $matches)) {
            $i += strlen($matches[0]);
        } else {
            $i += 1;
        }
    }
    return $entities;
}


//
//     Gera um caractere UTF-8 a partir do seu codigo (7 a 21 bits)
//
function chr_utf8($ord) {
// Int $ord: codigo do caractere
//
    // Tem 1 byte (7 bits significativos)
    if ($ord <= 0x7F) {
        return chr($ord);

    // Tem 2 bytes (11 bits significativos = 5 + 6)
    } elseif ($ord <= 0x7FF) {
        return chr((($ord >> 6) & 0x1F) | 0xC0).   // ((ord >> 6) & 00011111) | 11000000
               chr((   $ord     & 0x3F) | 0x80);   // (   ord     & 00111111) | 10000000

    // Tem 3 bytes (16 bits significativos = 4 + 6 + 6)
    } elseif ($ord <= 0xFFFF) {
        return chr((($ord >> 12) & 0xF)  | 0xE0).  // ((ord >> 12) & 00001111) | 11100000
               chr(( ($ord >> 6) & 0x3F) | 0x80).  // ( (ord >> 6) & 00111111) | 10000000
               chr((    $ord     & 0x3F) | 0x80);  // (    ord     & 00111111) | 10000000

    // Tem 4 bytes (21 bits significativos = 3 + 6 + 6 + 6)
    } elseif ($ord <= 0x10FFFF) {
        return chr((($ord >> 18) & 0x7)  | 0xF0).  // ((ord >> 18) & 00000111) | 11110000
               chr((($ord >> 12) & 0x3F) | 0x80).  // ((ord >> 12) & 00111111) | 10000000
               chr((($ord >> 6)  & 0x3F) | 0x80).  // ( (ord >> 6) & 00111111) | 10000000
               chr((    $ord     & 0x3F) | 0x80);  // (    ord     & 00111111) | 10000000
    }
    trigger_error('O codigo '.$ord.' nao pode ser representado em UTF-8', E_USER_NOTICE);
    return false;
}
