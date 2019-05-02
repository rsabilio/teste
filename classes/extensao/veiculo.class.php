<?php
//
//
// AlugarCar
// Descricao: Classe veiculo
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon Simoes Abilio
// License: LICENSE.TXT
//
final class veiculo extends veiculo_base {
    
    //
    //     Retorna um vetor com os dados da opcao (icone) que aparece na lista de entidades (pode ser sobrecarregado, desde que este metodo pai seja chamado no final)
    //     O metodo deve retornar um objeto stdClass com os possiveis atributos:
    //     Bool principal: indica se a opcao e' o link principal da lista ou deve ficar na lista de opcoes (opcional, padrao false)
    //                     Apenas uma opcao pode ser a principal dentro de um conjunto de opcoes escolhidas
    //     String icone: endereco do icone desejado (opcional, padrao "")
    //     String texto: texto a ser colocado apos o icone (opcional, padrao "")
    //     Bool exibir_texto: indica de deve exibir o texto apos o icone ou apenas defini-lo como descricao do icone (opcional, padrao false)
    //     Bool ajax: indica se o link deve usar ajax (opcional, padrao true)
    //     Bool carregando: indica se o link deve exibir o "carregando" na tela (opcional, padrao true)
    //     Bool foco: indica se o link deve definir o foco ao primeiro campo da proxima pagina (opcional, padrao true)
    //     String arquivo: nome do arquivo para onde a opcao aponta (opcional, padrao "")
    //     String modulo: nome do modulo para onde a opcao aponta (opcional, padrao "")
    //     String descricao: breve descricao da opcao (opcional, padrao "")
    //     String id: identificador unico da opcao (opcional, padrao "")
    //     String class: classe CSS da opcao (opcional, padrao "")
    //
    public function dados_opcao($opcao, $modulo) {
    // String $opcao: identificador da opcao
    // String $modulo: nome do modulo
    //
        $dados = new stdClass();
        
        switch ($opcao) {
            case 'opcionais':
                $dados->icone     = icone::endereco('editar');
                $dados->arquivo   = 'opcionais.php';
                $dados->modulo    = $modulo;
                $dados->descricao = 'Selecionar Opcionais';
                return $dados;
        }
        return parent::dados_opcao($opcao, $modulo);
    }
}//class
