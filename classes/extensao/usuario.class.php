<?php
//
// SIMP
// Descricao: Classe Usuario
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.50
// Data: 22/08/2007
// Modificado: 24/07/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
final class usuario extends usuario_base {

    //
    //     Define a forma como um atributo simples e' exibido
    //
    public function exibir_atributo($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser exibido
    //
        switch ($nome_atributo) {
        case 'senha':
            return '[valor codificado]';
        case 'email':
            return texto::proteger_email($this->get_atributo('email'));
        }
        return parent::exibir_atributo($nome_atributo);
    }


    //
    //     Retorna se um usuario pode manipular os dados de outro usuario
    //
    public function pode_ser_manipulado(&$usuario) {
    // usuario $usuario: usuario a ser testado
    //
        return $usuario->possui_grupo(COD_ADMIN) ||
               $this->get_valor_chave() == $usuario->get_valor_chave();
    }


    //
    //     Indica se o formulario de um registro pode ser acessado ou nao por um usuario
    //
    public function pode_acessar_formulario(&$usuario, &$motivo = '') {
    // usuario $usuario: usuario a ser testado
    // String $motivo: motivo pelo qual nao se pode acessar o registro
    //
        if ($usuario->possui_grupo(COD_ADMIN)) {
            return true;
        }
        switch ($this->id_form) {
        case $this->id_formulario_alterar('pessoal'):
            return true;
        case $this->id_formulario_alterar('senha'):
            return true;
        }
        return false;
    }


    //
    //     Realiza a validacao final (de dados dependentes)
    //
    public function validacao_final(&$dados) {
    // Object $dados: objeto com os valores originais submetidos
    //
        global $USUARIO;

        $r = true;
        switch ($this->id_form) {

        // Formulario para alterar senha
        case $this->id_formulario_alterar('senha'):

            // Admin nao precisa informar senha atual de outro usuario
            if ($USUARIO->possui_grupo(COD_ADMIN) && $USUARIO->get_valor_chave() != $this->get_valor_chave()) {
                //void

            // Se nao eh admin, ou eh admin alterando sua senha
            } else {
                if ($dados->senha_atual === '') {
                    $r = false;
                    $this->erros[] = 'Faltou preencher a senha atual';
                } elseif (!$this->validar_senha($this->__get('login'), $dados->senha_atual)) {
                    $r = false;
                    $this->erros[] = 'A senha atual est&aacute; incorreta';
                }
            }
            if (!$this->validar_senha_confirmacao($dados->nova_senha, $dados->confirmacao)) {
                $r = false;
            }
            break;

        // Formulario de inserir usuarios
        case $this->id_formulario_inserir('pessoal'):

            if ($dados->geracao_senha == USUARIO_SENHA_ESPECIFICA) {

                // Checar se a confirmacao esta' correta
                if (!$this->validar_senha_confirmacao($dados->senha_sugerida, $dados->confirmacao)) {
                    $r = false;
                }

            } elseif (($dados->senha_sugerida !== '') || ($dados->confirmacao !== '')) {
                $this->avisos[] = 'Aten&ccedil;&atilde;o: a senha/confirma&ccedil;&atilde;o especificada n&atilde;o foi utilizada!';
            }
            //nobreak

        // Formulario de definicao de grupos
        case $this->id_formulario_relacionamento('grupos'):
            if ($this->id_form == $this->id_formulario_relacionamento('grupos')) {
                $vt_grupos = isset($dados->cod_grupo) ? $dados->cod_grupo : array();
            } elseif ($this->id_form == $this->id_formulario_inserir('pessoal')) {
                $vt_grupos = isset($dados->vetor_grupos) ? $dados->vetor_grupos : array();
            }

            /**
            //TODO: inserir a logica de validacao de grupos AQUI (exemplo abaixo)

            // Se esta em X, precisa estar em Y
            if (in_array(COD_GRUPO_X, $vt_grupos)) {

                // Se nao marcou Y
                if ((!in_array(COD_GRUPO_Y, $vt_grupos))) {
                    $this->erros[] = 'Para ser X precisa ser Y';
                    $r = false;
                }
            }
            */
            break;
        }
        return $r;
    }


    //
    //     Valida a senha e a confirmacao
    //
    private function validar_senha_confirmacao($senha, $confirmacao) {
    // String $senha: senha a ser validada
    // String $confirmacao: confirmacao a ser validad
    //
        $r = true;

        // Checar se preencheu senha e confirmacao
        if ($senha === '') {
            $this->erros[] = 'Faltou preencher a nova senha';
            $r = false;
        }
        if ($confirmacao === '') {
            $this->erros[] = 'Faltou preencher a confirma&ccedil;&atilde;o de nova senha';
            $r = false;
        }
        if (!$r) {
            return false;
        }

        // Checar tamanho da senha
        $tamanho = texto::strlen($senha);
        $def_senha = $this->get_definicao_atributo('senha');
        if ($tamanho < $def_senha->minimo) {
            $this->erros[] = 'A senha precisa ter no m&iacute;nimo '.texto::numero($def_senha->minimo).' caracteres';
            $r = false;
        } elseif ($tamanho > $def_senha->maximo) {
            $this->erros[] = 'A senha precisa ter no m&aacute;ximo '.texto::numero($def_senha->minimo).' caracteres';
            $r = false;
        }
        if (!$r) {
            return false;
        }

        // Checar se a confirmacao esta' correta
        if (strcmp($senha, $confirmacao) != 0) {
            $this->erros[] = 'Senha n&atilde;o confere com a confirma&ccedil;&atilde;o';
            $r = false;
        }

        return $r;
    }


    //
    //     Obtem informacoes sobre um campo do formulario
    //
    public function get_info_campo($campo) {
    // String $campo: campo desejado
    //
        switch ($this->id_form) {
        default:
            switch ($campo) {
            case 'senha_atual':
                $atributo = $this->get_definicao_atributo('senha');
                $atributo->nome = $campo;
                $atributo->descricao = 'Senha Atual';
                $atributo->ajuda = 'Preencha com a sua senha atual';
                return $atributo;
            case 'nova_senha':
                $atributo = $this->get_definicao_atributo('senha');
                $atributo->nome = $campo;
                $atributo->descricao = 'Nova Senha';
                $atributo->ajuda = 'Preencha a nova senha desejada';
                return $atributo;
            case 'confirmacao':
                $atributo = $this->get_definicao_atributo('senha');
                $atributo->nome = $campo;
                $atributo->descricao = 'Confirma&ccedil;&atilde;o';
                $atributo->ajuda = 'Preencha a confirma&ccedil;&atilde;o com o mesmo valor da senha';
                return $atributo;
            }
            break;
        }
        return parent::get_info_campo($campo);
    }


    //
    //     Retorna se o usuario possui o grupo informado
    //
    public function possui_grupo($cod_grupo) {
    // Int $cod_grupo: codigo do grupo
    //
        return array_key_exists($cod_grupo, $this->get_vetor_rel_un('grupos'));
    }


    //
    //     Gera um sal aleatorio
    //
    public static function gerar_sal_senha($tipo) {
    // String $tipo: tipo de sal (STD_DES, EXT_DES, MD5, BLOWFISH, SHA256 ou SHA512)
    //
        $tipo = strtoupper($tipo);
        switch ($tipo) {
        case 'STD_DES':
            $intervalos = array(
                array(ord('A'), ord('Z'))
            );
            return senha::gerar_com_intervalo(2, $intervalos);
        case 'EXT_DES':
            $intervalos = array(
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
                array(ord('0'), ord('9')),
                array(ord('.'), ord('.')),
                array(ord('/'), ord('/'))
            );
            $iteracoes = senha::gerar_com_intervalo(4, $intervalos);
            $sal = senha::gerar_com_intervalo(4, $intervalos);
            return sprintf('_%04s%04s', $iteracoes, $sal);
        case 'MD5':
            $intervalos = array(
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
                array(ord('0'), ord('9'))
            );
            $sal = senha::gerar_com_intervalo(8, $intervalos);
            return sprintf('$1$%08s$', $sal);
        case 'BLOWFISH':
            $custo = mt_rand(7, 9);
            $intervalos = array(
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
                array(ord('0'), ord('9')),
                array(ord('.'), ord('.')),
                array(ord('/'), ord('/'))
            );
            $sal = senha::gerar_com_intervalo(21, $intervalos);
            return sprintf('$2a$%02d$%021s$', $custo, $sal);
        case 'SHA256':
            $rounds = mt_rand(5000, 9000); // 1000 .. 999.999.999
            $intervalos = array(
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
                array(ord('0'), ord('9'))
            );
            $sal = senha::gerar_com_intervalo(12, $intervalos);
            return sprintf('$5$rounds=%d$%012s$', $rounds, $sal);
        case 'SHA512':
            $rounds = mt_rand(5000, 9000); // 1000 .. 999.999.999
            $intervalos = array(
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
                array(ord('0'), ord('9'))
            );
            $sal = senha::gerar_com_intervalo(16, $intervalos);
            return sprintf('$6$rounds=%d$%012s$', $rounds, $sal);
        }
        return false;
    }


    //
    //     Retorna a senha criptografada
    //
    public function codificar($senha, $senha_codificada = false) {
    // String $senha: senha nao codificada
    // String $senha_codificada: senha codificada (para conferencia de senhas com sal)
    //
        //$senha = utf8_decode($senha);

        // Metodo MD5
        return md5($senha);

        // Metodo SHA-1
        //return sha1($senha);

        // Metodo Crypt
        //if ($senha_codificada !== false) {
        //    return crypt($senha, $senha_codificada);
        //} else {
        //    $sal = self::gerar_sal_senha('MD5');
        //    return crypt($senha, $sal);
        //}
    }


    //
    //     Verifica se a senha esta correta
    //
    public function validar_senha($login, $senha, &$erros = array()) {
    // String $login: login a ser verificado
    // String $senha: senha a ser comparada com a do objeto
    // Array[String] $erros: vetor de erros ocorridos
    //
        // Autenticacao tradicional ou e' o admin
        if (USUARIO_TIPO_AUTENTICACAO == 'simp' || $this->get_valor_chave() == 1) {
            if (strcmp($this->codificar($senha, $this->get_atributo('senha')), $this->get_atributo('senha')) != 0) {
                $erros[] = 'Senha inv&aacute;lida';
                return false;
            }
            return true;
        }

        // Autenticacao via driver
        $credenciais = array(
            'login' => $login,
            'senha' => $senha
        );
        $autenticacao = new autenticacao(USUARIO_TIPO_AUTENTICACAO);
        if (!$autenticacao->set_credenciais($credenciais, $erros)) {
            return false;
        }
        if (!$autenticacao->autenticar_usuario($erros)) {
            return false;
        }

        // Checar se ja existe o usuario no BD local
        $u = new self('login', $login);
        if ($u->existe()) {
            return true;
        }
        $u->limpar_objeto();
        $u->login = $login;
        $u->senha = $senha;

        $dados = $autenticacao->get_dados_usuario();

        $dominio = USUARIO_DOMINIO;
        if ($dominio[0] == '.') {
            $dominio = substr($dominio, 1);
        }

        // Criar o usuario caso nao exista
        switch (USUARIO_TIPO_AUTENTICACAO) {
        case 'aut_imap':
            $u->nome  = isset($dados['personal']) ? $dados['personal'] : ucfirst($login);
            $u->email = isset($dados['email'])    ? $dados['email']    : $login.'@'.$dominio;
            break;

        case 'aut_ldap':
            $u->nome  = isset($dados['cn'])   ? $dados['cn']   : ucfirst($login);
            $u->email = isset($dados['mail']) ? $dados['mail'] : $login.'@'.$dominio;
            break;

        case 'aut_linux':
            $u->nome  = isset($dados['gecos']) ? $dados['gecos']             : ucfirst($login);
            $u->email = isset($dados['name'])  ? $dados['name'].'@'.$dominio : $login.'@'.$dominio;
            break;

        default:
            $u->nome  = ucfirst($login);
            $u->email = $login.'@'.$dominio;
            break;
        }

        $salvar_campos = array('nome', 'login', 'email', 'senha');
        if (!$u->salvar_completo($salvar_campos)) {
            $erros[] = 'Erro ao cadastrar usu&aacute;rio no sistema';
            $erros[] = $u->get_erros();
            return false;
        }
        return true;
    }


    //
    //     Operacoes pre-salvar
    //
    public function pre_salvar(&$salvar_campos) {
    // Array[String] $salvar_campos: vetor de campos a serem salvos
    //
        $r = true;
        switch ($this->id_form) {

        // Alterar senha
        case $this->id_formulario_alterar('senha'):
            $salvar_campos[] = 'senha';
            $r = $r && $this->__set('senha', $this->get_auxiliar('nova_senha'));
            break;

        // Envia e-mail para o usuario caso esteja sendo cadastrado
        case $this->id_formulario_inserir('pessoal'):
            $salvar_campos[] = 'senha';
            $geracao_senha = $this->get_auxiliar('geracao_senha');

            // Gerar senha aleatoria
            if ($geracao_senha == USUARIO_SENHA_ALEATORIA) {
                $senha = senha::gerar(USUARIO_TAM_SENHA, true);
                $r = $r && $this->__set('senha', $senha);
                $r = $r && $this->enviar_senha($senha);

            // Especifiar senha vinda do formulario
            } elseif ($geracao_senha == USUARIO_SENHA_ESPECIFICA) {
                $senha = $this->get_auxiliar('senha_sugerida');
                $r = $r && $this->__set('senha', $senha);
            }
            break;
        }
        return $r;
    }


    //
    //     Opcoes pos-salvar
    //
    public function pos_salvar() {
        $r = true;

        switch ($this->id_form) {

        // Formulario de inserir usuario
        case $this->id_formulario_inserir('pessoal'):
            foreach (array_unique($this->get_auxiliar('vetor_grupos')) as $cod_grupo) {
                if (!$cod_grupo) { continue; }
                $dados = new stdClass();
                $dados->cod_grupo = (int)$cod_grupo;
                $r = $r && $this->inserir_elemento_rel_un('grupos', $dados);
            }
            if ($r) {
                $this->avisos[] = 'Grupos definidos com sucesso';
            } else {
                $this->erros[] = 'Erro ao definir os grupos';
            }
            break;

        // Formulario de definir grupos
        case $this->id_formulario_relacionamento('grupos'):
            objeto::limpar_cache('usuario', $this->get_valor_chave());
            break;
        }
        return $r;
    }


    //
    //     Envia uma senha por e-mail
    //
    public function enviar_senha($senha, $nova = false) {
    // String $senha: senha gerada aleatoriamente
    // Bool $nova: indica se e' uma nova senha ou o usuario esta' sendo cadastrado
    //
        // Gerar mensagem
        if (!$nova) {
            $assunto = 'Cadastro no Sistema';
            $msg = "Prezado(a) ".$this->get_atributo('nome').",\n".
                   "   Informamos que voce acaba de ser cadastrado no Sistema ".
                   USUARIO_NOME_SISTEMA.' - '.USUARIO_DESCRICAO_SISTEMA."\n\n".
                   "   Link para acesso ao Sistema: ".USUARIO_LINK_ACESSO."\n\n".
                   "   Os dados para acesso estao abaixo:\n".
                   "login: ".$this->get_atributo('login')."\n".
                   "senha: {$senha}\n\n".
                   "Obs: a senha foi gerada aleatoriamente. Por favor nao interprete-a ".
                   "como uma palavra de tom ofensivo.";

            $msg_html = "<p>Prezado(a) <em>".$this->exibir('nome')."</em>,<br />\n".
                        "Informamos que voc&ecirc; acaba de ser cadastrado(a) no Sistema ".
                        USUARIO_NOME_SISTEMA.' - '.USUARIO_DESCRICAO_SISTEMA."</p>\n".
                        "<p>Link para acesso ao Sistema: <a href=\"".USUARIO_LINK_ACESSO."\">".
                        USUARIO_LINK_ACESSO."</a>.</p>\n".
                        "<p>Os dados para acesso est&atilde;o abaixo:</p>\n".
                        "<p><strong>login:</strong> ".$this->exibir('login')."</p>\n".
                        "<p><strong>senha:</strong> {$senha}</p>".
                        "<p><small>Obs: a senha foi gerada aleatoriamente. Por favor n&atilde;o ".
                        "interprete-a como uma palavra de tom ofensivo.</small></p>\n";
        } else {
            $assunto = 'Nova senha';
            $msg = "Prezado(a) ".$this->get_atributo('nome').",\n".
                   "   Informamos a sua nova senha solicitada pelo Sistema ".
                   USUARIO_NOME_SISTEMA.' - '.USUARIO_DESCRICAO_SISTEMA."\n\n".
                   "   Link para acesso ao Sistema: ".USUARIO_LINK_ACESSO."\n\n".
                   "   Os novos dados para acesso estao abaixo:\n".
                   "login: ".$this->get_atributo('login')."\n".
                   "senha: {$senha}\n".
                   "Obs: a senha foi gerada aleatoriamente. Por favor nao interprete-a ".
                   "como uma palavra de tom ofensivo.";

            $msg_html = "<p>Prezado(a) <em>".$this->exibir('nome')."</em>,<br />\n".
                        "Informamos a sua nova senha solicitada pelo Sistema ".
                        USUARIO_NOME_SISTEMA.' - '.USUARIO_DESCRICAO_SISTEMA."</p>\n".
                        "<p>Link para acesso ao Sistema: <a href=\"".USUARIO_LINK_ACESSO."\">".
                        USUARIO_LINK_ACESSO."</a>.</p>\n".
                        "<p>Os novos dados para acesso est&atilde;o abaixo:</p>\n".
                        "<p><strong>login:</strong> ".$this->exibir('login')."</p>\n".
                        "<p><strong>senha:</strong> {$senha}</p>".
                        "<p><small>Obs: a senha foi gerada aleatoriamente. Por favor n&atilde;o ".
                        "interprete-a como uma palavra de tom ofensivo.</small></p>\n";
        }

        // Enviar e-mail
        $email = new email($assunto);
        $email->set_destinatario($this->get_atributo('nome'), $this->get_atributo('email'));

        // Pode-se escolher entre um metodo ou outro (obrigatorio apenas um)
        $email->set_mensagem($msg);
        $email->set_mensagem($msg_html, 1);

        if (!$email->enviar()) {
            $this->erros[] = 'Erro ao enviar e-mail com a senha para o usu&aacute;rio';
            $this->erros[] = $email->get_erros();
            return false;
        }
        return true;
    }


    //
    //     Imprime um campo do formulario
    //
    public function campo_formulario(&$form, $campo, $valor) {
    // formulario $form: objeto do tipo formulario
    // String $campo: campo a ser adicionado
    // Mixed $valor: valor a ser preenchido automaticamente
    //
        if ($this->possui_atributo($campo)) {
            $atributo = $this->get_definicao_atributo($campo);
        }

        switch ($campo) {

        // Campos de Senha
        case 'senha':
            $form->campo_password($atributo->nome, $atributo->nome, $atributo->maximo, 30, $atributo->get_label($this->id_form));
            return true;

        case 'senha_atual':
            $atributo = $this->get_definicao_atributo('senha');
            $form->campo_password($campo, $campo, $atributo->maximo, 30, 'Senha Atual');
            return true;

        case 'nova_senha':
            $atributo = $this->get_definicao_atributo('senha');
            $form->campo_password($campo, $campo, $atributo->maximo, 30, 'Nova Senha');
            return true;

        case 'confirmacao':
            $atributo = $this->get_definicao_atributo('senha');
            $form->campo_password($campo, $campo, $atributo->maximo, 30, 'Confirma&ccedil;&atilde;o');
            return true;

        case 'senha_sugerida':
            $atributo = $this->get_definicao_atributo('senha');
            $form->campo_password($campo, $campo, $atributo->maximo, 30, 'Senha Sugerida');
            return true;

        // Campo radio
        case 'geracao_senha':
            if (!$valor) {
                $valor = USUARIO_SENHA_ALEATORIA;
            }
            $vetor = array(
                           USUARIO_SENHA_ALEATORIA  => 'Gerar senha aleat&oacute;ria',
                           USUARIO_SENHA_ESPECIFICA => 'Definir senha'
                          );
            $form->campo_aviso('Utilize "gerar senha aleat&oacute;ria" para gerar uma senha e '.
                               'envi&aacute;-la por e-mail ou utilize "definir senha" e '.
                               'especifique uma senha abaixo (n&atilde;o ser&aacute; enviada '.
                               'por e-mail)');
            $form->campo_radio($campo, $campo, $vetor, $valor);
            return true;

        // Campo Grupo
        case 'vetor_grupos':
            $grupo = new grupo();
            $vt_grupos = array('0' => 'Nenhum') + $grupo->vetor_associativo();
            self::preparar_vetor_select($vt_grupos);

            $total_grupos = count($vt_grupos) - 1;
            $id_dom = 'area_'.$form->montar_id($campo);

            // Se ja' submeteu o formulario uma vez
            if (is_array($valor) && !empty($valor)) {
                $i = 0;
                foreach (array_unique($valor) as $item) {
                    $form->campo_select($campo.'[]', $campo.$i, $vt_grupos, $item, $grupo->get_entidade());
                    $i++;
                }
                $total_clones = $total_grupos - $i + 1;
                $form->campo_clone($id_dom, $grupo->get_entidade(), $total_clones);

            // Se esta' exibindo o formulario pela primeira vez
            } elseif (FORMULARIO_AJAX) {
                $form->campo_select($campo.'[]', $campo, $vt_grupos, 0, 'Grupo');
                $form->campo_clone($id_dom, $grupo->get_entidade(), $total_grupos);
            }
            return true;

        // Campo bool
        case 'exibir_ajuda_senha':
            $form->campo_aviso('O campo abaixo pode exibir informa&ccedil;&otilde;es importantes sobre a sua senha, '.
                               'como a pontua&ccedil;&atilde;o obtida com vogais e consoantes.');
            $form->campo_bool($campo, $campo, 'Exibir detalhes sobre a qualidade da senha', $valor);
            return true;
        }

        return parent::campo_formulario($form, $campo, $valor);
    }


    //
    //     Salva a nova senha em outra base de autenticacao
    //
    protected function salvar_nova_senha($senha) {
    // String $senha: nova senha
    //
        $config = new config();
        $autenticacao = new autenticacao($config->autenticacao);
        $credenciais['login'] = $this->get_atributo('login');
        $credenciais['senha'] = $senha;
        $autenticacao->set_credenciais($credenciais);
        return $autenticacao->alterar_senha($this->erros);
    }


    //
    //     Checa se um usuario tem acesso ao arquivo do modulo
    //
    public function checar_permissao($modulo, $arquivo) {
    // String $modulo: nome do modulo
    // String $arquivo: nome do arquivo
    //
        return (bool)$this->get_arquivo($modulo, $arquivo);
    }


    //
    //     Obtem os dados do arquivo caso o usuario tenha permissao
    //
    public function get_arquivo($modulo, $arquivo) {
    // String $modulo: nome do modulo
    // String $arquivo: nome do arquivo
    //
        static $vt_cache = null;
        if ($vt_cache === null) {
            $vt_cache = array();

            // Criar um vetor indexado pelo nome do modulo, dois-pontos, e o nome do arquivo
            foreach ($this->get_vetor_rel_un('grupos') as $usuarios_grupos) {
                foreach ($usuarios_grupos->grupo->permissoes as $p) {
                    $vt_cache[$p->arquivo->modulo.':'.$p->arquivo->arquivo] = $p->arquivo;
                }
            }

            // Arquivo principal
            $campos = array('arquivo', 'modulo', 'descricao');
            $dados_arquivo = new arquivo('', 1, $campos);
            $vt_cache[':index.php'] = $dados_arquivo;
        }
        if (DIRECTORY_SEPARATOR != '/') {
            $modulo = str_replace(DIRECTORY_SEPARATOR, '/', $modulo);
        }

        return isset($vt_cache[$modulo.':'.$arquivo]) ? $vt_cache[$modulo.':'.$arquivo] : false;
    }


    //
    //     Obtem um vetor de dominios semelhantes (com base em dominios conhecidos e dominios usados anteriormente por outros usuarios)
    //
    public static function get_dominios_semelhantes($dominio) {
    // String $dominio: dominio a ser testado
    //
        static $dominios_conhecidos = null;
        if ($dominios_conhecidos === null) {
            $dominios_conhecidos = array(
                'hotmail.com',
                'yahoo.com.br',
                'gmail.com',
                'bol.com.br',
                'terra.com.br',
                'ig.com.br',
                'uol.com.br',
                'msn.com',
                'oi.com.br',
                'yahoo.com',
                'ymail.com'
            );
            $emails = objeto::get_objeto(__CLASS__)->vetor_associativo('cod_usuario', 'email');
            foreach ($emails as $email) {
                $pos = strpos($email, '@');
                if ($pos !== false) {
                    $dominio_conhecido = strtolower(substr($email, $pos + 1));
                    if (!in_array($dominio_conhecido, $dominios_conhecidos)) {
                        $dominios_conhecidos[] = $dominio_conhecido;
                    }
                }
            }
        }

        $vetor = array();
        foreach ($dominios_conhecidos as $dominio_conhecido) {
            similar_text($dominio, $dominio_conhecido, $percentagem);
            $vetor[$dominio_conhecido] = $percentagem;
        }
        asort($vetor);
        return array_reverse($vetor);
    }

}//class
