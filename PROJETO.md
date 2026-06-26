VAMOS CONTRUIR ISSO, MAIS PRIMEIRO COMO ESTAMOS NO WINDOWS QUERO QUE VC USE O SERVIDOR LOCAL LARAGON, QUE JA ESTAR INSTALADO NELE: 📑 DOCUMENTAÇÃO DE ESCOPO: WEBAPP DIÁRIAS (PWA) 
 1. Visão Geral do Sistema 
 O sistema é uma plataforma bilateral de gerenciamento de diárias para eventos operando como um PWA (Progressive Web App) . O objetivo principal é centralizar e automatizar o fluxo de contratação de prestadores de serviço que hoje ocorre de forma caótica em um grupo de WhatsApp com mais de 1000 pessoas. O sistema deve ser focado em performance mobile ( mobile-first ), com transições fluidas e sensação de aplicativo nativo ( clicou, abriu ). 
 2. Pilhas Tecnológicas (A Stack)  
 Ambiente Backend:  PHP (Gerenciamento de regras de negócio, segurança, sessões e APIs REST). 
 Banco de Dados:  MySQL ou MariaDB (Relacional, ideal para consistência de dados financeiros e de candidaturas). 
 Frontend Mobile/Web:  HTML5, CSS3 avançado (com foco em transições e animações de interface) e JavaScript Assíncrono (Vanilla ou micro-frameworks reativos como Alpine.js para manipulação de estado sem refresh  de página). 
 Engine de Mapas:  API Mapbox (Mapbox GL JS para renderização vetorial no cliente e Mapbox Geocoding para conversão de endereços). 
 Tecnologia de Distribuição:  PWA (Service Workers para cache de assets e arquivos manifest para instalação na tela inicial do smartphone, ocultando a barra do navegador). 
 3. Arquitetura de Módulos e Funcionalidades 
 🖥️ Módulo 1: Painel Administrativo (Master/Dona do Negócio) 
 Disponível em Desktop (Web) e Mobile. 
 Autenticação e Segurança:  Login seguro e recuperação de senha. 
 Dashboard Gerencial:  Métricas em tempo real (Total de diárias ativas, taxa de preenchimento de vagas, gráficos de assiduidade/faltas e balanço financeiro do mês). 
 Moderação de Prestadores:  Fila de aprovação de novos cadastros (validação de foto de perfil clara e verificação de maioridade legal através da data de nascimento). Opção de banimento ou suspensão de usuários. 
 Gerenciamento de Empresas:  CRUD completo de empresas parceiras (contratantes) e geração de credenciais de acesso para elas. 
 Painel de Controle de Diárias:  Criação, edição, duplicação e cancelamento de vagas. 
 Módulo de Disparo:  Gerador de link curto e texto formatado da vaga para compartilhamento rápido no grupo do WhatsApp. 
 🏢 Módulo 2: Painel do Cliente (Empresas Contratantes) 
 Disponível em Desktop (Web) e Mobile (Responsivo). 
 Painel de Eventos:  Visualização cronológica das diárias solicitadas por aquela empresa (Próximas, Em Andamento e Finalizadas). 
 Lista de Presença Digital (Check-in):  No dia do evento, a empresa visualiza o card de cada prestador alocado (exibindo obrigatoriamente a foto do perfil para identificação visual). Controles para marcar "Presença Confirmada" ou "Falta". 
 Módulo de Feedback:  Sistema de avaliação compulsória pós-evento, onde a empresa atribui uma nota de 1 a 5 estrelas e um comentário opcional sobre o desempenho do prestador. 
 📱 Módulo 3: Aplicativo do Prestador (Trabalhadores) 
 Interface 100% Mobile-First com navegação por barra inferior fixa (estilo app nativo). 
 Onboarding/Cadastro:  Fluxo de cadastro intuitivo exigindo upload de foto de perfil nítida e validação automática de idade (+18). 
 Mural de Diárias (Lista):  Cards dinâmicos com rolagem infinita exibindo função, valor líquido, horários e indicador visual do tipo de pagamento (na hora ou posterior). 
 Mapa de Oportunidades (Mapbox):  Interface de mapa que captura a localização do usuário (via GPS do aparelho) e plota marcadores ( pins ) nos locais com diárias abertas. Ao tocar no marcador, exibe-se um balão flutuante com informações resumidas e atalho para a vaga. 
 Motor de Reserva Instantânea:  Botão "Garantir Vaga" que faz uma checagem asincrônica no banco de dados. Caso haja vaga, reserva o posto imediatamente e atualiza o contador global. 
 Agenda do Trabalhador:  Aba dedicada dividida entre "Próximos Trabalhos" (com integração ao Google Maps/Waze para rotas) e "Histórico de Ganhos" (com status de recebimento de cada diária). 
 4. Lógica de Engenharia e Fluxo de Dados 
 Geocodificação no Cadastro da Diária:  Quando a administradora insere um endereço textual no painel, o backend dispara uma requisição em segundo plano para a API do Mapbox. O retorno traz as coordenadas geográficas (Latitude e Longitude), que são salvas de forma nativa no banco de dados. 
 Consumo de Dados do Mapa:  O frontend do prestador solicita apenas os dados necessários do mapa (um JSON leve contendo coordenadas, título e ID). A renderização dos elementos visuais e popups é processada diretamente pelo hardware do celular através do Mapbox GL JS, garantindo 60 FPS (quadros por segundo) na navegação do mapa. 
 Concorrência de Vagas:  Para evitar que dois usuários peguem a última vaga ao mesmo tempo, o backend processará as requisições de reserva usando transações de banco de dados isoladas ( Row Locking ), garantindo que o primeiro clique processe a vaga e o segundo receba instantaneamente um aviso de "Vaga preenchida" via JavaScript, alterando a cor do botão na tela sem recarregar a página. 
 5. Requisitos de Experiência do Usuário (UI/UX para Fluidez) 
 Prevenção de Recarregamento:  Toda e qualquer ação de clique (aceitar vaga, confirmar presença, dar nota) deve ser enviada via requisições assíncronas (AJAX/Fetch). O layout muda de estado visual imediatamente para dar resposta ao usuário, enquanto o banco de dados se atualiza em segundo plano. 
 Skeleton Loading:  Durante a transição de telas ou carregamento de listas, o app exibirá silhuetas cinzas animadas simulando o formato dos cards para eliminar a percepção de lentidão na rede. 
 PWA Standalone Mode:  Configuração rigorosa para que, ao ser instalado no iOS ou Android, o sistema remova completamente a barra de navegação superior e inferior do browser, forçando a orientação vertical (Portrait) e habilitando telas de abertura ( Splash Screens ) personalizadas com o logotipo da marca. Essa é uma excelente dor de mercado para resolver. O fluxo atual da sua cliente (grupo de WhatsApp com mais de 1000 pessoas) é um caos de gestão: mensagens se perdem, furações de diárias acontecem e o controle financeiro vira um pesadelo. 
 
 Sei perfeitamente o que é uma diária nesse contexto: é uma prestação de serviço pontual (geralmente de 8h a 12h de trabalho), muito comum em eventos (garçons, seguranças, recepcionistas, montadores), onde o trabalhador recebe o valor fechado pelo dia trabalhado. 
 
 Para criar um app que seja leve, rápido, com cara de aplicativo nativo ("clicou, abriu"), mas usando PHP, CSS e JavaScript, a melhor abordagem técnica é transformar o seu sistema em um PWA (Progressive Web App). 
 
 Aqui está a arquitetura completa e o plano de desenvolvimento de como eu faria esse projeto: 
 
 🛠️ A Arquitetura Tecnológica (Stack) 
 Para garantir a fluidez e velocidade que você quer, a estrutura deve ser dividida assim: 
 
 Backend (PHP): Recomendo fortemente usar um framework como o Laravel (ou Slim Framework se quiser algo ultra minimalista). O Laravel vai acelerar a criação do painel administrativo, relatórios, segurança, upload de fotos e gerenciamento de banco de dados. 
 
 Frontend (JS & CSS): Para a sensação de "App Nativo", você precisa de transições suaves. Você pode usar JavaScript puro (Vanilla) com a API do Fetch/Axios para carregar os dados sem recarregar a página, ou uma biblioteca como Vue.js ou Alpine.js (que casa perfeitamente com PHP). Para o visual de app, frameworks CSS como Tailwind CSS ou Bootstrap (com componentes mobile) resolvem muito bem. 
 
 PWA (Manifest + Service Workers): É isso que vai permitir que o usuário instale o site na tela inicial do celular, oculte a barra do navegador, tenha carregamento instantâneo e funcione até offline em locais com sinal ruim de evento. 
 
 📋 Divisão de Módulos do Sistema 
 O sistema terá 3 níveis de acesso (Roles/Papéis), rodando sob o mesmo banco de dados: 
 
 1. Painel Admin (Sua Cliente) - Web & Mobile 
 Dashboard: Gráficos de faturamento, total de diárias no mês, taxa de presença/falta, quantidade de prestadores ativos. 
 
 Gestão de Cadastros (CRUD): Criar, editar e banir Prestadores e Empresas. 
 
 Aprovação de Perfis: Como o app exige fotos e maioridade, os novos usuários entram como "Pendentes" até a sua cliente validar o documento/foto. 
 
 Financeiro: Relatório de quem já foi pago e quem tem pagamentos pendentes (já que algumas diárias são pagas depois). 
 
 2. Painel Empresa (Os Clientes dela) - Web & Mobile 
 Visualização do Evento: Ver a lista de pessoas que aceitaram a diária (com foto e nome). 
 
 Check-in / Presença: Um botão simples ao lado do nome do trabalhador: [ Confirmar Presença ] ou [ Faltou ]. 
 
 Avaliação: Sistema de 1 a 5 estrelas para o trabalhador após o evento. 
 
 3. Visão do Prestador (Trabalhadores) - Foco 100% Mobile (PWA) 
 Feed de Diárias: Lista de vagas abertas com filtros por função ou valor. 
 
 Detalhes da Vaga: Card limpo com: Valor, Horário, Endereço (com link direto para o Google Maps) e Forma de Pagamento. 
 
 Botão "Quero a Vaga": Ao clicar, o sistema valida se ele está apto e preenche a vaga (com limite de vagas por diária, ex: 5 garçons). 
 
 Minha Agenda: Aba para ele ver as diárias que já aceitou e o histórico do que já trabalhou. 
 
 📈 Fluxo da Experiência do Usuário (UX/UI para Fluidez) 
 Para dar o efeito de "clicou, abriu", siga estas regras no desenvolvimento: 
 
 Single Page Application (SPA) Feelings: Quando o trabalhador clicar em uma diária, não recarregue a página inteira. Use JavaScript para abrir um modal ou deslizar uma tela lateral (drawer) com os detalhes. 
 
 Visual Mobile-First: Desenvolva o app pensando em uma tela de celular de 375px. Botões grandes na parte inferior (fáceis de alcançar com o polegar), menu inferior estilo Instagram/LinkedIn (Home, Minhas Vagas, Perfil). 
 
 Gatilho do WhatsApp: No painel da sua cliente, coloque um botão "Compartilhar no Grupo". Ao clicar, o sistema gera um texto automático para o WhatsApp: "Nova diária de Garçom disponível! Valor: R$ 150. Acesse o app para garantir sua vaga: [link-do-app]". Assim, ela migra o fluxo do grupo para o app aos poucos.