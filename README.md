# RapidJobs

Plataforma web/PWA para publicação e preenchimento de **vagas diárias em tempo real**,
com foco em eventos e trabalho temporário. Substitui grupos de WhatsApp e planilhas
por um fluxo digital em que empresas publicam, prestadores aceitam com poucos cliques,
e o admin acompanha tudo em um painel único.

**Autor:** [David Wendel](https://github.com/DavidWendel-Dev) · [LinkedIn](https://www.linkedin.com/in/david-wendel-10296b418)

> ⚠️ Projeto proprietário. Veja [LICENSE](LICENSE) antes de qualquer uso.

---

## ✨ Recursos principais

- 🕒 **Vagas em tempo real** — publicação instantânea; prestadores online recebem push
- 📱 **PWA** — instala como app no celular, funciona offline (cache básico)
- 👥 **3 perfis distintos** — Admin, Empresa contratante, Prestador
- ✅ **Fluxo rápido de aceite** — reserva em poucos cliques
- 📊 **Dashboards operacional e financeiro** por perfil
- 🛡️ **Moderação de prestadores** (pendente, aprovado, suspenso, banido)
- ⭐ **Sistema de avaliação** pós-diária
- 🗺️ **Geocoding via Mapbox** (opcional) para busca por proximidade
- 💰 **Cobrança e relatórios** para admin
- 🔒 **CSRF, hash de senha, autorização por role** built-in

---

## 🧰 Stack

| Camada    | Ferramenta                    |
|-----------|-------------------------------|
| Backend   | PHP 8.x puro (sem framework)  |
| Frontend  | HTML + CSS + JS vanilla       |
| DB        | MySQL / MariaDB (PDO)         |
| PWA       | Service Worker + Web App Manifest |
| Servidor  | Apache (via `.htaccess`) ou Nginx |
| Geocoding | Mapbox Geocoding API (opcional) |

Sem Composer, sem Node, sem build. Deploy é copiar pasta e configurar banco.

---

## 📁 Estrutura

```text
rapidjobs/
├── index.php               # landing pública
├── login.php               # login unificado
├── buscar.php              # busca de vagas por localização
├── diaria.php              # página da vaga (aceite)
├── admin/                  # painel administrativo
│   ├── dashboard.php
│   ├── diarias.php         # CRUD de vagas
│   ├── empresas.php        # gestão de empresas parceiras
│   ├── moderacao.php       # aprovação de prestadores
│   └── relatorios.php
├── empresa/                # painel da empresa contratante
├── app/                    # painel/PWA do prestador
├── api/                    # endpoints AJAX (JSON)
├── classes/                # camada OO (Auth, Diaria, Empresa, etc.)
├── config/                 # config.php + database.php
├── database/               # schema.sql + migrações incrementais
├── assets/                 # CSS, JS, imagens
├── uploads/                # arquivos de usuário (gitignored)
├── install-db.php          # instalador do banco (executa 1 vez)
├── install.html            # wizard de instalação inicial
├── migrar.php              # aplica migrations pendentes
├── sw.js                   # Service Worker (PWA)
├── manifest.json           # Web App Manifest
├── offline.html            # página offline
└── 404.php / 403.php / 500.php  # error pages customizadas
```

---

## 🚀 Setup local

### Pré-requisitos
- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10+
- Apache com `mod_rewrite` (ou Laragon/XAMPP)

### Passos

```bash
git clone https://github.com/DavidWendel-Dev/rapidjobs.git
cd rapidjobs

cp .env.example .env
# edite .env: DB_HOST, DB_NAME, DB_USER, DB_PASS, MAPBOX_TOKEN
```

Aponte o Apache/Laragon para a pasta e acesse:

- `http://localhost/rapidjobs/install.html` — wizard inicial
- ou execute direto o script: `php install-db.php`

Login padrão do admin:
- **E-mail:** definido durante o `install-db.php`
- **Senha:** exibida no console após a instalação (**troque imediatamente**)

---

## 🌐 Deploy em produção

1. Suba os arquivos via SCP/FTP para `/var/www/html/rapidjobs`
2. Configure Apache virtual host apontando para a pasta
3. Crie o banco: `mysql -u root -p < database/schema.sql`
4. Rode as migrations: `php migrar.php`
5. Ajuste permissões:
   ```bash
   chmod 755 uploads/
   chown -R www-data:www-data uploads/ config/
   ```
6. **SSL obrigatório** (Certbot/Cloudflare Tunnel/Nginx Proxy Manager)

---

## 🔐 Configuração (.env)

Copie `.env.example` para `.env` e ajuste:

| Variável | Descrição | Exemplo |
|---|---|---|
| `APP_NAME` | Nome exibido no header/PWA | `RapidJobs` |
| `APP_URL` | URL base do site | `https://rapidjobs.seu-dominio.com` |
| `DB_HOST` | Host do MySQL | `localhost` |
| `DB_NAME` | Nome do banco | `rapidjobs_db` |
| `DB_USER` / `DB_PASS` | Credenciais | — |
| `MAPBOX_TOKEN` | Token público do Mapbox (para geocoding) | `pk.eyJ1I...` |
| `SESSION_LIFETIME` | Tempo de sessão em segundos | `86400` |

---

## 📊 Roadmap

- [ ] Notificações push nativas via Web Push API
- [ ] Chat empresa ↔ prestador dentro do app
- [ ] Integração Pix (cobrança automática de mensalidade)
- [ ] Exportação de relatórios em PDF/Excel
- [ ] App nativo (Capacitor) para Android/iOS

---

## 📄 Licença

**Software proprietário.** Todos os direitos reservados a David Wendel.
Uso, cópia, modificação e distribuição são proibidos sem autorização expressa.
Veja o arquivo [LICENSE](LICENSE) para os termos completos.

Se você quer usar/adaptar comercialmente, entre em contato pelo LinkedIn.

---

## 🙌 Autor

**[David Wendel](https://github.com/DavidWendel-Dev)** — Full Stack Developer
[LinkedIn](https://www.linkedin.com/in/david-wendel-10296b418)
