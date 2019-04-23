<?php
//
// SIMP
// Descricao: Gera a data e hora do servidor
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 04/03/2011
// Modificado: 04/10/2012
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

$data = strftime($CFG->formato_data, $CFG->time);
$hora = strftime('%H:%M', $CFG->time);

$opcoes_http = array(
    'arquivo' => $CFG->time.'.xml'
);

/// Exibir o XML
http::cabecalho('text/xml; charset='.$CFG->charset, $opcoes_http);
echo <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<tempo><data><![CDATA[{$data}]]></data><hora><![CDATA[{$hora}]]></hora></tempo>
XML;
exit(0);
