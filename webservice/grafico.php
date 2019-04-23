<?php
//
// SIMP
// Descricao: Grafico generico
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 25/01/2011
// Modificado: 03/02/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

simp_autoload('grafico');

// Obter dados da sessao
if (!isset($_GET['id'])) {
    echo 'N&atilde;o foi informado o ID do gr&aacute;fico';
    exit(1);
}
$id = $_GET['id'];
if (!cache_arquivo::em_cache($id)) {
    echo 'Os dados do gr&aacute;fico j&aacute; expiraram ou o ID informado &eacute; inv&aacute;lido';
    exit(1);
}
$dados = cache_arquivo::get_valor($id);

/// Criar grafico
$g = new grafico($dados->titulo);
$g->formato        = isset($dados->formato)        ? $dados->formato        : GRAFICO_TIPO_PNG;
$g->largura        = isset($dados->largura)        ? $dados->largura        : 300;
$g->altura         = isset($dados->altura)         ? $dados->altura         : 200;
$g->tipo_grafico   = isset($dados->tipo_grafico)   ? $dados->tipo_grafico   : GRAFICO_LINHA;
$g->tipo_cor       = isset($dados->tipo_cor)       ? $dados->tipo_cor       : GRAFICO_COR_NORMAL;
$g->borda          = isset($dados->borda)          ? $dados->borda          : GRAFICO_BORDA_3D;
$g->ponto          = isset($dados->ponto)          ? $dados->ponto          : GRAFICO_PONTO_NENHUM;
$g->cache          = isset($dados->cache)          ? $dados->cache          : 0;
$g->qualidade      = isset($dados->qualidade)      ? $dados->qualidade      : 100;
$g->tamanho_titulo = isset($dados->tamanho_titulo) ? $dados->tamanho_titulo : 15;
$g->tamanho_texto  = isset($dados->tamanho_texto)  ? $dados->tamanho_texto  : 14;
$g->nome_arquivo   = isset($dados->nome_arquivo)   ? $dados->nome_arquivo   : 'grafico';
$g->salvar         = isset($dados->salvar)         ? $dados->salvar         : false;

if (isset($dados->escala)) {
    $g->escala = $dados->escala;
}
if (isset($dados->valores)) {
    $g->valores = $dados->valores;
}
if (isset($dados->cores)) {
    $g->set_cores($dados->cores);
}
if (isset($dados->legenda)) {
    $g->legenda = $dados->legenda;
    if (isset($dados->pos_legenda)) {
        $g->pos_legenda = $dados->pos_legenda;
    }
}
if (isset($dados->linhas)) {
    $g->linhas = $dados->linhas;
    if (isset($dados->legenda_linhas)) {
        $g->legenda_linhas = $dados->legenda_linhas;
    }
}
if (isset($dados->valor_topo)) {
    $g->valor_topo = $dados->valor_topo;
}
if (isset($dados->conversao_valores)) {
    $g->conversao_valores = $dados->conversao_valores;
}
if (isset($dados->codigo_conversao_valores)) {
    $g->codigo_conversao_valores = $dados->codigo_conversao_valores;
}

$g->imprimir();
