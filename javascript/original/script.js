//
// SIMP
// Descricao: JavaScript utilizado pelas paginas (AJAX)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.4.9
// Data: 12/06/2007
// Modificado: 14/06/2011
// TODO: Funcionar no IE(ca)
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//

/// Operacoes Iniciais
window.onload = function() {
    iniciar(true, false, true);
};
window.onkeydown = checar_tecla;
window.onpopstate = mudar_pagina;


/// Variaveis globais
{
    GLB.url_inicial    = window.location.href; // URL inicial
    GLB.url_atual      = "";                   // URL da pagina atual
    GLB.timestamp      = new Date().getTime(); // Timestamp atual
    GLB.limite_tamanho = 500;                  // Tamanho limite (bytes) dos elementos que sofrem animacoes
    GLB.ultima_boia    = false;                // Ultima boia
    GLB.timers         = new Array();          // Timers cadastrados
    GLB.intervals      = new Array();          // Intervals cadastrados
    GLB.bloqueio_ajax  = false;                // Bloqueio de requisicoes ajax

    // Lista de instancias de classes
    class_tremer.instancias = new Array();
    class_piscar.instancias = new Array();
    class_fechar.instancias = new Array();
}


/// Metodos Auxiliares


//
//     Insere um elemento apos outro
//
function inserir_apos(pai, elemento, referencia) {
// Object pai: elemento pai
// Object elemento: elemento a ser inserido
// Object referencia: elemento referencia
//
    if (referencia.nextSibling) {
        pai.insertBefore(elemento, referencia.nextSibling);
    } else {
        pai.appendChild(elemento);
    }
}


//
//     Insere um elemento no inicio de um container
//
function inserir_inicio(pai, elemento) {
// Object pai: elemento pai
// Object elemento: elemento a ser colocado no inicio do elemento pai
//
    if (pai.hasChildNodes()) {
        pai.insertBefore(elemento, pai.firstChild);
    } else {
        pai.appendChild(elemento);
    }
}


/// Funcoes principais


//
//     Instrucoes iniciais
//
function iniciar(foco, executar_scripts, rolar) {
// Bool foco: dar foco ao primeiro campo do primeiro formulario da pagina
// Bool executar_scripts: executar os scripts extras
// Bool rolar: rolar a pagina ate o ponto de fragmento
//
    try {
        if (GLB.url_inicial) {
            history.replaceState({url:GLB.url_inicial}, '', GLB.url_inicial);
            GLB.url_inicial = null;
        }
    } catch (e) {
        //void
    }

    // Executar scripts extras
    if (executar_scripts) {
        var scripts = document.getElementsByTagName("script");
        var l = scripts.length;
        for (var i = 0; i < l; i++) {
            var src = scripts.item(i).getAttribute("src");
            if (src != CFG.script_local) {
                var conteudo = get_conteudo(src);
                if (conteudo.length > 0) {
                    eval(conteudo);
                }
            }
        }
    }

    // Percorre os elementos do documento alterando-os quando desejado
    definir_atributos();

    // Timer para atualizar data/hora
    var hl =  document.getElementById("hora_local");
    if (hl) {
        ativar_interval("atualizar_tempo();", 30000);
    }

    // Se deseja colocar o foco no primeiro elemento do primeiro formulario
    if (foco) {
        ativar_timer("set_foco();", 700);
    }

    // Se deseja rolar a pagina ate o framento
    if (rolar) {
        var fragment = get_fragment(window.location.href);
        if (fragment) {
            rolar_fragment(fragment);
        }
    }

    return true;
}


//
//     Checa a tecla clicada
//
function checar_tecla(e) {
// Event e: evento disparado pelo teclado
//
    var k = e ? (e.keyCode ? e.keyCode : e.which) : window.event.keyCode;
    switch (k) {
    case 116: // F5: Atualizar
        var url = (GLB.url_atual != "") ? GLB.url_atual : window.location.href;

        // Remover param xml
        url = remover_params(url, ['xml']);

        window.location.replace(url);
        return false;
    }
    return true;
}


//
//     Atualiza a pagina
//
function mudar_pagina(e) {
// Object e: evento
//
    if (e.state) {
        window.location.reload();
    }
}


//
//     Classe para requisicoes HTTP remotas
//
function class_ajax() {
    var that = this;

    this.flag_erro  = true;  // Bool Flag que indica se exibe mensagem de erro ou nao
    this.xmlhttp    = null;  // Object XMLHttpRequest
    this.url        = null;  // String URL de destino
    this.usuario    = null;  // String nome do usuario p/ autenticacao HTTP
    this.senha      = null;  // String senha do usuario p/ autenticacao HTTP
    this.callback   = null;  // Callback funcao a ser chamada apos carregamento
    this.carregando = null;  // Object mensagem de carregando


    //
    //     Define o usuario e a senha
    //
    this.set_credencial = function(usuario, senha) {
    // String usuario: nome do usuario para acesso autenticado
    // String senha: senha do usuario para acesso autenticado
    //
        that.usuario = usuario;
        that.senha   = senha;
    };


    //
    //     Define o metodo usado apos a requisicao ser chamada
    //
    this.set_callback = function(callback) {
    // Callback callback: funcao a ser chamada apos o carregamento assincrono dos dados
    //
        that.callback = callback;
    };


    //
    //     Realiza uma requisicao HTTP remota
    //
    this.consultar = function(metodo, url, assincrona, dados, flag_erro) {
    // String metodo: metodo utilizado na requisicao (POST ou GET)
    // String url: endereco de destino dos dados
    // Bool assincrona: requisicao assincrona (true) ou sincrona (false)
    // String dados: dados formatados de forma x-www-form-urlencoded
    // Bool flag_erro: flag que indica se deve exibir os erros ou nao
    //
        that.url = url;
        if (!that.xmlhttp) {
            return false;
        }
        if (!dados) { dados = null; }
        if (flag_erro != undefined) { that.flag_erro = flag_erro; }

        try {
            if (that.usuario != null) {
                that.xmlhttp.open(metodo.toUpperCase(), that.url, assincrona, that.usuario, that.senha);
            } else {
                that.xmlhttp.open(metodo.toUpperCase(), that.url, assincrona);
            }
            that.xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            that.xmlhttp.onreadystatechange = that.processar;
            that.xmlhttp.send(dados);
        } catch (e) {
            window.alert("Erro ao consultar site: \"" + e.message + "\"");
            return false;
        }
        return true;
    };


    //
    //     Cria um DIV "Carregando..."
    //
    this.criar_carregando = function() {
        if (that.div_carregando) {
            return that.div_carregando.cloneNode(1);
        }

        // Criar DIV
        var div = criar_elemento("div", {"class":"carregando"}, {"visibility":"hidden"});
        {
            // Criar IMG
            var img = criar_elemento("img", {"src":CFG.wwwroot + "imgs/icones/carregando.gif"});

            // Criar Texto
            var texto = document.createTextNode("Carregando...");
        }
        if (img != undefined) {
            div.appendChild(img);
        }
        div.appendChild(texto);

        div.onclick = that.retirar_carregando;

        // Armazenar um backup na memoria
        that.div_carregando = div.cloneNode(1);

        return div;
    };


    //
    //     Coloca um "Carregando..." em algum elemento durante o carregamento
    //
    this.exibir_carregando = function(elemento) {
    // Object elemento: elemento que vai conter o elemento
    //
        var div = that.criar_carregando();
        if (elemento) {
            if (typeof elemento != "number") {
                tirar_visibilidade(elemento);
                div.style.position = "absolute";
                try {
                    div.style.left = "inherit";
                    div.style.top = "inherit";
                } catch (e) {
                    div.style.left = "";
                    div.style.top = "";
                }
                inserir_inicio(elemento, div);

            } else {
                document.getElementsByTagName("body").item(0).appendChild(div);
            }
        }
        div.style.visibility = 'visible';
        that.carregando = div;
    };


    //
    //     Retira o "Carregando..." da pagina
    //
    this.retirar_carregando = function() {
        if (that.carregando == null || that.carregando.parentNode == null) { return; }
        that.carregando.parentNode.removeChild(that.carregando);
        delete that.carregando;
        that.carregando = null;
    };


    //
    //     Processa um resultado
    //
    this.processar = function() {
        if (that.xmlhttp.readyState == 4) {
            switch (that.xmlhttp.status) {
            case 0: // Erro: provavelmente usou outro dominio para acessar
                if (that.flag_erro) {
                    window.alert('Erro ao realizar requisição ao servidor: ' + JSON.stringify(that.xmlhttp));
                }
                break;

            case 200: // OK
                if (that.callback) {
                    switch (typeof(that.callback)) {
                    case "object":
                        if (that.callback instanceof Array) {
                            var obj = that.callback[0];
                            var metodo = that.callback[1];
                            var codigo = "obj." + metodo + "(that);";
                        } else {
                            var funcao = that.callback;
                            var codigo = funcao + "(that);";
                        }
                        eval(codigo);
                        break;
                    default:
                        var funcao = that.callback;
                        var codigo = funcao + "(that);";
                        eval(codigo);
                        break;
                    }
                } else {
                    // funcao nao definida
                }
                break;
            case 503: // Sobrecarga
                if (that.flag_erro) {
                    window.alert("O sistema está operando acima do esperado.\nRecomenda-se sair e voltar mais tarde.");
                }
                break;
            default: // Outro Erro
                if (that.flag_erro) {
                    window.alert("Erro " + that.xmlhttp.status + ": " + that.xmlhttp.statusText);
                }
                break;
            }
            if (that.carregando) {
                that.retirar_carregando();
            }
        }
        return true;
    };


    //
    //     Cria uma instancia de XMLHttpRequest
    //
    this.criar_xmlhttp = function() {
        if (window.XMLHttpRequest) {
            return new XMLHttpRequest();
        } else if (window.ActiveXObject) {
            var versoes = ["MSXML2.XMLHttp",
                           "MSXML.XMLHttp",
                           "Microsoft.XMLHttp"];
            var l = versoes.length;
            for (var i = 0; i < l; i++) {
                try {
                    return new ActiveXObject(versoes[i]);
                } catch (e) {
                    // Tentar outro
                }
            }
        }
        return false;
    };


    //
    //     Obtem o retorno do processamento
    //
    this.get_retorno = function(tipo, opcao) {
    // String tipo: tipo de retorno desejado (xml, text, headers ou header)
    // String opcao: opcao desejada em caso de escolha do tipo header
    //
        switch (tipo.toLowerCase()) {
        case "xml":
            return that.xmlhttp.responseXML;
        case "text":
            return that.xmlhttp.responseText;
        case "headers":
            return that.xmlhttp.getAllResponseHeaders();
        case "header":
            return that.xmlhttp.getResponseHeader(opcao);
        }
        return false;
    };


    // Criar o XMLHttpRequest
    this.xmlhttp = this.criar_xmlhttp();
}


//
//     Funcao para enviar os dados de um formulario
//
function submeter(form, carregando) {
// Form form: formulario a ser submetido
// Bool carregando: flag que indica se sera colocado um carregando ou nao na pagina
//
    if (CFG.engine == 'mshtml') { return true; } // TODO: funcionar no IE
    var centro = document.getElementById("centro");
    var ajax = new class_ajax();

    if (!centro || !ajax.xmlhttp) { return true; }

    var url = form.getAttribute("action");
    url = adicionar_param(url, "xml", "1");

    var dados = get_dados(form);
    var metodo = form.getAttribute("method");

    if (carregando != undefined) {
        ajax.exibir_carregando(carregando);
    }

    if (metodo.toLowerCase() == "get") {
        url = url + (url.indexOf("?") >= 0 ? "&" : "?") + dados;
    }

    ajax.set_callback("atualizar");
    ajax.consultar(metodo, url, true, dados);
    return false;
}


//
//     Funcao para carregar um link da pagina
//
function carregar(link, carregando, setar_foco) {
// A link: link que sera transformado em uma requisicao Ajax
// Bool carregando: flag indicando se deve aparecer um carregando na pagina ou nao
// Bool setar_foco: flag indicando se deve ser definido o foco ao abrir a pagina ou nao
//
    if (CFG.engine == 'mshtml') { return true; } // TODO: funcionar no IE
    setar_foco = (setar_foco == undefined) ? false : setar_foco;
    var url = link.getAttribute("href");
    url = adicionar_param(url, "xml", "1");

    var centro = document.getElementById("centro");

    // Criar objeto Ajax
    var ajax = new class_ajax();
    if (!centro || !ajax.xmlhttp) {
        window.location.replace(url);
        return true;
    }
    if (carregando != 0) {
        ajax.exibir_carregando(carregando);
    }

    // Montar endereco absoluto (necessario para requisicoes Ajax)
    if (url.indexOf('http') != 0) {
        url = CFG.wwwroot + url;
    }

    // Realizar a requisicao remota
    ajax.foco = setar_foco;
    ajax.set_callback("atualizar");
    ajax.consultar("GET", url, true, null);
    return false;
}


//
//     Recupera os dados do formulario retornando na forma x-www-form-urlencoded
//
function get_dados(form) {
// Form form: formulario que deseja-se obter os dados de entrada pelo usuario
//
    var param = new Array();
    var l = form.elements.length;
    for (var i = 0; i < l; i++) {
        var elemento = form.elements[i];
        if (elemento.name == undefined ||
            elemento.name == '' ||
            elemento.disabled) {
            continue;
        }
        switch (get_tag(elemento)) {
        case "input":
            switch (elemento.getAttribute("type")) {
            case "checkbox":
            case "radio":
                if (elemento.checked) {
                    var parametro = elemento.name + "=" + encodeURIComponent(elemento.value);
                    param.push(parametro);
                }
                break;
            case "submit":
                if (elemento.clicou) {
                    var parametro = elemento.name + "=" + encodeURIComponent(elemento.value);
                    param.push(parametro);
                }
                break;
            default:
                var parametro = elemento.name + "=" + encodeURIComponent(elemento.value);
                param.push(parametro);
                break;
            }
            break;
        case "select":
            if (elemento.selectedIndex >= 0) {
                if (elemento.multiple) {
                    var l2 = elemento.options.length;
                    for (var j = 0; j < l2; j++) {
                        if (elemento.options[j].selected) {
                            var parametro = elemento.name + "=" + encodeURIComponent(elemento.options[j].value);
                            param.push(parametro);
                        }
                    }
                } else {
                    var parametro = elemento.name + "=" + encodeURIComponent(elemento.options[elemento.selectedIndex].value);
                    param.push(parametro);
                }
            }
            break;
        case "textarea":
            var parametro = elemento.name + "=" + encodeURIComponent(elemento.value);
            param.push(parametro);
            break;
        }
    }
    return param.join("&");
}


//
//     Clona um campo
//
function clonar(obj, container, info_clone) {
// Object obj: objeto a ser clonado
// Object container: local onde ficam os clones
// Object info_clone: dados de clonagem (limite, nome e id)
//

    //
    //     Atualiza os atributos id e for com um sufixo
    //
    function atualizar_clone(obj, sufixo) {
    // Object obj: objeto a ter seus atributos atualizados
    // String sufixo: sufixo a ser adicionado nos campos ID e FOR
    //
        if (obj.nodeType != 1) { return; }
        obj.def = 0;
        try {
            if (obj.hasAttribute("id")) { obj.id = obj.id + "_" + sufixo; }
            if (obj.hasAttribute("for")) { obj.setAttribute("for", obj.getAttribute("for") + "_" + sufixo); }
        } catch (e) {
            if (obj.getAttribute("id")) { obj.setAttribute("id", obj.getAttribute("id") + "_" + sufixo); }
            if (obj.htmlFor) { obj.htmlFor = obj.htmlFor + "_" + sufixo; }
        }
        if (obj.hasChildNodes()) {
            var c = obj.firstChild;
            while (c != null) {
                atualizar_clone(c, sufixo);
                c = c.nextSibling;
            }
        }
    };


    //
    //     Atualizar labels
    //
    container.atualizar_labels = function() {
        var l = this.childNodes.length;
        for (var i = 0; i < l; i++) {
            var num = i + 2;

            var div_clone = this.childNodes[i];
            var labels = div_clone.getElementsByTagName("label");
            var l2 = labels.length;
            for (var j = 0; j < l2; j++) {
                var label_clone = labels[j];
                var label_original = document.getElementById(div_clone.id_ref).getElementsByTagName("label").item(j);
                if (label_original && label_original.firstChild.nodeType == 3) {
                    var novo_texto = document.createTextNode(label_original.firstChild.nodeValue + " " + num);
                    label_clone.replaceChild(novo_texto, label_clone.firstChild);
                }
            }
        }
    };

    // Atributos
    that = this;
    if (!this.id) { this.id = 1; }
    this.id++;

    // Determinar a quantidade de clones
    var quantidade = container.childNodes.length;

    // Checar se estourou o limite (original + clones >= limite)
    if (info_clone.limite > 0 && quantidade + 1 >= info_clone.limite) {
        window.alert("São permitidos no máximo " + info_clone.limite + " elemento(s)");
        return false;
    }

    // Gerar clone
    var obj_clone = obj.cloneNode(true);
    atualizar_clone(obj_clone, quantidade + 1);

    // Colocar em um div
    var div = criar_elemento("div");
    div.id_ref = obj.id;
    div.appendChild(obj_clone);

    // Criar botao de remover
    var bt_remover = criar_elemento("input", {"type":"button", "class":"botao", "value":"Remover"});
    bt_remover.onclick = function() {
        var div = this.parentNode;
        var container = div.parentNode;
        container.removeChild(div);
        container.atualizar_labels();
        return true;
    };
    div.appendChild(bt_remover);

    // Inserir clone
    container.appendChild(div);

    // Atualizar labels
    container.atualizar_labels();

    iniciar(false, false, false);
    return true;
}


//
//     Funcao que atualiza o conteudo da pagina
//     TODO: funcionar no IE(ca)
//
function atualizar(ajax) {
// class_ajax ajax: objeto que devolve a requisicao
//
    // Verificar bloqueio de ajax
    var t1 = new Date().getTime();
    while (GLB.bloqueio_ajax) {
        var t2 = new Date().getTime();

        // Se passou de 10 segundos
        if (t2 > t1 + 10000) {
            var confirmacao = window.confirm("Uma página solicitada está demorando para ser carregada. Enquanto isso, você solicitou abrir outra página. Você deseja carregar a nova página assim mesmo?");
            if (!confirmacao) {
                return false;
            } else {
                GLB.bloqueio_ajax = false;
            }
        }
    }

    // Bloquear atualizacao via ajax
    GLB.bloqueio_ajax = true;

    var velha_url = remover_params(GLB.url_atual, true);
    var nova_url = remover_params(ajax.url, true);
    var mudou = velha_url != nova_url;

    // Limpar os timers
    limpar_intervals();
    limpar_timers();

    GLB.url_atual = remover_params(ajax.url, ['xml']);

    // Recuperar dados
    var head   = document.getElementsByTagName("head").item(0);
    var nav    = document.getElementById("navegacao");
    var centro = document.getElementById("centro");

    var xml = ajax.get_retorno("xml");

    // Se a sessao expirou
    if (!xml || !xml.documentElement || xml.getElementById('pagina_login')) {
        GLB.bloqueio_ajax = false;
        window.location.replace(GLB.url_atual);
        return false;
    }

    // Atualizar Processing Instructions
    var i = 0;
    while (i < document.childNodes.length) {
        var c = document.childNodes.item(i);
        if ((c.nodeType == 7) || (c.nodeType == 8)) {
            document.removeChild(c);
        } else {
            i++;
        }
    }

    for (var i = xml.childNodes.length - 1; i >= 0; i--) {
        var c = xml.childNodes.item(i);
        try {
            if (c.nodeType == 7) {
                if (c.sheet != null) {
                    var l = c.sheet.media.length;
                    for (var j = 0; j < l; j++) {
                        if (c.sheet.media.item(j) == "screen") {
                            var novo = document.importNode(c, true);
                            document.insertBefore(novo, document.firstChild);
                        }
                    }
                } else {
                    var novo = document.importNode(c, true);
                    document.insertBefore(novo, document.firstChild);
                }
            } else if (c.nodeType == 8) {
                var novo = document.importNode(c, true);
                document.appendChild(novo);
            }
        } catch (e) {
            // Ignorar
        }
    }

    // Atualizar HEAD
    var vhead2 = xml.documentElement.getElementsByTagName("head");
    if (vhead2) {
        var head2 = vhead2.item(0);
        var pai = head.parentNode;
        try {
            var novo = document.importNode(head2, true);

            // Remover script local da lista de scripts
            var scripts = novo.getElementsByTagName("script");
            var l = scripts.length;
            for (var i = 0; i < l; i++) {
                if (scripts[i].src == CFG.script_local) {
                    novo.removeChild(scripts[i]);
                }
            }

            pai.replaceChild(novo, head);
        } catch (e) {

            // Apagar tags
            var itens_head = head.childNodes;
            while (itens_head.length > 0) {
                head.removeChild(itens_head.item(0));
            }

            // Inserir novas tags
            var itens_head2 = head2.childNodes;
            var l = itens_head2.length;
            for (var i = 0; i < l; i++) {
                if (itens_head2.item(i).nodeType != 1) { continue; }
                var item = clonar_tag(itens_head2.item(i));
                try {
                    head.appendChild(item);
                } catch (e) {
                    window.alert("Erro: " + e.message + "\n\nRecomenda-se desabilitar o JavaScript.");
                }
            }
        }
    }

    // Atualizar barra de navegacao
    var nav2 = xml.getElementById("navegacao");
    if (nav2) {
        var pai = nav.parentNode;
        try {
            var novo = document.importNode(nav2, true);
            pai.replaceChild(novo, nav);
        } catch (e) {
            try {
                nav.innerHTML = nav2.innerHTML;
            } catch (e) {
                window.alert("Erro ao atualizar barra de navegacao:\n" + e.message + "\n\nRecomenda-se desabilitar o Javascript");
            }
        }
    }

    // Atualizar centro
    var centro2 = xml.getElementById("centro");
    if (centro2) {
        var pai = centro.parentNode;
        try {
            var novo = document.importNode(centro2, true);
            pai.replaceChild(novo, centro);
        } catch (e) {
            try {
                centro.innerHTML = centro2.innerHTML;
            } catch (e) {
                window.alert("Erro ao atualizar o conteudo da pagina:\n" + e.message + "\n\nRecomenda-se desabilitar o Javascript");
            }
        }
    } else {
        var conteudo = xml.getElementById("conteudo");
        if (conteudo) {
            limpar(centro);
            centro.appendChild(conteudo);
        } else {
            var body2 = xml.documentElement.getElementsByTagName("body");
            if (body2) {
                limpar(centro);
                centro.appendChild(body2.item(0))
            } else {
                window.alert("Documento incorreto");
            }
        }
    }

    var titulo = xml.documentElement.getElementsByTagName("title").item(0).innerHTML;

    // Atualizar URL, caso suportado
    if (mudou) {
        try {
            history.pushState({url:GLB.url_atual}, titulo, GLB.url_atual);
        } catch (e) {
            //void
        }
    }

    // Atualizar titulo
    document.title = titulo;

    // Atualizar ID do body
    var id_body = xml.documentElement.getElementsByTagName("body").item(0).id;
    document.getElementsByTagName("body").item(0).id = id_body ? id_body : "";

//TODO resolver bug no firefox:
// se a pagina possui JavaScript extra e aparece uma mensagem de erro,
// por algum motivo o script principal esta sendo recarregado, o que faz limpar o class_tremer.instancias,
// o que faz uma cachoeira de erros de interval tentando manipular objeto que nao existe

    // Reiniciar documento apos 0.5 segundo
    window.setTimeout('iniciar(' + (ajax.foco ? 'true' : 'false') + ', true, true);', 500);

    // Desbloquear
    GLB.bloqueio_ajax = false;

    return true;
}


//
//     Define atributos de objetos
//
function definir_atributos() {
    if (CFG.navegador == "firefox") {
        var objects = document.getElementsByTagName("object");
        if (objects && objects.length) {
            var l = objects.length;
            for (var i = 0; i < l; i++) {
                object = objects.item(i);
                var pai = object.parentNode;
                c = object.cloneNode(true);
                document.importNode(c, true);
                pai.insertBefore(c, object);
                pai.removeChild(object);
            }
        }
    }

    var body = document.getElementsByTagName("body").item(0);
    var itens = body.getElementsByTagName("*");
    var tam = itens.length;

    var captcha = document.getElementById("captcha");
    if (captcha && !captcha.def && possui_classe(captcha.parentNode, "captcha_imagem")) {

        // Criar botao de mudar imagem
        var b = criar_elemento("input", {"type":"button", "value":"Mudar imagem", "class":"botao", "title":"Mudar imagem pois a leitura está complicada"});

        // Evento do botao
        b.onclick = function() {
            var img = this.parentNode.getElementsByTagName("img").item(0);
            var vt = img.getAttribute("src").split("?");
            img.setAttribute("src", vt[0] + "?c=" + Math.random());
        };
        captcha.parentNode.appendChild(b);
        captcha.def = 1;
    }

    for (var i = 0; i < tam; i++) {
        var item = itens.item(i);

        // Se o item ja foi definido
        if (item.def) { continue; }

        // Checar classe
        var tag = get_tag(item);
        var classes = get_classe(item);
        if (classes != null) {
            var vt_classes = classes.split(" ");
            var l = vt_classes.length;
            for (var j = 0; j < l; j++) {
                definir_eventos_classe(item, vt_classes[j]);
            }
        }

        // Checar rel
        switch (item.getAttribute("rel")) {
        case "blank":
            item.setAttribute("target", "_blank");
            item.def = 1;
            break;
        case "checar":
            if (tag == "a") {
                checar_link(item);
                item.def = 1;
            }
            break;
        }
    }

    // Percorrer formularios
    var l = document.forms.length;
    for (var i = 0; i < l; i++) {
        var form = document.forms[i];
        var fields = form.getElementsByTagName("fieldset");
        var l2 = fields.length;
        for (var j = 0; j < l2; j++) {
            atualizar_fieldset(fields[j]);
        }
        var campos = form.getElementsByTagName("*");
        var l2 = campos.length;
        for (var j = 0; j < l2; j++) {
            atualizar_campo(campos.item(j), form);
        }

        var metas = form.getElementsByTagName("meta");
        var l2 = metas.length;
        for (var j = 0; j < l2; j++) {
            var meta = metas.item(j);
            var campo = document.getElementById(meta.getAttribute("name"));

            // Se achou o campo
            if (campo != null) {
                var div = campo;
                do {
                    div = div.parentNode;
                } while (div && div.nodeName.toLowerCase() != "div");
                if (div.previousSibling.nodeName.toLowerCase() == "label") {
                    var label = div.previousSibling;
                    atualizar_info_campo(form, campo, meta.getAttribute("content"), label);
                }

            // Se nao achou, pode ser um campo bool com dois IDs (posfixo "_sim" e "_nao")
            } else {
                var campo_sim = document.getElementById(meta.getAttribute("name") + "_sim");
                var campo_nao = document.getElementById(meta.getAttribute("name") + "_nao");
                if (campo_sim != null && campo_nao != null) {
                    var label = campo_sim.parentNode.parentNode.previousSibling;
                    atualizar_info_campo(form, campo_sim, meta.getAttribute("content"), label);
                    atualizar_info_campo(form, campo_nao, meta.getAttribute("content"), label);
                }
            }
        }
    }

    // Voltar ao topo
    var topo = document.getElementById("voltar_topo");
    if (topo) {
        topo.onclick = function(e) {
            e = e ? e : window.event;
            e.returnValue = false;
            window.scroll(0, 0);
            return false;
        };
    }

    // Menu flutuante
    var menu = document.getElementById("menu");
    if (menu) {
        menu.clonar = true;
        menu.simp_ondrop = function(pos) {
            var principal  = document.getElementById("conteudo_principal");
            var secundario = document.getElementById("conteudo_secundario");
            var conteudo   = principal.parentNode;
            limpar(conteudo);

            var largura_tela = 1024;
            if (document.body && document.body.offsetWidth) {
                largura_tela = document.body.offsetWidth;
            }
            if (document.compatMode=='CSS1Compat' &&
                document.documentElement &&
                document.documentElement.offsetWidth ) {
                largura_tela = document.documentElement.offsetWidth;
            }
            if (window.innerWidth && window.innerHeight) {
                largura_tela = window.innerWidth;
            }

            // Menu na direita
            if (pos.x <= (largura_tela / 2)) {
                conteudo.appendChild(secundario);
                conteudo.appendChild(principal);

            // Menu na esquerda
            } else  {
                conteudo.appendChild(principal);
                conteudo.appendChild(secundario);
            }

            // Remover clone
            GLB.flutuante.style.opacity  = GLB.clone.style.opacity;
            GLB.flutuante.style.position = GLB.clone.style.position;
            GLB.flutuante.style.width    = GLB.clone.style.width;
        };

        var strongs = menu.getElementsByTagName("strong");
        var l = strongs.length;
        for (var i = 0; i < l; i++) {
            objeto_movel(strongs.item(i), menu);
        }
    }
}


//
//     Incluir eventos a um item de acordo com a sua classe
//
function definir_eventos_classe(item, classe) {
// Mixed item: item
// String classe: classe
//
    var tag = get_tag(item);
    switch (classe) {
    case "drag":
        var item_movel = document.getElementById("drag_" + item.id);
        if (item_movel) {
            objeto_movel(item, item_movel);
        }
        item.def = 1;
        break;
    case "erro":
        if (tag == "div") {
            incluir_link_fechar(item);
            var obj_tremer = new class_tremer(item, 4);
            obj_tremer.tremer(1500);
            item.def = 1;
        }
        break;
    case "aviso":
        if (tag == "div") {
            incluir_link_fechar(item);
            var obj_piscar = new class_piscar(item, 1, 0.1);
            obj_piscar.piscar();
            item.def = 1;
        }
        break;
    case "bloco_ajuda_aberto":
    case "bloco_ajuda_fechado":
        var blockquote = item.getElementsByTagName("blockquote");
        if (blockquote.length == 1) {
            blockquote = blockquote.item(0);

            var link_abrir = criar_elemento("a", {"title":"Abrir em outra janela", "class":"bt_ajuda_externa"}, {"display":"block", "textAlign":"right"}, " Abrir em Janela Externa");
            {
                var img_abrir = criar_elemento("img", {"src":CFG.wwwroot + "imgs/icones/link_externo.gif", "alt":"Link externo"});
                inserir_inicio(link_abrir, img_abrir);
            }
            link_abrir.onclick = function() {
                window.open(CFG.wwwroot + "modulos/ajuda/popup.php", "_blank", "height=300px, width=500px, location=no, menubar=no, status=no, toolbar=no, scrollbars=yes", true);
                return false;
            };

            blockquote.appendChild(link_abrir);
        }
        item.def = 1;

        break;
    case "info_aguarde_sugestao":
        var inputs = item.nextSibling.getElementsByTagName("input");
        if (inputs.length == 1) {
            var input = inputs.item(0);
            definir_campo_sugestao(input, item.id);
            item.def = 1;
        }
        break;
    case "relacionamento":
        if (tag == "a") {
            var seletor = new class_seletor(item);
            item.def = 1;
        }
        break;
    case "hierarquia":
        if (tag == "a") {
            var seletor = new class_hierarquia(item);
            item.def = 1;
        }
        break;
    case "data":
        if (tag == "div") {
            var seletor_data = new class_calendario(item);
            item.def = 1;
        }
        break;
    case "popup":
        if (tag == "a") {
            var popup = new class_popup(item);
            item.def = 1;
        }
        break;
    case "botao":
        if (item.getAttribute("type") == "submit") {
            item.clicou = false;
            item.onclick = function() {
                this.clicou = true;
                if (possui_classe(this, "noajax")) {
                    this.form.onsubmit = function() { return true };
                }
                return true;
            };
            var form = item.form;
            if (form.onsubmit) {
                var novo = function() {
                    var vt = this.getElementsByTagName('input');
                    var l = vt.length;
                    for (var i = 0; i < l; i++) {
                        var s = vt.item(i);
                        if (s.clicou) {
                            var h = criar_elemento("input", {"type":"hidden", "name":s.getAttribute("name"), "value":s.getAttribute("value")});
                            this.appendChild(h);
                            s.setAttribute("disabled", "disabled");
                            s.antes = s.getAttribute("value");
                            s.setAttribute("value", "Aguarde");
                            s.style.backgroundImage = "url(" + CFG.wwwroot + "imgs/icones/carregando_form.gif)";
                            s.style.backgroundRepeat = "no-repeat";
                        }
                    }
                };
                form.onsubmit = juntar_funcoes(form.onsubmit, novo);
            }
            item.def = 1;
        }
        break;
    case "area_clones":
        var meta = item.getElementsByTagName("meta").item(0);
        var info_clone = eval('(' + meta.getAttribute("content") + ')');

        var bt_clonar = criar_elemento("input", {"type":"button", "class":"botao", "value":"Adicionar " + info_clone.nome});
        bt_clonar.info_clone = info_clone;
        bt_clonar.onclick = function() {
            var el = document.getElementById(this.info_clone.id);
            if (!el) { return false; }
            clonar(el, this.parentNode.getElementsByTagName("div").item(0), this.info_clone);
            return true;
        };
        item.appendChild(bt_clonar);
        item.def = 1;
        break;
    case "com_marcador":
        var label = criar_elemento("label", {}, {"marginTop":"0.5em"});
        {
            var cb = criar_elemento("input", {"type":"checkbox"});
            cb.onclick = function() {
                var f = this.parentNode.parentNode;
                var el = f.getElementsByTagName('input');
                var l = el.length;
                for (var i = 0; i < l; i++) {
                    if (el[i].type == 'checkbox' && el[i].disabled == false) {
                        el[i].checked = this.checked;
                    }
                }
                return true;
            };

            label.appendChild(cb);
            label.appendChild(document.createTextNode(" Marcar Todos"));
        }
        item.appendChild(label);
        item.def = 1;
        break;
    }
    return true;
}


//
//     Atualiza um fieldset incluindo botao de expandir
//
function atualizar_fieldset(fieldset) {
//
//
    var legend = fieldset.getElementsByTagName("legend").item(0);

    // se ja tem
    var img = legend.getElementsByTagName("img");
    if (img.length > 0) {
        return true;
    }

    var botao = criar_elemento(
        "img",
        {"src":CFG.wwwroot + "imgs/icones/menos.gif", "alt":"Abrir/Fechar", "title":"Abrir/Fechar"},
        {"backgroundColor":"#FFFFFF", "cursor":"pointer", "marginRight":"0.5em"}
    );
    botao.aberto = 1;
    botao.onclick = function() {
        var f = this.parentNode.parentNode;
        var c = f.firstChild;

        this.aberto = this.aberto ? 0 : 1;

        while (c != null) {
            if (c.nodeType == 1 && get_tag(c) != "legend") {
                if (c.display_original == undefined) {
                    c.display_original = c.style.display ? c.style.display : 'block';
                }
                if (this.aberto) {
                    c.style.display = c.display_original;
                } else {
                    c.style.display = "none";
                }
            }
            c = c.nextSibling;
        }
        if (this.aberto) {
            this.src = CFG.wwwroot + "imgs/icones/menos.gif";
        } else {
            this.src = CFG.wwwroot + "imgs/icones/mais.gif";
        }
        return false;
    };

    legend.insertBefore(botao, legend.firstChild);
}


//
//     Atualiza um elemento do formulario
//
function atualizar_campo(campo, form) {
// Object campo: campo do formularo
// Object form: formulario
//
    // Elementos criados dinamicamente nao sofrem modificacao
    if (campo.def) {
        return;
    }
    switch (campo.nodeName.toLowerCase()) {
    case "input":
        var type = campo.getAttribute("type");
        if (type == "password") {
            var aviso = criar_elemento("span", {"class":"caps_lock"}, {"display":"none"}, "Caps Lock");

            campo.aviso = aviso;
            campo.checado = false;
            inserir_apos(campo.parentNode, aviso, campo);

            campo.onkeypress = function(e) {
                var k = e ? (e.keyCode ? e.keyCode : e.which) : window.event.keyCode;
                e = e ? e : window.event;

                // Se precionou Caps Lock
                if (this.checado && k == 20) {
                    this.aviso.style.display = this.aviso.style.display == "block" ? "none" : "block";
                }

                this.checado = true;
                var shift = e.shiftKey;

                // Obs: ç = 231 / Ç 199

                // Se obteve uma tecla maiuscula
                if (entre(k, 65, 90) || k == 199) {

                    // se shift: nao usou caps lock
                    this.aviso.style.display = shift ? "none" : "block";
                }

                // Se obteve uma tecla minuscula
                if (entre(k, 97, 122) || k == 231) {

                    // se shift: usou caps lock
                    this.aviso.style.display = shift ? "block" : "none";
                }
                return true;
            };
            campo.def = 1;
        } else if (type == "hidden") {
            if (campo.getAttribute("name") == "id_progresso") {
                var novo = function() {
                    var janela = new class_janela('janela_progresso');
                    var caixa = janela.criar_janela("Progresso", screen.width / 2 - 200, 200, 400, 200);
                    caixa.id_progresso = this.id_progresso.getAttribute("value");
                    caixa.intervalo = 2000;
                    {
                        var div = criar_elemento("div", {"id":"area_janela_progresso"});
                        {
                            var p = criar_elemento("p", {}, {"textAlign":"center", "lineHeight":"150px"}, "Progresso: não iniciado");
                            div.appendChild(p);
                        }
                        caixa.appendChild(div);
                    }
                    janela.abrir();
                    atualizar_progresso();
                };
                form.onsubmit = juntar_funcoes(form.onsubmit, novo);
                campo.def = 1;
            }
        }
        break;
    case "select":
        if (campo.multiple && possui_classe(campo, "dupla")) {
            criar_select_dupla(form, campo);
        }
        break;
    case "textarea":
        break;
    }
}


//
//     Cria um campo de select de duas listas (enviar elementos para esquerda ou direita)
//
function criar_select_dupla(form, campo) {
// Form form: formulario
// Select campo: campo select original
//
    var div = campo.parentNode;
    var label = div.previousSibling;

    // Funcao para copiar itens nao selecionados para coluna 1 e itens selecionados para coluna 2
    campo.preencher_listas = function() {
        var listas = this.parentNode.getElementsByTagName("select");
        var lista1 = listas.item(1);
        var lista2 = listas.item(2);

        // Limpar lista 1 e lista 2
        for (var i = lista1.options.length; i > 0; i--) { lista1.remove(0); }
        for (var i = lista2.options.length; i > 0; i--) { lista2.remove(0); }

        var l = this.options.length;
        for (var i = 0; i < l; i++) {
            var option = this.options[i].cloneNode(true);
            option.indice = i;
            option.setAttribute("title", option.firstChild.nodeValue);
            if (option.selected) {
                option.removeAttribute("selected");
                option.selected = false;
                lista2.appendChild(option);
            } else {
                lista1.appendChild(option);
            }
        }
        this.atualizar_quantidade();
    };

    // Funcao para atualizar a quantidade de itens selecionados
    campo.atualizar_quantidade = function() {
        var listas = this.parentNode.getElementsByTagName("select");
        var lista1 = listas.item(1);
        var lista2 = listas.item(2);
        var p1 = lista1.parentNode.getElementsByTagName("p").item(0);
        var p2 = lista2.parentNode.getElementsByTagName("p").item(0);

        p1.replaceChild(document.createTextNode("Opções de Seleção (" + lista1.options.length + ")"), p1.firstChild);
        p2.replaceChild(document.createTextNode("Itens Selecionados (" + lista2.options.length + ")"), p2.firstChild);
    };

    // Se restaurar o formulario, reorganizar as listas
    if (!possui_classe(form, "reset_select")) {
        var novo_onreset = function() {
            // Obter selects multiplos
            var selects = this.getElementsByTagName("select");
            var l = selects.length;
            for (var i = 0; i < l; i++) {
                var select = selects.item(i);
                if (select.multiple && possui_classe(select, "dupla")) {
                    select.preencher_listas();
                }
            }
        };
        form.onreset = juntar_funcoes(form.onreset, novo_onreset);
        adicionar_classe(form, "reset_select");
    }

    // Omitir aviso de uso da tecla Ctrl
    if (possui_classe(div.parentNode.previousSibling.firstChild, "comentario")) {
        div.parentNode.previousSibling.style.display = "none";
    }

    // Aumentar largura do label
    if (get_tag(label) == "label") {
        label.style.width = "100%";
        label.style.textAlign = "left";
    }

    // Ajustar area com dois listbox
    div.style.clear = "both";
    div.style.width = "100%";

    // Criar tres colunas
    var coluna1 = criar_elemento("div", {}, {"width":"45%", "float":"left"});
    var coluna2 = criar_elemento("div", {}, {"width":"10%", "float":"left", "textAlign":"center"});
    var coluna3 = criar_elemento("div", {}, {"width":"45%", "float":"left"});

    // Incluir nova lista na coluna 1
    var lista1 = criar_elemento("select", {"multiple":"multiple", "size":campo.getAttribute("size")});
    lista1.def = 1;
    coluna1.appendChild(lista1);

    var p = criar_elemento("p", {}, {"fontSize":"0.8em"}, "Opções de Seleção");
    coluna1.appendChild(p);

    // Incluir botoes na coluna 2
    var bt_adiciona = criar_elemento("input", {"type":"button", "value":">", "title":"Enviar para direita"}, {"width":"100%", "cursor":"pointer"});
    bt_adiciona.def = 1;
    bt_adiciona.onclick = function() {
        var listas = this.parentNode.parentNode.getElementsByTagName("select");
        var lista = listas.item(0);
        var lista1 = listas.item(1);
        var lista2 = listas.item(2);

        // Copiar de 1 para 2 e marcar na original
        var vt_marcar = new Array();
        var l = lista1.options.length;
        for (var i = 0; i < l; i++) {
            var op = lista1.options[i];
            if (op.disabled) {
                continue;
            }
            if (op.selected) {

                // Marcar na original
                var indice = parseInt(op.indice);
                lista.options[indice].selected = true;

                // Copiar para 2
                op.selected = false;
                var l2 = lista2.options.length;
                var posicao = null;
                for (var j = 0; j < l2; j++) {
                    var op2 = lista2.options[j];
                    if (op.getAttribute("title").toLowerCase() < op2.getAttribute("title").toLowerCase()) {
                        posicao = op2;
                        break;
                    }
                }
                lista2.insertBefore(op, posicao);
                i -= 1;
                l -= 1;
            }
        }
        lista.atualizar_quantidade();
    };

    var bt_remove = criar_elemento("input", {"type":"button", "value":"<", "title":"Enviar para esquerda"}, {"width":"100%", "cursor":"pointer"});
    bt_remove.def = 1;
    bt_remove.onclick = function() {
        var listas = this.parentNode.parentNode.getElementsByTagName("select");
        var lista = listas.item(0);
        var lista1 = listas.item(1);
        var lista2 = listas.item(2);

        // Copiar de 2 para 1 e desmarcar na original
        var vt_desmarcar = new Array();
        var l = lista2.options.length;
        for (var i = 0; i < l; i++) {
            var op = lista2.options[i];
            if (op.disabled) {
                continue;
            }
            if (op.selected) {

                // Desmarcar na original
                var indice = parseInt(op.indice);
                lista.options[indice].selected = false;

                // Copiar para 1
                op.selected = false;
                var l2 = lista1.options.length;
                var posicao = null;
                for (var j = 0; j < l2; j++) {
                    var op2 = lista1.options[j];
                    if (op.getAttribute("title").toLowerCase() < op2.getAttribute("title").toLowerCase()) {
                        posicao = op2;
                        break;
                    }
                }
                lista1.insertBefore(op, posicao);
                i -= 1;
                l -= 1;
            }
        }
        lista.atualizar_quantidade();
    };

    coluna2.appendChild(bt_adiciona);
    coluna2.appendChild(bt_remove);

    // Incluir nova lista na coluna 3
    var lista2 = criar_elemento("select", {"multiple":"multiple", "size":campo.getAttribute("size")});
    lista2.def = 1;
    coluna3.appendChild(lista2);

    var p = criar_elemento("p", {}, {"fontSize":"0.8em"}, "Itens Selecionados");
    coluna3.appendChild(p);

    // Esconder lista original
    campo.style.display = "none";

    // Incluir colunas na area
    div.appendChild(coluna1);
    div.appendChild(coluna2);
    div.appendChild(coluna3);

    campo.preencher_listas();
    campo.def = 1;
}


//
//     Atualiza um elemento do formulario com informacoes
//
function atualizar_info_campo(form, campo, id_campo, label) {
// Object form: formulario
// Object campo: campo do formularo
// String id_campo: identificador do campo
// Object label: label do campo
//
    campo.label = label;

    campo.onfocus = function() {
        if (GLB.ultima_boia) {
            GLB.ultima_boia.style.visibility = "hidden";
        }
        var boia = this.label.parentNode.getElementsByTagName("img");
        if (boia && boia.length > 0) {
            boia = boia.item(0);
            boia.style.visibility = "visible";
        } else {
            var boia = criar_elemento(
                "img",
                {"src":CFG.wwwroot + "imgs/icones/ajuda.gif", "title":"Ajuda sobre o campo"},
                {"cursor":"pointer", "display":"block", "position":"absolute", "marginLeft":"-10px", "clear":"none"}
            );
            boia.id_campo = id_campo;
            boia.janela = false;
            boia.onclick = function(e) {
                if (!this.janela) {
                    var pos = get_posicao_mouse(e);
                    this.janela = new class_janela();
                    var caixa = this.janela.criar_janela("Carregando...", pos.x, pos.y + 5, 300);

                    atualizar_info_atributo(this.janela , campo, this.id_campo);
                }
                this.janela.abrir(document.getElementsByTagName("body").item(0));
            };
            if (label.getElementsByTagName("input").length > 0) {
                label.parentNode.appendChild(boia);
            } else {
                label.parentNode.insertBefore(boia, label);
            }
        }
        GLB.ultima_boia = boia;
    };
    return true;
}


//
//     Consuta as informacoes de um atributo e preenche o objeto
//
function atualizar_info_atributo(janela, campo, id_campo) {
// Object janela: janela que recebera as informacoes
// Object campo: campo associado a ajuda
// String id_campo: identificador do campo
//
    var that = this;
    this.janela   = janela;
    this.campo    = campo;
    this.id_campo = id_campo;
    this.ajax     = new class_ajax();

    //
    //     Atualiza a janela de ajuda com a descricao consultada com AJAX
    //
    this.atualizar_descricao = function(ajax) {
    // class_ajax ajax: objeto que devolve a requisicao
    //
        var xml = ajax.get_retorno("xml");
        obj = that.janela.caixa;

        // Se veio com erros
        if (xml.documentElement.getElementsByTagName("erro").length > 0) {
            janela.set_titulo("Erro");
            that.inserir_valor(obj, "Erro", "Dados do campo não disponíveis");
            return;
        }

        // Obter dados
        var descricao = that.get_atributo(xml.documentElement, "descricao");
        var tipo = that.get_atributo(xml.documentElement, "tipo");
        var minimo = that.get_atributo(xml.documentElement, "minimo");
        var maximo = that.get_atributo(xml.documentElement, "maximo");
        var obrigatorio = that.get_atributo(xml.documentElement, "pode_vazio") == '0';
        var unico = that.get_atributo(xml.documentElement, "unico") == '1';
        var validacao = xml.documentElement.getElementsByTagName("validacao");
        if (validacao.length) {
            var instrucoes = that.get_atributo(validacao.item(0), "instrucoes");
            var exemplo = that.get_atributo(validacao.item(0), "exemplo");
        } else {
            var instrucoes = false;
            var exemplo = false;
        }

        // Atualizar titulo da janela
        janela.set_titulo("Ajuda do campo " + descricao);

        // Checar de acordo com o tipo de campo
        switch (that.campo.nodeName.toLowerCase()) {
        case "select":
            if (that.campo.multiple) {
                that.inserir_valor(obj, "Instrução", "Escolha um elemento da lista");
            } else {
                that.inserir_valor(obj, "Instrução", "Escolha um ou mais elementos da lista");
            }
            if (instrucoes) {
                that.inserir_valor(obj, "Observações:", instrucoes);
            }
            return;
        case "input":
            switch (that.campo.getAttribute("text")) {
            case "text":
            case "password":
                break;
            case "checkbox":
                that.inserir_valor(obj, "Instrução", "Marque as opções desejadas");
                return;
            case "radio":
                that.inserir_valor(obj, "Instrução", "Escolha uma opção");
                return;
            case "file":
                that.inserir_valor(obj, "Instrução", "Escolha um arquivo");
                return;
            }
            break;
        case "textarea":
            break;
        }

        if (!minimo) {
            minimo = "indefinido";
        }
        if (!maximo) {
            maximo = "indefinido";
        }

        switch (tipo) {
        case "int":
            that.inserir_valor(obj, "Tipo", "Número Inteiro");
            that.inserir_valor(obj, "Intervalo", "de " + minimo + " até " + maximo);
            if (obrigatorio) {
                that.inserir_valor(obj, "Obrigatório", "Sim");
            }
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            break;
        case "float":
            that.inserir_valor(obj, "Tipo", "Número Real");
            that.inserir_valor(obj, "Intervalo", "de " + minimo + " até " + maximo);
            if (obrigatorio) {
                that.inserir_valor(obj, "Obrigatório", "Sim");
            }
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            break;
        case "string":
            that.inserir_valor(obj, "Tipo", "Texto");
            if (minimo == maximo) {
                that.inserir_valor(obj, "Tamanho", "exatamente " + minimo + " caracteres");
            } else {
                that.inserir_valor(obj, "Tamanho", "entre " + minimo + " e " + maximo + " caracteres");
            }
            if (obrigatorio) {
                that.inserir_valor(obj, "Obrigatório", "Sim");
            }
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            if (instrucoes) {
                that.inserir_valor(obj, "Instruções", instrucoes);
            }
            if (exemplo) {
                that.inserir_valor(obj, "Exemplo", exemplo);
            }
            break;
        case "binario":
            that.inserir_valor(obj, "Tipo", "Texto binário");
            that.inserir_valor(obj, "Tamanho", "entre " + minimo + " e " + maximo);
            if (obrigatorio) {
                that.inserir_valor(obj, "Obrigatório", "Sim");
            }
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            break;
        case "char":
            that.inserir_valor(obj, "Tipo", "Caractere");
            if (obrigatorio) {
                that.inserir_valor(obj, "Obrigatório", "Sim");
            }
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            break;
        case "bool":
            that.inserir_valor(obj, "Tipo", "Sim ou Não");
            if (instrucoes) {
                that.inserir_valor(obj, "Instruções", instrucoes);
            }
            break;
        case "data":
            that.inserir_valor(obj, "Tipo", "Data");
            if (unico) {
                that.inserir_valor(obj, "Único no Sistema", "Sim (não podem existir 2 iguais)");
            }
            break;
        }
    };

    //
    //     Insere uma chave/valor na janela de ajuda
    //
    this.inserir_valor = function(obj, label, valor) {
    // Object obj: objeto que recebera a linha
    // String label: texto do label
    // String valor: texto do valor
    //
        var p = criar_elemento("p");
        {
            var strong = criar_elemento("strong");
            strong.appendChild(document.createTextNode(label + ":"));
            p.appendChild(strong);
            p.appendChild(document.createTextNode(" " + valor));
        }
        obj.appendChild(p);
    };

    //
    //     Obtem um atributo de um elemento recebido por AJAX
    //
    this.get_atributo = function(xml, atributo) {
    // Object xml: elemento XML que deseja-se obter o atributo
    // String atributo: nome do atributo desejado
    //
        var valor = xml.getElementsByTagName(atributo);
        if (!valor.length || !valor.item(0).hasChildNodes()) {
            return false;
        }
        return valor.item(0).firstChild.nodeValue;
    };

    // Realizar a consulta com AJAX
    var link = CFG.wwwroot + "webservice/atributo.xml.php?id=" + id_campo;
    this.ajax.set_callback([that, "atualizar_descricao"]);
    this.ajax.consultar("GET", link, true, null);
}


//
//     Checa se um valor esta dentro de um intervalo
//
function entre(valor, minimo, maximo) {
// Numeric valor: valor a ser testado
// Numeric minimo: valor minimo possivel
// Numeric maximo: valor maximo possivel
//
    return valor >= minimo && valor <= maximo;
}


//
//     Solicita a atualizacao da barra de progresso
//
function atualizar_progresso() {
    var caixa = document.getElementById('janela_progresso');
    if (caixa) {
        var url = adicionar_param(CFG.wwwroot + "webservice/progresso.php", "id", caixa.id_progresso);

        var ajax = new class_ajax();
        if (!ajax.xmlhttp) { return; }
        ajax.set_callback("atualizar_progresso_xml");
        ajax.consultar("GET", url, true, null, true);
    }
}


//
//     Atualiza a barra de progresso e agenda uma nova atualizacao (caso necessario)
//
function atualizar_progresso_xml(ajax) {
// class_ajax ajax: objeto que devolve a requisicao
//
    var xml = ajax.get_retorno("xml");

    var caixa = document.getElementById('janela_progresso');
    var div = document.getElementById('area_janela_progresso');
    try {
        var percentual     = get_conteudo_node(xml.getElementsByTagName("percentual").item(0));
        var tempo_gasto    = get_conteudo_node(xml.getElementsByTagName("tempo_gasto").item(0));
        var tempo_estimado = get_conteudo_node(xml.getElementsByTagName("tempo_estimado").item(0));
        var tempo_restante = get_conteudo_node(xml.getElementsByTagName("tempo_restante").item(0));
        if (possui_classe(div, "progresso_iniciado")) {
            var barra = document.getElementById("barra_janela_progresso");
            barra.style.width = percentual + "%";
            barra.replaceChild(document.createTextNode(percentual + "%"), barra.firstChild);

        } else {
            definir_classe(div, "progresso_iniciado");
            limpar(div);
            div.style.padding = "1em";
            {
                var span = criar_elemento("span", {}, {}, "Progresso:");
                div.appendChild(span);

                var fundo = criar_elemento("div",
                    {},
                    {"backgroundColor":"#FFFFFF", "border":"2px inset #CCCCCC", "padding":"2px 4px 2px 2px", "height":"20px"}
                );
                {
                    var barra = criar_elemento(
                        "div",
                        {"id":"barra_janela_progresso"},
                        {"backgroundColor":"#000033", "border":"1px outset #000033", "height":"17px", "color":"#FFFF00", "fontSize":"9pt", "textAlign":"right", "padding":"1px 1px 0 0", "width":percentual + "%"},
                        percentual + "%"
                    );
                    fundo.appendChild(barra);
                }
                div.appendChild(fundo);
            }
        }
        if (percentual != 100 && caixa) {
            caixa.timer = ativar_timer("atualizar_progresso();", caixa.intervalo);
        }
    } catch (e) {
        //void
    }
}


//
//     Inclui um link para fechar o elemento
//
function incluir_link_fechar(item) {
// Object item: elemento da pagina
//
    var a = criar_elemento("a", {}, {"display":"block", "textAlign":"center", "fontSize":"small", "marginTop":"1em", "cursor":"pointer"}, "Fechar");
    a.onclick = function() {
        if (CFG.engine == 'mshtml') {
            this.parentNode.parentNode.removeChild(this.parentNode);
        } else {
            fechar(this.parentNode);
        }
        return false;
    };

    item.appendChild(a);
}


//
//     Classe Tremer
//
function class_tremer(item, offset) {
// Object item: elemento que vai tremer
// Int offset: deslocamento horizontal do elemento
//
    this.id = class_tremer.instancias.length;
    class_tremer.instancias[this.id] = this;

    var that = this;
    this.obj_tremer   = item.cloneNode(true);
    this.obj_original = item;
    this.offset       = (offset > 0) ? offset : 4;
    this.timer        = null;
    this.i            = 0;


    //
    //     Faz um objeto comecar a tremer
    //
    this.tremer = function(tempo) {
    // Int tempo: tempo que vai levar para parar de tremer
    //
        if (that.obj_tremer.innerHTML.length < GLB.limite_tamanho) {

            // Ajustar o objeto que vai tremer
            var pos = get_posicao(that.obj_original);

            that.obj_tremer.style.position = "absolute";
            that.obj_tremer.style.margin = "0px";
            that.obj_tremer.style.left = pos.x + "px";
            that.obj_tremer.style.top = pos.y + "px";

            // Substituir o objeto original pelo que vai tremer
            that.obj_original.parentNode.replaceChild(that.obj_tremer, that.obj_original);

            // Comecar a tremer
            that.timer = ativar_interval("try { class_tremer.instancias[" + that.id + "].mover(" + GLB.timestamp + "); } catch (e) {}", 70);
            ativar_timer("class_tremer.instancias[" + that.id + "].parar_tremer()", tempo);
        }
    };


    //
    //     Faz o objeto ir para um lado ou para o outro
    //
    this.mover = function(timestamp) {
    // Int timestamp: Timestamp da pagina
    //
        if (timestamp != GLB.timestamp) {
            return;
        }
        try {
            that.i = 1 - that.i;
            var left = (that.i == 1) ? (-1 * that.offset) : that.offset;
            var pos = parseInt(that.obj_tremer.style.left) + left;
            that.obj_tremer.style.left = pos + "px";
        } catch (e) {
            that.parar_tremer();
        }
    };


    //
    //     Faz o objeto parar de tremer
    //
    this.parar_tremer = function() {
        cancelar_interval(that.timer);
        that.obj_tremer.parentNode.replaceChild(that.obj_original, that.obj_tremer);
    };
}


//
//     Classe Piscar
//
function class_piscar(item, vezes, passo) {
// Object item: elemento que vai piscar
// Int vezes: numero de vezes que o elemento vai piscar
// Int passo: passo em que o atributo opacity vai caminhar (1 em 1 por padrao)
//
    this.id = class_piscar.instancias.length;
    class_piscar.instancias[this.id] = this;

    var that = this;
    this.obj_piscar = item;
    this.passo      = (passo > 0) ? passo : 0.1;
    this.opacity    = (item.style.opacity != "") ? parseFloat(item.style.opacity) : 1.0;
    this.timer      = null;
    this.vezes      = parseInt(vezes);
    this.sentido    = false;


    //
    //     Faz um objeto comecar a piscar
    //
    this.piscar = function() {
        if (that.obj_piscar.innerHTML.length < GLB.limite_tamanho) {
            that.obj_piscar.piscando = true;
            that.obj_piscar.style.opacity = parseFloat(that.opacity);
            that.sentido = false;
            that.timer = ativar_interval("try { class_piscar.instancias[" + that.id + "].mudar(" + GLB.timestamp + "); } catch (e) {}", 50);
        }
    };


    //
    //     Faz o objeto mudar a opacidade
    //
    this.mudar = function(timestamp) {
    // Int timestamp: Timestamp da pagina
    //
        if (timestamp != GLB.timestamp) {
            cancelar_interval(that.timer);
            return;
        }
        try {
            var f = parseFloat(that.obj_piscar.style.opacity);
            if (that.sentido) {
                f += that.passo;
            } else {
                f -= that.passo;
            }

            if (f <= 0) {
                f = 0;
                that.obj_piscar.style.opacity = f.toFixed(2);
                that.sentido = true;
                that.vezes -= 1;
            } else if (f >= that.opacity) {
                f = that.opacity;
                that.obj_piscar.style.opacity = f.toFixed(2);
                that.sentido = false;
            } else {
                that.obj_piscar.style.opacity = f.toFixed(2);
            }

            if ((that.vezes <= 0) && (f == that.opacity)) {
                that.parar_piscar();
            }
        } catch (e) {
            that.parar_piscar();
        }
    };


    //
    //     Faz um objeto parar de piscar
    //
    this.parar_piscar = function() {
        that.obj_piscar.piscando = false;
        that.sentido = false;
        cancelar_interval(that.timer);
    };
}


//
//     Faz um objeto sumir
//
function fechar(obj) {
// Object obj: objeto a ser fechado
//
    if (!obj.piscando) {
        var obj_fechar = new class_fechar(obj);
        obj_fechar.dissolver(0.1);
    }
}


//
//     Classe Fechar
//
function class_fechar(item) {
// Object item: elemento a ser fechado
//
    this.id = class_fechar.instancias.length;
    class_fechar.instancias[this.id] = this;

    var that = this;
    this.obj_fechar = item;
    this.passo      = 0.1;
    this.timer      = null;


    //
    //     Dissolve um objeto
    //
    this.dissolver = function(passo) {
    // Int passo: passo em que o elemento vai perder a opacity (padrao de 1 em 1)
    //
        if (passo > 0) {
            that.passo = parseFloat(passo);
        }
        if (that.obj_fechar.innerHTML.length < GLB.limite_tamanho) {
            if (that.obj_fechar.style.opacity == "") {
                that.obj_fechar.style.opacity = 1.0;
            }
            that.timer = ativar_interval("class_fechar.instancias[" + that.id + "].mudar_dissolver(" + GLB.timestamp + ")", 50);
        } else {
            that.obj_fechar.parentNode.removeChild(that.obj_fechar);
            delete that.obj_fechar;
            that.obj_fechar = null;
        }
    };


    //
    //     Reduz a opacidade de um objeto
    //
    this.mudar_dissolver = function(timestamp) {
    // Int timestamp: Timestamp da pagina
    //
        if (timestamp != GLB.timestamp) {
            return;
        }
        var f = parseFloat(that.obj_fechar.style.opacity);
        f -= that.passo;
        that.obj_fechar.style.opacity = f.toFixed(2);
        if (f <= 0) {
            that.parar_timer();
        }
    };


    //
    //     Para de dissolver um objeto
    //
    this.parar_timer = function() {
        cancelar_interval(that.timer);
        that.obj_fechar.parentNode.removeChild(that.obj_fechar);
        delete that.obj_fechar;
        that.obj_fechar = null;
    };
}


//
//     Define o foco no primeiro formulario
//
function set_foco() {
    var ignorar_foco = document.getElementById("ignorar_foco");
    if (ignorar_foco) {
        return;
    }
    try {
        var l = document.forms.length;
        for (var f = 0; f < l; f++) {
            var l2 = document.forms[f].length;
            for (var i = 0; i < l2; i++) {
                var c = document.forms[f][i];
                if (c.getAttribute("type") != "hidden" && !c.getAttribute("disabled")) {
                    c.focus();
                    c.select();
                    return;
                }
            }
        }
    } catch(e) {
        // Deixa queto, nao e' importante mesmo
    }
}


//
//     Rola ate o fragmento em tanto tempo
//
function rolar_fragment(fragment) {
// String fragment: Identificador do fragmento
//
    // Rolar ate o ponto do fragment
    var elem_fragment = document.getElementById(fragment);
    var pos_elem = get_posicao(elem_fragment);

    var x_inicial = window.scrollX;
    var y_inicial = window.scrollY;
    var dif = pos_elem.y - y_inicial;

    // Descer
    if (dif > 0) {
        rolar(pos_elem.y, 20, 10);

    // Subir
    } else {
        rolar(pos_elem.y, -20, 10);
    }
}


//
//     Rola ate uma posicao Y e agenda uma nova rolagem
//
function rolar(fim, passo, tempo) {
// Int fim: posicao fim
// Int passo: pixels para subir ou descer
// Int tempo: intervalo de tempo ate a proxima rolagem
//
    var x_inicial = window.scrollX;
    var y_inicial = window.scrollY;

    // Descendo
    if (passo > 0) {
        if (y_inicial + passo < fim) {
            window.scrollTo(x_inicial, y_inicial + passo);
            if (window.scrollY != y_inicial) {
                ativar_timer("rolar(" + fim + ", " + passo + ", " + tempo + ");", tempo);
            }
        } else {
            window.scrollTo(x_inicial, fim);
        }

    // Subindo
    } else {
        if (y_inicial + passo > fim) {
            window.scrollTo(x_inicial, y_inicial + passo);
            if (window.scrollY != y_inicial) {
                ativar_timer("rolar(" + fim + ", " + passo + ", " + tempo + ");", tempo);
            }
        } else {
            window.scrollTo(x_inicial, fim);
        }
    }
}


//
//     Atualiza a data e hora no menu
//
function atualizar_tempo() {
    var ajax = new class_ajax();
    ajax.set_callback("atualizar_tempo_xml");
    ajax.consultar("GET", CFG.wwwroot + "webservice/data.xml.php", true, null, false);
}


//
//     Atualiza a data e hora recebida do XML
//
function atualizar_tempo_xml(ajax) {
// class_ajax ajax: objeto que devolve a requisicao
//
    var xml = ajax.get_retorno("xml");
    if (!xml) {
        return false;
    }

    var hl =  document.getElementById("hora_local");
    var dl =  document.getElementById("data_local");
    if (!hl || !dl) { return; }

    var data = get_conteudo_node(xml.documentElement.firstChild);
    var hora = get_conteudo_node(xml.documentElement.lastChild);

    var texto_data = document.createTextNode(data);
    var texto_hora = document.createTextNode(hora);

    hl.replaceChild(texto_hora, hl.firstChild);
    dl.replaceChild(texto_data, dl.firstChild);
}


//
//     Obtem o conteudo textual do no
//
function get_conteudo_node(node) {
// Object node: node a ser verificado
//
    if (node.firstChild) {
        return get_conteudo_node(node.firstChild);
    } else if (node.data) {
        return node.data;
    } else if (node.nodeValue) {
        return node.nodeValue;
    }
    return null;
}


//
//     Cria um cookie
//
function setcookie(nome, valor, tempo) {
// String nome: nome do cookie
// String valor: valor do cookie
// Int tempo: dias de validade
//
    if (!tempo) {
        var expires = "0";
    } else {
        var d = new Date();
        d.setDate(d.getDate() + tempo);
        var expires = d.toGMTString();
    }

    var c = nome + "=" + encodeURIComponent(valor) + "; expires=" + expires + "; path=" + CFG.path;
    if (!CFG.localhost) {
        c += "; domain=" + CFG.dominio_cookies;
    }
    document.cookie = c;
};


//
//     Recupera um cookie
//
function getcookie(nome) {
// String nome: nome do cookie a ser recuperado
//
    var reMatchCookie = new RegExp ( "(?:; )?" + nome + "=([^;]*);?" );
    return (reMatchCookie.test(document.cookie) ? decodeURIComponent(RegExp.$1) : null);
};


//
//     Abre ou fecha um link de ajuda
//
function mostrar_ajuda(link) {
// A link: link que serve de botao para abrir/fechar o texto de ajuda
//
    var caixa = link.parentNode;
    var vet = caixa.getElementsByTagName("blockquote");
    if (!vet.length) { return false; }
    var blockquote = vet[0];

    if (get_classe(blockquote) == "hide") {
        setcookie("expandir", "1");
        definir_classe(caixa, "bloco_ajuda_aberto");
        definir_classe(blockquote, "visivel");
        link.setAttribute("title", "Esconder Ajuda");
    } else {
        setcookie("expandir", "0");
        definir_classe(caixa, "bloco_ajuda_fechado");
        definir_classe(blockquote, "hide");
        link.setAttribute("title", "Expandir Ajuda");
    }
    link.removeAttribute("href");
    return false;
}


//
//     Define uma classe ao elemento
//
function definir_classe(obj, classe) {
// Object obj: objeto que deseja-se definir uma classe
// String classe: classe CSS a ser aplicada
//
    obj.className = classe;
}


//
//     Adiciona uma classe ao elemento
//
function adicionar_classe(obj, classe) {
// Object obj: objeto que deseja-se adicionar uma classe
// String classe: classe CSS a ser adicionada
//
    var classes = get_classe(obj);
    if (classes == null) {
        definir_classe(obj, classe);
    } else {
        if (!in_array(classe, classes.split(" "))) {
            definir_classe(obj, classes + " " + classe);
        }
    }
}


//
//     Retira uma classe do elemento
//
function remover_classe(obj, classe) {
// Object obj: objeto que deseja-se remover uma classe
// String classe: classe CSS a ser removida
//
    if (classe == undefined) {
        obj.removeAttribute("class");
        return;
    }

    var classes = get_classe(obj);
    if (classes != null) {
        var vt = classes.split(" ");
        var vt2 = new Array();
        var l = vt.length;
        for (var i = 0; i < l; i++) {
            if (classe != vt[i]) {
                vt2.push(vt[i]);
            }
        }

        if (vt2.length > 0) {
            definir_classe(obj, vt2.join(" "));
        } else {
            definir_classe(obj, null);
            obj.removeAttribute("class");
        }
    }
}


//
//     Testa a classe de um elemento
//
function possui_classe(obj, classe) {
// Object obj: objeto que deseja-se checar a classe
// String classe: classe a ser testada
//
    var classes = get_classe(obj);
    if (classes != null) {
        var vt = classes.split(" ");
        var i = vt.length - 1;
        while (i >= 0) {
            if (classe == vt[i]) {
                return true;
            }
            i--;
        }
    }
    return false;
}


//
//     Recupera a classe de um elemento
//
function get_classe(obj) {
// Object obj: objeto que deseja-se obter a classe
//
    if (obj.className) {
        return obj.className;
    } else if (obj.getAttribute) {
        return obj.getAttribute("class");
    }
    return null;
}


//
//     Recupera o nome da tag
//
function get_tag(obj) {
    return obj.nodeName.toLowerCase();
}


//
//     Funcao para limpar um elemento recursivamente
//
function limpar(obj) {
// Object obj: objeto a ser limpado recursivamente
//
    while (obj.hasChildNodes()) {
        obj.removeChild(obj.firstChild);
    }
}


//
//     Faz um elemento aparecer ou sumir
//
function mudar(id) {
// String id: id do objeto
//
    var obj = document.getElementById(id);
    if (!obj) { return false; }
    if (get_classe(obj) == "hide") {
        definir_classe(obj, "bloco");
    } else {
        definir_classe(obj, "hide");
    }
    return false;
}


//
//     Mascaras usadas em campos de texto
//
function mascara(e, input, tipo, blur, local) {
// Event e: evento para acionar a mascara
// Object input: input de texto que deseja-se testar
// String tipo: tipo de mascara a ser checada (digitos, letras, moeda, int, float, uint e ufloat)
// Bool blur: flag indicando se o input acaba de perder o foco ou nao
// String local: codigo da localidade
//
    var valor = input.value;
    var exemplo = "";
    var exp;
    var fim;
    local = local.toLowerCase();

    if (valor.length == 0) { return true; }

    this.set_valido = function(valido, input) {
        var vt_img = input.parentNode.getElementsByTagName("img");
        if (vt_img.length > 0) {
            var img = vt_img.item(0);
        } else {
            var img = criar_elemento("img");
        }
        var src = CFG.wwwroot + "imgs/icones/" + (valido ? "valido.gif" : "invalido.gif");
        var alt = valido ? "válido" : "inválido";
        img.setAttribute("src", src);
        img.setAttribute("alt", "campo " + alt);
        img.setAttribute("title", "campo " + alt);
        if (vt_img.length == 0) {
            img.style.marginLeft = "4px";
            img.style.cssFloat = "left";
            img.style.position = "absolute";
            input.style.width = (input.offsetWidth - 30) + "px";
            input.parentNode.appendChild(img);
        }
    };

    switch (local) {
    case "pt_br":
    case "pt_br.utf-8":
    case "portuguese_brazil.1252":
        local = "pt_br";
        break;
    case "en_us":
    case "en_us.utf-8":
    case "English_Australia.1252":
        local = "en_us";
        break;

    default:
        return true;
    }
    var obj_tipo = eval("CFG.exp." + local + "." + tipo);

    var exp = obj_tipo.exp;
    var fim = obj_tipo.fim;

    var re_exp = new RegExp(exp.substr(1, exp.length - 2));
    var re_fim = new RegExp(fim.substr(1, fim.length - 2));

    var resultado_exp = re_exp.test(valor);
    var resultado_fim = re_fim.test(valor);

    this.set_valido(resultado_fim, input);

    // Se nao passou no teste
    if (blur == 1) {
        if (resultado_fim) {
            return true;
        }
    } else {
        if (resultado_exp) {
            return true;
        }
        if (input.valor_antigo != undefined) {
            input.value = input.valor_antigo;
        }
    }
    return false;
}


//
//     Define que um input sera de sugestao
//
function definir_campo_sugestao(input, id) {
// Object input: elemento input que recebe um texto
// String id: id do elemento que possui as informacoes sobre a busca pelas sugestoes
//
    input.timer_busca = false;
    input.info = document.getElementById(id);
    input.valor_antigo = "";
    input.setAttribute("autocomplete", "off");
    input.div = input.nextSibling;
    input.ajax = new class_ajax();

    /// Metodos

    //
    //     Limpar sugestoes
    //
    input.limpar_sugestoes = function() {
        limpar(this.div);
        this.div.style.display = "none";
    };

    //
    //     Ativa o timer de busca
    //
    input.ativar_timer_busca = function() {

        // Se o texto nao mudou
        if (this.value == this.valor_antigo) {
            return true;
        }
        if (this.timer_busca) {
            cancelar_timer(this.timer_busca);
        }

        // Limpar o div
        this.limpar_sugestoes();

        // Indicar o valor antigo
        this.valor_antigo = this.value;

        // Ativar apos 1 segundo
        this.timer_busca = ativar_timer("buscar('" + input.id + "')", 1000);
        return true;
    };

    //
    //     Busca uma palavra
    //
    input.buscar = function() {
        var url = CFG.wwwroot + "webservice/busca.xml.php";
        url = adicionar_param(url, "id", this.info.id);
        url = adicionar_param(url, "busca", this.value);
        if (this.value.length > 0) {
            this.div.style.display = "block";
            this.ajax.set_callback([this, "atualizar_itens"]);
            this.ajax.exibir_carregando(this.div);
            this.ajax.consultar("GET", url, true, null);
        } else {
            limpar(this.div);
            this.div.style.display = "none";
        }
        return true;
    };

    //
    //     Atualiza a lista de resultados encontrados
    //
    input.atualizar_itens = function(ajax) {
        var xml = ajax.get_retorno("xml");
        var resultados = xml.documentElement.getElementsByTagName("resultado");
        limpar(this.div);
        if (resultados.length == 0) {
            var msg = document.createTextNode("Resultado(s) Semelhante(s): nenhum");
            this.div.appendChild(msg);
        } else {
            var msg = document.createTextNode("Resultado(s) Semelhante(s): " + resultados.length);
            var select = criar_elemento("select", {"size":"7"});
            select.input = this;
            select.onchange = function() {
                this.input.value = this.value;
                this.input.valor_antigo = this.value;
                return true;
            };
            select.onkeydown = function(e) {
                var k = e ? (e.keyCode ? e.keyCode : e.which) : window.event.keyCode;
                e = e ? e : window.event;
                switch (k) {
                case 9: // TAB
                case 13: // Enter
                    this.input.value = this.value;
                    this.input.valor_antigo = this.value;
                    this.input.limpar_sugestoes();
                    this.input.focus();
                    e.returnValue = false;
                    return false;
                }
                return true;
            };

            var l = resultados.length;
            for (var i = 0; i < l; i++) {
                var valor = resultados.item(i).firstChild.nodeValue;
                var option = criar_elemento("option", {"title":valor, "value":valor}, {}, valor);
                option.ondblclick = function() {
                    this.parentNode.input.limpar_sugestoes();
                    this.parentNode.input.focus();
                    return false;
                };
                select.appendChild(option);
            }
            this.div.appendChild(msg);
            this.div.appendChild(select);
        }
        var a = criar_elemento("a", {"href":"#"}, {"display":"block"}, "Fechar");
        a.input = this;
        a.onclick = function(e) {
            e = e ? e : window.event;
            e.returnValue = false;
            this.input.limpar_sugestoes();
            this.input.focus();
            return false;
        };
        a.onkeypress = a.onclick;
        this.div.appendChild(a);

        return true;
    };


    /// Eventos

    input.onfocus = function() {
        this.info.style.color = "#006600";
    };

    input.onblur = function() {
        this.info.style.color = "#000000";
    };

    input.onkeydown = function(e) {
        var k = e ? (e.keyCode ? e.keyCode : e.which) : window.event.keyCode;
        switch (k) {

        // Tab
        case 9:
            if (this.timer_busca) {
                cancelar_timer(this.timer_busca);
            }
            return true;

         // Esc
         case 27:
            if (this.timer_busca) {
                cancelar_timer(this.timer_busca);
            }
            try {
                e.returnValue = false;
                this.limpar_sugestoes();
                this.focus();
            } catch (e) {
                //void
            }
            return false;

        // Enter
        case 13:
            if (this.timer_busca) {
                cancelar_timer(this.timer_busca);
            }
            try {
                var select = this.div.getElementsByTagName("select").item(0);
                if (select.selectedIndex >= 0) {
                    e.returnValue = false;
                    this.value = select.value;
                    this.valor_antigo = this.value;

                    this.limpar_sugestoes();
                    this.focus();
                } else {
                    e.returnValue = true;
                }

            } catch (e) {
                //void
            }
            return e.returnValue;

        // Seta para cima
        case 38:
            try {
                var select = this.div.getElementsByTagName("select").item(0);
                var i = select.selectedIndex;
                if (i > 0) {
                    select.options[i].selected = false;
                    select.options[i - 1].selected = true;
                } else {
                    select.options[0].selected = true;
                }
            } catch (e) {
                this.ativar_timer_busca();
                return true;
            }
                    return false;

        // Seta para baixo
        case 40:
            try {
                var select = this.div.getElementsByTagName("select").item(0);
                var i = select.selectedIndex;
                if (i >= 0 && i < select.options.length - 1) {
                    select.options[i].selected = false;
                    select.options[i + 1].selected = true;
                } else {
                    select.options[0].selected = true;
                }
            } catch (e) {
                this.ativar_timer_busca();
                return true;
            }
            return false;
        }
        this.ativar_timer_busca();
        return true;
    };
}

//
//     Aciona a busca de um elemento com sugestoes
//
function buscar(id) {
// String id: id do input de busca
//
    var input = document.getElementById(id);
    if (input) {
        input.buscar();
    }
}


//
//     Adiciona um parametro na url
//
function adicionar_param(url, param, valor) {
// String url: url
// String param: novo parametro
// String valor: valor do parametro
//
    var pos_ancora = url.indexOf("#");
    if (pos_ancora >= 0) {
        var ancora = url.substr(pos_ancora);
        url = url.substr(0, pos_ancora);
    } else {
        var ancora = "";
    }

    url += (url.indexOf("?") >= 0 ? "&" : "?") + param + "=" + encodeURIComponent(valor) + ancora;

    return url;
}


//
//     Remove parametros de uma url
//
function remover_params(url, params, delimitador) {
// String url: url a ser verificada
// Array[String] params: parametros a serem removidos
// String delimitador: delimitador de parametros
//
    if (delimitador == undefined) {
        var delimitador = "&";
    }

    var vt_url = url.split("?");
    if (vt_url.length <= 1) {
        return url;
    }
    var pos = vt_url[1].indexOf("#");
    if (pos > 0) {
        var ancora = vt_url[1].substr(pos);
        vt_url[1] = vt_url[1].substr(0, pos);
    } else {
        var ancora = "";
    }

    if (params == undefined || params == true) {
        return vt_url[0];
    }

    var vt_params = vt_url[1].split(delimitador);

    var vt_params2 = new Array();

    var l = vt_params.length;
    for (var i = 0; i < l; i++) {
        var param = vt_params[i];

        var pos = param.indexOf("=");
        if (pos >= 0) {
            var nome = param.substr(0, pos);
        } else {
            var nome = param;
        }

        if (!in_array(nome, params)) {
            vt_params2.push(param);
        }
    }

    if (vt_params2.length > 0) {
        var url_nova = vt_url[0] + "?" + vt_params2.join(delimitador) + ancora;
    } else {
        var url_nova = vt_url[0] + ancora;
    }

    return url_nova;
}


//
//    Obtem a componente fragment da URL
//
function get_fragment(url) {
// String url: URL a ser verificada
//
    pos = url.indexOf("#");
    if (pos >= 0) {
        return url.substr(pos + 1);
    }
    return '';
}


//
//     Checa se um elemento esta no vetor
//
function in_array(v, vet) {
// Mixed v: elemento a ser buscado no vetor
// Array vet: vetor a ser utilizado
//
    var l = vet.length;
    for (var i = 0; i < l; i++) {
        if (v == vet[i]) {
            return true;
        }
    }
    return false;
}


//
//     Checa se o link e' valido ou nao
//
function checar_link(link) {
// A link: link a ser verificado
//
    var url_link = link.getAttribute("href");
    if (url_link.indexOf("http") != 0) {
        url_link = CFG.wwwroot + url_link;
    }
    var url = adicionar_param(CFG.wwwroot + "webservice/checar_link.xml.php", "link", url_link);

    var ajax = new class_ajax();
    if (!ajax.xmlhttp) { return; }
    ajax.set_callback("checar_link_xml");
    ajax.consultar("GET", url, true, null, false);
}


//
//     Atualiza o link
//
function checar_link_xml(ajax) {
// class_ajax ajax: objeto que devolve a requisicao
//
    var xml = ajax.get_retorno("xml");
    if (!xml) {
        return false;
    }
    try {
        var resultado = parseInt(xml.documentElement.firstChild.data);
    } catch (e) {
        try {
            var resultado = parseInt(xml.documentElement.getElementsByTagName("resultado").item(0).firstChild.data);
        } catch (e2) {
            return false;
        }
    }
    switch (resultado) {
    case 0: // Link Valido
    case 1: // Link Indeterminado
        // Faz nada
        break;
    case 2: // Link Invalido
        link.style.color = "#990000";
        link.style.textDecoration = "line-through";

        var small = criar_elemento("small", {}, {}, " (link quebrado)");

        var busca = (link.getAttribute("title") != null) ? link.getAttribute("title") : link.getAttribute("href");
        var novo = criar_elemento("a", {"href":"http://www.google.com.br/search?q=" + encodeURIComponent(busca), "target":"_blank"}, {}, "Buscar no Google");
        small.appendChild(novo);

        if (link.nextSibling) {
            link.parentNode.insertBefore(small, link.nextSibling);
        } else {
            link.parentNode.appendChild(small);
        }
        break;
    }
    return false;
}



//
//     Tira a visibilidade dos elementos internos (recursivamente)
//
function tirar_visibilidade(obj) {
// Object obj: objeto que deseja-se tirar a visibilidade
//
    if (obj.hasChildNodes()) {
        var filhos = obj.getElementsByTagName("*");
        var l = filhos.length;
        for (var i = 0; i < l; i++) {
            var filho = filhos.item(i);
            filho.style.visibility = "hidden";
            tirar_visibilidade(filho);
        }
    }
}


//
//     Clona uma tag
//
function clonar_tag(obj) {
// Object obj: objeto a ser clonado
//
    var item = document.createElement(get_tag(obj), obj.innerHTML);
    for (var j = obj.attributes.length - 1; j >= 0; j--) {
        item.setAttribute(obj.attributes.item(j).name, obj.attributes.item(j).value);
    }
    return item;
}


//
//     Adiciona uma nova funcao no inicio de outra
//
function juntar_funcoes(velho, novo) {
// Function velho: funcao antiga
// Function novo: funcao nova
//
    if (!velho) {
        return novo;
    }
    velho = velho.toString();
    novo  = novo.toString();

    var p1 = velho.indexOf("{") + 1;
    var p2 = velho.lastIndexOf("}");
    velho = velho.substring(p1, p2);

    var p1 = novo.indexOf("{") + 1;
    var p2 = novo.lastIndexOf("}");
    novo = novo.substring(p1, p2);

    var saida = new Function(novo + velho);
    return saida;
}


//
//     Retorna o conteudo texto de URL
//
function get_conteudo(url) {
// String url: endereco do javascript a ser importado
//
    var ajax = new class_ajax();
    if (!ajax.xmlhttp) { return ""; }
    ajax.consultar("GET", url, false, null);
    return ajax.get_retorno("text");
}


//
//     Cria um elemento atraves de um objeto (json)
//
function criar_elemento(tag, atributos, estilos, texto) {
// String tag: nome da tag
// Object atributos: atributos na forma de objeto
// Object estilos: estilos CSS
// String texto: texto a ser incluido internamente
//
    var el = false;

    // Criar elemento
    try {
        var el = document.createElement(tag);

        // Atributos
        if (atributos != undefined) {
            for (var atributo in atributos) {
                var valor = atributos[atributo];
                if (valor == null) {
                    continue;
                }
                switch (atributo) {
                case "id":
                    el.setAttribute(atributo, valor);
                    el.id = valor;
                    break;
                case "class":
                    el.setAttribute(atributo, valor);
                    definir_classe(el, valor);
                    break;
                case "disabled":
                    el.setAttribute(atributo, valor);
                    el.disabled = true;
                    break;
                case "checked":
                    el.setAttribute(atributo, valor);
                    el.checked = true;
                    break;
                case "selected":
                    el.setAttribute(atributo, valor);
                    el.selected = true;
                    break;
                default:
                    el.setAttribute(atributo, valor);
                    break;
                }
            }
        }

        // Estilos
        if (estilos != undefined) {
            for (var estilo in estilos) {
                var valor = estilos[estilo];
                var codigo = "el.style." + estilo + " = '" + valor + "';";
                eval(codigo);
            }
        }

    } catch (e) {
        var conteudo = "<" + tag;
        for (var atributo in atributos) {
            var valor = atributos[atributo];
            conteudo += " " + atributo + '="' + valor + '"';
        }
        var conteudo_css = new Array();
        for (var estilo in estilos) {
            var valor = estilos[estilo];
            conteudo_css.append(estilo + ":" + valor);
        }
        if (conteudo_css.length > 0) {
            conteudo += ' style="' + conteudo_css.join(";") + '"';
        }
        conteudo += ">";
        conteudo += "</" + tag + ">";
        try {
            var el = document.createElement(conteudo);
        } catch (e2) {
            return false;
        }
    }

    // Texto
    if (texto != undefined) {
        var t = document.createTextNode(texto);
        el.appendChild(t);
    }

    return el;
}


//
//     Ativa um timer
//
function ativar_timer(codigo, tempo) {
// String codigo: codigo a ser executado
// Int tempo: tempo em milisegundos
//
    var id = window.setTimeout(codigo, tempo);
    GLB.timers.push(id);
    return id;
}


//
//     Cancela um timer
//
function cancelar_timer(id) {
// Int id: identificador do timer
//
    window.clearTimeout(id);
    for (var i in GLB.timers) {
        if (GLB.timers[i] == id) {
            delete GLB.timers[i];
            return;
        }
    }
}


//
//     Cancela um interval
//
function cancelar_interval(id) {
// Int id: identificador do interval
//
    window.clearInterval(id);
    for (var i in GLB.intervals) {
        if (GLB.intervals[i] == id) {
            delete GLB.intervals[i];
            return;
        }
    }
}


//
//     Ativa um interval
//
function ativar_interval(codigo, tempo) {
// String codigo: codigo a ser executado periodicamente
// Int tempo: tempo em milisegundos da periodicidade
//
    var id = window.setInterval(codigo, tempo);
    GLB.intervals.push(id);
    return id;
}


//
//     Limpa os timers
//
function limpar_timers() {
    for (var i in GLB.timers) {
        if (GLB.timers[i] != null) {
            window.clearTimeout(GLB.timers[i]);
        }
    }
    GLB.timers = new Array();
}


//
//     Limpa os intervals
//
function limpar_intervals() {
    for (var i in GLB.intervals) {
        if (GLB.intervals[i] != null) {
            window.clearInterval(GLB.intervals[i]);
        }
    }
    GLB.intervals = new Array();
}
