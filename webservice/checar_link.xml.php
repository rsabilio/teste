<?php
//
// SIMP
// Descricao: Arquivo que retorna se o link e' valido ou nao
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.9
// Data: 27/12/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

// Retornos:
define('TIPO_LINK_VALIDO',        0);
define('TIPO_LINK_INDETERMINADO', 1);
define('TIPO_LINK_INVALIDO',      2);

ini_set('user_agent', $_SERVER['HTTP_USER_AGENT']);

// Obter host e link
$resultado = isset($_GET['link']) ? get_resultado($_GET['link']) : TIPO_LINK_INDETERMINADO;

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<resultado>{$resultado}</resultado>
XML;

$opcoes_http = array(
    'arquivo' => 'link-'.md5($_GET['link']).'.xml',
    'compactacao' => true
);

http::cabecalho('text/xml; charset=UTF-8', $opcoes_http);
echo $xml;
exit(0);


//
//     Determina se um link esta quebrado ou nao
//
function get_resultado($url) {
// String $url: endereco a ser testado
//
    $vt = parse_url($url);
    $host  = isset($vt['host']) ? $vt['host'] : $_SERVER['HTTP_HOST'];
    $path  = isset($vt['path']) ? $vt['path'] : '/';
    $porta = isset($vt['port']) ? $vt['port'] : 80;

    $resultado = TIPO_LINK_INDETERMINADO;

    // Pedir retorno de cabecalho HTTP via metodo HEAD
    $head = http::enviar('HEAD', $host, $porta, $path);
    if (!$head) {
        return TIPO_LINK_INVALIDO;
    }
    $cod_retorno = $head->vt_header_resposta['resultado']->cod;

    if (is_numeric($cod_retorno)) {
        if ($cod_retorno == 301 ||
            $cod_retorno == 302 ||
            ($cod_retorno >= 200 && $cod_retorno < 300)) {
            $resultado = TIPO_LINK_VALIDO;
        } elseif ($cod_retorno == 404) {
            $resultado = TIPO_LINK_INVALIDO;
            salvar_link_quebrado($url);
        }
    }
    return $resultado;
}


//
//     Salva o link quebrado em arquivo texto
//
function salvar_link_quebrado($link) {
// String $link: url do link quebrado
//
    global $CFG;

    $link = trim($link);

    $arquivo = $CFG->dirarquivos.'links_quebrados.txt';

    // Se o arquivo nao existe
    if (!is_file($arquivo)) {
        file_put_contents($arquivo, $link."\n", LOCK_EX);
        return;
    }

    // Se o arquivo existe

    // Checar se o link ja foi cadastrado no arquivo
    $f = fopen($arquivo, 'r+');
    if (!$f) {
        return false;
    }

    // Travar
    flock(LOCK_EX);

    // Buscar link
    while (!feof($f)) {
        $l = trim(fgets($f, 256));

        // Se ja existe: ignorar
        if ($l == $link) {
            return;
        }
    }

    // Se nao existe: salvar no arquivo
    fwrite($f, $link."\n");

    // Destravar e fechar
    flock(LOCK_UN);
    fclose($f);
}
