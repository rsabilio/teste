<?php
//
// SIMP
// Descricao: Folha de estilos especificos de navegadores da familia do Mozilla
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.6
// Data: 15/06/2007
// Modificado: 18/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
?>
/*****************
 * ESTILOS GECKO *
 *****************/

a {
  -moz-transition: color 2s;
}
a:hover {
  -moz-transition: color 1s;
}

a img {
  -moz-transition: opacity 2s, -moz-opacity 2s;
}
a:hover img {
  -moz-transition: opacity 1s, -moz-opacity 1s;
}

<?php
if ($CFG->agent->navegador == 'Firefox' && (int)$CFG->agent->versao_navegador < 3):
echo <<<CSS
#titulo_pagina h1:before {
  color: #667788;
  content: '<?php echo $CFG->titulo ?>';
  display: block;
  margin: 0em 0em -1.55em 0.05em;
  padding: 0px;
}

#titulo_pagina h1:hover:before {
  color: #668899;
}

#titulo_pagina em:before {
  display: block;
  margin: 0em 0em -4.78em 0.15em;
  padding: 0;
  color: #667788;
  content: '<?php echo $CFG->descricao ?>';
}
CSS;
endif;
?>

#navegacao + hr { clear: both; }

#menu {
  -moz-box-shadow: 1px 1px 10px #AAAAAA;
  -moz-border-radius-topright: 20px;
  -moz-border-radius-bottomright: 20px;
<?php
if ($CFG->transparencia):
echo <<<CSS
  -moz-opacity: {$CFG->transparencia};
CSS;
endif;
?>
}

<?php
if ($CFG->transparencia):
echo <<<CSS
#menu:hover,
#menu:focus,
#menu:active {
  -moz-opacity: {$CFG->opaco};
}
CSS;
endif;
?>

#menu h2 + strong {
  -moz-border-radius-topright: 25px;
}

#aviso_sair {
  -moz-border-radius: 1em;
  -moz-box-shadow: 0px 0px 10px 2px #FF0000;
}

/* Botao de ajuda */
.bloco_ajuda_aberto a.ajuda,
.bloco_ajuda_fechado a.ajuda {
  -moz-border-radius: .5em;
}

/* Caixa de opcoes */
.caixa {
  -moz-border-radius-topleft: 18px;
  -moz-border-radius-topright: 5px;
  -moz-border-radius-bottomleft: 5px;
  -moz-border-radius-bottomright: 5px;
}

.caixa h2 {
  -moz-border-radius-topleft: 13px;
  -moz-border-radius-topright: 5px;
}

.caixa h2 .bt_fechar {
  -moz-border-radius: 5px;
}

div.carregando {
  -moz-border-radius-bottomright: 1em;
}

.opcoes {
  -moz-border-radius: 2em;
}

.abas .nomes_abas a {
  -moz-border-radius-topleft: 0.7em;
  -moz-border-radius-topright: 0.7em;
}

div.erro,
div.aviso {
  -moz-border-radius-bottomright: 25px;
  -moz-border-radius-bottomleft: 25px;
  -moz-box-shadow: 1px 1px 7px #808080;
}

.dados {
  -moz-box-shadow: 1px 1px 10px #AAAAAA;
}

.dados fieldset {
  -moz-border-radius: 20px;
}
.dados fieldset legend {
  -moz-border-radius: 15px;
}

div.opcoes {
  -moz-box-shadow: 1px 1px 10px #AAAAAA;
}

.lista .opcoes {
  -moz-box-shadow: none;
}

/* Formularios */
.formulario {
  min-height: 100px;
  -moz-border-radius-bottomleft: 20px;
  -moz-border-radius-bottomright: 20px;
  -moz-box-shadow: 1px 1px 10px #AAAAAA;
}
.formulario fieldset {
  -moz-border-radius: 20px;
}
.formulario fieldset legend {
  -moz-border-radius: 15px;
}
.formulario input.botao {
  -moz-border-radius: 20px;
  -moz-transition: background-color 2s, color 2s;
}
.formulario input.botao:hover {
  -moz-transition: background-color 1s, color 1s;
}

.formulario label {
  -moz-transition: color 2s;
}
.formulario label:hover {
  -moz-transition: color 1s;
}

/* Tabelas */
table.tabela {
  -moz-box-shadow: 1px 1px 10px #AAAAAA;
}

table.tabela tr { 
  -moz-transition: background-color 2s;
}

table.tabela tr:hover {
  -moz-transition: background-color 1s;
}
