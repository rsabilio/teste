<?php
//
// SIMP
// Descricao: Folha de estilos para elementos de desenvolvimento
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 26/04/2011
// Modificado: 04/10/2012
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

/// Armazenar em buffer
setlocale(LC_ALL, 'C');

// Enviar documento
$opcoes_http = array(
    'arquivo'        => 'devel.css',
    'tempo_expira'   => TEMPO_EXPIRA,
    'compactacao'    => true,
    'ultima_mudanca' => getlastmod()
);
http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);

echo <<<CSS
@charset "{$CFG->charset}";

#bloco_devel {
  background-color: #FFFF88;
  bottom: 0;
  height: 1em;
  left: 0;
  margin: 0 1em;
  overflow: hidden;
  padding: 0.5em;
  position: fixed;
  text-align: left;
  border-top-left-radius: 1em;
  border-top-right-radius: 1em;
  box-shadow: 1px 1px 10px #CCCCCC;
  transition: height 1s ease 1s;
}

#bloco_devel:hover {
  height: 370px;
  transition: height 1s ease 0s;
}

#bloco_devel div.usuarios_favoritos {
  height: 200px;
  overflow: auto;
}
#bloco_devel div.usuarios_favoritos table {
  width: 90%;
}
#bloco_devel div.usuarios_favoritos table thead {
  display: none;
}

#bloco_desempenho {
  background-color: #88DD88;
  border: 1px solid #005500;
  margin: 1em;
  padding: .5em;
  font-size: .8em;
  clear: both;
  text-align: left;
}
#bloco_desempenho.alerta {
  background-color: #FFAAAA;
  border: 1px solid #990000;
}

#bloco_desempenho table {
  width: 100%;
  max-width: 800px;
  margin: .2em auto;
}

CSS;

if ($CFG->agent->engine == 'gecko'):
echo <<<CSS
#bloco_devel {
  -moz-border-radius-topleft: 1em;
  -moz-border-radius-topright: 1em;
  -moz-box-shadow: 1px 1px 10px #CCCCCC;
  -moz-transition: height 1s ease 1s;
}
#bloco_devel:hover {
  -moz-transition: height 1s ease 0.5s;
}

CSS;
elseif ($CFG->agent->engine == 'webkit'):
echo <<<CSS
#bloco_devel {
  -webkit-border-top-left-radius: 1em;
  -webkit-border-top-right-radius: 1em;
  -webkit-box-shadow: 1px 1px 10px #CCCCCC;
  -webkit-transition: height 1s ease 1s;
}
#bloco_devel:hover {
  -webkit-transition: height 1s ease 0.5s;
}

CSS;
endif;

exit(0);