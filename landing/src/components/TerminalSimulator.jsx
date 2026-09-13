import React, { useState } from 'react';
import { Terminal, Copy, Check, Play, Shield, CheckCircle2, RefreshCw } from 'lucide-react';

const COMMANDS = [
  {
    id: 'supabase',
    label: '1-Click Supabase',
    cmd: 'beryl stack deploy supabase --ssl=auto --env=prod',
    description: 'Deploy complete enterprise backend stack in 45 seconds',
    logs: [
      { text: '[INIT] Resolving optimal node: beryl-node-01 (Ubuntu 24.04 LTS)', type: 'info' },
      { text: '[DOCKER] Pulling images: supabase/postgres:15, gotrue, postgrest, kong', type: 'info' },
      { text: '[STORAGE] Provisioning encrypted volume /data/supabase/db (20GB NVMe)', type: 'success' },
      { text: '[NETWORK] Creating isolated bridge network: net_supabase_internal', type: 'info' },
      { text: '[SSL] Requesting Let\'s Encrypt TLS certificate for db.trackifyapp.co.in', type: 'warning' },
      { text: '[PROXY] Traefik HTTP router dynamic rule bound: Host(`db.trackifyapp.co.in`)', type: 'info' },
      { text: '[HEALTHCHECK] 4/4 containers healthy (latency: 1.4ms)', type: 'success' },
      { text: '✓ SERVICE ONLINE: https://db.trackifyapp.co.in/project/default', type: 'accent' },
    ],
  },
  {
    id: 'git-deploy',
    label: 'Push to Deploy',
    cmd: 'git push beryl main # Webhook received from GitHub: commit 9b4f2a',
    description: 'Automated container build, zero-downtime rolling reload',
    logs: [
      { text: '[WEBHOOK] GitHub push received: branch "main" (commit: 9b4f2a)', type: 'info' },
      { text: '[BUILD] Building Next.js 15 standalone image with Railpack engine', type: 'info' },
      { text: '[CACHE] Restored 420MB layer cache from previous successful build', type: 'success' },
      { text: '[COMPOSE] Generating production orchestration manifest...', type: 'info' },
      { text: '[ROLLING] Starting new container nextjs-app_v2 (port: 3000)', type: 'info' },
      { text: '[HEALTHCHECK] GET /api/health -> 200 OK (5ms)', type: 'success' },
      { text: '[TRAEFIK] Swapping proxy traffic to nextjs-app_v2 with zero dropped packets', type: 'accent' },
      { text: '✓ DEPLOYED SUCCESSFULLY: https://app.trackifyapp.co.in', type: 'accent' },
    ],
  },
  {
    id: 'postgres',
    label: 'Postgres & Backups',
    cmd: 'beryl db create postgresql-17 --ha --s3-backup=hourly',
    description: 'Standalone PostgreSQL 17 with continuous point-in-time recovery',
    logs: [
      { text: '[DB] Provisioning StandalonePostgresql v17.2 on dedicated port 5432', type: 'info' },
      { text: '[SECURITY] Generated 64-char cryptographically secure root credentials', type: 'success' },
      { text: '[S3] Configuring automated hourly snapshot to Cloudflare R2 bucket', type: 'info' },
      { text: '[METRICS] Connected Prometheus real-time IOPS & connection pool monitor', type: 'info' },
      { text: '[READY] Connection URI: postgresql://beryl:****@db.trackifyapp.co.in:5432/main', type: 'accent' },
      { text: '✓ POSTGRESQL 17 READY: Status: running:healthy', type: 'accent' },
    ],
  },
  {
    id: 'forensic',
    label: 'Forensic Audit',
    cmd: 'beryl security audit verify --chain=latest --tamper-check',
    description: 'Cryptographic SHA-256 hash chain verification of all cluster mutations',
    logs: [
      { text: '[AUDIT] Inspecting forensic vault records (chain height: #1,842)', type: 'info' },
      { text: '[GENESIS] Root hash: 3a9f02c91b7e4d8... verified', type: 'success' },
      { text: '[GEOIP] Verified user session: 18.60.46.17 (AWS EC2 us-east-2)', type: 'info' },
      { text: '[CRYPTO] Calculating Merkle root across 1,842 mutation records...', type: 'info' },
      { text: '[RESULT] 100% Chain Integrity Confirmed. Zero tampering detected.', type: 'success' },
      { text: '✓ VAULT STATUS: 1,842 / 1,842 verified cryptographically', type: 'accent' },
    ],
  },
];

export default function TerminalSimulator() {
  const [activeTab, setActiveTab] = useState(COMMANDS[0].id);
  const [copied, setCopied] = useState(false);

  const activeCommand = COMMANDS.find((c) => c.id === activeTab) || COMMANDS[0];

  const handleCopy = () => {
    navigator.clipboard.writeText('curl -fsSL https://beryl.sh/install.sh | bash');
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="w-full rounded-2xl border border-white/[0.1] bg-[#0b0e14] shadow-2xl shadow-black/80 overflow-hidden">
      
      {/* Terminal Topbar */}
      <div className="flex flex-wrap items-center justify-between border-b border-white/[0.08] bg-[#0e121b] px-4 py-2.5 gap-2">
        <div className="flex items-center gap-2">
          <div className="flex gap-1.5">
            <span className="size-3 rounded-full bg-rose-500/80"></span>
            <span className="size-3 rounded-full bg-amber-500/80"></span>
            <span className="size-3 rounded-full bg-emerald-500/80"></span>
          </div>
          <span className="ml-2 font-mono text-[11px] text-neutral-400 font-medium flex items-center gap-1">
            <Terminal className="size-3 text-emerald-400" />
            beryl-control-plane ~ bash
          </span>
        </div>

        {/* Command tabs */}
        <div className="flex items-center gap-1 overflow-x-auto py-1">
          {COMMANDS.map((item) => (
            <button
              key={item.id}
              onClick={() => setActiveTab(item.id)}
              className={`rounded-md px-2.5 py-1 text-xs font-mono transition-all whitespace-nowrap ${
                activeTab === item.id
                  ? 'bg-white/[0.1] text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm'
                  : 'text-neutral-400 hover:text-neutral-200 hover:bg-white/[0.04]'
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>

        {/* Quick copy one-liner */}
        <button
          onClick={handleCopy}
          className="inline-flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.04] px-2.5 py-1 text-[11.5px] font-mono text-neutral-300 hover:border-emerald-500/40 hover:text-emerald-400 transition-all"
          title="Copy curl install command"
        >
          {copied ? <Check className="size-3 text-emerald-400" /> : <Copy className="size-3" />}
          <span>{copied ? 'Copied script' : 'curl install'}</span>
        </button>
      </div>

      {/* Terminal Body */}
      <div className="p-5 font-mono text-xs sm:text-[13px] leading-relaxed overflow-x-auto min-h-[290px] bg-[#090b10]">
        
        {/* Command prompt line */}
        <div className="flex items-center gap-2 text-neutral-300 mb-3 pb-2.5 border-b border-white/[0.05]">
          <span className="text-emerald-400 font-semibold">user@beryl-cloud:~$</span>
          <span className="text-white font-medium">{activeCommand.cmd}</span>
          <span className="inline-block size-2 bg-emerald-400 cursor-blink ml-1"></span>
        </div>

        {/* Simulated logs output */}
        <div className="space-y-1.5">
          {activeCommand.logs.map((log, idx) => (
            <div key={idx} className="flex items-start gap-2.5">
              <span className="text-neutral-600 select-none text-[11px] pt-0.5">
                {String(idx + 1).padStart(2, '0')}
              </span>
              <span
                className={
                  log.type === 'accent'
                    ? 'text-emerald-300 font-semibold bg-emerald-950/30 px-1.5 py-0.5 rounded border border-emerald-500/20'
                    : log.type === 'success'
                    ? 'text-emerald-400'
                    : log.type === 'warning'
                    ? 'text-amber-300'
                    : 'text-neutral-400'
                }
              >
                {log.text}
              </span>
            </div>
          ))}
        </div>

        {/* Active description badge at bottom */}
        <div className="mt-5 pt-3 border-t border-white/[0.06] flex items-center justify-between text-[11px] text-neutral-500">
          <span className="flex items-center gap-1.5">
            <span className="size-1.5 rounded-full bg-emerald-400"></span>
            {activeCommand.description}
          </span>
          <span className="text-neutral-500 font-mono">Response time: ~42ms</span>
        </div>
      </div>
    </div>
  );
}
