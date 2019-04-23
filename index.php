<?php
//
// SIMP
// Descricao: Arquivo Principal
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.7
// Data: 03/03/2007
// Modificado: 17/12/2010
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('./config.php');
require_once($CFG->dirroot.'sessao.php');

/// Dados da Pagina
$titulo  = 'Apresenta&ccedil;&atilde;o';
$nav[]   = '#index.php';
$estilos = array($CFG->wwwlayout.'calendario.css',
                 $CFG->wwwlayout.'principal.css');


/// Imprimir pagina
$pagina = new pagina();
$pagina->adicionar_rss("{$CFG->wwwmods}eventos/eventos.rss.php", "Eventos ({$CFG->titulo})");
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->imprimir_menu($USUARIO);
$pagina->inicio_conteudo($titulo);
apresentacao();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


///     Funcoes


//
//     Exibe a apresentacao do sistema
//
function apresentacao() {
    global $CFG, $USUARIO;

    $evento = new evento();

    // Consultar grupos do usuario
    $vt_grupos = array(0);
    if (is_array($USUARIO->grupos)) {
        foreach ($USUARIO->grupos as $g) {
            $vt_grupos[] = $g->cod_grupo;
        }
    }
    $eh_admin = $USUARIO->possui_grupo(COD_ADMIN);

    // Montar links para o modulo de eventos
    $link_pagina = $CFG->wwwmods.'eventos/exibir_eventos.php';
    $link_pagina_exibir = $CFG->wwwmods.'eventos/exibir.php';


    // CENTRO SECUNDARIO
    echo "<div id=\"centro_secundario\">\n";
    evento::imprimir_calendario($link_pagina, $link_pagina_exibir, 'calendario', $USUARIO->cod_usuario, false, $vt_grupos, $eh_admin);
    echo "<p class=\"clear\"><a class=\"rss\" rel=\"rss\" href=\"{$CFG->wwwmods}eventos/eventos.rss.php\" title=\"Eventos (RSS 2.0)\">Feed</a></p>";
    echo "</div>\n";


    // CENTRO PRINCIPAL
    echo "<div id=\"centro_principal\" class=\"texto\">\n";
    echo "<p>SIMP &eacute; um framework para desenvolvimento de Sistemas de Informa&ccedil;&atilde;o".
         " Modulares em PHP.</p>\n".
         "<p>Este meta-sistema representa apenas uma estrutura b&aacute;sica de um sistema de ".
         "informa&ccedil;&atilde;o Web. Ou seja, &eacute; o ponto de partida para cria&ccedil;&atilde;o ".
         "de uma aplica&ccedil;&atilde;o com prop&oacute;sitos mais espec&iacute;ficos.".
         "</p>\n";

    imprimir_aviso_saida();

    echo '<noscript>'.
         '<p><strong>Aten&ccedil;&atilde;o:</strong> seu navegador n&atilde;o d&aacute; suporte a JavaScript ou a '.
         'op&ccedil;&atilde;o est&aacute; desabilitada no momento. Para usufruir de todas as funcionalidades do '.
         'sistema de forma pr&aacute;tica, recomenda-se habilitar este suporte. Caso contr&aacute;rio, o sistema '.
         'continuar&aacute; acess&iacute;vel, mas com algumas limita&ccedil;&otilde;es.</p>'.
         "</noscript>\n";

    echo "</div>\n";
    echo "<p class=\"clear\"></p>\n";
}


//
//    Mostrar aviso sobre saida do sistema, caso nao tenha saido corretamente
//
function imprimir_aviso_saida() {
    global $CFG, $USUARIO;

    if (isset($_SESSION[__FILE__]['aviso'])) {
        $aviso = $_SESSION[__FILE__]['aviso'];
    } else {
        $aviso = false;

        // Obter penultimo acesso
        $vt_condicoes = array();
        $vt_condicoes[] = condicao_sql::montar('cod_usuario', '=', $USUARIO->get_valor_chave());
        $vt_condicoes[] = condicao_sql::sql_in('operacao', array('entrada', 'saida'));
        $vt_condicoes[] = condicao_sql::montar('erro', '=', false);
        $condicoes = condicao_sql::sql_and($vt_condicoes);
        $campos = array(
            'data',
            'operacao'
        );
        $ordem = array(
            'data' => false
        );
        $logs = objeto::get_objeto('log_sistema')->consultar_varios($condicoes, $campos, $ordem, false, 1, 1);
        if (!empty($logs)) {
            $penultimo_log = array_pop($logs);
            if ($penultimo_log->operacao == 'entrada') {
                $data = $penultimo_log->exibir('data');
                $aviso = <<<AVISO
<div class="aviso_sair">
  <p><strong>Aten&ccedil;&atilde;o!</strong> O sistema detectou que voc&ecirc; acessou em {$data},
  mas n&atilde;o clicou em "Sair" no menu principal do sistema.</p>
  <p>Por favor, clique em "Sair" quando encerrar suas atividades no sistema, caso contr&aacute;rio, voc&ecirc; corre 
  riscos de outra pessoa acessar a sua conta indevidamente.</p>
</div>
AVISO;
            }
        }

        $_SESSION[__FILE__]['aviso'] = $aviso;
    }
    if ($aviso) {
        echo $aviso;
    }
}
