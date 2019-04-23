<?php
//
// SIMP
// Descricao: Folha de estilos especificos de navegadores da familia do Chrome
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.0
// Data: 18/04/2011
// Modificado: 18/04/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
?>
/******************
 * ESTILOS CHROME *
 ******************/

#navegacao + hr { clear: both; }

/* Botao de ajuda */
.bloco_ajuda_aberto a.ajuda,
.bloco_ajuda_fechado a.ajuda {
  -webkit-border-radius: 10px;
}

/* Caixa de opcoes */
.caixa {
  -webkit-border-top-left-radius: 18px;
  -webkit-border-top-right-radius: 5px;
  -webkit-border-bottom-left-radius: 5px;
  -webkit-border-bottom-right-radius: 5px;
}

.caixa h2 {
  -webkit-border-top-left-radius: 13px;
  -webkit-border-top-right-radius: 5px;
}

.caixa h2 .bt_fechar {
  -webkit-border-radius: 5px;
}

div.carregando {
  -webkit-border-bottom-left-radius: 10px;
  -webkit-border-bottom-right-radius: 10px;
}

.opcoes {
  -webkit-border-radius: 2em;
}

.abas .nomes_abas a {
  -webkit-border-top-left-radius: 0.7em;
  -webkit-border-top-right-radius: 0.7em;
}

div.erro,
div.aviso {
  -webkit-border-bottom-left-radius: 25px;
  -webkit-border-bottom-right-radius: 25px;
}

#centro {
  -webkit-border-radius: 1em;
  -webkit-border-top-left-radius: 0;
}

#menu strong,
#menu ul li:hover {
  -webkit-border-top-left-radius: .5em;
  -webkit-border-bottom-left-radius: .5em;
  position: relative;
  left: 1px;
}

#menu ul + div {
  -webkit-border-bottom-left-radius: 1em;
  background-color: #909090;
  border-left: 1px solid #CCCCCC;
  border-bottom: 1px solid #CCCCCC;
  float: none;
  height: .5em;
  right: 0px;
  overflow: hidden;
  opacity: 0.9;
  position: fixed;
  text-align: right;
  top: 0px;
}

#menu ul + div > p + p {
  border: 0;
}

#menu ul + div:hover {
  background-color: #FFFFFF;
  height: auto;
}

#menu:after {
  color: #707070;
  content: '+ opções no canto superior direito';
  cursor: default;
  font-size: .6em;
}

/* formulario */
.formulario fieldset {
  -webkit-border-radius: 10px;
}
