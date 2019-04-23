<?php
//
// SIMP
// Descricao: Folha de estilos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.7
// Data: 06/05/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

// Obter nome do tema
$tema = basename(dirname(__FILE__));

// Armazenar em buffer
setlocale(LC_ALL, 'C');

$ultima_mudanca = max(
    array(
        filemtime($CFG->dirlayout.$tema.'/gecko.css.php'),
        filemtime($CFG->dirlayout.$tema.'/webkit.css.php'),
        filemtime($CFG->dirlayout.$tema.'/presto.css.php'),
        filemtime($CFG->dirlayout.$tema.'/ie6.css.php'),
        filemtime($CFG->dirlayout.$tema.'/formulario.css.php'),
        getlastmod()
    )
);

// Enviar documento
$opcoes_http = array(
    'arquivo'        => $tema.'.css',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => $ultima_mudanca
);
http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);

echo "@charset \"{$CFG->charset}\";\n";

// Importar layout do usuario e layout basicas
include_once($CFG->dirlayout.'geral/index.css.php');
include_once($CFG->dirlayout.$tema.'/basico.css.php');
?>

/*************************
 * ATRIBUTOS ESTRUTURAIS *
 *************************/
#titulo_pagina,
#navegacao,
#rodape,
#conteudo {
  clear: both;
  display: block;
  margin: 0px;
}

/*
 * TITULO
 */
#titulo_pagina {
  max-height: 75px;
  overflow: hidden;
  padding: 0px;
  white-space: nowrap;
  width: 100%;
}
#titulo_pagina h1 {
  background: #FFFFFF url(<?php echo $CFG->wwwlayout.$tema ?>/imgs/titulo.png) 10% 50% no-repeat;
  font-size: 50px;
  margin: 0px;
  padding: 0px;
}

#titulo_pagina h1,
#titulo_pagina em {
  display: block;
  float: left;
  height: 75px;
  line-height: 75px;
  padding-left: 160px;
  position: relative;
}
#titulo_pagina em {
  font-size: 16px;
  font-weight: bolder;
  padding-left: 20px;
  white-space: nowrap;
}

#titulo_pagina h1 a {
  text-decoration: none;
}

/*
 * NAVEGACAO
 */
#navegacao {
  border-top: 1px solid #C3D9FF;
  border-bottom: 1px solid #C3D9FF;
  color: #303030;
  display: block;
  font-size: 14px;
  font-weight: bolder;
  margin-bottom: 10px;
  padding: 2px 20px;
}

#navegacao * {
  line-height: 22px;
  padding: 0 .4em;
  white-space: nowrap;
}

/*
 * CONTEUDO
 */
#conteudo {
  float: left;
  padding: 0px;
  margin-bottom: 20px;
  width: 100%;
}

#conteudo_principal {
  float: left;
  width: 80%;
}
#conteudo_secundario {
  float: left;
  width: 20%;
}


/*
 * MENU E CENTRO
 */
#centro,
#menu {
  margin: 0;
}

#centro {
  border-radius: 1em;
  border-top-left-radius: 0;
  background-color: #C3D9FF;
  min-height: 300px;
  padding: 0px 10px 5px 10px;
  width: 95%;
}
#pagina_login #centro {
  background-color: #FFFFFF;
}

#menu h2 {
  font-variant: small-caps;
}

#menu {
  float: right;
  background: #FFFFFF;
  width: 90%;
}

#menu strong {
  background-color: #C3D9FF;
  display: block;
  font-variant: small-caps;
  text-align: center;
  border-top-left-radius: 1em;
  border-bottom-left-radius: 1em;
}

#menu ul {
  list-style-type: none;
  list-style-image: none;
  margin: 0px;
  margin-bottom: 5px;
  padding: 0;
}

#menu strong,
#menu ul li {
  padding: .2em 1em;
}

#menu ul li {
  margin-top: 2px;
  padding: .1em .5em;
}

#menu ul li a {
  display: inline;
  font-weight: bold;
  min-height: 18px;
}
#menu ul li:hover {
  background-color: #C3D9FF;
}

#menu ul + div,
#menu p + div {
  font-size: 80%;
  text-align: center;
}
#menu ul + div p {
  display: block;
  margin: 5px;
}
#menu ul + div p a {
  display: inline;
  line-height: 20px;
  padding: 2px 0;
}

#menu ul + div p + p {
  border: 1px solid #CCCCCC;
  font-style: normal;
  padding: 2px;
}

#menu #login_usuario {
  background: url(<?php echo icone::endereco('menu_usuario') ?>) 0% 60% no-repeat;
  padding-left: 17px;
}

#menu #opcoes {
  background: url(<?php echo icone::endereco('menu_opcoes') ?>) 0% 60% no-repeat;
  padding-left: 20px;
}

#menu #ajuda {
  background: url(<?php echo icone::endereco('menu_ajuda') ?>) 0% 60% no-repeat;
  padding-left: 20px;
}

#menu #saida {
  background: url(<?php echo icone::endereco('menu_sair') ?>) 0% 60% no-repeat;
  padding-left: 20px;
}

#aviso_sair {
  clear: both;
  background-color: #FFFFDD;
  border: 1px solid #444400;
  font-size: .7em;
  font-weight: bolder;
  margin: 2em 1em 1em 1em;
  padding: 1em;
  border-radius: 1em;
}

/*
 * RODAPE
 */
#rodape {
  clear: both;
  color: #606060;
  font-size: 70%;
  letter-spacing: 1px;
  margin-top: 1em;
  margin-bottom: 1em;
  text-align: center;
  width: 100%;
}

#rodape address {
  display: inline;
}

#rodape div + p {
  font-size: 90%;
  font-variant: small-caps;
}

/*
 * ELEMENTOS INVISIVEIS
 */
#container > hr,
#conteudo > hr,
#navegacao strong,
#menu h2,
#menu ul li span,
#rodape h2,
#rodape #voltar_topo {
  display: none;
}


/*
 * CURSORES
 */
#titulo_pagina em,
#menu h2,
#menu strong,
#menu ul + div,
#menu p + div,
#navegacao h2,
#navegacao strong,
#navegacao span,
#navegacao em,
#rodape {
  cursor: default;
}

<?php

// Estilos Especificos
switch ($CFG->agent->engine) {
case 'gecko':
    include_once($CFG->dirlayout.$tema.'/gecko.css.php');
    break;
case 'webkit':
    include_once($CFG->dirlayout.$tema.'/webkit.css.php');
    break;
case 'presto':
    include_once($CFG->dirlayout.$tema.'/presto.css.php');
    break;
case 'mshtml':
    if ($CFG->agent->navegador == 'Internet Explorer' && (int)$CFG->agent->versao_navegador < 7) {
        include_once($CFG->dirlayout.$tema.'/ie6.css.php');
    }
    break;
}

// Estilos de formularios
include_once($CFG->dirlayout.$tema.'/formulario.css.php');

exit(0);