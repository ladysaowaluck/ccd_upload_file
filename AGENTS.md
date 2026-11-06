# Workspace AI Engineering Rules

## Orchestration
For new applications, cross-role features, substantial changes, and release tasks, use the `master-orchestrator` skill. Keep its phase gates, role ownership, artifact handoffs, and approval checks. For a small single-role task, invoke only the relevant specialist skill and keep process proportional to risk.

## Specialist Skills
Use the relevant skills under `.agents/skills/`:
- `project-manager`
- `system-analyst`
- `data-analyst`
- `application-designer`
- `frontend-developer`
- `backend-developer`
- `quality-assurance`
- `devops-engineer`
- `master-orchestrator`
- `reverse-engineer`

Read the relevant `SKILL.md` before substantial specialist work. Do not claim a distinct subagent was launched unless the environment actually launched one.

## Coding Standards
- Inspect existing files and conventions before modifying code.
- Preserve unrelated behavior and avoid broad rewrites without justification.
- Apply SOLID principles where useful; do not overengineer.
- For meaningful modifications, add a concise `Gemini:` comment before the changed section when the file format and project conventions support comments. Also summarize the change.
- When rejecting an existing approach, use `Lady:` in the review or response to explain the concrete concern, its impact, and a better alternative.
- Never rely on comments inside deleted code to explain its removal. Do not insert invalid comments into formats that do not support comments.

## Safety and Evidence
- Never expose credentials, tokens, private user data, or sensitive production records.
- Do not deploy to production, alter live infrastructure, delete resources, or perform irreversible data changes without explicit authorization and applicable approvals.
- Do not bypass required tests, security checks, reviews, or release gates to get a passing result.
- Report tests and commands accurately as passed, failed, blocked, not run, unavailable, or partially passed.
- Never claim an action, file change, approval, test, or deployment succeeded unless it actually did.
- Keep assumptions, unresolved decisions, known defects, risks, and unverified areas visible.

## Token Efficiency & Direct Execution Rules

1. **Minimize Token Usage**:
   - Deliver direct, concise outputs without conversational fluff, introductions, or repetitive summaries.
   - Use plain text, bullet points, and brief code blocks instead of repeating large framework boilerplate.
   - Avoid echoing back user inputs, re-listing entire file contents, or generating redundant explanatory prose.

2. **No Role-Playing or Artificial Tool Claims**:
   - Execute tasks directly within your actual current capabilities.
   - Never claim or pretend to invoke separate external agents, autonomous sub-threads, background processes, or system commands unless they actually ran.
   - Communicate in a grounded, single-turn context: perform the work, present the evidence, and move to the next logical step.

3. **Lean Routing & Essential Invocation**:
   - Invoke specialist roles (e.g., PM, Dev, QA) only when strictly necessary for the prompt's core intent.
   - Skip non-essential steps (e.g., do not generate a full PM delivery plan or formal RAID log for minor bug fixes or small code tweaks).
   - Combine simple multi-role steps into a single unified deliverable rather than generating separate multi-page outputs for each role.

4. **Outcome-Focused Handoffs**:
   - Provide only necessary code, concise architectural specs, or direct file diffs.
   - Omit trivial commentary on obvious code lines.
   - Stop immediately after completing the primary requested task; do not generate unsolicited next-phase templates unless explicitly requested.
