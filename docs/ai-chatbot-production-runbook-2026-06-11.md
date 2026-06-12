# AI Chatbot Production Runbook

Date: 2026-06-11
Project: IQS-Framework core project
Status: Phase 5 production-readiness guidance

## Purpose

This runbook documents how to operate the IQS-Framework AI Chatbot safely in production and downstream projects.

The chatbot remains read-only. It must not be used to update records, manage users, change permissions, or run arbitrary database queries without a separate approved implementation phase.

## Required Tables

Run this script manually on the main IQS MySQL database before relying on usage persistence or daily quota controls:

```text
docs/ai-chatbot-tables-2026-06-11.sql
```

The current runtime records usage metadata in:

```text
tbl_ai_chat_usage
```

Conversation/session tables are prepared for future use, but raw conversation storage remains disabled by default.

## Production Defaults

Recommended production-safe defaults:

```env
AI_CHATBOT_ENABLED=false
AI_CHATBOT_ACCESS_MODE=super_admin_only
AI_CHATBOT_RATE_LIMIT_PER_MINUTE=10
AI_CHATBOT_USER_DAILY_REQUEST_LIMIT=100
AI_CHATBOT_GLOBAL_DAILY_REQUEST_LIMIT=1000
AI_CHATBOT_PERSIST_USAGE=true
AI_CHATBOT_STORE_CONVERSATIONS=false
AI_CHATBOT_LOG_MESSAGE_CONTENT=false
```

Enable production usage only after confirming provider, access mode, quota, and data-governance approval.

## Provider Setup

### Ollama Local

```env
AI_CHATBOT_PROVIDER=ollama
AI_CHATBOT_MODEL=llama3.2:3b
AI_CHATBOT_BASE_URL=http://127.0.0.1:11434
AI_CHATBOT_API_KEY=
```

Use this for local testing or internal deployments where the server can host the selected model.

### Groq

```env
AI_CHATBOT_PROVIDER=groq
AI_CHATBOT_MODEL=llama-3.1-8b-instant
AI_CHATBOT_BASE_URL=https://api.groq.com/openai/v1
AI_CHATBOT_API_KEY=your_groq_api_key
```

Use this for fast cloud inference testing. Confirm quota and model availability in the provider console.

### OpenRouter

```env
AI_CHATBOT_PROVIDER=openrouter
AI_CHATBOT_MODEL=openrouter/free
AI_CHATBOT_BASE_URL=https://openrouter.ai/api/v1
AI_CHATBOT_API_KEY=your_openrouter_api_key
AI_CHATBOT_APP_URL=https://iqs-framework.dev
AI_CHATBOT_APP_TITLE="IQS-Framework AI Chatbot"
```

Use this for model-routing tests. Confirm the selected free model is available before enabling wider user access.

## Access Modes

Super Admin only:

```env
AI_CHATBOT_ACCESS_MODE=super_admin_only
AI_CHATBOT_ALLOWED_GROUPS=
```

Selected groups:

```env
AI_CHATBOT_ACCESS_MODE=selected_groups
AI_CHATBOT_ALLOWED_GROUPS=ADM-SA,ADM-PE,12
```

All authenticated users:

```env
AI_CHATBOT_ACCESS_MODE=all_authenticated
AI_CHATBOT_ALLOWED_GROUPS=
```

Use `all_authenticated` only after provider cost, daily quota, data governance, and support readiness are approved.

## Quota Controls

Per-minute session throttle:

```env
AI_CHATBOT_RATE_LIMIT_PER_MINUTE=10
```

Per-user daily limit:

```env
AI_CHATBOT_USER_DAILY_REQUEST_LIMIT=100
```

Global daily limit:

```env
AI_CHATBOT_GLOBAL_DAILY_REQUEST_LIMIT=1000
```

Set either daily limit to `0` only when there is a separate provider-side quota or budget control.

## Data Governance

Default policy:

- Do not store raw conversation content.
- Store usage metadata only.
- Keep API keys server-side.
- Do not send passwords, tokens, cookies, CSRF tokens, or API keys to providers.
- Do not allow the model to perform writes or permission changes.
- Do not expose provider exception details to users.

Current default:

```env
AI_CHATBOT_STORE_CONVERSATIONS=false
AI_CHATBOT_LOG_MESSAGE_CONTENT=false
```

Changing either value requires explicit governance approval.

## Role-Aware Response Policy

The chatbot must not answer beyond the current user's allowed access.

Production behavior expectations:

- Normal users should receive help only for pages, menus, and workflows they are allowed to access.
- If a normal user asks about Super Admin settings or restricted configuration, the chatbot should provide a general support response and ask the user to contact the system administrator.
- The chatbot must not expose restricted menu names, internal routes, provider setup, API key handling, permission structure, or administrator-only workflows unless the current user is allowed to access them.
- The chatbot must not provide bypass instructions, role escalation steps, or database query instructions.
- Future knowledge-base retrieval must filter content by user access before the content is sent to any AI provider.

Recommended restricted-topic response:

```text
Tetapan tersebut hanya tersedia kepada pengguna yang diberi akses. Sila hubungi pentadbir sistem jika perubahan diperlukan.
```

## Safe Runtime Context

The chatbot may receive limited runtime context to improve system-focused answers:

- Current language.
- Current role label.
- Active group code or ID.
- Chatbot access mode.
- App title.
- Current page path.
- Current page title.

The runtime context must not include full URL query strings, hash fragments, cookies, tokens, login IDs, staff IDs, raw user profile data, hidden menu lists, role matrices, or unrestricted database records.

This context is advisory only. It must not be treated as permission to reveal hidden routes, administrator workflows, provider setup, API key handling, or internal configuration.

## Read-Only System Context Helper

The chatbot can receive a capped summary of visible modules and menus for the current active group.

Current helper behavior:

- Reads active group access from the server-side application context.
- Loads only configured modules and active menus that the active group can see.
- Sends a limited prompt summary, not raw database rows.
- Does not accept SQL, table names, filters, or query instructions from the AI model.
- Does not expose raw access CSV, role matrices, user identity fields, hidden menus, or unrestricted records.

The AI must use this context only for navigation and general system-help answers. It must not invent menus or routes outside the provided context.

## Curated Knowledge Base

Run this script manually only when the AI Chatbot should use curated FAQ, SOP, or manual text:

```text
docs/ai-chatbot-knowledge-tables-2026-06-12.sql
```

The knowledge table is optional. If it is not present, chatbot runtime continues without knowledge context.

Knowledge retrieval behavior:

- Read-only.
- Keyword-based against title, question, tags, and answer.
- Limited to five items per prompt.
- Filtered by language.
- Filtered by visibility before content is sent to the provider.

Supported visibility values:

```text
all_authenticated
selected_groups
super_admin_only
```

For `selected_groups`, store allowed group IDs or group codes in `f_allowedGroups` as comma-separated values, for example:

```text
1,2,ADM-SA,ADM-PE
```

Do not store secrets, passwords, API keys, internal provider configuration, hidden route details, or unrestricted database exports in the knowledge table.

## Permission-Filtered Retrieval

Every chatbot request should use a permission-filtered retrieval policy.

Runtime behavior:

- System context is filtered by active group module/menu access.
- Knowledge context is filtered by language and visibility before being sent to the AI provider.
- System-specific questions are marked as requiring grounded answers.
- The model is instructed to answer system-specific questions only from approved runtime context, visible system context, or curated knowledge context.
- If approved context is insufficient, the chatbot must say that it does not have enough approved system context yet.

System-specific questions include pages, menus, settings, roles, access, users, providers, models, configuration, and workflows.

The AI provider must never receive unrestricted database records, raw access matrices, hidden menus, or SQL generated by the model.

## Governance Review Loop

Each request stores review-safe classification metadata in `tbl_ai_chat_usage.f_requestMetaJson`.

Current categories:

```text
system_help
navigation_help
access_help
troubleshooting
sensitive_blocked
unknown
```

The metadata is intended to help administrators improve curated knowledge and spot unsafe usage patterns without storing the raw user question.

Review category volume:

```sql
SELECT
  JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_category')) AS question_category,
  JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_risk')) AS question_risk,
  COUNT(*) AS total_requests
FROM tbl_ai_chat_usage
WHERE f_createdDt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY question_category, question_risk
ORDER BY total_requests DESC;
```

Review items that need knowledge-base attention:

```sql
SELECT
  f_createdDt,
  f_loginID,
  f_provider,
  f_model,
  JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_category')) AS question_category,
  JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_review_reason')) AS review_reason,
  JSON_EXTRACT(f_requestMetaJson, '$.knowledge_items_in_prompt') AS knowledge_items_in_prompt
FROM tbl_ai_chat_usage
WHERE JSON_EXTRACT(f_requestMetaJson, '$.question_needs_review') = true
ORDER BY f_createdDt DESC
LIMIT 100;
```

Review sensitive blocked patterns:

```sql
SELECT
  f_createdDt,
  f_loginID,
  JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_review_reason')) AS review_reason,
  JSON_EXTRACT(f_requestMetaJson, '$.blocked_detail') AS blocked_detail
FROM tbl_ai_chat_usage
WHERE JSON_UNQUOTE(JSON_EXTRACT(f_requestMetaJson, '$.question_category')) = 'sensitive_blocked'
ORDER BY f_createdDt DESC
LIMIT 100;
```

## Monitoring

Review usage with:

```sql
SELECT
  DATE(f_createdDt) AS usage_date,
  f_provider,
  f_model,
  f_outcome,
  COUNT(*) AS total_requests,
  SUM(COALESCE(f_totalTokens, 0)) AS total_tokens,
  ROUND(AVG(COALESCE(f_latencyMs, 0))) AS avg_latency_ms
FROM tbl_ai_chat_usage
GROUP BY DATE(f_createdDt), f_provider, f_model, f_outcome
ORDER BY usage_date DESC, total_requests DESC;
```

Review per-user usage:

```sql
SELECT
  f_loginID,
  DATE(f_createdDt) AS usage_date,
  COUNT(*) AS total_requests,
  SUM(COALESCE(f_totalTokens, 0)) AS total_tokens
FROM tbl_ai_chat_usage
GROUP BY f_loginID, DATE(f_createdDt)
ORDER BY usage_date DESC, total_requests DESC;
```

Review provider failures:

```sql
SELECT
  f_createdDt,
  f_loginID,
  f_provider,
  f_model,
  f_outcome,
  f_errorCode,
  f_errorMessage
FROM tbl_ai_chat_usage
WHERE f_outcome <> 'success'
ORDER BY f_createdDt DESC
LIMIT 100;
```

## Rollback

Immediate disable:

```env
AI_CHATBOT_ENABLED=false
```

If provider is down:

```env
AI_CHATBOT_ENABLED=false
```

or switch back to local provider:

```env
AI_CHATBOT_PROVIDER=ollama
AI_CHATBOT_MODEL=llama3.2:3b
AI_CHATBOT_BASE_URL=http://127.0.0.1:11434
AI_CHATBOT_API_KEY=
```

If costs or request volume are too high:

```env
AI_CHATBOT_ACCESS_MODE=super_admin_only
AI_CHATBOT_USER_DAILY_REQUEST_LIMIT=20
AI_CHATBOT_GLOBAL_DAILY_REQUEST_LIMIT=100
```

## Production Checklist

- Required tables have been created.
- `AI_CHATBOT_ENABLED` is intentionally set.
- Provider API key is stored server-side only.
- Access mode is approved.
- Daily quota values are set.
- Raw conversation logging remains disabled.
- Test as Super Admin.
- Test as selected group user.
- Test as unauthorized user.
- Test provider failure or invalid API key.
- Test rate limit behavior.
- Review `tbl_ai_chat_usage` after test.
- Confirm rollback by setting `AI_CHATBOT_ENABLED=false`.
