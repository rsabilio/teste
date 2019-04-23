<?php
//
// SIMP
// Descricao: Classe que controla o envio de E-mail (simples e SMTP)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.34
// Data: 16/08/2007
// Modificado: 03/08/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('EMAIL_PREFIXO',      $CFG->preassunto);
define('EMAIL_NOME_SITE',    $CFG->titulo);
define('EMAIL_FORMATO_DATA', $CFG->formato_data);
define('EMAIL_FORMATO_HORA', $CFG->formato_hora);
define('EMAIL_CHARSET',      $CFG->charset);
define('EMAIL_REM_PADRAO',   'Sistema '.$CFG->titulo);
define('EMAIL_END_PADRAO',   $CFG->email_padrao);
define('EMAIL_TEMPO_LIMITE', 0.01);

// Tipos de envio de e-mail
define('EMAIL_TIPO_DEVEL',   0); // nao envia o e-mail, apenas retorna que o e-mail foi enviado com sucesso
define('EMAIL_TIPO_PADRAO',  1);
define('EMAIL_TIPO_SMTP',    2);
define('EMAIL_TIPO_IMAP',    3);

// Niveis de prioridade
define('EMAIL_PRIORIDADE_ALTISSIMA',  1);
define('EMAIL_PRIORIDADE_ALTA',       2);
define('EMAIL_PRIORIDADE_NORMAL',     3);
define('EMAIL_PRIORIDADE_BAIXA',      4);
define('EMAIL_PRIORIDADE_BAIXISSIMA', 5);

// Constantes para utilizar SMTP
define('EMAIL_SMTP_HOST',    $CFG->smtp_host);
define('EMAIL_SMTP_PORTA',   $CFG->smtp_porta);
define('EMAIL_SMTP_USUARIO', $CFG->smtp_usuario);
define('EMAIL_SMTP_SENHA',   $CFG->smtp_senha);

final class email {

    // Atributos do e-mail
    private $nome_remetente     = EMAIL_REM_PADRAO;
    private $email_remetente    = EMAIL_END_PADRAO;
    private $nome_destinatario  = '';
    private $email_destinatario = '';
    private $assunto            = '';
    private $mensagem           = '';
    private $mensagem_html      = '';
    private $tipo               = EMAIL_TIPO_PADRAO;
    private $prioridade         = EMAIL_PRIORIDADE_NORMAL;
    private $confirmacao        = false;
    private $copias;
    private $copias_ocultas;
    private $anexos;

    // Atributos SMTP
    private $smtp_conexao  = false;
    private $smtp_host     = false;
    private $smtp_porta    = 25;
    private $smtp_usuario  = '';
    private $smtp_senha    = '';
    private $smtp_log      = '';

    // Atributos de controle
    private $erros;

    // Atributos extras
    private $eol;


    //
    //     Construtor
    //
    public function __construct($assunto = '') {
    // String $assunto: assunto do email
    //
        global $CFG;

        $this->set_assunto($assunto);
        $this->copias         = array();
        $this->copias_ocultas = array();
        $this->anexos         = array();
        $this->erros          = array();
        $this->eol            = self::get_eol();

        $this->set_tipo_envio($CFG->tipo_email);

        $host    = defined('EMAIL_SMTP_HOST')    ? EMAIL_SMTP_HOST    : 'localhost';
        $porta   = defined('EMAIL_SMTP_PORTA')   ? EMAIL_SMTP_PORTA   : 25;
        $usuario = defined('EMAIL_SMTP_USUARIO') ? EMAIL_SMTP_USUARIO : false;
        $senha   = defined('EMAIL_SMTP_SENHA')   ? EMAIL_SMTP_SENHA   : false;
        $this->set_smtp($host, $porta, $usuario, $senha);
    }


    //
    //     Define as configuracoes de SMTP caso desejado
    //
    public function set_smtp($host, $porta, $usuario, $senha) {
    // String $host: host do servidor SMTP
    // Int $porta: porta para conexao
    // String $usuario: usuario para conexao
    // String $senha: senha para conexao
    //
        $this->smtp_host    = $host;
        $this->smtp_porta   = $porta;
        $this->smtp_usuario = $usuario;
        $this->smtp_senha   = $senha;
    }


    //
    //     Define o tipo de envio do e-mail
    //
    public function set_tipo_envio($tipo) {
    // Int $tipo: uma das constantes de tipo de envio
    //
        switch ($tipo) {
        case EMAIL_TIPO_DEVEL:
        case EMAIL_TIPO_PADRAO:
        case EMAIL_TIPO_SMTP:
        case EMAIL_TIPO_IMAP:
            $this->tipo = $tipo;
            return true;
        }
        trigger_error('Tipo invalido de envio de e-mail: '.$tipo, E_USER_ERROR);
        return false;
    }


    //
    //     Define o assunto do e-mail
    //
    public function set_assunto($assunto) {
    // String $assunto: define o assunto do e-mail
    //
        $tr = array(
            "\n" => '',
            "\r" => '',
            "\t" => ' '
        );
        $assunto = trim(strtr($assunto, $tr));
        $this->assunto = substr(trim(EMAIL_PREFIXO).' '.$assunto, 0, 255);
    }


    //
    //     Define a prioridade do e-mail
    //
    public function set_prioridade($prioridade) {
    // Int $prioridade: Altissima - 1 / Alta - 2 / Normal - 3 / Baixa - 4 / Baixissima 5
    //
        $prioridade = (int)$prioridade;
        if ($prioridade >= EMAIL_PRIORIDADE_BAIXISSIMA && $prioridade <= EMAIL_PRIORIDADE_ALTISSIMA) {
            $this->prioridade = $prioridade;
        }
    }


    //
    //     Define se o e-mail deve ter confirmacao ou nao
    //
    public function set_confirmacao($confirmacao) {
    // Bool $confirmacao: flag indicando se deve ou nao haver confirmacao
    //
        $this->confirmacao = (bool)$confirmacao;
    }


    //
    //     Define os valores do remetente
    //
    public function set_remetente($nome, $email) {
    // String $nome: nome do remetente
    // String $email: e-mail do remetente
    //
        // Validar Nome
        if ($this->validar_nome($nome, 'nome do remetente')) {
            $this->nome_remetente = $nome;
        }

        // Validar E-mail
        if ($this->validar_email($email, 'e-mail do remetente')) {
            $this->email_remetente = $email;
        }
    }


    //
    //     Define os valores do destinatario
    //
    public function set_destinatario($nome, $email) {
    // String $nome: nome do destinatario
    // String $email: e-mail do destinatario
    //
        // Validar Nome
        if ($this->validar_nome($nome, 'nome do destinat&aacute;rio')) {
            $this->nome_destinatario = $nome;
        }

        // Validar E-mail
        if ($this->validar_email($email, 'e-mail do destinat&aacute;rio')) {
            $this->email_destinatario = $email;
        }
    }


    //
    //     Adiciona um e-mail para envio de copia simples
    //
    public function adicionar_copia($nome, $email, $tipo = 'simples') {
    // String $nome: nome do destinatario por copia
    // String $email: e-mail do destinatario por copia
    // String $tipo: tipo de copia 'simples' ou 'oculta'
    //
        $pessoa = new stdClass();

        // Validar Nome
        if ($this->validar_nome($nome, 'nome do destinat&aacute;rio da c&oacute;pia')) {
            $pessoa->nome = $nome;
        }

        // Validar E-mail
        if ($this->validar_email($email, 'e-mail do destinat&aacute;rio da c&oacute;pia')) {
            $pessoa->email = $email;
        }

        // Adicionar no vetor correspondente
        switch ($tipo) {
        case 'simples':
            $this->copias[] = $pessoa;
            break;
        case 'oculta':
            $this->copias_ocultas[] = $pessoa;
            break;
        default:
            $this->erros[] = 'Tipo inv&aacute;lido de c&oacute;pia: '.$tipo;
            break;
        }
    }


    //
    //     Define a mensagem do e-mail
    //
    public function set_mensagem($mensagem, $html = false) {
    // String $mensagem: mensagem a ser enviada
    // Bool $html: mensagem no formato HTML
    //
        // Guardando a mensagem no formato Texo ou Html
        if ($html) {
            $this->mensagem_html = "<div>{$mensagem}</div>".$this->rodape_mensagem(true);
        } else {
            $this->mensagem = $mensagem.$this->rodape_mensagem();
        }
    }


    //
    //     Retorna o rodape da mensagem
    //
    private function rodape_mensagem($html = false) {
    // Bool $html: flag indicando se o rodape e' para uma mensagem em HTML ou nao
    //
        for ($digitos = '', $i = 34; $i < 256; $digitos .= chr($i++));
        $separador = chr(33);

        if ($html) {
            return '<hr />'.
                   '<div>'.
                   '<p>E-mail enviado pelo site '.EMAIL_NOME_SITE.'</p>'.
                   '<p>Data: '.strftime(EMAIL_FORMATO_DATA.' - '.EMAIL_FORMATO_HORA).'</p>'.
                   '</div>';
        } else {
            return $this->eol.
                   $this->eol.
                   '--'.$this->eol.
                   'E-mail enviado pelo site '.EMAIL_NOME_SITE.$this->eol.
                   'Data: '.strftime(EMAIL_FORMATO_DATA.' - '.EMAIL_FORMATO_HORA);
        }
    }


    //
    //     Anexa um arquivo do servidor ao e-mail
    //
    public function adicionar_anexo($arquivo, $descricao = false, $tipo = false, $link_alternativo = false) {
    // String $arquivo: nome do arquivo no servidor
    // String $descricao: descricao do arquivo anexado
    // String || Bool $tipo: mime-type do arquivo (false para obter automaticamente)
    // String $link_alternativo: endereco do link alternativo para a imagem a ser embutida
    //
        if (!file_exists($arquivo)) {
            $this->erros[] = "Arquivo {$arquivo} n&atilde;o existe";
            return false;
        } elseif (!is_readable($arquivo)) {
            $this->erros[] = "O arquivo {$arquivo} n&atilde;o tem permiss&atilde;o de leitura";
            return false;
        }

        $nome_arquivo = basename($arquivo);
        $conteudo = file_get_contents($arquivo);
        if (!$tipo) {
            $tipo = util::get_mime($arquivo);
        }
        $this->adicionar_conteudo_anexo($conteudo, $nome_arquivo, $descricao, $tipo, $link_alternativo);
    }


    //
    //     Adicionar o conteudo de um arquivo como anexo do e-mail
    //
    public function adicionar_conteudo_anexo($conteudo_arquivo, $arquivo, $descricao, $tipo, $link_alternativo = false) {
    // String $conteudo_arquivo: sequencia de bytes do arquivo
    // String $arquivo: nome do arquivo a ser anexado
    // String $descricao: descricao do arquivo anexado
    // String || Bool $tipo: mime-type do arquivo (false para obter automaticamente)
    // String $link_alternativo: endereco do link alternativo para a imagem a ser embutida
    //
        // Codificar em Base64 e quebrar linha a cada 76 caracteres
        $arquivo_codificado = base64_encode($conteudo_arquivo);
        $arquivo_codificado = chunk_split($arquivo_codificado, 76, $this->eol);

        // Gerar anexo
        $obj = new stdClass();
        $obj->nome = basename($arquivo);
        $obj->descricao = $descricao ? $descricao : $obj->nome;
        $obj->link_alternativo = $link_alternativo;
        $obj->tipo = $tipo;
        $obj->conteudo = trim($arquivo_codificado);

        // Se nao conseguiu obter o mime-type do arquivo
        if (!$obj->tipo) {
            $obj->tipo = 'application/octet-stream';
        }

        // Adicionar no vetor de anexos
        $this->anexos[] = $obj;
    }


    //
    //     Envia o e-mail
    //
    public function enviar() {
        $this->checar();
        if ($this->possui_erros()) {
            return false;
        }

        // Gerar um ID para a mensagem
        list($login, $dominio) = explode('@', $this->email_remetente);
        list($mili, $time) = explode(' ', microtime());
        $data = date('YmdHis', $time).number_format($mili * 100000, 0, '.', '');
        $remetente_codificado = md5($login).'@'.$dominio;
        $id_mensagem = $data.'.'.$remetente_codificado;

        // Montar o conteudo do email com a mensagem e os anexos
        $partes = $this->montar_conteudo();

        // Gerar mime boundary
        $boundary = self::gerar_boundary($partes);
        if ($boundary === false) {
            $this->erros[] = 'Erro eo enviar e-mail. Dificuldade em montar o separador de partes do e-mail (mime-boundary).';
            return false;
        }

        // Montar enderecos
        $remetente    = $this->montar_email($this->nome_remetente, $this->email_remetente);
        $destinatario = $this->montar_email($this->nome_destinatario, $this->email_destinatario);

        // Montar enderecos das Copias Simples
        $vt_cc = array();
        foreach ($this->copias as $pessoa) {
            $vt_cc[] = $this->montar_email($pessoa->nome, $pessoa->email);
        }
        $cc = implode(', ', $vt_cc);

        // Montar enderecos das Copias Ocultas
        $vt_bcc = array();
        foreach ($this->copias_ocultas as $pessoa) {
            $vt_bcc[] = $this->montar_email($pessoa->nome, $pessoa->email);
        }
        $bcc = implode(', ', $vt_bcc);

        // Montar assunto
        $assunto = $this->codificar($this->assunto);

        // Preparar o cabecalho do e-mail
        $content_type = 'multipart/mixed';

        if ($this->confirmacao) {
            $confirmacao = 'Disposition-Notification-To: '.$remetente.$this->eol;
        } else {
            $confirmacao = '';
        }

        $descricoes_prioridade = array(EMAIL_PRIORIDADE_ALTISSIMA  => '(Highest)',
                                       EMAIL_PRIORIDADE_ALTA       => '(High)',
                                       EMAIL_PRIORIDADE_NORMAL     => '(Normal)',
                                       EMAIL_PRIORIDADE_BAIXA      => '(Low)',
                                       EMAIL_PRIORIDADE_BAIXISSIMA => '(Lowest)');

        // Montar o cabecalho do e-mail
        $cabecalho = "Message-Id: <{$id_mensagem}>".$this->eol.
                     "From: {$remetente}".$this->eol.
                     "To: {$destinatario}".$this->eol.
                     "Cc: {$cc}".$this->eol.
                     "Bcc: {$bcc}".$this->eol.
                     "Reply-To: {$remetente}".$this->eol.
                     "Subject: {$assunto}".$this->eol.
                     "Date: ".date('D, d M Y H:i:s O').$this->eol.
                     'User-Agent: '.texto::strip_acentos(EMAIL_NOME_SITE).' Webmail'.$this->eol.
                     'MIME-Version: 1.0'.$this->eol.
                     $confirmacao.
                     "Content-Type: {$content_type}; boundary=\"{$boundary}\"".$this->eol.
                     'X-Mailer: PHP mail function'.$this->eol.
                     'X-Priority: '.$this->prioridade.' '.$descricoes_prioridade[$this->prioridade].$this->eol;

        // Montar o conteudo do e-mail
        $conteudo = "--{$boundary}".$this->eol.
                    implode($this->eol."--{$boundary}".$this->eol, $partes).$this->eol.
                    "--{$boundary}--";

        $enviou = false;
        switch ($this->tipo) {
        case EMAIL_TIPO_DEVEL:
            $enviou = true; // Simular que enviou corretamente

// Guardar em /tmp para depuracao
//file_put_contents('/tmp/email-'.$id_mensagem.'.txt', $cabecalho."\n".$conteudo);

// Enviar para outro e-mail para depuracao
//mail('teste@teste.com.br', $this->assunto, $conteudo, $cabecalho);
            break;
        case EMAIL_TIPO_PADRAO:
            if (!function_exists('mail')) {
                $this->erros[] = 'Erro ao enviar o e-mail. O servidor n&atilde;o suporta envio de e-mail nativo da plataforma PHP.';
            } else {
                $enviou = mail($destinatario, $this->assunto, $conteudo, $cabecalho);
                if (!$enviou) {
                    $this->erros[] = 'Erro ao enviar o e-mail. Talvez o servidor de e-mail esteja sobrecarregado.';
                }
            }
            break;
        case EMAIL_TIPO_SMTP:
            $enviou = $this->enviar_smtp($destinatario, $this->assunto, $conteudo, $cabecalho);
            break;
        case EMAIL_TIPO_IMAP:
            if (!function_exists('imap_mail')) {
                $this->erros[] = 'Erro ao enviar o e-mail. O servidor n&atilde;o suporta envio de e-mail do tipo IMAP.';
            } else {
                $enviou = imap_mail($destinatario, $assunto, $conteudo, $cabecalho, $cc, $bcc);
                if (!$enviou) {
                    $erro = imap_last_error();
                    $this->erros[] = 'Erro ao enviar o e-mail'.($erro ? ' (Detalhes: '.$erro.')' : '');
                }
            }
            break;
        }
        return $enviou;
    }


    //
    //     Gera um mime-boundary para encapsular as partes
    //
    private static function gerar_boundary(&$partes) {
    // Array[String] $partes: partes do e-mail
    //
        // Tentar gerar um boundary de tamanho 5 a 70
        for ($tamanho = 5; $tamanho <= 70; $tamanho += 5) {

            // Para cada tamanho, tentar gerar um boundary valido no maximo 3 vezes
            for ($i = 0; $i < 3 ; $i++) {
                $boundary = self::gerar_boundary_aleatorio($tamanho);

                // Checar se o boundary aparece nas partes
                $aparece = false;
                foreach ($partes as $parte) {
                    if (strpos($parte, $boundary) !== false) {
                        $aparece = true;
                        break;
                    }
                }

                // Se nao aparece: eh valido
                if (!$aparece) {
                    return $boundary;
                }
            }
        }
        return false;
    }


    //
    //     Gera uma sequencia aleatoria com um tamanho (RFC2046)
    //
    private static function gerar_boundary_aleatorio($tamanho) {
    // Int $tamanho: tamanho da sequencia gerada
    //
        // Alfabeto permitido pela RFC2046
        // Foram ignorados: espaco, hifen (menos) e aspas simples
        $alfabeto = '0123456789'.
                    'abcdefghijklmnopqrstuvwxyz'.
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
                    '()+_,./:=?';
        $len = strlen($alfabeto);

        $s = '';
        $tamanho = abs($tamanho);
        for ($i = 0; $i < $tamanho; $i++) {
            $rand = mt_rand(0, $len - 1);
            $s .= $alfabeto[$rand];
        }
        return $s;
    }


    //
    //     Quebra uma linha de resposta do servidor SMTP
    //
    private static function quebrar_resposta_smtp($resposta, &$match) {
    // String $resposta: linha de resposta
    // Array[String] $match: valores obtidos
    //
        return preg_match('/^([0-9]{3})[\s|\040|-]{1}(.*)[\r|\n]*$/', $resposta, $match);
    }


    //
    //     Envia o e-mail usando SMTP
    //
    private function enviar_smtp($destinatario, $assunto, $conteudo, $cabecalho) {
    // String $destinatario: destinatario do e-mail
    // String $assunto: assunto do e-mail
    // String $conteudo: conteudo do arquivo
    // String $cabecalho: cabecalho HTTP
    //
        // Checar se ha' erros
        $this->checar_smtp();
        if ($this->possui_erros()) {
            return false;
        }

        // Abrindo conexao
        $this->smtp_conexao = fsockopen($this->smtp_host, $this->smtp_porta, $cod_erro, $str_erro, EMAIL_TEMPO_LIMITE);

        // Se nao conectou
        if (!$this->smtp_conexao) {
            $this->erros[] = "Erro ao conectar no servidor SMTP (Erro {$cod_erro})";
            if ($str_erro) {
                $this->erros[] = "Resposta do servidor: {$str_erro}";
            }
            return false;
        }
        $this->smtp_log = '';

        // Obter resposta
        $resposta = $this->smtp_get();
        self::quebrar_resposta_smtp($resposta, $match);
        switch ($match[1]) {
        case '220':
        case '200':
            // OK
            break;
        case '421':
            $this->erros[] = 'Servidor de e-mail indispon&iacute;vel no momento (Erro '.$match[1].': '.$match[2].')';
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        default:
            $this->erros[] = 'Erro no servidor de e-mail (Erro '.$match[1].': '.$match[2].')';
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        }

        // Apresentando-se ao servidor SMTP
        $this->smtp_put("EHLO {$this->smtp_host}");
        $vt_autenticacao = array();

        // Obter resposta
        $respostas = explode($this->eol, $this->smtp_get());
        foreach ($respostas as $resposta) {
            self::quebrar_resposta_smtp($resposta, $match);
            switch ($match[1]) {
            case '250':
            case '200':
                if (strpos($match[2], 'AUTH') !== false) {
                    $vt = explode(' ', substr($match[2], strlen('AUTH') + 1));
                    foreach ($vt as $item) {
                        $vt_autenticacao[] = strtolower($item);
                    }
                }
                break;
            case '421':
                $this->erros[] = 'Servidor de e-mail indispon&iacute;vel no momento (Erro '.$match[1].': '.$match[2].')';
                $this->smtp_put('QUIT');
                fclose($this->smtp_conexao);
                return false;
            default:
                $this->erros[] = 'Erro no servidor de e-mail (Erro '.$match[1].': '.$match[2].')';
                $this->smtp_put('QUIT');
                fclose($this->smtp_conexao);
                return false;
            }
        }

        //TODO: checar se os metodos DIGEST-MD5 e CRAM-MD5 estao funcionando
        if ($this->smtp_usuario && $this->smtp_senha) {

            // DIGEST-MD5 (RFC 2831): http://www.ietf.org/rfc/rfc2831.txt
            if (in_array('digest-md5', $vt_autenticacao)) {
                $this->smtp_put('AUTH DIGEST-MD5');

                $resposta = $this->smtp_get();
                self::quebrar_resposta_smtp($resposta, $match);
                switch ($match[1]) {
                case '334':
                    $challenge = base64_decode($match[2]);
                    $credenciais = $this->montar_credenciais_digest($challenge);
                    break;
                default:
                    $this->erros[] = "Erro ao autenticar pelo m&eacute;todo DIGEST-MD5";
                    fclose($this->smtp_conexao);
                    return false;
                }
                $this->smtp_put($credenciais);

            // CRAM-MD5 (RFC 2195): http://tools.ietf.org/html/rfc2195
            } elseif (in_array('cram-md5', $vt_autenticacao)) {
                $this->smtp_put('AUTH CRAM-MD5');

                $resposta = $this->smtp_get();
                self::quebrar_resposta_smtp($resposta, $match);
                switch ($match[1]) {
                case '334':
                    $challenge = base64_decode($match[2]);
                    $digest = $this->hmac_md5($challenge, $this->smtp_senha);
                    $credenciais = base64_encode($this->smtp_usuario.' '.$digest);
                    break;
                default:
                    $this->erros[] = "Erro ao autenticar pelo m&eacute;todo CRAM-MD5";
                    fclose($this->smtp_conexao);
                    return false;
                }
                $this->smtp_put($credenciais);

            // LOGIN
            } elseif (in_array('login', $vt_autenticacao)) {
                $this->smtp_put('AUTH LOGIN');
                $this->smtp_put(base64_encode($this->smtp_usuario));
                $this->smtp_put(base64_encode($this->smtp_senha));

            // PLAIN
            } elseif (in_array('plain', $vt_autenticacao)) {
                $credenciais = base64_encode("\000{$this->smtp_usuario}\000{$this->smtp_senha}");
                $this->smtp_put('AUTH PLAIN '.$credenciais);
            }

            // Verificar se autenticou corretamente
            $resposta = $this->smtp_get();
            self::quebrar_resposta_smtp($resposta, $match);
            switch ($match[1]) {
            case '235':
                // OK!
                break;
            default:
                $this->erros[] = 'Erro durante a autentica&ccedil;&atilde;o (Erro '.$match[1].': '.$match[2].')';
                $this->smtp_put('QUIT');
                fclose($this->smtp_conexao);
                return false;
            }
        }

        // Definir remetente
        $this->smtp_put("MAIL FROM: {$this->email_remetente}");

        $resposta = $this->smtp_get();
        self::quebrar_resposta_smtp($resposta, $match);
        switch ($match[1]) {
        case '250':
            // OK
            break;
        default:
            $this->erros[] = "Erro no e-mail de origem \"{$this->email_remetente}\"";
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        }

        // Definir destinatario principal
        $this->smtp_put("RCPT TO: {$this->email_destinatario}");

        $resposta = $this->smtp_get();
        self::quebrar_resposta_smtp($resposta, $match);
        switch ($match[1]) {
        case '250':
            // OK
            break;
        default:
            $this->erros[] = "Erro no e-mail de destino \"{$this->email_destinatario}\"";
            trigger_error('E-mail invalido '.$this->email_destinatario.' (resposta SMTP: '.$resposta.')', E_USER_NOTICE);
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        }

        // Definir destinatarios secundarios
        foreach ($this->copias as $pessoa) {
            $this->smtp_put("RCPT TO: {$pessoa->email}");

            $resposta = $this->smtp_get();
            self::quebrar_resposta_smtp($resposta, $match);
            switch ($match[1]) {
            case '250':
                // OK
                break;
            default:
                $this->erros[] = "Erro no e-mail de destino \"{$this->email_remetente}\"";
                $this->smtp_put('QUIT');
                fclose($this->smtp_conexao);
                return false;
            }
        }

        // Definir destinatarios ocultos
        foreach ($this->copias_ocultas as $pessoa) {
            $this->smtp_put("RCPT TO: {$pessoa->email}");

            $resposta = $this->smtp_get();
            self::quebrar_resposta_smtp($resposta, $match);
            switch ($match[1]) {
            case '250':
                // OK
                break;
            default:
                $this->erros[] = "Erro no e-mail de destino \"{$this->email_remetente}\"";
                $this->smtp_put('QUIT');
                fclose($this->smtp_conexao);
                return false;
            }
        }

        // Definir o conteudo do e-mail
        $this->smtp_put('DATA');

        $resposta = $this->smtp_get();
        self::quebrar_resposta_smtp($resposta, $match);
        switch ($match[1]) {
        case '354':
            // OK
            break;
        default:
            $this->erros[] = 'Erro na montagem do e-mail (Erro '.$match[1].': '.$match[2].')';
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        }

        // Enviar cabecalho
        $this->smtp_put($cabecalho);
        $this->smtp_put($this->eol);

        // Enviar conteudo
        $this->smtp_put($conteudo);

        // Indicar final de conteudo
        $this->smtp_put('.');

        $resposta = $this->smtp_get();
        self::quebrar_resposta_smtp($resposta, $match);
        switch ($match[1]) {
        case '250':
            // OK
            break;
        default:
            $this->erros[] = 'Erro no corpo do e-mail (Erro '.$match[1].': '.$match[2].')';
            $this->smtp_put('QUIT');
            fclose($this->smtp_conexao);
            return false;
        }

        // Fechando a conexao com o servidor SMTP
        $this->smtp_put('QUIT');

        $resposta = $this->smtp_get();
        return fclose($this->smtp_conexao);
    }


    //
    //     Monta as credenciais para autenticacao DIGEST-MD5 (codigo baseado no arquivo auth.php do projeto SquirrelMail)
    //
    private function montar_credenciais_digest($challenge) {
    // String $challenge: desafio devolvido pelo servidor SMTP
    //
        $params = array();
        while (preg_match('/^([A-Za-z-]+)=([\"][^"]*[\"]|[^,]+)[,](.*)/', $challenge, $param)) {
            $valor = ($param[2][0] == '"') ? substr($param[2], 1, -1) : $param[2];
            $params[$param[1]] = $valor;
            $challenge = $param[3];
        }
        preg_match('/^([A-Za-z-]+)=([\"][^"]*[\"]|[^,]+)$/', $challenge, $param);
        $valor = ($param[2][0] == '"') ? substr($param[2], 1, -1) : $param[2];
        $params[$param[1]] = $valor;

        $cnonce = base64_encode(bin2hex($this->hmac_md5(microtime())));
        $ncount = sprintf('%08d', 1);

        $qop_value = 'auth';
        $digest_uri_value = 'imap/'.$this->smtp_host;

        $string_a1 = utf8_encode($this->smtp_usuario.':'.$params['realm'].':'.$this->smtp_senha);
        $string_a1 = $this->hmac_md5($string_a1);

        $A1 = $string_a1.':'.$param['nonce'].':'.$cnonce;
        $A1 = bin2hex($this->hmac_md5('md5', $A1));

        $A2 = 'AUTHENTICATE:'.$digest_uri_value;

        if ($qop_value != 'auth') {
            $A2 .= ':'.str_repeat('0', 32);
        }
        $A2 = bin2hex($this->hmac_md5($A2));

        $string_response = $params['nonce'].':'.$ncount.':'.$cnonce.':'.$qop_value;
        $response_value = bin2hex($this->hmac_md5($A1.':'.$string_response.':'.$A2));

        $reply = 'charset=utf-8,username="'.$this->smtp_usuario.'",realm="'.$params['realm'].'",'.
                 'nonce="'.$params['nonce'].'",nc='.$ncount.',cnonce="'.$cnonce.'",'.
                 'digest-uri="'.$digest_uri_value.'",response='.$response_value.
                 ',qop='.$qop_value;

        return base64_encode($reply);
    }


    //
    //     Gera o hash MD5 com chave (codigo baseado no arquivo auth.php do projeto SquirrelMail)
    //
    private function hmac_md5($data, $key = '') {
    // String $data: dado a ser criptografado
    // String $key: chave de criptografia
    //
        if (extension_loaded('mhash')) {
            if ($key === '') {
                $hmac = mhash(MHASH_MD5, $data);
            } else {
                $hmac = mhash(MHASH_MD5, $data, $key);
            }
            return $hmac;
        } elseif (extension_loaded('hash') && in_array('md5', hash_algos())) {
            if (!$chave) {
                $hmac = hash('md5', $str, true);
            } else {
                $hmac = hash_hmac('md5', $str, $chave, true);
            }
            return $hmac;
        }

        if (!$key) {
            return pack('H*', md5($data));
        }
        $key = str_pad($key, 64, chr(0x00));
        if (strlen($key) > 64) {
            $key = pack('H*', md5($key));
        }
        $k_ipad = $key ^ str_repeat(chr(0x36), 64) ;
        $k_opad = $key ^ str_repeat(chr(0x5c), 64) ;

        $hmac = $this->hmac_md5($k_opad.pack('H*', md5($k_ipad.$data)));
        return $hmac;
    }


    //
    //     Envia um comando ao servidor SMTP aberto
    //
    private function smtp_put($mensagem) {
    // String $mensagem: comando ou mensagem a ser enviada
    //
        fwrite($this->smtp_conexao, $mensagem.$this->eol);

        // Guardar log de comunicacao com SMTP
        $this->smtp_log .= '[Envio '.microtime(true).'] '.$mensagem.$this->eol;
    }


    //
    //     Obtem a resposta do servidor SMTP
    //
    private function smtp_get() {
        $bytes = 128;
        $resposta = '';

        do {
            $resposta .= fread($this->smtp_conexao, $bytes);
            $meta = stream_get_meta_data($this->smtp_conexao);
            $bytes = min($meta['unread_bytes'], 128);
        } while ($bytes);

        if (substr($resposta, -2) == "\r\n") {
            $resposta = substr($resposta, 0, -2);
        } elseif (substr($resposta, -1) == "\n") {
            $resposta = substr($resposta, 0, -1);
        } elseif (substr($resposta, -1) == "\r") {
            $resposta = substr($resposta, 0, -1);
        }
        $resposta = trim($resposta);

        // Guardar log de comunicacao com SMTP
        $this->smtp_log .= '[Resposta '.microtime(true).'] '.$resposta.$this->eol;

        return $resposta;
    }


    //
    //     Retorna o log entre o cliente e servidor SMTP
    //
    public function smtp_get_log() {
        return $this->smtp_log;
    }


    //
    //     Monta o conteudo do e-mail com os anexos
    //
    private function montar_conteudo() {

        // Montar as partes do e-mail
        $partes = array();

        // Mensagem Texto
        $mensagem_texto = '';
        if ($this->mensagem) {
            if (function_exists('quoted_printable_encode')) {
                $header = 'Content-Type: text/plain; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: quoted-printable'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = quoted_printable_encode($this->mensagem);
            } elseif (function_exists('imap_8bit')) {
                $header = 'Content-Type: text/plain; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: quoted-printable'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = imap_8bit($this->mensagem);
            } else {
                $header = 'Content-Type: text/plain; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: base64'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = chunk_split(base64_encode($this->mensagem), 76, $this->eol);
            }

            // Nao permitir ponto final sozinho em uma linha (nos e-mails enviados por SMTP)
            if ($this->tipo == EMAIL_TIPO_SMTP || ini_get('SMTP')) {
                $parte = str_replace($this->eol.'.'.$this->eol, $this->eol.'..'.$this->eol, $parte);
            }

            $mensagem_texto = $header.$this->eol.$parte;
        }

        // Mensagem HTML
        $mensagem_html = '';
        if ($this->mensagem_html) {
            if (function_exists('quoted_printable_encode')) {
                $header = 'Content-Type: text/html; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: quoted-printable'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = quoted_printable_encode($this->mensagem_html);
            } elseif (function_exists('imap_8bit')) {
                $header = 'Content-Type: text/html; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: quoted-printable'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = imap_8bit($this->mensagem_html);
            } else {
                $header = 'Content-Type: text/html; charset='.EMAIL_CHARSET.$this->eol.
                          'Content-Transfer-Encoding: base64'.$this->eol.
                          'Content-Disposition: inline'.$this->eol;
                $parte = chunk_split(base64_encode($this->mensagem_html), 76, $this->eol);
            }

            // Nao permitir ponto final sozinho em uma linha (nos e-mails enviados por SMTP)
            if ($this->tipo == EMAIL_TIPO_SMTP || ini_get('SMTP')) {
                $parte = str_replace($this->eol.'.'.$this->eol, $this->eol.'..'.$this->eol, $parte);
            }

            $mensagem_html = $header.$this->eol.$parte;
        }

        $partes[] = $this->montar_mensagens($mensagem_texto, $mensagem_html);

        // Anexos
        foreach ($this->anexos as $arquivo) {
            $header = "Content-Type: {$arquivo->tipo}; name=\"{$arquivo->nome}\"".$this->eol.
                      "Content-Transfer-Encoding: base64".$this->eol.
                      "Content-Description: \"{$arquivo->descricao}\"".$this->eol;
            if ($arquivo->link_alternativo) {
                $header .= "Content-Location: \"{$link_alternativo}\"".$this->eol;
            } else {
                $header .= "Content-Disposition: attachment; filename=\"{$arquivo->nome}\"".$this->eol;
            }
            $parte = $arquivo->conteudo;

            // Nao permitir ponto final sozinho em uma linha (nos e-mails enviados por SMTP)
            if ($this->tipo == EMAIL_TIPO_SMTP || ini_get('SMTP')) {
                $parte = str_replace($this->eol.'.'.$this->eol, $this->eol.'..'.$this->eol, $parte);
            }

            $partes[] = $header.$this->eol.$parte;
        }

        return $partes;
    }


    //
    //     Monta as mensagens em uma parte do pacote de e-mail
    //
    private function montar_mensagens($mensagem_texto, $mensagem_html) {
    // String $mensagem_texto: mensagem em texto
    // String $mensagem_html: mensagem em HTML
    //
        // Mensagem com Texto e HTML
        if ($mensagem_texto && $mensagem_html) {
            $partes = array($mensagem_texto, $mensagem_html);
            $boundary = self::gerar_boundary($partes);

            return "Content-Type: multipart/alternative; boundary=\"{$boundary}\"".$this->eol.
                   $this->eol.
                   '--'.$boundary.$this->eol.
                   trim($mensagem_texto).$this->eol.
                   '--'.$boundary.$this->eol.
                   trim($mensagem_html).$this->eol.
                   '--'.$boundary.'--';
        }

        // Mensagem apenas com Texto
        if ($mensagem_texto) {
            return $mensagem_texto;
        }

        // Mensagem apenas com HTML
        if ($mensagem_html) {
            return $mensagem_html;
        }
        return false;
    }


    //
    //     Monta um nome e e-mail no protocolo exigido
    //
    private function montar_email($nome, $email) {
    // String $nome: nome do usuario
    // String $email: email do usuario
    //
        $nome = $this->codificar($nome);
        return "{$nome} <{$email}>";
    }


    //
    //     Checa se esta tudo pronto para mandar o e-mail
    //
    private function checar() {
        $this->validar_nome($this->nome_remetente, 'nome remetente');
        $this->validar_email($this->email_remetente, 'e-mail do remetente');

        $this->validar_nome($this->nome_destinatario, 'nome destinat&aacute;rio');
        $this->validar_email($this->email_destinatario, 'e-mail do destinat&aacute;rio');

        if (empty($this->mensagem) && empty($this->mensagem_html)) {
            $this->erros[] = 'Faltou preencher a mensagem';
        }
    }


    //
    //     Checa se esta tudo pronto para mandar o e-mail por SMTP
    //
    private function checar_smtp() {
        return !(empty($this->smtp_host) ||
                 empty($this->smtp_porta) ||
                 empty($this->smtp_usuario) ||
                 empty($this->smtp_senha));
    }


    //
    //     Verifica se possui erros
    //
    public function possui_erros() {
        return !empty($this->erros);
    }


    //
    //     Imprime as mensagens de erro
    //
    public function imprimir_erros() {
        if (!empty($this->erros)) {
            mensagem::erro($this->erros);
        }
        return false;
    }


    //
    //     Retorna o vetor de erros
    //
    public function get_erros() {
        return $this->erros;
    }


    //
    //     Faz a validacao do nome
    //
    private function validar_nome($nome, $descricao) {
    // String $nome: nome a ser validado
    // String $descricao: origem do nome
    //
        $validacao = validacao::get_instancia();

        $ok = true;
        if (empty($nome)) {
            $this->erros[] = "Faltou preencher o campo \"{$descricao}\"";
            $ok = false;
        } elseif (!$validacao->validar_campo('TEXTO_LINHA', $nome, $erro)) {
            $this->erros[] = "Campo \"{$descricao}\" possui caracteres inv&aacute;lidos ou n&atilde;o est&aacute; no padr&atilde;o ({$erro}).";
            $ok = false;
        }
        return $ok;
    }


    //
    //     Valida um e-mail
    //
    private function validar_email($email, $descricao) {
    // String $email: e-mail a ser validado
    // String $descricao: origem do e-mail
    //
        $validacao = validacao::get_instancia();

        $ok = true;
        if (empty($email)) {
            $this->erros[] = "Faltou preencher o campo \"{$descricao}\"";
            $ok = false;
        } elseif (!$validacao->validar_campo('EMAIL', $email, $erro)) {
            $this->erros[] = "Campo \"{$descricao}\" possui caracteres inv&aacute;lidos ou n&atilde;o est&aacute; no padr&atilde;o ({$erro}).";
            $ok = false;
        }
        return $ok;
    }


    //
    //     Retorna a quebra de linha
    //
    private static function get_eol() {
        return "\r\n";
    }


    //
    //     Codifica uma string caso seja necessario
    //
    private function codificar($str) {
    // String $str: string a ser codificada
    //
        // Se precisa codificar
        if (self::precisa_codificar($str)) {

            // Utilizar codificacao quoted-printed (Q)
            if (function_exists('quoted_printable_encode')) {
                $str2 = quoted_printable_encode($str);
                return '=?'.strtoupper(EMAIL_CHARSET).'?Q?'.$str2.'?=';
            } elseif (function_exists('imap_8bit')) {
                $str2 = imap_8bit($str);
                return '=?'.strtoupper(EMAIL_CHARSET).'?Q?'.$str2.'?=';
            }

            // Utilizar a codificacao base64 (B)
            $str2 = base64_encode($str);
            return '=?'.strtoupper(EMAIL_CHARSET).'?B?'.$str2.'?=';
        }
        return $str;
    }


    //
    //     Checa se existe a necessidade de codificar uma string
    //
    public static function precisa_codificar($str) {
    // String $str: string a ser analisada
    //
        $tam = strlen($str);
        for ($i = 0; $i < $tam; $i++) {
            if ((!ctype_alpha($str[$i])) && ($str[$i] != ' ')) {
                return true;
            }
        }
        return false;
    }

}//class
