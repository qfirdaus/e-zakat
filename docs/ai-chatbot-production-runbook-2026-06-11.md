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
