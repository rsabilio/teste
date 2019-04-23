<?php
//
// SIMP
// Descricao: Arquivo que determina o tamanho do BD
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 05/01/2011
// Modificado: 18/01/2011
// License: LICENSE.TXT
// Copyright (C) 2011  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Tamanho do BD';
$nav[$CFG->wwwmods.$modulo.'/index.php'] = 'Desenvolvimento';
$nav[''] = 'Tamanho do BD';
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Bloquear caso necessario
require_once($CFG->dirmods.$modulo.'/bloqueio.php');


/// Imprimir Pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo($titulo);
logica_listar_tabelas();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Logica para listar as tabelas
//
function logica_listar_tabelas() {
    $dados = formulario::get_dados();
    imprimir_form($dados);
    if ($dados) {
        listar_tabelas($dados);
    }
}


//
//     Imprime o formulario
//
function imprimir_form($dados) {
// stdClass $dados: dados submetidos
//
    global $CFG;
    $vt_ordem = array(
        'nome' => 'Nome',
        'tamanho' => 'Tamanho'
    );

    $padrao = array(
        'ordem' => 'nome'
    );
    $dados = formulario::montar_dados($padrao, $dados);

    $form = new formulario($CFG->site, 'form_tamanho_bd');
    $form->campo_select('ordem', 'ordem', $vt_ordem, $dados->ordem, 'Ordem');
    $form->campo_submit('enviar', 'enviar', 'Listar');
    $form->imprimir();
}


//
//     Lista as tabelas
//
function listar_tabelas($dados) {
// stdClass $dados: dados submetidos
//
    global $CFG;

    if (!$CFG->instalacao) {
        echo '<p>O sistema ainda n&atilde;o foi instalado.</p>';
        return;
    }

    $bd = new objeto_dao();
    $bd->carregar('operacao');
    $tabelas = $bd->get_tabelas();

    if (!is_array($tabelas) || empty($tabelas)) {
        echo "<p>Nenhuma tabela instalada no sistema.</p>\n";
        return;
    }

    $total = 0;
    $nomes = array();
    $tamanhos = array();
    foreach ($tabelas as &$tabela) {
        $tabela->tamanho_humano = memoria::formatar_bytes($tabela->tamanho);
        $total += $tabela->tamanho;

        $nomes[] = $tabela->nome;
        $tamanhos[] = $tabela->tamanho;
    }
    foreach ($tabelas as &$tabela) {
        $tabela->tamanho_percentual = ($total ? texto::numero($tabela->tamanho * 100 / $total, 2) : 0).'%';
    }

    // Ordenar
    switch ($dados->ordem) {
    case 'nome':
        array_multisort($nomes, SORT_ASC, SORT_REGULAR, $tabelas);
        break;
    case 'tamanho':
        array_multisort($tamanhos, SORT_DESC, SORT_NUMERIC, $tabelas);
        break;
    }

    echo '<table class="tabela">';
    echo '<caption>Tabelas do BD</caption>';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="2">#</th>';
    echo '<th rowspan="2">Nome</th>';
    echo '<th colspan="3">Tamanho</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><abbr title="Tamanho em Bytes">B</abbr></th>';
    echo '<th><abbr title="Tamanho em Nota&ccedil;&atilde;o Humana">H</abbr></th>';
    echo '<th><abbr title="Percentual">%</abbr></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $i = 0;
    foreach ($tabelas as $tabela) {
        echo '<tr>';
        echo '<td>'.texto::numero(++$i).'</td>';
        echo '<td>'.$tabela->nome.'</td>';
        echo '<td>'.$tabela->tamanho.'</td>';
        echo '<td>'.$tabela->tamanho_humano.'</td>';
        echo '<td>'.$tabela->tamanho_percentual.'</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}