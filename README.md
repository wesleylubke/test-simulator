# Test Simulator

Aplicação web em PHP para simulação de provas com importação por CSV.

## Funcionalidades atuais

- Upload real de arquivo CSV
- Validação estruturada do CSV
- Exibição de resumo da prova importada
- Tratamento de erros por linha e campo
- Deploy automatizado em self-hosted runner

## Como rodar localmente

```bash
docker compose up -d --build
```

Acesse:

```text
http://127.0.0.1:8081
```

## Próximos passos

- Persistir prova no Firestore
- Enviar CSV original para Google Cloud Storage
- Listar provas já importadas
- Criar página da prova com resolução e correção
