<?php
//
// SIMP
// Descricao: Arquivo que exibe um grafico de utilizacao da memoria
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.7
// Data: 20/09/2007
// Modificado: 16/02/2011
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Bloquear caso necessario
$modulo = modulo::get_modulo(__FILE__);
require_once($CFG->dirmods.$modulo.'/bloqueio.php');


/// Criar grafico
$g = new grafico('Gráfico de Memória por Entidade');
$g->nome_arquivo = 'memoria';
$g->formato      = GRAFICO_TIPO_PNG;
$g->largura      = 450;
$g->altura       = 350; // 2M
$g->pos_legenda  = GRAFICO_DIREITA;
$g->tipo_cor     = GRAFICO_COR_NORMAL;
$g->tipo_grafico = GRAFICO_LINHA;

$entidade = $_GET['entidade'];
if (!simp_autoload($entidade)) {
    exit(1);
}

try {
    $rc = new ReflectionClass($entidade);
    if (!$rc->isSubclassOf('objeto')) {
        exit(1);
    }
} catch (Exception $e) {
    exit(1);
}

$vt = array();

$inicio = memory_get_usage();

for ($i = 10; $i <= 100; $i += 10) {
    for ($j = 0; $j < 10; $j++) {
        $vt[] = new $entidade();
    }
    $escala[] = $i;
    $valores[] = memory_get_usage() - $inicio;
}

$g->escala = $escala;
$g->valores = $valores;
$g->conversao_valores = 'memoria::formatar_bytes';

$maior = max($valores);
$margem = 118;

// Regra de 3
// altura -> $maior
// 350    -> 2097152
$g->altura = ($maior * 350 / 2097152) + $margem;
$g->imprimir();
exit(0);