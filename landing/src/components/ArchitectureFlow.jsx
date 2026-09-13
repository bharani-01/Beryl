import React, { useState } from 'react';
import { Server, Globe, Shield, Database, Cpu, HardDrive, RefreshCw, Layers, ArrowRight, Activity } from 'lucide-react';

const NODES = [
  {
    id: 'fleet',
    title: 'Fleet Sentinel Layer',
    subtitle: 'Any Linux Host (Ubuntu/Debian)',
    badge: 'Hardware Agnostic',
    icon: Server,
    color: 'emerald',
    specs: ['SSH Keypair Sentinel', 'System Resource Telemetry', 'Zero-Agent Daemon', 'Localhost id=0 Support'],
    detail: 'Beryl connects to any remote VPS, dedicated machine, or local cloud node over hardened SSH. No complex Kubernetes master-node overhead required.',
  },
  {
    id: 'ingress',
    title: 'Dynamic Edge Ingress',
    subtitle: 'Traefik v3 & Auto TLS',
    badge: 'Let\'s Encrypt TLS',
    icon: Globe,
    color: 'cyan',
    specs: ['Automated Cert Resolution', 'Wildcard Domain Routing', 'Zero-Downtime Blue/Green', 'Custom HTTP Headers'],
    detail: 'Real-time proxy routing dynamically reconfigured via Docker socket labels. Certificates renew automatically with zero packet loss.',
  },
  {
    id: 'engine',
    title: 'Container Engine',
    subtitle: 'Native Docker & Compose',
    badge: 'Multi-Arch x86/ARM',
    icon: Layers,
    color: 'purple',
    specs: ['Multi-Stage Railpack Builds', 'Isolated Bridge Networks', 'Healthcheck Watchdogs', 'Auto-Restart On Failure'],
    detail: 'Runs standard Dockerfiles, Nixpacks, and Docker Compose manifests. No proprietary runtime lock-in.',
  },
  {
    id: 'storage',
    title: 'Storage & S3 Vault',
    subtitle: 'Encrypted Volumes & Backups',
    badge: 'Automated Snapshots',
    icon: Database,
    color: 'amber',
    specs: ['Standalone PostgreSQL 17', 'In-Memory Redis & KeyDB', 'Hourly S3 / R2 Snapshots', 'Point-in-Time Recovery'],
    detail: 'Stateful database engines run on dedicated high-speed NVMe volumes with automated cron snapshots to Cloudflare R2, AWS S3, or MinIO.',
  },
];

export default function ArchitectureFlow() {
  const [selectedNode, setSelectedNode] = useState(NODES[0].id);

  const active = NODES.find((n) => n.id === selectedNode) || NODES[0];

  return (
    <section id="architecture" className="relative py-20 border-t border-white/[0.08] bg-[#080b11]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="max-w-3xl mb-12">
          <div className="inline-flex items-center gap-2 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-mono text-emerald-400 mb-3">
            <Cpu className="size-3" />
            <span>INFRASTRUCTURE BLUEPRINT</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Engineered for Sovereignty. <br />
            <span className="text-neutral-400 font-medium">Simple as a PaaS, powerful as raw metal.</span>
          </h2>
          <p className="mt-3 text-neutral-400 text-sm sm:text-base leading-relaxed">
            Unlike rigid cloud vendors that force proprietary configurations and astronomical egress pricing, Beryl runs on raw Docker orchestration atop standard Linux hosts.
          </p>
        </div>

        {/* 4-Stage Interactive Pipeline Grid */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          {NODES.map((node, idx) => {
            const Icon = node.icon;
            const isSelected = selectedNode === node.id;

            return (
              <div
                key={node.id}
                onClick={() => setSelectedNode(node.id)}
                className={`group relative cursor-pointer rounded-xl border p-5 transition-all ${
                  isSelected
                    ? 'border-emerald-500/50 bg-[#0e1420] shadow-xl shadow-emerald-500/10'
                    : 'border-white/[0.08] bg-[#0c0f17] hover:border-white/[0.2] hover:bg-[#0f131e]'
                }`}
              >
                {/* Stage number */}
                <div className="flex items-center justify-between mb-4">
                  <span className="font-mono text-[11px] font-semibold text-neutral-500">
                    STAGE 0{idx + 1}
                  </span>
                  <span
                    className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${
                      isSelected
                        ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                        : 'bg-white/[0.05] text-neutral-400'
                    }`}
                  >
                    {node.badge}
                  </span>
                </div>

                <div className="flex items-center gap-3 mb-3">
                  <div
                    className={`flex size-10 items-center justify-center rounded-lg border ${
                      isSelected
                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
                        : 'border-white/[0.1] bg-white/[0.03] text-neutral-400 group-hover:text-white'
                    }`}
                  >
                    <Icon className="size-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-semibold text-white">{node.title}</h3>
                    <p className="text-[11px] text-neutral-400">{node.subtitle}</p>
                  </div>
                </div>

                <div className="mt-4 pt-3 border-t border-white/[0.06] flex items-center justify-between text-[11px]">
                  <span className={isSelected ? 'text-emerald-400 font-medium' : 'text-neutral-500'}>
                    {isSelected ? 'Active Selection' : 'Inspect Layer'}
                  </span>
                  <ArrowRight
                    className={`size-3.5 transition-transform ${
                      isSelected ? 'text-emerald-400 translate-x-0.5' : 'text-neutral-600 group-hover:text-neutral-400'
                    }`}
                  />
                </div>
              </div>
            );
          })}
        </div>

        {/* Selected Layer In-Depth Inspection Card */}
        <div className="rounded-2xl border border-white/[0.1] bg-[#0d121c] p-6 sm:p-8 shadow-2xl">
          <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-white/[0.08]">
            <div className="flex items-center gap-4">
              <div className="flex size-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                <active.icon className="size-6" />
              </div>
              <div>
                <span className="font-mono text-xs text-emerald-400 tracking-wider uppercase font-semibold">
                  Layer Deep-Dive
                </span>
                <h4 className="text-xl font-bold text-white mt-0.5">{active.title} — {active.subtitle}</h4>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 text-xs font-mono text-emerald-400">
                <Activity className="size-3.5 animate-pulse" />
                Live Telemetry Active
              </span>
            </div>
          </div>

          <p className="mt-6 text-sm sm:text-base text-neutral-300 leading-relaxed max-w-4xl">
            {active.detail}
          </p>

          {/* Technical capabilities tags */}
          <div className="mt-6 pt-6 border-t border-white/[0.06] grid grid-cols-2 sm:grid-cols-4 gap-3">
            {active.specs.map((spec, sIdx) => (
              <div
                key={sIdx}
                className="flex items-center gap-2 rounded-lg border border-white/[0.06] bg-white/[0.02] p-3 text-xs font-mono text-neutral-300"
              >
                <div className="size-1.5 rounded-full bg-emerald-400 shrink-0"></div>
                <span className="truncate">{spec}</span>
              </div>
            ))}
          </div>
        </div>

      </div>
    </section>
  );
}
