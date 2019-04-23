<?php
//
// SIMP
// Descricao: Folha de estilos para Impressao
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.16
// Data: 06/06/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

/// Armazenar em buffer
setlocale(LC_ALL, 'C');

// Enviar documento
$opcoes_http = array(
    'arquivo'        => 'print.css',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => getlastmod()
);
http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);

echo "@charset \"{$CFG->charset}\";\n";
?>
/***************
 * MEDIA PRINT *
 ***************/

* {
  color: #000000;
  background-color: #FFFFFF;
  font-family: Verdana, Arial, sans-serif;
  position: static !important;
}

h1 { font-size: 125%; }
h2 { font-size: 120%; }
h3 { font-size: 115%; }
h4 { font-size: 110%; }
h5 { font-size: 105%; }

p {
  text-align: justify;
  text-indent: 2em;
}

#titulo_pagina * {
  margin: 2px;
}

#conteudo * {
  border: 0px;
  text-decoration: none;
}

table,
table td,
table th,
.tabela,
.tabela td,
.tabela th {
  border: 1px solid #000000 !important;
  font-size: 85%;
}

#menu,
#navegacao,
#rodape,
.comentario,
.comentario_fechado,
.bloco_ajuda_aberto,
.bloco_ajuda_fechado,
form {
  display: none;
}

