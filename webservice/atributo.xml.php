<?php
//
// SIMP
// Descricao: Gera a descricao de um atributo de uma classe em formato XML
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.0.3
// Data: 18/06/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');


/// Obter classe
$id_campo = util::get_dado('id', 'string');
$id_campo = base64_decode($id_campo);

list($classe, $atributo, $id_form) = explode(':', $id_campo);
$classe   = trim($classe);
$atributo = trim($atributo);
$id_form  = trim($id_form);


/// Obter entidade
$erro = false;
if (simp_autoload($classe)) {
    $entidade = objeto::get_objeto($classe);
} else {
    $erro = true;
}

$entidade->set_id_form($id_form);
$definicao = $entidade->get_info_campo($atributo);
if (!$definicao) {
    $erro = true;
}

/// Montar conteudo XML
$xml = "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" ?>\n";
if (!$erro) {
    $xml_definicao = $entidade->gerar_definicao_atributo_xml($definicao);
    $xml .= $xml_definicao;
} else {
    $xml .= '<erro>1</erro>';
}

$opcoes_http = array(
    'opcoes_http' => $classe.'-'.$atributo.'-'.$id_form.'.xml',
    'tempo_expira' => 3600,
    'compactacao' => true,
    'ultima_mudanca' => filemtime($CFG->dirclasses.'entidade/'.$classe.'.class.php')
);

/// Exibir o XML
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo $xml;
exit(0);
