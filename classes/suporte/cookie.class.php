<?php
//
// SIMP
// Descricao: Classe que controla os cookies
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.12
// Data: 22/08/2007
// Modificado: 27/06/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('COOKIE_NOME',            $CFG->nome_cookie);
define('COOKIE_TEMPO_EXPIRA',    31536000);
define('COOKIE_DOMINIO_COOKIES', $CFG->dominio_cookies);
define('COOKIE_PATH',            $CFG->path);

final class cookie {
    static private $dados;


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Recupera os valores do cookie retornando um vetor associativo
    //
    static public function consultar() {
        if (!isset($_COOKIE[COOKIE_NOME])) {
            return array();
        }
        self::$dados = self::decodificar($_COOKIE[COOKIE_NOME]);
        return self::$dados;
    }


    //
    //     Salva o valor dos cookies
    //
    static public function salvar($vetor) {
    // Array[String => Mixed] $vetor: vetor com os valores a serem salvos
    //
        if (!is_array($vetor)) {
            return false;
        }

        // Armazenar os dados no cookie
        $mudou  = count($vetor) != count(self::$dados);
        if (!$mudou) {
            foreach ($vetor as $nome => $valor) {
                if (!isset(self::$dados[$nome]) || (self::$dados[$nome] != $valor)) {
                    $mudou = true;
                    break;
                }
            }
        }

        // Se ocorreram mudancas, entao salvar
        if ($mudou) {
            $dados = self::codificar($vetor);

            // Cookie do sistema
            if (strcmp(COOKIE_NOME, 'cookie_instalacao') != 0) {
                return setcookie(COOKIE_NOME, $dados,  time() + COOKIE_TEMPO_EXPIRA, COOKIE_PATH, COOKIE_DOMINIO_COOKIES);

            // Cookie da instalacao
            } else {
                $tempo_cookie_instalacao = 1800;  // 30 minutos
                return setcookie(COOKIE_NOME, $dados,  time() + $tempo_cookie_instalacao, '/');
            }
        }
        return -1;
    }


    //
    //     Metodo usado para codificar os dados no cookie
    //
    static public function codificar($valor) {
    // Mixed $valor: valor a ser codificado
    //
        $novo_valor = serialize($valor);
        $novo_valor = strtr(base64_encode($novo_valor), '+/=', '-_.');
        return $novo_valor;
    }


    //
    //     Metodo usado para decodificar os dados do cookie
    //
    static public function decodificar($valor) {
    // Mixed $valor: valor a ser decodificado
    //
        $novo_valor = base64_decode(strtr($valor, '-_.', '+/='));
        $novo_valor = unserialize($novo_valor);
        return $novo_valor;
    }

}//class
