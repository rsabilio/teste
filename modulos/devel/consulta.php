<?php
//
// SIMP
// Descricao: Realiza consultas genericas
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.4.5
// Data: 16/05/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');


/// Bloquear caso necessario
$modulo = modulo::get_modulo(__FILE__);
require_once($CFG->dirmods.$modulo.'/bloqueio.php');

ini_set('max_execution_time', '0');


/// Dados do formulario
$dados = formulario::get_dados();


/// Dados da pagina
$titulo  ='Consulta Gen&eacute;rica';
$nav[$CFG->wwwmods.'devel/index.php'] = 'Desenvolvimento';
$nav[''] = 'Consulta Gen&eacute;rica';
$estilos = array(
    $CFG->wwwmods.'devel/estilos_consulta.css'
);
$scripts = array(
    $CFG->wwwmods.'devel/script_consulta.js.php'
);

inicializar_consulta($sessao);
if (isset($dados->operacao->consultar->csv)) {
    gerar_csv($dados, $sessao);
    exit(0);
}

/// Imprimir pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos, $scripts);
$pagina->inicio_conteudo($titulo);
logica_consulta($dados, $sessao);
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


/// Funcoes


//
//     Logica da consulta generica
//
function logica_consulta(&$dados, &$sessao) {
// Object $dados: dados submetidos por algum formulario
// Array[Mixed] $sessao: sessao
//
    global $CFG, $pagina;

    // Se uma operacao de link foi acionada, checar qual foi
    if (isset($_GET['op'])) {

        switch ($_GET['op']) {
        case 'abrir_classe':
            $classe = $_GET['classe'];
            $sessao['classe'] = $classe;
            break;

        case 'abrir_aba':
            $aba = $_GET['aba'];
            $sessao['aba'] = max(1, min(4, (int)$aba)); // 1..4
            break;

        case 'abrir_atributo':
            $campo = $_GET['atributo'];
            $sessao['abrir'][$campo] = 1;
            break;

        case 'fechar_atributo':
            $campo = $_GET['atributo'];
            unset($sessao['abrir'][$campo]);
            break;

        case 'adicionar_atributo':
            $campo = $_GET['atributo'];

            $pos = strpos($campo, ':') + 1;
            $campo = substr($campo, $pos);
            switch ($sessao['aba']) {
            case 1://campos
                $sessao['campos'][$campo] = $campo;
                break;
            case 2://condicoes
                $operando = &$sessao['condicao']['operando'];
                $sessao['condicao']['operandos'][$operando] = $campo;
                $operando = 1 - $operando;
                break;
            case 3://ordem
                $sessao['ordem'][$campo] = 1;
                break;
            }
            break;

        case 'avancar_pagina':
            $sessao['opcoes']['pagina_atual'] += 1;
            $dados->operacao->consultar = true;
            break;

        case 'desagrupar':
            desagrupar_condicoes($sessao);
            break;

        case 'limpar_condicao_simples':
            $sessao['condicao']['operando'] = 0;
            $sessao['condicao']['operandos'] = array('', '');
            break;

        case 'limpar_consulta':
            limpar_consulta($sessao);
            break;

        case 'listar_entidades':
            inicializar_consulta($sessao, true);
            break;

        case 'mudar_tipo_ordem':
            $campo = $_GET['atributo'];
            if (isset($sessao['ordem'][$campo])) {
                $sessao['ordem'][$campo] = 1 - $sessao['ordem'][$campo];
            }
            break;

        case 'remover_atributo':
            $campo = $_GET['atributo'];
            if (isset($sessao['campos'][$campo])) {
                unset($sessao['campos'][$campo]);
            }
            break;

        case 'remover_ordem':
            $campo = $_GET['atributo'];
            if (isset($sessao['ordem'][$campo])) {
                unset($sessao['ordem'][$campo]);
            }
            break;
        case 'forma_mostrar_campo':
            switch ($_GET['tipo']) {
            case 'nome':
            case 'descricao':
                $sessao['opcoes']['forma_mostrar_campo'] = $_GET['tipo'];
                break;
            }
            break;
        case 'voltar_pagina':
            $sessao['opcoes']['pagina_atual'] -= 1;
            $dados->operacao->consultar = true;
            break;
        }

    // Acoes de formulario:
    } elseif (isset($dados->operacao)) {
        list($operacao, ) = each($dados->operacao);

        switch ($operacao) {

        // Incluir condicao simples
        case 'incluir':
            $d = &$dados->operacao->incluir;

            $id = $sessao['condicao']['ultimo_id'] += 1;
            switch ($d->operador) {
            case 'ISNULL':
                $condicao = condicao_sql::montar($d->operando1, '=', null, false, $id);
                break;
            case 'ISNOTNULL':
                $condicao = condicao_sql::montar($d->operando1, '<>', null, false, $id);
                break;
            default:
                $flags = array();
                $flags['tipo1'] = $d->tipo1;
                if ($d->tipo2 == 'enum') {
                    $flags['tipo2'] = CONDICAO_SQL_TIPO_VALOR;
                } elseif ($d->tipo2 == 'const') {
                    $flags['tipo2'] = CONDICAO_SQL_TIPO_VALOR;
                } else {
                    $flags['tipo2'] = $d->tipo2;
                }
                if ($d->funcao1) {
                    $flags['funcao1'] = $d->funcao1;
                }
                if ($d->funcao2) {
                    $flags['funcao2'] = $d->funcao2;
                }

                if ($d->tipo2 == 'enum') {
                    $condicao = condicao_sql::montar($d->operando1, $d->operador, $d->operando2_enum, $flags, $id);
                } elseif ($d->tipo2 == 'const') {
                    $condicao = condicao_sql::montar($d->operando1, $d->operador, $d->operando2_const, $flags, $id);
                } else {
                    $condicao = condicao_sql::montar($d->operando1, $d->operador, $d->operando2, $flags, $id);
                }
                break;
            }
            $sessao['condicao']['condicoes'][] = $condicao;
            $sessao['condicao']['operandos'][0] = '';
            $sessao['condicao']['operandos'][1] = '';
            $sessao['condicao']['operando'] = 0;
            break;

        // Agrupar condicoes simples
        case 'agrupar_condicoes':
            $d = &$dados->operacao->agrupar_condicoes;
            list($operador, ) = each($d);
            agrupar_condicoes($sessao, $dados->condicoes, $operador);
            break;

        // Remover condicoes simples
        case 'remover_condicoes':
            remover_condicoes($sessao, $dados->condicoes);
            break;

        // Consultar
        case 'consultar':
            $sessao['opcoes']['pagina_atual'] = 0;
            break;

        // Salvar opcoes
        case 'opcoes':
            $sessao['opcoes']['filtrar'] = (int)$dados->operacao->opcoes->filtrar;
            $sessao['opcoes']['limite']  = round(abs($dados->operacao->opcoes->limite));
            break;

        // Operacao desconhecida
        default:
            mensagem::erro('Opera&ccedil;&atilde;o desconhecida');
        }
    }

    // Se nao escolheu a classe ainda
    if (!$sessao['classe']) {
        listar_entidades();

    // Se escolheu a classe, exibir formulario e resultado
    } else {
        imprimir_formulario($dados, $sessao);
        imprimir_resultado($dados, $sessao);
        listar_opcoes($sessao);
    }

    salvar_sessao($sessao);
}


//
//     Inicializa uma consulta obtendo os dados da sessao ou os dados padrao
//
function inicializar_consulta(&$sessao, $zerar = false) {
// Array[Mixed] $sessao: dados a serem armazenados em sessao
// Bool $zerar: apaga os dados da sessao
//
    if (isset($_SESSION['consulta']) && !$zerar) {
        $sessao = unserialize($_SESSION['consulta']);
    } else {
        $sessao['classe'] = false;
        $sessao['aba'] = 1;
        $sessao['abrir'] = array();
        $sessao['opcoes']['filtrar'] = 1;
        $sessao['opcoes']['limite'] = 0;
        $sessao['opcoes']['pagina_atual'] = 0;
        $sessao['opcoes']['forma_mostrar_campo'] = 'descricao';
        limpar_consulta($sessao);
    }
}


//
//     Salva os dados na sessao com serialize
//
function salvar_sessao(&$sessao) {
// Array[Mixed] $sessao: dados a serem salvos na sessao
//
    $_SESSION['consulta'] = serialize($sessao);
}


//
//     Limpa os dados da consulta
//
function limpar_consulta(&$sessao) {
// Array[Mixed] $sessao: dados da sessao
//
    $sessao['campos'] = array();
    $sessao['ordem']  = array();
    $sessao['condicao'] = array(
        'condicoes' => null,
        'operando'  => 0,
        'ultimo_id' => 0
    );
    $sessao['opcoes'] = array(
        'filtrar'             => 1,
        'limite'              => 0,
        'pagina_atual'        => 0,
        'forma_mostrar_campo' => 'descricao'
    );
}


//
//     Lista todas as entidades do sistema
//
function listar_entidades() {
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    $entidades = listas::get_entidades();
    echo '<p>Selecione a entidade principal que deseja consultar:</p>';
    echo '<ul>';
    foreach ($entidades as $classe => $entidade) {
        $link = link::adicionar_atributo($link_base, array('op', 'classe'), array('abrir_classe', $classe));
        echo '<li>';
        link::texto($link, $entidade, $entidade, false, false, false, false, false);
        echo '</li>';
    }
    echo '</ul>';
}


//
//     Exibe uma entidade e o formulario de busca generica
//
function exibir_entidade(&$sessao) {
// Array[Mixed] $sessao: dados a serem armazenados em sessao
//
    global $CFG;
    $classe = $sessao['classe'];
    $obj = objeto::get_objeto($classe);

    $lista = array();
    preencher_todos_atributos($lista, $sessao, $obj, $obj->get_classe(), false);

    echo '<fieldset><legend>Campos</legend>';
    echo '<p title="'.$obj->get_classe().'">'.$obj->get_entidade().'</p>';
    lista::hierarquica($lista);
    echo '</fieldset>';
}


//
//     Preenche uma lista com os atributos da entidade corrente
//
function preencher_todos_atributos(&$lista, &$sessao, $obj, $nome, $vetor = false) {
// Array[String => Bool || Type] $lista: Lista hierarquica
// Array[Mixed] $sessao: dados da sessao
// Object $obj: objeto corrente
// String $nome: nome (caminho) da entidade escolhida ate o objeto corrente
// Bool $vetor: indica que a lista e' derivada de um atributo do tipo vetor
//
    preencher_atributos($lista, $sessao, $obj, $nome, $vetor);
    preencher_implicitos($lista, $sessao, $obj, $nome, $vetor);
    preencher_objetos($lista, $sessao, $obj, $nome, $vetor);
    preencher_vetores($lista, $sessao, $obj, $nome);
}


//
//     Preenche uma lista com os atributos reais da entidade corrente
//
function preencher_atributos(&$lista, &$sessao, &$obj, $nome, $vetor = false) {
// Array[String => Bool || Type] $lista: Lista hierarquica
// Array[Mixed] $sessao: dados da sessao
// Object $obj: objeto corrente
// String $nome: nome (caminho) da entidade escolhida ate o objeto corrente
// Bool $vetor: indica que a lista e' derivada de um atributo do tipo vetor
//
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    foreach ($obj->get_atributos() as $nome_atributo => $def_atributo) {
        $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('adicionar_atributo', $nome.':'.$nome_atributo));

        $pos = '';
        if ($nome_atributo == $obj->get_chave()) {
            $pos .= '[PK] <em title="'.$nome_atributo.'">'.$def_atributo->descricao.'</em> ';
        } else {
            $pos .= '[Atr] <span title="'.$nome_atributo.'">'.$def_atributo->descricao.'</span> ';
        }
        switch ($sessao['aba']) {
        case 1://campos
        case 3://ordem
            if (!$vetor) {
                $pos .= link::icone($link, icone::endereco('adicionar'), 'Adicionar '.$def_atributo->descricao, '', false, true, false);
            }
            break;
        case 2://condicoes
            $pos .= link::icone($link, icone::endereco('adicionar'), 'Adicionar '.$def_atributo->descricao, '', false, true, false);
            break;
        }
        $lista[$pos] = false;
    }
}


//
//     Preenche uma lista com os atributos implicitos da entidade corrente
//
function preencher_implicitos(&$lista, &$sessao, &$obj, $nome, $vetor = false) {
// Array[String => Bool || Type] $lista: Lista hierarquica
// Array[Mixed] $sessao: dados da sessao
// Object $obj: objeto corrente
// String $nome: nome (caminho) da entidade escolhida ate o objeto corrente
// Bool $vetor: indica que a lista e' derivada de um atributo do tipo vetor
//
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);
    foreach ($obj->get_implicitos() as $nome_atributo => $def_atributo) {
        $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('adicionar_atributo', $nome.':'.$nome_atributo));
        $pos = '[Imp] <span title="'.$nome_atributo.'">'.$def_atributo->descricao.'</span>';
        switch ($sessao['aba']) {
        case 1://campos
            if (!$vetor) {
                $pos .= ' '.link::icone($link, icone::endereco('adicionar'), 'Adicionar '.$def_atributo->descricao, '', false, true, false);
            }
            break;
        case 2://condicoes
        case 3://ordem
            break;
        }
        $lista[$pos] = false;
    }
}


//
//     Preenche uma lista com objetos (relacionamentos 1:1) da entidade corrente
//
function preencher_objetos(&$lista, &$sessao, &$obj, $nome, $vetor = false) {
// Array[String => Bool || Type] $lista: Lista hierarquica
// Array[Mixed] $sessao: dados da sessao
// Object $obj: objeto corrente
// String $nome: nome (caminho) da entidade escolhida ate o objeto corrente
// Bool $vetor: indica que a lista e' derivada de um atributo do tipo vetor
//
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);
    foreach ($obj->get_definicoes_rel_uu() as $chave => $def) {
        $novo_nome = $nome.':'.$def->nome;

        // Se esta aberto
        if (is_array($sessao['abrir']) && array_key_exists($novo_nome, $sessao['abrir'])) {
            $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('fechar_atributo', $novo_nome));
            $pos = '[Obj] '.link::texto($link, $def->descricao, $def->nome, false, false, true, false, false);

            $lista[$pos] = array();
            preencher_todos_atributos($lista[$pos], $sessao, $obj->{$def->nome}, $novo_nome, $vetor);

        // Se esta fechado
        } else {
            $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('abrir_atributo', $novo_nome));
            $pos = '[Obj] '.link::texto($link, $def->descricao, $def->nome, false, false, true, false, false);

            $lista[$pos] = false;
        }
    }
}


//
//     Preenche uma lista com vetores (relacionamentos 1:N) da entidade corrente
//
function preencher_vetores(&$lista, &$sessao, &$obj, $nome) {
// Array[String => Bool || Type] $lista: Lista hierarquica
// Array[Mixed] $sessao: dados da sessao
// Object $obj: objeto corrente
// String $nome: nome (caminho) da entidade escolhida ate o objeto corrente
//
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);
    foreach ($obj->get_definicoes_rel_un() as $nome_vetor => $def_vetor) {
        $classe = $def_vetor->classe;
        $obj_vetor = objeto::get_objeto($classe);
        if ($def_vetor->descricao) {
            $desc_vetor = $def_vetor->descricao;
        } else {
            $desc_vetor = $obj_vetor->get_entidade(true);
        }

        $novo_nome = $nome.':'.$nome_vetor;
        if (array_key_exists($novo_nome, $sessao['abrir'])) {
            $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('fechar_atributo', $novo_nome));
            $pos = '[Vet] '.link::texto($link, $desc_vetor, $novo_nome, false, false, true, false, false);

            $lista[$pos] = array();
            preencher_todos_atributos($lista[$pos], $sessao, $obj_vetor, $novo_nome, true);
        } else {
            $link = link::adicionar_atributo($link_base, array('op', 'atributo'), array('abrir_atributo', $novo_nome));
            $pos = '[Vet] '.link::texto($link, $desc_vetor, $novo_nome, false, false, true, false, false);

            $lista[$pos] = false;
        }
    }
}


//
//     Imprime um formulario generico
//
function imprimir_formulario(&$dados, &$sessao) {
// Object $dados: dados submetidos
// Array[Mixed] $sessao: dados da sessao
//
    global $CFG;

    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    echo '<p>Selecione os campos desejados na busca, as condi&ccedil;&otilde;es de busca ';
    echo 'e os campos usados para ordena&ccedil;&atilde;o dos resultados.</p>';
    echo '<div class="dados">';
    echo '<strong class="titulo">Consulta</strong>';

    echo '<div class="abas">';

    echo '<div class="nomes_abas">';
    $class = ($sessao['aba'] == 1) ? 'ativa' : false;
    link::texto(link::adicionar_atributo($link_base, array('op', 'aba'), array('abrir_aba', '1')), 'Campos', false, false, $class, false, false, false);
    echo '<span> | </span>';
    $class = ($sessao['aba'] == 2) ? 'ativa' : false;
    link::texto(link::adicionar_atributo($link_base, array('op', 'aba'), array('abrir_aba', '2')), 'Condi&ccedil;&otilde;es', false, false, $class, false, false, false);
    echo '<span> | </span>';
    $class = ($sessao['aba'] == 3) ? 'ativa' : false;
    link::texto(link::adicionar_atributo($link_base, array('op', 'aba'), array('abrir_aba', '3')), 'Ordem', false, false, $class, false, false, false);
    echo '<span> | </span>';
    $class = ($sessao['aba'] == 4) ? 'ativa' : false;
    link::texto(link::adicionar_atributo($link_base, array('op', 'aba'), array('abrir_aba', '4')), 'Op&ccedil;&otilde;es', false, false, $class, false, false, false);
    echo '</div>';

    echo '<div class="conteudo_aba">';
    switch ($sessao['aba']) {
    case 1://campos
        $ajuda = '<p>Selecione os campos desejados no quadro abaixo clicando no &iacute;cone de adicionar.</p>'.
                 '<p>&Eacute; poss&iacute;vel expandir/esconder campos derivados clicando sobre eles.</p>';
        mensagem::comentario($link_base, $ajuda);

        $obj = objeto::get_objeto($sessao['classe']);
        if (!empty($sessao['campos'])) {
            echo '<ul>';
            foreach ($sessao['campos'] as $campo) {
                echo '<li>';
                echo formatar_campo($obj, $campo, $sessao['opcoes']['forma_mostrar_campo']);
                echo ' ';
                echo link::icone(link::adicionar_atributo($link_base, array('op', 'atributo'), array('remover_atributo', $campo)), icone::endereco('excluir'), 'Remover Campo', '', false, false, false, false, false);
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nenhum campo selecionado.</p>';
        }

        // Opcoes: descricao ou nome do campo
        echo '<hr />';
        imprimir_formas_mostrar_campo($sessao);
        break;

    case 2://condicoes
        $ajuda = '<ol>'.
                 '  <li>Inclua todas as condi&ccedil;&otilde;es envolvidas:'.
                 '    <ol>'.
                 '      <li>Selecione o primeiro operando no quadro abaixo</li>'.
                 '      <li>Selecione o segundo operando ou digite um valor de compara&ccedil;&atilde;o</li>'.
                 '      <li>Selecione o operador desejado</li>'.
                 '      <li>Selecione o tipo de operadores</li>'.
                 '      <li>Selecione a fun&ccedil;&atilde;o a ser aplicada sobre os operadores</li>'.
                 '      <li>Clique na a&ccedil;&atilde;o "Incluir"</li>'.
                 '    </ol>'.
                 '  </li>'.
                 '  <li>Agrupe as condi&ccedil;&otilde;es de forma hier&aacute;rquica:'.
                 '    <ol>'.
                 '      <li>Selecione as condi&ccedil;&otilde;es desejadas</li>'.
                 '      <li>Clique em um agrupador: "E", "OU" ou "N&Atilde;O" (obs.: "E" e "OU" devem agrupar '.
                 '      condi&ccedil;&otilde;es de um mesmo n&iacute;vel, ou seja, n&atilde;o agrupe '.
                 '      condi&ccedil;&otilde;es j&aacute; agrupadas)</li>'.
                 '    </ol>'.
                 '  </li>'.
                 '  <li>Utilize o link "Desagrupar tudo" para voltar as condi&ccedil;&otilde;es ao padr&atilde;o</li>'.
                 '</ol>';
        mensagem::comentario($link_base, $ajuda);

        $condicoes = &$sessao['condicao']['condicoes'];
        $disabled = empty($condicoes) ? ' disabled="disabled"' : '';

        echo '<form id="form_condicoes" action="'.$link_base.'" method="post" onsubmit="return submeter(this, 1);">';
        echo '<fieldset>';
        echo '<legend>Condi&ccedil;&otilde;es</legend>';
        echo '<div class="condicoes">';
        imprimir_condicoes($condicoes, $sessao);
        echo '</div>';
        echo '<hr />';
        echo '<p>';
        echo '  <span>Agrupar com:</span>';
        echo '  <input name="operacao[agrupar_condicoes][or]" type="submit" value="OU" class="botao" '.$disabled.'/>';
        echo '  <input name="operacao[agrupar_condicoes][and]" type="submit" value="E" class="botao" '.$disabled.'/>';
        echo '  <input name="operacao[agrupar_condicoes][not]" type="submit" value="N&Atilde;O" class="botao" '.$disabled.'/>';
        echo '</p>';
        echo '<p>';
        echo '  <span>Op&ccedil;&otilde;es:</span>';
        echo '  <input name="operacao[remover_condicoes][submit]" type="submit" value="Remover" class="botao" '.$disabled.'/>';
        echo '</p>';
        echo '<p>';
        link::texto(link::adicionar_atributo($link_base, 'op', 'desagrupar'), 'Desagrupar tudo', false, false, false, false, false, false);
        echo '</p>';
        echo '<hr />';
        imprimir_formas_mostrar_campo($sessao);
        echo '</fieldset>';
        echo '</form>';

        $operando1 = isset($sessao['condicao']['operandos'][0]) ? $sessao['condicao']['operandos'][0] : '';
        $operando2 = isset($sessao['condicao']['operandos'][1]) ? $sessao['condicao']['operandos'][1] : '';

        echo '<form id="form_nova_condicao" action="'.$link_base.'" method="post" onsubmit="return submeter(this, 1);">';
        echo '<fieldset>';
        echo '<legend>Nova condi&ccedil;&atilde;o simples</legend>';

        // Tabela de criacao de condicao simples
        echo '<table id="insercao_condicao">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">Propriedade</th>';
        echo '<th scope="col">Operando 1</th>';
        echo '<th scope="col">Operador</th>';
        echo '<th scope="col">Operando 2</th>';
        echo '<th scope="col">A&ccedil;&atilde;o</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Linha: VALOR
        echo '<tr>';
        echo '<th scope="row">Valor</th>';
        echo '<td>';
        $class = ($sessao['condicao']['operando'] == 0) ? 'class="ativo"' : '';
        echo '<input type="text" name="operacao[incluir][operando1]" id="input_operando1" value="'.$operando1.'" size="10" '.$class.'/>';
        echo '</td>';
        echo '<td>';
        echo '<select name="operacao[incluir][operador]">';
        echo '  <option value="=">igual a (=)</option>';
        echo '  <option value="&lt;&gt;">diferente de (&ne;)</option>';
        echo '  <option value="&gt;">maior que (&gt;)</option>';
        echo '  <option value="&lt;">menor que (&lt;)</option>';
        echo '  <option value="&gt;=">maior ou igual a (&ge;)</option>';
        echo '  <option value="&lt;=">menor ou igual a (&le;)</option>';
        echo '  <option value="LIKE">similar a (&sim;)</option>';
        echo '  <option value="ISNULL">&eacute; nulo</option>';
        echo '  <option value="ISNOTNULL">n&atilde;o &eacute; nulo</option>';
        echo '</select>';
        echo '</td>';
        echo '<td>';

        // Se e' um campo enum
        $primeiro_operando = $sessao['condicao']['operandos'][0];
        if ($primeiro_operando && operando_enum($sessao, $primeiro_operando, $vt_enum)) {
            echo '<select name="operacao[incluir][operando2_enum]" id="select_enum">';
            foreach ($vt_enum as $k => $v) {
                echo '<option value="'.$k.'">'.$v.'</option>';
            }
            echo '</select>';
        }
        echo '<select name="operacao[incluir][operando2_const]" id="select_const">';
        foreach (objeto_dao::dao()->get_constantes() as $nome => $valor) {
            echo '<option value="'.$valor.'">'.$nome.'</option>';
        }
        echo '</select>';

        $class = ($sessao['condicao']['operando'] == 1) ? 'class="ativo"' : '';
        echo '<input type="text" name="operacao[incluir][operando2]" id="input_operando2" value="'.$operando2.'" size="10" '.$class.'/>';
        echo '</td>';
        echo '<td rowspan="3">';
        echo '<input name="operacao[incluir][submit]" type="submit" value="Incluir" class="botao" />';
        echo '</td>';
        echo '</tr>';

        // Linha: TIPO
        echo '<tr>';
        echo '<th scope="row">Tipo</th>';
        echo '<td>';
        echo '<select name="operacao[incluir][tipo1]" id="select_tipo1">';
        echo '  <option value="'.CONDICAO_SQL_TIPO_ATRIBUTO.'" selected="selected">Atributo</option>';
        echo '  <option value="'.CONDICAO_SQL_TIPO_VALOR.'" disabled="disabled">Valor</option>';
        echo '</select>';
        echo '</td>';
        echo '<td>-</td>';
        echo '<td>';
        echo '<select name="operacao[incluir][tipo2]" id="select_tipo2">';
        if (empty($vt_enum)) {
            echo '  <option value="'.CONDICAO_SQL_TIPO_ATRIBUTO.'">Atributo</option>';
            echo '  <option value="'.CONDICAO_SQL_TIPO_VALOR.'" selected="selected">Valor</option>';
            echo '  <option value="const">Constante do BD</option>';
        } else {
            echo '  <option value="enum" selected="selected">Valor Enumerado</option>';
            echo '  <option value="'.CONDICAO_SQL_TIPO_ATRIBUTO.'">Atributo</option>';
            echo '  <option value="'.CONDICAO_SQL_TIPO_VALOR.'">Valor</option>';
            echo '  <option value="const">Constante do BD</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        // Linha: FUNCAO
        echo '<tr>';
        echo '<th scope="row">Fun&ccedil;&atilde;o</th>';
        echo '<td>';
        echo '<select name="operacao[incluir][funcao1]">';
        echo '  <option value="" selected="selected">Nenhuma</option>';
        foreach (driver_objeto::get_funcoes(true) as $grupo => $funcoes) {
            echo '  <optgroup label="'.$grupo.'">';
            foreach ($funcoes as $funcao => $descricao) {
                echo '    <option value="'.$funcao.'">'.$descricao.'</option>';
            }
            echo '  </optgroup>';
        }
        echo '</select>';
        echo '</td>';
        echo '<td>-</td>';
        echo '<td>';
        echo '<select name="operacao[incluir][funcao2]">';
        echo '  <option value="" selected="selected">Nenhuma</option>';
        echo '  <option value="" selected="selected">Nenhuma</option>';
        foreach (driver_objeto::get_funcoes(true) as $grupo => $funcoes) {
            echo '  <optgroup label="'.$grupo.'">';
            foreach ($funcoes as $funcao => $descricao) {
                echo '    <option value="'.$funcao.'">'.$descricao.'</option>';
            }
            echo '  </optgroup>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '</tbody>';
        echo '</table>';

        echo '<p>';
        link::texto(link::adicionar_atributo($link_base, 'op', 'limpar_condicao_simples'), 'Limpar', false, false, false, false, false, false);
        echo '</p>';
        echo '</fieldset>';
        echo '</form>';
        break;

    case 3://ordem
        $ajuda = '<p>Selecione os campos usados para ordena&ccedil;&atilde;o no quadro abaixo.</p>'.
                 '<p>Em seguida, escolha o tipo de ordena&ccedil;&atilde;o (crescente ou decrescente).</p>';
        mensagem::comentario($link_base, $ajuda);
        if (!empty($sessao['ordem'])) {
            $obj = objeto::get_objeto($sessao['classe']);
            echo '<ul>';
            foreach ($sessao['ordem'] as $campo => $tipo) {
                echo '<li>';
                echo formatar_campo($obj, $campo, $sessao['opcoes']['forma_mostrar_campo']);
                echo ' ';
                $link_ordem = link::adicionar_atributo($link_base, array('op', 'atributo'), array('mudar_tipo_ordem', $campo));
                if ($tipo) {
                    echo link::icone($link_ordem, icone::endereco('crescente'), 'Tornar Decrescente', '', false, false, false, false, false);
                } else {
                    echo link::icone($link_ordem, icone::endereco('decrescente'), 'Tornar Crescente', '', false, false, false, false, false);
                }
                echo ' <span class="hide">|</span> ';
                echo link::icone(link::adicionar_atributo($link_base, array('op', 'atributo'), array('remover_ordem', $campo)), icone::endereco('excluir'), 'Remover Campo', '', false, false, false, false, false);
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nenhum campo selecionado para ordena&ccedil;&atilde;o.</p>';
        }

        echo '<hr />';
        imprimir_formas_mostrar_campo($sessao);
        break;
    case 4://opcoes
        $ajuda = '<p>Selecione o tipo de filtro e o n&uacute;mero m&aacute;ximo de resultados exibidos por vez. '.
                 'Em seguida, clique em Salvar.</p>';
        mensagem::comentario($link_base, $ajuda);
        echo '<form id="opcoes_consulta" action="'.$link_base.'" method="post" onsubmit="return submeter(this, 1);">';
        echo '<div>';
        echo '<p>Forma de Exibi&ccedil;&atilde;o: ';
        switch ($sessao['opcoes']['filtrar']) {
        case 1:
            echo 'dados filtrados';
            break;
        case 2:
            echo 'dados brutos do <abbr title="Banco de Dados">BD</abbr>';
            break;
        }
        echo '</p>';
        echo '<p>Limite de Resultados: '.($sessao['opcoes']['limite'] ? $sessao['opcoes']['limite'] : 'sem limite').'</p>';
        echo '</div>';
        echo '<fieldset><legend>Op&ccedil;&otilde;es</legend>';
        echo '<div>';
        $checked = ($sessao['opcoes']['filtrar'] == 1) ? ' checked="checked"' : '';
        echo '<p><label><input'.$checked.' type="radio" name="operacao[opcoes][filtrar]" value="1" /> Dados Filtrados</label> (Padr&atilde;o)</p>';
        $checked = ($sessao['opcoes']['filtrar'] == 2) ? ' checked="checked"' : '';
        echo '<p><label><input'.$checked.' type="radio" name="operacao[opcoes][filtrar]" value="2" /> Dados Brutos do BD</label></p>';
        echo '</div>';
        echo '<p>';
        echo '<label for="limite">Limite de Resultados:</label> ';
        echo '<select name="operacao[opcoes][limite]">';
        echo '<option value="0">Sem limite</option>';
        for ($i = 10; $i <= 300; $i += 10) {
            $selected = $sessao['opcoes']['limite'] == $i ? ' selected="selected"' : '';
            echo '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
        }
        echo '</select>';
        echo '</p>';
        echo '<div><input type="submit" name="operacao[opcoes][submit]" value="Salvar" /></div>';
        echo '</fieldset>';
        echo '</form>';
        break;
    }
    echo '</div>';
    exibir_entidade($sessao);
    echo '</div>';

    echo '<form id="consulta_generica" action="'.$link_base.'" method="post" onsubmit="return submeter(this, 1);">';

    $disabled = is_array($sessao['campos']) && !empty($sessao['campos']) ? '' : ' disabled="disabled"';
    echo '<div>';
    echo '<input type="submit" name="operacao[consultar][submit]" value="Consultar" class="botao"'.$disabled.' /> ';
    echo '<input type="submit" name="operacao[consultar][csv]" value="Gerar CSV" class="botao noajax"'.$disabled.' />';
    echo '</div>';
    if ($disabled) {
        echo '<p>&Eacute; necess&aacute;rio escolher pelo menos um campo para sele&ccedil;&atilde;o.</p>';
    }
    if (is_array($sessao['condicao']['condicoes']) && count($sessao['condicao']['condicoes']) > 1) {
        echo '<p><abbr title="Observa&ccedil;&atilde;o">Obs.</abbr>: O resultado ser&aacute; gerado a partir da UNI&Atilde;O '.
             'das consultas que n&atilde;o foram agrupadas.</p>';
    }
    echo '</form>';
    echo '</div>';
}


//
//     Imprime as formas de mostrar os campos
//
function imprimir_formas_mostrar_campo($sessao) {
// Array[Mixed] $sessao: dados da sessao
//
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);
    $opcoes = array(
        'descricao' => 'Descri&ccedil;&atilde;o',
        'nome' => 'Nome do campo'
    );
    $links = array();
    foreach ($opcoes as $chave => $valor) {
        $link = link::adicionar_atributo($link_base, array('op', 'tipo'), array('forma_mostrar_campo', $chave));
        $class = ($sessao['opcoes']['forma_mostrar_campo'] == $chave) ? 'destaque' : '';
        $links[] = link::texto($link, $valor, false, false, $class, true, false, false);
    }
    echo '<p>Exibir: '.implode(' | ', $links).'</p>';
}


//
//     Imprime parte do formulario de condicoes hierarquicas
//
function imprimir_condicoes(&$condicoes, $sessao) {
// Array[condicao_sql] || condicao_sql $condicoes: condicoes a serem impressas na forma de formulario
// Array[String => Mixed] $sessao: dados da sessao
//
    switch (util::get_tipo($condicoes)) {
    case 'array':
        foreach ($condicoes as $c) {
            imprimir_condicoes($c, $sessao);
        }
        break;
    case 'object':
        if ($condicoes->tipo == CONDICAO_SQL_SIMPLES) {
            $c = &$condicoes;
            list($operando1, $operador, $operando2) = get_operandos_exibicao($c, $sessao);
            $id = $c->id;
            echo '<div class="linha">';
            echo '<span class="celula"><input type="checkbox" id="condicao_'.$id.'" name="condicoes['.$id.']" value="'.$id.'" /></span>';
            echo '<span class="abre"></span>';
            if (is_null($c->operando2)) {
                switch ($c->operador) {
                case '=':
                    echo '<span class="condicao"><label for="condicao_'.$id.'">'.$operando1.' IS NULL</label></span>';
                    break;
                case '<>':
                    echo '<span class="condicao"><label for="condicao_'.$id.'">'.$operando1.' IS NOT NULL</label></span>';
                    break;
                }
            } else {
                echo '<span class="condicao"><label for="condicao_'.$id.'">'.$operando1.' '.$operador.' '.$operando2.'</label></span>';
            }
            echo '<span class="fecha"></span>';
            echo '</div>';
        } elseif ($condicoes->tipo == CONDICAO_SQL_COMPOSTA) {
            $vetor = array_values($condicoes->vetor);
            $ultimo = count($vetor) - 1;
            $id = $condicoes->id;

            echo '<div class="linha">';
            echo '<span class="celula"><input type="checkbox" id="condicao_'.$id.'" name="condicoes['.$id.']" value="'.$id.'" /></span>';
            echo '<span class="abre"></span>';
            echo '<div class="condicao">';
            echo '  <div class="condicoes">';
            foreach ($vetor as $i => $c) {
                imprimir_condicoes($c, $sessao);
                if ($i != $ultimo) {
                    echo '<div class="linha">';
                    echo '  <span class="celula"></span>';
                    echo '  <span class="celula"></span>';
                    echo '  <span class="operando"><label for="condicao_'.$id.'">'.converter_operador($condicoes->operador).'</label></span>';
                    echo '  <span class="celula"></span>';
                    echo '</div>';
                }
            }
            echo '  </div>';
            echo '</div>';
            echo '<span class="fecha"></span>';
            echo '</div>';
        } elseif ($condicoes->tipo == CONDICAO_SQL_UNITARIA) {
            $id = $condicoes->id;

            echo '<div class="linha">';
            echo '  <span class="celula">';
            echo '    <input type="checkbox" id="condicao_'.$id.'" name="condicoes['.$id.']" value="'.$id.'" />';
            echo '    <span class="operando"><label for="condicao_'.$id.'">'.converter_operador($condicoes->operador).'</label></span>';
            echo '  </span>';
            echo '  <span class="abre"></span>';
            echo '  <div class="condicao">';
            echo '    <div class="condicoes">';
            imprimir_condicoes($condicoes->condicao, $sessao);
            echo '    </div>';
            echo '  </div>';
            echo '  <span class="fecha"></span>';
            echo '</div>';
        }
        break;

    default:
    case 'null':
        echo '<p>Nenhuma condi&ccedil;&atilde;o (isso consulta todos os registros).</p>';
        echo '<p>Utilize o formul&aacute;rio abaixo para incluir condi&ccedil;&otilde;es.</p>';
        break;
    }
}


//
//     Obtem o valor de um operando de forma humana
//
function get_operando_filtrado($obj, $operando, $valor) {
// objeto $obj: entidade
// String $operando: operando a ser verificado
// String $valor: valor do operando
//
    $pos = strpos($operando, ':');
    if ($pos !== false) {
        $parte1 = substr($operando, 0, $pos);
        $parte2 = substr($operando, $pos + 1);

        if ($obj->possui_rel_uu($parte1)) {
            return get_operando_filtrado($obj->get_objeto_rel_uu($parte1), $parte2, $valor);
        } elseif ($obj->possui_rel_un($parte1)) {
            $def = $obj->get_definicao_rel_un($parte1);
            $obj2 = objeto::get_objeto($def->classe);
            return get_operando_filtrado($obj2, $parte2, $valor);
        }
        return '';
    } else {
        $def = $obj->get_definicao_atributo($operando);
        if ($def->tipo == 'data') {
            $data = objeto::parse_data($valor);
            $tr = array(
                '%d' => sprintf('%02d', $data['dia']),
                '%m' => sprintf('%02d', $data['mes']),
                '%Y' => sprintf('%04d', $data['ano']),
                '%H' => sprintf('%02d', $data['hora']),
                '%M' => sprintf('%02d', $data['minuto']),
                '%S' => sprintf('%02d', $data['segundo'])
            );
            switch ($def->campo_formulario) {
            case 'data':
                return strtr(ATRIBUTO_FORMATO_DATA, $tr);
            case 'hora':
                return strtr(ATRIBUTO_FORMATO_HORA, $tr);
            case 'data_hora':
                return strtr(ATRIBUTO_FORMATO_DATA_HORA, $tr);
            }
        } else {
            return $def->exibir($valor);
        }
    }
}


//
//     Devolve os operandos da forma como devem ser mostrados
//
function get_operandos_exibicao($c, $sessao) {
// Object $c: dados da consulta submetidos
// Array[String => Mixed] $sessao: dados da sessao
//
    $tipo1 = condicao_sql::get_flag($c, 'tipo1');
    $funcao1 = condicao_sql::get_flag($c, 'funcao1');
    $tipo2 = condicao_sql::get_flag($c, 'tipo2');
    $funcao2 = condicao_sql::get_flag($c, 'funcao2');

    $obj = objeto::get_objeto($sessao['classe']);
    $forma = $sessao['opcoes']['forma_mostrar_campo'];

    switch ($forma) {
    case 'descricao':

        $definicao1 = formatar_campo($obj, $c->operando1, $forma, false);
        $operando1 = converter_operando($definicao1, $tipo1, $funcao1);
        $operador  = converter_operador($c->operador);
        if ($tipo2 == CONDICAO_SQL_TIPO_VALOR) {
            if ($funcao2) {
                $def = objeto_dao::dao()->get_definicao_parametro_funcao($funcao2, 0);
                $operando2 = converter_operando($def->exibir($c->operando2), $tipo2, $funcao2);
            } elseif ($funcao1) {
                $def = objeto_dao::dao()->get_definicao_retorno_funcao($funcao1);
                $operando2 = '"'.$def->exibir($c->operando2).'"';
            } else {
                $operando2_filtrado = get_operando_filtrado($obj, $c->operando1, $c->operando2);
                $operando2 = converter_operando($operando2_filtrado, $tipo2, $funcao2);
            }
        } else {
            $definicao2 = formatar_campo($obj, $c->operando2, $forma, false);
            $operando2 = converter_operando($definicao2, $tipo2, $funcao2);
        }
        break;
    case 'nome':
    default:
        $operando1 = converter_operando(formatar_campo($obj, $c->operando1, $forma, false), $tipo1, $funcao1);
        $operador  = converter_operador($c->operador);
        $operando2 = converter_operando(formatar_campo($obj, $c->operando2, $forma, false), $tipo2, $funcao2);
        break;
    }

    return array($operando1, $operador, $operando2);
}


//
//     Imprime o resultado da busca generica
//
function imprimir_resultado(&$dados, &$sessao) {
// Object $dados: dados submetidos
// Array[Mixed] $sessao: dados da sessao
//
    global $CFG;
    if (!isset($dados->operacao->consultar)) {
        return;
    }
    $d = &$dados->operacao->consulta;

    $classe = $sessao['classe'];
    $campos = isset($sessao['campos']) ? $sessao['campos'] : array();
    $ordem  = isset($sessao['ordem'])  ? $sessao['ordem']  : false;
    $condicoes_sessao = &$sessao['condicao']['condicoes'];
    if (is_array($condicoes_sessao)) {
        switch (count($condicoes_sessao)) {
        case 0:
            $condicoes = condicao_sql::vazia();
            break;
        case 1:
            reset($condicoes_sessao);
            list(, $condicoes) = each($condicoes_sessao);
            break;
        default:
            $condicoes = condicao_sql::sql_union($condicoes_sessao);
            break;
        }
    } else {
        $condicoes = condicao_sql::vazia();
    }
    $obj = objeto::get_objeto($classe);

    $atributos = $obj->get_campos_reais($campos);
    $index  = false;
    $limite = $sessao['opcoes']['limite'] ? $sessao['opcoes']['limite'] : false;
    if ($limite) {
        $inicio = $sessao['opcoes']['pagina_atual'] ? $sessao['opcoes']['pagina_atual'] * $limite : false;
    } else {
        $inicio = false;
    }

    $simp_ordem = array();
    foreach ($sessao['ordem'] as $campo => $tipo_ordem) {
        $simp_ordem[] = "'{$campo}' => ".($tipo_ordem ? 'true' : 'false');
    }

    // Exibir a SQL usada
    $dao = new objeto_dao();
    $dao->set_exibicao_usuario(true);
    $sql = $dao->sql_select($obj, $atributos, $condicoes, $ordem, $index, $limite, $inicio);
    echo '<div class="dados">';
    echo '<p><strong>SQL:</strong></p>';
    echo '<code>'.nl2br(texto::codificar($sql)).'</code>';
    echo '</div>';

    // Exibir o comando SQL do SIMP
    echo '<div class="dados">';
    echo '<p><strong>Simp</strong></p>';
    echo '<p><strong>Campos:</strong></p>';
    echo '<code>'."array('".implode("', '", $sessao['campos'])."')".'</code>';
    echo '<p><strong>Condi&ccedil;&otilde;es:</strong></p>';
    echo '<code>'.$condicoes.'</code>';
    echo '<p><strong>Campos de Ordena&ccedil;&atilde;o:</strong></p>';
    echo '<code>';
    echo 'array(';
    echo !empty($simp_ordem) ? '<br />'.implode(',<br />', $simp_ordem).'<br />' : '';
    echo ')';
    echo '</code>';
    echo '</div>';

    // Consultar efetivamente
    $tempo = microtime(true);
    $resultados = $obj->consultar_varios_iterador($condicoes, $campos, $ordem, $limite, $inicio);
    $tempo = microtime(true) - $tempo;
    $tempo = round($tempo, 3);

    if (!$resultados || !$resultados->size()) {
        echo '<p>Nenhum resultado</p>';
        return;
    }

    echo '<table class="tabela" summary="Tabela com os resultados da consulta">';
    echo '<caption>Resultados da consulta</caption>';
    echo '<thead>';
    if ($sessao['opcoes']['limite']) {
        $pagina_atual = $sessao['opcoes']['pagina_atual'] + 1;
        $limite = $sessao['opcoes']['limite'];
        $quantidade = $obj->quantidade_registros($condicoes);
        $total_paginas = max(1, ceil($quantidade / $limite));

        $link_base = $CFG->site;
        link::normalizar($link_base, true);

        if ($pagina_atual > 1) {
            $link_voltar = link::adicionar_atributo($link_base, 'op', 'voltar_pagina');
            $voltar = link::texto($link_voltar, paginacao::seta_esquerda(), 'Voltar', '', '', 1, 0, 0);
        } else {
            $voltar = paginacao::seta_esquerda();
        }

        if ($pagina_atual < $total_paginas) {
            $link_avancar = link::adicionar_atributo($link_base, 'op', 'avancar_pagina');
            $avancar = link::texto($link_avancar, paginacao::seta_direita(), 'Avan&ccedil;ar', '', '', 1, 0, 0);
        } else {
            $avancar = paginacao::seta_direita();
        }

        echo '<tr>';
        echo '  <th colspan="'.(count($campos) + 1).'">';
        echo $voltar.' <strong>P&aacute;gina '.$pagina_atual.'/'.$total_paginas.'</strong> '.$avancar;
        echo '  </th>';
        echo '</tr>';
    }
    echo '<tr>';
    echo '<th>#</th>';
    foreach ($campos as $c) {
        if ($obj->possui_atributo($c)) {
            $def = $obj->get_definicao_atributo($c);
            $descricao = $def->descricao;
        } elseif ($obj->possui_atributo_implicito($c)) {
            $def = $obj->get_definicao_implicito($c);
            $descricao = $def->descricao;
        }
        echo '<th>'.$descricao.'<br />('.str_replace(':', ' &rarr; ', $c).')</th>';
    }
    echo '</tr>';
    echo '</thead>';

    $colspan = count($campos) + 1;
    $quantidade_exibidos = count($resultados);
    $rodape = texto::numero($quantidade_exibidos).' resultado'.($quantidade_exibidos != 1 ? 's' : '');
    if ($sessao['opcoes']['limite']) {
        $rodape .= ' (de '.texto::numero($quantidade).')';
    }
    $rodape .= ' em '.texto::numero($tempo).' segundo'.($tempo != 1 ? 's' : '');

    echo '<tfoot>';
    echo '<tr>';
    echo '<td colspan="'.$colspan.'">'.$rodape.'</td>';
    echo '</tr>';
    echo '</tfoot>';
    echo '<tbody>';
    $i = 1;
    foreach ($resultados as $resultado) {
        echo '<tr>';
        echo '<td>'.($i++).'</td>';
        foreach ($campos as $c) {
            $valor = ($sessao['opcoes']['filtrar'] == 1) ? $resultado->imprimir_atributo($c, 1, 0) : texto::codificar($resultado->$c);
            echo '<td>'.$valor.'</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}


//
//     Exibe as opcoes de rodape do formulario de consulta
//
function listar_opcoes(&$sessao) {
// Array[Mixed] $sessao: dados da sessao
//
    global $CFG, $pagina;

    // Opcoes de rodape
    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    $links = array();

    $link = link::adicionar_atributo($link_base, 'op', 'listar_entidades');
    $links[] = link::texto($link, 'Voltar', 'Listar Entidades', false, false, true, false, false);

    $link = link::adicionar_atributo($link_base, 'op', 'limpar_consulta');
    $links[] = link::texto($link, 'Limpar Consulta', 'Limpar todos os dados da consulta', false, false, true, false, false);

    $pagina->listar_opcoes($links);
}


//
//     Formata um campo de forma legivel
//
function formatar_campo($obj, $campo, $forma = 'descricao', $incluir_entidade = true) {
// objeto $obj: Objeto entidade
// String $campo: campo
// String $forma: forma de mostrar o campo
// Bool $incluir_entidade: inclui a entidade principal
//
    switch ($forma) {
    case 'descricao':
        $obj_aux = $obj;
        $vt_campos = explode(':', $campo);
        $vt_nomes = array();
        foreach ($vt_campos as $c) {
            if ($obj_aux->possui_atributo($c)) {
                $def = $obj_aux->get_definicao_atributo($c);
                $vt_nomes[] = '<span title="'.$c.'">'.$def->descricao.'</span>';
            } elseif ($obj_aux->possui_atributo_implicito($c)) {
                $def = $obj_aux->get_definicao_implicito($c);
                $vt_nomes[] = '<span title="'.$c.'">'.$def->descricao.'</span>';
            } elseif ($obj_aux->possui_rel_uu($c)) {
                $def = $obj_aux->get_definicao_rel_uu($c);
                $vt_nomes[] = '<span title="'.$c.'">'.$def->descricao.'</span>';
                $obj_aux = $obj_aux->get_objeto_rel_uu($c);
            } elseif ($obj_aux->possui_rel_un($c)) {
                $def = $obj_aux->get_definicao_rel_un($c);
                $obj_aux = objeto::get_objeto($def->classe);
                if ($def->descricao) {
                    $desc_vetor = $def->descricao;
                } else {
                    $desc_vetor = $obj_aux->get_entidade(true);
                }
                $vt_nomes[] = '<span title="'.$c.'">'.$desc_vetor.'</span>';
            }
        }
        if ($incluir_entidade) {
            return '<span title="'.$obj->get_classe().'">'.$obj->get_entidade().'</span> &rarr; '.implode(' &rarr; ', $vt_nomes);
        } else {
            return implode(' &rarr; ', $vt_nomes);
        }
        break;

    case 'nome':
    default:
        if ($incluir_entidade) {
            return $obj->get_classe().' &rarr; '.implode(' &rarr; ', explode(':', $campo));
        } else {
            return implode(' &rarr; ', explode(':', $campo));
        }
        break;
    }
}


//
//     Converte um operando para a notacao HTML
//
function converter_operando($operando, $tipo, $funcao) {
// String $operando: nome ou valor do operando
// Int $tipo: tipo de operando
// String $funcao: funcao aplicada sobre o operando
//
    $r = '';
    switch ($tipo) {
    case CONDICAO_SQL_TIPO_ATRIBUTO:
        if ($funcao) {
            $r = $funcao.'('.$operando.')';
        } else {
            $r = $operando;
        }
        break;
    case CONDICAO_SQL_TIPO_VALOR:
        if ($funcao) {
            $r = $funcao.'("'.$operando.'")';
        } else {
            $r = '"'.$operando.'"';
        }
        break;
    }
    return $r;
}


//
//     Converte um operador para a notacao HTML
//
function converter_operador($operador) {
// String $operador: codigo do operador aceito pela classe condicao_sql
//
    $conversao = array(
        '<'    => '&lt;',
        '>'    => '&gt;',
        '<='   => '&le;',
        '>='   => '&ge;',
        '='    => '=',
        '<>'   => '&ne;',
        'LIKE' => '&sim;',
        'AND'  => 'E',
        'OR'   => 'OU',
        'NOT'  => 'N&Atilde;O'
    );
    if (isset($conversao[$operador])) {
        return $conversao[$operador];
    }
    return '?';
}


//
//     Agrupa as condicoes dos IDs informados com algum operador
//
function agrupar_condicoes(&$sessao, $ids, $operador) {
// Array[Mixed] $sessao: dados da sessao
// Array[String] $ids: identificadores das condicoes a serem agrupadas
// String $operador: operador de agrupamento (AND, OR, NOT)
//
    $erros = array();
    $compostas = array('AND' => 1, 'OR' => 1);
    $unitarias = array('NOT' => 1);

    $operador = strtoupper(trim($operador));

    if (empty($ids)) {
        $erros[] = 'Nenhuma condi&ccedil;&atilde;o foi selecionada para ser agrupada';
        mensagem::erro($erros);
        return false;
    }

    // Agrupamento de operador Composto (AND e OR)
    if (isset($compostas[$operador])) {
        $primeiro_id = array_shift($ids);
        try {
            $referencia = &obter_condicao($sessao, $primeiro_id);
        } catch (Exception $e) {
            return false;
        }
        $condicao = clone($referencia);
        $vt_condicoes = array($condicao);
        foreach ($ids as $id) {
            try {
                $condicao = &obter_condicao($sessao, $id, true);
                $vt_condicoes[] = $condicao;
            } catch (Exception $e) {
                return false;
            }
        }
        if (count($vt_condicoes) > 1) {
            $nova = condicao_sql::montar_composta($vt_condicoes, $operador, $sessao['condicao']['ultimo_id'] + 1);
            $referencia = $nova;
            $sessao['condicao']['ultimo_id'] += 1;
        } else {
            $erros[] = '&Eacute; necess&aacute;rio selecionar pelo menos duas condi&ccedil;&otilde;es para este tipo de agrupamento';
        }

    // Agrupamento de operador Unitario (NOT)
    } elseif (isset($unitarias[$operador])) {
        foreach ($ids as $id) {
            try {
                $referencia = &obter_condicao($sessao, $id);
            } catch (Exception $e) {
                $erros[] = 'Erro ao obter condi&ccedil;&atilde;o (ID: '.$id.')';
                continue;
            }
            $nova = condicao_sql::montar_unitaria(clone($referencia), $operador, $sessao['condicao']['ultimo_id'] + 1);
            $referencia = $nova;
            $sessao['condicao']['ultimo_id'] += 1;
        }
    }
    if (!empty($erros)) {
        mensagem::erro($erros);
    }
}


//
//     Desagrupa todas as condicoes
//
function desagrupar_condicoes(&$sessao) {
// Array[String => Mixed] $sessao: dados da sessao
//
    $condicoes = $sessao['condicao']['condicoes'];
    $vt_condicoes = array();
    foreach ($condicoes as $condicao) {
        $vt_condicoes = array_merge($vt_condicoes, obter_condicoes($condicao));
    }

    // Refazer IDs das condicoes
    $id = 1;
    foreach ($vt_condicoes as $i => $c) {
        $vt_condicoes[$i]->id = $id++;
    }
    $id -= 1;

    $sessao['condicao']['ultimo_id'] = $id;
    $sessao['condicao']['condicoes'] = $id ? $vt_condicoes : null;
}


//
//     Obtem o vetor de condicoes recursivamente
//
function obter_condicoes($condicao) {
// condicao_sql $condicao: condicao a ser percorrida
//
    if (is_object($condicao) && get_class($condicao) == 'condicao_sql') {
        switch ($condicao->tipo) {
        case CONDICAO_SQL_SIMPLES:
            return array($condicao);

        case CONDICAO_SQL_COMPOSTA:
            $vt_condicoes = array();
            foreach ($condicao->vetor as $c) {
                $vt_condicoes = array_merge($vt_condicoes, obter_condicoes($c));
            }
            return $vt_condicoes;

        case CONDICAO_SQL_UNITARIA:
            return obter_condicoes($condicao->condicao);
        }
    }
    return array();
}


//
//     Obtem uma condicao da sessao pelo ID
//
function &obter_condicao(&$sessao, $id, $apagar = false) {
// Array[Mixed] $sessao: dados da sessao
// String $id: identificador da condicao a ser obtida
// Bool $apagar: flag indicando se a condicao deve ser apagada
//
    try {
        return buscar_condicao($id, $sessao['condicao']['condicoes'], $apagar);
    } catch (Exception $e) {
        // Ignorar
    }
    throw new Exception('Condi&ccedil;&atilde;o n&atilde;o encontrada');
}


//
//     Busca uma condicao pelo ID
//
function &buscar_condicao($id, &$condicoes, $apagar = false) {
// String $id: identificador da condicao a ser obtida
// Array[condicao_sql] $condicoes: local da busca
// Bool $apagar: flag indicando se a condicao deve ser apagada
//
    foreach ($condicoes as $i => $c) {
        if ($c->id == $id) {
            if ($apagar) {
                $retorno = clone($condicoes[$i]);
                unset($condicoes[$i]);
                return $retorno;
            } else {
                return $condicoes[$i];
            }
        }
        if ($condicoes[$i]->tipo == CONDICAO_SQL_COMPOSTA) {
            try {
                return buscar_condicao($id, $condicoes[$i]->vetor, $apagar);
            } catch (Exception $e) {
                // Ignorar
            }
        } elseif ($condicoes[$i]->tipo == CONDICAO_SQL_UNITARIA) {
            $ref = &$condicoes[$i];
            do {
                if ($ref->condicao->id == $id) {
                    if ($apagar) {
                        $retorno = clone($ref->condicao);
                        unset($ref->condicao);
                        return $retorno;
                    } else {
                        $retorno = &$ref->condicao;
                        return $retorno;
                    }
                }
                $ref = &$ref->condicao;
            } while ($ref->tipo == CONDICAO_SQL_UNITARIA);
            if ($ref->tipo == CONDICAO_SQL_COMPOSTA) {
                try {
                    return buscar_condicao($id, $ref->vetor, $apagar);
                } catch (Exception $e) {
                    // Ignorar
                }
            }
        }
    }
    throw new Exception('Condi&ccedil;&atilde;o n&atilde;o encontrada');
}


//
//     Remove as condicoes dos IDs informados
//
function remover_condicoes(&$sessao, $ids) {
// Array[Mixed] $sessao: dados da sessao
// Array[String] $ids: identificadores das condicoes a serem apagadas
//
    $erros = array();
    if (empty($ids)) {
        mensagem::erro('Nenhuma condi&ccedil;&atilde;o foi selecionada para exclus&atilde;o');
        return false;
    }

    foreach ($ids as $id) {
        try {
            obter_condicao($sessao, $id, true);
        } catch (Exception $e) {
            $erros[] = $e->getMessage();
        }
    }

    // Se apagou todas as condicoes
    if (empty($sessao['condicao']['condicoes'])) {
        $sessao['condicao']['condicoes'] = null;
    }

    if (!empty($erros)) {
        mensagem::erro($erros);
    }
}


//
//     Verifica se o operando da entidade e' um campo enum
//
function operando_enum($sessao, $operando, &$vt_enum) {
// Array[Mixed] $sessao: dados da sessao
// String $operando: operando a ser verificado
// Array[Mixed => String] $vt_enum: vetor de possibilidades
//
    $obj = objeto::get_objeto($sessao['classe']);
    return operando_enum_objeto($obj, $operando, $vt_enum);
}


//
//     Verifica se o operando de um objeto e' um campo enum
//
function operando_enum_objeto($obj, $operando, &$vt_enum) {
// objeto $obj: entidade avaliada
// String $operando: operando a ser verificado
// Array[Mixed => String] $vt_enum: vetor de possibilidades
//
    $possui = false;

    // Atributo simples
    if ($obj->possui_atributo($operando)) {
        $pos = strrpos($operando, ':');
        if ($pos === false) {
            $possui = method_exists($obj, 'get_vetor_'.$operando);
            $callback = array($obj, 'get_vetor_'.$operando);
        } else {
            $obj_filho = substr($operando, 0, $pos);
            $atributo  = substr($operando, $pos + 1);
            if ($obj->possui_rel_uu($obj_filho)) {
                return operando_enum_objeto($obj->__get($obj_filho), $atributo, $vt_enum);
            } elseif ($obj->possui_rel_un($obj_filho)) {
                $def = $obj->get_definicao_rel_un($obj_filho);
                $obj2 = objeto::get_objeto($def->classe);
                return operando_enum_objeto($obj2, $atributo, $vt_enum);
            }
        }
        if ($possui) {
            $vt_enum = call_user_func($callback);
        }
    }
    return $possui;
}


//
//     Gera o CSV
//
function gerar_csv(&$dados, &$sessao) {
// Object $dados: dados submetidos
// Array[Mixed] $sessao: dados da sessao
//
    global $CFG;
    if (!isset($dados->operacao->consultar)) {
        return;
    }
    $d = &$dados->operacao->consulta;

    $classe = $sessao['classe'];
    $campos = isset($sessao['campos']) ? $sessao['campos'] : array();
    $ordem  = isset($sessao['ordem'])  ? $sessao['ordem']  : false;
    $condicoes_sessao = &$sessao['condicao']['condicoes'];
    if (is_array($condicoes_sessao)) {
        switch (count($condicoes_sessao)) {
        case 0:
            $condicoes = condicao_sql::vazia();
            break;
        case 1:
            reset($condicoes_sessao);
            list(, $condicoes) = each($condicoes_sessao);
            break;
        default:
            $condicoes = condicao_sql::sql_union($condicoes_sessao);
            break;
        }
    } else {
        $condicoes = condicao_sql::vazia();
    }
    $obj = objeto::get_objeto($classe);

    $atributos = $obj->get_campos_reais($campos);
    $index  = false;

    $simp_ordem = array();
    foreach ($sessao['ordem'] as $campo => $tipo_ordem) {
        $simp_ordem[] = "'{$campo}' => ".($tipo_ordem ? 'true' : 'false');
    }

    // Gerar documento
    $opcoes = array(
        'arquivo' => 'consulta.'.$CFG->time.'.csv',
        'disposition' => 'attachment',
        'compactacao' => true
    );
    http::cabecalho('text/csv; charset='.$CFG->charset.'; header=present', $opcoes);
    echo imprimir_registro_csv($campos, ',', '"');

    // Consultar efetivamente
    $resultados = $obj->consultar_varios_iterador($condicoes, $campos, $ordem);
    foreach ($resultados as $resultado) {
        $registro = array();
        foreach ($campos as $campo) {
            $registro[] = ($sessao['opcoes']['filtrar'] == 1) ? texto::decodificar($resultado->exibir($campo)) : $resultado->__get($campo);
        }
        echo imprimir_registro_csv($registro, ',', '"');
    }
    exit(0);
}


//
//     Imprime um registro CSV
//
function imprimir_registro_csv($registro, $separador = ',', $delimitador = '"') {
// Object || Array[String] $registro: valores
// String $separador: separador de dados
// String $delimitador: delimitador de uma celula
//
    $registro2 = array();
    foreach ($registro as $valor) {
        $registro2[] = $delimitador.str_replace($delimitador, $delimitador.$delimitedor, $valor).$delimitador;
    }
    return implode($separador, $registro2).PHP_EOL;
}
