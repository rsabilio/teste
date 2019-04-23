<?php
//
// SIMP
// Descricao: Lista de Arquivos do Sistema
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.0.7
// Data: 25/09/2007
// Modificado: 07/10/2010
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');

/// Dados da Lista
$dados_lista = new stdClass();
$dados_lista->links = array(
    array('arquivo' => 'inserir.php',         'class' => 'inserir'),
    array('arquivo' => 'inconsistencias.php', 'class' => 'inconsistencia'),
    array('arquivo' => 'gerar_ini.php',       'class' => 'ini')
);

/// Dados da Pagina
$dados_pagina = new stdClass();
$dados_pagina->ajuda = <<<AJUDA
  <p>A lista a seguir apresenta os arquivos do sistema
  separados por m&oacute;dulos.</p>
  <p>Ela define quais os nomes dos arquivos que os usu&aacute;rios
  poder&atilde;o acessar. As permiss&otilde;es de acesso s&atilde;o
  definidas no m&oacute;dulo "Permiss&otilde;es".</p>
AJUDA;


/// Dados do Formulario
$dados_form = new stdClass();
$dados_form->funcao_form = 'imprimir_formulario';
$dados_form->funcao_condicoes = 'montar_condicoes';

modulo::listar_entidades('arquivo', $dados_lista, $dados_pagina, $dados_form);


/// Funcoes


//
//     Imprime um formulario de filtro
//
function imprimir_formulario($dados) {
// stdClass $dados: dados submetidos
//
    global $CFG;
    $action = $CFG->site;
    link::normalizar($action, true);

    $vt_modulos = array('T' => 'Todos', 'S' => 'Simp') + listas::get_modulos();

    $padrao = array(
        'modulo'    => 'T',
        'arquivo'   => '',
        'descricao' => ''
    );
    $dados = formulario::montar_dados($padrao, $dados);

    $form = new formulario($action, 'form_arquivos');
    $form->titulo_formulario('Formul&aacute;rio de Filtro');
    $form->campo_select('modulo', 'modulo', $vt_modulos, $dados->modulo, 'M&oacute;dulo');
    $form->campo_text('arquivo', 'arquivo', $dados->arquivo, 128, 30, 'Arquivo');
    $form->campo_text('descricao', 'descricao', $dados->descricao, 128, 30, 'Descri&ccedil;&atilde;o');
    $form->campo_submit('enviar', 'enviar', 'Buscar');
    $form->imprimir();
}


//
//     Monta as condicoes de filtragem
//
function montar_condicoes($dados, &$erros) {
// stdClass $dados: dados submetidos
// Array[String] $erros: erros ocorridos
//
    $vt_condicoes = array();

    switch ($dados->modulo) {
    case 'T':
        //void
        break;
    case 'S':
        $vt_condicoes[] = condicao_sql::montar('modulo', '=', '');
        break;
    default:
        $vt_condicoes[] = condicao_sql::montar('modulo', '=', $dados->modulo);
        break;
    }
    
    if ($dados->arquivo) {
        $vt_condicoes[] = condicao_sql::montar('arquivo', '=', $dados->arquivo);
    }
    
    if ($dados->descricao) {
        $vt_condicoes[] = condicao_sql::montar('descricao', 'LIKE', '%'.$dados->descricao.'%');
    }

    $condicoes = condicao_sql::sql_and($vt_condicoes);

    return $condicoes;
}

