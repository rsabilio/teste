<?php
//
// SIMP
// Descricao: Classe com metodos uteis para PHP-Cli
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 11/02/2011
// Modificado: 22/03/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
final class util_cli {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Obtem argumentos da linha de comando
    //
    static public function get_argumentos($tipos_opcoes, $padrao = array()) {
    // Array[String || Array[String]] $tipos_opcoes: vetor com tipos de opcoes, cada linha representa uma opcao ou um vetor com duas opcoes correspondentes, uma simples e outra longa (veja a definicao da funcao getopt do PHP)
    // Array[String => Mixed] $padrao: valores padrao, caso nao sejam informados
    //
        $simples = array();
        $longas = array();
        $correspondencias = array();
        foreach ($tipos_opcoes as $opcao) {
            if (is_string($opcao)) {

                // Simples
                if (preg_match('/^([a-z])[:]{0,2}$/i', $opcao, $matches)) {
                    $simples[] = $opcao;

                // Longa
                } elseif (preg_match('/^([a-z][a-z0-9]*(?:-[a-z0-9]+)*)[:]{0,2}$/i', $opcao, $matches)) {
                    $longas[] = $opcao;
                }

            } elseif (is_array($opcao)) {
                if (count($opcao) != 2) {
                    trigger_error('Especificacao de opcoes invalido: o vetor precisa ter uma opcao simples e uma longa', E_USER_WARNING);
                    return false;
                }
                $opcao_simples = null;
                $opcao_longa = null;
                foreach ($opcao as $opcao2) {

                    // Simples
                    if (preg_match('/^([a-z])[:]{0,2}$/i', $opcao2, $matches)) {
                        $simples[] = $opcao2;
                        $opcao_simples = $matches[1];

                    // Longa
                    } elseif (preg_match('/^([a-z][a-z0-9]*(?:-[a-z0-9]+)*)[:]{0,2}$/i', $opcao2, $matches)) {
                        $longas[] = $opcao2;
                        $opcao_longa = $matches[1];
                    }
                }
                if ($opcao_simples && $opcao_longa) {
                    $correspondencias[$opcao_simples] = $opcao_longa;
                } else {
                    trigger_error('Especificacao de opcoes invalido: o vetor precisa ter uma opcao simples e uma longa', E_USER_WARNING);
                    return false;
                }
            }
        }

        $simples = implode('', $simples);

        // Obter argumentos
        if (empty($longas)) {
            $opcoes = getopt($simples);
        } else {
            $opcoes = getopt($simples, $longas);
        }

        // Preencher valores correspondentes
        foreach ($correspondencias as $opcao_simples => $opcao_longa) {
            if (isset($opcoes[$opcao_simples])) {
                if (!isset($opcoes[$opcao_longa])) {
                    $opcoes[$opcao_longa] = $opcoes[$opcao_simples];
                    unset($opcoes[$opcao_simples]);
                } else {
                    unset($opcoes[$opcao_simples]);
                }
            }
        }

        // Preencher valores opcionais com valores padrao
        foreach ($opcoes as $opcao => $valor) {
            if (is_bool($valor) && isset($padrao[$opcao])) {
                $opcoes[$opcao] = $padrao[$opcao];
            }
        }

        // Preencher valores padrao
        foreach ($padrao as $opcao => $valor) {
            if (!isset($opcoes[$opcao])) {
                $opcoes[$opcao] = $valor;
            }
        }

        return $opcoes;
    }


    //
    //     Imprime mensagens de erro
    //
    public static function imprimir_erros($erros, $return = false, $identacao = 0) {
    // String || Array[Type] $erros: mensagem de erro ou vetor de erros
    //
        $mensagem = self::imprimir_erros_recursivo($erros, $identacao);
        if ($return) {
            return $mensagem;
        }
        fwrite(STDERR, $mensagem);
    }


    //
    //     Imprime mensagens de erro
    //
    private static function imprimir_erros_recursivo($erros, $identacao = 0) {
    // String || Array[Type] $erros: mensagem de erro ou vetor de erros
    // Int $identacao: nivel de identacao do erro
    //
        $retorno = '';
        if (is_string($erros)) {
            $retorno .= str_repeat(' ', $identacao * 3).'* '.strip_tags(texto::decodificar($erros)).PHP_EOL;
        } elseif (is_array($erros)) {
            foreach ($erros as $erro) {
                $retorno .= self::imprimir_erros_recursivo($erro, $identacao + 1);
            }
        }
        return $retorno;
    }


    //
    //     Realiza um backtrace legivel na sapi CLI (deve ser utilizado dentro de metodos)
    //
    public static function debug($incluir_ultima_chamada = true, $notacao = UTIL_EXIBIR_PHP) {
    // Bool $incluir_ultima_chamada: incluir a chamada ao metodo debug
    // Int $notacao: notacao dos parametros mostrados com util::exibir_var
    //
        $linha = str_repeat('#', 80);
        $backtrace = debug_backtrace(1);
        if (!empty($backtrace) && !$incluir_ultima_chamada) {
            array_shift($backtrace);
        }

        echo 'DEBUG:'.PHP_EOL;
        foreach ($backtrace as $chamada) {
            $str_chamada = $linha.PHP_EOL;
            if (isset($chamada['class'])) {
                if ($chamada['type'] == '::') {
                    $tipo = ' Estático';
                    $forma_chamada = $chamada['class'].'::'.$chamada['function'].'(';
                } else {
                    if (isset($chamada['object'])) {
                        $tipo = '';
                        $forma_chamada = '$'.get_class($chamada['object']).'->'.$chamada['function'].'(';
                    } else {
                        $tipo = '';
                        $forma_chamada = '$'.$chamada['class'].'->'.$chamada['function'].'(';
                    }
                }
                $str_chamada .= 'Método'.$tipo.': '.$forma_chamada;
            } else {
                $str_chamada .= 'Função: '.$chamada['function'].'(';
            }
            if (isset($chamada['args']) && !empty($chamada['args'])) {
                $vt_args = array();
                foreach ($chamada['args'] as $arg) {
                    $vt_args[] = util::exibir_var($arg, $notacao);
                }
                $str_chamada .= implode(', ', $vt_args);
            } else {
                $str_chamada .= 'void';
            }
            $str_chamada .= ')'.PHP_EOL;
            if (isset($chamada['file']) && isset($chamada['line'])) {
                $str_chamada .= 'Arquivo: '.$chamada['file'].' / Linha '.$chamada['line'].PHP_EOL;
            }
            echo $str_chamada;
        }
        echo $linha.PHP_EOL;
    }

}
