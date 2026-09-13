import React, { useState } from 'react';
import { Layers, Database, Box, Sparkles, Check, ArrowUpRight } from 'lucide-react';

const TEMPLATES = [
  { name: 'Supabase', category: 'Stacks', icon: '⚡', desc: 'PostgreSQL backend, Auth, Realtime & Storage', port: '8000' },
  { name: 'PostgreSQL 17', category: 'Databases', icon: '🐘', desc: 'High performance relational engine with automated S3 WAL', port: '5432' },
  { name: 'Redis Cache', category: 'Databases', icon: '🔴', desc: 'Ultra-low latency in-memory caching & queue broker', port: '6379' },
  { name: 'Next.js 15', category: 'Runtimes', icon: '▲', desc: 'React framework with SSR, SSG, and edge route support', port: '3000' },
  { name: 'ClickHouse', category: 'Databases', icon: '📊', desc: 'Fast columnar analytics database for massive log telemetry', port: '8123' },
  { name: 'Ghost CMS', category: 'Stacks', icon: '👻', desc: 'Modern professional independent publishing platform', port: '2368' },
  { name: 'FastAPI / Python', category: 'Runtimes', icon: '⚡', desc: 'High performance Python microservices with Uvicorn', port: '8080' },
  { name: 'MinIO Storage', category: 'Stacks', icon: '🪣', desc: 'High-performance S3-compatible object storage server', port: '9000' },
  { name: 'Meilisearch', category: 'Stacks', icon: '🔍', desc: 'Lightning fast, typo-tolerant search engine', port: '7700' },
  { name: 'MongoDB', category: 'Databases', icon: '🍃', desc: 'Document-oriented NoSQL database for flexible schemas', port: '27017' },
  { name: 'PocketBase', category: 'Stacks', icon: '📦', desc: 'Open-source embedded SQLite backend in 1 binary file', port: '8090' },
  { name: 'Custom Dockerfile', category: 'Runtimes', icon: '🐳', desc: 'Any multi-stage Dockerfile or docker-compose.yml file', port: 'Any' },
];

const CATEGORIES = ['All', 'Databases', 'Stacks', 'Runtimes'];

export default function SupportedTemplates() {
  const [activeCategory, setActiveCategory] = useState('All');

  const filtered = activeCategory === 'All' 
    ? TEMPLATES 
    : TEMPLATES.filter((t) => t.category === activeCategory);

  return (
    <section id="templates" className="relative py-20 border-t border-white/[0.08] bg-[#090c12]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div>
            <div className="inline-flex items-center gap-2 rounded-md border border-white/[0.1] bg-white/[0.04] px-2.5 py-1 text-xs font-mono text-neutral-300 mb-3">
              <Layers className="size-3 text-cyan-400" />
              <span>TEMPLATES & RUNTIMES</span>
            </div>
            <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
              Instant 1-Click Catalog. <br />
              <span className="text-neutral-400 font-medium">Over 50+ pre-built services and native runtimes.</span>
            </h2>
          </div>

          {/* Filter tabs */}
          <div className="flex items-center gap-1.5 p-1 rounded-xl border border-white/[0.08] bg-white/[0.03] self-start md:self-auto">
            {CATEGORIES.map((cat) => (
              <button
                key={cat}
                onClick={() => setActiveCategory(cat)}
                className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-all ${
                  activeCategory === cat
                    ? 'bg-emerald-500 text-neutral-950 font-semibold shadow-sm'
                    : 'text-neutral-400 hover:text-white'
                }`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>

        {/* Templates Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {filtered.map((item, idx) => (
            <a
              key={idx}
              href="/login"
              className="group relative rounded-xl border border-white/[0.08] bg-[#0c1017] p-4 transition-all hover:border-emerald-500/40 hover:bg-[#0f141f] hover:-translate-y-0.5"
            >
              <div className="flex items-center justify-between mb-3">
                <span className="text-2xl select-none">{item.icon}</span>
                <span className="font-mono text-[10px] text-neutral-400 bg-white/[0.04] border border-white/[0.06] px-2 py-0.5 rounded">
                  Port: {item.port}
                </span>
              </div>

              <h4 className="text-sm font-bold text-white flex items-center justify-between">
                <span>{item.name}</span>
                <ArrowUpRight className="size-3.5 text-neutral-500 group-hover:text-emerald-400 transition-colors" />
              </h4>

              <p className="mt-1 text-xs text-neutral-400 leading-relaxed">
                {item.desc}
              </p>

              <div className="mt-3 pt-2.5 border-t border-white/[0.05] flex items-center justify-between text-[10.5px] text-neutral-500">
                <span>{item.category}</span>
                <span className="text-emerald-400 font-mono">1-Click Deploy</span>
              </div>
            </a>
          ))}
        </div>

      </div>
    </section>
  );
}
