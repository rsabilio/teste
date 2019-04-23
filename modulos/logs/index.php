<?php
//
// SIMP
// Descricao: Lista de logs do sistema
// Autor: Rubens Takiguti Ribeiro && Rodrigo Pereira Moreira
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.2.5
// Data: 23/05/2007
// Modificado: 11/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');

/// Dados do Formulario
$modulo = modulo::get_modulo(__FILE__);
$classe = 'log_sistema';
$dados  = formulario::get_dados();
sanitizar($dados);
$action = $CFG->site;
$sistema = texto::codificar($CFG->titulo);
$ajuda  = <<<AJUDA
  <p>Este formul&aacute;rio lista as opera&ccedil;&otilde;es realizadas no sistema em um
  determinado per&iacute;odo (iniciado na instala&ccedil;&atilde;o do sistema {$sistema}).
  Para refinar a busca, deve-se preencher o(s) campo(s)
  Usu&aacute;rio e/ou IP e/ou Entidade e/ou ID.</p><p>A busca por nome de usu&aacute;rio
  &eacute; feita atrav&eacute;s da semelhan&ccedil;a com o nome, j&aacute; por IP a busca
  &eacute; espec&iacute;fica.</p><p>Para n&atilde;o refinar a busca, basta deixar os
  campos em branco.</p>
  <p>Os poss&iacute;veis campos exibidos pela pesquisa s&atilde;o:</p>
  <ul>
    <li>Usu&aacute;rio: nome do usu&aacute;rio que realizou a a&ccedil;&atilde;o</li>
    <li>Nome da Entidade: nome da entidade que sofreu a a&ccedil;&atilde;o</li>
    <li>Data: data em que ocorreu a a&ccedil;&atilde;o</li>
    <li>A&ccedil;&atilde;o: a&ccedil;&atilde;o realizada pelo usu&aacute;rio</li>
    <li>IP: Endere&ccedil;o de IP do usu&aacute;rio quando realizou a a&ccedil;&atilde;o</li>
    <li>ID: Chave &uacute;nica que identifica a entidade que sofreu a a&ccedil;&atilde;o</li>
    <li>Entidade: Tipo de entidade que sofreu a a&ccedil;&atilde;o</li>
    <li>Detalhes: Outras informa&ccedil;&otilde;es referentes &agrave; a&ccedil;&atilde;o
    como os dados alterados</li>
  </ul>
  <p>Obs.: recomenda-se n&atilde;o especificar um per&iacute;odo muito longo para evitar a
  sobrecarga do sistema.</p>
AJUDA;


/// Dados da Pagina
$titulo  = 'Logs';
$nav[]   = '#index.php';
$nav[]   = $modulo.'#'.basename(__FILE__);
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Imprimir pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->imprimir_menu($USUARIO);
$pagina->inicio_conteudo($titulo);
mensagem::comentario($CFG->site, $ajuda);
logica_formulario_log($dados, $action);
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Logica do formulario de log
//
function logica_formulario_log($dados, $action) {
// Array[String => Mixed] $dados: Dados
// String $action: url para o action do formulario
//
    if ($dados) {
        $condicoes = montar_condicoes($dados, $erros);
        if (!$condicoes) {
            mensagem::erro($erros);
            imprimir_form($dados, $action);
        } else {
            imprimir_form($dados, $action);
            imprimir_resultado($condicoes, $dados->campos_exibidos);
        }
    } else {
        imprimir_form($dados, $action);
    }
}


//
//     Monta as condicoes de busca
//
function montar_condicoes($dados, &$erros) {
// Array[String => Mixed] $dados: Dados a serem avaliados pela logica de negocio
// Array[String] $erros: Vetor de erros
//
    $erros = array();

    // Se os dados nao foram submetidos
    if (!$dados) {
        $erros[] = 'Dados n&atilde;o foram submetidos';
        return false;
    }

    if (!isset($dados->campos_exibidos)) {
        $erros[] = '&Eacute; necess&aacute;rio escolher pelo menos um campo a ser exibido';
        return false;
    }

    // Montar o time de inicio e termino
    $de  = mktime($dados->de_hora, $dados->de_minuto, $dados->de_segundo, $dados->de_mes, $dados->de_dia, $dados->de_ano);
    $ate = mktime($dados->ate_hora, $dados->ate_minuto, $dados->ate_segundo, $dados->ate_mes, $dados->ate_dia, $dados->ate_ano);

    $vt_condicoes = array();

    if ($de > $ate) {
        $erros[] = 'O momento de in&iacute;cio precisa ser anterior ao momento de t&eacute;rmino dos logs';
        return false;
    }

    // Montar condicoes com o intervalo de tempo
    $vt_condicoes[] = condicao_sql::montar('data', '>=', $de);
    $vt_condicoes[] = condicao_sql::montar('data', '<=', $ate);

    // Tipo de erro
    switch ($dados->tipo_log) {
    case 0:
        //void
        break;
    case 1:
        $vt_condicoes[] = condicao_sql::montar('erro', '=', false);
        break;
    case 2:
        $vt_condicoes[] = condicao_sql::montar('erro', '=', true);
        break;
    }

    // Se informou o IP
    if ($dados->ip !== '') {
        $vt_condicoes[] = condicao_sql::montar('ip', '=', $dados->ip);
    }

    // Se Informou a Entidade
    if ($dados->entidade) {
        $vt_condicoes[] = condicao_sql::montar('entidade', '=', $dados->entidade);
    }

    // Se informou o Usuario
    if (!empty($dados->usuario)) {
        $vt_condicoes[] = condicao_sql::montar('usuario:nome', 'LIKE', "%{$dados->usuario}%");
    }
    $condicoes = condicao_sql::sql_and($vt_condicoes);
    return $condicoes;
}



//
//     Metodo que retorna quais sao os campos da busca
//
function get_campos_exibidos() {
    return array(
        0 => 'Usu&aacute;rio',
        1 => 'Nome da Entidade',
        2 => 'Data',
        3 => 'A&ccedil;&atilde;o',
        4 => 'IP',
        5 => 'ID',
        6 => 'Entidade',
        7 => 'Detalhes'
    );
}


//
//     Imprime um formulario de filtro do Log
//
function imprimir_form($dados, $action) {
// Object $dados: dados enviados pelo formulario
// String $action: endereco de destino dos dados;
//
    global $CFG;

    // Obter data de instalacao do sistema
    $ano_inicio = (int)strftime('%Y', $CFG->instalacao);

    // Obter data atual
    $ano_atual = (int)strftime('%Y', $CFG->time);

    $vt_campos_exibidos = get_campos_exibidos();

    $vt_entidades = array(0 => 'Todas') + listas::get_entidades();
    $vt_tipo_log = array(
        0 => 'Todas',
        1 => 'Opera&ccedil;&otilde;es com Sucesso',
        2 => 'Apenas Opera&ccedil;&otilde;es com Erro'
    );
    list($dia, $mes, $ano) = util::get_data_completa($CFG->time);

    // Campos do formulario
    $campos = array(
        'de_dia'      => $dia,
        'de_mes'      => $mes,
        'de_ano'      => $ano,
        'de_hora'     => 0,
        'de_minuto'   => 0,
        'de_segundo'  => 0,
        'ate_dia'     => $dia,
        'ate_mes'     => $mes,
        'ate_ano'     => $ano,
        'ate_hora'    => 23,
        'ate_minuto'  => 59,
        'ate_segundo' => 59,
        'usuario'     => '',
        'ip'          => '',
        'entidade'    => 0,
        'tipo_log'    => 0,
        'campos_exibidos' => array(0, 1, 2, 3)
    );
    $dados = formulario::montar_dados($campos, $dados);

    $form = new formulario($action, 'form_logs');
    $form->inicio_bloco('Busca espec&iacute;fica');
    $form->campo_busca('usuario', 'usuario', 'usuario', 'nome', $dados->usuario, null, 128, 30, 'Usu&aacute;rio');
    $form->campo_busca('ip', 'ip', 'log_sistema', 'ip', $dados->ip, null, 15, 30, 'IP');
    $form->fim_bloco();
    $form->campo_select('entidade', 'entidade', $vt_entidades, $dados->entidade, 'Entidade');
    $form->campo_select('tipo_log', 'tipo_log', $vt_tipo_log, $dados->tipo_log, 'Tipo de Log');

    $form->inicio_bloco('In&iacute;cio');
    $form->campo_data('de', $dados->de_dia, $dados->de_mes, $dados->de_ano, 'Data', $ano_atual - $ano_inicio, 0);
    $form->campo_hora('de', $dados->de_hora, $dados->de_minuto, $dados->de_segundo, 'Hora');
    $form->fim_bloco();

    $form->inicio_bloco('T&eacute;rmino');
    $form->campo_data('ate', $dados->ate_dia, $dados->ate_mes, $dados->ate_ano, 'Data', $ano_atual - $ano_inicio, 0);
    $form->campo_hora('ate', $dados->ate_hora, $dados->ate_minuto, $dados->ate_segundo, 'Hora');
    $form->fim_bloco();

    $form->campo_checkbox('campos_exibidos', 'campo_exibidos', $vt_campos_exibidos, $dados->campos_exibidos, 'Campos Exibidos na Pesquisa', 2);
    $form->campo_submit('enviar', 'enviar', 'Consultar', true);
    $form->imprimir();
}


//
//     Sanitiza os dados submetidos
//
function sanitizar(&$dados) {
// stdClass $dados: dados a serem sanitizados
//
    if (!$dados) {
        return;
    }
    $dados->de_dia = formulario::filtrar('string', $dados->de_dia);
    $dados->de_mes = formulario::filtrar('string', $dados->de_mes);
    $dados->de_ano = formulario::filtrar('string', $dados->de_ano);
    $dados->de_hora    = formulario::filtrar('string', $dados->de_hora);
    $dados->de_minuto  = formulario::filtrar('string', $dados->de_minuto);
    $dados->de_segundo = formulario::filtrar('string', $dados->de_segundo);
    $dados->ate_dia = formulario::filtrar('string', $dados->ate_dia);
    $dados->ate_mes = formulario::filtrar('string', $dados->ate_mes);
    $dados->ate_ano = formulario::filtrar('string', $dados->ate_ano);
    $dados->ate_hora    = formulario::filtrar('string', $dados->ate_hora);
    $dados->ate_minuto  = formulario::filtrar('string', $dados->ate_minuto);
    $dados->ate_segundo = formulario::filtrar('string', $dados->ate_segundo);
    $dados->usuario = formulario::filtrar('string', $dados->usuario);
    $dados->ip = formulario::filtrar('string', $dados->ip);
    $dados->entidade = formulario::filtrar('string', $dados->entidade);
    $dados->tipo_log = formulario::filtrar('string', $dados->tipo_log);
    $dados->campo_exibidos = formulario::filtrar('string', $dados->campos_exibidos);
}


//
//     Imprime a tabela de Logs
//
function imprimir_resultado($condicoes, $vt_campos) {
// condicao_sql $condicoes: condicoes de busca dos logs
// Array[Int] $vt_campos: Um vetor com os campos a serem exibidos
//
    global $CFG;

    $descricao_campos = get_campos_exibidos();
    $nomes_campos = array(
        0 => 'usuario:nome',
        2 => 'data',
        3 => 'operacao',
        4 => 'ip',
        5 => 'cod_entidade',
        6 => 'entidade',
        7 => 'detalhes'
    );

    $campos_consultar = array(
        'usuario:nome',
        'data',
        'operacao',
        'ip',
        'cod_entidade',
        'entidade',
        'detalhes',
        'erro'
    );
    $ordem = array('data' => true);

    $possui = objeto::get_objeto('log_sistema')->possui_registros($condicoes);
    if (!$possui) {
        echo '<p>Nenhum log com estas restri&ccedil;&otilde;es</p>';
        return;
    }

    echo "<table class=\"tabela\" id=\"lista_logs\">\n";
    echo "<caption>Tabela de Log no Sistema</caption>\n";
    echo "<thead>\n";
    echo "  <tr>\n";
    foreach ($vt_campos as $id_campo) {
        $descricao = $descricao_campos[$id_campo];
        echo "    <th>{$descricao}</th>\n";
    }
    echo "  </tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";

    $l = new log_sistema();
    $logs = $l->consultar_varios_iterador($condicoes, $campos_consultar, $ordem);
    foreach ($logs as $log) {
        $class = $log->erro ? ' class="erro"' : '';

        echo "  <tr>\n";
        foreach ($vt_campos as $id_campo) {
            switch ($id_campo) {
            case 0:
                if (!$log->cod_usuario) {
                    $valor = '-';
                } else {
                    $valor = $log->exibir('usuario:nome');
                }
                break;
            case 1:
                if ($log->cod_entidade && $log->entidade) {
                    $classe = $log->entidade;
                    if (simp_autoload($classe)) {
                        $obj = new $classe();
                        $campos_obj = array();
                        if ($obj->get_campo_nome()) {
                            $campos_obj[] = $obj->get_campo_nome();
                        }
                        $obj->consultar('', $log->cod_entidade, $campos_obj);
                        $valor = $obj->existe() ? $obj->get_nome() : '-';
                    } else {
                        $valor = '-';
                    }
                } else {
                    $valor = '-';
                }
                break;
            case 2:
                $valor = strftime("{$CFG->formato_data} ({$CFG->formato_hora})", $log->data);
                break;
            default:
                $nome_campo = $nomes_campos[$id_campo];
                $valor = $log->exibir($nome_campo);
                break;
            }
            echo "    <td {$class}>".$valor."</td>\n";
        }
        echo "  </tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
}
