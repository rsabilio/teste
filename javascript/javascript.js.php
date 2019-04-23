<?php
//
// SIMP
// Descricao: Arquivo que mescla todos arquivos js em um compactado
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.18
// Data: 22/01/2008
// Modificado: 04/10/2012
// License: LICENSE.TXT
// Copyright (C) 2008  Rubens Takiguti Ribeiro
//
require_once('../config.php');

// Configuracoes gerais do JavaScript
$CFG_JS = new stdClass();
$CFG_JS->script_local    = $CFG->wwwroot.'javascript/javascript.js.php'.(isset($_GET['v']) ? '?v='.$_GET['v'] : '');
$CFG_JS->localhost       = $CFG->localhost;
$CFG_JS->wwwroot         = $CFG->wwwroot;
$CFG_JS->dominio         = $CFG->dominio;
$CFG_JS->dominio_cookies = $CFG->dominio_cookies ? $CFG->dominio_cookies : false;
$CFG_JS->path            = $CFG->path;
$CFG_JS->navegador       = strtolower($CFG->agent->navegador);
$CFG_JS->engine          = $CFG->agent->engine;

// Expressoes regulares
$CFG_JS->exp = array();

$localidades = array(
   'en_US',
   'pt_BR'
);
foreach ($localidades as $localidade) {
    setlocale(LC_ALL, $localidade);
    $localidade = strtolower($localidade);

    $conv = validacao::get_convencoes_localidade();
    $positivo = preg_quote($conv['positive_sign']);
    $negativo = preg_quote($conv['negative_sign']);
    $sinal    = preg_quote($conv['positive_sign'].$conv['negative_sign']);
    $milhar   = preg_quote($conv['thousands_sep']);
    $decimal  = preg_quote($conv['decimal_point']);

    $CFG_JS->exp[$localidade] = array();

    // Digitos
    $CFG_JS->exp[$localidade]['digitos'] = array(
        'exemplo' => '000 ou 001 ou 002 ...',
        'exp'     => '/^[0-9]*$/',
        'fim'     => '/^[0-9]*$/'
    );

    // Letras
    $CFG_JS->exp[$localidade]['letras'] = array(
        'exemplo' => 'abc',
        'exp'     => '/^[A-Za-z]*$/',
        'fim'     => '/^[A-Za-z]*$/'
    );

    // Moeda
    $exemplos = array(
        texto::numero(0, 2),
        texto::numero(1, 2),
        texto::numero(-1, 2),
        texto::numero(-0.12, 2),
        texto::numero(1000.23, 2),
        texto::numero(-1000.30, 2)
    );
    if ($conv['thousands_sep'] === '') {
        $CFG_JS->exp[$localidade]['moeda'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^['.$sinal.']?(0['.$decimal.']?[0-9]{0,2}|[1-9]{1}[0-9]*['.$decimal.']?[0-9]{0,2})?$/',
            'fim'     => '/^(0|['.$sinal.']?0['.$decimal.'][0-9]{1,2}|['.$sinal.']?[1-9]{1}[0-9]*(['.$decimal.']{1}[0-9]{1,2})?)$/'
        );
    } else {
        $CFG_JS->exp[$localidade]['moeda'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^['.$sinal.']?(0['.$decimal.']?[0-9]{0,2}|[1-9]{1}[0-9'.$milhar.']*['.$decimal.']?[0-9]{0,2})?$/',
            'fim'     => '/^(['.$sinal.']?0(['.$decimal.'][0-9]{1,2})?|['.$sinal.']?0['.$decimal.'][0-9]{1,2}|['.$sinal.']?[1-9]{1}[0-9]{0,2}(['.$milhar.'][0-9]{3})*(['.$decimal.']{1}[0-9]{1,2})?)$/'
        );
    }

    // Int
    $exemplos = array(
        texto::numero(0, 0),
        texto::numero(1000, 0),
        texto::numero(-1000, 0)
    );
    if ($conv['thousands_sep'] === '') {
        $CFG_JS->exp[$localidade]['int'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^(['.$sinal.']?([0-9]*)?)$/',
            'fim'     => '/^(0|['.$sinal.']?[1-9]{1}[0-9]*)$/'
        );
    } else {
        $CFG_JS->exp[$localidade]['int'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^(['.$sinal.']?([0-9'.$milhar.']*)?)$/',
            'fim'     => '/^(0|['.$sinal.']?[1-9]{1}[0-9]{0,2}(['.$milhar.'][0-9]{3})*)$/'
        );
    }

    // Float
    $exemplos = array(
        texto::numero(0, 0),
        texto::numero(-0.123, 3),
        texto::numero(1000.1, 1),
        texto::numero(-1000.1, 1)
    );
    if ($conv['thousands_sep'] === '') {
        $CFG_JS->exp[$localidade]['float'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^['.$sinal.']?([0-9]*['.$decimal.']?[0-9]*)?$/',
            'fim'     => '/^(0|['.$sinal.']?0['.$decimal.'][0-9]+|['.$sinal.']?[1-9]{1}[0-9]*(['.$decimal.']{1}[0-9]+)?)$/'
        );
    } else {
        $CFG_JS->exp[$localidade]['float'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^['.$sinal.']?([0-9'.$milhar.']*['.$decimal.']?[0-9]*)?$/',
            'fim'     => '/^(0|['.$sinal.']?0['.$decimal.'][0-9]+|['.$sinal.']?[1-9]{1}[0-9]{0,2}(['.$milhar.'][0-9]{3})*(['.$decimal.']{1}[0-9]+)?)$/'
        );
    }

    // Unsigned Int
    $exemplos = array(
        texto::numero(0, 0),
        texto::numero(1000, 0),
    );
    $exp_positivo = $positivo ? '['.$positivo.']?' : '';
    if ($conv['thousands_sep'] === '') {
        $CFG_JS->exp[$localidade]['uint'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^('.$exp_positivo.'([0-9]*)?)$/',
            'fim'     => '/^(0|'.$exp_positivo.'[1-9]{1}[0-9]*)$/'
        );
    } else {
        $CFG_JS->exp[$localidade]['uint'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^(0|'.$exp_positivo.'([0-9'.$milhar.']*)?)$/',
            'fim'     => '/^(0|'.$exp_positivo.'[1-9]{1}[0-9]{0,2}(['.$milhar.'][0-9]{3})*)$/'
        );
    }

    // Unsigned Float
    $exemplos = array(
        texto::numero(0, 0),
        texto::numero(0.1, 1),
        texto::numero(1000, 0),
        texto::numero(1000.1, 1)
    );
    $exp_positivo = $positivo ? '['.$positivo.']?' : '';
    if ($conv['thousands_sep'] === '') {
        $CFG_JS->exp[$localidade]['ufloat'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^'.$exp_positivo.'([0-9]*['.$decimal.']?[0-9]*)?$/',
            'fim'     => '/^(0|'.$exp_positivo.'0['.$decimal.'][0-9]+|'.$exp_positivo.'[1-9]{1}[0-9]*(['.$decimal.']{1}[0-9]+)?)$/'
        );
    } else {
        $CFG_JS->exp[$localidade]['ufloat'] = array(
            'exemplo' => implode(' ou ', $exemplos),
            'exp'     => '/^'.$exp_positivo.'([0-9'.$milhar.']*['.$decimal.']?[0-9]*)?$/',
            'fim'     => '/^(0|'.$exp_positivo.'0['.$decimal.'][0-9]+|'.$exp_positivo.'[1-9]{1}[0-9]{0,2}(['.$milhar.'][0-9]{3})*(['.$decimal.']{1}[0-9]+)?)$/'
        );
    }

}
setlocale(LC_ALL, 'C');
$CFG_JS = json_encode($CFG_JS);
$buffer = "var CFG={$CFG_JS}; var GLB = {};";

// Arquivos JavaScript
$diretorio = $CFG->dirroot.'javascript/';
$dir = opendir($diretorio);
if ($dir) {
    while (($item = readdir($dir)) !== false) {
        if (preg_match('/\.js$/', $item)) {
            $buffer .= trim(file_get_contents($diretorio.$item));
            $vt_last[] = filemtime($diretorio.$item);
        }
    }
    closedir($dir);
} else {
    $buffer .= 'window.onload = function() { window.alert("Erro ao gerar JavaScript"); }';
}

// Data de ultima modificacao
$last = max($vt_last);

// Exibir documento
$opcoes = array(
    'arquivo'        => 'javascript.js',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => $last
);

http::cabecalho('text/javascript; charset='.$CFG->charset, $opcoes);
echo $buffer;
exit(0);
