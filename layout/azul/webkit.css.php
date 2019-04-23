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

#menu {
  -webkit-box-shadow: 1px 1px 10px #AAAAAA;
  -webkit-border-top-right-radius: 20px;
  -webkit-border-bottom-right-radius: 20px;
<?php
if ($CFG->transparencia):
echo <<<CSS
  -webkit-opacity: {$CFG->transparencia};
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
  -webkit-opacity: {$CFG->opaco};
}
CSS;
endif;
?>

#menu h2 + strong {
  -webkit-border-radius-topright: 25px;
}

#aviso_sair {
  -webkit-border-radius: 1em;
  -webkit-box-shadow: 0px 0px 10px 2px #FF0000;
}

/* Botao de ajuda */
.bloco_ajuda_aberto a.ajuda,
.bloco_ajuda_fechado a.ajuda {
  -webkit-border-radius: .5em;
}

/* Abas */
.abas .nomes_abas a {
  top: 1px;
}
.abas .nomes_abas a.ativa {
  top: 2px;
}

/* Caixa de opcoes */
.caixa {
  -webkit-border-radius: 5px;
  -webkit-border-top-left-radius: 18px;
}

.caixa h2 {
  -webkit-border-top-left-radius: 13px;
  -webkit-border-top-right-radius: 5px;
}

.caixa h2 .bt_fechar {
  -webkit-border-radius: 5px;
}

div.carregando {
  -webkit-border-bottom-right-radius: 1em;
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
  -webkit-border-bottom-right-radius: 25px;
  -webkit-border-bottom-left-radius: 25px;
  -webkit-box-shadow: 1px 1px 7px #808080;
}

.dados {
  -webkit-box-shadow: 1px 1px 10px #AAAAAA;
}

div.opcoes {
  -webkit-box-shadow: 1px 1px 10px #AAAAAA;
}

.lista .opcoes {
  -webkit-box-shadow: none;
}
