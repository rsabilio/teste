<?php
//
// SIMP
// Descricao: Script que exibe o conteudo de um arquivo PHP
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.5
// Data: 17/01/2008
// Modificado: 04/10/2012
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Bloquear caso necessario
$modulo = modulo::get_modulo(__FILE__);
require_once($CFG->dirmods.$modulo.'/bloqueio.php');


/// Exibir pagina
$a = base64_decode($_GET['a']);
$a = realpath($a);
$nome = basename($a);

$opcoes_http = array(
    'arquivo' => $nome.'.html',
    'tempo_expira' => TEMPO_EXPIRA,
    'compactacao' => true,
    'ultima_mudanca' => filemtime($a)
);

http::cabecalho('text/html; charset=UTF-8', $opcoes_http);
echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>'.basename($a).'</title>
<style type="text/css">
<!--
* {
  margin: 0;
  padding: 0;
}
body {
  background-color: #F5F5F5;
  margin: 1em;
}
code#principal {
  font-size: 0.9em;
  line-height: 1em;
}
-->
</style>
<body>

HTML;
if (strpos($a, $CFG->dirroot) === false) {
    echo '<p>Este arquivo n&atilde;o faz parte do sistema.</p>';
} else {
    if (preg_match('/\.php$/', $a)) {
        if ($a == realpath($CFG->dirroot.'config.php')) {
            echo '<p>Este arquivo n&atilde;o pode ser mostrado por quest&otilde;es de seguran&ccedil;a.</p>';
        } else {
            echo '<code id="principal">';
            highlight_file($a);
            echo '</code>';
        }
    } else {
        echo '<pre>';
        readfile($a);
        echo '</pre>';
    }
}
echo <<<HTML
</body>
</html>
HTML;
exit(0);
