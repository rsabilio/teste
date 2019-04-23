<?php
//
// SIMP
// Descricao: Pagina de estatisticas sobre S.O. utilizados
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.3.1
// Data: 09/11/2007
// Modificado: 12/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');


/// Dados da Pagina
$dados_pagina = new stdClass();

/// Dados Gerais
$dados_gerais = new stdClass();
$dados_gerais->ajuda = <<<AJUDA
  <p>Esta ferramenta apresenta a quantidade de acessos realizados com cada tipo de
  <abbr title="Sistema Operacional">S.O.</abbr>.
  &Eacute; recomend&aacute;vel selecionar um per&iacute;odo de no m&aacute;ximo 12 meses
  para evitar a sobrecarga do sistema.</p>
AJUDA;


/// Imprimir pagina
modulo::pagina('logica_exibir_grafico', $dados_gerais);


/// Funcoes


//
//     Logica de exibir grafico
//
function logica_exibir_grafico($pagina, $dados, $arquivos, $dados_gerais) {
// pagina $pagina: pagina
// stdClass $dados: dados submetidos
// stdClass $arquivos: arquivos submetidos
// stdClass $dados_gerais: dados gerais
//
    global $CFG;
    $action = $CFG->site;
    $modulo = modulo::get_modulo(__FILE__);

    if (isset($dados_gerais->ajuda)) {
        mensagem::comentario($CFG->site, $dados_gerais->ajuda);
    }
    if (!$dados) {
        imprimir_form($dados, $action);
    } else {
        if (!validar_dados($dados, $erros)) {
            mensagem::erro($erros);
            imprimir_form($dados, $action);
        } else {
            imprimir_form($dados, $action);

            $id = md5(serialize($dados)).cache_arquivo::get_id();
            if (cache_arquivo::em_cache($id)) {
                $dados_grafico = cache_arquivo::get_valor($id);
            } else {
                $dados_grafico = calcular_dados_grafico($dados);
                cache_arquivo::set_valor($id, $dados_grafico, 600);
            }
            grafico::exibir_grafico('Gr&aacute;fico de S.O.', $CFG->wwwroot.'webservice/grafico.php', $CFG->dirroot.'webservice/grafico.php', array('id' => $id));
        }
    }

}


//
//     Imprime o formulario
//
function imprimir_form($dados, $action) {
// Object $dados: dados submetidos
// String $action: endereco de destino dos dados
//
    global $CFG;

    $vt_meses = listas::get_meses();
    $mes_atual = (int)strftime('%m', $CFG->time);
    $ano_atual = (int)strftime('%Y', $CFG->time);

    // Vetor de tipos de ordenacao
    $vt_ordem = array(1 => 'Nome do S.O.', 2 => 'Quantidade de Acessos');

    $padrao = array(
        'versoes'     => true,
        'ordem'       => 1,
        'mes_inicio'  => $mes_atual,
        'ano_inicio'  => $ano_atual,
        'mes_termino' => $mes_atual,
        'ano_termino' => $ano_atual
    );
    $dados = formulario::montar_dados($padrao, $dados);

    // Imprimir o formulario
    $form = new formulario($action, 'form_acessos');
    $form->titulo_formulario('Filtro de consulta');
    $form->inicio_bloco('In&iacute;cio');
    $form->campo_select('mes_inicio', 'mes_inicio', $vt_meses, $dados->mes_inicio, 'M&ecirc;s');
    $form->campo_text('ano_inicio', 'ano_inicio', $dados->ano_inicio, 4, 10, 'Ano', false, false, false, 'uint');
    $form->fim_bloco();
    $form->inicio_bloco('T&eacute;rmino');
    $form->campo_select('mes_termino', 'mes_termino', $vt_meses, $dados->mes_termino, 'M&ecirc;s');
    $form->campo_text('ano_termino', 'ano_termino', $dados->ano_termino, 4, 10, 'Ano', false, false, false, 'uint');
    $form->fim_bloco();
    $form->campo_select('ordem', 'ordem', $vt_ordem, $dados->ordem, 'Ordem');
    $form->campo_bool('versoes', 'versoes', 'Modelos separados por vers&atilde;o', $dados->versoes);
    $form->campo_submit('enviar', 'enviar', 'Consultar', true);
    $form->imprimir();
}


//
//     Valida os dados do formulario
//
function validar_dados($dados, &$erros) {
// stdClass $dados: dados submetidos
// Array[String] $erros: vetor de erros
//
    $erros = array();

    if (!formulario::validar('Ano de In&iacute;cio', 'int', $dados->ano_inicio, $erros_campo)) {
        $erros[] = 'O ano de in&iacute;cio n&atilde;o est&aacute; no padr&atilde;o:';
        $erros[] = $erros_campo;
    }
    if (!formulario::validar('Ano de T&eacute;rmino', 'int', $dados->ano_termino, $erros_campo)) {
        $erros[] = 'O ano de t&eacute;rmino n&atilde;o est&aacute; no padr&atilde;o:';
        $erros[] = $erros_campo;
    }

    if (!empty($erros)) {
        return false;
    }

    // Sanitizar dados
    $dados->mes_inicio  = (int)$dados->mes_inicio;
    $dados->ano_inicio  = formulario::filtrar('int', $dados->ano_inicio);
    $dados->mes_termino = (int)$dados->mes_termino;
    $dados->ano_termino = formulario::filtrar('int', $dados->ano_termino);

    if ($dados->ano_termino < $dados->ano_inicio) {
        $erros[] = 'A data de t&eacute;rmino deve ser posterior &agrave; data de in&iacute;cio';
        return false;
    } elseif ($dados->ano_termino == $dados->ano_inicio) {
        if ($dados->mes_termino < $dados->mes_inicio) {
            $erros[] = 'A data de t&eacute;rmino deve ser posterior &agrave; data de in&iacute;cio';
            return false;
        }
    }

    $quantidade_meses = (($dados->ano_termino - $dados->ano_inicio) * 12) + ($dados->mes_termino - $dados->mes_inicio) + 1;
    if ($quantidade_meses > 12) {
        $erros[] = 'Selecione um per&iacute;odo de no m&aacute;ximo 12 meses';
        return false;
    }

    return true;
}


//
//     Monta um objeto com os dados do grafico
//
function calcular_dados_grafico($dados) {
// stdClass $dados: dados submetidos
//
    simp_autoload('grafico');

    $mes_inicio = (int)$dados->mes_inicio;
    $ano_inicio = (int)$dados->ano_inicio;
    $mes_termino = (int)$dados->mes_termino;
    $ano_termino = (int)$dados->ano_termino;
    $versoes = (bool)$dados->versoes;
    $ordem = (int)$dados->ordem;

    $valores = array();
    $escala  = array();

    $time_inicio  = mktime(0, 0, 0, $mes_inicio, 1, $ano_inicio);
    $time_termino = mktime(0, 0, 0, $mes_termino + 1, 0, $ano_termino);

    // Consultar Logs
    ini_set('max_execution_time', '600');
    simp_autoload('log_sistema');
    $vt_condicoes = array();
    $vt_condicoes[] = condicao_sql::montar('data', '>=', $time_inicio);
    $vt_condicoes[] = condicao_sql::montar('data', '<', $time_termino);
    $vt_condicoes[] = condicao_sql::montar('operacao', '=', LOG_ENTRADA);
    $vt_condicoes[] = condicao_sql::montar('erro', '=', false);
    $condicoes = condicao_sql::sql_and($vt_condicoes);
    $campos = array('detalhes');
    $logs = objeto::get_objeto('log_sistema')->consultar_varios_iterador($condicoes, $campos);

    foreach ($logs as $log) {

        // Obter dados do S.O.
        $user_agent = $log->detalhes;
        $ua = new user_agent($user_agent);

        // Separar versoes diferentes
        if ($versoes) {
            $nome = $ua->so.($ua->versao_so ? ' '.$ua->versao_so : '');

        // Agrupar versoes diferentes
        } else {
            $nome = $ua->so;
        }

        $pos = array_search($nome, $escala);
        if ($pos !== false) {
            $valores[$pos] += 1;
        } else {
            $escala[] = $nome;
            $valores[] = 1;
        }
    }

    // Ordenar
    switch ($ordem) {

    // Nome
    case 1:
        array_multisort($escala, SORT_ASC, SORT_STRING, $valores);
        break;

    // Valor
    case 2:
        array_multisort($valores, SORT_DESC, SORT_NUMERIC, $escala);
        break;
    }

    /// Criar grafico
    $dados_grafico = new stdClass();
    $dados_grafico->titulo       = 'Gráfico de S.O.';
    $dados_grafico->tipo_grafico = GRAFICO_BARRA;
    $dados_grafico->escala       = $escala;
    $dados_grafico->valores      = $valores;
    $dados_grafico->largura      = 100 + (35 * count($dados_grafico->escala));
    $dados_grafico->altura       = 300;
    $dados_grafico->nome_arquivo = 'grafico_so_'.$mes_inicio.'-'.$ano_inicio.'_'.$mes_termino.'-'.$ano_termino.($versoes ? '_versao' : '');
    $dados_grafico->cache        = 600; // 10 minutos

    return $dados_grafico;
}
