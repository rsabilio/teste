<?php
//
// SIMP
// Descricao: Arquivo de Constantes
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.25
// Data: 28/05/2007
// Modificado: 19/01/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

/// VARIAVEIS
define('SISTEMA',         'simp');  // Nome do sistema
define('VERSAO_SISTEMA',  '1.0');   // Versao do sistema
define('ANO_INICIO',      2007);    // Ano de inicio do sistema
define('DEVEL_BLOQUEADO', false);   // Situacao do modulo Devel


/// ERROS
define('ERRO_ALTERAR',   'Voc&ecirc; n&atilde;o tem permiss&atilde;o para alterar este registro');
define('ERRO_EXCLUIR',   'Voc&ecirc; n&atilde;o tem permiss&atilde;o para excluir este registro');
define('ERRO_EXIBIR',    'Voc&ecirc; n&atilde;o tem permiss&atilde;o para ver os dados deste registro');
define('ERRO_INSERIR',   'Voc&ecirc; n&atilde;o tem permiss&atilde;o para inserir um novo registro');
define('ERRO_PERMISSAO', 'Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar esta p&aacute;gina');


/// TEMAS
define('TEMA_PADRAO', 'azul');
global $vt_temas;
$vt_temas = array(
    'azul'           => 'Azul',
    'gelo'           => 'Gelo',
    'ave'            => 'Ave',
    'liamg'          => 'Liamg',
    'acessibilidade' => 'Acessibilidade',
    '0'              => 'Nenhum'
);


/// GRUPOS

// GERAIS
define('COD_ADMIN', 1);


/// CONSTANTES
define('TEMPO_EXPIRA', 432000); // Tempo de duracao da cache (5 dias)
define('DESCRICAO_SIMP', 'Framework para o Desenvolvimento de Sistemas de Informação Modulares em PHP');
define('VERSAO_SIMP', '1.5.2');
define('MANUAL_PHP', 'http://br.php.net/manual/pt_BR/');

// Ajustar estes valores de acordo com as capacidades do servidor
define('LOAD_AVG_MAX_ESPERADO', 1); // padrao 1
define('LOAD_AVG_MIN_ALERTA',   2); // padrao 2
define('LOAD_AVG_MAX_ALERTA',   3); // padrao 3

define('TEMPO_ALERTA',            10); // Tempo de carregamento da pagina em segundos considerado em alerta
define('MEMORIA_ALERTA',     '50MiB'); // Quantidade de memoria considerada em alerta (padrao IEC)
define('SQL_ALERTA',              30); // Quantidade de SQLs considerada em alerta
define('SQL_DEMANDA_ALERTA',      10); // Quantidade de SQLs feitas sob demanda considerada em alerta
