<?php
//
// SIMP
// Descricao: Classe de requisicoes HTTP
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.0.0
// Data: 23/01/2008
// Modificado: 04/10/20012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
final class http {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Monta um cabecalho HTTP
    //     As opcoes especiais sao:
    //     - Int $codigo_http: codigo de retorno HTTP
    //     - String $arquivo: nome do arquivo
    //     - String $disposition: disposicao do arquivo (inline ou attachment)
    //     - Int $tempo_expira: tempo em segundos para expirar o arquivo
    //     - String $tipo_cache: public ou private
    //     - String $compactacao: tipo de compactacao
    //     - Int $ultima_mudanca: time da ultima mudanca
    //     - Array[String => String] $valores: vetor associativo de chaves e valores de cabecalhos HTTP
    //
    static public function cabecalho($content_type, $opcoes = array()) {
    // String $content_type: mime-type do arquivo + opcoes (charset, por exemplo)
    // Array[String => Mixed] $opcoes: 
    //
        global $CFG;

        $locale = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'C');

        $opcoes_padrao = array(
            'codigo_http'    => 200,
            'arquivo'        => preg_replace('/(\.php)$/', '', util::get_arquivo()),
            'disposition'    => 'inline',
            'tempo_expira'   => 0,
            'tipo_cache'     => 'private',
            'compactacao'    => false,
            'ultima_mudanca' => $CFG->time,
            'valores'        => array()
        );
        $opcoes = array_merge($opcoes_padrao, $opcoes);

        // Se usa cache, checar se nao foi modificado
        if ($opcoes['tempo_expira']) {
            $time_cache_agent = self::get_time_cache_agent();

            // Se nao foi modificado e esta dentro da validade do cache
            if (
                $time_cache_agent &&
                $time_cache_agent >= $opcoes['ultima_mudanca'] &&
                $time_cache_agent + $opcoes['tempo_expira'] >= $CFG->time
            ) {
                header('HTTP/1.0 304 Not Modified');
                header('Date: '.gmstrftime($CFG->gmt, $CFG->time));
                header('Cache-Control: ');
                header('Pragma: ');
                header('Expires: ');
                exit(0);
            }
        }

        // Enviar header
        if (function_exists('http_response_code')) {
            http_response_code($opcoes['codigo_http']);
        } else {
            header(':', true, $opcoes['codigo_http']);
        }
        header('X-Powered-By: PHP');
        header('X-Framework: SIMP/'.VERSAO_SIMP);
        header('Content-Type: '.$content_type);
        header('Content-Base: '.$CFG->wwwroot);
        header('Content-Disposition: '.$opcoes['disposition'].($opcoes['arquivo'] ? '; filename="'.$opcoes['arquivo'].'"' : ''));
        if (preg_match('/^text\//', $content_type)) {
            header('Content-Language: '.$CFG->lingua);
        }
        header('Date: '.gmstrftime($CFG->gmt, $CFG->time));
        header('Last-Modified: '.gmstrftime($CFG->gmt, $CFG->time));
        header('Expires: '.gmstrftime($CFG->gmt, $CFG->time + $opcoes['tempo_expira']));
        if ($opcoes['tempo_expira'] > 0) {
            header('Cache-Control: '.$opcoes['tipo_cache']);
            header('Pragma: ');
        } else {
            header('Cache-Control: no-cache, no-store, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
        }
        if ($opcoes['compactacao']) {
            ob_start('ob_gzhandler');
        }
        foreach ($opcoes['valores'] as $chave => $valor) {
            header($chave.': '.$valor);
        }
        setlocale(LC_TIME, $locale);
    }

    //
    //     Obtem o time do cache que o user-agent informou
    //
    static public function get_time_cache_agent() {
        $data = false;

        // Checar em $_SERVER
        if (array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            $data = $_SERVER['HTTP_IF_MODIFIED_SINCE'];

        // Checar em request header
        } else {
            $headers = array();
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } elseif (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
            }

            foreach ($headers as $chave => $valor) {
                if (strcasecmp('If-Modified-Since', $chave) == 0) {
                    $data = $valor;
                    break;
                }
            }
        }

        // Nao encontrou
        if (!$data) {
            return false;
        }

        // Formatar como timestamp
        $d = strptime($data, '%a, %d %b %Y %T');
        if (!$d) {
            return false;
        }

        return gmmktime(
            $d['tm_hour'], $d['tm_min'], $d['tm_sec'],
            $d['tm_mon'] + 1, $d['tm_mday'], 1900 + $d['tm_year']
        );

    }


    //
    //     Atalho para obter o conteudo de um link especifico
    //
    static public function get_conteudo_link($link, $cookies = false, $tipo = null) {
    // String $link: link a ser aberto
    // Object || Array[String => String] $cookies: dados a serem enviados por cookie
    // String $tipo: 'ssl', 'tsl', 'plain' ou null
    //
        $dados_link = parse_url($link);
        $padrao = array(
            'scheme' => 'http',
            'host'   => 'localhost',
            'port'   => 0,
            'path'   => '/'
        );
        $dados_link = array_merge($padrao, $dados_link);
        if (!$dados_link['port']) {
            switch ($dados_link['scheme']) {
            case 'http':
                $dados_link['port'] = 80;
                if (!$tipo) {
                    $tipo = 'plain';
                }
                break;
            case 'https':
                $dados_link['port'] = 443;
                if (!$tipo) {
                    $tipo = 'ssl';
                }
                break;
            }
        } else {
            switch ($dados_link['scheme']) {
            case 'http':
                if (!$tipo) {
                    $tipo = 'plain';
                }
                break;
            case 'https':
                if (!$tipo) {
                    $tipo = 'ssl';
                }
                break;
            }
        }

        if (isset($dados_link['query'])) {
            $dados_link['query'] = str_replace('&amp;', '&', $dados_link['query']);
            parse_str($dados_link['query'], $dados);
        } else {
            $dados = null;
        }

        $resultado = self::get($dados_link['host'], $dados_link['port'], $dados_link['path'], $dados, $cookies, $tipo);
        if ($resultado->cod_erro != 0) {
            return false;
        }
        return $resultado->conteudo_resposta;
    }


    //
    //     Envia dados via post para algum endereco e recebe o resultado
    //
    static public function post($host, $porta = 80, $path = '/', $dados = null, $cookies = null, $tipo = null) {
    // String $host: endereco do host para enviar os dados
    // Int $porta: porta usada na conexao por socket
    // String $path: caminho relativo do endereco para envio dos dados
    // Object || Array[String => Mixed] $dados: dados a serem submetidos
    // Object || Array[String => String] $cookies: dados a serem enviados por cookie
    // String $tipo: 'ssl', 'tsl', 'plain' ou null
    //
        return self::enviar('POST', $host, $porta, $path, $dados, $cookies, $tipo);
    }


    //
    //     Envia dados via get para algum endereco e recebe o resultado
    //
    static public function get($host, $porta = 80, $path = '/', $dados = null, $cookies = null, $tipo = null) {
    // String $host: endereco do host para enviar os dados
    // Int $porta: porta usada na conexao por socket
    // String $path: caminho relativo do endereco para envio dos dados
    // Object || Array[String => Mixed] $dados: dados a serem submetidos
    // Object || Array[String => String] $cookies: dados a serem enviados por cookie
    // String $tipo: 'ssl', 'tsl', 'plain' ou null
    //
        return self::enviar('GET', $host, $porta, $path, $dados, $cookies, $tipo);
    }


    //
    //     Envia dados por HTTP para algum endereco e recebe o resultado (cod_erro, erro, header_envio, header_resposta, vt_header_resposta, conteudo_resposta)
    //
    static public function enviar($metodo, $host, $porta = 80, $path = '/', $dados = null, $cookies = null, $tipo = null) {
    // String $metodo: metodo HTTP utilizado (GET, POST, HEAD, etc.)
    // String $host: endereco do host para enviar os dados
    // Int $porta: porta usada na conexao por socket
    // String $path: caminho relativo do endereco para envio dos dados
    // Object || Array[String => Mixed] $dados: dados a serem submetidos
    // Object || Array[String => String] $cookies: dados a serem enviados por cookie
    // String $tipo: 'ssl', 'tsl', 'plain' ou null
    //
        $retorno = new stdClass();
        $retorno->cod_erro = false;
        $retorno->erro = false;
        $retorno->header_envio = self::montar_header($metodo, $host, $path, $dados, $cookies);

        // Abrir conexao via socket
        switch ($tipo) {
        case 'ssl':
            $prefixo = 'ssl://';
            break;
        case 'tls':
            $prefixo = 'tls://';
            break;
        case 'plain':
        default:
            $prefixo = '';
            break;
        }
        $socket = fsockopen($prefixo.$host, $porta, $retorno->cod_erro, $retorno->erro, 1.0);

        stream_set_timeout($socket, 0, 100);
        if (!$socket) {
            return $retorno;
        }

        // Enviar pedido
        fwrite($socket, $retorno->header_envio);

        // Receber cabecalho da resposta
        $retorno->header_resposta = self::get_header_resposta($socket);
        if ($retorno->header_resposta) {
            $retorno->vt_header_resposta = self::parse_header($retorno->header_resposta);

            // Receber conteudo da resposta
            $retorno->conteudo_resposta = self::get_conteudo_resposta($socket, $retorno->vt_header_resposta);

        } else {
            $retorno->vt_header_resposta = array();
            $retorno->conteudo_resposta = '';
        }

        // Fechar a conexao
        fclose($socket);

        return $retorno;
    }


    //
    //     Monta o cabecalho HTTP de envio dos dados
    //
    static private function montar_header($metodo, $host, $path = '/', $dados = null, $cookies = null) {
    // String $metodo: metodo HTTP utilizado (GET, POST, HEAD, etc.)
    // String $host: endereco do host para enviar os dados
    // String $path: caminho relativo do endereco para envio dos dados
    // Object || Array[String => String] $dados: dados a serem submetidos
    // Object || Array[String => String] $cookies: dados a serem enviados por cookie
    //
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        $metodo = strtoupper($metodo);

        if ($dados) {
            $query = http_build_query((array)$dados, '', '&');
            $len = strlen($query);
            $http_query = '?'.$query;
        } else {
            $query = '';
            $len = 0;
            $http_query = '';
        }

        if ($cookies) {
            $vt_cookies = array();
            foreach ($cookies as $c => $v) {
                $vt_cookies[] = urlencode($c).'='.urlencode($v);
            }
            $http_cookies = 'Cookie: '.implode('; ', $vt_cookies)."\n";
        } else {
            $http_cookies = '';
        }

        $protocolo = 'HTTP/1.1';

        switch ($metodo) {
        case 'GET':
            $h = "GET {$path}{$http_query} {$protocolo}\n".
                 "Host: {$host}\n".
                 "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\n".
                 $http_cookies.
                 "Connection: keep-alive\n".
                 "\n";
            break;

        case 'POST':
            $h = "POST {$path} {$protocolo}\n".
                 "Host: {$host}\n".
                 "Content-Type: application/x-www-form-urlencoded\n".
                 "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\n".
                 "Content-Length: {$len}\n".
                 $http_cookies.
                 "Connection: keep-alive\n".
                 "\n".
                 $query;
            break;
        default:
            $h = "{$metodo} {$path} {$protocolo}\n".
                 "Host: {$host}\n".
                 ($len ? "Content-Type: application/x-www-form-urlencoded\n" : '').
                 "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\n".
                 ($len ? "Content-Length: {$len}\n" : '').
                 $http_cookies.
                 "Connection: close\n".
                 "\n".
                 ($len ? $query : '');
            break;
        }
        return $h;
    }


    //
    //     Obtem o header da resposta
    //
    static private function get_header_resposta(&$socket) {
    // Resource $socket: conexao socket aberta
    //
        $h = '';
        $time = time();
        $limite = 10; // 10 segundos
        do {
            $h .= fread($socket, 1);
            if ($time + $limite < time()) {
                break;
            }
        } while (!preg_match('/\\r\\n\\r\\n$/', $h) && !preg_match('/\\n\\n$/', $h));
        return $h;
    }


    //
    //     Interpreta um header HTTP de resposta e retorna na forma de um vetor associativo
    //
    static public function parse_header($header) {
    // String $header: headet HTTP a ser interpretado
    //
        $vt = explode((preg_match('/\\r\\n\\r\\n$/', $header) ? "\r\n" : "\n"), $header);

        $retorno = array();

        // Interpretar primeira linha
        $resultado = new stdClass();
        sscanf(array_shift($vt), '%s %d %s', $resultado->protocolo, $resultado->cod, $resultado->str);

        $retorno['resultado'] = $resultado;

        foreach ($vt as $item) {
            if ($item = trim($item)) {
                $p = strpos($item, ':');
                $cod = strtolower(substr($item, 0, $p));
                $valor = trim(substr($item, $p + 1));
                $retorno[$cod] = $valor;
            }
        }
        return $retorno;
    }


    //
    //     Obtem o conteudo da resposta HTTP
    //
    static private function get_conteudo_resposta(&$socket, $vt_header) {
    // Resource $socket: conexao socket aberta
    // Array[String => String] $vt_header: vetor com os dados do cabecalho da resposta
    //
        $c = '';

        // Caso tenha sido enviado truncado (em blocos)
        if (isset($vt_header['transfer-encoding']) && $vt_header['transfer-encoding'] == 'chunked') {

            $md = stream_get_meta_data($socket);
            while (!$md['eof']) {
                $chunk_hex_size = fgets($socket);

                $pos = strpos($chunk_hex_size, ';');
                if ($pos !== false) {
                    $chunk_hex_size = substr($chunk_hex_size, 0, $pos);
                } else {
                    $chunk_hex_size = trim($chunk_hex_size);
                }

                // Aguardar
                if ($chunk_hex_size === '') {
                    usleep(5000);

                // Fim do arquivo em chunk
                } elseif ($chunk_hex_size === '0') {
                    $nova_linha = fread($socket, 2);

                // Obter chunk
                } else {
                    $chunk_size = hexdec($chunk_hex_size);
                    $chunk_data = '';
                    $read_size = 0;
                    while ($read_size < $chunk_size) {
                        $chunk_data .= fread($socket, $chunk_size - $read_size);
                        $read_size = strlen($chunk_data);
                    }
                    $nova_linha = fread($socket, 2); //CRLF
                    $c .= $chunk_data;
                }
                $md = stream_get_meta_data($socket);
            }

        // Caso tenha sido enviado em um bloco
        } else {
            if (isset($vt_header['content-length'])) {
                while (!feof($socket) && (strlen($c) < (int)$vt_header['content-length'])) {
                    $c .= fread($socket, 1024);
                }
            } else {
                $bytes = 128;
                do {
                    $c .= fread($socket, $bytes);
                    $meta = stream_get_meta_data($socket);
                    $bytes = min($meta['unread_bytes'], 128);
                } while ($bytes);
            }
        }
        if (isset($vt_header['content-length'])) {
            $c = substr($c, 0, (int)$vt_header['content-length']);
        }

        // Descomprimir o conteudo, caso tenha sido comprimido
        if (isset($vt_header['content-encoding'])) {
            $c = self::descomprimir($vt_header['content-encoding'], $c);
        }
        return $c;
    }


    //
    //     Descomprime um conteudo pelo metodo GZIP ou DEFLATE
    //
    static public function descomprimir($metodo, $conteudo) {
    // String $metodo: metodo usado na compressao (gzip ou deflate)
    // String $conteudo: valor comprimido
    //
        switch (strtolower($metodo)) {
        case 'gzip':
            if (function_exists('gzdecode')) {
                return gzdecode($conteudo);
            } else {
                echo 'Erro ao descomprimir conte&uacute;do com m&eacute;todo gzdecode';
                exit(1);
            }
        case 'deflate':
            if (functioin_exists('gzinflate')) {
                return gzinflate($conteudo);
            } else {
                echo 'Erro ao descomprimir conte&uacute;do com m&eacute;todo gzdecode';
                exit(1);
            }
        }
        return '';
    }


    //
    //     Obtem o tipo de codificacao do texto retornado
    //
    static public function get_charset(&$vt_header) {
    // Array[String => String] $vt_header: vetor com os dados do header
    //
        if (isset($vt_header['content-type']) && $pos = stripos($vt_header['content-type'], ';')) {
            $pos = stripos($vt_header['content-type'], '=', $pos) + 1;
            return trim(substr($vt_header['content-type'], $pos));
        }
        $charsets = explode(',', $_SERVER['HTTP_ACCEPT_CHARSET']);
        return strtolower(array_shift($charsets));
    }

}//class
