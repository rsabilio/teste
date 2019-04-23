<?php
//
// SIMP
// Descricao: Popup de ajuda
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.0
// Data: 27/06/2011
// Modificado: 27/06/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

$modulo = modulo::get_modulo(__FILE__);

header('Content-type: '.$CFG->content.'; charset='.$CFG->charset);
echo <<<HTML
<?xml version="1.0" encoding="{$CFG->charset}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "{$CFG->wwwroot}dtd/xhtml1-20020801/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$CFG->lingua}" dir="ltr">
<head>
<title>Ajuda</title>
<script type="text/javascript" src="{$CFG->wwwmods}{$modulo}/popup.js.php"></script>
</head>
<body>
<h1 id="titulo"></h1>
<div id="conteudo" style="text-align: justify;"></div>
</body>
</html>
HTML;
