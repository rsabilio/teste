<?php
//
// SIMP
// Descricao: Biblioteca especial de tratamento de excecoes e erros
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.13
// Data: 03/06/2008
// Modificado: 14/04/2011
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//


//
//     Formata uma linha com erro para apresentacao
//
function formatar_linha($linha, &$vars) {
// String $linha: texto da linha onde ocorreu o erro
// Array[String => Mixed] $vars: variaveis alocadas no momento do erro
//
    global $CFG;
    $conversao = array('&' => '&amp;', '<' => '&lt;', '>' => '&gt;');
    $linha = strtr($linha, $conversao);
    if (preg_match_all('/\$([A-Za-z_][A-Za-z0-9-_]*)/', $linha, $match)) {
        foreach ($match[1] as $i => $nome_var) {
            $var = $match[0][$i];
            if (isset($vars['GLOBALS']) && array_key_exists($nome_var, $vars['GLOBALS'])) {
                $valor = $vars['GLOBALS'][$nome_var];
                $nova_var = '<var title="'.util::exibir_var($valor).'">'.htmlentities($var, ENT_COMPAT, $CFG->charset).'</var>';
                $linha = str_replace($var, $nova_var, $linha);
            }
        }
    }
    return $linha;
}


//
//     Trata o erro de acordo com o seu tipo
//
function tratar_erro($nivel, $erro, $arquivo, $linha, $vars) {
// Int $nivel: nivel (tipo) de erro disparado
// String $erro: mensagem do erro disparado
// String $arquivo: caminho absoluto do arquivo que disparou o erro
// Int $linha: linha do arquivo que ocasionou o erro
// Array[String => Mixed] $vars: variaveis disponiveis no contexto do erro
//
    global $CFG;
    $error_reporting = (int)ini_get('error_reporting');

    if (!defined('E_DEPRECATED')) {
        define('E_DEPRECATED', 0);
    }
    if (!defined('E_USER_DEPRECATED')) {
        define('E_USER_DEPRECATED', 0);
    }
    if (!defined('E_STRICT')) {
        define('E_STRICT', 0);
    }

    // Tipos de erros fatais: abortam a execucao da pagina
    $fatais = array(
        -1                  => 'Exce&ccedil;&atilde;o',
        E_ERROR             => 'Erro fatal',
        E_PARSE             => 'Erro de interpretador',
        E_CORE_ERROR        => 'Erro fatal PHP',
        E_COMPILE_ERROR     => 'Erro ao compilar PHP',
        E_USER_ERROR        => 'Erro do programador',
        E_RECOVERABLE_ERROR => 'Erro n&atilde;o inst&aacute;vel'
    );

    // Tipos de erros nao fatais: continuam a execucao, mas geram um log de erro
    $nao_fatais = array(
        E_WARNING         => 'Aviso importante',
        E_NOTICE          => 'Notifica&ccedil;&atilde;o de poss&iacute;vel problema',
        E_DEPRECATED      => 'Aviso de recurso depreciado',
        E_CORE_WARNING    => 'Aviso importante do PHP',
        E_COMPILE_WARNING => 'Aviso importante ao compilar',
        E_USER_WARNING    => 'Aviso importante do programador',
        E_USER_NOTICE     => 'Notifica&ccedil;&atilde;o de poss&iacute;vel problema pelo programador',
        E_USER_DEPRECATED => 'Aviso de recurso depreciado pelo programador',
        E_STRICT          => 'Sugest&atilde;o de melhoria'
    );

    // Obter nome do erro e checar se e' um erro fatal
    if (isset($fatais[$nivel])) {
        $nome = $fatais[$nivel];
        $fatal = true;
    } elseif (isset($nao_fatais[$nivel])) {
        $nome = $nao_fatais[$nivel];
        $fatal = DEBUG_SIMP;
    } else {
        $nome = 'Desconhecido ('.$nivel.')';
        $fatal = DEBUG_SIMP;
    }

    // Formatar string a ser enviada para o log de erros
    $log = $nome.': '.$erro.' / Arquivo: '.$arquivo.' / Linha: '.$linha;
    $tabela = get_html_translation_table(HTML_ENTITIES);
    $tabela = array_flip($tabela);
    $log = strtr($log, $tabela);

    // Erros fatais abortam a execucao do programa
    if ($fatal) {

        // Modo de producao
        if (DEVEL_BLOQUEADO) {
            if (headers_sent()) {
                echo '<div>';
                echo '<h1>Erro do Sistema</h1>';
                echo '<p>Ocorreu um erro fatal no sistema e ele precisou ser interrompido. ';
                echo 'Uma mensagem j&aacute; foi encaminhada para os respons&aacute;veis pelo sistema. ';
                echo 'Pedimos desculpas pelo inconveniente.</p>';
                echo '</div>';
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                header('Status: 500 Internal Server Error');
                header('Content-Type: text/html');

                echo '<html>';
                echo '<head>';
                echo '<meta http-equiv="Content-Type" value="text/html" />';
                echo '<title>Erro</title>';
                echo '</head>';
                echo '<body>';
                echo '<div>';
                echo '<h1>Erro do Sistema</h1>';
                echo '<p>Ocorreu um erro fatal no sistema e ele precisou ser interrompido. ';
                echo 'Uma mensagem j&aacute; foi encaminhada para os respons&aacute;veis pelo sistema. ';
                echo 'Pedimos desculpas pelo inconveniente.</p>';
                echo '</div>';
                echo '</body>';
            }

        // Modo de desenvolvimento
        } else {
            if (PHP_SAPI == 'cli') {
                echo 'Descricao: '.texto::decodificar($erro).PHP_EOL;
                echo 'Arquivo: '.texto::decodificar($arquivo).PHP_EOL;
                echo 'Linha: '.$linha.PHP_EOL;
                util_cli::debug(false);
            } else {
                if (headers_sent()) {
                    echo '<div>';
                    echo '<h1>'.$nome.'</h1>';
                    echo '<p><strong>Descri&ccedil;&atilde;o:</strong> '.$erro.'</p>';
                    echo '<p><strong>Arquivo:</strong> '.$arquivo.'</p>';
                    echo '<p><strong>Linha:</strong> '.$linha.'</p>';
                    echo '</div>';
                    echo '<h2>Rastreamento da chamada que ocasionou o erro</h2>';
                    util::debug(false);
                } else {
                    header('HTTP/1.1 500 Internal Server Error');
                    header('Status: 500 Internal Server Error');
                    header('Content-Type: text/html');

                    echo '<html>';
                    echo '<head>';
                    echo '<meta http-equiv="Content-Type" value="text/html" />';
                    echo '<title>'.$nome.'</title>';
                    echo '<style type="text/css"><!--';
                    echo 'table { border: 1px solid #000000; width: 40em }';
                    echo 'table td { text-align: left; }';
                    echo 'table td var { cursor: pointer; border-bottom: 1px dotted #000000; font-style: normal }';
                    echo 'table td.l { background-color: #FFFAEE; width: 2em; text-align: right; padding-right: .5em }';
                    echo 'table tr.erro td { background-color: #FFEEEE }';
                    echo 'pre, code { margin: 0; padding 0; }';
                    echo '--></style>';
                    echo '</head>';
                    echo '<body>';
                    echo '<h1>'.$nome.'</h1>';
                    echo '<p><strong>Descri&ccedil;&atilde;o:</strong> '.$erro.'</p>';
                    echo '<p><strong>Arquivo:</strong> '.$arquivo.'</p>';
                    echo '<p><strong>Linha:</strong> '.$linha.'</p>';
                    if (is_file($arquivo)) {
                        $conteudo = file($arquivo);
                        echo '<table>';
                        echo '<tr><th class="l">#</th><th>C&oacute;digo</th></tr>';
                        for ($i = max($linha - 4, 0); $i < $linha; $i++) {
                            echo '<tr><td class="l">'.$i.'</td><td><pre><code>'.htmlentities($conteudo[$i - 1], ENT_COMPAT, $CFG->charset).'</code></pre></td></tr>';
                        }
                        echo '<tr class="erro"><td class="l">'.($linha).'</td><td><pre><code>'.formatar_linha($conteudo[$linha - 1], $vars).'</code></pre></td></tr>';
                        for ($i = 0; $i < 4 && isset($conteudo[$linha + $i]); $i++) {
                            echo '<tr><td class="l">'.($linha + 1 + $i).'</td><td><pre><code>'.htmlentities($conteudo[$linha + $i], ENT_COMPAT, $CFG->charset).'</code></pre></td></tr>';
                        }
                        echo '</table>';
                    }
                    echo '<h2>Rastreamento da chamada que ocasionou o erro</h2>';
                    util::debug(false);
                    echo '<hr />';
                    echo '<p>Ocorreu um erro ao gerar esta p&aacute;gina.</p>';
                    echo '<p>Dados do Administrador: '.$_SERVER['SERVER_ADMIN'].'</p>';
                    echo '</body>';
                    echo '</html>';
                }
            }
        }
        if ($nivel == -1 || $nivel & $error_reporting) {
            gravar_log_erro($log);
        }
        exit(1);
    } else {
        if ($nivel == -1 || $nivel & $error_reporting) {
            gravar_log_erro($log);
        }
    }
    return false;
}


//
//     Lanca um log de erro
//
function gravar_log_erro($log) {
// String $log: mensagem
//
    global $CFG;

    // Saida alternativa
/*
    if (function_exists('sys_get_temp_dir')) {
        $temp_dir = sys_get_temp_dir();
    } else {
        $temp_dir = '/tmp';
    }
    $arquivo = $temp_dir.'/simp-'.$CFG->sistema.'.log';
    file_put_contents($arquivo, texto::decodificar($log).PHP_EOL, FILE_APPEND);
    @chmod($arquivo, 0777);
*/

    // Saida padrao
    error_log($log);
}
