# Plataforma de vagas diarias em tempo real (PWA)

Projeto web/PWA para publicacao e preenchimento de vagas diarias para eventos, com fluxo rapido para quem quer aceitar trabalho sem burocracia.

Nao ha nome comercial definido neste repositorio.

## Objetivo

Centralizar a operacao de diarias em um sistema unico, substituindo processos manuais (ex.: grupos de mensagem), com foco em:

- publicacao de vagas em tempo real
- reserva de vaga com poucos cliques
- controle de presenca e faltas
- avaliacao de prestadores
- visao financeira e operacional para admin/empresa

## Perfis e modulos

### 1) Admin

- Dashboard com metricas operacionais e financeiras
- Moderacao de prestadores (pendente, aprovado, rejeitado, suspenso, banido)
- Gestao de empresas parceiras
- CRUD de diarias (criar, editar, duplicar, cancelar, excluir)
- Relatorios e configuracoes gerais

Arquivos principais: `admin/dashboard.php`, `admin/diarias.php`, `admin/moderacao.php`, `admin/empresas.php`, `admin/relatorios.php`

### 2) Empresa contratante

- Dashboard com proximos eventos
- Publicacao/gestao das proprias diarias
- Check-in de prestadores no dia do evento
- Marcacao de presenca/falta
- Historico, relatorios e pagamentos

Arquivos principais: `empresa/dashboard.php`, `empresa/eventos.php`, `empresa/evento.php`, `empresa/historico.php`, `empresa/pagamentos.php`

### 3) Prestador (mobile-first)

- Mural de vagas ativas
- Botao "garantir vaga" em tempo real
- Agenda de trabalhos
- Check-in por codigo
- Historico de ganhos e perfil

Arquivos principais: `app/index.php`, `app/agenda.php`, `app/checkin.php`, `app/historico.php`, `app/perfil.php`

## Funcionalidades tecnicas implementadas

- Autenticacao por sessao com perfis (`admin`, `empresa`, `prestador`)
- Cadastro de prestador com validacao de maioridade e foto obrigatoria
- Cadastro de empresa
- API para candidaturas (`garantir`, `cancelar`, `checkin`, `checkin_empresa`)
- API de vagas em tempo real (`api/verificar_vagas.php`)
- API de notificacoes (`api/notificacoes.php`)
- Geocodificacao via Mapbox (quando token configurado)
- PWA com `manifest.json` e `sw.js` (modo standalone + cache offline)

## Stack

- PHP 7.4+
- MySQL/MariaDB
- HTML, CSS, JavaScript (vanilla)
- PWA (Service Worker + Web App Manifest)
- Mapbox (opcional)

## Estrutura do projeto

```text
admin/          painel administrativo
app/            app do prestador (PWA)
empresa/        painel da empresa
api/            endpoints de negocio
assets/         css, js, icones, favicon, audio
config/         bootstrap, auth, env, banco
database/       schema e scripts SQL
uploads/        arquivos enviados pelos usuarios (runtime)

app.php         inicializacao e helpers
index.php       landing/acesso inicial
login.php       autenticacao
manifest.json   configuracao PWA
sw.js           service worker
```

## Banco de dados

Script principal: `database/schema.sql`

Tabelas centrais:

- `usuarios`
- `prestadores`
- `empresas`
- `diarias`
- `candidaturas`
- `avaliacoes`
- `pagamentos`
- `notificacoes`
- `configuracoes`
- `logs_auditoria`

Script auxiliar de notificacoes: `database/notificacoes.sql`

Script de cobranca: `database/migracao_cobranca.sql`

## Instalacao local (Laragon)

1. Coloque o projeto em `C:\laragon\www\conect-eventos`
2. Copie `.env.example` para `.env`
3. Ajuste `APP_URL`, `DB_*` e `MAPBOX_TOKEN` no `.env`
4. Crie/importe o banco com:
   - `database/schema.sql`, ou
   - execute `php install-db.php`
5. (Opcional) rode migracoes extras:
   - `php migrar.php`
   - `php migrar_cobranca.php`
6. Acesse pelo host local configurado no Laragon

## Credenciais iniciais (seed)

- Email: `admin@diarias.com`
- Senha: `password`

(Definidas em `database/schema.sql`)

## Endpoints relevantes

- `api/auth.php` (login, logout, cadastro, sessao)
- `api/diarias.php` (listar, salvar, excluir, duplicar, cancelar, compartilhar)
- `api/candidaturas.php` (garantir vaga, cancelar, check-in)
- `api/verificar_vagas.php` (novas vagas em tempo real)
- `api/notificacoes.php` (listar e marcar notificacoes)

## Seguranca e boas praticas para GitHub

- O `.env` ja esta ignorado no `.gitignore`
- Nao publique credenciais reais, tokens e dados sensiveis
- Nao versione uploads reais de usuarios
- Revise configuracoes de `APP_DEBUG` antes de producao

## Publicacao

Para demonstracao rapida:

- manter backend local (Laragon) para testes funcionais completos; ou
- subir em servidor PHP/MySQL (VPS/shared hosting) para ambiente online com banco real.

## Status do repositorio

Repositorio preparado para GitHub com:

- `README.md` completo
- `.gitignore` configurado
- `.env.example` pronto para onboarding
