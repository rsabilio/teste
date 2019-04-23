<?php
//
// SIMP
// Descricao: Grafico de pilha
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.0
// Data: 10/08/2010
// Modificado: 10/08/2010
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

/// Criar grafico
$g = new grafico($_GET['titulo']);
$g->formato      = $CFG->gd ? GRAFICO_TIPO_PNG : GRAFICO_TIPO_HTML;
$g->largura      = 250 + (20 * count($_GET['escala']));
$g->altura       = 380;
$g->tipo_cor     = GRAFICO_COR_NORMAL;
$g->tipo_grafico = GRAFICO_PILHA;
$g->cache        = 24 * 60; // 1 dia
$g->cache        = false;
$g->escala       = $_GET['escala'];
$g->valores      = $_GET['valores'];
$g->legenda      = $_GET['legenda'];

if (isset($_GET['valor_topo'])) {
    $g->altura     = 300 + ($_GET['valor_topo'] / 1048576 * 20);
    $g->valor_topo = $_GET['valor_topo'];
}
if (isset($_GET['formatar'])) {
    $g->conversao_valores = 'memoria::formatar_bytes';
}

$g->imprimir();
