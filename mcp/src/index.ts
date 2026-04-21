import { execFile } from "node:child_process";
import { promisify } from "node:util";
import { access, readFile, readdir } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

const execFileAsync = promisify(execFile);
const currentFilePath = fileURLToPath(import.meta.url);
const currentDirPath = path.dirname(currentFilePath);

const projectRoot = path.resolve(
  process.env.BOOKCABIN_PROJECT_ROOT ?? path.join(currentDirPath, "..", ".."),
);

const docsCatalog = {
  readme: {
    title: "Repository README",
    uri: "project://overview",
    filePath: "README.md",
  },
  architecture: {
    title: "Architecture",
    uri: "project://architecture",
    filePath: "docs/architecture.md",
  },
  terraformRunbook: {
    title: "Terraform Runbook",
    uri: "project://runbook/terraform",
    filePath: "docs/runbooks/terraform-environments.md",
  },
  openCodeChangeLog: {
    title: "OpenCode Change Log",
    uri: "project://changes/opencode-2026-04-17",
    filePath: "docs/changes/2026-04-17-opencode-update.md",
  },
  agentPlaybook: {
    title: "Shared Agent Playbook",
    uri: "project://agent-playbook",
    filePath: "mcp/context/agent-playbook.md",
  },
  knownGaps: {
    title: "Known Gaps Snapshot",
    uri: "project://known-gaps",
    filePath: "mcp/context/known-gaps.md",
  },
  awsMcpChangeLog: {
    title: "AWS MCP Change Log",
    uri: "project://changes/aws-mcp-2026-04-21",
    filePath: "docs/changes/2026-04-21-aws-mcp.md",
  },
} as const;

type DocKey = keyof typeof docsCatalog;

const appHealthUrl = process.env.BOOKCABIN_APP_HEALTH_URL ?? "http://127.0.0.1:8080/api/health";
const agentHealthUrl = process.env.BOOKCABIN_AGENT_HEALTH_URL ?? "http://127.0.0.1:9000/health";
const awsRegion = process.env.AWS_REGION ?? process.env.AWS_DEFAULT_REGION ?? "ap-southeast-1";
const awsProfile = process.env.AWS_PROFILE;
const githubToken = process.env.GITHUB_TOKEN ?? process.env.GH_TOKEN;

const server = new McpServer(
  {
    name: "bookcabin-project-mcp",
    version: "0.1.0",
  },
  {
    instructions:
      "Use this server as shared project context for Bookcabin. Read overview and known gaps first. Treat all tools as read-only and never assume secrets are available.",
  },
);

function resolveProjectPath(relativePath: string): string {
  const fullPath = path.resolve(projectRoot, relativePath);
  if (!fullPath.startsWith(projectRoot)) {
    throw new Error(`Refusing to access path outside project root: ${relativePath}`);
  }

  return fullPath;
}

async function readProjectFile(relativePath: string): Promise<string> {
  return readFile(resolveProjectPath(relativePath), "utf8");
}

async function getWorkflowFiles(): Promise<string[]> {
  const workflowsDir = resolveProjectPath(".github/workflows");

  try {
    const entries = await readdir(workflowsDir, { withFileTypes: true });
    return entries
      .filter((entry) => entry.isFile() && (entry.name.endsWith(".yml") || entry.name.endsWith(".yaml")))
      .map((entry) => path.join(".github", "workflows", entry.name))
      .sort((a, b) => a.localeCompare(b));
  } catch {
    return [];
  }
}

async function pathExists(relativePath: string): Promise<boolean> {
  try {
    await access(resolveProjectPath(relativePath));
    return true;
  } catch {
    return false;
  }
}

async function getTopLevelEntries(): Promise<string[]> {
  const entries = await readdir(projectRoot, { withFileTypes: true });
  return entries
    .filter((entry) => !entry.name.startsWith(".git"))
    .map((entry) => (entry.isDirectory() ? `${entry.name}/` : entry.name))
    .sort((a, b) => a.localeCompare(b));
}

async function getGitStatus(): Promise<string> {
  try {
    const { stdout } = await execFileAsync("git", ["status", "--short"], { cwd: projectRoot });
    return stdout.trim() || "clean";
  } catch (error) {
    return `unavailable: ${String(error)}`;
  }
}

async function getGitBranch(): Promise<string> {
  try {
    const { stdout } = await execFileAsync("git", ["rev-parse", "--abbrev-ref", "HEAD"], {
      cwd: projectRoot,
    });
    return stdout.trim();
  } catch (error) {
    return `unknown (${String(error)})`;
  }
}

async function getGitRemoteOriginUrl(): Promise<string | null> {
  try {
    const { stdout } = await execFileAsync("git", ["remote", "get-url", "origin"], { cwd: projectRoot });
    return stdout.trim() || null;
  } catch {
    return null;
  }
}

function parseGitHubRepoSlug(remoteUrl: string | null) {
  if (!remoteUrl) {
    return null;
  }

  const httpsMatch = remoteUrl.match(/github\.com[/:]([^/]+)\/([^/.]+)(?:\.git)?$/i);
  if (httpsMatch) {
    return {
      owner: httpsMatch[1],
      repo: httpsMatch[2],
      slug: `${httpsMatch[1]}/${httpsMatch[2]}`,
    };
  }

  return null;
}

async function isGhCliAvailable() {
  try {
    await execFileAsync("gh", ["--version"], { cwd: projectRoot });
    return true;
  } catch {
    return false;
  }
}

function makeTextResult(text: string, structuredContent?: Record<string, unknown>) {
  return {
    content: [{ type: "text" as const, text }],
    ...(structuredContent ? { structuredContent } : {}),
  };
}

function parseWorkflowSummary(source: string, filePath: string) {
  const nameMatch = source.match(/^\s*name:\s*(.+)\s*$/m);
  const onMatch = source.match(/^\s*on:\s*(.+)\s*$/m);
  const jobMatches = [...source.matchAll(/^\s{2}([A-Za-z0-9_-]+):\s*$/gm)];

  return {
    filePath,
    name: nameMatch?.[1]?.trim().replace(/^['"]|['"]$/g, "") ?? path.basename(filePath),
    trigger:
      onMatch?.[1]?.trim().replace(/^['"]|['"]$/g, "") ??
      (source.includes("pull_request")
        ? "pull_request"
        : source.includes("push")
          ? "push"
          : source.includes("workflow_dispatch")
            ? "workflow_dispatch"
            : "unknown"),
    jobs: jobMatches.map((match) => match[1]).filter((jobName) => jobName !== "jobs").slice(0, 12),
  };
}

function buildAwsBaseArgs() {
  const args = ["--region", awsRegion];

  if (awsProfile) {
    args.push("--profile", awsProfile);
  }

  return args;
}

async function execAwsJson<T = Record<string, unknown>>(serviceArgs: string[]): Promise<T> {
  const args = [...buildAwsBaseArgs(), ...serviceArgs, "--output", "json"];
  const { stdout } = await execFileAsync("aws", args, { cwd: projectRoot, maxBuffer: 1024 * 1024 * 4 });
  return JSON.parse(stdout) as T;
}

async function execAwsText(serviceArgs: string[]): Promise<string> {
  const args = [...buildAwsBaseArgs(), ...serviceArgs, "--output", "text"];
  const { stdout } = await execFileAsync("aws", args, { cwd: projectRoot, maxBuffer: 1024 * 1024 * 4 });
  return stdout.trim();
}

function makeAwsErrorResult(toolName: string, error: unknown) {
  const message = error instanceof Error ? error.message : String(error);
  return makeTextResult(`${toolName} failed: ${message}`, {
    ok: false,
    tool: toolName,
    region: awsRegion,
    profile: awsProfile ?? "default",
    error: message,
  });
}

function makeGitHubErrorResult(toolName: string, error: unknown) {
  const message = error instanceof Error ? error.message : String(error);
  return makeTextResult(`${toolName} failed: ${message}`, {
    ok: false,
    tool: toolName,
    error: message,
  });
}

function toIsoDate(value: string | Date | undefined) {
  if (!value) {
    return null;
  }

  const date = value instanceof Date ? value : new Date(value);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

function toIsoFromEpochMs(value: number | undefined) {
  if (typeof value !== "number") {
    return null;
  }

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

async function probeHttpEndpoint(url: string, method: "GET" | "HEAD", timeoutSeconds: number) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutSeconds * 1000);
  const startedAt = Date.now();

  try {
    const response = await fetch(url, {
      method,
      redirect: "follow",
      signal: controller.signal,
      headers: {
        Accept: "text/html,application/json,text/plain;q=0.9,*/*;q=0.8",
        "User-Agent": "bookcabin-mcp-probe/0.1",
      },
    });

    const bodyText = method === "HEAD" ? "" : await response.text();
    return {
      ok: response.ok,
      url,
      finalUrl: response.url,
      method,
      status: response.status,
      statusText: response.statusText,
      elapsedMs: Date.now() - startedAt,
      headers: Object.fromEntries(response.headers.entries()),
      bodyPreview: bodyText.slice(0, 1200),
    };
  } finally {
    clearTimeout(timeout);
  }
}

async function fetchGitHubApi<T = Record<string, unknown>>(pathname: string): Promise<T> {
  const response = await fetch(`https://api.github.com${pathname}`, {
    headers: {
      Accept: "application/vnd.github+json",
      "User-Agent": "bookcabin-mcp/0.1",
      ...(githubToken ? { Authorization: `Bearer ${githubToken}` } : {}),
    },
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`GitHub API ${response.status} ${response.statusText}: ${body.slice(0, 280)}`);
  }

  return (await response.json()) as T;
}

function ec2StateRank(stateName: string | undefined) {
  switch (stateName) {
    case "running":
      return 0;
    case "pending":
      return 1;
    case "stopping":
      return 2;
    case "stopped":
      return 3;
    case "shutting-down":
      return 4;
    case "terminated":
      return 5;
    default:
      return 9;
  }
}

function sleep(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

const ssmDiagnosticCommands = {
  uptime: "uptime",
  disk_usage: "df -h",
  memory_usage: "free -m",
  docker_ps: "docker ps --format 'table {{.Names}}\\t{{.Image}}\\t{{.Status}}\\t{{.Ports}}'",
  docker_ps_compact: "docker ps --format '{{.Names}}|{{.Image}}|{{.Status}}'",
  docker_compose_ps: "docker compose ps",
  docker_images: "docker images --format 'table {{.Repository}}\\t{{.Tag}}\\t{{.ID}}\\t{{.CreatedSince}}'",
  journal_docker_tail: "sudo journalctl -u docker --no-pager -n 80",
  systemctl_status_docker: "sudo systemctl status docker --no-pager",
  app_container_logs_tail: "docker logs --tail 120 bookcabin-app",
  agent_container_logs_tail: "docker logs --tail 120 bookcabin-agent",
} as const;

type SsmDiagnosticCommandId = keyof typeof ssmDiagnosticCommands;

const ssmRemediationCommands = {
  restart_app_container: "docker restart bookcabin-app",
  restart_agent_container: "docker restart bookcabin-agent",
  restart_docker_service: "sudo systemctl restart docker",
} as const;

type SsmRemediationCommandId = keyof typeof ssmRemediationCommands;

async function runSsmDiagnosticCommand(instanceId: string, commandId: SsmDiagnosticCommandId, timeoutSeconds: number) {
  const command = ssmDiagnosticCommands[commandId];
  const sendResponse = await execAwsJson<{
    Command?: {
      CommandId?: string;
      Status?: string;
    };
  }>([
    "ssm",
    "send-command",
    "--instance-ids",
    instanceId,
    "--document-name",
    "AWS-RunShellScript",
    "--comment",
    `Bookcabin MCP diagnostic command: ${commandId}`,
    "--parameters",
    `commands=${JSON.stringify([command])}`,
  ]);

  const awsCommandId = sendResponse.Command?.CommandId;
  if (!awsCommandId) {
    return {
      command,
      awsCommandId: null,
      status: sendResponse.Command?.Status ?? "unknown",
      responseCode: null,
      stdout: "",
      stderr: "",
    };
  }

  const startedAt = Date.now();
  let lastStatus = sendResponse.Command?.Status ?? "Pending";
  let invocationResult: {
    Status?: string;
    ResponseCode?: number;
    StandardOutputContent?: string;
    StandardErrorContent?: string;
  } | null = null;

  while (Date.now() - startedAt < timeoutSeconds * 1000) {
    await sleep(3000);

    const response = await execAwsJson<{
      CommandInvocations?: Array<{
        Status?: string;
        ResponseCode?: number;
        CommandPlugins?: Array<{ Output?: string }>;
        StandardOutputContent?: string;
        StandardErrorContent?: string;
      }>;
    }>(["ssm", "list-command-invocations", "--command-id", awsCommandId, "--details"]);

    const invocation = response.CommandInvocations?.[0];
    lastStatus = invocation?.Status ?? lastStatus;

    if (invocation) {
      invocationResult = {
        Status: invocation.Status,
        ResponseCode: invocation.ResponseCode,
        StandardOutputContent:
          invocation.StandardOutputContent ?? invocation.CommandPlugins?.map((plugin) => plugin.Output ?? "").join("\n"),
        StandardErrorContent: invocation.StandardErrorContent ?? "",
      };
    }

    if (["Success", "Cancelled", "TimedOut", "Failed", "Cancelling"].includes(lastStatus)) {
      break;
    }
  }

  return {
    command,
    awsCommandId,
    status: invocationResult?.Status ?? lastStatus,
    responseCode: invocationResult?.ResponseCode ?? null,
    stdout: invocationResult?.StandardOutputContent?.trim() || "",
    stderr: invocationResult?.StandardErrorContent?.trim() || "",
  };
}

async function runSsmRemediationCommand(
  instanceId: string,
  actionId: SsmRemediationCommandId,
  timeoutSeconds: number,
) {
  const command = ssmRemediationCommands[actionId];
  const sendResponse = await execAwsJson<{
    Command?: {
      CommandId?: string;
      Status?: string;
    };
  }>([
    "ssm",
    "send-command",
    "--instance-ids",
    instanceId,
    "--document-name",
    "AWS-RunShellScript",
    "--comment",
    `Bookcabin MCP remediation action: ${actionId}`,
    "--parameters",
    `commands=${JSON.stringify([command])}`,
  ]);

  const awsCommandId = sendResponse.Command?.CommandId;
  if (!awsCommandId) {
    return {
      command,
      awsCommandId: null,
      status: sendResponse.Command?.Status ?? "unknown",
      responseCode: null,
      stdout: "",
      stderr: "",
    };
  }

  const startedAt = Date.now();
  let lastStatus = sendResponse.Command?.Status ?? "Pending";
  let invocationResult: {
    Status?: string;
    ResponseCode?: number;
    StandardOutputContent?: string;
    StandardErrorContent?: string;
  } | null = null;

  while (Date.now() - startedAt < timeoutSeconds * 1000) {
    await sleep(3000);

    const response = await execAwsJson<{
      CommandInvocations?: Array<{
        Status?: string;
        ResponseCode?: number;
        CommandPlugins?: Array<{ Output?: string }>;
        StandardOutputContent?: string;
        StandardErrorContent?: string;
      }>;
    }>(["ssm", "list-command-invocations", "--command-id", awsCommandId, "--details"]);

    const invocation = response.CommandInvocations?.[0];
    lastStatus = invocation?.Status ?? lastStatus;

    if (invocation) {
      invocationResult = {
        Status: invocation.Status,
        ResponseCode: invocation.ResponseCode,
        StandardOutputContent:
          invocation.StandardOutputContent ?? invocation.CommandPlugins?.map((plugin) => plugin.Output ?? "").join("\n"),
        StandardErrorContent: invocation.StandardErrorContent ?? "",
      };
    }

    if (["Success", "Cancelled", "TimedOut", "Failed", "Cancelling"].includes(lastStatus)) {
      break;
    }
  }

  return {
    command,
    awsCommandId,
    status: invocationResult?.Status ?? lastStatus,
    responseCode: invocationResult?.ResponseCode ?? null,
    stdout: invocationResult?.StandardOutputContent?.trim() || "",
    stderr: invocationResult?.StandardErrorContent?.trim() || "",
  };
}

async function buildProjectOverview() {
  const branch = await getGitBranch();
  const gitStatus = await getGitStatus();
  const topLevel = await getTopLevelEntries();

  const summary = [
    "Bookcabin project overview",
    `- Root: ${projectRoot}`,
    `- Branch: ${branch}`,
    `- Top-level entries: ${topLevel.join(", ")}`,
    "- App stack: Laravel 13 + Vite frontend",
    "- Agent stack: Go SQS consumer for OTA events",
    "- Infra stack: Terraform for AWS networking, EC2, RDS, ALB, CDN, SQS, and monitoring",
    `- Git status: ${gitStatus}`,
  ].join("\n");

  return {
    branch,
    gitStatus,
    topLevel,
    summary,
  };
}

function parseRouteFile(source: string, routeFile: string) {
  const matches = [...source.matchAll(/Route::(get|post|put|patch|delete)\(\s*['"]([^'"]+)['"]/g)];
  return matches.map((match) => ({
    method: match[1].toUpperCase(),
    path: match[2],
    file: routeFile,
  }));
}

async function checkUrl(url: string) {
  try {
    const response = await fetch(url, {
      method: "GET",
      headers: { Accept: "application/json, text/plain;q=0.9, */*;q=0.8" },
    });

    const text = await response.text();

    return {
      ok: response.ok,
      status: response.status,
      bodyPreview: text.slice(0, 280),
    };
  } catch (error) {
    return {
      ok: false,
      status: 0,
      bodyPreview: `Request failed: ${error instanceof Error ? error.message : String(error)}`,
    };
  }
}

for (const [name, entry] of Object.entries(docsCatalog)) {
  server.resource(name, entry.uri, async (uri) => ({
    contents: [
      {
        uri: uri.href,
        mimeType: "text/markdown",
        text: await readProjectFile(entry.filePath),
      },
    ],
  }));
}

server.tool("project_overview", {}, async () => {
  const overview = await buildProjectOverview();
  return makeTextResult(overview.summary, overview);
});

server.tool(
  "repo_status",
  {
    include_top_level_entries: z.boolean().default(true),
  },
  async ({ include_top_level_entries }) => {
    const branch = await getGitBranch();
    const gitStatus = await getGitStatus();
    const topLevel = include_top_level_entries ? await getTopLevelEntries() : [];

    const lines = [
      `Branch: ${branch}`,
      `Git status: ${gitStatus}`,
    ];

    if (include_top_level_entries) {
      lines.push(`Top-level entries: ${topLevel.join(", ")}`);
    }

    return makeTextResult(lines.join("\n"), {
      branch,
      gitStatus,
      topLevel,
    });
  },
);

server.tool(
  "search_docs",
  {
    query: z.string().min(2),
    max_results: z.number().int().min(1).max(20).default(8),
  },
  async ({ query, max_results }) => {
    const normalizedQuery = query.toLowerCase();
    const hits: Array<Record<string, unknown>> = [];

    for (const [key, entry] of Object.entries(docsCatalog)) {
      const text = await readProjectFile(entry.filePath);
      const lines = text.split(/\r?\n/);

      lines.forEach((line, index) => {
        if (line.toLowerCase().includes(normalizedQuery) && hits.length < max_results) {
          hits.push({
            doc: key,
            title: entry.title,
            line: index + 1,
            snippet: line.trim(),
            uri: entry.uri,
          });
        }
      });

      if (hits.length >= max_results) {
        break;
      }
    }

    if (hits.length === 0) {
      return makeTextResult(`No matches found for "${query}" in curated project docs.`, {
        query,
        hits,
      });
    }

    const text = hits
      .map(
        (hit) =>
          `- ${String(hit.title)} [line ${String(hit.line)}]: ${String(hit.snippet)} (${String(hit.uri)})`,
      )
      .join("\n");

    return makeTextResult(text, { query, hits });
  },
);

server.tool("list_http_endpoints", {}, async () => {
  const apiSource = await readProjectFile("app/routes/api.php");
  const webSource = await readProjectFile("app/routes/web.php");
  const routes = [
    ...parseRouteFile(apiSource, "app/routes/api.php"),
    ...parseRouteFile(webSource, "app/routes/web.php"),
  ];

  const text = routes.map((route) => `- ${route.method} ${route.path} (${route.file})`).join("\n");
  return makeTextResult(text || "No routes found.", { routes });
});

server.tool("health_check_services", {}, async () => {
  const [appResult, agentResult] = await Promise.all([checkUrl(appHealthUrl), checkUrl(agentHealthUrl)]);

  const result = {
    app: {
      url: appHealthUrl,
      ...appResult,
    },
    agent: {
      url: agentHealthUrl,
      ...agentResult,
    },
  };

  const text = [
    `App health: ${result.app.ok ? "OK" : "FAILED"} (${result.app.status}) ${result.app.url}`,
    `Agent health: ${result.agent.ok ? "OK" : "FAILED"} (${result.agent.status}) ${result.agent.url}`,
  ].join("\n");

  return makeTextResult(text, result as Record<string, unknown>);
});

server.tool(
  "get_context_document",
  {
    document: z.enum(Object.keys(docsCatalog) as [DocKey, ...DocKey[]]),
  },
  async ({ document }) => {
    const entry = docsCatalog[document];
    const text = await readProjectFile(entry.filePath);

    return makeTextResult(text, {
      document,
      title: entry.title,
      uri: entry.uri,
      filePath: entry.filePath,
    });
  },
);

server.tool("aws_environment_status", {}, async () => {
  try {
    const [callerIdentity, ssmInfo, awsVersion] = await Promise.all([
      execAwsJson<{
        Account?: string;
        Arn?: string;
        UserId?: string;
      }>(["sts", "get-caller-identity"]),
      execAwsJson<{
        InstanceInformationList?: Array<{
          InstanceId?: string;
          PingStatus?: string;
          LastPingDateTime?: string;
          PlatformName?: string;
          PlatformVersion?: string;
          AgentVersion?: string;
        }>;
      }>(["ssm", "describe-instance-information", "--max-results", "10"]),
      execAwsText(["--version"]),
    ]);

    const managedInstances =
      ssmInfo.InstanceInformationList?.map((instance) => ({
        instanceId: instance.InstanceId ?? "unknown",
        pingStatus: instance.PingStatus ?? "unknown",
        platform: [instance.PlatformName, instance.PlatformVersion].filter(Boolean).join(" "),
        agentVersion: instance.AgentVersion ?? "unknown",
        lastPingAt: toIsoDate(instance.LastPingDateTime),
      })) ?? [];

    const result = {
      ok: true,
      region: awsRegion,
      profile: awsProfile ?? "default",
      awsVersion,
      callerIdentity: {
        account: callerIdentity.Account ?? "unknown",
        arn: callerIdentity.Arn ?? "unknown",
        userId: callerIdentity.UserId ?? "unknown",
      },
      managedInstances,
    };

    const text = [
      `AWS environment ready in region ${result.region} using profile ${result.profile}.`,
      `Caller ARN: ${result.callerIdentity.arn}`,
      `Account: ${result.callerIdentity.account}`,
      `SSM managed instances: ${managedInstances.length}`,
      ...(managedInstances.length > 0
        ? managedInstances.map(
            (instance) =>
              `- ${instance.instanceId}: ${instance.pingStatus} (${instance.platform || "platform unknown"})`,
          )
        : ["- No SSM managed instances returned by AWS CLI."]),
    ].join("\n");

    return makeTextResult(text, result as Record<string, unknown>);
  } catch (error) {
    return makeAwsErrorResult("aws_environment_status", error);
  }
});

server.tool(
  "aws_list_ec2_instances",
  {
    include_stopped: z.boolean().default(false),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ include_stopped, max_results }) => {
    try {
      const response = await execAwsJson<{
        Reservations?: Array<{
          Instances?: Array<{
            InstanceId?: string;
            InstanceType?: string;
            State?: { Name?: string };
            PrivateIpAddress?: string;
            PublicIpAddress?: string;
            LaunchTime?: string;
            Tags?: Array<{ Key?: string; Value?: string }>;
          }>;
        }>;
      }>(["ec2", "describe-instances"]);

      const instances =
        response.Reservations?.flatMap((reservation) => reservation.Instances ?? []).map((instance) => {
          const nameTag = instance.Tags?.find((tag) => tag.Key === "Name")?.Value ?? null;
          return {
            instanceId: instance.InstanceId ?? "unknown",
            name: nameTag,
            state: instance.State?.Name ?? "unknown",
            instanceType: instance.InstanceType ?? "unknown",
            privateIp: instance.PrivateIpAddress ?? null,
            publicIp: instance.PublicIpAddress ?? null,
            launchTime: toIsoDate(instance.LaunchTime),
          };
        }) ?? [];

      const filtered = instances
        .filter((instance) => include_stopped || instance.state === "running")
        .sort((a, b) => ec2StateRank(a.state) - ec2StateRank(b.state) || a.instanceId.localeCompare(b.instanceId))
        .slice(0, max_results);

      const text =
        filtered.length > 0
          ? filtered
              .map(
                (instance) =>
                  `- ${instance.instanceId} (${instance.state}) ${instance.instanceType} ${instance.name ? `[${instance.name}] ` : ""}private=${instance.privateIp ?? "-"} public=${instance.publicIp ?? "-"}`,
              )
              .join("\n")
          : "No EC2 instances matched the requested filter.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        includeStopped: include_stopped,
        instances: filtered,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_ec2_instances", error);
    }
  },
);

server.tool(
  "aws_list_ssm_managed_instances",
  {
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ max_results }) => {
    try {
      const response = await execAwsJson<{
        InstanceInformationList?: Array<{
          InstanceId?: string;
          ComputerName?: string;
          PingStatus?: string;
          PlatformName?: string;
          PlatformVersion?: string;
          ResourceType?: string;
          LastPingDateTime?: string;
        }>;
      }>(["ssm", "describe-instance-information", "--max-results", String(max_results)]);

      const instances =
        response.InstanceInformationList?.map((instance) => ({
          instanceId: instance.InstanceId ?? "unknown",
          computerName: instance.ComputerName ?? null,
          pingStatus: instance.PingStatus ?? "unknown",
          platform: [instance.PlatformName, instance.PlatformVersion].filter(Boolean).join(" "),
          resourceType: instance.ResourceType ?? "unknown",
          lastPingAt: toIsoDate(instance.LastPingDateTime),
        })) ?? [];

      const text =
        instances.length > 0
          ? instances
              .map(
                (instance) =>
                  `- ${instance.instanceId}: ${instance.pingStatus} (${instance.platform || "platform unknown"})`,
              )
              .join("\n")
          : "No SSM managed instances found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        instances,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_ssm_managed_instances", error);
    }
  },
);

server.tool(
  "aws_list_ecr_repositories",
  {
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ max_results }) => {
    try {
      const response = await execAwsJson<{
        repositories?: Array<{
          repositoryName?: string;
          repositoryUri?: string;
          imageTagMutability?: string;
          createdAt?: string;
        }>;
      }>(["ecr", "describe-repositories", "--max-items", String(max_results)]);

      const repositories =
        response.repositories?.map((repository) => ({
          name: repository.repositoryName ?? "unknown",
          uri: repository.repositoryUri ?? "unknown",
          imageTagMutability: repository.imageTagMutability ?? "unknown",
          createdAt: toIsoDate(repository.createdAt),
        })) ?? [];

      const text =
        repositories.length > 0
          ? repositories.map((repository) => `- ${repository.name}: ${repository.uri}`).join("\n")
          : "No ECR repositories found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        repositories,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_ecr_repositories", error);
    }
  },
);

server.tool(
  "aws_list_load_balancers",
  {
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ max_results }) => {
    try {
      const response = await execAwsJson<{
        LoadBalancers?: Array<{
          LoadBalancerArn?: string;
          LoadBalancerName?: string;
          DNSName?: string;
          Type?: string;
          Scheme?: string;
          State?: { Code?: string };
        }>;
      }>(["elbv2", "describe-load-balancers", "--page-size", String(Math.min(max_results, 20))]);

      const loadBalancers =
        response.LoadBalancers?.slice(0, max_results).map((loadBalancer) => ({
          arn: loadBalancer.LoadBalancerArn ?? "unknown",
          name: loadBalancer.LoadBalancerName ?? "unknown",
          dnsName: loadBalancer.DNSName ?? "unknown",
          type: loadBalancer.Type ?? "unknown",
          scheme: loadBalancer.Scheme ?? "unknown",
          state: loadBalancer.State?.Code ?? "unknown",
        })) ?? [];

      const text =
        loadBalancers.length > 0
          ? loadBalancers
              .map(
                (loadBalancer) =>
                  `- ${loadBalancer.name} (${loadBalancer.type}/${loadBalancer.scheme}) state=${loadBalancer.state} dns=${loadBalancer.dnsName}`,
              )
              .join("\n")
          : "No load balancers found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        loadBalancers,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_load_balancers", error);
    }
  },
);

server.tool(
  "aws_list_cloudwatch_log_groups",
  {
    prefix: z.string().optional(),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ prefix, max_results }) => {
    try {
      const args = ["logs", "describe-log-groups", "--limit", String(max_results)];
      if (prefix) {
        args.push("--log-group-name-prefix", prefix);
      }

      const response = await execAwsJson<{
        logGroups?: Array<{
          logGroupName?: string;
          storedBytes?: number;
          retentionInDays?: number;
          creationTime?: number;
        }>;
      }>(args);

      const logGroups =
        response.logGroups?.map((group) => ({
          name: group.logGroupName ?? "unknown",
          storedBytes: group.storedBytes ?? 0,
          retentionInDays: group.retentionInDays ?? null,
          createdAt: group.creationTime ? new Date(group.creationTime).toISOString() : null,
        })) ?? [];

      const text =
        logGroups.length > 0
          ? logGroups
              .map(
                (group) =>
                  `- ${group.name} retention=${group.retentionInDays ?? "never-expire"} storedBytes=${group.storedBytes}`,
              )
              .join("\n")
          : "No CloudWatch log groups found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        prefix: prefix ?? null,
        logGroups,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_cloudwatch_log_groups", error);
    }
  },
);

server.tool(
  "aws_list_cloudwatch_log_streams",
  {
    log_group_name: z.string().min(1),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ log_group_name, max_results }) => {
    try {
      const response = await execAwsJson<{
        logStreams?: Array<{
          logStreamName?: string;
          creationTime?: number;
          lastIngestionTime?: number;
          lastEventTimestamp?: number;
          storedBytes?: number;
        }>;
      }>([
        "logs",
        "describe-log-streams",
        "--log-group-name",
        log_group_name,
        "--order-by",
        "LastEventTime",
        "--descending",
        "--limit",
        String(max_results),
      ]);

      const logStreams =
        response.logStreams?.map((stream) => ({
          name: stream.logStreamName ?? "unknown",
          createdAt: toIsoFromEpochMs(stream.creationTime),
          lastIngestionAt: toIsoFromEpochMs(stream.lastIngestionTime),
          lastEventAt: toIsoFromEpochMs(stream.lastEventTimestamp),
          storedBytes: stream.storedBytes ?? 0,
        })) ?? [];

      const text =
        logStreams.length > 0
          ? logStreams
              .map(
                (stream) =>
                  `- ${stream.name} lastEvent=${stream.lastEventAt ?? "-"} storedBytes=${stream.storedBytes}`,
              )
              .join("\n")
          : `No log streams found for group "${log_group_name}".`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        logGroupName: log_group_name,
        logStreams,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_cloudwatch_log_streams", error);
    }
  },
);

server.tool(
  "aws_tail_cloudwatch_logs",
  {
    log_group_name: z.string().min(1),
    log_stream_name: z.string().optional(),
    minutes: z.number().int().min(1).max(1440).default(30),
    limit: z.number().int().min(1).max(200).default(50),
    filter_pattern: z.string().optional(),
  },
  async ({ log_group_name, log_stream_name, minutes, limit, filter_pattern }) => {
    try {
      const startTime = Date.now() - minutes * 60 * 1000;
      const args = [
        "logs",
        "filter-log-events",
        "--log-group-name",
        log_group_name,
        "--start-time",
        String(startTime),
        "--limit",
        String(limit),
      ];

      if (log_stream_name) {
        args.push("--log-stream-names", log_stream_name);
      }

      if (filter_pattern) {
        args.push("--filter-pattern", filter_pattern);
      }

      const response = await execAwsJson<{
        events?: Array<{
          timestamp?: number;
          logStreamName?: string;
          message?: string;
        }>;
      }>(args);

      const events =
        response.events?.map((event) => ({
          timestamp: toIsoFromEpochMs(event.timestamp),
          logStreamName: event.logStreamName ?? "unknown",
          message: (event.message ?? "").trim(),
        })) ?? [];

      const text =
        events.length > 0
          ? events
              .map(
                (event) =>
                  `[${event.timestamp ?? "-"}] ${event.logStreamName}: ${event.message || "<empty message>"}`,
              )
              .join("\n")
          : `No log events found for group "${log_group_name}" in the last ${minutes} minutes.`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        logGroupName: log_group_name,
        logStreamName: log_stream_name ?? null,
        filterPattern: filter_pattern ?? null,
        minutes,
        limit,
        events,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_tail_cloudwatch_logs", error);
    }
  },
);

server.tool(
  "aws_list_ssm_parameters",
  {
    path_prefix: z.string().min(1).default("/"),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ path_prefix, max_results }) => {
    try {
      const response = await execAwsJson<{
        Parameters?: Array<{
          Name?: string;
          Type?: string;
          Tier?: string;
          LastModifiedDate?: string;
          ARN?: string;
        }>;
      }>([
        "ssm",
        "describe-parameters",
        "--parameter-filters",
        `Key=Name,Option=BeginsWith,Values=${path_prefix}`,
        "--max-results",
        String(max_results),
      ]);

      const parameters =
        response.Parameters?.map((parameter) => ({
          name: parameter.Name ?? "unknown",
          type: parameter.Type ?? "unknown",
          tier: parameter.Tier ?? "unknown",
          arn: parameter.ARN ?? null,
          lastModifiedAt: toIsoDate(parameter.LastModifiedDate),
        })) ?? [];

      const text =
        parameters.length > 0
          ? parameters.map((parameter) => `- ${parameter.name} (${parameter.type}/${parameter.tier})`).join("\n")
          : `No SSM parameters found under prefix "${path_prefix}".`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        pathPrefix: path_prefix,
        parameters,
        note: "This tool intentionally returns metadata only and never reads parameter values.",
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_ssm_parameters", error);
    }
  },
);

server.tool(
  "aws_list_cloudfront_distributions",
  {
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ max_results }) => {
    try {
      const response = await execAwsJson<{
        DistributionList?: {
          Items?: Array<{
            Id?: string;
            ARN?: string;
            Status?: string;
            DomainName?: string;
            Comment?: string;
            Enabled?: boolean;
            LastModifiedTime?: string;
            Aliases?: { Items?: string[] };
            Origins?: { Items?: Array<{ DomainName?: string; Id?: string }> };
          }>;
        };
      }>(["cloudfront", "list-distributions"]);

      const distributions =
        response.DistributionList?.Items?.slice(0, max_results).map((distribution) => ({
          id: distribution.Id ?? "unknown",
          arn: distribution.ARN ?? "unknown",
          status: distribution.Status ?? "unknown",
          domainName: distribution.DomainName ?? "unknown",
          comment: distribution.Comment ?? "",
          enabled: distribution.Enabled ?? false,
          aliases: distribution.Aliases?.Items ?? [],
          origins:
            distribution.Origins?.Items?.map((origin) => ({
              id: origin.Id ?? "unknown",
              domainName: origin.DomainName ?? "unknown",
            })) ?? [],
          lastModifiedAt: toIsoDate(distribution.LastModifiedTime),
        })) ?? [];

      const text =
        distributions.length > 0
          ? distributions
              .map(
                (distribution) =>
                  `- ${distribution.id} (${distribution.status}) enabled=${distribution.enabled} domain=${distribution.domainName}`,
              )
              .join("\n")
          : "No CloudFront distributions found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        distributions,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_cloudfront_distributions", error);
    }
  },
);

server.tool(
  "aws_list_rds_instances",
  {
    include_stopped: z.boolean().default(true),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ include_stopped, max_results }) => {
    try {
      const response = await execAwsJson<{
        DBInstances?: Array<{
          DBInstanceIdentifier?: string;
          DBInstanceArn?: string;
          Engine?: string;
          EngineVersion?: string;
          DBInstanceClass?: string;
          DBInstanceStatus?: string;
          Endpoint?: { Address?: string; Port?: number };
          DBName?: string;
          MultiAZ?: boolean;
          PubliclyAccessible?: boolean;
          StorageEncrypted?: boolean;
          InstanceCreateTime?: string;
          TagList?: Array<{ Key?: string; Value?: string }>;
        }>;
      }>(["rds", "describe-db-instances"]);

      const instances =
        response.DBInstances?.map((instance) => ({
          identifier: instance.DBInstanceIdentifier ?? "unknown",
          arn: instance.DBInstanceArn ?? "unknown",
          engine: instance.Engine ?? "unknown",
          engineVersion: instance.EngineVersion ?? "unknown",
          instanceClass: instance.DBInstanceClass ?? "unknown",
          status: instance.DBInstanceStatus ?? "unknown",
          endpoint: instance.Endpoint?.Address ?? null,
          port: instance.Endpoint?.Port ?? null,
          dbName: instance.DBName ?? null,
          multiAz: instance.MultiAZ ?? false,
          publiclyAccessible: instance.PubliclyAccessible ?? false,
          storageEncrypted: instance.StorageEncrypted ?? false,
          createdAt: toIsoDate(instance.InstanceCreateTime),
          tags: instance.TagList ?? [],
        }))
          .filter((instance) => include_stopped || instance.status !== "stopped")
          .slice(0, max_results) ?? [];

      const text =
        instances.length > 0
          ? instances
              .map(
                (instance) =>
                  `- ${instance.identifier} (${instance.status}) ${instance.engine}@${instance.engineVersion} endpoint=${instance.endpoint ?? "-"}`,
              )
              .join("\n")
          : "No RDS instances matched the requested filter.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        includeStopped: include_stopped,
        instances,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_rds_instances", error);
    }
  },
);

server.tool(
  "aws_list_target_groups",
  {
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ max_results }) => {
    try {
      const response = await execAwsJson<{
        TargetGroups?: Array<{
          TargetGroupArn?: string;
          TargetGroupName?: string;
          Protocol?: string;
          Port?: number;
          TargetType?: string;
          HealthCheckPath?: string;
          Matcher?: { HttpCode?: string };
          LoadBalancerArns?: string[];
        }>;
      }>(["elbv2", "describe-target-groups"]);

      const targetGroups =
        response.TargetGroups?.slice(0, max_results).map((targetGroup) => ({
          arn: targetGroup.TargetGroupArn ?? "unknown",
          name: targetGroup.TargetGroupName ?? "unknown",
          protocol: targetGroup.Protocol ?? "unknown",
          port: targetGroup.Port ?? null,
          targetType: targetGroup.TargetType ?? "unknown",
          healthCheckPath: targetGroup.HealthCheckPath ?? null,
          matcher: targetGroup.Matcher?.HttpCode ?? null,
          loadBalancerArns: targetGroup.LoadBalancerArns ?? [],
        })) ?? [];

      const text =
        targetGroups.length > 0
          ? targetGroups
              .map(
                (targetGroup) =>
                  `- ${targetGroup.name} ${targetGroup.protocol}:${targetGroup.port ?? "-"} health=${targetGroup.healthCheckPath ?? "-"} matcher=${targetGroup.matcher ?? "-"}`,
              )
              .join("\n")
          : "No target groups found.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        targetGroups,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_target_groups", error);
    }
  },
);

server.tool(
  "aws_get_target_group_health",
  {
    target_group_arn: z.string().optional(),
  },
  async ({ target_group_arn }) => {
    try {
      let targetGroupArn = target_group_arn;

      if (!targetGroupArn) {
        const targetGroupsResponse = await execAwsJson<{
          TargetGroups?: Array<{ TargetGroupArn?: string }>;
        }>(["elbv2", "describe-target-groups"]);
        targetGroupArn = targetGroupsResponse.TargetGroups?.[0]?.TargetGroupArn;
      }

      if (!targetGroupArn) {
        return makeTextResult("No target group ARN provided and no target group found in AWS.", {
          region: awsRegion,
          profile: awsProfile ?? "default",
          targetGroupArn: null,
          targets: [],
        });
      }

      const response = await execAwsJson<{
        TargetHealthDescriptions?: Array<{
          Target?: { Id?: string; Port?: number };
          TargetHealth?: { State?: string; Reason?: string; Description?: string };
        }>;
      }>(["elbv2", "describe-target-health", "--target-group-arn", targetGroupArn]);

      const targets =
        response.TargetHealthDescriptions?.map((target) => ({
          targetId: target.Target?.Id ?? "unknown",
          port: target.Target?.Port ?? null,
          state: target.TargetHealth?.State ?? "unknown",
          reason: target.TargetHealth?.Reason ?? null,
          description: target.TargetHealth?.Description ?? null,
        })) ?? [];

      const text =
        targets.length > 0
          ? targets
              .map(
                (target) =>
                  `- ${target.targetId}:${target.port ?? "-"} state=${target.state}${target.reason ? ` reason=${target.reason}` : ""}`,
              )
              .join("\n")
          : "Target group has no registered targets.";

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        targetGroupArn,
        targets,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_get_target_group_health", error);
    }
  },
);

server.tool(
  "aws_run_ssm_diagnostic_command",
  {
    instance_id: z.string().min(4),
    command_id: z.enum(Object.keys(ssmDiagnosticCommands) as [SsmDiagnosticCommandId, ...SsmDiagnosticCommandId[]]),
    timeout_seconds: z.number().int().min(10).max(120).default(45),
  },
  async ({ instance_id, command_id, timeout_seconds }) => {
    try {
      const result = await runSsmDiagnosticCommand(instance_id, command_id, timeout_seconds);
      const stdout = result.stdout;
      const stderr = result.stderr;
      const textParts = [
        `SSM diagnostic ${command_id} on ${instance_id}`,
        `AWS command id: ${result.awsCommandId ?? "unknown"}`,
        `Status: ${result.status}`,
      ];

      if (stdout) {
        textParts.push("", "STDOUT:", stdout.slice(0, 4000));
      }

      if (stderr) {
        textParts.push("", "STDERR:", stderr.slice(0, 2000));
      }

      if (!stdout && !stderr) {
        textParts.push("", "No output captured yet. Command may still be running or the host returned no content.");
      }

        return makeTextResult(textParts.join("\n"), {
          region: awsRegion,
          profile: awsProfile ?? "default",
          instanceId: instance_id,
          commandId: command_id,
          shellCommand: result.command,
          awsCommandId: result.awsCommandId,
          status: result.status,
          responseCode: result.responseCode,
          stdoutPreview: stdout.slice(0, 4000),
          stderrPreview: stderr.slice(0, 2000),
          guardrail:
          "This tool only runs commands from a fixed read-only diagnostic allowlist and is intended for observability.",
      });
    } catch (error) {
      return makeAwsErrorResult("aws_run_ssm_diagnostic_command", error);
    }
  },
);

server.tool(
  "aws_probe_http_endpoint",
  {
    url: z.string().url(),
    method: z.enum(["GET", "HEAD"]).default("GET"),
    timeout_seconds: z.number().int().min(3).max(60).default(15),
  },
  async ({ url, method, timeout_seconds }) => {
    try {
      const result = await probeHttpEndpoint(url, method, timeout_seconds);

        return makeTextResult(
          [
            `${method} ${url}`,
            `Status: ${result.status} ${result.statusText}`,
            `Elapsed: ${result.elapsedMs}ms`,
            `Final URL: ${result.finalUrl}`,
            result.bodyPreview ? "" : undefined,
            result.bodyPreview ? "Body preview:" : undefined,
            result.bodyPreview ? result.bodyPreview : undefined,
          ]
            .filter(Boolean)
            .join("\n"),
          {
            ...result,
          },
        );
    } catch (error) {
      return makeAwsErrorResult("aws_probe_http_endpoint", error);
    }
  },
);

server.tool(
  "aws_list_cloudwatch_metrics",
  {
    namespace: z.string().default("AWS/ApplicationELB"),
    metric_name: z.string().optional(),
    max_results: z.number().int().min(1).max(100).default(50),
  },
  async ({ namespace, metric_name, max_results }) => {
    try {
      const args = ["cloudwatch", "list-metrics", "--namespace", namespace];
      if (metric_name) {
        args.push("--metric-name", metric_name);
      }

      const response = await execAwsJson<{
        Metrics?: Array<{
          Namespace?: string;
          MetricName?: string;
          Dimensions?: Array<{ Name?: string; Value?: string }>;
        }>;
      }>(args);

      const metrics =
        response.Metrics?.slice(0, max_results).map((metric) => ({
          namespace: metric.Namespace ?? namespace,
          metricName: metric.MetricName ?? "unknown",
          dimensions:
            metric.Dimensions?.map((dimension) => ({
              name: dimension.Name ?? "unknown",
              value: dimension.Value ?? "unknown",
            })) ?? [],
        })) ?? [];

      const text =
        metrics.length > 0
          ? metrics
              .map(
                (metric) =>
                  `- ${metric.metricName} (${metric.dimensions.map((dimension) => `${dimension.name}=${dimension.value}`).join(", ") || "no dimensions"})`,
              )
              .join("\n")
          : `No CloudWatch metrics found for namespace "${namespace}".`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        namespace,
        metricName: metric_name ?? null,
        metrics,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_cloudwatch_metrics", error);
    }
  },
);

server.tool(
  "aws_get_cloudwatch_metric_series",
  {
    namespace: z.string(),
    metric_name: z.string(),
    statistic: z.enum(["Average", "Sum", "Minimum", "Maximum", "SampleCount"]).default("Average"),
    minutes: z.number().int().min(5).max(1440).default(60),
    period_seconds: z.number().int().min(60).max(3600).default(300),
    dimensions: z
      .array(
        z.object({
          name: z.string(),
          value: z.string(),
        }),
      )
      .default([]),
  },
  async ({ namespace, metric_name, statistic, minutes, period_seconds, dimensions }) => {
    try {
      const endTime = new Date();
      const startTime = new Date(endTime.getTime() - minutes * 60 * 1000);
      const dimensionArgs = dimensions.flatMap((dimension) => [`Name=${dimension.name},Value=${dimension.value}`]);
      const args = [
        "cloudwatch",
        "get-metric-statistics",
        "--namespace",
        namespace,
        "--metric-name",
        metric_name,
        "--statistics",
        statistic,
        "--start-time",
        startTime.toISOString(),
        "--end-time",
        endTime.toISOString(),
        "--period",
        String(period_seconds),
      ];

      if (dimensionArgs.length > 0) {
        args.push("--dimensions", ...dimensionArgs);
      }

      const response = await execAwsJson<{
        Datapoints?: Array<{
          Timestamp?: string;
          Average?: number;
          Sum?: number;
          Minimum?: number;
          Maximum?: number;
          SampleCount?: number;
          Unit?: string;
        }>;
        Label?: string;
      }>(args);

      const datapoints =
        response.Datapoints?.map((point) => ({
          timestamp: toIsoDate(point.Timestamp),
          value:
            statistic === "Average"
              ? point.Average
              : statistic === "Sum"
                ? point.Sum
                : statistic === "Minimum"
                  ? point.Minimum
                  : statistic === "Maximum"
                    ? point.Maximum
                    : point.SampleCount,
          unit: point.Unit ?? null,
        }))
          .sort((a, b) => (a.timestamp ?? "").localeCompare(b.timestamp ?? "")) ?? [];

      const text =
        datapoints.length > 0
          ? datapoints
              .map((point) => `- ${point.timestamp}: ${point.value ?? "null"}${point.unit ? ` ${point.unit}` : ""}`)
              .join("\n")
          : `No datapoints found for metric "${metric_name}" in namespace "${namespace}".`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        namespace,
        metricName: metric_name,
        statistic,
        minutes,
        periodSeconds: period_seconds,
        dimensions,
        datapoints,
        label: response.Label ?? metric_name,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_get_cloudwatch_metric_series", error);
    }
  },
);

server.tool("github_actions_workflow_catalog", {}, async () => {
  try {
    const workflowFiles = await getWorkflowFiles();
    const workflows = await Promise.all(
      workflowFiles.map(async (filePath) => parseWorkflowSummary(await readProjectFile(filePath), filePath)),
    );

    const textParts = [
      `GitHub CLI available: no`,
      `Workflows found: ${workflows.length}`,
      ...workflows.map(
        (workflow) =>
          `- ${workflow.name} (${workflow.filePath}) trigger=${workflow.trigger} jobs=${workflow.jobs.join(", ") || "unknown"}`,
      ),
    ];

    return makeTextResult(textParts.join("\n"), {
      ghCliAvailable: false,
      note: "This host does not have gh CLI installed, so workflow run status is limited to local workflow files.",
      workflows,
    });
  } catch (error) {
    return makeAwsErrorResult("github_actions_workflow_catalog", error);
  }
});

server.tool(
  "github_actions_recent_runs",
  {
    workflow_file: z.string().optional(),
    max_results: z.number().int().min(1).max(20).default(10),
  },
  async ({ workflow_file, max_results }) => {
    try {
      const ghAvailable = await isGhCliAvailable();
      const remoteUrl = await getGitRemoteOriginUrl();
      const repo = parseGitHubRepoSlug(remoteUrl);

      if (!repo) {
        return makeTextResult("Could not determine GitHub repository from git remote origin.", {
          ghCliAvailable: ghAvailable,
          remoteUrl,
          repo: null,
          runs: [],
        });
      }

      let runs: Array<Record<string, unknown>> = [];
      let source = "none";

      if (ghAvailable) {
        try {
          const args = ["run", "list", "--repo", repo.slug, "--limit", String(max_results), "--json", "databaseId,workflowName,headBranch,headSha,status,conclusion,createdAt,updatedAt,event,url"];
          if (workflow_file) {
            args.push("--workflow", workflow_file);
          }
          const { stdout } = await execFileAsync("gh", args, { cwd: projectRoot, maxBuffer: 1024 * 1024 * 4 });
          runs = JSON.parse(stdout) as Array<Record<string, unknown>>;
          source = "gh";
        } catch {
          source = "gh-failed";
        }
      }

      if (runs.length === 0) {
        const query = workflow_file ? `?per_page=${max_results}&event=&status=` : `?per_page=${max_results}`;
        const apiPath = workflow_file
          ? `/repos/${repo.slug}/actions/workflows/${encodeURIComponent(workflow_file)}/runs${query}`
          : `/repos/${repo.slug}/actions/runs${query}`;
        const response = await fetchGitHubApi<{
          workflow_runs?: Array<{
            id?: number;
            name?: string;
            head_branch?: string;
            head_sha?: string;
            status?: string;
            conclusion?: string;
            created_at?: string;
            updated_at?: string;
            event?: string;
            html_url?: string;
          }>;
        }>(apiPath);

        runs =
          response.workflow_runs?.slice(0, max_results).map((run) => ({
            databaseId: run.id ?? null,
            workflowName: run.name ?? "unknown",
            headBranch: run.head_branch ?? null,
            headSha: run.head_sha ?? null,
            status: run.status ?? "unknown",
            conclusion: run.conclusion ?? null,
            createdAt: run.created_at ?? null,
            updatedAt: run.updated_at ?? null,
            event: run.event ?? null,
            url: run.html_url ?? null,
          })) ?? [];
        source = "github-api";
      }

      const text = [
        `GitHub workflow runs for ${repo.slug}`,
        `Source: ${source}`,
        ...runs.map(
          (run) =>
            `- ${String(run.workflowName)} status=${String(run.status)} conclusion=${String(run.conclusion ?? "-")} branch=${String(run.headBranch ?? "-")} sha=${String(run.headSha ?? "").slice(0, 12)}`,
        ),
      ].join("\n");

      return makeTextResult(text, {
        ghCliAvailable: ghAvailable,
        remoteUrl,
        repo,
        source,
        runs,
        note:
          source === "github-api" && !githubToken
            ? "GitHub API was used without token. This may be rate-limited or fail for private repos."
            : null,
      });
    } catch (error) {
      return makeGitHubErrorResult("github_actions_recent_runs", error);
    }
  },
);

server.tool(
  "github_actions_find_run_for_sha",
  {
    sha: z.string().min(7),
    workflow_file: z.string().optional(),
    max_results: z.number().int().min(1).max(30).default(20),
  },
  async ({ sha, workflow_file, max_results }) => {
    try {
      const ghAvailable = await isGhCliAvailable();
      const remoteUrl = await getGitRemoteOriginUrl();
      const repo = parseGitHubRepoSlug(remoteUrl);

      if (!repo) {
        return makeTextResult("Could not determine GitHub repository from git remote origin.", {
          ghCliAvailable: ghAvailable,
          remoteUrl,
          repo: null,
          sha,
          runs: [],
        });
      }

      let runs: Array<Record<string, unknown>> = [];
      let source = "none";

      if (ghAvailable) {
        try {
          const args = [
            "run",
            "list",
            "--repo",
            repo.slug,
            "--limit",
            String(max_results),
            "--json",
            "databaseId,workflowName,headBranch,headSha,status,conclusion,createdAt,updatedAt,event,url",
            "--commit",
            sha,
          ];
          if (workflow_file) {
            args.push("--workflow", workflow_file);
          }
          const { stdout } = await execFileAsync("gh", args, { cwd: projectRoot, maxBuffer: 1024 * 1024 * 4 });
          runs = JSON.parse(stdout) as Array<Record<string, unknown>>;
          source = "gh";
        } catch {
          source = "gh-failed";
        }
      }

      if (runs.length === 0) {
        const apiPath = workflow_file
          ? `/repos/${repo.slug}/actions/workflows/${encodeURIComponent(workflow_file)}/runs?per_page=${max_results}`
          : `/repos/${repo.slug}/actions/runs?per_page=${max_results}`;
        const response = await fetchGitHubApi<{
          workflow_runs?: Array<{
            id?: number;
            name?: string;
            head_branch?: string;
            head_sha?: string;
            status?: string;
            conclusion?: string;
            created_at?: string;
            updated_at?: string;
            event?: string;
            html_url?: string;
          }>;
        }>(apiPath);

        runs =
          response.workflow_runs
            ?.filter((run) => (run.head_sha ?? "").startsWith(sha))
            .slice(0, max_results)
            .map((run) => ({
              databaseId: run.id ?? null,
              workflowName: run.name ?? "unknown",
              headBranch: run.head_branch ?? null,
              headSha: run.head_sha ?? null,
              status: run.status ?? "unknown",
              conclusion: run.conclusion ?? null,
              createdAt: run.created_at ?? null,
              updatedAt: run.updated_at ?? null,
              event: run.event ?? null,
              url: run.html_url ?? null,
            })) ?? [];
        source = "github-api";
      }

      const text = [
        `Workflow runs for commit ${sha} in ${repo.slug}`,
        `Source: ${source}`,
        ...(runs.length > 0
          ? runs.map(
              (run) =>
                `- ${String(run.workflowName)} status=${String(run.status)} conclusion=${String(run.conclusion ?? "-")} sha=${String(run.headSha ?? "").slice(0, 12)}`,
            )
          : ["- No workflow runs matched this SHA in the inspected window."]),
      ].join("\n");

      return makeTextResult(text, {
        ghCliAvailable: ghAvailable,
        remoteUrl,
        repo,
        source,
        sha,
        runs,
        note:
          source === "github-api" && !githubToken
            ? "GitHub API was used without token. This may be rate-limited or fail for private repos."
            : null,
      });
    } catch (error) {
      return makeGitHubErrorResult("github_actions_find_run_for_sha", error);
    }
  },
);

server.tool(
  "aws_list_ecr_image_tags",
  {
    repository_name: z.string().min(1),
    max_results: z.number().int().min(1).max(50).default(20),
  },
  async ({ repository_name, max_results }) => {
    try {
      const response = await execAwsJson<{
        imageDetails?: Array<{
          imageDigest?: string;
          imageTags?: string[];
          imagePushedAt?: string;
          imageSizeInBytes?: number;
          imageStatus?: string;
        }>;
      }>(["ecr", "describe-images", "--repository-name", repository_name, "--max-items", String(max_results)]);

      const images =
        response.imageDetails
          ?.map((image) => ({
            digest: image.imageDigest ?? "unknown",
            tags: image.imageTags ?? [],
            pushedAt: toIsoDate(image.imagePushedAt),
            sizeBytes: image.imageSizeInBytes ?? 0,
            status: image.imageStatus ?? "unknown",
          }))
          .sort((a, b) => (b.pushedAt ?? "").localeCompare(a.pushedAt ?? ""))
          .slice(0, max_results) ?? [];

      const text =
        images.length > 0
          ? images
              .map(
                (image) =>
                  `- ${image.tags.join(", ") || "<untagged>"} digest=${image.digest} pushedAt=${image.pushedAt ?? "-"}`,
              )
              .join("\n")
          : `No ECR images found for repository "${repository_name}".`;

      return makeTextResult(text, {
        region: awsRegion,
        profile: awsProfile ?? "default",
        repositoryName: repository_name,
        images,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_list_ecr_image_tags", error);
    }
  },
);

server.tool(
  "aws_detect_ec2_container_drift",
  {
    instance_id: z.string().min(4),
    repository_prefix: z.string().default("bookcabin"),
    timeout_seconds: z.number().int().min(10).max(120).default(45),
  },
  async ({ instance_id, repository_prefix, timeout_seconds }) => {
    try {
      const [reposResponse, runtime] = await Promise.all([
        execAwsJson<{
          repositories?: Array<{
            repositoryName?: string;
            repositoryUri?: string;
          }>;
        }>(["ecr", "describe-repositories", "--max-items", "100"]),
        runSsmDiagnosticCommand(instance_id, "docker_ps_compact", timeout_seconds),
      ]);

      const repositories =
        reposResponse.repositories?.filter((repo) => repo.repositoryName?.includes(repository_prefix)).map((repo) => ({
          repositoryName: repo.repositoryName ?? "unknown",
          repositoryUri: repo.repositoryUri ?? "unknown",
        })) ?? [];

      const latestImages = await Promise.all(
        repositories.map(async (repo) => {
          const imageResponse = await execAwsJson<{
            imageDetails?: Array<{
              imageDigest?: string;
              imageTags?: string[];
              imagePushedAt?: string;
            }>;
          }>(["ecr", "describe-images", "--repository-name", repo.repositoryName, "--max-items", "20"]);

          const latest =
            imageResponse.imageDetails
              ?.filter((image) => (image.imageTags?.length ?? 0) > 0)
              .map((image) => ({
                digest: image.imageDigest ?? "unknown",
                tags: image.imageTags ?? [],
                pushedAt: toIsoDate(image.imagePushedAt),
              }))
              .sort((a, b) => (b.pushedAt ?? "").localeCompare(a.pushedAt ?? ""))[0] ?? null;

          return {
            ...repo,
            latest,
          };
        }),
      );

      const runningContainers = runtime.stdout
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
          const [name, image, status] = line.split("|");
          return {
            name: name ?? "unknown",
            image: image ?? "unknown",
            status: status ?? "unknown",
          };
        });

      const driftReport = runningContainers.map((container) => {
        const matchedRepo = latestImages.find(
          (repo) =>
            container.image.includes(repo.repositoryName) ||
            container.image.includes(repo.repositoryUri) ||
            container.name.includes(repo.repositoryName.replace(/^bookcabin-/, "")),
        );

        const runtimeTag = container.image.includes(":") ? container.image.split(":").at(-1) ?? null : null;

        return {
          containerName: container.name,
          image: container.image,
          status: container.status,
          matchedRepository: matchedRepo?.repositoryName ?? null,
          expectedLatestTags: matchedRepo?.latest?.tags ?? [],
          expectedDigest: matchedRepo?.latest?.digest ?? null,
          runtimeTag,
          driftDetected:
            matchedRepo != null &&
            matchedRepo.latest != null &&
            runtimeTag != null &&
            !matchedRepo.latest.tags.includes(runtimeTag),
        };
      });

      const textParts = [
        `Runtime drift check for ${instance_id}`,
        `Repositories checked: ${latestImages.length}`,
        `Running containers: ${runningContainers.length}`,
        ...driftReport.map(
          (item) =>
            `- ${item.containerName}: image=${item.image} repo=${item.matchedRepository ?? "-"} drift=${item.driftDetected ? "yes" : "no"}`,
        ),
      ];

      if (runtime.stderr) {
        textParts.push("", "SSM STDERR:", runtime.stderr.slice(0, 1200));
      }

      return makeTextResult(textParts.join("\n"), {
        region: awsRegion,
        profile: awsProfile ?? "default",
        instanceId: instance_id,
        repositoryPrefix: repository_prefix,
        runtimeCommandId: runtime.awsCommandId,
        repositories: latestImages,
        runningContainers,
        driftReport,
      });
    } catch (error) {
      return makeAwsErrorResult("aws_detect_ec2_container_drift", error);
    }
  },
);

server.tool(
  "aws_incident_alb_5xx_snapshot",
  {
    probe_path: z.string().default("/health"),
    minutes: z.number().int().min(5).max(1440).default(60),
  },
  async ({ probe_path, minutes }) => {
    try {
      const [loadBalancerResponse, targetGroupResponse, metricCatalog] = await Promise.all([
        execAwsJson<{
          LoadBalancers?: Array<{ LoadBalancerArn?: string; LoadBalancerName?: string; DNSName?: string }>;
        }>(["elbv2", "describe-load-balancers"]),
        execAwsJson<{
          TargetGroups?: Array<{ TargetGroupArn?: string; TargetGroupName?: string; LoadBalancerArns?: string[] }>;
        }>(["elbv2", "describe-target-groups"]),
        execAwsJson<{
          Metrics?: Array<{
            MetricName?: string;
            Dimensions?: Array<{ Name?: string; Value?: string }>;
          }>;
        }>(["cloudwatch", "list-metrics", "--namespace", "AWS/ApplicationELB"]),
      ]);

      const loadBalancer =
        loadBalancerResponse.LoadBalancers?.find((item) => item.LoadBalancerName?.includes("bookcabin")) ??
        loadBalancerResponse.LoadBalancers?.[0];
      const targetGroup =
        targetGroupResponse.TargetGroups?.find((item) => item.TargetGroupName?.includes("bookcabin")) ??
        targetGroupResponse.TargetGroups?.[0];

      const lbDimension =
        metricCatalog.Metrics?.find((metric) =>
          metric.Dimensions?.some((dimension) => dimension.Name === "LoadBalancer" && dimension.Value?.includes("bookcabin-free-tier-alb")),
        )?.Dimensions?.find((dimension) => dimension.Name === "LoadBalancer")?.Value ?? null;
      const tgDimension =
        metricCatalog.Metrics?.find((metric) =>
          metric.Dimensions?.some((dimension) => dimension.Name === "TargetGroup" && dimension.Value?.includes("bookcabin-free-tier-tg")),
        )?.Dimensions?.find((dimension) => dimension.Name === "TargetGroup")?.Value ?? null;

      const metricQueries = [];
      if (lbDimension) {
        metricQueries.push(
          execAwsJson<{
            Datapoints?: Array<{ Sum?: number; Timestamp?: string }>;
          }>([
            "cloudwatch",
            "get-metric-statistics",
            "--namespace",
            "AWS/ApplicationELB",
            "--metric-name",
            "HTTPCode_ELB_5XX_Count",
            "--statistics",
            "Sum",
            "--start-time",
            new Date(Date.now() - minutes * 60 * 1000).toISOString(),
            "--end-time",
            new Date().toISOString(),
            "--period",
            "300",
            "--dimensions",
            `Name=LoadBalancer,Value=${lbDimension}`,
          ]),
        );
      }
      if (lbDimension) {
        metricQueries.push(
          execAwsJson<{
            Datapoints?: Array<{ Sum?: number; Timestamp?: string }>;
          }>([
            "cloudwatch",
            "get-metric-statistics",
            "--namespace",
            "AWS/ApplicationELB",
            "--metric-name",
            "RequestCount",
            "--statistics",
            "Sum",
            "--start-time",
            new Date(Date.now() - minutes * 60 * 1000).toISOString(),
            "--end-time",
            new Date().toISOString(),
            "--period",
            "300",
            "--dimensions",
            `Name=LoadBalancer,Value=${lbDimension}`,
          ]),
        );
      }
      if (lbDimension && tgDimension) {
        metricQueries.push(
          execAwsJson<{
            Datapoints?: Array<{ Sum?: number; Average?: number; Timestamp?: string }>;
          }>([
            "cloudwatch",
            "get-metric-statistics",
            "--namespace",
            "AWS/ApplicationELB",
            "--metric-name",
            "HTTPCode_Target_5XX_Count",
            "--statistics",
            "Sum",
            "--start-time",
            new Date(Date.now() - minutes * 60 * 1000).toISOString(),
            "--end-time",
            new Date().toISOString(),
            "--period",
            "300",
            "--dimensions",
            `Name=LoadBalancer,Value=${lbDimension}`,
            `Name=TargetGroup,Value=${tgDimension}`,
          ]),
        );
      }

      const [elb5xx, requestCount, target5xx, pathDiagnosis] = await Promise.all([
        metricQueries[0] ?? Promise.resolve({ Datapoints: [] }),
        metricQueries[1] ?? Promise.resolve({ Datapoints: [] }),
        metricQueries[2] ?? Promise.resolve({ Datapoints: [] }),
        (async () => {
          const probes = [];
          if (loadBalancer?.DNSName) {
            probes.push(await probeHttpEndpoint(`http://${loadBalancer.DNSName}${probe_path}`, "GET", 15));
          }
          const targetHealth = targetGroup?.TargetGroupArn
            ? await execAwsJson<{
                TargetHealthDescriptions?: Array<{
                  Target?: { Id?: string; Port?: number };
                  TargetHealth?: { State?: string; Reason?: string };
                }>;
              }>(["elbv2", "describe-target-health", "--target-group-arn", targetGroup.TargetGroupArn])
            : { TargetHealthDescriptions: [] };

          return {
            probes,
            targetHealth:
              targetHealth.TargetHealthDescriptions?.map((item) => ({
                targetId: item.Target?.Id ?? "unknown",
                port: item.Target?.Port ?? null,
                state: item.TargetHealth?.State ?? "unknown",
                reason: item.TargetHealth?.Reason ?? null,
              })) ?? [],
          };
        })(),
      ]);

      const sumDatapoints = (points?: Array<{ Sum?: number }>) => points?.reduce((sum, point) => sum + (point.Sum ?? 0), 0) ?? 0;

      const result = {
        region: awsRegion,
        profile: awsProfile ?? "default",
        minutes,
        loadBalancer:
          loadBalancer
            ? { arn: loadBalancer.LoadBalancerArn ?? "unknown", name: loadBalancer.LoadBalancerName ?? "unknown", dnsName: loadBalancer.DNSName ?? "unknown" }
            : null,
        targetGroup:
          targetGroup
            ? { arn: targetGroup.TargetGroupArn ?? "unknown", name: targetGroup.TargetGroupName ?? "unknown" }
            : null,
        metrics: {
          elb5xxSum: sumDatapoints(elb5xx.Datapoints),
          target5xxSum: sumDatapoints(target5xx.Datapoints),
          requestCountSum: sumDatapoints(requestCount.Datapoints),
        },
        targetHealth: pathDiagnosis.targetHealth,
        probes: pathDiagnosis.probes,
      };

      const text = [
        `ALB 5xx incident snapshot for last ${minutes} minutes`,
        result.loadBalancer ? `ALB: ${result.loadBalancer.name}` : "ALB: not found",
        result.targetGroup ? `Target Group: ${result.targetGroup.name}` : "Target Group: not found",
        `ELB 5xx sum: ${result.metrics.elb5xxSum}`,
        `Target 5xx sum: ${result.metrics.target5xxSum}`,
        `Request count sum: ${result.metrics.requestCountSum}`,
        ...(result.targetHealth.length > 0
          ? result.targetHealth.map(
              (target) => `- Target ${target.targetId}:${target.port ?? "-"} state=${target.state}${target.reason ? ` reason=${target.reason}` : ""}`,
            )
          : ["- No target health entries"]),
        ...(result.probes.length > 0
          ? result.probes.map((probe) => `- Probe: ${probe.status} ${probe.statusText} (${probe.elapsedMs}ms) ${probe.finalUrl}`)
          : ["- No probe result"]),
      ].join("\n");

      return makeTextResult(text, result as Record<string, unknown>);
    } catch (error) {
      return makeAwsErrorResult("aws_incident_alb_5xx_snapshot", error);
    }
  },
);

server.tool(
  "aws_incident_target_unhealthy_snapshot",
  {
    target_group_arn: z.string().optional(),
    probe_path: z.string().default("/health"),
    minutes: z.number().int().min(5).max(1440).default(60),
  },
  async ({ target_group_arn, probe_path, minutes }) => {
    try {
      let targetGroupArn = target_group_arn;
      const [targetGroupsResponse, loadBalancersResponse] = await Promise.all([
        execAwsJson<{
          TargetGroups?: Array<{
            TargetGroupArn?: string;
            TargetGroupName?: string;
            HealthCheckPath?: string;
            LoadBalancerArns?: string[];
          }>;
        }>(["elbv2", "describe-target-groups"]),
        execAwsJson<{
          LoadBalancers?: Array<{ LoadBalancerArn?: string; LoadBalancerName?: string; DNSName?: string }>;
        }>(["elbv2", "describe-load-balancers"]),
      ]);

      const targetGroup =
        (targetGroupArn
          ? targetGroupsResponse.TargetGroups?.find((item) => item.TargetGroupArn === targetGroupArn)
          : targetGroupsResponse.TargetGroups?.find((item) => item.TargetGroupName?.includes("bookcabin"))) ??
        targetGroupsResponse.TargetGroups?.[0];
      targetGroupArn = targetGroup?.TargetGroupArn;

      if (!targetGroupArn || !targetGroup) {
        return makeTextResult("No target group found for unhealthy snapshot.", {
          region: awsRegion,
          profile: awsProfile ?? "default",
          targetGroupArn: null,
        });
      }

      const lbArn = targetGroup.LoadBalancerArns?.[0] ?? null;
      const loadBalancer = loadBalancersResponse.LoadBalancers?.find((item) => item.LoadBalancerArn === lbArn) ?? null;

      const targetHealthResponse = await execAwsJson<{
        TargetHealthDescriptions?: Array<{
          Target?: { Id?: string; Port?: number };
          TargetHealth?: { State?: string; Reason?: string; Description?: string };
        }>;
      }>(["elbv2", "describe-target-health", "--target-group-arn", targetGroupArn]);

      const unhealthyTargets =
        targetHealthResponse.TargetHealthDescriptions?.map((item) => ({
          targetId: item.Target?.Id ?? "unknown",
          port: item.Target?.Port ?? null,
          state: item.TargetHealth?.State ?? "unknown",
          reason: item.TargetHealth?.Reason ?? null,
          description: item.TargetHealth?.Description ?? null,
        })).filter((item) => item.state !== "healthy") ?? [];

      const probes = [];
      if (loadBalancer?.DNSName) {
        probes.push(await probeHttpEndpoint(`http://${loadBalancer.DNSName}${probe_path}`, "GET", 15));
      }

      let metricSummary = { unhealthyHostCountMax: 0, target5xxSum: 0 };
      const lbDim = loadBalancer?.LoadBalancerArn?.split(":loadbalancer/")[1] ? `app/${loadBalancer.LoadBalancerName}/${loadBalancer.LoadBalancerArn?.split("/").at(-1)}` : null;
      const tgDim = targetGroupArn.split(":targetgroup/")[1] ? `targetgroup/${targetGroup.TargetGroupName}/${targetGroupArn.split("/").at(-1)}` : null;

      if (lbDim && tgDim) {
        const [unhealthyMetric, target5xxMetric] = await Promise.all([
          execAwsJson<{ Datapoints?: Array<{ Maximum?: number }> }>([
            "cloudwatch",
            "get-metric-statistics",
            "--namespace",
            "AWS/ApplicationELB",
            "--metric-name",
            "UnHealthyHostCount",
            "--statistics",
            "Maximum",
            "--start-time",
            new Date(Date.now() - minutes * 60 * 1000).toISOString(),
            "--end-time",
            new Date().toISOString(),
            "--period",
            "300",
            "--dimensions",
            `Name=LoadBalancer,Value=${lbDim}`,
            `Name=TargetGroup,Value=${tgDim}`,
          ]),
          execAwsJson<{ Datapoints?: Array<{ Sum?: number }> }>([
            "cloudwatch",
            "get-metric-statistics",
            "--namespace",
            "AWS/ApplicationELB",
            "--metric-name",
            "HTTPCode_Target_5XX_Count",
            "--statistics",
            "Sum",
            "--start-time",
            new Date(Date.now() - minutes * 60 * 1000).toISOString(),
            "--end-time",
            new Date().toISOString(),
            "--period",
            "300",
            "--dimensions",
            `Name=LoadBalancer,Value=${lbDim}`,
            `Name=TargetGroup,Value=${tgDim}`,
          ]),
        ]);

        metricSummary = {
          unhealthyHostCountMax: unhealthyMetric.Datapoints?.reduce((max, p) => Math.max(max, p.Maximum ?? 0), 0) ?? 0,
          target5xxSum: target5xxMetric.Datapoints?.reduce((sum, p) => sum + (p.Sum ?? 0), 0) ?? 0,
        };
      }

      const result = {
        region: awsRegion,
        profile: awsProfile ?? "default",
        minutes,
        targetGroup: {
          arn: targetGroupArn,
          name: targetGroup.TargetGroupName ?? "unknown",
          healthCheckPath: targetGroup.HealthCheckPath ?? null,
        },
        loadBalancer: loadBalancer
          ? {
              arn: loadBalancer.LoadBalancerArn ?? "unknown",
              name: loadBalancer.LoadBalancerName ?? "unknown",
              dnsName: loadBalancer.DNSName ?? "unknown",
            }
          : null,
        unhealthyTargets,
        probes,
        metrics: metricSummary,
      };

      const text = [
        `Target unhealthy snapshot for ${result.targetGroup.name}`,
        `Unhealthy targets: ${unhealthyTargets.length}`,
        `UnHealthyHostCount max: ${metricSummary.unhealthyHostCountMax}`,
        `Target 5xx sum: ${metricSummary.target5xxSum}`,
        ...unhealthyTargets.map(
          (target) => `- ${target.targetId}:${target.port ?? "-"} state=${target.state}${target.reason ? ` reason=${target.reason}` : ""}`,
        ),
        ...(probes.length > 0 ? probes.map((probe) => `- Probe: ${probe.status} ${probe.statusText} (${probe.elapsedMs}ms)`) : []),
      ].join("\n");

      return makeTextResult(text, result as Record<string, unknown>);
    } catch (error) {
      return makeAwsErrorResult("aws_incident_target_unhealthy_snapshot", error);
    }
  },
);

server.tool(
  "aws_trace_deployment_source_of_truth",
  {
    instance_id: z.string().min(4),
    workflow_file: z.string().default("deploy-free-tier.yml"),
    repository_prefix: z.string().default("bookcabin"),
    timeout_seconds: z.number().int().min(10).max(120).default(45),
  },
  async ({ instance_id, workflow_file, repository_prefix, timeout_seconds }) => {
    try {
      const remoteUrl = await getGitRemoteOriginUrl();
      const repo = parseGitHubRepoSlug(remoteUrl);

      let workflowRuns: Array<Record<string, unknown>> = [];
      let workflowSource = "none";
      if (repo) {
        try {
          const response = await fetchGitHubApi<{
            workflow_runs?: Array<{
              id?: number;
              name?: string;
              head_branch?: string;
              head_sha?: string;
              status?: string;
              conclusion?: string;
              created_at?: string;
              updated_at?: string;
              html_url?: string;
            }>;
          }>(`/repos/${repo.slug}/actions/workflows/${encodeURIComponent(workflow_file)}/runs?per_page=5`);
          workflowRuns =
            response.workflow_runs?.map((run) => ({
              id: run.id ?? null,
              name: run.name ?? workflow_file,
              headSha: run.head_sha ?? null,
              headBranch: run.head_branch ?? null,
              status: run.status ?? "unknown",
              conclusion: run.conclusion ?? null,
              createdAt: run.created_at ?? null,
              updatedAt: run.updated_at ?? null,
              url: run.html_url ?? null,
            })) ?? [];
          workflowSource = "github-api";
        } catch {
          workflowSource = "github-api-failed";
        }
      }

      const [drift, targetHealth] = await Promise.all([
        (async () => {
          const reposResponse = await execAwsJson<{
            repositories?: Array<{ repositoryName?: string; repositoryUri?: string }>;
          }>(["ecr", "describe-repositories", "--max-items", "100"]);
          const runtime = await runSsmDiagnosticCommand(instance_id, "docker_ps_compact", timeout_seconds);
          const repositories =
            reposResponse.repositories?.filter((repo) => repo.repositoryName?.includes(repository_prefix)).map((repo) => ({
              repositoryName: repo.repositoryName ?? "unknown",
              repositoryUri: repo.repositoryUri ?? "unknown",
            })) ?? [];
          return { runtime, repositories };
        })(),
        execAwsJson<{
          TargetGroups?: Array<{ TargetGroupArn?: string; TargetGroupName?: string }>;
        }>(["elbv2", "describe-target-groups"]),
      ]);

      const targetGroupArn =
        targetHealth.TargetGroups?.find((item) => item.TargetGroupName?.includes("bookcabin"))?.TargetGroupArn ??
        targetHealth.TargetGroups?.[0]?.TargetGroupArn;
      const health =
        targetGroupArn != null
          ? await execAwsJson<{
              TargetHealthDescriptions?: Array<{
                Target?: { Id?: string; Port?: number };
                TargetHealth?: { State?: string; Reason?: string };
              }>;
            }>(["elbv2", "describe-target-health", "--target-group-arn", targetGroupArn])
          : { TargetHealthDescriptions: [] };

      const runningContainers = drift.runtime.stdout
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
          const [name, image, status] = line.split("|");
          return { name: name ?? "unknown", image: image ?? "unknown", status: status ?? "unknown" };
        });

      const result = {
        repo,
        workflowSource,
        workflowRuns,
        instanceId: instance_id,
        runtimeCommandId: drift.runtime.awsCommandId,
        runningContainers,
        targetHealth:
          health.TargetHealthDescriptions?.map((item) => ({
            targetId: item.Target?.Id ?? "unknown",
            port: item.Target?.Port ?? null,
            state: item.TargetHealth?.State ?? "unknown",
            reason: item.TargetHealth?.Reason ?? null,
          })) ?? [],
        repositories: drift.repositories,
      };

      const text = [
        `Deployment source-of-truth trace for ${instance_id}`,
        `Workflow source: ${workflowSource}`,
        ...(workflowRuns.length > 0
          ? workflowRuns.map(
              (run) =>
                `- Run ${String(run.id)} sha=${String(run.headSha ?? "").slice(0, 12)} status=${String(run.status)} conclusion=${String(run.conclusion ?? "-")}`,
            )
          : ["- No workflow runs available"]),
        ...runningContainers.map((container) => `- Runtime ${container.name}: ${container.image} (${container.status})`),
        ...result.targetHealth.map(
          (target) => `- Target ${target.targetId}:${target.port ?? "-"} state=${target.state}${target.reason ? ` reason=${target.reason}` : ""}`,
        ),
      ].join("\n");

      return makeTextResult(text, result as Record<string, unknown>);
    } catch (error) {
      return makeGitHubErrorResult("aws_trace_deployment_source_of_truth", error);
    }
  },
);

server.tool(
  "aws_incident_summary",
  {
    scenario: z.enum(["alb_5xx", "target_unhealthy", "deploy_drift"]),
    instance_id: z.string().min(4).optional(),
    probe_path: z.string().default("/health"),
    minutes: z.number().int().min(5).max(1440).default(60),
    timeout_seconds: z.number().int().min(10).max(120).default(45),
    repository_prefix: z.string().default("bookcabin"),
  },
  async ({ scenario, instance_id, probe_path, minutes, timeout_seconds, repository_prefix }) => {
    try {
      if (scenario === "alb_5xx") {
        const [loadBalancerResponse, targetGroupResponse] = await Promise.all([
          execAwsJson<{
            LoadBalancers?: Array<{ LoadBalancerArn?: string; LoadBalancerName?: string; DNSName?: string }>;
          }>(["elbv2", "describe-load-balancers"]),
          execAwsJson<{
            TargetGroups?: Array<{ TargetGroupArn?: string; TargetGroupName?: string }>;
          }>(["elbv2", "describe-target-groups"]),
        ]);
        const loadBalancer =
          loadBalancerResponse.LoadBalancers?.find((item) => item.LoadBalancerName?.includes("bookcabin")) ??
          loadBalancerResponse.LoadBalancers?.[0];
        const targetGroup =
          targetGroupResponse.TargetGroups?.find((item) => item.TargetGroupName?.includes("bookcabin")) ??
          targetGroupResponse.TargetGroups?.[0];

        return makeTextResult(
          [
            `Incident summary: ALB 5xx`,
            `ALB: ${loadBalancer?.LoadBalancerName ?? "not found"}`,
            `Target Group: ${targetGroup?.TargetGroupName ?? "not found"}`,
            `Recommended next tools: aws_incident_alb_5xx_snapshot, aws_tail_cloudwatch_logs, aws_run_ssm_diagnostic_command`,
          ].join("\n"),
          {
            scenario,
            probePath: probe_path,
            minutes,
            loadBalancerName: loadBalancer?.LoadBalancerName ?? null,
            targetGroupName: targetGroup?.TargetGroupName ?? null,
          },
        );
      }

      if (scenario === "target_unhealthy") {
        const targetGroupsResponse = await execAwsJson<{
          TargetGroups?: Array<{ TargetGroupArn?: string; TargetGroupName?: string }>;
        }>(["elbv2", "describe-target-groups"]);
        const targetGroup =
          targetGroupsResponse.TargetGroups?.find((item) => item.TargetGroupName?.includes("bookcabin")) ??
          targetGroupsResponse.TargetGroups?.[0];

        return makeTextResult(
          [
            `Incident summary: target unhealthy`,
            `Target Group: ${targetGroup?.TargetGroupName ?? "not found"}`,
            `Recommended next tools: aws_incident_target_unhealthy_snapshot, aws_get_target_group_health, aws_probe_http_endpoint`,
          ].join("\n"),
          {
            scenario,
            probePath: probe_path,
            minutes,
            targetGroupName: targetGroup?.TargetGroupName ?? null,
          },
        );
      }

      if (!instance_id) {
        return makeTextResult("Scenario deploy_drift requires instance_id.", {
          scenario,
          instanceId: null,
        });
      }

      return makeTextResult(
        [
          `Incident summary: deploy drift`,
          `Instance: ${instance_id}`,
          `Repository prefix: ${repository_prefix}`,
          `Recommended next tools: aws_detect_ec2_container_drift, aws_trace_deployment_source_of_truth, github_actions_find_run_for_sha`,
        ].join("\n"),
        {
          scenario,
          instanceId: instance_id,
          repositoryPrefix: repository_prefix,
          timeoutSeconds: timeout_seconds,
        },
      );
    } catch (error) {
      return makeAwsErrorResult("aws_incident_summary", error);
    }
  },
);

server.tool(
  "aws_run_ssm_remediation_action",
  {
    instance_id: z.string().min(4),
    action_id: z.enum(Object.keys(ssmRemediationCommands) as [SsmRemediationCommandId, ...SsmRemediationCommandId[]]),
    timeout_seconds: z.number().int().min(10).max(120).default(60),
  },
  async ({ instance_id, action_id, timeout_seconds }) => {
    try {
      const result = await runSsmRemediationCommand(instance_id, action_id, timeout_seconds);
      const textParts = [
        `SSM remediation ${action_id} on ${instance_id}`,
        `AWS command id: ${result.awsCommandId ?? "unknown"}`,
        `Status: ${result.status}`,
        `Command: ${result.command}`,
      ];

      if (result.stdout) {
        textParts.push("", "STDOUT:", result.stdout.slice(0, 3000));
      }

      if (result.stderr) {
        textParts.push("", "STDERR:", result.stderr.slice(0, 2000));
      }

      return makeTextResult(textParts.join("\n"), {
        region: awsRegion,
        profile: awsProfile ?? "default",
        instanceId: instance_id,
        actionId: action_id,
        shellCommand: result.command,
        awsCommandId: result.awsCommandId,
        status: result.status,
        responseCode: result.responseCode,
        stdoutPreview: result.stdout.slice(0, 3000),
        stderrPreview: result.stderr.slice(0, 2000),
        guardrail:
          "This remediation tool is intentionally narrow and only allows a tiny audited set of restart actions.",
      });
    } catch (error) {
      return makeAwsErrorResult("aws_run_ssm_remediation_action", error);
    }
  },
);

server.tool(
  "aws_diagnose_delivery_path",
  {
    probe_path: z.string().default("/health"),
    timeout_seconds: z.number().int().min(3).max(60).default(15),
  },
  async ({ probe_path, timeout_seconds }) => {
    try {
      const [cloudfrontResponse, loadBalancerResponse, targetGroupResponse] = await Promise.all([
        execAwsJson<{
          DistributionList?: { Items?: Array<{ Id?: string; DomainName?: string; Comment?: string; Enabled?: boolean }> };
        }>(["cloudfront", "list-distributions"]),
        execAwsJson<{
          LoadBalancers?: Array<{ LoadBalancerArn?: string; LoadBalancerName?: string; DNSName?: string; State?: { Code?: string } }>;
        }>(["elbv2", "describe-load-balancers"]),
        execAwsJson<{
          TargetGroups?: Array<{ TargetGroupArn?: string; TargetGroupName?: string; HealthCheckPath?: string }>;
        }>(["elbv2", "describe-target-groups"]),
      ]);

      const cloudfrontDistribution =
        cloudfrontResponse.DistributionList?.Items?.find((item) => item.Comment?.toLowerCase().includes("bookcabin")) ??
        cloudfrontResponse.DistributionList?.Items?.[0];
      const loadBalancer =
        loadBalancerResponse.LoadBalancers?.find((item) => item.LoadBalancerName?.toLowerCase().includes("bookcabin")) ??
        loadBalancerResponse.LoadBalancers?.[0];
      const targetGroup =
        targetGroupResponse.TargetGroups?.find((item) => item.TargetGroupName?.toLowerCase().includes("bookcabin")) ??
        targetGroupResponse.TargetGroups?.[0];

      const targetHealth = targetGroup?.TargetGroupArn
        ? await execAwsJson<{
            TargetHealthDescriptions?: Array<{
              Target?: { Id?: string; Port?: number };
              TargetHealth?: { State?: string; Reason?: string };
            }>;
          }>(["elbv2", "describe-target-health", "--target-group-arn", targetGroup.TargetGroupArn])
        : { TargetHealthDescriptions: [] };

      const probes = [];

      if (cloudfrontDistribution?.DomainName) {
        probes.push({
          label: "cloudfront",
          ...(await probeHttpEndpoint(`https://${cloudfrontDistribution.DomainName}${probe_path}`, "GET", timeout_seconds)),
        });
      }

      if (loadBalancer?.DNSName) {
        probes.push({
          label: "alb",
          ...(await probeHttpEndpoint(`http://${loadBalancer.DNSName}${probe_path}`, "GET", timeout_seconds)),
        });
      }

      const result = {
        region: awsRegion,
        profile: awsProfile ?? "default",
        probePath: probe_path,
        cloudfrontDistribution:
          cloudfrontDistribution
            ? {
                id: cloudfrontDistribution.Id ?? "unknown",
                domainName: cloudfrontDistribution.DomainName ?? "unknown",
                enabled: cloudfrontDistribution.Enabled ?? false,
                comment: cloudfrontDistribution.Comment ?? "",
              }
            : null,
        loadBalancer:
          loadBalancer
            ? {
                arn: loadBalancer.LoadBalancerArn ?? "unknown",
                name: loadBalancer.LoadBalancerName ?? "unknown",
                dnsName: loadBalancer.DNSName ?? "unknown",
                state: loadBalancer.State?.Code ?? "unknown",
              }
            : null,
        targetGroup:
          targetGroup
            ? {
                arn: targetGroup.TargetGroupArn ?? "unknown",
                name: targetGroup.TargetGroupName ?? "unknown",
                healthCheckPath: targetGroup.HealthCheckPath ?? null,
              }
            : null,
        targetHealth:
          targetHealth.TargetHealthDescriptions?.map((item) => ({
            targetId: item.Target?.Id ?? "unknown",
            port: item.Target?.Port ?? null,
            state: item.TargetHealth?.State ?? "unknown",
            reason: item.TargetHealth?.Reason ?? null,
          })) ?? [],
        probes,
      };

      const text = [
        `Delivery path diagnosis for ${probe_path}`,
        result.cloudfrontDistribution
          ? `CloudFront: ${result.cloudfrontDistribution.id} ${result.cloudfrontDistribution.domainName}`
          : "CloudFront: not found",
        result.loadBalancer ? `ALB: ${result.loadBalancer.name} ${result.loadBalancer.dnsName}` : "ALB: not found",
        result.targetGroup ? `Target Group: ${result.targetGroup.name}` : "Target Group: not found",
        ...(result.targetHealth.length > 0
          ? result.targetHealth.map(
              (target) => `- Target ${target.targetId}:${target.port ?? "-"} state=${target.state}${target.reason ? ` reason=${target.reason}` : ""}`,
            )
          : ["- No target health entries returned"]),
        ...(probes.length > 0
          ? probes.map(
              (probe) =>
                `- Probe ${probe.label}: ${probe.status} ${probe.statusText} (${probe.elapsedMs}ms) -> ${probe.finalUrl}`,
            )
          : ["- No HTTP probes were executed"]),
      ].join("\n");

      return makeTextResult(text, result as Record<string, unknown>);
    } catch (error) {
      return makeAwsErrorResult("aws_diagnose_delivery_path", error);
    }
  },
);

server.prompt(
  "plan_bugfix",
  {
    issue: z.string(),
  },
  ({ issue }) => ({
    messages: [
      {
        role: "user",
        content: {
          type: "text",
          text: [
            "You are working on the Bookcabin project.",
            "Before changing code, read the shared MCP resources for overview and known gaps.",
            `Bug to fix: ${issue}`,
            "Return a short plan that includes impacted layers, likely files, test approach, and deployment risk.",
          ].join("\n"),
        },
      },
    ],
  }),
);

server.prompt(
  "plan_feature",
  {
    feature: z.string(),
  },
  ({ feature }) => ({
    messages: [
      {
        role: "user",
        content: {
          type: "text",
          text: [
            "You are planning a new Bookcabin feature.",
            "Use the MCP tools to inspect routes, docs, architecture, and known gaps first.",
            `Feature request: ${feature}`,
            "Return a short implementation plan with backend, frontend, infra, tests, and rollout notes.",
          ].join("\n"),
        },
      },
    ],
  }),
);

server.prompt(
  "plan_uiux_change",
  {
    request: z.string(),
  },
  ({ request }) => ({
    messages: [
      {
        role: "user",
        content: {
          type: "text",
          text: [
            "You are improving the UI or UX of Bookcabin.",
            "Read project overview, routes, and architecture before editing views or assets.",
            `UI/UX request: ${request}`,
            "Return a concise plan covering user flow, affected screens, API dependencies, and visual consistency risks.",
          ].join("\n"),
        },
      },
    ],
  }),
);

async function main() {
  const missingDocs = [];

  for (const entry of Object.values(docsCatalog)) {
    if (!(await pathExists(entry.filePath))) {
      missingDocs.push(entry.filePath);
    }
  }

  if (missingDocs.length > 0) {
    throw new Error(`Missing required MCP context files: ${missingDocs.join(", ")}`);
  }

  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((error) => {
  console.error("Bookcabin MCP failed to start:", error);
  process.exit(1);
});
