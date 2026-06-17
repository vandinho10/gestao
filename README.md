# Sistema de Prestação de Contas

Sistema web para gestão e prestação de contas de despesas corporativas (refeição, carro, administração e outros).

## Funcionalidades

- **Lançamento de Notas Fiscais** — Cadastro de despesas por data, tipo e valor
- **Agrupamento em Prestações** — Organização de múltiplas notas em prestações de contas
- **Controle de Status** — Acompanhamento do ciclo: Falta Enviar → Em Conferência → Ag Pagamento → Pago / Rejeitado / Cancelado
- **Soft Delete** — Usuários podem excluir notas pendentes (ficam ocultas, restauráveis pelo admin)
- **Painel Administrador** — Visão geral de todos os usuários, gerenciamento de deletadas e exclusão permanente
- **Autenticação via API externa** — Login integrado com serviço de autenticação JWT
- **Tema Claro/Escuro** — Alternância de tema salva no navegador

## Tecnologias

- **Backend:** PHP 8+ (POO, MVC, PDO)
- **Frontend:** Bootstrap 5.3, JavaScript
- **Banco:** MySQL / MariaDB
- **Autenticação:** API externa com JWT

## Estrutura

```
gestao/
├── app/
│   ├── Controllers/    # Lógica das rotas
│   ├── Core/           # Config, Database, Auth, Router, Security
│   ├── Models/         # Despesa, Prestacao
│   └── Views/          # Templates e parciais
├── index.php           # Entrypoint e rotas
├── .env.example        # Template de variáveis de ambiente
└── README.md
```

## Configuração

As variáveis sensíveis são injetadas via ambiente com prefixo `GESTAO_`:

| Variável | Descrição |
|----------|-----------|
| `GESTAO_DB_PASS` | Senha do banco de dados `gestao_contas` |
| `GESTAO_AUTH_API_URL` | URL base da API de autenticação |
| `GESTAO_COOKIE_DOMAIN` | Domínio para cookie de sessão |