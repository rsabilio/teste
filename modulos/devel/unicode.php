<?php
//
// SIMP
// Descricao: Exibe grupos de caracteres Unicode
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.4
// Data: 20/11/2008
// Modificado: 18/10/2010
// License: LICENSE.TXT
// Copyright (C) 2008  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Dados do Formulario
$dados  = formulario::get_dados(false);
$action = $CFG->site;


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Caracteres Unicode';
$nav = array();
$nav[$CFG->wwwmods.$modulo.'/index.php'] = 'Desenvolvimento';
$nav[''] = 'Caracteres Unicode';
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Bloquear caso necessario
require_once($CFG->dirmods.$modulo.'/bloqueio.php');


/// Imprimir Pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo($titulo);
logica_formulario_unicode($dados, $action);
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//    Realiza a logica do formulario de grupos Unicode
//
function logica_formulario_unicode($dados, $action) {
// Object $dados: dados submetidos
// String $action: endereco de destino dos dados
//
    imprimir_formulario($dados, $action);
    if ($dados) {
        $_SESSION['devel']['unicode'] = array();
        $_SESSION['devel']['unicode']['dados'] = serialize($dados);
        imprimir_resultado($dados);
    } elseif (isset($_SESSION['devel']['unicode'])) {
        $dados = unserialize($_SESSION['devel']['unicode']['dados']);
        if (isset($_GET['inicio'])) {
            $_SESSION['devel']['unicode']['inicio'] = round(abs($_GET['inicio']));
        }
        imprimir_resultado($dados);
    }
}


//
//     Imprime o formulario de grupos Unicode
//
function imprimir_formulario($dados, $action) {
// Object $dados: dados submetidos
// String $action: endereco de destino dos dados
//
    global $CFG;
    $id_faixas = array_keys(listas::get_faixas_unicode());

    $padrao = array(
        'id_faixa' => 0,
        'char'     => '',
        'ord'      => '',
        'entity'   => ''
    );
    if (!$dados && isset($_SESSION['devel']['unicode'])) {
        $dados = unserialize($_SESSION['devel']['unicode']['dados']);
    } else {
        $dados = formulario::montar_dados($padrao, $dados);
    }

    $form = new formulario($action, 'form_unicode');
    $form->campo_text('char', 'char', $dados->char, 4, 10, 'Caractere');
    $form->campo_text('ord', 'ord', $dados->ord, 10, 20, 'Ord (Decimal)');
    $form->campo_text('entity', 'entity', htmlentities($dados->entity, ENT_QUOTES, $CFG->charset), 10, 20, 'Entity');
    $form->campo_select('id_faixa', 'id_faixa', $id_faixas, $dados->id_faixa, 'Grupo Unicode');
    $form->campo_submit('enviar', 'enviar', 'Enviar');
    $form->imprimir();
}


//
//     Imprime o resultado da solicitacao
//
function imprimir_resultado($dados) {
// Object $dados: dados submetidos
//
    global $CFG;

    $faixas_completas = listas::get_faixas_unicode();
    $controle = listas::get_caracteres_controle();

    // Dados do caractere
    if ($dados->char !== '') {
        $c = texto::get_char($dados->char, 0);
        exibir_char($c, $faixas_completas);

    // Dados do codigo
    } elseif ($dados->ord !== '') {
        $c = texto::chr_utf8($dados->ord);
        exibir_char($c, $faixas_completas);

    // Dados da entity
    } elseif ($dados->entity !== '') {
        $c = texto::decodificar($dados->entity);
        if ($c == $dados->entity) {
            mensagem::erro('Entity inv&aacute;lida');
        } else {
            exibir_char($c, $faixas_completas);
        }
    }

    // Imprimir faixa
    $nomes  = array_keys($faixas_completas);
    $faixas = array_values($faixas_completas);

    $nome   = $nomes[$dados->id_faixa];
    $faixa  = $faixas[$dados->id_faixa];

    unset($faixas_completas, $nomes, $faixas);

    if ($faixa[0] < 40) {
        $php = <<<PHP
\$char = texto::chr_utf8(\$i);
\$len = strlen(\$char);
\$vt_bin = array();
for (\$j = 0; \$j < \$len; \$j++) {
    \$vt_bin[] = sprintf('%08b', ord(\$char[\$j]));
}
\$bin = implode(' ', \$vt_bin);
if (isset(\$controle[\$i])) {
    \$char = '['.\$controle[\$i].']';
    \$hex = '-';
    \$entity = '-';
} else {
    \$char = htmlspecialchars(\$char);
    \$hex = sprintf('%X', \$i);
    \$entity = texto::codificar(\$char);
    \$entity = (\$entity != \$char) ? htmlentities(\$entity, ENT_QUOTES, 'UTF-8') : '-';
}
PHP;
    } else {
        $php = <<<PHP
\$char = texto::chr_utf8(\$i);
\$len = strlen(\$char);
\$vt_bin = array();
for (\$j = 0; \$j < \$len; \$j++) {
    \$vt_bin[] = sprintf('%08b', ord(\$char[\$j]));
}
\$bin = implode(' ', \$vt_bin);
\$hex = sprintf('%X', \$i);
\$entity = texto::codificar(\$char);
\$entity = (\$entity != \$char) ? htmlentities(\$entity, ENT_QUOTES, 'UTF-8') : '-';
PHP;
    }

    $inicio = isset($_SESSION['devel']['unicode']['inicio']) ? $_SESSION['devel']['unicode']['inicio'] : $faixa[0];
    $fim = ($inicio + 100) < $faixa[1] ? $inicio + 100 : $faixa[1];

    // Link para voltar
    if ($inicio > $faixa[0]) {
        $voltar = $CFG->site;
        link::normalizar($voltar, true);
        $voltar = link::adicionar_atributo($voltar, 'inicio', $inicio - 100);
        $link_voltar = link::texto($voltar, paginacao::seta_esquerda(), 'Voltar', false, false, true, true, false);
    } else {
        $link_voltar = paginacao::seta_esquerda();
    }

    // Link para avancar
    if ($inicio + 100 < $faixa[1]) {
        $avancar = $CFG->site;
        link::normalizar($avancar, true);
        $avancar = link::adicionar_atributo($avancar, 'inicio', $inicio + 100);
        $link_avancar = link::texto($avancar, paginacao::seta_direita(), 'Avan&ccedil;ar', false, false, true, true, false);
    } else {
        $link_avancar = paginacao::seta_direita();
    }

    echo '<table class="tabela">';
    echo '<caption>Caracteres "'.$nome.'" / '.$faixa[0].'-'.$faixa[1].'</caption>';
    echo '<thead>';
    echo '<tr>';
    echo '<th colspan="5">'.$link_voltar.' '.$inicio.' '.$link_avancar.'</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>Ord</th>';
    echo '<th>Char</th>';
    echo '<th>Hex</th>';
    echo '<th>Entity</th>';
    echo '<th>Bin (UTF-8)</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tfoot>';
    echo '<tr>';
    echo '<td colspan="5">'.$link_voltar.' '.$inicio.' '.$link_avancar.'</td>';
    echo '</tr>';
    echo '</tfoot>';
    echo '<tbody>';
    for ($i = $inicio; $i <= $fim; $i++) {
        eval($php);
        echo '<tr>';
        echo '<td>'.$i.'</td>';
        echo '<td>'.$char.'</td>';
        echo '<td>'.$hex.'</td>';
        echo '<td>'.$entity.'</td>';
        echo '<td>'.$bin.'</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}


//
//     Exibe dados de um caractere
//
function exibir_char($c, $faixas_completas) {
// String $c: caractere UTF-8
// Array[String => Array[Int]] $faixas_completas: faixas de caracteres Unicode
//
    static $controle = null;
    static $mascaras = null;

    if ($controle === null) {
        $controle = listas::get_caracteres_controle();
        $mascaras = array();
        $mascaras[1] = '0<strong>XXXXXXX</strong> (7 bits / ASCII)';
        $mascaras[2] = '110<strong>XXXXX</strong> 10<strong>XXXXXX</strong> (11 bits)';
        $mascaras[3] = '1110<strong>XXXX</strong> 10<strong>XXXXXX</strong> 10<strong>XXXXXX</strong> (16 bits)';
        $mascaras[4] = '11110<strong>XXX</strong> 10<strong>XXXXXX</strong> 10<strong>XXXXXX</strong> 10<strong>XXXXXX</strong> (21 bits)';
    }

    $ord = texto::ord_utf8($c);
    $entity = texto::codificar($c);
    $vt_bin = array();
    for ($i = 0; $i < strlen($c); $i++) {
        $vt_bin[] = sprintf('%08b', ord($c[$i]));
    }
    $bin = implode(' ', $vt_bin);
    $nome_faixa = false;
    foreach ($faixas_completas as $nome => $faixa) {
        if ($ord >= $faixa[0] && $ord <= $faixa[1]) {
            $nome_faixa = $nome;
            break;
        }
    }

    echo '<div class="dados">';
    echo '<fieldset>';
    echo '<legend>Dados do Caractere</legend>';
    if (isset($controle[$ord])) {
        echo '<p><strong>Caractere:</strong> [n&atilde;o imprim&iacute;vel]</p>';
        echo '<p><strong>Significado:</strong> '.$controle[$ord].'</p>';
    } else {
        echo '<p><strong>Caractere:</strong> '.htmlspecialchars($c, ENT_NOQUOTES, 'UTF-8').'</p>';
    }
    echo '<p><strong>C&oacute;digo:</strong> '.$ord.'</p>';
    echo '<p><strong>Hexadecimal:</strong> 0x'.dechex($ord).'</p>';
    if ($entity !== $c) {
        echo '<p><strong>Entity:</strong> '.htmlspecialchars($entity).'</p>';
    }
    echo '<hr />';
    if ($nome_faixa) {
        $faixa = &$faixas_completas[$nome_faixa];
        echo '<p><strong>Grupo Unicode:</strong> '.$nome_faixa.'</p>';
        echo '<p><strong>Faixa Unicode:</strong> '.$faixa[0].'-'.$faixa[1].' (0x'.dechex($faixa[0]).'-0x'.dechex($faixa[1]).')</p>';
        echo '<p><strong>Capacidade da Faixa:</strong> '.($faixa[1] - $faixa[0]).' s&iacute;mbolos</p>';
        echo '<hr />';
    }
    echo '<p><strong>Bin&aacute;rio UTF-8:</strong> '.$bin.'</p>';
    echo '<p><strong>M&aacute;scara UTF-8:</strong> <span class="mascara">'.$mascaras[strlen($c)].'</span></p>';
    echo '<p><strong>Bin&aacute;rio Efetivo:</strong> '.decbin($ord).'</p>';
    echo '</fieldset>';
    echo '</div>';
}
