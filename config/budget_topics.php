<?php

/*
 * How the subjects of the State Budget amendments are read for the top of topics. The topics
 * themselves come from the titles on every import; this only teaches the reader the budget's
 * jargon, so it rarely needs a change.
 */
return [

    // A topic has to come up in at least this many amendments of a budget.
    'min_proposals' => 5,

    // A word pair is a topic of its own when it holds at least this share of the amendments of
    // its rarer word ("saúde mental" holds most of "mental"); the rarer word then stops being one.
    'pair_share' => 0.6,

    // Words that say how an amendment changes the budget, not what it is about.
    'stopwords' => [
        'a', 'à', 'ao', 'aos', 'as', 'às', 'até', 'após', 'com', 'como', 'contra', 'da', 'das', 'de', 'do', 'dos', 'e', 'é', 'em', 'entre', 'mais', 'na', 'nas', 'no', 'nos', 'o', 'os', 'ou', 'para', 'pela', 'pelas', 'pelo', 'pelos', 'por', 'que', 'se', 'sem', 'seu', 'seus', 'sobre', 'sua', 'suas', 'um', 'uma', 'umas', 'uns',
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
        'acesso', 'aditamento', 'alargamento', 'alínea', 'alteração', 'anexa', 'anexo', 'apoio', 'aquisição', 'artigo', 'atribuição', 'atualização', 'aumento', 'benefício', 'código', 'conclusão', 'construção', 'criação', 'decreto-lei', 'definição', 'disposição', 'distrito', 'estudo', 'execução', 'extensão', 'extraordinário', 'financiamento', 'garantia', 'gestão', 'implementação', 'instalação', 'investimento', 'lei', 'lista', 'mapa', 'medida', 'modernização', 'nacional', 'norma', 'novo', 'nova', 'número', 'n.º', 'nº', 'orçamental', 'pagamento', 'plano', 'portaria', 'procedimento', 'programa', 'promoção', 'prorrogação', 'reabilitação', 'recuperação', 'reforço', 'regime', 'regulamentação', 'reposição', 'requalificação', 'revisão', 'revogação', 'transferência', 'valorização', 'verba',
        'âmbito', 'caso', 'concelho', 'condição', 'especial', 'específico', 'fim', 'i.p', 'não', 'ppl', 'revogatória', 'sistema', 'todo', 'troço',
    ],

    // Words too broad to be a topic alone that still make one in a pair: "proteção" alone says
    // little, "proteção civil" says what.
    'generic' => [
        'ação', 'acompanhamento', 'agrícola', 'autoridade', 'cria', 'renovação', 'energético', 'estabelecimento', 'guarda', 'oficial', 'recurso', 'relativo', 'segurança', 'sexual', 'verde', 'ambiental', 'aplicação', 'área', 'associação', 'atividade', 'avaliação', 'campanha', 'capacidade', 'comunidade', 'constante', 'controlo', 'cultural', 'desenvolvimento', 'edifício', 'entidade', 'especializado', 'estado', 'financeiro', 'fixação', 'humano', 'imposto', 'integrado', 'internacional', 'intervenção', 'laboral', 'mecanismo', 'pessoal', 'posto', 'produção', 'projeto', 'reabertura', 'redução', 'regularização', 'rio', 'risco', 'salvaguarda', 'setor', 'situação', 'tabela', 'territorial', 'variante', 'vida', 'vila', 'zona', 'carreira', 'centro', 'combate', 'continuidade', 'contratação', 'contrato', 'corpo', 'cuidado', 'direito', 'eliminação', 'equipamento', 'estatuto', 'estratégia', 'estrutura', 'estruturante', 'fiscal', 'função', 'fundo', 'geral', 'incentivo', 'infraestrutura', 'instituto', 'integração', 'isenção', 'ligação', 'linha', 'local', 'meio', 'municipal', 'nacional', 'permanente', 'pessoa', 'portugal', 'portuguesa', 'português', 'prestação', 'prevenção', 'profissional', 'proteção', 'pública', 'público', 'rede', 'regional', 'resposta', 'serviço', 'social', 'subsídio', 'taxa', 'técnico', 'unidade', 'valor',
    ],

    // Names that mean the same thing, as written in the titles (lower case), and the topic they
    // are counted as. The longer names are matched first.
    'synonyms' => [
        'imposto sobre o valor acrescentado' => 'IVA',
        'valor acrescentado' => 'IVA',
        'iva' => 'IVA',
        'imposto sobre o rendimento das pessoas singulares' => 'IRS',
        'rendimento das pessoas singulares' => 'IRS',
        'irs' => 'IRS',
        'imposto sobre o rendimento das pessoas coletivas' => 'IRC',
        'rendimento das pessoas coletivas' => 'IRC',
        'irc' => 'IRC',
        'imposto municipal sobre imóveis' => 'IMI',
        'imi' => 'IMI',
        'imposto municipal sobre as transmissões onerosas de imóveis' => 'IMT',
        'imt' => 'IMT',
        'imposto sobre os produtos petrolíferos' => 'ISP',
        'isp' => 'ISP',
        'imposto do selo' => 'Imposto do selo',
        'serviço nacional de saúde' => 'SNS',
        'sns' => 'SNS',
        'estatuto dos benefícios fiscais' => 'Benefícios fiscais',
        'benefícios fiscais' => 'Benefícios fiscais',
        'ebf' => 'Benefícios fiscais',
        'ministério dos negócios estrangeiros' => 'Negócios Estrangeiros',
        'negócios estrangeiros' => 'Negócios Estrangeiros',
        'polícia de segurança pública' => 'PSP',
        'psp' => 'PSP',
        'guarda nacional republicana' => 'GNR',
        'gnr' => 'GNR',
        'polícia judiciária' => 'Polícia Judiciária',
        'forças e serviços de segurança' => 'Forças de segurança',
        'forças de segurança' => 'Forças de segurança',
        'forças armadas' => 'Forças Armadas',
        'região autónoma da madeira' => 'Madeira',
        'madeira' => 'Madeira',
        'região autónoma dos açores' => 'Açores',
        'açores' => 'Açores',
        'regiões autónomas' => 'Regiões autónomas',
        'segurança social' => 'Segurança Social',
        'administração pública' => 'Administração pública',
        'ensino superior' => 'Ensino superior',
        'saúde mental' => 'Saúde mental',
        'violência doméstica' => 'Violência doméstica',
        'professores' => 'Professores',
        'professor' => 'Professores',
        'docentes' => 'Professores',
        'docente' => 'Professores',
        'ferroviário' => 'Ferrovia',
        'ferroviária' => 'Ferrovia',
        'ferroviárias' => 'Ferrovia',
        'ferroviários' => 'Ferrovia',
        'ferrovia' => 'Ferrovia',
        'comboio' => 'Ferrovia',
        'comboios' => 'Ferrovia',
        'portagem' => 'Portagens',
        'portagens' => 'Portagens',
        'pensão' => 'Pensões',
        'pensões' => 'Pensões',
        'reformados' => 'Pensões',
        'bombeiros' => 'Bombeiros',
        'escola' => 'Escolas',
        'escolas' => 'Escolas',
        'escolar' => 'Escolas',
        'escolares' => 'Escolas',
        'prisional' => 'Prisões',
        'prisionais' => 'Prisões',
        'prisão' => 'Prisões',
        'prisões' => 'Prisões',
        'médico' => 'Médicos',
        'médicos' => 'Médicos',
        'animal' => 'Animais',
        'animais' => 'Animais',
        'habitação' => 'Habitação',
        'habitacional' => 'Habitação',
        'rendas' => 'Habitação',
        'arrendamento' => 'Habitação',
    ],
];
