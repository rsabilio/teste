#!/usr/bin/env bash
# SIMP
# Descricao: script para compactar os arquivos JavaScript
# Autor: Rubens Takiguti Ribeiro
# Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
# E-mail: rubens@tecnolivre.com.br
# Versao: 1.0.0.7
# Data: 16/01/2008
# Modificado: 11/11/2010
# Utilizacao: ./compactar.sh [arquivo]
# Observacaoes: requer o programa JavaScript-Squish-0.05 no path
# Copyright (C) 2008  Rubens Takiguti Ribeiro
# License: LICENSE.TXT
#

EXIT_STATUS=0

echo "Compactando..."

# buscar o js_compactor
programa=`type -P js_compactor &> /dev/null`
if [ ! -f "$programa" ]
then

    # buscar manualmente em lugares comuns    
    if [ -f "/usr/local/bin/js_compactor" ]
    then
        programa="/usr/local/bin/js_compactor"

    elif [ -f "/usr/bin/js_compactor" ]
    then
        programa="/usr/bin/js_compactor"

    elif [ -f "/bin/js_compactor" ]
    then
        programa="/bin/js_compactor"

    else
        echo "O programa JavaScript-Squish-0.07 (js_compactor) nao foi encontrado" 1>&2
        echo "Consulte: http://search.cpan.org/~unrtst/JavaScript-Squish-0.07/" 1>&2
        exit 1
    fi
fi

cd `dirname "$0"` &> /dev/null

cmd_rm=$(type -P rm) || (echo "Erro ao obter comando rm" 1>&2 && exit 1)
cmd_php=$(type -P php) || (echo "Erro ao obter comando php" 1>&2 && exit 1)

# Compactar todos arquivos
if (( $# == 0 ))
then
    for arq in ./original/*.js
    do
        # Compactar
        dest=`basename "$arq"`
        "$cmd_rm" -f "$dest"
        "$programa" --src="$arq" --dest="$dest" --opt --force
        let EXIT_STATUS=$EXIT_STATUS+$?

        # Remover espacos antes e depois
        "$cmd_php" -r "file_put_contents('${dest}', trim(file_get_contents('${dest}')));" &> /dev/null
    done

# Compactar um arquivo especifico
else
    if [ -f "./original/${1}" ]
    then
        # Compactar
        $programa --src="./original/${1}" --dest="./${1}" --opt --force
        let EXIT_STATUS=$EXIT_STATUS+$?

        # Remover espacos antes e depois
        "$cmd_php" -r "file_put_contents('${dest}', trim(file_get_contents('${dest}')));" &> /dev/null

    else
        echo "Erro: arquivo inexistente (./original/${1})" 1>&2
        let EXIT_STATUS=1
    fi
fi

cd - &> /dev/null

if (( $EXIT_STATUS == 0 ))
then
    echo "OK"
else
    echo "Erro" 1>&2
fi

exit $EXIT_STATUS
