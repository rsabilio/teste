<?php
//
// SIMP
// Descricao: Script para limpar a cache de arquivos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.0
// Data: 14/03/2011
// Modificado: 14/03/2011
// License: LICENSE.TXT
// Copyright (C) 2011  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Bloquear caso necessario
$modulo = modulo::get_modulo(__FILE__);
require_once($CFG->dirmods.$modulo.'/bloqueio.php');

/// Limpar cache de arquivos
cache_arquivo::limpar();

/// Redirecionar para onde estava
if (isset($_GET['url'])) {
    $url = base64_decode($_GET['url']);
} else {
    $url = $CFG->wwwroot;
}
header('Location: '.$url);
exit(0);