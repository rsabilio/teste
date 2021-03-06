<?php
//
// SIMP
// Descricao: Arquivo de sobreposicao das configuracoes do php.ini
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.16
// Data: 30/01/2008
// Modificado: 24/08/2010
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Modificar estas configuracoes com cautela!                    // Valor padrao

// Erros
ini_set('display_errors',                  '0');                 // 0
ini_set('display_startup_errors',          '0');                 // 0
ini_set('log_errors',                      '1');                 // 1
ini_set('report_memleaks',                 '1');                 // 1

// Runtime
ini_set('memory_limit',                    '16M');               // 16M
ini_set('precision',                       '14');                // 14
ini_set('y2k_compliance',                  '1');                 // 1 (nao mudar)
ini_set('date.timezone',                   'America/Sao_Paulo'); // America/Sao_Paulo
ini_set('default_mimetype',                'text/html');         // text/html
ini_set('default_charset',                 'utf-8');             // utf-8
ini_set('short_open_tag',                  '0');                 // 0
ini_set('asp_tags',                        '0');                 // 0
ini_set('zend.ze1_compatibility_mode',     '0');                 // 0
ini_set('zend.enable_gc',                  '0');                 // 0
ini_set('register_globals',                '0');                 // 0
ini_set('auto_detect_line_endings',        '0');                 // 0
ini_set('magic_quotes_runtime',            '0');                 // 0 (nao mudar)
ini_set('magic_quotes_gpc',                '0');                 // 0 (nao mudar)
ini_set('arg_separator.output',            '&amp;');             // &amp; (nao mudar)

// Sessao
ini_set('session.auto_start',              '0');                 // 0 (nao mudar)
ini_set('session.use_cookies',             '1');                 // 1 (nao mudar)
ini_set('session.use_trans_sid',           '0');                 // 0
ini_set('session.use_only_cookies',        '1');                 // 1 (nao mudar)
ini_set('session.hash_bits_per_character', '6');                 // 6
ini_set('session.gc_probability',          '1');                 // 1
ini_set('session.gc_divisor',              '1000');              // 100
ini_set('session.cache_limiter',           'nocache');           // nocache

// Tempo de execucao maximo do script
if (php_sapi_name() == 'cli') {
    ini_set('max_execution_time', '0');  // 0
} else {
    ini_set('max_execution_time', '30'); // 30
}

// Configuracoes para SGBD Oracle (descomentar caso necessario)
//putenv('ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0/server');
//putenv('NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8');
