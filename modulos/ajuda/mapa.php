<?php
//
// SIMP
// Descricao: Mapa do Site
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.6
// Data: 27/06/2007
// Modificado: 09/05/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Mapa do Site';
if (isset($_SESSION[$modulo]['login']) && $_SESSION[$modulo]['login']) {
    $nav[] = 'login#index.php';
} else {
    $nav[] = '#index.php';
}
$nav[]   = $modulo.'#index.php';
$nav[]   = $modulo.'#'.basename(__FILE__);
$estilos = $CFG->wwwmods.$modulo.'/estilos.css';


/// Imprimir pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo($titulo);
imprimir_mapa();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Imprime o mapa do site
//
function imprimir_mapa() {

    // Consultar os grupos
    $condicoes = condicao_sql::vazia();
    $campos = array('nome');
    $ordem = array('nome' => true);
    $grupos = objeto::get_objeto('grupo')->consultar_varios_iterador($condicoes, $campos, $ordem);

    foreach ($grupos as $grupo) {
        $grupo->consultar_vetor_rel_un('permissoes', array('visivel', 'posicao', 'arquivo:descricao'));

        echo "<h3>{$grupo->nome}</h3>\n";
        echo "<ul>\n";
        foreach ($grupo->permissoes as $permissao) {
            $permissao->arquivo->consultar_campos(array('descricao'));
            $descricao = $permissao->arquivo->descricao;

            $class = $permissao->visivel ? '' : 'class="inativo"';
            echo "  <li {$class}>{$descricao}</li>\n";
        }
        echo "</ul>\n";
    }
}

