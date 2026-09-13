import React, { useState } from 'react';
import { Layers, Database, Box, Play, Square, Activity, ExternalLink, HardDrive, Cpu, Shield, RefreshCw } from 'lucide-react';

const INITIAL_RESOURCES = [
  {
    id: 'nextjs-web',
    name: 'nextjs-ecommerce',
    type: 'Application',
    typeIcon: Box,
    status: 'Running',
    isRunning: true,
    env: 'production',
    updated: '5 mins ago',
    cpu: '0.12 vCPU',
    memory: '142 MB',
    fqdn: 'https://store.trackifyapp.co.in',
  },
  {
    id: 'postgres-db',
    name: 'postgresql-primary',
    type: 'Database',
    typeIcon: Database,
    status: 'Running',
    isRunning: true,
    env: 'production',
    updated: '1 hour ago',
    cpu: '0.25 vCPU',
    memory: '280 MB',
    fqdn: 'db:5432',
  },
  {
    id: 'redis-cache',
    name: 'redis-cache-cluster',
    type: 'Database',
    typeIcon: Database,
    status: 'Running',
    isRunning: true,
    env: 'production',
    updated: '2 hours ago',
    cpu: '0.05 vCPU',
    memory: '64 MB',
    fqdn: 'redis:6379',
  },
  {
    id: 'supabase-stack',
    name: 'supabase-backend',
    type: 'Service',
    typeIcon: Layers,
    status: 'Running',
    isRunning: true,
    env: 'production',
    updated: '2 days ago',
    cpu: '0.38 vCPU',
    memory: '410 MB',
    fqdn: 'https://db.trackifyapp.co.in',
  },
];

export default function InteractiveLivePreview() {
  const [activeTab, setActiveTab] = useState('all');
  const [resources, setResources] = useState(INITIAL_RESOURCES);
  const [selectedId, setSelectedId] = useState(INITIAL_RESOURCES[0].id);

  const toggleResource = (id, e) => {
    e.stopPropagation();
    setResources((prev) =>
      prev.map((r) => {
        if (r.id === id) {
          const nextState = !r.isRunning;
          return {
            ...r,
            isRunning: nextState,
            status: nextState ? 'Running' : 'Exited',
          };
        }
        return r;
      })
    );
  };

  const filtered = resources.filter((r) => {
    if (activeTab === 'all') return true;
    if (activeTab === 'application') return r.type === 'Application';
    if (activeTab === 'database') return r.type === 'Database';
    if (activeTab === 'service') return r.type === 'Service';
    return true;
  });

  const selectedResource = resources.find((r) => r.id === selectedId) || resources[0];

  const appsCount = resources.filter((r) => r.type === 'Application').length;
  const dbsCount = resources.filter((r) => r.type === 'Database').length;
  const svcsCount = resources.filter((r) => r.type === 'Service').length;

  return (
    <section id="simulator" className="relative py-20 border-t border-white/[0.08] bg-[#090c12]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="max-w-3xl mb-12">
          <div className="inline-flex items-center gap-2 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-mono text-emerald-400 mb-3">
            <Activity className="size-3" />
            <span>INTERACTIVE CONTROL PLANE</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Test Drive the Beryl Experience. <br />
            <span className="text-neutral-400 font-medium">Click around the simulated control plane below.</span>
          </h2>
        </div>

        {/* Dashboard Shell Frame */}
        <div className="rounded-2xl border border-white/[0.12] bg-[#0c1018] shadow-2xl overflow-hidden">
          
          {/* Top simulated control bar */}
          <div className="flex flex-wrap items-center justify-between border-b border-white/[0.08] bg-[#0e131e] px-4 sm:px-6 py-3.5 gap-4">
            <div className="flex items-center gap-3">
              <img src="/beryl-logo.png" alt="Beryl" className="size-6 object-contain" />
              <div>
                <span className="text-xs font-bold text-white tracking-tight">Acme Engineering / production</span>
                <span className="ml-2 font-mono text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                  Starter Plan (3/3 Active)
                </span>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <a
                href="/login"
                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-neutral-950 hover:bg-emerald-400 transition-colors shadow-sm"
              >
                <span>Launch Real Console</span>
              </a>
            </div>
          </div>

          <div className="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {/* Left 2 Cols: Resources Table & Tabs */}
            <div className="lg:col-span-2 space-y-4">
              
              {/* Tabs */}
              <div className="flex items-center gap-4 border-b border-white/[0.08] text-xs font-medium pb-2">
                <button
                  onClick={() => setActiveTab('all')}
                  className={`pb-2 transition-colors ${
                    activeTab === 'all'
                      ? 'border-b-2 border-emerald-400 text-white font-semibold'
                      : 'text-neutral-400 hover:text-white'
                  }`}
                >
                  All resources <span className="ml-1 rounded-full bg-white/[0.06] px-1.5 py-px text-[10px] text-neutral-400">{resources.length}</span>
                </button>

                <button
                  onClick={() => setActiveTab('application')}
                  className={`pb-2 transition-colors ${
                    activeTab === 'application'
                      ? 'border-b-2 border-emerald-400 text-white font-semibold'
                      : 'text-neutral-400 hover:text-white'
                  }`}
                >
                  Applications <span className="ml-1 rounded-full bg-white/[0.06] px-1.5 py-px text-[10px] text-neutral-400">{appsCount}</span>
                </button>

                <button
                  onClick={() => setActiveTab('database')}
                  className={`pb-2 transition-colors ${
                    activeTab === 'database'
                      ? 'border-b-2 border-emerald-400 text-white font-semibold'
                      : 'text-neutral-400 hover:text-white'
                  }`}
                >
                  Databases <span className="ml-1 rounded-full bg-white/[0.06] px-1.5 py-px text-[10px] text-neutral-400">{dbsCount}</span>
                </button>

                <button
                  onClick={() => setActiveTab('service')}
                  className={`pb-2 transition-colors ${
                    activeTab === 'service'
                      ? 'border-b-2 border-emerald-400 text-white font-semibold'
                      : 'text-neutral-400 hover:text-white'
                  }`}
                >
                  Services <span className="ml-1 rounded-full bg-white/[0.06] px-1.5 py-px text-[10px] text-neutral-400">{svcsCount}</span>
                </button>
              </div>

              {/* Table */}
              <div className="overflow-hidden rounded-xl border border-white/[0.08] bg-[#090c12]">
                <table className="w-full text-left text-xs">
                  <thead className="border-b border-white/[0.06] bg-white/[0.02] text-[11px] font-medium text-neutral-400">
                    <tr>
                      <th className="py-2.5 pl-4 pr-3">Name</th>
                      <th className="px-3 py-2.5">Type</th>
                      <th className="px-3 py-2.5">Status</th>
                      <th className="py-2.5 pl-3 pr-4 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-white/[0.04]">
                    {filtered.map((item) => {
                      const Icon = item.typeIcon;
                      const isSelected = selectedId === item.id;

                      return (
                        <tr
                          key={item.id}
                          onClick={() => setSelectedId(item.id)}
                          className={`cursor-pointer transition-colors ${
                            isSelected ? 'bg-white/[0.04]' : 'hover:bg-white/[0.02]'
                          }`}
                        >
                          <td className="py-3 pl-4 pr-3">
                            <div className="flex items-center gap-2.5">
                              <div className="flex size-7 items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.03] text-neutral-300">
                                <Icon className="size-3.5" />
                              </div>
                              <div>
                                <p className="font-semibold text-white truncate">{item.name}</p>
                                <p className="text-[10.5px] text-neutral-500 font-mono truncate">{item.fqdn}</p>
                              </div>
                            </div>
                          </td>

                          <td className="px-3 py-3 whitespace-nowrap">
                            <span className="rounded-full bg-white/[0.05] border border-white/[0.06] px-2 py-0.5 text-[10px] text-neutral-300 font-medium">
                              {item.type}
                            </span>
                          </td>

                          <td className="px-3 py-3 whitespace-nowrap">
                            <span
                              className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10.5px] font-medium ${
                                item.isRunning
                                  ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                  : 'bg-neutral-800 text-neutral-400'
                              }`}
                            >
                              <span className={`size-1.5 rounded-full ${item.isRunning ? 'bg-emerald-400 animate-pulse' : 'bg-neutral-500'}`}></span>
                              {item.status}
                            </span>
                          </td>

                          <td className="py-3 pl-3 pr-4 text-right">
                            <button
                              onClick={(e) => toggleResource(item.id, e)}
                              className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium border transition-colors ${
                                item.isRunning
                                  ? 'border-red-500/30 text-red-400 hover:bg-red-500/10'
                                  : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'
                              }`}
                            >
                              {item.isRunning ? <Square className="size-2.5 fill-current" /> : <Play className="size-2.5 fill-current" />}
                              <span>{item.isRunning ? 'Stop' : 'Start'}</span>
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>

            </div>

            {/* Right 1 Col: Live Inspector Detail Panel */}
            <div className="rounded-xl border border-white/[0.08] bg-[#090c12] p-4 sm:p-5 flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between pb-3 border-b border-white/[0.06] mb-4">
                  <span className="font-mono text-[10.5px] uppercase font-bold text-neutral-400 tracking-wider">
                    Container Metrics
                  </span>
                  <span className="flex items-center gap-1 font-mono text-[10.5px] text-emerald-400">
                    <span className="size-1.5 rounded-full bg-emerald-400"></span>
                    Live
                  </span>
                </div>

                <div className="space-y-4">
                  <div>
                    <p className="text-[11px] text-neutral-500 uppercase font-mono">Resource Identity</p>
                    <p className="text-sm font-bold text-white mt-0.5">{selectedResource.name}</p>
                    <p className="text-xs text-neutral-400 font-mono mt-0.5">{selectedResource.fqdn}</p>
                  </div>

                  <div className="grid grid-cols-2 gap-3 pt-2">
                    <div className="rounded-lg border border-white/[0.06] bg-white/[0.02] p-3">
                      <div className="flex items-center gap-1.5 text-neutral-400 text-[11px] mb-1">
                        <Cpu className="size-3 text-emerald-400" />
                        <span>CPU Quota</span>
                      </div>
                      <p className="font-mono text-sm font-bold text-white">{selectedResource.cpu}</p>
                    </div>

                    <div className="rounded-lg border border-white/[0.06] bg-white/[0.02] p-3">
                      <div className="flex items-center gap-1.5 text-neutral-400 text-[11px] mb-1">
                        <HardDrive className="size-3 text-cyan-400" />
                        <span>Memory RSS</span>
                      </div>
                      <p className="font-mono text-sm font-bold text-white">{selectedResource.memory}</p>
                    </div>
                  </div>

                  <div className="rounded-lg border border-white/[0.06] bg-white/[0.02] p-3 space-y-1 text-xs">
                    <div className="flex justify-between text-neutral-400">
                      <span>TLS Cert Issuer:</span>
                      <span className="text-white font-mono">Let's Encrypt Authority</span>
                    </div>
                    <div className="flex justify-between text-neutral-400">
                      <span>Storage Quota:</span>
                      <span className="text-white font-mono">20 GB NVMe</span>
                    </div>
                    <div className="flex justify-between text-neutral-400">
                      <span>Backup Status:</span>
                      <span className="text-emerald-400 font-mono">Hourly WAL Enabled</span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="pt-4 mt-4 border-t border-white/[0.06]">
                <a
                  href="/login"
                  className="w-full inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 py-2 text-xs font-semibold text-emerald-400 hover:bg-emerald-500/20 transition-colors"
                >
                  <span>Open Configuration in Console</span>
                  <ExternalLink className="size-3" />
                </a>
              </div>
            </div>

          </div>

        </div>

      </div>
    </section>
  );
}
