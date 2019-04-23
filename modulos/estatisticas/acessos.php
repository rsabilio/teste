<?php
//
// SIMP
// Descricao: Pagina de estatisticas do numero de acessos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.3.1
// Data: 25/06/2007
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
  <p>Esta ferramenta apresenta a quantidade de acessos realizados em um determinado per&iacute;odo.
  &Eacute; recomend&aacute;vel selecionar um per&iacute;odo de no m&aacute;ximo 15 meses
  para evitar a sobrecarga do sistema.</p>
AJUDA;


/// Imprimir pagina
modulo::pagina('logica_exibir_grafico', $dados_pagina, $dados_gerais);


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
            grafico::exibir_grafico('Gr&aacute;fico de Acessos', $CFG->wwwroot.'webservice/grafico.php', $CFG->dirroot.'webservice/grafico.php', array('id' => $id));
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

    $padrao = array(
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
    if ($quantidade_meses > 15) {
        $erros[] = 'Selecione um per&iacute;odo de no m&aacute;ximo 15 meses';
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

    $dados_grafico = new stdClass();
    $dados_grafico->titulo = 'Gráfico de Acessos';
    $dados_grafico->valores = array();
    $dados_grafico->escala  = array();

    $mes_inicio = (int)$dados->mes_inicio;
    $ano_inicio = (int)$dados->ano_inicio;
    $mes_termino = (int)$dados->mes_termino;
    $ano_termino = (int)$dados->ano_termino;

    $nomes_meses = listas::get_meses();

    // Consultar Logs
    ini_set('max_execution_time', '600');
    $log = new log_sistema();
    $time = mktime(0, 0, 0, $mes_inicio, 1, $ano_inicio);
    $time_ate = mktime(0, 0, 0, $mes_termino + 1, 0, $ano_termino);
    $time_ate = max($time, $time_ate);

    while ($time < $time_ate) {
        list($dia, $mes, $ano) = util::get_data_completa($time);
        $proximo_time = mktime(0, 0, 0, $mes + 1, 1, $ano);

        $vt_condicoes = array();
        $vt_condicoes[] = condicao_sql::montar('operacao', '=', LOG_ENTRADA);
        $vt_condicoes[] = condicao_sql::montar('data', '>=', $time);
        $vt_condicoes[] = condicao_sql::montar('data', '<', $proximo_time);
        $vt_condicoes[] = condicao_sql::montar('erro', '=', false);
        $vt_condicoes[] = condicao_sql::montar('cod_usuario', '<>', 1);
        $condicoes = condicao_sql::sql_and($vt_condicoes);
        $dados_grafico->valores[] = $log->quantidade_registros($condicoes);
        $m = ($mes % 12) ? $mes % 12 : 12;
        $dados_grafico->escala[]  = $nomes_meses[$m].'/'.$ano;

        $time = $proximo_time;
    }

    // Calcular media
    $media = round(array_sum($dados_grafico->valores) / count($dados_grafico->valores), 2);
    $dados_grafico->legenda_linhas = array('Média');
    $dados_grafico->linhas = array($media, $media);

    /// Criar grafico
    $dados_grafico->largura        = 100 + (30 * count($dados_grafico->escala));
    $dados_grafico->altura         = 300;
    $dados_grafico->tipo_grafico   = GRAFICO_LINHA;
    $dados_grafico->ponto          = GRAFICO_PONTO_BOLA;
    $dados_grafico->nome_arquivo   = 'grafico_acessos_'.$mes_inicio.'-'.$ano_inicio.'_'.$mes_termino.'-'.$ano_termino;
    $dados_grafico->cache          = 600; // 10 minutos

    return $dados_grafico;
}
