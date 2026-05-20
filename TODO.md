# TODO - Melhorias e correções

## Passo 1 — Diagnóstico do formato `options` no Firestore
- [ ] Inspecionar como `options` está sendo gravado/esperado (`saveQuestions`, `listQuestions`, `exam.php`)
- [ ] Criar/ajustar um helper/end-point para inspecionar payload real (se necessário)

## Passo 2 — Corrigir consistência do `options`
- [ ] Ajustar `FirestoreRestRepository::toFirestoreValue()` ou `saveQuestions()` para gravar `options` em um shape estável (map A/B/C/D)
- [ ] Ajustar `listQuestions()` para ler o mesmo shape
- [ ] Garantir que `multiple_choice` funciona após importar CSV e após editar (updateQuestion)

## Passo 3 — Defaults de `folder_id`/`folder_name` na criação
- [ ] Atualizar `saveExam()` para persistir folder defaults
- [ ] Verificar agrupamento em `templates/home.php`

## Passo 4 — Segurança (CSRF) (later, após bug principal)
- [ ] Implementar CSRF token por sessão
- [ ] Aplicar em todos os POSTs (upload, delete/update folder, delete exam, salvar tentativa, update exam)

## Passo 5 — Testes manuais
- [ ] Importar `app/sample/valid_exam.csv` e validar UI
- [ ] Fazer tentativa em `exam.php` e validar persistência de `attempts`
- [ ] Editar questões e re-renderizar prova

