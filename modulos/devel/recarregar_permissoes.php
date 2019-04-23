<?php
//
// SIMP
// Descricao: Script que recarrega as permissoes do usuario gravadas em cache
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

/// Limpar permissoes da cache
if (isset($_SESSION[$CFG->codigo_session])) {
    $cod_usuario = (int)$_SESSION[$CFG->codigo_session];
    if (objeto::em_cache('usuario', $cod_usuario)) {
        objeto::limpar_cache('usuario', $cod_usuario);
        objeto::limpar_cache('usuarios_grupos');
        objeto::limpar_cache('permissao');
        objeto::limpar_cache('arquivo');
        objeto::limpar_cache('grupo');
    }
}

/// Redirecionar para onde estava
if (isset($_GET['url'])) {
    $url = base64_decode($_GET['url']);
} else {
    $url = $CFG->wwwroot;
}
header('Location: '.$url);
exit(0);