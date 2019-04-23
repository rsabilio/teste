<?php
//
// SIMP
// Descricao: Folha de estilos basica
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.32
// Data: 03/03/2007
// Modificado: 10/06/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
if (!isset($CFG)) { exit(0); }

// Obter nome do tema
$tema = basename(dirname(__FILE__));

setlocale(LC_ALL, 'C');
?>
/********************
 * ATRIBUTOS GERAIS *
 ********************/

* {
  z-index: 1;
}

/* Body */
body {
  background-color: #FFFFFF;
  direction: ltr;
  font-family: Arial, Verdana, Helvetica, sans-serif;
  margin: 0px 0px 5px 0px;
  min-width: 780px;
}

/* Links */
a {
  color: #3377BB;
  text-decoration: none;
  transition: color 2s;
}
a:hover {
  color: #4499DD;
  cursor: pointer;
  text-decoration: underline;
  transition: color 1s;
}

a.inserir {
  background-image: url(<?php echo icone::endereco('adicionar') ?>);
  background-position: 0% 50%;
  background-repeat: no-repeat;
  padding-left: 20px !important;
}

a.importar {
  background-image: url(<?php echo icone::endereco('importar') ?>);
  background-position: 0% 50%;
  background-repeat: no-repeat;
  padding-left: 20px !important;
}

a.inconsistencia {
  background-image: url(<?php echo icone::endereco('bug') ?>);
  background-position: 0% 50%;
  background-repeat: no-repeat;
  padding-left: 20px !important;
}

a.ini {
  background-image: url(<?php echo icone::endereco('arq_ini') ?>);
  background-position: 0% 50%;
  background-repeat: no-repeat;
  padding-left: 20px !important;
}

a img {
  border: 0px;
  opacity: 0.5;
  transition: opacity 2s;
}

a:hover img {
  opacity: 1;
  transition: opacity 1s;
}

dd:active {
  color: #000099;
}


/* Linhas */
hr {
  border-top: 0px;
  border-left: 0px;
  border-right: 0px;
  margin-top: 4px;
  margin-bottom: 2px;
}

h2, hr {
  border-bottom: 2px #909ADD dotted;
  text-shadow: #AAAAAA 2px 1px 2px;
}

p:active {
  color: #0000AA;
}

/* Listas */
ul {
  list-style-image: url(<?php echo $CFG->wwwlayout.$tema ?>/imgs/li.gif);
}

/***********
 * CLASSES *
 ***********/

/* Abas */
.abas .nomes_abas a {
  border-top-left-radius: 0.7em;
  border-top-right-radius: 0.7em;
}

/* Dados */
.dados {
  background: #DDE5EE url(<?php echo $CFG->wwwlayout.$tema ?>/imgs/formulario.jpg) top left no-repeat;
  border: 1px outset #DDE5EE;
  box-shadow: 1px 1px 10px #AAAAAA;
}

.dados .titulo {
  background-color: #FFFFFF;
  border: 1px inset #FFFFFF;
}

.dados .rodape {
  border-top: 2px dotted #CCCCCC;
}

/*
 * FIELDSET
 */
.dados fieldset {
  border-radius: 20px;
  background-color: #D5DBE6;
  border: 1px outset #DDE0EE;
}

.dados fieldset legend {
  border-radius: 15px;
  background-color: #FFFFFF;
  border: 1px outset #DDE0EE;
}

/* Observacoes */
.observacao {
  background: transparent url(<?php echo $CFG->wwwlayout.$tema ?>/imgs/observacao.jpg) left top no-repeat;
}

/* Comentarios */
.comentario {
  border: 1px dotted #909ADD;
  background-color: #FFFFFF;
  color: #000055;
}

/* Comentario de Ajuda */

/* Texto da ajuda */
.bloco_ajuda_aberto blockquote,
.bloco_ajuda_fechado blockquote {
  color: #000066;
}
.bloco_ajuda_aberto blockquote {
  border: 2px dotted #909ADD;
  background-color: #FFFFFF;
}

/* Botao de ajuda */
.bloco_ajuda_aberto a.ajuda {
  background-color: #DDDDDD;
  border: 1px inset #DDDDDD;
}

.bloco_ajuda_fechado a.ajuda {
  background-color: #EEEEEE;
  border: 1px outset #EEEEEE;
}

/* Informacoes */
.info {
  border: 1px dotted #909ADD;
  color: #000066;
}

/* Lista de Opcoes */
div.opcoes {
  background-color: #DDE5EE;
  border: 1px outset #DDDDDD;
  border-radius: 1em;
}

div.opcoes span {
  color: #AAB0BB;
}

/* Lista de busca */
ul.lista_busca span {
  color: #808080;
}

/* Inativos */
.inativo {
  color: #AAAAAA;
}
