<?php
//
// SIMP
// Descricao: Arquivo de configuracoes padrao (pre-instalacao do sistema)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.12
// Data: 03/03/2007
// Modificado: 09/11/2010
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Nome do Sistema
$sistema = 'simp';
$dominio = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

// Obter caminho ate o arquivo corrente
if (php_sapi_name() == 'cli') {
    $endereco = '';
} else {
    $arquivo_corrente = basename(__FILE__);
    $md5_corrente = md5_file(__FILE__);

    $dir_script = dirname($_SERVER['SCRIPT_FILENAME']);
    $rel = $dir_script;
    while (!is_file($rel.'/'.$arquivo_corrente) || md5_file($rel.'/'.$arquivo_corrente) != $md5_corrente)  {
        $novo_rel = dirname($rel);
        if ($novo_rel == $rel) {
            echo '<p>O arquivo '.$arquivo_corrente.' n&atilde;o foi encontrado</p>';
            exit(1);
        }
        $rel = $novo_rel;
    }
    $path_extra = str_replace($rel, '', $dir_script);
    $endereco   = $dominio.str_replace($path_extra, '', dirname($_SERVER['SCRIPT_NAME'])).'/';
}

// Configuracoes padrao
$dirroot    = dirname(__FILE__).'/';
$wwwroot    = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.str_replace('//', '/', $endereco);
$path       = '/';
$versao     = 0;
$instalacao = 0;
$localhost  = true;
$charset    = 'utf-8';

/// Configuracoes do BD antes da instalacao
$bd_config = new stdClass();
$bd_config->sgbd     = '';
$bd_config->servidor = '';
$bd_config->porta    = '';
$bd_config->base     = '';
$bd_config->usuario  = '';
$bd_config->senha    = '';

require($dirroot.'var.php');
