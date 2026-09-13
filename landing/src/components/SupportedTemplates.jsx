import React, { useState } from 'react';
import { Layers, ArrowUpRight, Check, Sparkles } from 'lucide-react';

const OPEN_SOURCE_SOFTWARE = [
  {
    name: 'Supabase',
    category: 'Apps & CMS',
    icon: '⚡',
    desc: 'Complete open-source Firebase alternative with Postgres, Auth & Realtime.',
    port: '8000',
    tag: 'Popular',
  },
  {
    name: 'Ghost CMS',
    category: 'Apps & CMS',
    icon: '👻',
    desc: 'Modern, independent publishing platform for newsletters and blogs.',
    port: '2368',
    tag: 'Publishing',
  },
  {
    name: 'WordPress',
    category: 'Apps & CMS',
    icon: '🌐',
    desc: 'The world’s favorite open-source content and website creator.',
    port: '80',
    tag: 'CMS',
  },
  {
    name: 'Plausible Analytics',
    category: 'Privacy & Tools',
    icon: '📊',
    desc: 'Lightweight, privacy-friendly Google Analytics alternative without cookies.',
    port: '8000',
    tag: 'Privacy',
  },
  {
    name: 'PostgreSQL 17',
    category: 'Databases',
    icon: '🐘',
    desc: 'Rock-solid relational engine with automated S3 point-in-time snapshots.',
    port: '5432',
    tag: 'Database',
  },
  {
    name: 'Redis Cache',
    category: 'Databases',
    icon: '🔴',
    desc: 'Ultra-low latency in-memory data store, cache, and message queue broker.',
    port: '6379',
    tag: 'Cache',
  },
  {
    name: 'n8n Automation',
    category: 'Privacy & Tools',
    icon: '🔀',
    desc: 'Fair-code workflow automation tool connecting hundreds of services.',
    port: '5678',
    tag: 'Automation',
  },
  {
    name: 'Nextcloud',
    category: 'Privacy & Tools',
    icon: '☁️',
    desc: 'Sovereign productivity suite: private file storage, calendar, and contacts.',
    port: '8080',
    tag: 'Productivity',
  },
  {
    name: 'MinIO Storage',
    category: 'Privacy & Tools',
    icon: '🪣',
    desc: 'High-performance S3-compatible object storage for backups and files.',
    port: '9000',
    tag: 'Storage',
  },
  {
    name: 'Vaultwarden',
    category: 'Privacy & Tools',
    icon: '🔐',
    desc: 'Lightweight, self-hostable Bitwarden password manager backend.',
    port: '8080',
    tag: 'Security',
  },
  {
    name: 'Grafana',
    category: 'Privacy & Tools',
    icon: '📈',
    desc: 'Interactive metric dashboards, CPU and memory telemetry visualizations.',
    port: '3000',
    tag: 'Telemetry',
  },
  {
    name: 'Meilisearch',
    category: 'Databases',
    icon: '🔍',
    desc: 'Instant, typo-tolerant full-text search engine for fast web apps.',
    port: '7700',
    tag: 'Search',
  },
];

const CATEGORIES = ['All', 'Apps & CMS', 'Databases', 'Privacy & Tools'];

export default function SupportedTemplates() {
  const [activeCategory, setActiveCategory] = useState('All');

  const filtered = activeCategory === 'All'
    ? OPEN_SOURCE_SOFTWARE
    : OPEN_SOURCE_SOFTWARE.filter((item) => item.category === activeCategory);

  return (
    <section id="templates" className="py-20 sm:py-28 bg-[#FDFCF8] border-t border-stone-200/60">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full border border-stone-200/80 bg-white px-3 py-1 text-xs font-medium text-[#78716C] shadow-xs mb-3">
              <Sparkles className="size-3 text-[#FFB7B2]" />
              <span>300+ Pre-Configured Templates</span>
            </div>
            
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-medium tracking-tight text-[#292524] leading-tight">
              Deploy any free{' '}
              <span className="font-cursive text-4xl sm:text-5xl md:text-6xl text-[#FFB7B2] font-normal inline-block mx-1">
                open-source
              </span>{' '}
              software.
            </h2>
            <p className="text-sm sm:text-base text-[#78716C] mt-2 max-w-xl font-normal">
              No manual Docker setups or complicated YAML tinkering. Click launch, and Beryl manages persistent volumes, domains, and automatic SSL for you.
            </p>
          </div>

          {/* Filter tabs */}
          <div className="flex items-center gap-1.5 p-1.5 rounded-full border border-stone-200 bg-white shadow-xs self-start md:self-auto">
            {CATEGORIES.map((cat) => (
              <button
                key={cat}
                type="button"
                onClick={() => setActiveCategory(cat)}
                className={`rounded-full px-4 py-2 text-xs font-medium transition-all cursor-pointer ${
                  activeCategory === cat
                    ? 'bg-[#292524] text-white shadow-xs'
                    : 'text-[#78716C] hover:text-[#292524] hover:bg-stone-50'
                }`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>

        {/* Templates Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          {filtered.map((item, idx) => (
            <a
              key={idx}
              href="/login"
              className="group rounded-3xl border border-stone-200/70 bg-white p-6 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] hover:shadow-[0_10px_30px_-4px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
            >
              <div>
                <div className="flex items-center justify-between mb-4">
                  <span className="text-3xl select-none">{item.icon}</span>
                  <span className="text-[11px] font-medium text-stone-500 bg-stone-50 border border-stone-100 px-2.5 py-0.5 rounded-full">
                    {item.tag}
                  </span>
                </div>

                <h3 className="text-lg font-medium text-[#292524] flex items-center justify-between group-hover:text-[#FFB7B2] transition-colors">
                  <span>{item.name}</span>
                  <ArrowUpRight className="size-4 text-stone-400 group-hover:text-[#FFB7B2] transition-colors" />
                </h3>

                <p className="mt-2 text-xs sm:text-[13px] text-[#78716C] leading-relaxed">
                  {item.desc}
                </p>
              </div>

              <div className="mt-5 pt-3.5 border-t border-stone-100 flex items-center justify-between text-[11px] text-[#78716C]">
                <span className="text-stone-400">Port: {item.port}</span>
                <span className="font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                  1-Click Free
                </span>
              </div>
            </a>
          ))}
        </div>

        {/* Bottom prompt */}
        <div className="mt-12 text-center">
          <p className="text-xs text-stone-500">
            Need a tool not listed? Beryl runs any <span className="font-semibold text-stone-700">Docker Compose</span> or custom <span className="font-semibold text-stone-700">Dockerfile</span> with zero friction.
          </p>
        </div>

      </div>
    </section>
  );
}
