<?php
//
// SIMP
// Descricao: Classe Abstrata Objeto Formulario Confirmacao
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.0.5
// Data: 26/03/2009
// Modificado: 08/04/2011
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
abstract class objeto_formulario_confirmacao extends objeto_formulario {


/// @ METODOS DE LOGICA


    //
    //     Faz a Logica de um formulario de alteracao de dados mediante confirmacao
    //
    protected function logica_formulario_confirmar(&$dados, $campos, $opcoes = false, $captcha = false, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos pelo formulario
    // Array[String] $campos: campos reais vindos do formulario
    // Array[String => Mixed] $opcoes: opcoes a serem salvas
    // Bool $captcha: indica se um campo captcha foi solicitado no formulario
    // Int $modo_transacao: tipo de transacao
    //
        // Se os dados nao foram submetidos
        if (!isset($dados->id_form) ||
            $dados->id_form != $this->id_form) {
            return null;
        }

        // Se o formulario possui um campo captcha
        if ($captcha && !captcha::validar($dados->captcha)) {
            $this->erros[] = 'O texto da imagem est&aacute; incorreto';
        }

        $classe = $this->get_classe();
        $chave_confirmacao = $this->chave_confirmacao();

        // Checar se o usuario confirmou
        if (!$dados->confirmacao) {
            $this->avisos[] = 'Nada foi feito (marque a confirma&ccedil;&atilde;o)';
            $this->imprimir_avisos();
            return null;
        }

        // Fazer as validacoes sobre a chave de confirmacao
        if (!isset($dados->chave_confirmacao)) {
            $this->erros[] = 'N&atilde;o foi informada a chave de confirma&ccedil;&atilde;o (Erro inesperado)';
        } elseif (strcmp($dados->chave_confirmacao, $chave_confirmacao) != 0) {
            $this->erros[] = 'Chave para confirma&ccedil;&atilde;o n&atilde;o confere (Erro inesperado)';
        }

        // Se houve erros
        if ($this->possui_erros()) {
            $this->imprimir_erros();
            return false;
        }

        // Montar vetor de opcoes adicionais e retorna o vetor de campos a serem salvos
        $salvar_campos = $this->montar_opcoes($dados, $campos, $opcoes);
        if (is_array($opcoes) && !empty($opcoes)) {
            $opcoes_hierarquico = objeto::converter_notacao_vetor(array_keys($opcoes));
            $this->names = vetor::array_unique_recursivo(array_merge_recursive($this->names, $opcoes_hierarquico));
        }

        // Armazenar valores antigos
        if ($this->existe()) {
            $this->antigo = $this->get_dados(self::converter_notacao_vetor_hierarquico($this->names));
        } else {
            $this->antigo = new stdClass();
        }

        // Se nao conseguir confirmar
        if (!$this->set_valores($dados->$classe, $this->names, true) ||
            !$this->salvar_completo($salvar_campos, 'salvar', $modo_transacao)) {
            $this->imprimir_erros();
            return false;
        }

        // Se conseguiu salvar os valores
        $this->imprimir_avisos();
        return true;
    }


/// @ METODOS AUXILIARES


    //
    //     Retorna uma chave unica de confirmacao de um elemento da entidade
    //
    final protected function chave_confirmacao() {
         $c = $this->get_classe().'.'.                       // Nome da classe da entidade
              $this->get_chave().'.'.                        // Nome da chave primaria da entidade
              $_SERVER['REMOTE_ADDR'].'.'.                   // IP de quem esta' solicitando
              count($this->get_atributos()).'.'.             // Numero de atributos da entidade
              $this->get_valor_chave().'.'.                  // Valor da chave da entidade
              strftime('%d', time());                        // Dia em que ocorreu a solicitacao

         $c = md5($c);

         return $c;
    }


    //
    //     ID do formulario de confirmacao
    //
    final public function id_formulario_confirmar($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_confirmar_'.$this->get_classe();
    }



/// @ METODOS DE APRESENTACAO DOS FORMULARIOS


    //
    //     Logica de geracao de um formulario de confirmacao
    //     Possiveis chaves/valores dos parametros extras:
    //     String $titulo: titulo do formulario de confirmacao
    //     String $aviso_acesso: aviso caso o usuario nao possa acessar o formulario
    //     Array[String => Mixed] $opcoes: valores a serm definidos automaticamente apos confirmacao
    //     String $prefixo_id: prefixo do ID do formulario
    //     String $class: nome da classe CSS utilizada
    //     Bool $ajax: usar ajax ou nao
    //     String $nome_botao: nome do botao de confirmar os dados
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_confirmar(&$dados, $mensagem, &$campos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // String $mensagem: mensagem de confirmacao
    // Array[String || String => Array[String]] $campos: campos do formulario (true = todos)
    // String $action: endereco para envio dos dados
    //
        global $USUARIO;

        $padrao = array(
            'titulo'         => 'Formul&aacute;rio de Confirma&ccedil;&atilde;o',
            'aviso_acesso'   => 'Este registro n&atilde;o pode ser alterado',
            'nome_botao'     => 'Confirmar',
            'prefixo'        => '',
            'opcoes'         => false,
            'class'          => 'formulario',
            'ajax'           => true,
            'modo_transacao' => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        if (!$this->existe()) {
            trigger_error('Para confirmar precisa ter consultado o objeto', E_USER_ERROR);
            return false;
        }

        $this->set_id_form($this->id_formulario_confirmar(), $extra['prefixo']);

        // Checar se pode acessar o formulario
        if (!$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            if (!empty($campos)) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Formul&aacute;rio indispon&iacute;vel</p>';
                echo '</div>';
            }
            return null;
        }

        // Consultar os campos necessarios
        if ($campos === true) {
            $campos = filtro_atributo::get_atributos_classe($this->get_classe(), '[S] , -[PK]');
        }
        $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_IGNORAR_IMPLICITOS;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
        $captcha = in_array('captcha', $campos);
        $this->consultar_campos($vt_campos);

        // Cria o formulario
        $form = $this->montar_formulario_confirmar($action, $this->id_form, $extra['class'], $campos, $dados, $extra['opcoes'], $extra['nome_botao'], $extra['ajax'], $mensagem);

        // Executar a logica de confirmacao
        $r = $this->logica_formulario_confirmar($dados, $vt_campos, $extra['opcoes'], $captcha, $extra['modo_transacao']);

        // Se nao submeteu ou ocorreu um erro
        if (!$r) {

            // Imprimir o formulario
            $form->imprimir();

        // Se confirmou os dados
        } else {
            if (!empty($campos)) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Confirma&ccedil;&atilde;o efetuada</p>';
                echo '</div>';
            }
        }

        return $r;
    }


    //
    //     Retorna um formulario de confirmacao com os campos especificados e permitidos
    //
    final public function montar_formulario_confirmar($action, $id_form, $class, $campos, &$valores, $opcoes = false, $botao = false, $ajax = true, $mensagem = false) {
    // String $action: endereco para onde os dados sao enviados
    // String $id_form: nome do formulario
    // String $class: nome da classe CSS utilizada
    // Array[String || String => Array[String]] $campos: vetor com os campos do formulario de forma hierarquica (vetor de vetor)
    // Object $valores: valores a serem preenchidos automaticamente
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas nos dados
    // String $botao: nome do botao do formulario
    // Bool $ajax: usar ajax ou nao
    // String $mensagem: mensagem de confirmacao
    //
        $classe = $this->get_classe();

        // Se nao submeteu os dados do formulario
        if (!isset($valores->id_form)) {
            $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_IGNORAR_IMPLICITOS;
            $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
            $valores = new stdClass();
            $valores->$classe = $this->get_dados($vt_campos);
            $valores->default = true;

        // Converte os dados submetidos em campos separados nos respectivos atributos
        } else {
            $vt_campos = array();
            foreach ($campos as $campo) {
                if (is_array($campo)) {
                    $vt_campos = array_merge($vt_campos, $campo);
                } else {
                    $vt_campos[] = $campo;
                }
            }
            $salvar_campos = $this->montar_opcoes($valores, $vt_campos, $opcoes);
            $vt_campos = objeto::converter_notacao_vetor($vt_campos);
            $valores->$classe = $this->converter_componentes($valores->$classe, $vt_campos);
        }

        $form = new formulario($action, $this->id_form, $class, 'post', $ajax);

        // Inserir os campos
        foreach ($campos as $chave => $campo) {
            if (is_array($campo)) {
                $form->inicio_bloco($chave);
                foreach ($campo as $c) {
                    if (!$this->inserir_campo_formulario($form, $c, $valores, $this->id_form)) {
                        return false;
                    }
                }
                $form->fim_bloco();
            } elseif (!$this->inserir_campo_formulario($form, $campo, $valores, $this->id_form)) {
                return false;
            }
        }
        $form->set_nome(array());

        // Gera uma chave para garantir a confirmacao
        $chave_confirmacao = $this->chave_confirmacao();
        $form->campo_hidden('chave_confirmacao', $chave_confirmacao);
        if (!$mensagem) {
            $mensagem = 'Marque para confirmar';
        }
        $form->campo_bool('confirmacao', 'confirmacao', $mensagem, 0);

        // Gera o botao de enviar
        $form->campo_hidden('id_form', $this->id_form);
        $form->campo_submit($this->id_salvar(), $this->id_salvar(), $botao, 1);

        return $form;
    }

}//class
