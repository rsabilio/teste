<?php
//
// SIMP
// Descricao: Folha de estilos mais limpa e acessivel
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.16
// Data: 08/06/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

$tema = basename(dirname(__FILE__));
setlocale(LC_ALL, 'C');

// Enviar documento
$opcoes_http = array(
    'arquivo'        => $tema.'.css',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => getlastmod()
);
http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);

echo "@charset \"{$CFG->charset}\";\n";

include_once($CFG->dirlayout.'geral/index.css.php');
include_once($CFG->dirlayout.'azul/basico.css.php');
?>

/****************
 * MEDIA SCREEN *
 ****************/

/********************
 * ATRUBUTOS GERAIS *
 ********************/
body {
  background: #FFFFFF;
  color: #000000;
  font-family: Verdana, Arial, sans-serif;
  margin: 10px;
}

h1, h2, h3, h4, h5 {
  color: #000000;
  clear: both;
  margin: 0px;
  padding: 0px;
  text-decoration: none;
}

h1 { font-size: 1.7em; }
h2 { font-size: 1.4em; }
h3 { font-size: 1.3em; }
h4 { font-size: 1.2em; }
h5 { font-size: 1.1em; }

a {
  color: #0000FF;
  text-decoration: underline;
}

a[accesskey]:after {
  color: #000033;
  content: '(' attr(accesskey) ')';
  font-size: 80%;
}

a:hover {
  background-color: #FFFFDD;
  color: #000000;
  outline: 1px solid #000000;
}

ul {
  list-style-type: square;
  list-style-image: none;
}

div.aviso,
div.erro {
  width: 30em !important;
}

/*************************
 * ATRIBUTOS ESTRUTURAIS *
 *************************/

#titulo_pagina,
#navegacao,
#menu,
#centro,
#rodape {
  background-color: #F5F8FF;
  border: 1px solid #000000;
  clear: both;
  display: block;
  margin-bottom: 0.4em;
  padding: 0.3em;
}

#titulo_pagina a {
  font-size: 1.7em;
}

#navegacao {
  margin-bottom: 0.4em;
}

#conteudo_principal,
#conteudo_secundario {
  float: left;
  position: relative;
}

#conteudo_secundario {
  width: 30%;
}

#menu {
  width: 90%;
}

#menu ul li a {
  white-space: nowrap;
}

#conteudo_principal {
  width: 70%;
}

#centro > br,
#conteudo > br,
#conteudo + br {
  clear: both;
}

#voltar_topo {
  float: right;
}

#rodape {
  font-size: 0.8em;
}

body > hr,
#conteudo > hr,
#rodape h2 {
  display: none;
}

/* FORMULARIOS */
form.formulario {
  background-color: #EEEEEE;
  border: 1px outset #EEEEEE;
  padding: 1em;
}

form.formulario div.sim_nao {
  width: 100%;
  float: none;
}
form.formulario div.sim_nao label.escolha {
  width: 100%;
  float: none;
}
