import React from 'react';
import { ShieldCheck, GitBranch, Database, Globe, Lock, Cpu, Server, FileText, Zap, RefreshCw, Layers, HardDrive } from 'lucide-react';

const FEATURES = [
  {
    icon: GitBranch,
    title: 'Push-to-Deploy Git Workflows',
    badge: 'Automated CI/CD',
    color: 'emerald',
    description: 'Connect your GitHub or GitLab repository. Every push to your production branch automatically triggers a cached multi-stage build and zero-downtime rolling container swap.',
    codeSnippet: 'git push origin main → Webhook → Buildpack → Zero Downtime Reload',
  },
  {
    icon: Database,
    title: 'Enterprise Database Engines',
    badge: 'Stateful Storage',
    color: 'cyan',
    description: 'Deploy standalone PostgreSQL 17, Redis 7, MySQL 8, MariaDB, MongoDB, ClickHouse, KeyDB, and Dragonfly with one click. Automated S3/R2 backup schedules with 1-click restore.',
    codeSnippet: 'Auto-WAL Archiving · Point-in-time Recovery · Dedicated NVMe Volumes',
  },
  {
    icon: ShieldCheck,
    title: 'Forensic Audit & Hash Chains',
    badge: 'Cryptographic Security',
    color: 'purple',
    description: 'Every deployment, environment mutation, and cluster event is cryptographically sealed into a tamper-evident SHA-256 Merkle hash chain with GeoIP and device fingerprinting.',
    codeSnippet: 'SHA-256 Chain · Immutable Audit Trail · PII Auto-Redaction',
  },
  {
    icon: Globe,
    title: 'Automated TLS & Edge Ingress',
    badge: 'Traefik Dynamic Routing',
    color: 'amber',
    description: 'Automated Let\'s Encrypt SSL certificates for all your apex domains, subdomains, and wildcard paths. Custom headers, basic auth guards, and automated proxy reload with 0 dropped requests.',
    codeSnippet: 'Let\'s Encrypt Auto-Renewal · Dynamic WebSocket & HTTP/2 Routing',
  },
  {
    icon: Cpu,
    title: 'Granular Resource Quotas',
    badge: 'Resource Guard',
    color: 'emerald',
    description: 'Set hard container vCPU allocations, RAM limits, and disk quotas per environment. Protect host nodes from runaway processes and memory exhaustion automatically.',
    codeSnippet: 'Real-Time cgroup V2 Limits · Memory OOM Protections · Volume Limits',
  },
  {
    icon: Layers,
    title: 'Docker Compose & Stack Catalog',
    badge: 'No Lock-in',
    color: 'cyan',
    description: 'Bring standard Docker Compose YAML files or choose from 50+ pre-configured stacks including Supabase, WordPress, Ghost, Meilisearch, Plausible, and Grafana.',
    codeSnippet: 'Standard docker-compose.yml · No Custom Proprietary DSLs',
  },
];

export default function FeaturesGrid() {
  return (
    <section id="features" className="relative py-20 border-t border-white/[0.08] bg-[#07090e]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="max-w-3xl mb-14">
          <div className="inline-flex items-center gap-2 rounded-md border border-white/[0.1] bg-white/[0.04] px-2.5 py-1 text-xs font-mono text-neutral-300 mb-3">
            <Zap className="size-3 text-amber-400" />
            <span>PLATFORM CAPABILITIES</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Built for Engineers. <br />
            <span className="text-neutral-400 font-medium">Without the enterprise vendor bloat.</span>
          </h2>
          <p className="mt-3 text-neutral-400 text-sm sm:text-base leading-relaxed">
            Everything you need to orchestrate modern software fleets: push-to-deploy pipelines, stateful database instances, forensic audit compliance, and automated proxy routing.
          </p>
        </div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {FEATURES.map((feat, idx) => {
            const Icon = feat.icon;

            return (
              <div
                key={idx}
                className="group relative rounded-2xl border border-white/[0.08] bg-[#0c1017] p-6 transition-all duration-200 hover:border-white/[0.2] hover:bg-[#0f141e] hover:-translate-y-1 shadow-lg shadow-black/40"
              >
                {/* Top icon and badge */}
                <div className="flex items-center justify-between mb-5">
                  <div className="flex size-11 items-center justify-center rounded-xl border border-white/[0.1] bg-white/[0.03] text-emerald-400 group-hover:border-emerald-500/30 group-hover:bg-emerald-500/10 transition-colors">
                    <Icon className="size-5" />
                  </div>
                  <span className="font-mono text-[10.5px] font-medium text-neutral-400 bg-white/[0.04] px-2 py-0.5 rounded-full border border-white/[0.06]">
                    {feat.badge}
                  </span>
                </div>

                <h3 className="text-base font-bold text-white mb-2 group-hover:text-emerald-300 transition-colors">
                  {feat.title}
                </h3>

                <p className="text-xs sm:text-[13px] text-neutral-400 leading-relaxed mb-5 font-normal">
                  {feat.description}
                </p>

                {/* Code / Telemetry strip */}
                <div className="pt-3 border-t border-white/[0.06]">
                  <div className="flex items-center gap-2 font-mono text-[11px] text-neutral-500 group-hover:text-neutral-300 transition-colors truncate">
                    <span className="text-emerald-400 font-bold">$</span>
                    <span className="truncate">{feat.codeSnippet}</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

      </div>
    </section>
  );
}
