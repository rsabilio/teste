<?php
//
// SIMP
// Descricao: Descricao das etiquetas suportadas
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.0
// Data: 25/04/2011
// Modificado: 25/04/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Etiquetas';
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
logica_etiquetas();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Inicializa a sessao
//
function iniciar_sessao() {
    $_SESSION[__FILE__] = array(
        'abertos' => array()
    );
}


//
//     Retorna se o tipo esta aberto ou fechado
//
function tipo_aberto($tipo) {
// String $tipo: tipo de etiqueta
//
    return isset($_SESSION[__FILE__]['abertos'][$tipo]);
}


//
//     Abre um tipo de etiqueta
//
function abrir_tipo($tipo) {
// String $tipo: tipo de etiqueta
//
    $_SESSION[__FILE__]['abertos'][$tipo] = true;
}


//
//     Fecha um tipo de etiqueta
//
function fechar_tipo($tipo) {
// String $tipo: tipo de etiqueta
//
    unset($_SESSION[__FILE__]['abertos'][$tipo]);
}


//
//     Logica da ferramenta de etiquetas
//
function logica_etiquetas() {
    tratar_eventos();
    imprimir_etiquetas();
}


//
//     Trata os eventos
//
function tratar_eventos() {
    if (isset($_GET['op'])) {
        switch ($_GET['op']) {
        case 'abrir':
            $tipos = fpdf_etiqueta::get_tipos_etiquetas();
            if (isset($tipos[$_GET['cod_etiqueta']])) {
                $cod_etiqueta = $tipos[$_GET['cod_etiqueta']];
                abrir_tipo($cod_etiqueta);
            }
            break;
        case 'fechar':
            $tipos = fpdf_etiqueta::get_tipos_etiquetas();
            if (isset($tipos[$_GET['cod_etiqueta']])) {
                $cod_etiqueta = $tipos[$_GET['cod_etiqueta']];
                fechar_tipo($cod_etiqueta);
            }
            break;
        }
    }
}


//
//     Imprime os detalhes das etiquetas
//
function imprimir_etiquetas() {
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    $tipos = fpdf_etiqueta::get_tipos_etiquetas();

    echo '<ul>';
    foreach ($tipos as $i => $tipo) {
        $def = get_definicao_tipo($tipo);

        echo '<li>';
        if (tipo_aberto($tipo)) {
            $link = link::adicionar_atributo($link_base, array('op', 'cod_etiqueta'), array('fechar', $i));

            echo '<div>'.link::texto($link, $tipo, $tipo, false, false, true).' ('.$def->colunas.'x'.$def->linhas.')</div>';
            echo '<div>';
            echo '<fieldset>';
            echo '<legend>Defini&ccedil;&atilde;o</legend>';
            echo '<p><strong>Papel:</strong> '.$def->papel.'</p>';
            echo '<p><strong>Orienta&ccedil;&atilde;o do Papel:</strong> '.$def->orientacao.'</p>';
            echo '</fieldset>';
            echo '<fieldset>';
            echo '<legend>Quantidades</legend>';
            echo '<p><strong>N&uacute;mero de Colunas por Folha:</strong> '.$def->colunas.'</p>';
            echo '<p><strong>N&uacute;mero de Linhas por Folha:</strong> '.$def->linhas.'</p>';
            echo '<p><strong>N&uacute;mero de Etiquetas por Folha:</strong> '.$def->etiquetas.'</p>';
            echo '</fieldset>';
            echo '<fieldset>';
            echo '<legend>Medidas</legend>';
            echo '<p><strong>Largura de uma Etiqueta:</strong> '.$def->largura.'</p>';
            echo '<p><strong>Altura de uma Etiqueta:</strong> '.$def->altura.'</p>';
            echo '<hr />';
            echo '<p><strong>Dist&acirc;ncia Horizontal:</strong> '.$def->distancia_horizontal.'</p>';
            echo '<p><strong>Dist&acirc;ncia Vertical:</strong> '.$def->distancia_vertical.'</p>';
            echo '<hr />';
            echo '<p><strong>Margem Esquerda:</strong> '.$def->margem_esquerda.'</p>';
            echo '<p><strong>Margem Superior:</strong> '.$def->margem_superior.'</p>';
            echo '</fieldset>';
            echo '</div>';
        } else {
            $link = link::adicionar_atributo($link_base, array('op', 'cod_etiqueta'), array('abrir', $i));
            echo '<div>'.link::texto($link, $tipo, $tipo, false, false, true).' ('.$def->colunas.'x'.$def->linhas.')</div>';
        }
        echo '</li>';
    }
    echo '</ul>';

    echo '<div class="observacoes" style="margin-top: 2em">';
    echo '<p>Observa&ccedil;&otilde;es:</p>';
    echo '<ul>';
    echo '<li>A = Largura = Dist&acirc;ncia entre a borda esquerda e a borda direita da mesma etiqueta.</li>';
    echo '<li>B = Altura = Dist&acirc;ncia entre a borda superior e a borda inferior da mesma etiqueta.</li>';
    echo '<li>C = Dist&acirc;ncia Horizontal = Dist&acirc;ncia entre as bordas esquerda de etiquetas adjacentes horizontalmente.</li>';
    echo '<li>D = Dist&acirc;ncia Vertical = Dist&acirc;ncia entre as bordas superiores de etiquetas adjacentes verticalmente.</li>';
    echo '<li>E = Margem Esquerda = Dist&acirc;ncia entre a borda esquerda da folha e a borda esquerda das etiquetas da primeira coluna.</li>';
    echo '<li>F = Margem Superior = Dist&acirc;ncia entre a borda superior da folha e a borda superior das etiquetas da primeira linha.</li>';
    echo '</ul>';
    echo '<img src="'.$CFG->wwwimgs.'geral/distancia_etiqueta.jpg" width="514" height="317" alt="Dist&acirc;ncias dos Modelos de Etiquetas" />';
    echo '</div>';
}


//
//     Obtem as definicoes do tipo de etiqueta
//
function get_definicao_tipo($tipo) {
// String $tipo: codigo do tipo de etiqueta
//
    $def = new stdClass();

    $medidas = fpdf_etiqueta::get_medidas_tipo($tipo);

    // Unidade
    switch ($medidas[2]) {
    case 'pt':
        $def->unidade = 'pt';
        $def->descricao_unidade = 'pontos';
        break;
    case 'mm':
        $def->unidade = 'mm';
        $def->descricao_unidade = 'mil&iacute;metros';
        break;
    case 'cm':
        $def->unidade = 'cm';
        $def->descricao_unidade = 'cent&iacute;metros';
        break;
    case 'in':
        $def->unidade = 'in';
        $def->descricao_unidade = 'polegadas';
        break;
    default:
        $def->unidade = '?';
        $def->descricao_unidade = '?';
        break;
    }
    $unidade = ' <abbr title="'.$def->descricao_unidade.'">'.$def->unidade.'</abbr>';

    // Papel
    switch ($medidas[0]) {
    case 'A3':
        $def->papel = 'A3';
        break;
    case 'A4':
        $def->papel = 'A4';
        break;
    case 'A5':
        $def->papel = 'A5';
        break;
    case 'Letter':
        $def->papel = 'Carta';
        break;
    case 'Legal':
        $def->papel = 'Of&iacute;cio';
        break;
    default:
        if (is_array($medidas[0])) {
            $largura = texto::numero($medidas[0][0], 3).$unidade;
            $altura = texto::numero($medidas[0][1], 3).$unidade;
            $def->papel = 'Espec&iacute;fico (Largura: '.$largura.' / Altura: '.$altura.')';
        } else {
            $def->papel = $medidas[0];
        }
        break;
    }

    // Orientacao
    switch (strtoupper($medidas[1])) {
    case 'P':
        $def->orientacao = 'Retrato';
        break;
    case 'L':
        $def->orientacao = 'Paisagem';
        break;
    }

    // Numero de linhas/colunas por folha
    $def->colunas   = texto::numero($medidas[3]);
    $def->linhas    = texto::numero($medidas[4]);
    $def->etiquetas = texto::numero($medidas[3] * $medidas[4]);

    // Distancia horizontal e vertical
    $def->distancia_horizontal = texto::numero($medidas[5], 3).$unidade;
    $def->distancia_vertical   = texto::numero($medidas[6], 3).$unidade;

    // Largura/Altura da etiqueta
    $def->largura = texto::numero($medidas[7], 3).$unidade;
    $def->altura  = texto::numero($medidas[8], 3).$unidade;

    // Margem esquerda/superior
    $def->margem_esquerda = texto::numero($medidas[9], 3).$unidade;
    $def->margem_superior = texto::numero($medidas[10], 3).$unidade;

    return $def;
}